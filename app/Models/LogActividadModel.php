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
