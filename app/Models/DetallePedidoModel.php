<?php

namespace App\Models;

use CodeIgniter\Model;

class DetallePedidoModel extends Model
{
    protected $table            = 'detalle_pedido';
    protected $primaryKey       = 'id_detalle';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'negocio_id',
        'pedido_id',
        'producto_id',
        'producto_titulo',
        'cantidad',
        'precio_unitario',
        'nota',
    ];

    protected $useTimestamps = false;

    /**
     * @return list<array<string, mixed>>
     */
    public function listarDelPedido(int $idPedido, int $idNegocio): array
    {
        return $this->where('pedido_id', $idPedido)
            ->where('negocio_id', $idNegocio)
            ->orderBy('id_detalle', 'ASC')
            ->findAll();
    }
}
