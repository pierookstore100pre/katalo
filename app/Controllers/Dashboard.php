<?php

namespace App\Controllers;

use App\Models\NegocioModel;
use App\Models\PlanModel;
use App\Models\SuscripcionModel;

class Dashboard extends BaseController
{
    public function index()
    {
        if (session()->get('rol_plataforma') === 'administrador') {
            return redirect()->to(base_url('admin/dashboard'));
        }

        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0');

        $idNegocio    = (int) session()->get('id_negocio');
        $negocioModel = model(NegocioModel::class);
        $negocio      = $negocioModel
            ->where('id_negocio', $idNegocio)
            ->where('activo', 1)
            ->first();

        if ($negocio === null) {
            session()->destroy();

            return redirect()->to(base_url('auth/login'))->with(
                'error',
                'Tu negocio no se encuentra activo. Inicia sesión nuevamente.'
            );
        }

        $suscripcion = model(SuscripcionModel::class)
            ->where('negocio_id', $idNegocio)
            ->whereIn('estado', ['prueba', 'activa'])
            ->first();

        $plan = null;
        if ($suscripcion !== null) {
            $plan = model(PlanModel::class)->find((int) $suscripcion['plan_id']);
        }

        $tieneReportes = $plan !== null && (int) ($plan['tiene_reportes'] ?? 0) === 1;
        $esPlanPro     = $plan !== null && (
            ($plan['slug'] ?? '') === 'pro'
            || ($plan['max_usuarios'] !== null && (int) $plan['max_usuarios'] > 1)
        );

        return view('dashboard/index', [
            'negocio'       => $negocio,
            'suscripcion'   => $suscripcion,
            'plan'          => $plan,
            'tiene_reportes' => $tieneReportes,
            'es_plan_pro'   => $esPlanPro,
            'ciudad_slug'   => $negocioModel->obtenerCiudadSlug($idNegocio),
        ]);
    }
}
