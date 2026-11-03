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
}
