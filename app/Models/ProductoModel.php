<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductoModel extends Model
{
    protected $table            = 'productos';
    protected $primaryKey       = 'id_producto';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'negocio_id',
        'categoria_id',
        'titulo',
        'descripcion',
        'precio_referencial',
        'estado',
        'visible',
        'destacado',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';

    /**
     * @return array<string, mixed>|null
     */
    public function findDelNegocio(int $idProducto, int $idNegocio): ?array
    {
        return $this->where('id_producto', $idProducto)
            ->where('negocio_id', $idNegocio)
            ->first();
    }

    public function contarPorNegocio(int $idNegocio): int
    {
        return $this->where('negocio_id', $idNegocio)->countAllResults();
    }

    /**
     * @param list<int|string> $ids
     *
     * @return list<array<string, mixed>>
     */
    public function obtenerDisponiblesPorIds(int $idNegocio, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        return $this->select('id_producto, titulo, precio_referencial')
            ->where('negocio_id', $idNegocio)
            ->whereIn('id_producto', $ids)
            ->where('visible', 1)
            ->where('estado', 'activo')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarDelNegocio(int $idNegocio, string $filtro = 'todos'): array
    {
        $builder = $this->select('productos.*, categorias.nombre AS categoria_nombre')
            ->join(
                'categorias',
                'categorias.id_categoria = productos.categoria_id AND categorias.negocio_id = productos.negocio_id',
                'left'
            )
            ->where('productos.negocio_id', $idNegocio);

        if ($filtro === 'visibles') {
            $builder->where('productos.visible', 1);
        } elseif ($filtro === 'ocultos') {
            $builder->where('productos.visible', 0);
        } elseif ($filtro === 'agotados') {
            $builder->where('productos.estado', 'agotado');
        }

        return $builder
            ->orderBy('productos.created_at', 'DESC')
            ->orderBy('productos.id_producto', 'DESC')
            ->findAll();
    }
}
