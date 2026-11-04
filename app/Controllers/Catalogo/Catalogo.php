<?php

namespace App\Controllers\Catalogo;

use App\Controllers\BaseController;
use App\Models\CategoriaModel;
use App\Models\ImagenProductoModel;
use App\Models\NegocioPublicoModel;
use App\Models\ProductoPublicoModel;

class Catalogo extends BaseController
{
    protected NegocioPublicoModel $negocioModel;
    protected ProductoPublicoModel $productoModel;
    protected CategoriaModel $categoriaModel;
    protected ImagenProductoModel $imagenModel;

    public function __construct()
    {
        $this->negocioModel  = model(NegocioPublicoModel::class);
        $this->productoModel = model(ProductoPublicoModel::class);
        $this->categoriaModel = model(CategoriaModel::class);
        $this->imagenModel   = model(ImagenProductoModel::class);
    }

    /**
     * Estrategia elegida: el filtro por categoría y la búsqueda se resuelven
     * en el mismo GET /c/{slug} mediante query params (render del lado del
     * servidor, sin AJAX). El endpoint JSON /c/{slug}/productos queda
     * disponible con la misma lógica para consumos futuros.
     *
     * La búsqueda usa LIKE sobre título y descripción para el MVP. Si el
     * catálogo crece, se migrará a un índice FULLTEXT.
     */
    public function index($slug = null)
    {
        $negocio = $this->negocioModel->findPorSlug((string) $slug);

        if ($negocio === null) {
            return $this->catalogoNoDisponible();
        }

        $idNegocio  = (int) $negocio['id_negocio'];
        $categorias = $this->categoriaModel->listarActivasConProductosVisibles($idNegocio);

        $categoriaActiva = mb_substr(trim((string) $this->request->getGet('categoria')), 0, 120);
        $busqueda        = mb_substr(trim((string) $this->request->getGet('q')), 0, 100);
        $productos = $this->productoModel->listarPorNegocio(
            $idNegocio,
            $categoriaActiva !== '' ? $categoriaActiva : null,
            $busqueda
        );

        $this->adjuntarImagenes($productos);

        return view('catalogo/index', [
            'titulo'          => $negocio['nombre_comercial'],
            'negocio'         => $negocio,
            'categorias'      => $categorias,
            'categoriaActiva' => $categoriaActiva,
            'busqueda'        => $busqueda,
            'productos'       => $productos,
        ]);
    }

    public function productos($slug = null)
    {
        $negocio = $this->negocioModel->findPorSlug((string) $slug);

        if ($negocio === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'Este catálogo no está disponible o el negocio ya no opera en Katalo.pe',
            ]);
        }

        $idNegocio = (int) $negocio['id_negocio'];

        $categoriaActiva = mb_substr(trim((string) $this->request->getGet('categoria')), 0, 120);
        $busqueda        = mb_substr(trim((string) $this->request->getGet('q')), 0, 100);
        $productos       = $this->productoModel->listarPorNegocio(
            $idNegocio,
            $categoriaActiva !== '' ? $categoriaActiva : null,
            $busqueda
        );

        $this->adjuntarImagenes($productos);

        $salida = array_map(static function (array $producto): array {
            return [
                'id_producto'      => (int) $producto['id_producto'],
                'titulo'           => $producto['titulo'],
                'descripcion'      => $producto['descripcion'],
                'precio'           => number_format((float) $producto['precio_referencial'], 2, '.', ''),
                'destacado'        => (int) $producto['destacado'] === 1,
                'categoria_slug'   => $producto['categoria_slug'],
                'categoria_nombre' => $producto['categoria_nombre'],
                'imagen_url'       => $producto['imagen_id'] !== null
                    ? base_url('c/imagen/' . $producto['imagen_id'])
                    : null,
            ];
        }, $productos);

        return $this->response->setJSON([
            'negocio' => [
                'nombre_comercial' => $negocio['nombre_comercial'],
                'slug'             => $negocio['slug'],
            ],
            'productos' => $salida,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $productos
     */
    private function adjuntarImagenes(array &$productos): void
    {
        $ids = array_map(
            static fn (array $producto): int => (int) $producto['id_producto'],
            $productos
        );

        $principales = $this->imagenModel->listarPrincipalesPorProductos($ids);

        foreach ($productos as &$producto) {
            $imagen = $principales[(int) $producto['id_producto']] ?? null;
            $producto['imagen_id'] = $imagen !== null ? (int) $imagen['id_imagen'] : null;
        }
        unset($producto);
    }

    private function catalogoNoDisponible()
    {
        return $this->response
            ->setStatusCode(404)
            ->setBody(view('catalogo/no_disponible', [
                'titulo' => 'Catálogo no disponible',
            ]));
    }
}
