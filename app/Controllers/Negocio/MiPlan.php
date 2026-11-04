<?php

namespace App\Controllers\Negocio;

use App\Controllers\BaseController;
use App\Models\LogActividadModel;
use App\Models\NegocioUsuarioModel;
use App\Models\PedidoModel;
use App\Models\PlanModel;
use App\Models\ProductoModel;
use App\Models\SuscripcionModel;
use RuntimeException;
use Throwable;

class MiPlan extends BaseController
{
    protected SuscripcionModel $suscripcionModel;
    protected PlanModel $planModel;
    protected ProductoModel $productoModel;
    protected NegocioUsuarioModel $negocioUsuarioModel;
    protected PedidoModel $pedidoModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->suscripcionModel     = model(SuscripcionModel::class);
        $this->planModel            = model(PlanModel::class);
        $this->productoModel        = model(ProductoModel::class);
        $this->negocioUsuarioModel  = model(NegocioUsuarioModel::class);
        $this->pedidoModel          = model(PedidoModel::class);
        $this->logModel             = model(LogActividadModel::class);
    }

    public function index()
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $suscripcion     = $this->suscripcionModel->obtenerVigentePorNegocio($idNegocio);
        $plan            = $suscripcion !== null
            ? $this->planModel->find((int) $suscripcion['plan_id'])
            : null;
        $pendiente       = $this->suscripcionModel->obtenerPendientePagoPorNegocio($idNegocio);
        $planPendiente   = $pendiente !== null
            ? $this->planModel->find((int) $pendiente['plan_id'])
            : null;
        $precioActual    = $suscripcion !== null ? (float) $suscripcion['precio_pactado'] : 0.0;
        $planes          = $this->planModel
            ->where('activo', 1)
            ->orderBy('precio_mensual', 'ASC')
            ->orderBy('id_plan', 'ASC')
            ->findAll();

        foreach ($planes as &$planDisponible) {
            $planDisponible['es_actual'] = $plan !== null
                && (int) $planDisponible['id_plan'] === (int) $plan['id_plan'];
            $planDisponible['es_superior'] = (float) $planDisponible['precio_mensual'] > $precioActual;
        }
        unset($planDisponible);

        $uso = [
            'productos' => $this->productoModel->contarPorNegocio($idNegocio),
            'usuarios'  => $this->negocioUsuarioModel->contarActivosPorNegocio($idNegocio),
            'pedidos'   => $this->pedidoModel->contarDelMes($idNegocio),
        ];

        return view('negocio/mi_plan', [
            'titulo'        => 'Mi Plan',
            'suscripcion'   => $suscripcion,
            'plan'          => $plan,
            'pendiente'     => $pendiente,
            'planPendiente' => $planPendiente,
            'planes'        => $planes,
            'uso'           => $uso,
        ]);
    }

    public function solicitarUpgrade($idPlan = null)
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $idPlan = (int) $idPlan;
        $plan   = $this->planModel
            ->where('id_plan', $idPlan)
            ->where('activo', 1)
            ->first();

        if ($plan === null || (float) $plan['precio_mensual'] <= 0) {
            return redirect()->to(base_url('negocio/mi-plan'))->with(
                'error',
                'El plan seleccionado no está disponible para upgrade.'
            );
        }

        $suscripcion = $this->suscripcionModel->obtenerVigentePorNegocio($idNegocio);

        if ($suscripcion === null) {
            return redirect()->to(base_url('negocio/mi-plan'))->with(
                'error',
                'No tienes una suscripción vigente para solicitar un upgrade.'
            );
        }

        if ((float) $plan['precio_mensual'] <= (float) $suscripcion['precio_pactado']) {
            return redirect()->to(base_url('negocio/mi-plan'))->with(
                'error',
                'Ya tienes este plan o uno superior.'
            );
        }

        if ($this->suscripcionModel->obtenerPendientePagoPorNegocio($idNegocio) !== null) {
            return redirect()->to(base_url('negocio/mi-plan'))->with(
                'error',
                'Ya tienes una solicitud de upgrade pendiente.'
            );
        }

        $idUsuario   = (int) session()->get('id_usuario');
        $planAnterior = $this->planModel->find((int) $suscripcion['plan_id']);
        $db          = db_connect();
        $db->transBegin();

        try {
            $bloqueada = $db->query(
                'SELECT id_suscripcion, plan_id, precio_pactado FROM suscripciones '
                . "WHERE id_suscripcion = ? AND negocio_id = ? AND estado IN ('prueba', 'activa') "
                . 'AND deleted_at IS NULL FOR UPDATE',
                [(int) $suscripcion['id_suscripcion'], $idNegocio]
            )->getRowArray();

            if ($bloqueada === null) {
                throw new RuntimeException('La suscripción vigente cambió.');
            }

            if ((float) $plan['precio_mensual'] <= (float) $bloqueada['precio_pactado']) {
                throw new RuntimeException('El plan ya no es superior al actual.');
            }

            $pendiente = $db->query(
                'SELECT id_suscripcion FROM suscripciones '
                . "WHERE negocio_id = ? AND estado = 'pendiente_pago' AND deleted_at IS NULL "
                . 'LIMIT 1 FOR UPDATE',
                [$idNegocio]
            )->getRowArray();

            if ($pendiente !== null) {
                throw new RuntimeException('Ya existe una solicitud pendiente.');
            }

            $idSuscripcion = (int) $this->suscripcionModel->insert([
                'negocio_id'     => $idNegocio,
                'plan_id'        => $idPlan,
                'estado'         => 'pendiente_pago',
                'fecha_inicio'   => date('Y-m-d'),
                'fecha_fin'      => null,
                'precio_pactado' => number_format((float) $plan['precio_mensual'], 2, '.', ''),
                'created_by'     => $idUsuario,
                'modified_by'    => $idUsuario,
            ], true);

            if ($idSuscripcion <= 0) {
                throw new RuntimeException('No se pudo crear la suscripción pendiente.');
            }

            if ($this->logModel->insert([
                'usuario_id'     => $idUsuario,
                'negocio_id'     => $idNegocio,
                'accion'         => 'solicitar_upgrade',
                'tabla_afectada' => 'suscripciones',
                'registro_id'    => $idSuscripcion,
                'descripcion'    => mb_substr(
                    ($planAnterior['nombre'] ?? 'Plan actual') . ' → ' . $plan['nombre'],
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la solicitud.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al solicitar upgrade: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('negocio/mi-plan'))->with(
                'error',
                'No pudimos registrar tu solicitud de upgrade. Actualiza la página e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/mi-plan'))->with(
            'success',
            'Solicitud de upgrade registrada. El administrador confirmará el pago.'
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
