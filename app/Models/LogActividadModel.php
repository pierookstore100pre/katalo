<?php

namespace App\Models;

use CodeIgniter\Model;

class LogActividadModel extends Model
{
    protected $table            = 'logs_actividad';
    protected $primaryKey       = 'id_log';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'usuario_id',
        'negocio_id',
        'accion',
        'tabla_afectada',
        'registro_id',
        'descripcion',
        'datos',
        'ip_address',
        'user_agent',
    ];

    protected $useTimestamps = false;

    /**
     * @param array{desde?: string, hasta?: string, accion?: string, negocio_id?: int|null, usuario_id?: int|null} $filtros
     *
     * @return list<array<string, mixed>>
     */
    public function listarConFiltros(array $filtros = [], int $perPage = 50): array
    {
        $builder = $this->select(
            'logs_actividad.*, usuarios.email AS usuario_email, '
            . 'negocios.nombre_comercial AS negocio_nombre'
        )
            ->join('usuarios', 'usuarios.id_usuario = logs_actividad.usuario_id', 'left')
            ->join('negocios', 'negocios.id_negocio = logs_actividad.negocio_id', 'left');

        $this->aplicarFiltros($builder, $filtros);

        return $builder
            ->orderBy('logs_actividad.created_at', 'DESC')
            ->orderBy('logs_actividad.id_log', 'DESC')
            ->paginate($perPage, 'logs_admin');
    }

    /**
     * @param array{desde?: string, hasta?: string, accion?: string} $filtros
     *
     * @return list<array<string, mixed>>
     */
    public function listarPorNegocio(int $idNegocio, array $filtros = [], int $perPage = 50): array
    {
        $builder = $this->select(
            'logs_actividad.*, usuarios.email AS usuario_email, '
            . 'negocios.nombre_comercial AS negocio_nombre'
        )
            ->join('usuarios', 'usuarios.id_usuario = logs_actividad.usuario_id', 'left')
            ->join('negocios', 'negocios.id_negocio = logs_actividad.negocio_id', 'left')
            ->where('logs_actividad.negocio_id', $idNegocio);

        $this->aplicarFiltros($builder, $filtros);

        return $builder
            ->orderBy('logs_actividad.created_at', 'DESC')
            ->orderBy('logs_actividad.id_log', 'DESC')
            ->paginate($perPage, 'logs_negocio');
    }

    /**
     * @return list<string>
     */
    public function obtenerAcciones(): array
    {
        $filas = $this->db->query(
            'SELECT DISTINCT accion FROM logs_actividad ORDER BY accion ASC'
        )->getResultArray();

        return array_map(static fn (array $fila): string => (string) $fila['accion'], $filas);
    }

    /**
     * @return list<string>
     */
    public function obtenerAccionesPorNegocio(int $idNegocio): array
    {
        $filas = $this->db->query(
            'SELECT DISTINCT accion FROM logs_actividad WHERE negocio_id = ? ORDER BY accion ASC',
            [$idNegocio]
        )->getResultArray();

        return array_map(static fn (array $fila): string => (string) $fila['accion'], $filas);
    }

    /**
     * Obtiene los registros marcados por el filtro de moderación y que siguen
     * pendientes (productos/categorías ocultos o negocios inactivos).
     *
     * @return list<array<string, mixed>>
     */
    public function obtenerMarcadosPorTabla(string $tabla, int $dias = 30): array
    {
        $dias = max(1, $dias);

        switch ($tabla) {
            case 'productos':
                $select = 'p.id_producto AS id, p.titulo AS titulo, p.descripcion AS descripcion, '
                    . 'p.negocio_id AS negocio_id, n.nombre_comercial AS negocio_nombre, '
                    . 'p.visible AS estado_visible';
                $join = 'JOIN productos p ON p.id_producto = l.registro_id '
                    . 'JOIN negocios n ON n.id_negocio = p.negocio_id';
                $where = 'p.visible = 0 AND p.deleted_at IS NULL';
                $acciones = "'contenido_marcado', 'contenido_aprobado', 'contenido_rechazado'";
                break;

            case 'categorias':
                $select = 'c.id_categoria AS id, c.nombre AS titulo, c.descripcion AS descripcion, '
                    . 'c.negocio_id AS negocio_id, n.nombre_comercial AS negocio_nombre, '
                    . 'c.activo AS estado_visible';
                $join = 'JOIN categorias c ON c.id_categoria = l.registro_id '
                    . 'JOIN negocios n ON n.id_negocio = c.negocio_id';
                $where = 'c.activo = 0 AND c.deleted_at IS NULL';
                $acciones = "'contenido_marcado', 'contenido_aprobado', 'contenido_rechazado'";
                break;

            case 'negocios':
                $select = 'n.id_negocio AS id, n.nombre_comercial AS titulo, n.descripcion AS descripcion, '
                    . 'n.id_negocio AS negocio_id, n.nombre_comercial AS negocio_nombre, '
                    . 'n.activo AS estado_visible';
                $join = 'JOIN negocios n ON n.id_negocio = l.registro_id';
                $where = 'n.activo = 0 AND n.deleted_at IS NULL';
                $acciones = "'contenido_marcado', 'negocio_aprobado', 'suspender_negocio', 'contenido_rechazado'";
                break;

            default:
                return [];
        }

        $sql = "SELECT {$select}, l.descripcion AS motivo, l.created_at AS log_created_at,
                    (
                        SELECT l2.accion
                        FROM logs_actividad l2
                        WHERE l2.tabla_afectada = ?
                          AND l2.registro_id = l.registro_id
                          AND l2.accion IN ({$acciones})
                        ORDER BY l2.id_log DESC
                        LIMIT 1
                    ) AS estado_moderacion
                FROM logs_actividad l
                JOIN (
                    SELECT registro_id, MAX(id_log) AS max_id
                    FROM logs_actividad
                    WHERE tabla_afectada = ?
                      AND accion = 'contenido_marcado'
                      AND created_at >= DATE_SUB(NOW(), INTERVAL {$dias} DAY)
                    GROUP BY registro_id
                ) lm ON lm.max_id = l.id_log
                {$join}
                WHERE {$where}
                ORDER BY l.created_at DESC, l.id_log DESC
                LIMIT 50";

        return $this->db->query($sql, [$tabla, $tabla])->getResultArray();
    }

    /**
     * @param array{desde?: string, hasta?: string, accion?: string, negocio_id?: int|null, usuario_id?: int|null} $filtros
     */
    private function aplicarFiltros($builder, array $filtros): void
    {
        $desde = trim((string) ($filtros['desde'] ?? ''));
        if ($desde !== '') {
            $builder->where('logs_actividad.created_at >=', $desde . ' 00:00:00');
        }

        $hasta = trim((string) ($filtros['hasta'] ?? ''));
        if ($hasta !== '') {
            $builder->where('logs_actividad.created_at <=', $hasta . ' 23:59:59');
        }

        $accion = trim((string) ($filtros['accion'] ?? ''));
        if ($accion !== '') {
            $builder->where('logs_actividad.accion', $accion);
        }

        $negocioId = $filtros['negocio_id'] ?? null;
        if ($negocioId !== null && (int) $negocioId > 0) {
            $builder->where('logs_actividad.negocio_id', (int) $negocioId);
        }

        $usuarioId = $filtros['usuario_id'] ?? null;
        if ($usuarioId !== null && (int) $usuarioId > 0) {
            $builder->where('logs_actividad.usuario_id', (int) $usuarioId);
        }
    }
}
