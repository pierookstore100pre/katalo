<?php

namespace App\Models;

use CodeIgniter\Model;

class CiudadModel extends Model
{
    protected $table            = 'ciudades';
    protected $primaryKey       = 'id_ciudad';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'slug',
        'region',
        'activo',
        'fecha_lanzamiento',
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
    public function listarTodas(): array
    {
        return $this->orderBy('nombre', 'ASC')->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarConNegocios(string $filtro = 'todas'): array
    {
        $sql = 'SELECT c.id_ciudad, c.nombre, c.slug, c.region, c.activo, c.fecha_lanzamiento,
                       (
                           SELECT COUNT(*)
                           FROM negocios n
                           JOIN distritos d ON d.id_distrito = n.distrito_id
                           WHERE d.ciudad_id = c.id_ciudad
                             AND n.activo = 1
                             AND n.deleted_at IS NULL
                       ) AS negocios_activos
                FROM ciudades c
                WHERE c.deleted_at IS NULL';

        if ($filtro === 'activas') {
            $sql .= ' AND c.activo = 1';
        } elseif ($filtro === 'inactivas') {
            $sql .= ' AND c.activo = 0';
        }

        $sql .= ' ORDER BY c.nombre ASC';

        return $this->db->query($sql)->getResultArray();
    }
}
