<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoriaModel;
use App\Models\ImagenProductoModel;
use App\Models\LogActividadModel;
use App\Models\NegocioModel;
use App\Models\ProductoModel;
use RuntimeException;
use Throwable;

class Moderacion extends BaseController
{
    protected ProductoModel $productoModel;
    protected CategoriaModel $categoriaModel;
    protected NegocioModel $negocioModel;
    protected ImagenProductoModel $imagenModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->productoModel  = model(ProductoModel::class);
        $this->categoriaModel = model(CategoriaModel::class);
        $this->negocioModel   = model(NegocioModel::class);
        $this->imagenModel    = model(ImagenProductoModel::class);
        $this->logModel       = model(LogActividadModel::class);
    }

    public function index()
    {
        return view('admin/moderacion/index', [
            'titulo'     => 'Moderación',
            'productos'  => $this->enriquecerProductos($this->logModel->obtenerMarcadosPorTabla('productos')),
            'categorias' => $this->enriquecerCategorias($this->logModel->obtenerMarcadosPorTabla('categorias')),
            'negocios'   => $this->enriquecerNegocios($this->logModel->obtenerMarcadosPorTabla('negocios')),
            'imagenes'   => $this->imagenModel->listarRecientes(7),
        ]);
    }

    /**
     * @param list<array<string, mixed>> $filas
     *
     * @return list<array<string, mixed>>
     */
    private function enriquecerProductos(array $filas): array
    {
        $datosMarcado = $this->datosMarcado('productos', array_column($filas, 'id'));

        foreach ($filas as &$fila) {
            $id = (int) ($fila['id'] ?? 0);

            if ($id <= 0) {
                $fila['datos'] = null;

                continue;
            }

            $producto = db_connect()->table('productos')
                ->select('productos.id_producto, productos.titulo, productos.descripcion, productos.precio_referencial, productos.categoria_id, productos.negocio_id, categorias.nombre AS categoria_nombre')
                ->join('categorias', 'categorias.id_categoria = productos.categoria_id', 'left')
                ->where('productos.id_producto', $id)
                ->where('productos.deleted_at IS NULL', null, false)
                ->get(1)
                ->getRowArray();

            if ($producto !== null) {
                $fila['titulo']             = $producto['titulo'];
                $fila['descripcion']        = $producto['descripcion'];
                $fila['precio_referencial'] = $producto['precio_referencial'];
                $fila['categoria_id']       = $producto['categoria_id'];
                $fila['negocio_id']         = $producto['negocio_id'];
                $fila['categoria_nombre']   = $producto['categoria_nombre'];
            } else {
                $fila['categoria_nombre'] = null;
            }

            $imagen = db_connect()->table('imagenes_producto')
                ->select('id_imagen')
                ->where('producto_id', $id)
                ->where('es_principal', 1)
                ->where('deleted_at IS NULL', null, false)
                ->orderBy('id_imagen', 'DESC')
                ->get(1)
                ->getRowArray();

            $fila['id_imagen'] = $imagen['id_imagen'] ?? null;
            $fila['datos']     = $datosMarcado[$id] ?? null;
        }
        unset($fila);

        return $filas;
    }

    /**
     * @param list<array<string, mixed>> $filas
     *
     * @return list<array<string, mixed>>
     */
    private function enriquecerCategorias(array $filas): array
    {
        $datosMarcado = $this->datosMarcado('categorias', array_column($filas, 'id'));

        foreach ($filas as &$fila) {
            $id = (int) ($fila['id'] ?? 0);

            if ($id <= 0) {
                $fila['datos'] = null;

                continue;
            }

            $categoria = db_connect()->table('categorias')
                ->select('id_categoria, nombre, descripcion, negocio_id')
                ->where('id_categoria', $id)
                ->where('deleted_at IS NULL', null, false)
                ->get(1)
                ->getRowArray();

            if ($categoria !== null) {
                $fila['titulo']      = $categoria['nombre'];
                $fila['descripcion'] = $categoria['descripcion'];
                $fila['negocio_id']  = $categoria['negocio_id'];
            }

            $fila['datos'] = $datosMarcado[$id] ?? null;
        }
        unset($fila);

        return $filas;
    }

    /**
     * @param list<array<string, mixed>> $filas
     *
     * @return list<array<string, mixed>>
     */
    private function enriquecerNegocios(array $filas): array
    {
        $datosMarcado = $this->datosMarcado('negocios', array_column($filas, 'id'));

        foreach ($filas as &$fila) {
            $id = (int) ($fila['id'] ?? 0);

            if ($id <= 0) {
                $fila['datos'] = null;

                continue;
            }

            $negocio = db_connect()->table('negocios')
                ->select('id_negocio, nombre_comercial, descripcion')
                ->where('id_negocio', $id)
                ->where('deleted_at IS NULL', null, false)
                ->get(1)
                ->getRowArray();

            if ($negocio !== null) {
                $fila['titulo']          = $negocio['nombre_comercial'];
                $fila['negocio_nombre']  = $negocio['nombre_comercial'];
                $fila['descripcion']     = $negocio['descripcion'];
            }

            $fila['datos'] = $datosMarcado[$id] ?? null;
        }
        unset($fila);

        return $filas;
    }

    /**
     * @param list<int|string> $ids
     *
     * @return array<int, array<string, mixed>|null>
     */
    private function datosMarcado(string $tabla, array $ids): array
    {
        $ids = array_values(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        ));

        if ($ids === []) {
            return [];
        }

        $filas = db_connect()->table('logs_actividad')
            ->select('registro_id, datos')
            ->where('tabla_afectada', $tabla)
            ->where('accion', 'contenido_marcado')
            ->whereIn('registro_id', $ids)
            ->orderBy('id_log', 'DESC')
            ->get()
            ->getResultArray();

        $resultado = [];

        foreach ($filas as $fila) {
            $id = (int) $fila['registro_id'];

            if (array_key_exists($id, $resultado)) {
                continue;
            }

            $resultado[$id] = $this->decodificarDatos($fila['datos'] ?? null);
        }

        return $resultado;
    }

    private function decodificarDatos(mixed $datos): ?array
    {
        if (is_array($datos)) {
            return $datos === [] ? null : $datos;
        }

        if (! is_string($datos) || trim($datos) === '') {
            return null;
        }

        $decodificado = json_decode($datos, true);

        return is_array($decodificado) && $decodificado !== [] ? $decodificado : null;
    }

    public function aprobarProducto($id = null)
    {
        return $this->cambiarProducto((int) $id, true);
    }

    public function rechazarProducto($id = null)
    {
        return $this->cambiarProducto((int) $id, false);
    }

    public function aprobarCategoria($id = null)
    {
        return $this->cambiarCategoria((int) $id, true);
    }

    public function rechazarCategoria($id = null)
    {
        return $this->cambiarCategoria((int) $id, false);
    }

    public function aprobarNegocio($id = null)
    {
        $idNegocio = (int) $id;
        $negocio   = $this->negocioModel->find($idNegocio);

        if ($negocio === null) {
            return $this->volverConError('El negocio no existe.');
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            if (! $this->negocioModel->update($idNegocio, [
                'activo'      => 1,
                'modified_by' => $idAdmin,
            ])) {
                throw new RuntimeException('No se pudo aprobar el negocio.');
            }

            $this->registrarLog(
                'negocio_aprobado',
                'negocios',
                $idNegocio,
                $idNegocio,
                'Negocio aprobado: ' . $negocio['nombre_comercial'] . '.'
            );

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al aprobar negocio: {message}', ['message' => $e->getMessage()]);

            return $this->volverConError('No pudimos aprobar el negocio. Inténtalo nuevamente.');
        }

        return $this->volverConExito('El negocio fue aprobado correctamente.');
    }

    public function aprobarImagen($id = null)
    {
        $idImagen = (int) $id;
        $imagen   = $this->imagenModel->find($idImagen);

        if ($imagen === null) {
            return $this->volverConError('La imagen no existe.');
        }

        $producto = $this->productoModel->find((int) $imagen['producto_id']);

        if ($producto === null) {
            return $this->volverConError('El producto de la imagen no existe.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $this->registrarLog(
                'imagen_aprobada',
                'imagenes_producto',
                $idImagen,
                (int) $producto['negocio_id'],
                'Imagen aprobada del producto ' . $producto['titulo'] . '.'
            );

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al aprobar imagen: {message}', ['message' => $e->getMessage()]);

            return $this->volverConError('No pudimos aprobar la imagen. Inténtalo nuevamente.');
        }

        return $this->volverConExito('La imagen fue aprobada correctamente.');
    }

    public function rechazarImagen($id = null)
    {
        $idImagen = (int) $id;
        $imagen   = $this->imagenModel->find($idImagen);

        if ($imagen === null) {
            return $this->volverConError('La imagen no existe.');
        }

        $producto = $this->productoModel->find((int) $imagen['producto_id']);

        if ($producto === null) {
            return $this->volverConError('El producto de la imagen no existe.');
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            $this->imagenModel->update($idImagen, ['modified_by' => $idAdmin]);

            if (! $this->imagenModel->delete($idImagen)) {
                throw new RuntimeException('No se pudo rechazar la imagen.');
            }

            $this->registrarLog(
                'imagen_rechazada',
                'imagenes_producto',
                $idImagen,
                (int) $producto['negocio_id'],
                'Imagen rechazada del producto ' . $producto['titulo'] . '.'
            );

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al rechazar imagen: {message}', ['message' => $e->getMessage()]);

            return $this->volverConError('No pudimos rechazar la imagen. Inténtalo nuevamente.');
        }

        return $this->volverConExito('La imagen fue rechazada correctamente.');
    }

    private function cambiarProducto(int $idProducto, bool $aprobar)
    {
        $producto = $this->productoModel->find($idProducto);

        if ($producto === null) {
            return $this->volverConError('El producto no existe.');
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            if (! $this->productoModel->update($idProducto, [
                'visible'     => $aprobar ? 1 : 0,
                'modified_by' => $idAdmin,
            ])) {
                throw new RuntimeException('No se pudo actualizar el producto.');
            }

            $this->registrarLog(
                $aprobar ? 'contenido_aprobado' : 'contenido_rechazado',
                'productos',
                $idProducto,
                (int) $producto['negocio_id'],
                ($aprobar ? 'Producto aprobado: ' : 'Producto rechazado: ') . $producto['titulo'] . '.'
            );

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al moderar producto: {message}', ['message' => $e->getMessage()]);

            return $this->volverConError('No pudimos actualizar el producto. Inténtalo nuevamente.');
        }

        return $this->volverConExito(
            $aprobar ? 'El producto fue aprobado correctamente.' : 'El producto fue rechazado correctamente.'
        );
    }

    private function cambiarCategoria(int $idCategoria, bool $aprobar)
    {
        $categoria = $this->categoriaModel->find($idCategoria);

        if ($categoria === null) {
            return $this->volverConError('La categoría no existe.');
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            if (! $this->categoriaModel->update($idCategoria, [
                'activo'      => $aprobar ? 1 : 0,
                'modified_by' => $idAdmin,
            ])) {
                throw new RuntimeException('No se pudo actualizar la categoría.');
            }

            $this->registrarLog(
                $aprobar ? 'contenido_aprobado' : 'contenido_rechazado',
                'categorias',
                $idCategoria,
                (int) $categoria['negocio_id'],
                ($aprobar ? 'Categoría aprobada: ' : 'Categoría rechazada: ') . $categoria['nombre'] . '.'
            );

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al moderar categoría: {message}', ['message' => $e->getMessage()]);

            return $this->volverConError('No pudimos actualizar la categoría. Inténtalo nuevamente.');
        }

        return $this->volverConExito(
            $aprobar ? 'La categoría fue aprobada correctamente.' : 'La categoría fue rechazada correctamente.'
        );
    }

    private function registrarLog(
        string $accion,
        string $tabla,
        int $registroId,
        ?int $negocioId,
        string $descripcion
    ): void {
        $insertado = $this->logModel->insert([
            'usuario_id'     => (int) session()->get('id_usuario'),
            'negocio_id'     => $negocioId,
            'accion'         => $accion,
            'tabla_afectada' => $tabla,
            'registro_id'    => $registroId,
            'descripcion'    => mb_substr($descripcion, 0, 500),
            'ip_address'     => $this->request->getIPAddress(),
            'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
        ]);

        if ($insertado === false) {
            throw new RuntimeException('No se pudo registrar la actividad de moderación.');
        }
    }

    private function volverConExito(string $mensaje)
    {
        return redirect()->to(base_url('admin/moderacion'))->with('success', $mensaje);
    }

    private function volverConError(string $mensaje)
    {
        return redirect()->to(base_url('admin/moderacion'))->with('error', $mensaje);
    }
}
