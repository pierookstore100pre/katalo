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
     * @return array{total_vendido: float, cantidad_pedidos: int, ticket_promedio: float}
     */
    public function obtenerMetricasReporte(int $idNegocio, string $desde, string $hasta): array
    {
        $inicio = $desde . ' 00:00:00';
        $fin    = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';

        $fila = $this->db->query(
            'SELECT COUNT(*) AS cantidad_pedidos, COALESCE(SUM(total), 0) AS total_vendido
             FROM pedidos
             WHERE negocio_id = ?
               AND deleted_at IS NULL
               AND estado <> ?
               AND created_at >= ?
               AND created_at < ?',
            [$idNegocio, 'cancelado', $inicio, $fin]
        )->getRowArray();

        $cantidad = (int) ($fila['cantidad_pedidos'] ?? 0);
        $total    = (float) ($fila['total_vendido'] ?? 0);

        return [
            'total_vendido'    => $total,
            'cantidad_pedidos' => $cantidad,
            'ticket_promedio'  => $cantidad > 0 ? $total / $cantidad : 0.0,
        ];
    }

    /**
     * @return list<array{producto_titulo: string, total_cantidad: int, total_importe: float}>
     */
    public function obtenerTopProductos(int $idNegocio, string $desde, string $hasta, int $limite = 5): array
    {
        $inicio = $desde . ' 00:00:00';
        $fin    = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';
        $limite = max(1, min(20, $limite));

        $filas = $this->db->query(
            'SELECT d.producto_titulo,
                    SUM(d.cantidad) AS total_cantidad,
                    COALESCE(SUM(d.subtotal), 0) AS total_importe
             FROM detalle_pedido d
             JOIN pedidos p
               ON p.id_pedido = d.pedido_id
              AND p.negocio_id = d.negocio_id
             WHERE d.negocio_id = ?
               AND p.deleted_at IS NULL
               AND p.estado <> ?
               AND p.created_at >= ?
               AND p.created_at < ?
             GROUP BY d.producto_titulo
             ORDER BY total_cantidad DESC, total_importe DESC
             LIMIT ' . $limite,
            [$idNegocio, 'cancelado', $inicio, $fin]
        )->getResultArray();

        return array_map(static function (array $fila): array {
            return [
                'producto_titulo' => (string) $fila['producto_titulo'],
                'total_cantidad'  => (int) $fila['total_cantidad'],
                'total_importe'   => (float) $fila['total_importe'],
            ];
        }, $filas);
    }

    /**
     * @return list<array{fecha: string, total_vendido: float, cantidad_pedidos: int}>
     */
    public function obtenerVentasPorDia(int $idNegocio, string $desde, string $hasta): array
    {
        $inicio = $desde . ' 00:00:00';
        $fin    = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';

        $filas = $this->db->query(
            'SELECT DATE(created_at) AS fecha,
                    COUNT(*) AS cantidad_pedidos,
                    COALESCE(SUM(total), 0) AS total_vendido
             FROM pedidos
             WHERE negocio_id = ?
               AND deleted_at IS NULL
               AND estado <> ?
               AND created_at >= ?
               AND created_at < ?
             GROUP BY DATE(created_at)
             ORDER BY fecha ASC',
            [$idNegocio, 'cancelado', $inicio, $fin]
        )->getResultArray();

        $porFecha = [];

        foreach ($filas as $fila) {
            $porFecha[(string) $fila['fecha']] = [
                'total_vendido'    => (float) $fila['total_vendido'],
                'cantidad_pedidos' => (int) $fila['cantidad_pedidos'],
            ];
        }

        $resultado = [];
        $cursor    = new \DateTimeImmutable($desde);
        $limite    = new \DateTimeImmutable($hasta);

        while ($cursor <= $limite) {
            $fecha = $cursor->format('Y-m-d');
            $resultado[] = [
                'fecha'            => $fecha,
                'total_vendido'    => $porFecha[$fecha]['total_vendido'] ?? 0.0,
                'cantidad_pedidos' => $porFecha[$fecha]['cantidad_pedidos'] ?? 0,
            ];
            $cursor = $cursor->modify('+1 day');
        }

        return $resultado;
    }

    /**
     * @return list<array{producto_titulo: string, total_cantidad: int, total_importe: float}>
     */
    public function obtenerBottomProductos(int $idNegocio, string $desde, string $hasta, int $limite = 5): array
    {
        $inicio = $desde . ' 00:00:00';
        $fin    = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';
        $limite = max(1, min(20, $limite));

        $filas = $this->db->query(
            'SELECT d.producto_titulo,
                    SUM(d.cantidad) AS total_cantidad,
                    COALESCE(SUM(d.subtotal), 0) AS total_importe
             FROM detalle_pedido d
             JOIN pedidos p
               ON p.id_pedido = d.pedido_id
              AND p.negocio_id = d.negocio_id
             WHERE d.negocio_id = ?
               AND p.deleted_at IS NULL
               AND p.estado <> ?
               AND p.created_at >= ?
               AND p.created_at < ?
             GROUP BY d.producto_titulo
             ORDER BY total_cantidad ASC, total_importe ASC
             LIMIT ' . $limite,
            [$idNegocio, 'cancelado', $inicio, $fin]
        )->getResultArray();

        return array_map(static function (array $fila): array {
            return [
                'producto_titulo' => (string) $fila['producto_titulo'],
                'total_cantidad'  => (int) $fila['total_cantidad'],
                'total_importe'   => (float) $fila['total_importe'],
            ];
        }, $filas);
    }

    /**
     * @return list<array{estado: string, total: int}>
     */
    public function obtenerConteoPorEstado(int $idNegocio, string $desde, string $hasta): array
    {
        $inicio = $desde . ' 00:00:00';
        $fin    = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';

        $filas = $this->db->query(
            'SELECT estado, COUNT(*) AS total
             FROM pedidos
             WHERE negocio_id = ?
               AND deleted_at IS NULL
               AND created_at >= ?
               AND created_at < ?
             GROUP BY estado',
            [$idNegocio, $inicio, $fin]
        )->getResultArray();

        $porEstado = [];

        foreach ($filas as $fila) {
            $porEstado[(string) $fila['estado']] = (int) $fila['total'];
        }

        $resultado = [];

        foreach (['nuevo', 'confirmado', 'en_proceso', 'entregado', 'cancelado'] as $estado) {
            $resultado[] = [
                'estado' => $estado,
                'total'  => $porEstado[$estado] ?? 0,
            ];
        }

        return $resultado;
    }

    /**
     * @return list<array{cliente_nombre: string, cliente_telefono: string, cantidad_compras: int, total_gastado: float}>
     */
    public function obtenerTopClientes(int $idNegocio, string $desde, string $hasta, int $limite = 10): array
    {
        $inicio = $desde . ' 00:00:00';
        $fin    = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';
        $limite = max(1, min(50, $limite));

        $filas = $this->db->query(
            'SELECT cliente_telefono,
                    MAX(cliente_nombre) AS cliente_nombre,
                    COUNT(*) AS cantidad_compras,
                    COALESCE(SUM(total), 0) AS total_gastado
             FROM pedidos
             WHERE negocio_id = ?
               AND deleted_at IS NULL
               AND estado <> ?
               AND created_at >= ?
               AND created_at < ?
             GROUP BY cliente_telefono
             ORDER BY total_gastado DESC, cantidad_compras DESC
             LIMIT ' . $limite,
            [$idNegocio, 'cancelado', $inicio, $fin]
        )->getResultArray();

        return array_map(static function (array $fila): array {
            return [
                'cliente_nombre'   => (string) $fila['cliente_nombre'],
                'cliente_telefono' => (string) $fila['cliente_telefono'],
                'cantidad_compras' => (int) $fila['cantidad_compras'],
                'total_gastado'    => (float) $fila['total_gastado'],
            ];
        }, $filas);
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
