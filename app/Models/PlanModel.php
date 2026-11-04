<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table            = 'planes';
    protected $primaryKey       = 'id_plan';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'slug',
        'descripcion',
        'precio_mensual',
        'max_productos',
        'max_imagenes_por_producto',
        'max_usuarios',
        'max_pedidos_mes',
        'tiene_dominio_propio',
        'tiene_analytics',
        'tiene_reportes',
        'activo',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPorSlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerVigentePorNegocio(int $idNegocio): ?array
    {
        return $this->select('planes.*')
            ->join('suscripciones', 'suscripciones.plan_id = planes.id_plan')
            ->where('suscripciones.negocio_id', $idNegocio)
            ->whereIn('suscripciones.estado', ['prueba', 'activa'])
            ->where('suscripciones.deleted_at', null)
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarConSuscripciones(): array
    {
        return $this->select(
            'planes.*, '
            . '(
                SELECT COUNT(*)
                FROM suscripciones s
                WHERE s.plan_id = planes.id_plan
                  AND s.estado = \'activa\'
                  AND s.deleted_at IS NULL
            ) AS suscripciones_activas'
        )
            ->orderBy('planes.precio_mensual', 'ASC')
            ->orderBy('planes.id_plan', 'ASC')
            ->findAll();
    }
}
