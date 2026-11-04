<?php

namespace App\Controllers\Negocio;

use App\Controllers\BaseController;
use App\Models\CategoriaModel;
use App\Models\ImagenProductoModel;
use App\Models\LogActividadModel;
use App\Models\PlanModel;
use App\Models\ProductoModel;
use App\Services\ModerationService;
use App\Services\PlanLimites;
use RuntimeException;
use Throwable;

class Productos extends BaseController
{
    protected ProductoModel $productoModel;
    protected CategoriaModel $categoriaModel;
    protected ImagenProductoModel $imagenModel;
    protected PlanModel $planModel;
    protected LogActividadModel $logModel;
    protected PlanLimites $planLimites;
    protected ModerationService $moderationService;

    public function __construct()
    {
        $this->productoModel     = model(ProductoModel::class);
        $this->categoriaModel    = model(CategoriaModel::class);
        $this->imagenModel       = model(ImagenProductoModel::class);
        $this->planModel         = model(PlanModel::class);
        $this->logModel          = model(LogActividadModel::class);
        $this->planLimites       = new PlanLimites();
        $this->moderationService = new ModerationService();
    }

    public function index()
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $filtro = (string) $this->request->getGet('filtro');
        if (! in_array($filtro, ['todos', 'visibles', 'ocultos', 'agotados'], true)) {
            $filtro = 'todos';
        }

        $productos = $this->productoModel->listarDelNegocio($idNegocio, $filtro);
        $ids       = array_map(static fn (array $producto): int => (int) $producto['id_producto'], $productos);
        $principales = $this->imagenModel->listarPrincipalesPorProductos($ids);

        foreach ($productos as &$producto) {
            $producto['imagen_principal_data_uri'] = $this->imagenDataUri(
                $principales[(int) $producto['id_producto']] ?? null
            );
        }
        unset($producto);

        $plan = $this->planModel->obtenerVigentePorNegocio($idNegocio);

        return view('negocio/productos/index', [
            'titulo'         => 'Mis productos',
            'productos'      => $productos,
            'filtro'         => $filtro,
            'plan'           => $plan,
            'maxProductos'   => $plan !== null ? $plan['max_productos'] : null,
            'totalProductos' => $this->productoModel->contarPorNegocio($idNegocio),
        ]);
    }

    public function crear()
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        return view('negocio/productos/form', [
            'titulo'     => 'Nuevo producto',
            'esEdicion'  => false,
            'producto'   => null,
            'categorias' => $this->categoriaModel->listarActivasPorNegocio($idNegocio),
            'accion'     => base_url('negocio/productos/crear'),
            'plan'       => $this->planModel->obtenerVigentePorNegocio($idNegocio),
        ]);
    }

    public function guardar()
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $idUsuario = (int) session()->get('id_usuario');
        $data      = $this->datosFormulario();

        if (! $this->validarProducto($data, $idNegocio)) {
            return redirect()->back()->withInput();
        }

        $plan = $this->planModel->obtenerVigentePorNegocio($idNegocio);
        $maxProductos = $plan !== null ? $plan['max_productos'] : null;

        if (! $this->planLimites->puedeCrearProducto($idNegocio)) {
            return redirect()->back()->withInput()->with(
                'error',
                'Alcanzaste el límite de ' . (int) $maxProductos . ' productos de tu plan. Mejora tu plan para agregar más productos.'
            );
        }

        $evaluacion = $this->moderationService->evaluarCampos($data);
        $bloqueado  = $evaluacion['bloqueado'];

        $db = db_connect();
        $db->transStart();

        try {
            $idProducto = (int) $this->productoModel->insert([
                'negocio_id'         => $idNegocio,
                'categoria_id'       => (int) $data['categoria_id'],
                'titulo'             => $data['titulo'],
                'descripcion'        => $data['descripcion'] !== '' ? $data['descripcion'] : null,
                'precio_referencial' => $this->precio($data['precio_referencial']),
                'estado'             => $data['estado'],
                'visible'            => $bloqueado ? 0 : $data['visible'],
                'destacado'          => $data['destacado'],
                'created_by'         => $idUsuario,
                'modified_by'        => $idUsuario,
            ], true);

            if ($idProducto <= 0) {
                throw new RuntimeException('No se pudo crear el producto.');
            }

            $this->registrarLog(
                'crear_producto',
                $idUsuario,
                $idNegocio,
                $idProducto,
                'Creación del producto ' . $data['titulo'] . '.'
            );

            if ($bloqueado) {
                $this->moderationService->registrarMarcado(
                    'productos',
                    $idProducto,
                    $evaluacion,
                    $idNegocio,
                    $idUsuario
                );
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción del producto no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al crear producto: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos crear el producto. Revisa los datos e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/productos'))->with(
            'success',
            $bloqueado
                ? 'Tu producto ha sido guardado, pero será revisado por un administrador antes de publicarse.'
                : 'Producto creado correctamente.'
        );
    }

    public function editar($id = null)
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $producto = $this->productoModel->findDelNegocio((int) $id, $idNegocio);

        if ($producto === null) {
            return redirect()->to(base_url('negocio/productos'))->with(
                'error',
                'El producto no existe o no pertenece a tu negocio.'
            );
        }

        return view('negocio/productos/form', [
            'titulo'     => 'Editar producto',
            'esEdicion'  => true,
            'producto'   => $producto,
            'categorias' => $this->categoriaModel->listarActivasPorNegocio($idNegocio),
            'accion'     => base_url('negocio/productos/editar/' . (int) $producto['id_producto']),
            'plan'       => $this->planModel->obtenerVigentePorNegocio($idNegocio),
        ]);
    }

    public function actualizar($id = null)
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $idProducto = (int) $id;
        $producto   = $this->productoModel->findDelNegocio($idProducto, $idNegocio);

        if ($producto === null) {
            return redirect()->to(base_url('negocio/productos'))->with(
                'error',
                'El producto no existe o no pertenece a tu negocio.'
            );
        }

        $idUsuario = (int) session()->get('id_usuario');
        $data      = $this->datosFormulario();

        if (! $this->validarProducto($data, $idNegocio)) {
            return redirect()->back()->withInput();
        }

        $evaluacion = $this->moderationService->evaluarCampos($data);
        $bloqueado  = $evaluacion['bloqueado'];

        $db = db_connect();
        $db->transStart();

        try {
            $actualizado = $this->productoModel->update($idProducto, [
                'categoria_id'       => (int) $data['categoria_id'],
                'titulo'             => $data['titulo'],
                'descripcion'        => $data['descripcion'] !== '' ? $data['descripcion'] : null,
                'precio_referencial' => $this->precio($data['precio_referencial']),
                'estado'             => $data['estado'],
                'visible'            => $bloqueado ? 0 : $data['visible'],
                'destacado'          => $data['destacado'],
                'modified_by'        => $idUsuario,
            ]);

            if (! $actualizado) {
                throw new RuntimeException('No se pudo actualizar el producto.');
            }

            $this->registrarLog(
                'editar_producto',
                $idUsuario,
                $idNegocio,
                $idProducto,
                'Actualización del producto ' . $data['titulo'] . '.'
            );

            if ($bloqueado) {
                $this->moderationService->registrarMarcado(
                    'productos',
                    $idProducto,
                    $evaluacion,
                    $idNegocio,
                    $idUsuario
                );
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción del producto no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al actualizar producto: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos actualizar el producto. Revisa los datos e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/productos'))->with(
            'success',
            $bloqueado
                ? 'Tu producto ha sido guardado, pero será revisado por un administrador antes de publicarse.'
                : 'Producto actualizado correctamente.'
        );
    }

    public function ocultar($id = null)
    {
        return $this->cambiarCampo(
            (int) $id,
            ['visible' => 0],
            'ocultar_producto',
            'Producto ocultado correctamente.',
            'Ocultación del producto.'
        );
    }

    public function reactivar($id = null)
    {
        return $this->cambiarCampo(
            (int) $id,
            ['visible' => 1],
            'reactivar_producto',
            'Producto reactivado correctamente.',
            'Reactivación de la visibilidad del producto.'
        );
    }

    public function agotar($id = null)
    {
        return $this->cambiarCampo(
            (int) $id,
            ['estado' => 'agotado'],
            'agotar_producto',
            'Producto marcado como agotado.',
            'Producto marcado como agotado.'
        );
    }

    public function reactivarEstado($id = null)
    {
        return $this->cambiarCampo(
            (int) $id,
            ['estado' => 'activo'],
            'editar_producto',
            'Producto marcado como activo nuevamente.',
            'Producto reactivado a estado activo.'
        );
    }

    /**
     * @param array<string, int|string> $cambios
     */
    private function cambiarCampo(
        int $idProducto,
        array $cambios,
        string $accion,
        string $mensajeExito,
        string $descripcionLog
    ) {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $producto = $this->productoModel->findDelNegocio($idProducto, $idNegocio);

        if ($producto === null) {
            return redirect()->to(base_url('negocio/productos'))->with(
                'error',
                'El producto no existe o no pertenece a tu negocio.'
            );
        }

        $idUsuario = (int) session()->get('id_usuario');
        $db        = db_connect();
        $db->transStart();

        try {
            $actualizado = $this->productoModel->update($idProducto, $cambios + ['modified_by' => $idUsuario]);

            if (! $actualizado) {
                throw new RuntimeException('No se pudo actualizar el producto.');
            }

            $this->registrarLog(
                $accion,
                $idUsuario,
                $idNegocio,
                $idProducto,
                $descripcionLog . ' ' . $producto['titulo'] . '.'
            );

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción del producto no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al cambiar estado de producto: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('negocio/productos'))->with(
                'error',
                'No pudimos actualizar el producto. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/productos'))->with('success', $mensajeExito);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosFormulario(): array
    {
        return [
            'titulo'             => trim((string) $this->request->getPost('titulo')),
            'categoria_id'       => trim((string) $this->request->getPost('categoria_id')),
            'precio_referencial' => trim((string) $this->request->getPost('precio_referencial')),
            'descripcion'        => trim((string) $this->request->getPost('descripcion')),
            'estado'             => (string) $this->request->getPost('estado'),
            'visible'            => $this->request->getPost('visible') ? 1 : 0,
            'destacado'          => $this->request->getPost('destacado') ? 1 : 0,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validarProducto(array $data, int $idNegocio): bool
    {
        $rules = [
            'titulo'             => 'required|max_length[200]',
            'categoria_id'       => 'required|is_natural_no_zero',
            'precio_referencial' => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[99999999.99]',
            'descripcion'        => 'permit_empty',
            'estado'             => 'required|in_list[activo,pausado,agotado]',
        ];

        $messages = [
            'titulo' => [
                'required'   => 'Ingresa el título del producto.',
                'max_length' => 'El título no debe superar los 200 caracteres.',
            ],
            'categoria_id' => [
                'required'           => 'Selecciona una categoría.',
                'is_natural_no_zero' => 'Selecciona una categoría válida.',
            ],
            'precio_referencial' => [
                'required'                 => 'Ingresa el precio referencial.',
                'numeric'                  => 'El precio debe ser un número válido.',
                'greater_than_equal_to'    => 'El precio no puede ser negativo.',
                'less_than_equal_to'       => 'El precio ingresado es demasiado alto.',
            ],
            'estado' => [
                'required' => 'Selecciona un estado para el producto.',
                'in_list'  => 'Selecciona un estado válido.',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return false;
        }

        if ($this->categoriaModel->findDelNegocio((int) $data['categoria_id'], $idNegocio) === null) {
            $this->validator->setError(
                'categoria_id',
                'Selecciona una categoría válida de tu negocio.'
            );

            return false;
        }

        return true;
    }

    private function precio(string $precio): string
    {
        return number_format((float) $precio, 2, '.', '');
    }

    private function imagenDataUri(?array $imagen): ?string
    {
        if ($imagen === null || empty($imagen['ruta_imagen'])) {
            return null;
        }

        $ruta = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR
            . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $imagen['ruta_imagen']), DIRECTORY_SEPARATOR);

        if (! is_file($ruta)) {
            return null;
        }

        $mime = @mime_content_type($ruta);

        if ($mime === false || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $contenido = @file_get_contents($ruta);

        if ($contenido === false) {
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

    private function registrarLog(
        string $accion,
        int $idUsuario,
        int $idNegocio,
        int $registroId,
        string $descripcion
    ): void {
        $this->logModel->insert([
            'usuario_id'     => $idUsuario,
            'negocio_id'     => $idNegocio,
            'accion'         => $accion,
            'tabla_afectada' => 'productos',
            'registro_id'    => $registroId,
            'descripcion'    => mb_substr($descripcion, 0, 500),
            'ip_address'     => $this->request->getIPAddress(),
            'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
        ]);
    }
}
