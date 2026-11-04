<?php

namespace App\Controllers\Catalogo;

use App\Controllers\BaseController;
use App\Models\ImagenProductoModel;
use App\Models\NegocioPublicoModel;
use App\Models\ProductoPublicoModel;

class Imagen extends BaseController
{
    private const MIMES_PERMITIDOS = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    protected ImagenProductoModel $imagenModel;
    protected ProductoPublicoModel $productoModel;
    protected NegocioPublicoModel $negocioModel;

    public function __construct()
    {
        $this->imagenModel    = model(ImagenProductoModel::class);
        $this->productoModel  = model(ProductoPublicoModel::class);
        $this->negocioModel   = model(NegocioPublicoModel::class);
    }

    public function ver($idImagen = null)
    {
        $idImagen = (int) $idImagen;

        if ($idImagen <= 0) {
            return $this->noEncontrada();
        }

        $imagen = $this->imagenModel->find($idImagen);

        if ($imagen === null) {
            return $this->noEncontrada();
        }

        $producto = $this->productoModel->find((int) $imagen['producto_id']);

        if ($producto === null) {
            return $this->noEncontrada();
        }

        $idNegocio  = (int) $producto['negocio_id'];
        $idProducto = (int) $producto['id_producto'];

        if ($this->negocioModel->find($idNegocio) === null) {
            return $this->noEncontrada();
        }

        $nombreArchivo = basename((string) $imagen['nombre_archivo']);

        if ($nombreArchivo === '' || $nombreArchivo === '.' || $nombreArchivo === '..') {
            return $this->noEncontrada();
        }

        $ruta = rtrim(WRITEPATH, '/\\')
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'productos'
            . DIRECTORY_SEPARATOR . $idNegocio
            . DIRECTORY_SEPARATOR . $idProducto
            . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (! is_file($ruta)) {
            return $this->noEncontrada();
        }

        $mime = @mime_content_type($ruta);

        if ($mime === false || ! in_array($mime, self::MIMES_PERMITIDOS, true)) {
            return $this->noEncontrada();
        }

        $contenido = @file_get_contents($ruta);

        if ($contenido === false) {
            return $this->noEncontrada();
        }

        return $this->response
            ->removeHeader('Cache-Control')
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string) strlen($contenido))
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($contenido);
    }

    private function noEncontrada()
    {
        return $this->response
            ->setStatusCode(404)
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setBody('Imagen no disponible.');
    }
}
