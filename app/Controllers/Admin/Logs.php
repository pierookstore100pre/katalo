<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LogActividadModel;
use App\Models\NegocioModel;
use App\Models\UsuarioModel;

class Logs extends BaseController
{
    protected LogActividadModel $logModel;
    protected NegocioModel $negocioModel;
    protected UsuarioModel $usuarioModel;

    public function __construct()
    {
        $this->logModel     = model(LogActividadModel::class);
        $this->negocioModel = model(NegocioModel::class);
        $this->usuarioModel = model(UsuarioModel::class);
    }

    public function index()
    {
        $filtros = [
            'desde'      => $this->normalizarFecha((string) $this->request->getGet('desde')),
            'hasta'      => $this->normalizarFecha((string) $this->request->getGet('hasta')),
            'accion'     => trim((string) $this->request->getGet('accion')),
            'negocio_id' => (int) $this->request->getGet('negocio_id'),
            'usuario_id' => (int) $this->request->getGet('usuario_id'),
        ];

        if ($filtros['desde'] !== '' && $filtros['hasta'] !== '' && $filtros['desde'] > $filtros['hasta']) {
            [$filtros['desde'], $filtros['hasta']] = [$filtros['hasta'], $filtros['desde']];
        }

        $logs = $this->logModel->listarConFiltros($filtros, 50);

        return view('admin/logs/index', [
            'titulo'   => 'Registro de actividad',
            'logs'     => $logs,
            'filtros'  => $filtros,
            'acciones' => $this->logModel->obtenerAcciones(),
            'negocios' => $this->negocioModel
                ->select('id_negocio, nombre_comercial')
                ->orderBy('nombre_comercial', 'ASC')
                ->findAll(),
            'usuarios' => $this->usuarioModel
                ->select('id_usuario, email')
                ->orderBy('email', 'ASC')
                ->findAll(),
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
