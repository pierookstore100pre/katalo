<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

class VerificarAislamiento extends BaseCommand
{
    protected $group       = 'Katalo';
    protected $name        = 'katalo:verificar-aislamiento';
    protected $description = 'Verifica de forma solo lectura el aislamiento de datos entre negocios.';
    protected $usage       = 'katalo:verificar-aislamiento';

    public function run(array $params)
    {
        CLI::write('Katalo.pe - Verificación de aislamiento entre negocios', 'yellow');
        CLI::newLine();

        try {
            $db = db_connect();

            $checks = [
                [
                    'Productos con categoría de otro negocio',
                    $this->contar($db,
                        'SELECT COUNT(*) AS total
                         FROM productos p
                         LEFT JOIN categorias c
                           ON c.id_categoria = p.categoria_id
                          AND c.negocio_id = p.negocio_id
                         WHERE c.id_categoria IS NULL
                           AND p.deleted_at IS NULL'
                    ),
                ],
                [
                    'Detalle de pedido con pedido o producto de otro negocio',
                    $this->contar($db,
                        'SELECT COUNT(*) AS total
                         FROM detalle_pedido d
                         LEFT JOIN pedidos p
                           ON p.id_pedido = d.pedido_id
                          AND p.negocio_id = d.negocio_id
                         LEFT JOIN productos pr
                           ON pr.id_producto = d.producto_id
                          AND pr.negocio_id = d.negocio_id
                         WHERE p.id_pedido IS NULL
                            OR pr.id_producto IS NULL'
                    ),
                ],
                [
                    'Negocios con más de un propietario activo',
                    $this->contarGrupos($db,
                        "SELECT negocio_id, COUNT(*) AS total
                         FROM negocio_usuarios
                         WHERE rol_negocio = 'propietario'
                           AND activo = 1
                         GROUP BY negocio_id
                         HAVING COUNT(*) > 1"
                    ),
                ],
                [
                    'Suscripciones vigentes duplicadas por negocio',
                    $this->contarGrupos($db,
                        "SELECT negocio_id, COUNT(*) AS total
                         FROM suscripciones
                         WHERE estado IN ('prueba', 'activa')
                           AND deleted_at IS NULL
                         GROUP BY negocio_id
                         HAVING COUNT(*) > 1"
                    ),
                ],
                [
                    'Productos con más de una imagen principal',
                    $this->contarGrupos($db,
                        'SELECT producto_id, COUNT(*) AS total
                         FROM imagenes_producto
                         WHERE es_principal = 1
                           AND deleted_at IS NULL
                         GROUP BY producto_id
                         HAVING COUNT(*) > 1'
                    ),
                ],
            ];
        } catch (Throwable $e) {
            log_message('error', 'Error al verificar aislamiento: {message}', ['message' => $e->getMessage()]);
            CLI::error('No se pudieron ejecutar las verificaciones de aislamiento.');

            return EXIT_ERROR;
        }

        $fallas = 0;

        foreach ($checks as $indice => [$nombre, $total]) {
            $ok = $total === 0;

            if (! $ok) {
                $fallas++;
            }

            CLI::write(
                sprintf(
                    '%d) %s: %s (%d)',
                    $indice + 1,
                    $nombre,
                    $ok ? 'OK' : 'FALLA',
                    $total
                ),
                $ok ? 'green' : 'red'
            );
        }

        CLI::newLine();

        if ($fallas === 0) {
            CLI::write('Resultado: todas las verificaciones pasaron.', 'green');

            return EXIT_SUCCESS;
        }

        CLI::error(sprintf('Resultado: %d verificación(es) con fallas.', $fallas));

        return EXIT_ERROR;
    }

    private function contar($db, string $sql): int
    {
        $fila = $db->query($sql)->getRowArray();

        return (int) ($fila['total'] ?? 0);
    }

    private function contarGrupos($db, string $sql): int
    {
        return count($db->query($sql)->getResultArray());
    }
}
