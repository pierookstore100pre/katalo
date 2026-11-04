<?php

namespace App\Controllers\Negocio;

use App\Controllers\BaseController;
use App\Models\DetallePedidoModel;
use App\Models\LogActividadModel;
use App\Models\PedidoModel;
use RuntimeException;
use Throwable;

class Pedidos extends BaseController
{
    private const TRANSICIONES = [
        'nuevo'      => ['confirmado', 'cancelado'],
        'confirmado' => ['en_proceso', 'cancelado'],
        'en_proceso' => ['entregado', 'cancelado'],
        'entregado'  => [],
        'cancelado'  => [],
    ];

    private const SIGUIENTE = [
        'nuevo'      => 'confirmado',
        'confirmado' => 'en_proceso',
        'en_proceso' => 'entregado',
    ];

    protected PedidoModel $pedidoModel;
    protected DetallePedidoModel $detalleModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->pedidoModel  = model(PedidoModel::class);
        $this->detalleModel = model(DetallePedidoModel::class);
        $this->logModel     = model(LogActividadModel::class);
    }

    public function index()
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $filtro = (string) $this->request->getGet('estado');
        if (! in_array($filtro, ['todos', 'nuevo', 'confirmado', 'en_proceso', 'entregado', 'cancelado'], true)) {
            $filtro = 'todos';
        }

        $pedidos = $this->pedidoModel->listarDelNegocio($idNegocio, $filtro);

        foreach ($pedidos as &$pedido) {
            $pedido['siguiente_estado'] = self::SIGUIENTE[$pedido['estado']] ?? null;
        }
        unset($pedido);

        return view('negocio/pedidos/index', [
            'titulo'   => 'Mis Pedidos',
            'pedidos'  => $pedidos,
            'filtro'   => $filtro,
        ]);
    }

    public function ver($id = null)
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $pedido = $this->pedidoModel->findDelNegocio((int) $id, $idNegocio);

        if ($pedido === null) {
            return redirect()->to(base_url('negocio/pedidos'))->with(
                'error',
                'El pedido no existe o no pertenece a tu negocio.'
            );
        }

        return view('negocio/pedidos/detalle', [
            'titulo'       => 'Pedido ' . $pedido['codigo'],
            'pedido'       => $pedido,
            'detalles'     => $this->detalleModel->listarDelPedido((int) $pedido['id_pedido'], $idNegocio),
            'transiciones' => self::TRANSICIONES[$pedido['estado']] ?? [],
        ]);
    }

    public function cambiarEstado($id = null)
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $idPedido = (int) $id;
        $pedido   = $this->pedidoModel->findDelNegocio($idPedido, $idNegocio);

        if ($pedido === null) {
            return redirect()->to(base_url('negocio/pedidos'))->with(
                'error',
                'El pedido no existe o no pertenece a tu negocio.'
            );
        }

        $nuevoEstado   = (string) $this->request->getPost('estado');
        $estadoActual  = (string) $pedido['estado'];
        $permitidos    = self::TRANSICIONES[$estadoActual] ?? [];

        if (! in_array($nuevoEstado, $permitidos, true)) {
            return redirect()->back()->with(
                'error',
                'Ese cambio de estado no está permitido para el pedido ' . $pedido['codigo'] . '.'
            );
        }

        $idUsuario = (int) session()->get('id_usuario');
        $db        = db_connect();
        $db->transBegin();

        try {
            $actualizado = $this->pedidoModel->update($idPedido, [
                'estado'      => $nuevoEstado,
                'modified_by' => $idUsuario,
            ]);

            if (! $actualizado) {
                throw new RuntimeException('No se pudo actualizar el estado del pedido.');
            }

            $logCreado = $this->logModel->insert([
                'usuario_id'     => $idUsuario,
                'negocio_id'     => $idNegocio,
                'accion'         => 'cambiar_estado_pedido',
                'tabla_afectada' => 'pedidos',
                'registro_id'    => $idPedido,
                'descripcion'    => mb_substr(
                    $pedido['codigo'] . ': ' . $estadoActual . ' → ' . $nuevoEstado,
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]);

            if ($logCreado === false) {
                throw new RuntimeException('No se pudo registrar la actividad del pedido.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al cambiar estado del pedido: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->with(
                'error',
                'No pudimos actualizar el estado del pedido. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/pedidos/' . $idPedido))->with(
            'success',
            'El pedido ' . $pedido['codigo'] . ' pasó a estado ' . $nuevoEstado . '.'
        );
    }

    private function negocioId(): ?int
    {
        $idNegocio = (int) session()->get('id_negocio');

        return $idNegocio > 0 ? $idNegocio : null;
    }

    private function cerrarSesionSinNegocio()
    {
        session()->destroy();

        return redirect()->to(base_url('auth/login'));
    }
}
