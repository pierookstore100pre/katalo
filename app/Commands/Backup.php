<?php

namespace App\Commands;

use App\Models\LogActividadModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

class Backup extends BaseCommand
{
    protected $group       = 'Katalo';
    protected $name        = 'katalo:backup';
    protected $description = 'Genera un respaldo SQL de la base de datos y aplica retención de 7 archivos.';
    protected $usage       = 'katalo:backup';

    public function run(array $params)
    {
        $directorio = WRITEPATH . 'backups';

        if (! is_dir($directorio)
            && ! mkdir($directorio, 0755, true)
            && ! is_dir($directorio)
        ) {
            CLI::error('No se pudo crear el directorio de respaldos: writable/backups/.');

            return EXIT_ERROR;
        }

        try {
            $db = db_connect();
        } catch (Throwable $e) {
            log_message('error', 'Error al conectar para el respaldo: {message}', ['message' => $e->getMessage()]);
            CLI::error('No se pudo conectar a la base de datos para generar el respaldo.');

            return EXIT_ERROR;
        }

        $nombreBase = (string) $db->getDatabase();
        $nombreBase = preg_replace('/[^A-Za-z0-9_]/', '_', $nombreBase) ?: 'katalo_pe';
        $prefijo    = $nombreBase . '_' . date('Ymd_His');
        $nombre     = $prefijo . '.sql';
        $contador   = 1;

        while (is_file($directorio . DIRECTORY_SEPARATOR . $nombre)) {
            $nombre = $prefijo . '_' . $contador . '.sql';
            $contador++;
        }

        $ruta     = $directorio . DIRECTORY_SEPARATOR . $nombre;
        $generado = false;
        $binario  = $this->localizarMysqldump();

        if ($binario !== null) {
            $generado = $this->generarConMysqldump($binario, $ruta, $nombreBase);

            if (! $generado) {
                if (is_file($ruta)) {
                    @unlink($ruta);
                }

                CLI::write('mysqldump no pudo completar el respaldo; se usará el exportador PHP.', 'yellow');
            }
        }

        if (! $generado) {
            try {
                $generado = $this->generarConPhp($db, $ruta);
            } catch (Throwable $e) {
                log_message('error', 'Error en el respaldo PHP: {message}', ['message' => $e->getMessage()]);
                $generado = false;
            }

            if (! $generado) {
                if (is_file($ruta)) {
                    @unlink($ruta);
                }

                CLI::error('No se pudo generar el respaldo de la base de datos.');

                return EXIT_ERROR;
            }
        }

        $tamano      = (int) filesize($ruta);
        $conservados = $this->aplicarRetencion($directorio, 7);

        $this->registrarLog($nombre, $tamano);

        CLI::write(
            sprintf(
                'Respaldo generado: %s (%s). Se conservan %d archivos.',
                $nombre,
                $this->formatearTamano($tamano),
                $conservados
            ),
            'green'
        );

        return EXIT_SUCCESS;
    }

    private function localizarMysqldump(): ?string
    {
        $rutaEnv = getenv('MYSQLDUMP_PATH');

        if (is_string($rutaEnv) && trim($rutaEnv) !== '' && is_file(trim($rutaEnv))) {
            return trim($rutaEnv);
        }

        $esWindows  = DIRECTORY_SEPARATOR === '\\';
        $ejecutable = $esWindows ? 'mysqldump.exe' : 'mysqldump';
        $candidatos = [];

        $path = getenv('PATH');

        if (is_string($path) && $path !== '') {
            foreach (explode(PATH_SEPARATOR, $path) as $directorio) {
                $directorio = trim($directorio);

                if ($directorio !== '') {
                    $candidatos[] = rtrim($directorio, '\\/') . DIRECTORY_SEPARATOR . $ejecutable;
                }
            }
        }

        if ($esWindows) {
            $candidatos[] = dirname(ROOTPATH, 2) . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
            $candidatos[] = dirname(ROOTPATH, 3) . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
            $candidatos[] = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        } else {
            $candidatos[] = '/usr/bin/mysqldump';
            $candidatos[] = '/usr/local/bin/mysqldump';
            $candidatos[] = '/usr/local/mysql/bin/mysqldump';
        }

        foreach (array_unique($candidatos) as $candidato) {
            if (is_file($candidato)) {
                return $candidato;
            }
        }

        return null;
    }

    private function generarConMysqldump(string $binario, string $ruta, string $baseDatos): bool
    {
        $config   = config('Database');
        $usuario  = (string) ($config->default['username'] ?? '');
        $password = (string) ($config->default['password'] ?? '');
        $host     = (string) ($config->default['hostname'] ?? '');
        $puerto   = (string) ($config->default['port'] ?? '');

        $comando = [
            $binario,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
        ];

        if ($host !== '') {
            $comando[] = '--host=' . $host;
        }

        if ($puerto !== '') {
            $comando[] = '--port=' . $puerto;
        }

        $comando[] = '-u';
        $comando[] = $usuario;
        $comando[] = $password === '' ? '--password=' : '-p' . $password;
        $comando[] = $baseDatos;

        $descriptores = [
            0 => ['pipe', 'r'],
            1 => ['file', $ruta, 'w'],
            2 => ['pipe', 'w'],
        ];

        $opciones = DIRECTORY_SEPARATOR === '\\' ? ['bypass_shell' => true] : [];
        $proceso  = proc_open($comando, $descriptores, $tuberias, null, null, $opciones);

        if (! is_resource($proceso)) {
            return false;
        }

        fclose($tuberias[0]);
        $error = stream_get_contents($tuberias[2]);
        fclose($tuberias[2]);

        $codigo = proc_close($proceso);

        if ($codigo !== 0) {
            log_message('error', 'mysqldump falló con código {codigo}: {error}', [
                'codigo' => $codigo,
                'error'  => trim((string) $error),
            ]);

            return false;
        }

        return is_file($ruta) && (int) filesize($ruta) > 0;
    }

    private function generarConPhp($db, string $ruta): bool
    {
        $manejador = fopen($ruta, 'wb');

        if ($manejador === false) {
            return false;
        }

        $baseDatos = (string) $db->getDatabase();

        fwrite($manejador, "-- Respaldo Katalo.pe\n");
        fwrite($manejador, '-- Base de datos: ' . $baseDatos . "\n");
        fwrite($manejador, '-- Generado: ' . date('Y-m-d H:i:s') . "\n\n");
        fwrite($manejador, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tablas = $db->query('SHOW FULL TABLES')->getResultArray();

        foreach ($tablas as $filaTabla) {
            $valores = array_values($filaTabla);
            $tabla   = (string) ($valores[0] ?? '');
            $tipo    = strtoupper((string) ($valores[1] ?? ''));

            if ($tabla === '' || $tipo !== 'BASE TABLE') {
                continue;
            }

            $tablaSegura = str_replace('`', '``', $tabla);
            $creacion    = $db->query('SHOW CREATE TABLE `' . $tablaSegura . '`')->getRowArray();

            if ($creacion === null) {
                continue;
            }

            $valoresCreacion = array_values($creacion);
            $sqlCreacion     = (string) ($valoresCreacion[1] ?? '');

            if ($sqlCreacion === '') {
                continue;
            }

            fwrite($manejador, '-- Tabla: ' . $tabla . "\n");
            fwrite($manejador, 'DROP TABLE IF EXISTS `' . $tablaSegura . "`;\n");
            fwrite($manejador, $sqlCreacion . ";\n\n");

            $columnasGeneradas = $this->obtenerColumnasGeneradas($db, $tablaSegura);
            $filas             = $db->query('SELECT * FROM `' . $tablaSegura . '`')->getResultArray();

            foreach ($filas as $fila) {
                foreach ($columnasGeneradas as $columnaGenerada) {
                    unset($fila[$columnaGenerada]);
                }

                if ($fila === []) {
                    continue;
                }

                $columnas = array_keys($fila);
                $columnasSql = implode(', ', array_map(
                    static fn ($columna): string => '`' . str_replace('`', '``', (string) $columna) . '`',
                    $columnas
                ));
                $valoresSql = implode(', ', array_map(
                    static fn ($valor): string => $valor === null ? 'NULL' : $db->escape($valor),
                    array_values($fila)
                ));

                fwrite(
                    $manejador,
                    'INSERT INTO `' . $tablaSegura . '` (' . $columnasSql . ') VALUES (' . $valoresSql . ");\n"
                );
            }

            fwrite($manejador, "\n");
        }

        fwrite($manejador, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($manejador);

        return is_file($ruta) && (int) filesize($ruta) > 0;
    }

    /**
     * @return list<string>
     */
    private function obtenerColumnasGeneradas($db, string $tablaSegura): array
    {
        $columnas = $db->query('SHOW COLUMNS FROM `' . $tablaSegura . '`')->getResultArray();
        $generadas = [];

        foreach ($columnas as $columna) {
            $extra = strtoupper((string) ($columna['Extra'] ?? ''));

            if (str_contains($extra, 'GENERATED')) {
                $generadas[] = (string) ($columna['Field'] ?? '');
            }
        }

        return array_values(array_filter($generadas, static fn (string $columna): bool => $columna !== ''));
    }

    private function aplicarRetencion(string $directorio, int $maximo): int
    {
        $archivos = glob($directorio . DIRECTORY_SEPARATOR . '*.sql') ?: [];

        usort($archivos, static function (string $a, string $b): int {
            $fechaA = (int) filemtime($a);
            $fechaB = (int) filemtime($b);

            return $fechaB <=> $fechaA ?: strcmp($b, $a);
        });

        foreach (array_slice($archivos, max(0, $maximo)) as $archivoAntiguo) {
            @unlink($archivoAntiguo);
        }

        return count(glob($directorio . DIRECTORY_SEPARATOR . '*.sql') ?: []);
    }

    private function registrarLog(string $archivo, int $tamano): void
    {
        try {
            $logModel = model(LogActividadModel::class);
            $logModel->insert([
                'usuario_id'     => null,
                'negocio_id'     => null,
                'accion'         => 'backup_generado',
                'tabla_afectada' => null,
                'registro_id'    => null,
                'descripcion'    => mb_substr($archivo . ' (' . $this->formatearTamano($tamano) . ')', 0, 500),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'No se pudo registrar el respaldo en logs_actividad: {message}', ['message' => $e->getMessage()]);
        }
    }

    private function formatearTamano(int $bytes): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $valor    = max(0, $bytes);
        $indice   = 0;

        while ($valor >= 1024 && $indice < count($unidades) - 1) {
            $valor /= 1024;
            $indice++;
        }

        return number_format($valor, $indice === 0 ? 0 : 2, '.', '') . ' ' . $unidades[$indice];
    }
}
