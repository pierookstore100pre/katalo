<?php

namespace App\Models;

use CodeIgniter\Model;

class NegocioModel extends Model
{
    protected $table            = 'negocios';
    protected $primaryKey       = 'id_negocio';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre_comercial',
        'slug',
        'direccion',
        'distrito_id',
        'telefono_principal',
        'siguiente_numero_pedido',
        'activo',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';
}
