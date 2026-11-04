<?php

namespace App\Controllers\Catalogo;

use App\Controllers\BaseController;
use App\Models\NegocioPublicoModel;

class Redireccion extends BaseController
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

    protected NegocioPublicoModel $negocioModel;

    public function __construct()
    {
        $this->negocioModel = model(NegocioPublicoModel::class);
    }

    public function index($slug = null)
    {
        $slugNegocio = trim((string) $slug);

        if ($slugNegocio === '' || $this->esSlugReservado($slugNegocio)) {
            return $this->noDisponible();
        }

        $negocio = $this->negocioModel->findPorSlug($slugNegocio);

        if ($negocio === null) {
            return $this->noDisponible();
        }

        $ciudadSlug = $this->negocioModel->obtenerCiudadSlugPorNegocio($slugNegocio);

        if ($ciudadSlug === null || $ciudadSlug === '') {
            return $this->noDisponible();
        }

        return redirect()->to(
            base_url($ciudadSlug . '/' . $slugNegocio),
            301,
            'location'
        );
    }

    private function esSlugReservado(string $valor): bool
    {
        return in_array(strtolower($valor), self::SLUGS_RESERVADOS, true);
    }

    private function noDisponible()
    {
        return $this->response
            ->setStatusCode(404)
            ->setBody(view('catalogo/no_disponible', [
                'titulo' => 'Catálogo no disponible',
            ]));
    }
}
