<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LogActividadModel;
use App\Models\NegocioModel;
use App\Models\PagoModel;
use App\Models\PlanModel;
use App\Models\SuscripcionModel;
use RuntimeException;
use Throwable;

class Pagos extends BaseController
{
    protected PagoModel $pagoModel;
    protected SuscripcionModel $suscripcionModel;
    protected PlanModel $planModel;
    protected NegocioModel $negocioModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->pagoModel        = model(PagoModel::class);
        $this->suscripcionModel = model(SuscripcionModel::class);
        $this->planModel        = model(PlanModel::class);
        $this->negocioModel     = model(NegocioModel::class);
        $this->logModel         = model(LogActividadModel::class);
    }

    public function index()
    {
        $filtro = (string) $this->request->getGet('estado');

        if (! in_array($filtro, ['todos', 'pendiente', 'confirmado', 'rechazado'], true)) {
            $filtro = 'todos';
        }

        return view('admin/pagos/index', [
            'titulo'     => 'Pagos de suscripción',
            'pendientes' => $this->suscripcionModel->listarPendientesPago(),
            'pagos'      => $this->pagoModel->listarConDetalle($filtro),
            'filtro'     => $filtro,
        ]);
    }

    public function registrar($idSuscripcion = null)
    {
        $suscripcion = $this->suscripcionPendiente((int) $idSuscripcion);

        if ($suscripcion === null) {
            return redirect()->to(base_url('admin/pagos'))->with(
                'error',
                'La suscripción no existe o no está pendiente de pago.'
            );
        }

        $plan    = $this->planModel->find((int) $suscripcion['plan_id']);
        $negocio = $this->negocioModel->find((int) $suscripcion['negocio_id']);

        return view('admin/pagos/form', [
            'titulo'       => 'Registrar pago',
            'suscripcion'  => $suscripcion,
            'plan'         => $plan,
            'negocio'      => $negocio,
            'montoDefault' => number_format((float) $suscripcion['precio_pactado'], 2, '.', ''),
            'desdeDefault' => date('Y-m-d'),
            'hastaDefault' => date('Y-m-d', strtotime('+1 month')),
            'pagoDefault'  => date('Y-m-d'),
        ]);
    }

    public function guardar($idSuscripcion = null)
    {
        $suscripcion = $this->suscripcionPendiente((int) $idSuscripcion);

        if ($suscripcion === null) {
            return redirect()->to(base_url('admin/pagos'))->with(
                'error',
                'La suscripción no existe o no está pendiente de pago.'
            );
        }

        $idAdmin = (int) session()->get('id_usuario');
        $data    = [
            'monto'           => trim((string) $this->request->getPost('monto')),
            'metodo'          => (string) $this->request->getPost('metodo'),
            'referencia'      => trim((string) $this->request->getPost('referencia')),
            'comprobante_ruta' => trim((string) $this->request->getPost('comprobante_ruta')),
            'periodo_desde'   => trim((string) $this->request->getPost('periodo_desde')),
            'periodo_hasta'   => trim((string) $this->request->getPost('periodo_hasta')),
            'fecha_pago'      => trim((string) $this->request->getPost('fecha_pago')),
        ];

        $rules = [
            'monto'            => 'required|numeric|greater_than[0]|less_than_equal_to[99999999.99]',
            'metodo'           => 'required|in_list[transferencia,yape,plin,efectivo,otro]',
            'referencia'       => 'permit_empty|max_length[100]',
            'comprobante_ruta' => 'permit_empty|max_length[255]',
            'periodo_desde'    => 'required|valid_date',
            'periodo_hasta'    => 'required|valid_date',
            'fecha_pago'       => 'required|valid_date',
        ];

        $messages = [
            'monto' => [
                'required'              => 'Ingresa el monto del pago.',
                'numeric'               => 'El monto debe ser un número válido.',
                'greater_than'          => 'El monto debe ser mayor a 0.',
                'less_than_equal_to'    => 'El monto ingresado es demasiado alto.',
            ],
            'metodo' => [
                'required' => 'Selecciona el método de pago.',
                'in_list'  => 'Selecciona un método de pago válido.',
            ],
            'referencia' => [
                'max_length' => 'La referencia no debe superar los 100 caracteres.',
            ],
            'comprobante_ruta' => [
                'max_length' => 'La ruta del comprobante no debe superar los 255 caracteres.',
            ],
            'periodo_desde' => [
                'required'   => 'Ingresa el inicio del periodo.',
                'valid_date' => 'Ingresa una fecha de inicio válida.',
            ],
            'periodo_hasta' => [
                'required'   => 'Ingresa el fin del periodo.',
                'valid_date' => 'Ingresa una fecha de fin válida.',
            ],
            'fecha_pago' => [
                'required'   => 'Ingresa la fecha del pago.',
                'valid_date' => 'Ingresa una fecha de pago válida.',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput();
        }

        if ($data['periodo_hasta'] < $data['periodo_desde']) {
            return redirect()->back()->withInput()->with(
                'error',
                'El fin del periodo debe ser mayor o igual al inicio del periodo.'
            );
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $idPago = (int) $this->pagoModel->insert([
                'suscripcion_id'   => (int) $suscripcion['id_suscripcion'],
                'monto'            => number_format((float) $data['monto'], 2, '.', ''),
                'metodo'           => $data['metodo'],
                'referencia'       => $data['referencia'] !== '' ? $data['referencia'] : null,
                'comprobante_ruta' => $data['comprobante_ruta'] !== '' ? $data['comprobante_ruta'] : null,
                'periodo_desde'    => $data['periodo_desde'],
                'periodo_hasta'    => $data['periodo_hasta'],
                'fecha_pago'       => $data['fecha_pago'],
                'estado'           => 'pendiente',
                'created_by'       => $idAdmin,
                'modified_by'      => $idAdmin,
            ], true);

            if ($idPago <= 0) {
                throw new RuntimeException('No se pudo registrar el pago.');
            }

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => (int) $suscripcion['negocio_id'],
                'accion'         => 'registrar_pago',
                'tabla_afectada' => 'pagos',
                'registro_id'    => $idPago,
                'descripcion'    => mb_substr(
                    'Pago registrado para la suscripción #' . (int) $suscripcion['id_suscripcion']
                    . ' por S/ ' . number_format((float) $data['monto'], 2, '.', '') . '.',
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad del pago.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al registrar pago: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos registrar el pago. Revisa los datos e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('admin/pagos'))->with(
            'success',
            'Pago registrado correctamente. Ahora puedes confirmarlo.'
        );
    }

    public function confirmar($idPago = null)
    {
        $idPago  = (int) $idPago;
        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            $pago = $db->query(
                'SELECT * FROM pagos WHERE id_pago = ? AND deleted_at IS NULL FOR UPDATE',
                [$idPago]
            )->getRowArray();

            if ($pago === null) {
                throw new RuntimeException('El pago no existe.');
            }

            if ($pago['estado'] !== 'pendiente') {
                throw new RuntimeException('El pago ya fue procesado.');
            }

            $suscripcion = $db->query(
                'SELECT * FROM suscripciones WHERE id_suscripcion = ? AND deleted_at IS NULL FOR UPDATE',
                [(int) $pago['suscripcion_id']]
            )->getRowArray();

            if ($suscripcion === null) {
                throw new RuntimeException('La suscripción no existe.');
            }

            if ($suscripcion['estado'] !== 'pendiente_pago') {
                throw new RuntimeException('La suscripción ya no está pendiente de pago.');
            }

            $anterior = $db->query(
                'SELECT id_suscripcion FROM suscripciones '
                . "WHERE negocio_id = ? AND id_suscripcion <> ? AND estado IN ('prueba', 'activa') "
                . 'AND deleted_at IS NULL FOR UPDATE',
                [(int) $suscripcion['negocio_id'], (int) $suscripcion['id_suscripcion']]
            )->getRowArray();

            if ($anterior !== null) {
                $db->table('suscripciones')
                    ->where('id_suscripcion', (int) $anterior['id_suscripcion'])
                    ->update([
                        'estado'      => 'cancelada',
                        'fecha_fin'   => date('Y-m-d'),
                        'modified_by' => $idAdmin,
                    ]);
            }

            $db->table('suscripciones')
                ->where('id_suscripcion', (int) $suscripcion['id_suscripcion'])
                ->update([
                    'estado'       => 'activa',
                    'fecha_inicio' => $pago['periodo_desde'],
                    'fecha_fin'    => $pago['periodo_hasta'],
                    'modified_by'  => $idAdmin,
                ]);

            $db->table('pagos')
                ->where('id_pago', $idPago)
                ->update([
                    'estado'         => 'confirmado',
                    'confirmado_por' => $idAdmin,
                    'confirmado_at'  => date('Y-m-d H:i:s'),
                    'modified_by'    => $idAdmin,
                ]);

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => (int) $suscripcion['negocio_id'],
                'accion'         => 'confirmar_pago',
                'tabla_afectada' => 'pagos',
                'registro_id'    => $idPago,
                'descripcion'    => mb_substr(
                    'Pago #' . $idPago . ' confirmado; suscripción #'
                    . (int) $suscripcion['id_suscripcion'] . ' activada.',
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad de confirmación.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al confirmar pago: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/pagos'))->with(
                'error',
                'No pudimos confirmar el pago. Actualiza la página e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('admin/pagos'))->with(
            'success',
            'Pago confirmado y suscripción activada correctamente.'
        );
    }

    public function rechazar($idPago = null)
    {
        $idPago  = (int) $idPago;
        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            $pago = $db->query(
                'SELECT * FROM pagos WHERE id_pago = ? AND deleted_at IS NULL FOR UPDATE',
                [$idPago]
            )->getRowArray();

            if ($pago === null) {
                throw new RuntimeException('El pago no existe.');
            }

            if ($pago['estado'] !== 'pendiente') {
                throw new RuntimeException('El pago ya fue procesado.');
            }

            $suscripcion = $db->query(
                'SELECT negocio_id FROM suscripciones WHERE id_suscripcion = ?',
                [(int) $pago['suscripcion_id']]
            )->getRowArray();

            $db->table('pagos')
                ->where('id_pago', $idPago)
                ->update([
                    'estado'      => 'rechazado',
                    'modified_by' => $idAdmin,
                ]);

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => $suscripcion !== null ? (int) $suscripcion['negocio_id'] : null,
                'accion'         => 'rechazar_pago',
                'tabla_afectada' => 'pagos',
                'registro_id'    => $idPago,
                'descripcion'    => mb_substr('Pago #' . $idPago . ' rechazado.', 0, 500),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad de rechazo.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al rechazar pago: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/pagos'))->with(
                'error',
                'No pudimos rechazar el pago. Actualiza la página e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('admin/pagos'))->with(
            'success',
            'Pago rechazado correctamente.'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function suscripcionPendiente(int $idSuscripcion): ?array
    {
        if ($idSuscripcion <= 0) {
            return null;
        }

        return $this->suscripcionModel
            ->where('id_suscripcion', $idSuscripcion)
            ->where('estado', 'pendiente_pago')
            ->first();
    }
}
