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
}
