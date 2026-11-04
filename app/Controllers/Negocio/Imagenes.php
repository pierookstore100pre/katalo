<?php

namespace App\Controllers\Negocio;

use App\Controllers\BaseController;
use App\Models\ImagenProductoModel;
use App\Models\LogActividadModel;
use App\Models\PlanModel;
use App\Models\ProductoModel;
use App\Services\PlanLimites;
use RuntimeException;
use Throwable;

class Imagenes extends BaseController
{
    private const TAMANO_MAXIMO = 2 * 1024 * 1024;

    private const MIMES_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    protected ProductoModel $productoModel;
    protected ImagenProductoModel $imagenModel;
    protected PlanModel $planModel;
    protected LogActividadModel $logModel;
    protected PlanLimites $planLimites;

    public function __construct()
    {
        $this->productoModel = model(ProductoModel::class);
        $this->imagenModel   = model(ImagenProductoModel::class);
        $this->planModel     = model(PlanModel::class);
        $this->logModel      = model(LogActividadModel::class);
        $this->planLimites   = new PlanLimites();
    }

    public function index($id = null)
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

        $plan     = $this->planModel->obtenerVigentePorNegocio($idNegocio);
        $imagenes = $this->imagenModel->listarDelProducto($idProducto);

        foreach ($imagenes as &$imagen) {
            $imagen['data_uri'] = $this->imagenDataUri($imagen);
        }
        unset($imagen);

        return view('negocio/productos/imagenes', [
            'titulo'        => 'Imágenes del producto',
            'producto'      => $producto,
            'imagenes'      => $imagenes,
            'plan'          => $plan,
            'totalImagenes' => count($imagenes),
            'maxImagenes'   => $plan !== null ? $plan['max_imagenes_por_producto'] : null,
        ]);
    }

    public function subir($id = null)
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

        $plan = $this->planModel->obtenerVigentePorNegocio($idNegocio);
        $maxImagenes = $plan !== null ? $plan['max_imagenes_por_producto'] : null;

        if (! $this->planLimites->puedeSubirImagen($idNegocio, $idProducto)) {
            return redirect()->to(base_url('negocio/productos/' . $idProducto . '/imagenes'))->with(
                'error',
                'Tu plan permite hasta ' . (int) $maxImagenes . ' imágenes por producto. Elimina una imagen o mejora tu plan.'
            );
        }

        $archivo = $this->request->getFile('imagen');

        if ($archivo === null) {
            return $this->volverConError($idProducto, 'Selecciona una imagen para subir.');
        }

        if (! $archivo->isValid()) {
            return $this->volverConError(
                $idProducto,
                $archivo->getError() === UPLOAD_ERR_NO_FILE
                    ? 'Selecciona una imagen para subir.'
                    : 'No pudimos recibir la imagen. Inténtalo nuevamente.'
            );
        }

        if ($archivo->getSize() > self::TAMANO_MAXIMO) {
            return $this->volverConError($idProducto, 'La imagen no debe superar los 2 MB.');
        }

        $rutaAbsoluta = null;

        try {
            $rutaTemporal = $archivo->getTempName();

            if (! is_file($rutaTemporal)) {
                throw new RuntimeException('El archivo temporal no existe.');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo === false) {
                throw new RuntimeException('No se pudo validar el tipo de archivo.');
            }

            $mime = finfo_file($finfo, $rutaTemporal);
            finfo_close($finfo);

            if (! is_string($mime) || ! isset(self::MIMES_PERMITIDOS[$mime])) {
                return $this->volverConError(
                    $idProducto,
                    'Formato no permitido. Sube una imagen JPG, PNG o WebP.'
                );
            }

            $extension = self::MIMES_PERMITIDOS[$mime];
            $directorio = $this->directorioProducto($idNegocio, $idProducto);

            if (! is_dir($directorio)) {
                @mkdir($directorio, 0775, true);
            }

            if (! is_dir($directorio) || ! is_writable($directorio)) {
                throw new RuntimeException('La carpeta de imágenes no es escribible.');
            }

            $nombreArchivo = hash('sha256', time() . random_bytes(8)) . '.' . $extension;
            $archivo->move($directorio, $nombreArchivo);
            $rutaAbsoluta = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;

            if (! is_file($rutaAbsoluta)) {
                throw new RuntimeException('No se pudo guardar el archivo.');
            }

            $rutaRelativa = 'uploads/productos/' . $idNegocio . '/' . $idProducto . '/' . $nombreArchivo;
            $idUsuario    = (int) session()->get('id_usuario');
            $esPrincipal  = $this->imagenModel->principalDelProducto($idProducto) === null ? 1 : 0;
            $orden        = min(255, $this->imagenModel->siguienteOrden($idProducto));

            $db = db_connect();
            $db->transStart();

            try {
                $idImagen = (int) $this->imagenModel->insert([
                    'producto_id'   => $idProducto,
                    'ruta_imagen'   => $rutaRelativa,
                    'nombre_archivo' => $nombreArchivo,
                    'es_principal'  => $esPrincipal,
                    'orden'         => $orden,
                    'created_by'    => $idUsuario,
                    'modified_by'   => $idUsuario,
                ], true);

                if ($idImagen <= 0) {
                    throw new RuntimeException('No se pudo registrar la imagen.');
                }

                $this->registrarLog(
                    'subir_imagen',
                    $idUsuario,
                    $idNegocio,
                    $idImagen,
                    'Subida de imagen para el producto ' . $producto['titulo'] . '.'
                );

                $db->transComplete();

                if ($db->transStatus() === false) {
                    throw new RuntimeException('La transacción de la imagen no se pudo confirmar.');
                }
            } catch (Throwable $e) {
                $db->transRollback();

                if (is_file($rutaAbsoluta)) {
                    @unlink($rutaAbsoluta);
                }

                throw $e;
            }
        } catch (Throwable $e) {
            if ($rutaAbsoluta !== null && is_file($rutaAbsoluta)) {
                @unlink($rutaAbsoluta);
            }

            log_message('error', 'Error al subir imagen: {message}', ['message' => $e->getMessage()]);

            return $this->volverConError(
                $idProducto,
                'No pudimos subir la imagen. Revisa el archivo e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/productos/' . $idProducto . '/imagenes'))->with(
            'success',
            'Imagen subida correctamente.'
        );
    }

    public function principal($id = null, $idImagen = null)
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $idProducto = (int) $id;
        $idImagen   = (int) $idImagen;
        $producto   = $this->productoModel->findDelNegocio($idProducto, $idNegocio);

        if ($producto === null) {
            return redirect()->to(base_url('negocio/productos'))->with(
                'error',
                'El producto no existe o no pertenece a tu negocio.'
            );
        }

        if ($this->imagenModel->findDelProducto($idImagen, $idProducto) === null) {
            return $this->volverConError($idProducto, 'La imagen no existe o no pertenece a este producto.');
        }

        $idUsuario = (int) session()->get('id_usuario');
        $db        = db_connect();
        $db->transStart();

        try {
            $this->imagenModel
                ->where('producto_id', $idProducto)
                ->where('es_principal', 1)
                ->set([
                    'es_principal' => 0,
                    'modified_by'  => $idUsuario,
                ])
                ->update();

            if (! $this->imagenModel->update($idImagen, [
                'es_principal' => 1,
                'modified_by'  => $idUsuario,
            ])) {
                throw new RuntimeException('No se pudo marcar la imagen principal.');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción de la imagen principal no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al cambiar imagen principal: {message}', ['message' => $e->getMessage()]);

            return $this->volverConError(
                $idProducto,
                'No pudimos cambiar la imagen principal. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/productos/' . $idProducto . '/imagenes'))->with(
            'success',
            'Imagen principal actualizada correctamente.'
        );
    }

    public function eliminar($id = null, $idImagen = null)
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $idProducto = (int) $id;
        $idImagen   = (int) $idImagen;
        $producto   = $this->productoModel->findDelNegocio($idProducto, $idNegocio);

        if ($producto === null) {
            return redirect()->to(base_url('negocio/productos'))->with(
                'error',
                'El producto no existe o no pertenece a tu negocio.'
            );
        }

        $imagen = $this->imagenModel->findDelProducto($idImagen, $idProducto);

        if ($imagen === null) {
            return $this->volverConError($idProducto, 'La imagen no existe o no pertenece a este producto.');
        }

        $idUsuario   = (int) session()->get('id_usuario');
        $eraPrincipal = (int) $imagen['es_principal'] === 1;
        $db          = db_connect();
        $db->transStart();

        try {
            if (! $this->imagenModel->delete($idImagen)) {
                throw new RuntimeException('No se pudo eliminar la imagen.');
            }

            if ($eraPrincipal) {
                $siguiente = $this->imagenModel
                    ->where('producto_id', $idProducto)
                    ->orderBy('orden', 'ASC')
                    ->orderBy('id_imagen', 'ASC')
                    ->first();

                if ($siguiente !== null) {
                    $this->imagenModel->update((int) $siguiente['id_imagen'], [
                        'es_principal' => 1,
                        'modified_by'  => $idUsuario,
                    ]);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción de eliminación no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al eliminar imagen: {message}', ['message' => $e->getMessage()]);

            return $this->volverConError(
                $idProducto,
                'No pudimos eliminar la imagen. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/productos/' . $idProducto . '/imagenes'))->with(
            'success',
            'Imagen eliminada correctamente.'
        );
    }

    private function directorioProducto(int $idNegocio, int $idProducto): string
    {
        return rtrim(WRITEPATH, '/\\')
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'productos'
            . DIRECTORY_SEPARATOR . $idNegocio
            . DIRECTORY_SEPARATOR . $idProducto;
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

    private function volverConError(int $idProducto, string $mensaje)
    {
        return redirect()->to(base_url('negocio/productos/' . $idProducto . '/imagenes'))->with('error', $mensaje);
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
            'tabla_afectada' => 'imagenes_producto',
            'registro_id'    => $registroId,
            'descripcion'    => mb_substr($descripcion, 0, 500),
            'ip_address'     => $this->request->getIPAddress(),
            'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
        ]);
    }
}
