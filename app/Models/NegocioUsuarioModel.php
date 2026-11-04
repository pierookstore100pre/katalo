<?php

namespace App\Models;

use CodeIgniter\Model;

class NegocioUsuarioModel extends Model
{
    protected $table            = 'negocio_usuarios';
    protected $primaryKey       = 'id_negocio_usuario';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'negocio_id',
        'usuario_id',
        'rol_negocio',
        'activo',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';

    public function contarActivosPorNegocio(int $idNegocio): int
    {
        return $this->where('negocio_id', $idNegocio)
            ->where('activo', 1)
            ->countAllResults();
    }
}
