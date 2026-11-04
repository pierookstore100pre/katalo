<?php

namespace App\Models;

use CodeIgniter\Model;

class DistritoModel extends Model
{
    protected $table            = 'distritos';
    protected $primaryKey       = 'id_distrito';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ciudad_id',
        'nombre',
        'descripcion',
        'activo',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Distritos activos cuya ciudad también está activa.
     *
     * @return list<array<string, mixed>>
     */
    public function getActivosConCiudadActiva(): array
    {
        return $this->select('distritos.id_distrito, distritos.nombre, ciudades.nombre AS ciudad')
            ->join('ciudades', 'ciudades.id_ciudad = distritos.ciudad_id')
            ->where('distritos.activo', 1)
            ->where('ciudades.activo', 1)
            ->where('ciudades.deleted_at', null)
            ->orderBy('ciudades.nombre', 'ASC')
            ->orderBy('distritos.nombre', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarConNegocios(?int $ciudadId = null, string $estado = 'todos'): array
    {
        $builder = $this->select(
            'distritos.*, ciudades.nombre AS ciudad_nombre, ciudades.slug AS ciudad_slug, '
            . '(
                SELECT COUNT(*)
                FROM negocios n
                WHERE n.distrito_id = distritos.id_distrito
                  AND n.deleted_at IS NULL
            ) AS negocios_count'
        )
            ->join('ciudades', 'ciudades.id_ciudad = distritos.ciudad_id')
            ->where('ciudades.deleted_at', null);

        if ($ciudadId !== null && $ciudadId > 0) {
            $builder->where('distritos.ciudad_id', $ciudadId);
        }

        if ($estado === 'activos') {
            $builder->where('distritos.activo', 1);
        } elseif ($estado === 'inactivos') {
            $builder->where('distritos.activo', 0);
        }

        return $builder
            ->orderBy('ciudades.nombre', 'ASC')
            ->orderBy('distritos.nombre', 'ASC')
            ->findAll();
    }

    public function contarNegocios(int $idDistrito): int
    {
        return $this->db->table('negocios')
            ->where('distrito_id', $idDistrito)
            ->where('deleted_at', null)
            ->countAllResults();
    }
}
