<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LogActividadModel;
use App\Models\NegocioModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

class Impersonacion extends BaseController
{
    protected NegocioModel $negocioModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->negocioModel = model(NegocioModel::class);
        $this->logModel     = model(LogActividadModel::class);
    }

    public function iniciar($id = null)
    {
        $session = session();

        if ($session->get('rol_plataforma') !== 'administrador') {
            return redirect()->to(base_url('dashboard'))->with(
                'error',
                'Acceso denegado. Esta sección es solo para administradores.'
            );
        }

        if ($session->get('impersonando') === true) {
            return redirect()->to(base_url('admin/dashboard'))->with(
                'error',
                'Ya estás en una impersonación activa'
            );
        }

        $idNegocio = (int) $id;
        $negocio   = $this->negocioModel->find($idNegocio);

        if ($negocio === null) {
            throw PageNotFoundException::forPageNotFound('El negocio no existe.');
        }

        $propietario = $this->negocioModel->obtenerPropietarioActivo($idNegocio);

        if ($propietario === null) {
            return $this->error500(
                'El negocio no tiene propietario activo. Revisa la integridad en /admin/negocios.'
            );
        }

        if ((int) ($propietario['es_administrador'] ?? 0) === 1) {
            return redirect()->to(base_url('admin/negocios/' . $idNegocio))->with(
                'error',
                'No se puede impersonar a un administrador.'
            );
        }

        // Si el admin cierra sesión mientras impersona, Auth\Logout::index()
        // destruye toda la sesión, incluida 'admin_original'. Es aceptable
        // para el MVP: no queda rastro de la impersonación al volver a entrar.
        $adminOriginal = [
            'id_usuario'       => $session->get('id_usuario'),
            'id_negocio'       => $session->get('id_negocio'),
            'rol_negocio'      => $session->get('rol_negocio'),
            'rol_plataforma'   => $session->get('rol_plataforma'),
            'nombre_comercial' => $session->get('nombre_comercial'),
            'email'            => $session->get('email'),
        ];

        $session->set('admin_original', $adminOriginal);

        $session->set([
            'id_usuario'       => (int) $propietario['usuario_id'],
            'id_negocio'       => $idNegocio,
            'rol_negocio'      => 'propietario',
            'rol_plataforma'   => 'usuario_negocio',
            'nombre_comercial' => $negocio['nombre_comercial'],
            'email'            => $propietario['email'],
            'impersonando'     => true,
        ]);

        try {
            if ($this->logModel->insert([
                'usuario_id'     => (int) $adminOriginal['id_usuario'],
                'negocio_id'     => $idNegocio,
                'accion'         => 'impersonacion_iniciada',
                'tabla_afectada' => 'negocios',
                'registro_id'    => $idNegocio,
                'descripcion'    => mb_substr(
                    'Admin ' . (string) $adminOriginal['email']
                    . ' impersona a ' . $negocio['nombre_comercial']
                    . ' (' . $negocio['slug'] . ')',
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la impersonación.');
            }
        } catch (Throwable $e) {
            $session->set($adminOriginal);
            $session->remove('impersonando');
            $session->remove('admin_original');
            log_message('error', 'Error al iniciar impersonación: {message}', ['message' => $e->getMessage()]);

            return $this->error500('No se pudo iniciar la impersonación. Inténtalo nuevamente.');
        }

        return redirect()->to(base_url('dashboard'))->with(
            'success',
            'Ahora estás viendo el panel como ' . $negocio['nombre_comercial']
        );
    }

    public function salir()
    {
        $session = session();

        if ($session->get('impersonando') !== true) {
            return redirect()->to(base_url('dashboard'))->with(
                'error',
                'No hay impersonación activa'
            );
        }

        $original = $session->get('admin_original');

        if (! is_array($original) || ! isset($original['id_usuario'])) {
            return $this->error500('No se encontró la sesión original del administrador.');
        }

        $idNegocio       = (int) $session->get('id_negocio');
        $nombreComercial = (string) $session->get('nombre_comercial');
        $emailAdmin      = (string) ($original['email'] ?? '');

        $session->set($original);
        $session->remove('impersonando');
        $session->remove('admin_original');

        try {
            if ($this->logModel->insert([
                'usuario_id'     => (int) $original['id_usuario'],
                'negocio_id'     => $idNegocio > 0 ? $idNegocio : null,
                'accion'         => 'impersonacion_finalizada',
                'tabla_afectada' => 'negocios',
                'registro_id'    => $idNegocio > 0 ? $idNegocio : null,
                'descripcion'    => mb_substr(
                    'Admin ' . $emailAdmin . ' finaliza impersonación de '
                    . ($nombreComercial !== '' ? $nombreComercial : 'negocio #' . $idNegocio),
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la salida de la impersonación.');
            }
        } catch (Throwable $e) {
            log_message('error', 'Error al finalizar impersonación: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/dashboard'))->with(
                'error',
                'Has vuelto a tu panel, pero no se pudo registrar la actividad de la impersonación.'
            );
        }

        return redirect()->to(base_url('admin/dashboard'))->with(
            'success',
            'Has vuelto a tu panel de administrador'
        );
    }

    private function error500(string $mensaje): ResponseInterface
    {
        return $this->response
            ->setStatusCode(500)
            ->setBody(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
                . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
                . '<title>Error · Katalo.pe</title></head>'
                . '<body style="margin:0;padding:40px 20px;background:#f1f5f9;color:#0f172a;'
                . 'font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,sans-serif">'
                . '<div style="max-width:640px;margin:0 auto;padding:24px;background:#fff;'
                . 'border:1px solid #e2e8f0;border-radius:16px">'
                . '<h1 style="margin:0 0 8px;font-size:1.35rem">No se pudo completar la operación</h1>'
                . '<p style="margin:0;color:#64748b">' . esc($mensaje) . '</p>'
                . '</div></body></html>'
            );
    }
}
