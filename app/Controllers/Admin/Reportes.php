<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CiudadModel;
use App\Models\DistritoModel;
use App\Models\NegocioModel;
use App\Models\PlanModel;

class Reportes extends BaseController
{
    public function index()
    {
        return view('admin/reportes/index', $this->datosReporte());
    }

    public function pdf()
    {
        $datos               = $this->datosReporte();
        $datos['generadoEn'] = date('d/m/Y H:i');

        $html = view('admin/reportes/pdf', $datos);

        if (! class_exists(\Dompdf\Dompdf::class)) {
            require_once ROOTPATH . 'vendor/autoload.php';
        }

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => false,
            'defaultFont'     => 'DejaVu Sans',
        ]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nombreArchivo = 'reporte-por-' . $datos['agrupacion'] . '-' . date('Y-m-d') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
            ->setBody($dompdf->output());
    }

    /**
     * @return array<string, mixed>
     */
    private function datosReporte(): array
    {
        $negocioModel  = model(NegocioModel::class);
        $ciudadModel   = model(CiudadModel::class);
        $distritoModel = model(DistritoModel::class);
        $planModel     = model(PlanModel::class);
        $db            = db_connect();

        $agrupaciones = [
            'ciudad'   => 'Ciudad',
            'distrito' => 'Distrito',
            'plan'     => 'Plan',
        ];

        $metodosPago = [
            'transferencia' => 'Transferencia',
            'yape'          => 'Yape',
            'plin'          => 'Plin',
            'efectivo'      => 'Efectivo',
        ];

        $agrupacion = strtolower(trim((string) $this->request->getGet('agrupar_por')));

        if (! array_key_exists($agrupacion, $agrupaciones)) {
            $agrupacion = 'ciudad';
        }

        $ciudadId   = (int) $this->request->getGet('ciudad_id');
        $distritoId = (int) $this->request->getGet('distrito_id');
        $planId     = (int) $this->request->getGet('plan_id');
        $metodoPago = strtolower(trim((string) $this->request->getGet('metodo_pago')));

        if ($ciudadId < 0) {
            $ciudadId = 0;
        }

        if ($distritoId < 0) {
            $distritoId = 0;
        }

        if ($planId < 0) {
            $planId = 0;
        }

        if (! array_key_exists($metodoPago, $metodosPago)) {
            $metodoPago = '';
        }

        $mrr = $db->query(
            'SELECT COALESCE(SUM(precio_pactado), 0) AS total
             FROM suscripciones
             WHERE estado = \'activa\'
               AND deleted_at IS NULL'
        )->getRowArray();

        $pedidosMes = $db->query(
            'SELECT COUNT(*) AS total
             FROM pedidos
             WHERE deleted_at IS NULL
               AND YEAR(created_at) = YEAR(CURDATE())
               AND MONTH(created_at) = MONTH(CURDATE())'
        )->getRowArray();

        $ingresos = $db->query(
            'SELECT COALESCE(SUM(total), 0) AS total
             FROM pedidos
             WHERE estado = \'entregado\'
               AND deleted_at IS NULL'
        )->getRowArray();

        $ciudades  = $ciudadModel->listarTodas();
        $distritos = $distritoModel
            ->orderBy('nombre', 'ASC')
            ->findAll();
        $planes = $planModel
            ->orderBy('precio_mensual', 'ASC')
            ->orderBy('id_plan', 'ASC')
            ->findAll();

        $ciudadNombre = 'Todas';
        foreach ($ciudades as $ciudad) {
            if ((int) $ciudad['id_ciudad'] === $ciudadId) {
                $ciudadNombre = (string) $ciudad['nombre'];
                break;
            }
        }

        $distritoNombre = 'Todos';
        foreach ($distritos as $distrito) {
            if ((int) $distrito['id_distrito'] === $distritoId) {
                $distritoNombre = (string) $distrito['nombre'];
                break;
            }
        }

        $metodoPagoNombre = $metodosPago[$metodoPago] ?? 'Todos';

        $planNombre = 'Todos';
        foreach ($planes as $plan) {
            if ((int) $plan['id_plan'] === $planId) {
                $planNombre = (string) $plan['nombre'];
                break;
            }
        }

        return [
            'titulo'             => 'Reportes',
            'metricas'           => [
                'mrr'                  => (float) ($mrr['total'] ?? 0),
                'negocios_activos'     => $negocioModel->contarActivos(),
                'negocios_suspendidos' => $negocioModel->contarSuspendidos(),
                'pedidos_mes'          => (int) ($pedidosMes['total'] ?? 0),
                'ingresos'             => (float) ($ingresos['total'] ?? 0),
            ],
            'reporte'            => $negocioModel->obtenerReporteDinamico(
                $agrupacion,
                $ciudadId,
                $planId,
                $distritoId,
                $metodoPago
            ),
            'agrupacion'         => $agrupacion,
            'etiquetaAgrupacion' => $agrupaciones[$agrupacion],
            'tituloTabla'        => 'Comparativa por ' . $agrupaciones[$agrupacion],
            'filtros'            => [
                'agrupar_por' => $agrupacion,
                'ciudad_id'   => $ciudadId,
                'distrito_id' => $distritoId,
                'plan_id'     => $planId,
                'metodo_pago' => $metodoPago,
            ],
            'ciudades'           => $ciudades,
            'distritos'          => $distritos,
            'planes'             => $planes,
            'metodosPago'        => $metodosPago,
            'ciudadNombre'       => $ciudadNombre,
            'distritoNombre'     => $distritoNombre,
            'planNombre'         => $planNombre,
            'metodoPagoNombre'   => $metodoPagoNombre,
        ];
    }
}
