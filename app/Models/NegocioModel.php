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
        'descripcion',
        'direccion',
        'distrito_id',
        'telefono_principal',
        'horario_texto',
        'siguiente_numero_pedido',
        'activo',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';

    public function actualizarPerfil(int $idNegocio, array $datos): bool
    {
        return $this->update($idNegocio, $datos) !== false;
    }

    public function slugDisponible(string $slug, ?int $excluirId = null): bool
    {
        $builder = $this->withDeleted()
            ->where('slug', $slug);

        if ($excluirId !== null) {
            $builder->where('id_negocio !=', $excluirId);
        }

        return $builder->countAllResults() === 0;
    }

    public function obtenerCiudadSlug(int $idNegocio): ?string
    {
        $fila = $this->db->query(
            'SELECT ciudades.slug
             FROM negocios
             JOIN distritos ON distritos.id_distrito = negocios.distrito_id
             JOIN ciudades ON ciudades.id_ciudad = distritos.ciudad_id
             WHERE negocios.id_negocio = ? AND negocios.deleted_at IS NULL
             LIMIT 1',
            [$idNegocio]
        )->getRowArray();

        return $fila === null ? null : (string) $fila['slug'];
    }

    public function obtenerSiguienteNumeroConBloqueo(int $idNegocio): ?int
    {
        $fila = $this->db->query(
            'SELECT siguiente_numero_pedido FROM negocios WHERE id_negocio = ? AND deleted_at IS NULL FOR UPDATE',
            [$idNegocio]
        )->getRowArray();

        return $fila === null ? null : (int) $fila['siguiente_numero_pedido'];
    }
}
