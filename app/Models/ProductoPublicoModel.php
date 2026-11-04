<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductoPublicoModel extends Model
{
    protected $table            = 'v_productos_publicos';
    protected $primaryKey       = 'id_producto';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [];

    protected $useTimestamps = false;

    /**
     * @return list<array<string, mixed>>
     */
    public function listarPorNegocio(int $idNegocio, ?string $categoriaSlug = null, string $busqueda = ''): array
    {
        $builder = $this->select(
            'v_productos_publicos.*, categorias.nombre AS categoria_nombre, categorias.slug AS categoria_slug'
        )
            ->join(
                'categorias',
                'categorias.id_categoria = v_productos_publicos.categoria_id'
                . ' AND categorias.negocio_id = v_productos_publicos.negocio_id'
                . ' AND categorias.deleted_at IS NULL',
                'left'
            )
            ->where('v_productos_publicos.negocio_id', $idNegocio);

        if ($categoriaSlug !== null && $categoriaSlug !== '') {
            $builder->where('categorias.slug', $categoriaSlug)
                ->where('categorias.activo', 1);
        }

        $busqueda = trim($busqueda);

        if ($busqueda !== '') {
            $escapada = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busqueda);
            $builder->groupStart()
                ->like('v_productos_publicos.titulo', $escapada, 'both', true)
                ->orLike('v_productos_publicos.descripcion', $escapada, 'both', true)
                ->groupEnd();
        }

        return $builder
            ->orderBy('v_productos_publicos.destacado', 'DESC')
            ->orderBy('v_productos_publicos.titulo', 'ASC')
            ->findAll();
    }
}
