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

    /**
     * @return list<array<string, mixed>>
     */
    public function listarColaboradores(int $idNegocio): array
    {
        return $this->select(
            'negocio_usuarios.id_negocio_usuario, negocio_usuarios.usuario_id, '
            . 'negocio_usuarios.rol_negocio, negocio_usuarios.activo, negocio_usuarios.created_at, '
            . 'usuarios.email, personas.nombres, personas.apellidos, personas.telefono'
        )
            ->join('usuarios', 'usuarios.id_usuario = negocio_usuarios.usuario_id')
            ->join('personas', 'personas.id_persona = usuarios.persona_id')
            ->where('negocio_usuarios.negocio_id', $idNegocio)
            ->where('negocio_usuarios.rol_negocio', 'colaborador')
            ->orderBy('negocio_usuarios.activo', 'DESC')
            ->orderBy('personas.apellidos', 'ASC')
            ->orderBy('personas.nombres', 'ASC')
            ->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findColaboradorDelNegocio(int $idNegocioUsuario, int $idNegocio): ?array
    {
        return $this->where('id_negocio_usuario', $idNegocioUsuario)
            ->where('negocio_id', $idNegocio)
            ->where('rol_negocio', 'colaborador')
            ->first();
    }
}
