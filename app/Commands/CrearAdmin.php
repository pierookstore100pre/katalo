<?php

namespace App\Commands;

use App\Models\PersonaModel;
use App\Models\RolModel;
use App\Models\UsuarioModel;
use App\Models\UsuarioRolModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;
use Throwable;

class CrearAdmin extends BaseCommand
{
    protected $group       = 'Katalo';
    protected $name        = 'katalo:crear-admin';
    protected $description = 'Crea un usuario administrador de plataforma sin negocio.';
    protected $usage       = 'katalo:crear-admin --email=admin@katalo.pe --password=Admin1234 --nombres=Admin --apellidos=Katalo --telefono=999999999';
    protected $options     = [
        '--email'     => 'Correo electrónico del administrador.',
        '--password'  => 'Contraseña del administrador (mínimo 8 caracteres y un número).',
        '--nombres'   => 'Nombres del administrador.',
        '--apellidos' => 'Apellidos del administrador.',
        '--telefono'  => 'Teléfono del administrador (7 a 15 dígitos).',
    ];

    public function run(array $params)
    {
        $email     = strtolower(trim((string) $this->opcion($params, 'email')));
        $password  = (string) $this->opcion($params, 'password');
        $nombres   = trim((string) $this->opcion($params, 'nombres'));
        $apellidos = trim((string) $this->opcion($params, 'apellidos'));
        $telefono  = trim((string) $this->opcion($params, 'telefono'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            CLI::error('Indica un correo electrónico válido con --email.');

            return;
        }

        if (mb_strlen($password) < 8 || ! preg_match('/[0-9]/', $password)) {
            CLI::error('La contraseña debe tener al menos 8 caracteres e incluir un número.');

            return;
        }

        if ($nombres === '' || mb_strlen($nombres) > 100) {
            CLI::error('Indica los nombres con --nombres (máximo 100 caracteres).');

            return;
        }

        if ($apellidos === '' || mb_strlen($apellidos) > 100) {
            CLI::error('Indica los apellidos con --apellidos (máximo 100 caracteres).');

            return;
        }

        if (! preg_match('/^[0-9]{7,15}$/', $telefono)) {
            CLI::error('Indica un teléfono válido de 7 a 15 dígitos con --telefono.');

            return;
        }

        $rol = model(RolModel::class)
            ->where('nombre', 'administrador')
            ->where('activo', 1)
            ->first();

        if ($rol === null) {
            CLI::error('No se encontró el rol activo "administrador".');

            return;
        }

        $idRol          = (int) $rol['id_rol'];
        $usuarioModel   = model(UsuarioModel::class);
        $usuarioRolModel = model(UsuarioRolModel::class);
        $usuarioExistente = $usuarioModel
            ->withDeleted()
            ->where('email', $email)
            ->first();

        if ($usuarioExistente !== null) {
            $idUsuario = (int) $usuarioExistente['id_usuario'];
            $yaTieneRol = $usuarioRolModel
                ->where('usuario_id', $idUsuario)
                ->where('rol_id', $idRol)
                ->first() !== null;

            if (! $yaTieneRol) {
                $usuarioRolModel->insert([
                    'usuario_id' => $idUsuario,
                    'rol_id'     => $idRol,
                ]);
                CLI::write('El usuario ya existía; se agregó el rol administrador.', 'green');
            } else {
                CLI::write('El usuario ya existe y ya tiene el rol administrador.', 'yellow');
            }

            return;
        }

        $db = db_connect();
        $distrito = $db->table('distritos d')
            ->select('d.id_distrito')
            ->join('ciudades c', 'c.id_ciudad = d.ciudad_id')
            ->where('c.nombre', 'Tacna')
            ->where('c.activo', 1)
            ->where('c.deleted_at', null)
            ->where('d.activo', 1)
            ->where('d.deleted_at', null)
            ->orderBy('d.id_distrito', 'ASC')
            ->get(1)
            ->getRowArray();

        if ($distrito === null) {
            CLI::error('No se encontró un distrito activo de Tacna.');

            return;
        }

        $db->transBegin();

        try {
            $idPersona = (int) model(PersonaModel::class)->insert([
                'nombres'     => $nombres,
                'apellidos'   => $apellidos,
                'telefono'    => $telefono,
                'distrito_id' => (int) $distrito['id_distrito'],
            ], true);

            if ($idPersona <= 0) {
                throw new RuntimeException('No se pudo crear la persona.');
            }

            $idUsuario = (int) $usuarioModel->insert([
                'persona_id'    => $idPersona,
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'activo'        => 1,
                'created_by'    => $idPersona,
                'modified_by'   => $idPersona,
            ], true);

            if ($idUsuario <= 0) {
                throw new RuntimeException('No se pudo crear el usuario.');
            }

            if ($usuarioRolModel->insert([
                'usuario_id' => $idUsuario,
                'rol_id'     => $idRol,
                'created_by' => $idUsuario,
                'modified_by' => $idUsuario,
            ]) === false) {
                throw new RuntimeException('No se pudo asignar el rol administrador.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al crear administrador: {message}', ['message' => $e->getMessage()]);
            CLI::error('No se pudo crear el administrador. Revisa el log.');

            return;
        }

        CLI::write('Administrador creado correctamente: ' . $email, 'green');
    }

    /**
     * @param array<array-key, string|null> $params
     */
    private function opcion(array $params, string $nombre): ?string
    {
        if (isset($params[$nombre]) && is_string($params[$nombre])) {
            return $params[$nombre];
        }

        foreach ($params as $clave => $valor) {
            if (is_string($clave) && str_starts_with($clave, $nombre . '=')) {
                return substr($clave, mb_strlen($nombre) + 1);
            }
        }

        $valor = CLI::getOption($nombre);

        return is_string($valor) ? $valor : null;
    }
}
