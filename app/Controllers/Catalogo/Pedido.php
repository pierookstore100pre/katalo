<?php

namespace App\Controllers\Catalogo;

use App\Controllers\BaseController;
use App\Models\ConfiguracionModel;
use App\Models\DetallePedidoModel;
use App\Models\LogActividadModel;
use App\Models\NegocioModel;
use App\Models\NegocioPublicoModel;
use App\Models\PedidoModel;
use App\Models\ProductoModel;
use App\Services\PlanLimites;
use RuntimeException;
use Throwable;

class Pedido extends BaseController
{
    private const SLUGS_RESERVADOS = [
        'auth',
        'negocio',
        'admin',
        'dashboard',
        'c',
        'api',
        'assets',
        'favicon.ico',
    ];

    protected NegocioPublicoModel $negocioPublicoModel;
    protected NegocioModel $negocioModel;
    protected ProductoModel $productoModel;
    protected PedidoModel $pedidoModel;
    protected DetallePedidoModel $detalleModel;
    protected LogActividadModel $logModel;
    protected ConfiguracionModel $configuracionModel;
    protected PlanLimites $planLimites;

    public function __construct()
    {
        $this->negocioPublicoModel = model(NegocioPublicoModel::class);
        $this->negocioModel        = model(NegocioModel::class);
        $this->productoModel       = model(ProductoModel::class);
        $this->pedidoModel         = model(PedidoModel::class);
        $this->detalleModel        = model(DetallePedidoModel::class);
        $this->logModel            = model(LogActividadModel::class);
        $this->configuracionModel  = model(ConfiguracionModel::class);
        $this->planLimites         = new PlanLimites();
    }

    public function crear($ciudad_slug = null, $slug = null)
    {
        $ciudadSlug  = trim((string) $ciudad_slug);
        $slugNegocio = trim((string) $slug);
        $negocio     = $this->buscarNegocioPublico($ciudadSlug, $slugNegocio);

        if ($negocio === null) {
            return $this->respuestaJson([
                'ok'      => false,
                'mensaje' => 'Este catálogo no está disponible en esta ciudad o el negocio ya no opera en Katalo.pe.',
            ], 404);
        }

        $ip           = $this->request->getIPAddress();
        $maxPorIpHora = (int) $this->configuracionModel->obtenerValor('pedidos_max_por_ip_hora', 10);

        if ($maxPorIpHora > 0 && $this->pedidoModel->contarPorIpEnUltimaHora($ip) >= $maxPorIpHora) {
            return $this->respuestaJson([
                'ok'      => false,
                'mensaje' => 'Se alcanzó el límite de pedidos por hora desde esta conexión. Intenta más tarde.',
            ], 429);
        }

        $datos = $this->datosFormulario();
        $error = $this->validarDatos($datos);

        if ($error !== null) {
            return $this->respuestaJson(['ok' => false, 'mensaje' => $error], 422);
        }

        $items = $this->normalizarItems($datos['items']);

        if ($items === null || $items === []) {
            return $this->respuestaJson([
                'ok'      => false,
                'mensaje' => 'Agrega al menos un producto válido a tu pedido.',
            ], 422);
        }

        $idNegocio = (int) $negocio['id_negocio'];
        $productos = $this->productoModel->obtenerDisponiblesPorIds($idNegocio, array_keys($items));
        $mapa      = [];

        foreach ($productos as $producto) {
            $mapa[(int) $producto['id_producto']] = $producto;
        }

        $lineasMensaje = [];
        $detalles      = [];
        $totalCentavos = 0;

        foreach ($items as $idProducto => $cantidad) {
            if (! isset($mapa[$idProducto])) {
                return $this->respuestaJson([
                    'ok'      => false,
                    'mensaje' => 'Uno de los productos de tu carrito ya no está disponible. Actualiza tu carrito e inténtalo nuevamente.',
                ], 422);
            }

            $producto        = $mapa[$idProducto];
            $precioCentavos  = (int) round(((float) $producto['precio_referencial']) * 100);
            $subtotalCentavos = $precioCentavos * $cantidad;
            $totalCentavos   += $subtotalCentavos;

            $lineasMensaje[] = '- ' . $cantidad . 'x ' . $producto['titulo']
                . ' - S/ ' . number_format($subtotalCentavos / 100, 2, '.', '');

            $detalles[] = [
                'negocio_id'      => $idNegocio,
                'producto_id'     => $idProducto,
                'producto_titulo' => (string) $producto['titulo'],
                'cantidad'        => $cantidad,
                'precio_unitario' => number_format($precioCentavos / 100, 2, '.', ''),
                'nota'            => null,
            ];
        }

        $total = number_format($totalCentavos / 100, 2, '.', '');

        if (! $this->planLimites->puedeCrearPedido($idNegocio)) {
            return $this->respuestaJson([
                'ok'      => false,
                'mensaje' => 'Este negocio alcanzó el límite de pedidos de su plan este mes.',
            ], 422);
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $numero = $this->negocioModel->obtenerSiguienteNumeroConBloqueo($idNegocio);

            if ($numero === null) {
                throw new RuntimeException('No se pudo obtener el correlativo del pedido.');
            }

            $codigo = 'KTL-' . date('Y') . '-' . str_pad((string) $numero, 4, '0', STR_PAD_LEFT);

            $actualizado = $db->query(
                'UPDATE negocios SET siguiente_numero_pedido = siguiente_numero_pedido + 1 WHERE id_negocio = ?',
                [$idNegocio]
            );

            if ($actualizado === false) {
                throw new RuntimeException('No se pudo actualizar el correlativo del pedido.');
            }

            $idPedido = (int) $this->pedidoModel->insert([
                'negocio_id'        => $idNegocio,
                'numero'            => $numero,
                'codigo'            => $codigo,
                'cliente_nombre'    => $datos['cliente_nombre'],
                'cliente_telefono'  => $datos['cliente_telefono'],
                'cliente_direccion' => $datos['cliente_direccion'] !== '' ? $datos['cliente_direccion'] : null,
                'comentario'        => $datos['comentario'] !== '' ? $datos['comentario'] : null,
                'total'             => $total,
                'estado'            => 'nuevo',
                'canal'             => 'whatsapp',
                'ip_cliente'        => $this->request->getIPAddress(),
            ], true);

            if ($idPedido <= 0) {
                throw new RuntimeException('No se pudo crear el pedido.');
            }

            foreach ($detalles as $detalle) {
                $detalle['pedido_id'] = $idPedido;

                if ($this->detalleModel->insert($detalle) === false) {
                    throw new RuntimeException('No se pudo registrar el detalle del pedido.');
                }
            }

            if ($this->logModel->insert([
                'usuario_id'     => null,
                'negocio_id'     => $idNegocio,
                'accion'         => 'crear_pedido',
                'tabla_afectada' => 'pedidos',
                'registro_id'    => $idPedido,
                'descripcion'    => mb_substr($codigo, 0, 500),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad del pedido.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al registrar pedido: {message}', ['message' => $e->getMessage()]);

            return $this->respuestaJson([
                'ok'      => false,
                'mensaje' => 'No pudimos registrar tu pedido. Inténtalo nuevamente en unos instantes.',
            ], 500);
        }

        $mensaje = $this->construirMensajeWhatsApp(
            (string) $negocio['nombre_comercial'],
            $codigo,
            $lineasMensaje,
            $total,
            $datos
        );

        return $this->respuestaJson([
            'ok'                => true,
            'codigo'            => $codigo,
            'mensaje_whatsapp'  => $mensaje,
            'telefono_negocio'  => preg_replace('/\D+/', '', (string) ($negocio['telefono_principal'] ?? '')),
            'id_pedido'         => $idPedido,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buscarNegocioPublico(string $ciudadSlug, string $slug): ?array
    {
        if ($ciudadSlug === '' || $slug === ''
            || $this->esSlugReservado($ciudadSlug)
            || $this->esSlugReservado($slug)
        ) {
            return null;
        }

        return $this->negocioPublicoModel->findPorCiudadYSlug($ciudadSlug, $slug);
    }

    private function esSlugReservado(string $valor): bool
    {
        return in_array(strtolower($valor), self::SLUGS_RESERVADOS, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosFormulario(): array
    {
        try {
            $datos = $this->request->getJSON(true);
        } catch (Throwable $e) {
            $datos = null;
        }

        if (! is_array($datos)) {
            $datos = $this->request->getPost();
        }

        return [
            'cliente_nombre'    => trim((string) ($datos['cliente_nombre'] ?? '')),
            'cliente_telefono'  => trim((string) ($datos['cliente_telefono'] ?? '')),
            'cliente_direccion' => trim((string) ($datos['cliente_direccion'] ?? '')),
            'comentario'        => trim((string) ($datos['comentario'] ?? '')),
            'items'             => is_array($datos['items'] ?? null) ? $datos['items'] : [],
        ];
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function validarDatos(array $datos): ?string
    {
        if ($datos['cliente_nombre'] === '') {
            return 'Ingresa tu nombre para enviar el pedido.';
        }

        if (mb_strlen($datos['cliente_nombre']) > 150) {
            return 'El nombre no debe superar los 150 caracteres.';
        }

        if (! preg_match('/^[0-9]{7,15}$/', $datos['cliente_telefono'])) {
            return 'Ingresa un teléfono válido de 7 a 15 dígitos.';
        }

        if (mb_strlen($datos['cliente_direccion']) > 255) {
            return 'La dirección no debe superar los 255 caracteres.';
        }

        if (mb_strlen($datos['comentario']) > 500) {
            return 'El comentario no debe superar los 500 caracteres.';
        }

        if ($datos['items'] === []) {
            return 'Agrega al menos un producto a tu pedido.';
        }

        return null;
    }

    /**
     * @param array<int, mixed> $items
     *
     * @return array<int, int>|null
     */
    private function normalizarItems(array $items): ?array
    {
        $normalizados = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                return null;
            }

            $idProducto = filter_var(
                $item['id_producto'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            $cantidad = filter_var(
                $item['cantidad'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($idProducto === false || $cantidad === false) {
                return null;
            }

            $normalizados[$idProducto] = ($normalizados[$idProducto] ?? 0) + $cantidad;
        }

        return $normalizados;
    }

    /**
     * @param list<string>         $lineasProductos
     * @param array<string, mixed> $datos
     */
    private function construirMensajeWhatsApp(
        string $nombreNegocio,
        string $codigo,
        array $lineasProductos,
        string $total,
        array $datos
    ): string {
        $lineas = [
            'Hola ' . $nombreNegocio . ', quiero hacer un pedido:',
            '',
            'Pedido ' . $codigo,
            '',
        ];

        $lineas = array_merge($lineas, $lineasProductos, [
            '',
            'Total: S/ ' . $total,
            '',
            'Nombre: ' . $datos['cliente_nombre'],
            'Teléfono: ' . $datos['cliente_telefono'],
        ]);

        if ($datos['cliente_direccion'] !== '') {
            $lineas[] = 'Dirección: ' . $datos['cliente_direccion'];
        }

        if ($datos['comentario'] !== '') {
            $lineas[] = 'Comentario: ' . $datos['comentario'];
        }

        $lineas[] = '';
        $lineas[] = 'Enviado desde Katalo.pe';

        return implode("\n", $lineas);
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function respuestaJson(array $datos, int $estado = 200): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response
            ->setStatusCode($estado)
            ->setHeader(csrf_header(), csrf_hash())
            ->setJSON($datos);
    }
}
