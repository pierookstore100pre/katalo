<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id_usuario';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'persona_id',
        'email',
        'password_hash',
        'activo',
        'intentos_fallidos',
        'bloqueado_hasta',
        'ultimo_login',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';

    public function obtenerEmailPorId(int $idUsuario): ?string
    {
        $fila = $this->select('email')->find($idUsuario);

        return $fila === null ? null : (string) $fila['email'];
    }
}
