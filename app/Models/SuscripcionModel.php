<?php

namespace App\Models;

use CodeIgniter\Model;

class SuscripcionModel extends Model
{
    protected $table            = 'suscripciones';
    protected $primaryKey       = 'id_suscripcion';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'negocio_id',
        'plan_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'precio_pactado',
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
    public function obtenerVigentePorNegocio(int $idNegocio): ?array
    {
        return $this->where('negocio_id', $idNegocio)
            ->whereIn('estado', ['prueba', 'activa'])
            ->orderBy('id_suscripcion', 'DESC')
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPendientePagoPorNegocio(int $idNegocio): ?array
    {
        return $this->where('negocio_id', $idNegocio)
            ->where('estado', 'pendiente_pago')
            ->orderBy('id_suscripcion', 'DESC')
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarPendientesPago(): array
    {
        return $this->select(
            'suscripciones.*, negocios.nombre_comercial, negocios.slug, '
            . 'planes.nombre AS plan_nombre, planes.descripcion AS plan_descripcion'
        )
            ->join('negocios', 'negocios.id_negocio = suscripciones.negocio_id')
            ->join('planes', 'planes.id_plan = suscripciones.plan_id')
            ->where('suscripciones.estado', 'pendiente_pago')
            ->orderBy('suscripciones.created_at', 'ASC')
            ->orderBy('suscripciones.id_suscripcion', 'ASC')
            ->findAll();
    }
}
