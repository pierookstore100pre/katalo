<?php

namespace App\Models;

use CodeIgniter\Model;

class PagoModel extends Model
{
    protected $table            = 'pagos';
    protected $primaryKey       = 'id_pago';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'suscripcion_id',
        'monto',
        'metodo',
        'referencia',
        'comprobante_ruta',
        'periodo_desde',
        'periodo_hasta',
        'fecha_pago',
        'estado',
        'confirmado_por',
        'confirmado_at',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';

    /**
     * @return list<array<string, mixed>>
     */
    public function listarConDetalle(string $estado = 'todos'): array
    {
        $builder = $this->select(
            'pagos.*, suscripciones.negocio_id, suscripciones.plan_id, '
            . 'negocios.nombre_comercial, planes.nombre AS plan_nombre'
        )
            ->join('suscripciones', 'suscripciones.id_suscripcion = pagos.suscripcion_id')
            ->join('negocios', 'negocios.id_negocio = suscripciones.negocio_id')
            ->join('planes', 'planes.id_plan = suscripciones.plan_id');

        if (in_array($estado, ['pendiente', 'confirmado', 'rechazado'], true)) {
            $builder->where('pagos.estado', $estado);
        }

        return $builder
            ->orderBy('pagos.created_at', 'DESC')
            ->orderBy('pagos.id_pago', 'DESC')
            ->findAll();
    }
}
