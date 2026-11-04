<?php

namespace App\Models;

use CodeIgniter\Model;

class PedidoModel extends Model
{
    protected $table            = 'pedidos';
    protected $primaryKey       = 'id_pedido';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'negocio_id',
        'numero',
        'codigo',
        'cliente_nombre',
        'cliente_telefono',
        'cliente_direccion',
        'comentario',
        'total',
        'estado',
        'canal',
        'ip_cliente',
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
    public function listarDelNegocio(int $idNegocio, string $estado = 'todos'): array
    {
        $estados = ['nuevo', 'confirmado', 'en_proceso', 'entregado', 'cancelado'];
        $builder = $this->where('negocio_id', $idNegocio);

        if (in_array($estado, $estados, true)) {
            $builder->where('estado', $estado);
        }

        return $builder
            ->orderBy('created_at', 'DESC')
            ->orderBy('id_pedido', 'DESC')
            ->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDelNegocio(int $idPedido, int $idNegocio): ?array
    {
        return $this->where('id_pedido', $idPedido)
            ->where('negocio_id', $idNegocio)
            ->first();
    }

    public function contarDelMes(int $idNegocio): int
    {
        $inicio = date('Y-m-01');
        $fin    = date('Y-m-01', strtotime('+1 month'));

        return $this->where('negocio_id', $idNegocio)
            ->where('created_at >=', $inicio . ' 00:00:00')
            ->where('created_at <', $fin . ' 00:00:00')
            ->countAllResults();
    }

    /**
     * Cuenta los pedidos no eliminados creados desde una IP en la última hora.
     *
     * Se incluyen los pedidos cancelados a propósito: el abuso de creación de
     * pedidos no distingue el estado del pedido.
     */
    public function contarPorIpEnUltimaHora(string $ip): int
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS total
             FROM pedidos
             WHERE ip_cliente = ?
               AND created_at >= (NOW() - INTERVAL 1 HOUR)
               AND deleted_at IS NULL',
            [$ip]
        )->getRowArray();

        return (int) ($fila['total'] ?? 0);
    }
}
