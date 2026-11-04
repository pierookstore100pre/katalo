<?php

namespace App\Controllers\Negocio;

use App\Controllers\BaseController;
use App\Models\LogActividadModel;

class Logs extends BaseController
{
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->logModel = model(LogActividadModel::class);
    }

    public function index()
    {
        $idNegocio = (int) session()->get('id_negocio');

        if ($idNegocio <= 0) {
            session()->destroy();

            return redirect()->to(base_url('auth/login'))->with(
                'error',
                'Debes iniciar sesión con un negocio activo.'
            );
        }

        $filtros = [
            'desde'  => $this->normalizarFecha((string) $this->request->getGet('desde')),
            'hasta'  => $this->normalizarFecha((string) $this->request->getGet('hasta')),
            'accion' => trim((string) $this->request->getGet('accion')),
        ];

        if ($filtros['desde'] !== '' && $filtros['hasta'] !== '' && $filtros['desde'] > $filtros['hasta']) {
            [$filtros['desde'], $filtros['hasta']] = [$filtros['hasta'], $filtros['desde']];
        }

        return view('negocio/logs/index', [
            'titulo'   => 'Registro de actividad',
            'logs'     => $this->logModel->listarPorNegocio($idNegocio, $filtros, 50),
            'filtros'  => $filtros,
            'acciones' => $this->logModel->obtenerAccionesPorNegocio($idNegocio),
            'pager'    => $this->logModel->pager,
        ]);
    }

    private function normalizarFecha(string $fecha): string
    {
        $fecha = trim($fecha);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return '';
        }

        [$anio, $mes, $dia] = array_map('intval', explode('-', $fecha));

        return checkdate($mes, $dia, $anio) ? $fecha : '';
    }
}
