<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioRolModel extends Model
{
    protected $table            = 'usuario_roles';
    protected $primaryKey       = 'id_usuario_rol';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'usuario_id',
        'rol_id',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
}
