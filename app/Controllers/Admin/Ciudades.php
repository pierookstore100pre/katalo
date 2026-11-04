<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CiudadModel;
use App\Models\LogActividadModel;
use RuntimeException;
use Throwable;

class Ciudades extends BaseController
{
    protected CiudadModel $ciudadModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->ciudadModel = model(CiudadModel::class);
        $this->logModel    = model(LogActividadModel::class);
    }

    public function index()
    {
        $filtro = (string) $this->request->getGet('estado');

        if (! in_array($filtro, ['todas', 'activas', 'inactivas'], true)) {
            $filtro = 'todas';
        }

        return view('admin/ciudades/index', [
            'titulo'   => 'Ciudades',
            'ciudades' => $this->ciudadModel->listarConNegocios($filtro),
            'filtro'   => $filtro,
        ]);
    }

    public function activar($id = null)
    {
        return $this->cambiarEstado((int) $id, true);
    }

    public function desactivar($id = null)
    {
        return $this->cambiarEstado((int) $id, false);
    }

    private function cambiarEstado(int $idCiudad, bool $activar)
    {
        $ciudad = $this->ciudadModel->find($idCiudad);

        if ($ciudad === null) {
            return redirect()->to(base_url('admin/ciudades'))->with(
                'error',
                'La ciudad no existe.'
            );
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            $this->ciudadModel->update($idCiudad, [
                'activo'      => $activar ? 1 : 0,
                'modified_by' => $idAdmin,
            ]);

            $accion = $activar ? 'activar_ciudad' : 'desactivar_ciudad';

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => null,
                'accion'         => $accion,
                'tabla_afectada' => 'ciudades',
                'registro_id'    => $idCiudad,
                'descripcion'    => mb_substr(
                    'Ciudad ' . $ciudad['nombre'] . ' (' . $ciudad['slug'] . ') '
                    . ($activar ? 'activada' : 'desactivada') . '.',
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad de la ciudad.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al cambiar estado de ciudad: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/ciudades'))->with(
                'error',
                'No pudimos actualizar la ciudad. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('admin/ciudades'))->with(
            'success',
            'La ciudad ' . $ciudad['nombre'] . ' fue ' . ($activar ? 'activada' : 'desactivada') . ' correctamente.'
        );
    }
}
