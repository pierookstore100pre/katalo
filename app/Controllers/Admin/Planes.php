<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LogActividadModel;
use App\Models\PlanModel;
use RuntimeException;
use Throwable;

class Planes extends BaseController
{
    protected PlanModel $planModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->planModel = model(PlanModel::class);
        $this->logModel  = model(LogActividadModel::class);
    }

    public function index()
    {
        return view('admin/planes/index', [
            'titulo' => 'Planes',
            'planes' => $this->planModel->listarConSuscripciones(),
        ]);
    }

    public function editar($id = null)
    {
        $plan = $this->planModel->find((int) $id);

        if ($plan === null) {
            return redirect()->to(base_url('admin/planes'))->with(
                'error',
                'El plan no existe.'
            );
        }

        return view('admin/planes/form', [
            'titulo' => 'Editar plan ' . $plan['nombre'],
            'plan'   => $plan,
            'accion' => base_url('admin/planes/editar/' . (int) $plan['id_plan']),
        ]);
    }

    public function actualizar($id = null)
    {
        $idPlan = (int) $id;
        $plan   = $this->planModel->find($idPlan);

        if ($plan === null) {
            return redirect()->to(base_url('admin/planes'))->with(
                'error',
                'El plan no existe.'
            );
        }

        $data = [
            'precio_mensual'            => trim((string) $this->request->getPost('precio_mensual')),
            'max_productos'             => trim((string) $this->request->getPost('max_productos')),
            'max_imagenes_por_producto' => trim((string) $this->request->getPost('max_imagenes_por_producto')),
            'max_usuarios'              => trim((string) $this->request->getPost('max_usuarios')),
            'max_pedidos_mes'           => trim((string) $this->request->getPost('max_pedidos_mes')),
            'descripcion'               => trim((string) $this->request->getPost('descripcion')),
        ];

        $rules = [
            'precio_mensual'            => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[99999999.99]',
            'max_productos'             => 'permit_empty|is_natural',
            'max_imagenes_por_producto' => 'required|is_natural_no_zero|less_than_equal_to[255]',
            'max_usuarios'              => 'permit_empty|is_natural|less_than_equal_to[65535]',
            'max_pedidos_mes'           => 'permit_empty|is_natural',
            'descripcion'               => 'permit_empty|max_length[255]',
        ];

        $messages = [
            'precio_mensual' => [
                'required'                => 'Ingresa el precio mensual del plan.',
                'numeric'                 => 'El precio mensual debe ser un número válido.',
                'greater_than_equal_to'   => 'El precio mensual no puede ser negativo.',
                'less_than_equal_to'      => 'El precio mensual es demasiado alto.',
            ],
            'max_productos' => [
                'is_natural' => 'El máximo de productos debe ser un número entero o quedar vacío para ilimitado.',
            ],
            'max_imagenes_por_producto' => [
                'required'           => 'Ingresa el máximo de imágenes por producto.',
                'is_natural_no_zero' => 'El máximo de imágenes por producto debe ser mayor a 0.',
                'less_than_equal_to' => 'El máximo de imágenes por producto no puede superar 255.',
            ],
            'max_usuarios' => [
                'is_natural'         => 'El máximo de usuarios debe ser un número entero o quedar vacío para ilimitado.',
                'less_than_equal_to' => 'El máximo de usuarios es demasiado alto.',
            ],
            'max_pedidos_mes' => [
                'is_natural' => 'El máximo de pedidos por mes debe ser un número entero o quedar vacío para ilimitado.',
            ],
            'descripcion' => [
                'max_length' => 'La descripción no debe superar los 255 caracteres.',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput();
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            $this->planModel->update($idPlan, [
                'precio_mensual'            => number_format((float) $data['precio_mensual'], 2, '.', ''),
                'max_productos'             => $data['max_productos'] === '' ? null : (int) $data['max_productos'],
                'max_imagenes_por_producto' => (int) $data['max_imagenes_por_producto'],
                'max_usuarios'              => $data['max_usuarios'] === '' ? null : (int) $data['max_usuarios'],
                'max_pedidos_mes'           => $data['max_pedidos_mes'] === '' ? null : (int) $data['max_pedidos_mes'],
                'tiene_dominio_propio'      => $this->request->getPost('tiene_dominio_propio') === '1' ? 1 : 0,
                'tiene_analytics'           => $this->request->getPost('tiene_analytics') === '1' ? 1 : 0,
                'tiene_reportes'            => $this->request->getPost('tiene_reportes') === '1' ? 1 : 0,
                'descripcion'               => $data['descripcion'] !== '' ? $data['descripcion'] : null,
                'modified_by'               => $idAdmin,
            ]);

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => null,
                'accion'         => 'editar_plan',
                'tabla_afectada' => 'planes',
                'registro_id'    => $idPlan,
                'descripcion'    => mb_substr(
                    'Plan ' . $plan['nombre'] . ' actualizado. '
                    . 'Precio mensual: S/ ' . number_format((float) $data['precio_mensual'], 2, '.', '') . '.',
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad del plan.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al actualizar plan: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos actualizar el plan. Revisa los datos e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('admin/planes'))->with(
            'success',
            'Plan actualizado correctamente. Las suscripciones existentes conservan su precio pactado.'
        );
    }

    public function activar($id = null)
    {
        return $this->cambiarEstado((int) $id, true);
    }

    public function desactivar($id = null)
    {
        return $this->cambiarEstado((int) $id, false);
    }

    private function cambiarEstado(int $idPlan, bool $activar)
    {
        $plan = $this->planModel->find($idPlan);

        if ($plan === null) {
            return redirect()->to(base_url('admin/planes'))->with(
                'error',
                'El plan no existe.'
            );
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            $this->planModel->update($idPlan, [
                'activo'      => $activar ? 1 : 0,
                'modified_by' => $idAdmin,
            ]);

            $accion = $activar ? 'activar_plan' : 'desactivar_plan';

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => null,
                'accion'         => $accion,
                'tabla_afectada' => 'planes',
                'registro_id'    => $idPlan,
                'descripcion'    => mb_substr(
                    'Plan ' . $plan['nombre'] . ' ' . ($activar ? 'activado' : 'desactivado') . '.',
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad del plan.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al cambiar estado de plan: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/planes'))->with(
                'error',
                'No pudimos actualizar el plan. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('admin/planes'))->with(
            'success',
            'El plan ' . $plan['nombre'] . ' fue ' . ($activar ? 'activado' : 'desactivado') . ' correctamente.'
        );
    }
}
