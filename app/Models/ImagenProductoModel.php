<?php

namespace App\Models;

use CodeIgniter\Model;

class ImagenProductoModel extends Model
{
    protected $table            = 'imagenes_producto';
    protected $primaryKey       = 'id_imagen';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'producto_id',
        'ruta_imagen',
        'nombre_archivo',
        'es_principal',
        'orden',
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
    public function findDelProducto(int $idImagen, int $idProducto): ?array
    {
        return $this->where('id_imagen', $idImagen)
            ->where('producto_id', $idProducto)
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function principalDelProducto(int $idProducto): ?array
    {
        return $this->where('producto_id', $idProducto)
            ->where('es_principal', 1)
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarDelProducto(int $idProducto): array
    {
        return $this->where('producto_id', $idProducto)
            ->orderBy('es_principal', 'DESC')
            ->orderBy('orden', 'ASC')
            ->orderBy('id_imagen', 'ASC')
            ->findAll();
    }

    public function contarDelProducto(int $idProducto): int
    {
        return $this->where('producto_id', $idProducto)->countAllResults();
    }

    public function siguienteOrden(int $idProducto): int
    {
        $fila = $this->selectMax('orden', 'max_orden')
            ->where('producto_id', $idProducto)
            ->first();

        return ((int) ($fila['max_orden'] ?? -1)) + 1;
    }

    /**
     * @param list<int> $idsProductos
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarPrincipalesPorProductos(array $idsProductos): array
    {
        if ($idsProductos === []) {
            return [];
        }

        $imagenes = $this->whereIn('producto_id', $idsProductos)
            ->where('es_principal', 1)
            ->findAll();

        $principales = [];
        foreach ($imagenes as $imagen) {
            $principales[(int) $imagen['producto_id']] = $imagen;
        }

        return $principales;
    }
}
