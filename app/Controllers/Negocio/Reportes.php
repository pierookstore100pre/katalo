<?php

namespace App\Controllers\Negocio;

use App\Controllers\BaseController;
use App\Models\NegocioModel;
use App\Models\PedidoModel;
use App\Models\PlanModel;

class Reportes extends BaseController
{
    private const TIPOS = [
        'ventas'    => 'Ventas',
        'productos' => 'Productos',
        'pedidos'   => 'Pedidos',
        'clientes'  => 'Clientes',
    ];

    protected PedidoModel $pedidoModel;
    protected PlanModel $planModel;
    protected NegocioModel $negocioModel;

    public function __construct()
    {
        $this->pedidoModel  = model(PedidoModel::class);
        $this->planModel    = model(PlanModel::class);
        $this->negocioModel = model(NegocioModel::class);
    }

    public function index()
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $plan          = $this->planModel->obtenerVigentePorNegocio($idNegocio);
        $tieneReportes = $plan !== null && (int) $plan['tiene_reportes'] === 1;
        $tipo          = $this->tipoSolicitado();
        [$desde, $hasta] = $this->rangoFechas();

        if (! $tieneReportes) {
            return view('negocio/reportes/index', $this->datosVista([
                'bloqueado' => true,
                'plan'      => $plan,
                'tipo'      => $tipo,
                'desde'     => $desde,
                'hasta'     => $hasta,
            ]));
        }

        return view('negocio/reportes/index', $this->datosVista([
            'bloqueado'        => false,
            'plan'             => $plan,
            'tipo'             => $tipo,
            'desde'            => $desde,
            'hasta'            => $hasta,
            'metricas'         => $this->pedidoModel->obtenerMetricasReporte($idNegocio, $desde, $hasta),
            'ventasPorDia'     => $this->pedidoModel->obtenerVentasPorDia($idNegocio, $desde, $hasta),
            'topProductos'     => $this->pedidoModel->obtenerTopProductos($idNegocio, $desde, $hasta),
            'bottomProductos'  => $this->pedidoModel->obtenerBottomProductos($idNegocio, $desde, $hasta),
            'conteoEstados'    => $this->pedidoModel->obtenerConteoPorEstado($idNegocio, $desde, $hasta),
            'topClientes'      => $this->pedidoModel->obtenerTopClientes($idNegocio, $desde, $hasta),
        ]));
    }

    public function pdf()
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $plan = $this->planModel->obtenerVigentePorNegocio($idNegocio);

        if ($plan === null || (int) $plan['tiene_reportes'] !== 1) {
            return redirect()->to(base_url('dashboard'))->with(
                'error',
                'Los reportes en PDF son exclusivos de los planes con reportes habilitados.'
            );
        }

        $tipo            = $this->tipoSolicitado();
        [$desde, $hasta] = $this->rangoFechas();
        $negocio         = $this->negocioModel->find($idNegocio);

        $datos = [
            'tipo'            => $tipo,
            'tipoTitulo'      => self::TIPOS[$tipo],
            'desde'           => $desde,
            'hasta'           => $hasta,
            'nombreComercial' => (string) ($negocio['nombre_comercial'] ?? session()->get('nombre_comercial') ?? 'Katalo.pe'),
            'logo'            => $this->logoDataUri($idNegocio),
            'generadoEn'      => date('d/m/Y H:i'),
        ];

        if ($tipo === 'productos') {
            $datos['topProductos']    = $this->pedidoModel->obtenerTopProductos($idNegocio, $desde, $hasta);
            $datos['bottomProductos'] = $this->pedidoModel->obtenerBottomProductos($idNegocio, $desde, $hasta);
        } elseif ($tipo === 'pedidos') {
            $datos['conteoEstados'] = $this->pedidoModel->obtenerConteoPorEstado($idNegocio, $desde, $hasta);
        } elseif ($tipo === 'clientes') {
            $datos['topClientes'] = $this->pedidoModel->obtenerTopClientes($idNegocio, $desde, $hasta);
        } else {
            $datos['metricas']     = $this->pedidoModel->obtenerMetricasReporte($idNegocio, $desde, $hasta);
            $datos['ventasPorDia'] = $this->pedidoModel->obtenerVentasPorDia($idNegocio, $desde, $hasta);
        }

        $html = view('negocio/reportes/pdf', $datos);

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

        $nombreArchivo = 'reporte-' . $tipo . '-' . $desde . '-' . $hasta . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
            ->setBody($dompdf->output());
    }

    /**
     * @param array<string, mixed> $datos
     *
     * @return array<string, mixed>
     */
    private function datosVista(array $datos): array
    {
        $conteoEstados = $datos['conteoEstados'] ?? [];
        $totalPedidos  = 0;

        foreach ($conteoEstados as $fila) {
            $totalPedidos += (int) $fila['total'];
        }

        return array_merge([
            'titulo'          => 'Reportes',
            'tipos'           => self::TIPOS,
            'metricas'        => [
                'total_vendido'    => 0.0,
                'cantidad_pedidos' => 0,
                'ticket_promedio'  => 0.0,
            ],
            'ventasPorDia'    => [],
            'topProductos'    => [],
            'bottomProductos' => [],
            'conteoEstados'   => [],
            'totalPedidos'    => $totalPedidos,
            'topClientes'     => [],
            'nombreComercial' => (string) (session()->get('nombre_comercial') ?? ''),
        ], $datos);
    }

    private function tipoSolicitado(): string
    {
        $tipo = strtolower(trim((string) $this->request->getGet('tipo')));

        return array_key_exists($tipo, self::TIPOS) ? $tipo : 'ventas';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function rangoFechas(): array
    {
        $hoy   = date('Y-m-d');
        $desde = trim((string) ($this->request->getGet('start_date') ?? $this->request->getGet('desde')));
        $hasta = trim((string) ($this->request->getGet('end_date') ?? $this->request->getGet('hasta')));

        if (! $this->esFechaValida($desde)) {
            $desde = date('Y-m-01');
        }

        if (! $this->esFechaValida($hasta)) {
            $hasta = $hoy;
        }

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }

    private function esFechaValida(string $fecha): bool
    {
        if ($fecha === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }

        $objeto = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        return $objeto instanceof \DateTimeImmutable && $objeto->format('Y-m-d') === $fecha;
    }

    private function logoDataUri(int $idNegocio): ?string
    {
        $ruta = $this->negocioModel->obtenerLogoAbsoluto($idNegocio);

        if ($ruta === null) {
            return null;
        }

        $mime = @mime_content_type($ruta);
        $contenido = @file_get_contents($ruta);

        if (! is_string($mime)
            || $contenido === false
            || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
        ) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contenido);
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
