<?php

namespace App\Models;

use CodeIgniter\Model;

class NegocioPublicoModel extends Model
{
    protected $table            = 'v_negocios_publicos';
    protected $primaryKey       = 'id_negocio';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [];

    protected $useTimestamps = false;

    /**
     * @return array<string, mixed>|null
     */
    public function findPorSlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    /**
     * Busca un negocio público validando que la ciudad de la URL coincida
     * con la ciudad del negocio (negocios.distrito_id → distritos.ciudad_id).
     *
     * @return array<string, mixed>|null
     */
    public function findPorCiudadYSlug(string $ciudadSlug, string $slug): ?array
    {
        return $this->select('v_negocios_publicos.*')
            ->join('negocios', 'negocios.id_negocio = v_negocios_publicos.id_negocio')
            ->join('distritos', 'distritos.id_distrito = negocios.distrito_id')
            ->join('ciudades', 'ciudades.id_ciudad = distritos.ciudad_id')
            ->where('v_negocios_publicos.slug', $slug)
            ->where('ciudades.slug', $ciudadSlug)
            ->where('ciudades.activo', 1)
            ->where('ciudades.deleted_at', null)
            ->first();
    }

    public function obtenerCiudadSlugPorNegocio(string $slug): ?string
    {
        $fila = $this->db->query(
            'SELECT ciudades.slug
             FROM v_negocios_publicos
             JOIN negocios ON negocios.id_negocio = v_negocios_publicos.id_negocio
             JOIN distritos ON distritos.id_distrito = negocios.distrito_id
             JOIN ciudades ON ciudades.id_ciudad = distritos.ciudad_id
             WHERE v_negocios_publicos.slug = ?
               AND ciudades.activo = 1
               AND ciudades.deleted_at IS NULL
             LIMIT 1',
            [$slug]
        )->getRowArray();

        return $fila === null ? null : (string) $fila['slug'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarPorCiudadSlug(string $ciudadSlug): array
    {
        return $this->select('v_negocios_publicos.*, ciudades.nombre AS ciudad_nombre')
            ->join('negocios', 'negocios.id_negocio = v_negocios_publicos.id_negocio')
            ->join('distritos', 'distritos.id_distrito = negocios.distrito_id')
            ->join('ciudades', 'ciudades.id_ciudad = distritos.ciudad_id')
            ->where('ciudades.slug', $ciudadSlug)
            ->where('ciudades.activo', 1)
            ->where('ciudades.deleted_at', null)
            ->orderBy('v_negocios_publicos.nombre_comercial', 'ASC')
            ->findAll();
    }
}
