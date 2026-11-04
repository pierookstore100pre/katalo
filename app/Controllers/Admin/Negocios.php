<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CiudadModel;
use App\Models\LogActividadModel;
use App\Models\NegocioModel;
use App\Models\PedidoModel;
use App\Models\PlanModel;
use App\Models\SuscripcionModel;
use RuntimeException;
use Throwable;

class Negocios extends BaseController
{
    protected NegocioModel $negocioModel;
    protected CiudadModel $ciudadModel;
    protected SuscripcionModel $suscripcionModel;
    protected PlanModel $planModel;
    protected PedidoModel $pedidoModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->negocioModel     = model(NegocioModel::class);
        $this->ciudadModel      = model(CiudadModel::class);
        $this->suscripcionModel = model(SuscripcionModel::class);
        $this->planModel        = model(PlanModel::class);
        $this->pedidoModel      = model(PedidoModel::class);
        $this->logModel         = model(LogActividadModel::class);
    }

    public function index()
    {
        $filtroEstado = (string) $this->request->getGet('estado');

        if (! in_array($filtroEstado, ['todos', 'activo', 'suspendido'], true)) {
            $filtroEstado = 'todos';
        }

        $filtros = [
            'ciudad' => (int) $this->request->getGet('ciudad'),
            'estado' => $filtroEstado,
            'q'      => trim((string) $this->request->getGet('q')),
        ];

        $negocios = $this->negocioModel->listarConFiltros($filtros, 20);

        return view('admin/negocios/index', [
            'titulo'       => 'Negocios',
            'negocios'     => $negocios,
            'ciudades'     => $this->ciudadModel->listarTodas(),
            'filtros'      => $filtros,
            'pager'        => $this->negocioModel->pager,
        ]);
    }

    public function ver($id = null)
    {
        $idNegocio = (int) $id;
        $negocio   = $this->negocioModel->obtenerDetalleAdmin($idNegocio);

        if ($negocio === null) {
            return redirect()->to(base_url('admin/negocios'))->with(
                'error',
                'El negocio no existe.'
            );
        }

        $suscripcionVigente = $this->suscripcionModel
            ->where('negocio_id', $idNegocio)
            ->whereIn('estado', ['prueba', 'activa'])
            ->orderBy('id_suscripcion', 'DESC')
            ->first();

        $ultimaSuscripcion = $this->suscripcionModel
            ->where('negocio_id', $idNegocio)
            ->orderBy('id_suscripcion', 'DESC')
            ->first();

        $planVigente = $suscripcionVigente !== null
            ? $this->planModel->find((int) $suscripcionVigente['plan_id'])
            : null;

        $planUltima = $ultimaSuscripcion !== null
            ? $this->planModel->find((int) $ultimaSuscripcion['plan_id'])
            : null;

        $productos = db_connect()->query(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(visible = 1), 0) AS visibles,
                    COALESCE(SUM(visible = 0), 0) AS ocultos,
                    COALESCE(SUM(estado = \'agotado\'), 0) AS agotados
             FROM productos
             WHERE negocio_id = ?
               AND deleted_at IS NULL',
            [$idNegocio]
        )->getRowArray();

        return view('admin/negocios/detalle', [
            'titulo'             => 'Negocio ' . $negocio['nombre_comercial'],
            'negocio'            => $negocio,
            'suscripcionVigente' => $suscripcionVigente,
            'planVigente'        => $planVigente,
            'ultimaSuscripcion'  => $ultimaSuscripcion,
            'planUltima'         => $planUltima,
            'productos'          => [
                'total'    => (int) ($productos['total'] ?? 0),
                'visibles' => (int) ($productos['visibles'] ?? 0),
                'ocultos'  => (int) ($productos['ocultos'] ?? 0),
                'agotados' => (int) ($productos['agotados'] ?? 0),
            ],
            'pedidosTotal'       => $this->pedidoModel->where('negocio_id', $idNegocio)->countAllResults(),
            'pedidosMes'         => $this->pedidoModel->contarDelMes($idNegocio),
            'ultimosPedidos'     => $this->pedidoModel
                ->where('negocio_id', $idNegocio)
                ->orderBy('created_at', 'DESC')
                ->orderBy('id_pedido', 'DESC')
                ->findAll(10),
        ]);
    }

    public function suspender($id = null)
    {
        return $this->cambiarEstado((int) $id, false);
    }

    public function reactivar($id = null)
    {
        return $this->cambiarEstado((int) $id, true);
    }

    private function cambiarEstado(int $idNegocio, bool $activar)
    {
        $negocio = $this->negocioModel->find($idNegocio);

        if ($negocio === null) {
            return redirect()->to(base_url('admin/negocios'))->with(
                'error',
                'El negocio no existe.'
            );
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            $this->negocioModel->update($idNegocio, [
                'activo'      => $activar ? 1 : 0,
                'modified_by' => $idAdmin,
            ]);

            $accion = $activar ? 'reactivar_negocio' : 'suspender_negocio';

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => $idNegocio,
                'accion'         => $accion,
                'tabla_afectada' => 'negocios',
                'registro_id'    => $idNegocio,
                'descripcion'    => mb_substr(
                    $negocio['nombre_comercial'] . ' (' . $negocio['slug'] . ')',
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad del negocio.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al cambiar estado de negocio: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/negocios/' . $idNegocio))->with(
                'error',
                'No pudimos actualizar el negocio. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('admin/negocios/' . $idNegocio))->with(
            'success',
            'El negocio ' . $negocio['nombre_comercial'] . ' fue '
            . ($activar ? 'reactivado' : 'suspendido') . ' correctamente.'
        );
    }
}
