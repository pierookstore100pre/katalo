<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ConfiguracionModel;
use App\Models\LogActividadModel;
use RuntimeException;
use Throwable;

class Configuracion extends BaseController
{
    private const CAMPOS = [
        'sesion_minutos' => [
            'label' => 'Duración de la sesión (minutos)',
            'min'   => 1,
            'max'   => 1440,
        ],
        'dias_prueba' => [
            'label' => 'Días de prueba',
            'min'   => 0,
            'max'   => 90,
        ],
        'reset_password_horas' => [
            'label' => 'Vigencia del enlace de recuperación (horas)',
            'min'   => 1,
            'max'   => 72,
        ],
        'pedidos_max_por_ip_hora' => [
            'label' => 'Pedidos por IP por hora',
            'min'   => 0,
            'max'   => 1000,
        ],
        'login_intentos_max' => [
            'label' => 'Intentos de login antes del bloqueo',
            'min'   => 1,
            'max'   => 20,
        ],
        'login_bloqueo_minutos' => [
            'label' => 'Duración del bloqueo de login (minutos)',
            'min'   => 1,
            'max'   => 1440,
        ],
    ];

    protected ConfiguracionModel $configuracionModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->configuracionModel = model(ConfiguracionModel::class);
        $this->logModel           = model(LogActividadModel::class);
    }

    public function index()
    {
        return view('admin/configuracion/index', [
            'titulo' => 'Configuración',
            'campos' => $this->obtenerCampos(),
        ]);
    }

    public function guardar()
    {
        $datos  = [];
        $rules  = [];
        $errors = [];

        foreach (self::CAMPOS as $clave => $campo) {
            $datos[$clave] = trim((string) $this->request->getPost($clave));

            $rules[$clave] = 'required|integer|greater_than_equal_to[' . $campo['min'] . ']|less_than_equal_to[' . $campo['max'] . ']';

            $errors[$clave] = [
                'required'               => 'Ingresa un valor para ' . $campo['label'] . '.',
                'integer'                => 'El valor de ' . $campo['label'] . ' debe ser un número entero.',
                'greater_than_equal_to'  => 'El valor de ' . $campo['label'] . ' no puede ser menor a ' . $campo['min'] . '.',
                'less_than_equal_to'     => 'El valor de ' . $campo['label'] . ' no puede ser mayor a ' . $campo['max'] . '.',
            ];
        }

        if (! $this->validateData($datos, $rules, $errors)) {
            return redirect()->back()->withInput();
        }

        $idAdmin = (int) session()->get('id_usuario');
        $antes   = $this->obtenerValores();
        $despues = [];

        foreach (self::CAMPOS as $clave => $campo) {
            $despues[$clave] = (int) $datos[$clave];
        }

        $db = db_connect();
        $db->transBegin();

        try {
            foreach ($despues as $clave => $valor) {
                if (! $this->configuracionModel->actualizarPorClave($clave, (string) $valor, $idAdmin)) {
                    throw new RuntimeException('No se pudo actualizar la configuración "' . $clave . '".');
                }
            }

            $datosLog = json_encode([
                'antes'   => $antes,
                'despues' => $despues,
            ], JSON_UNESCAPED_UNICODE);

            if ($datosLog === false) {
                throw new RuntimeException('No se pudo serializar el detalle de la configuración.');
            }

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => null,
                'accion'         => 'editar_configuracion',
                'tabla_afectada' => 'configuracion',
                'registro_id'    => null,
                'descripcion'    => mb_substr('Configuración global actualizada.', 0, 500),
                'datos'          => $datosLog,
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad de configuración.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al actualizar configuración: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos actualizar la configuración. Revisa los datos e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('admin/configuracion'))->with(
            'success',
            'Configuración actualizada correctamente.'
        );
    }

    /**
     * @return list<array{clave: string, label: string, valor: string, descripcion: string, min: int, max: int}>
     */
    private function obtenerCampos(): array
    {
        $mapa = [];

        foreach ($this->configuracionModel->whereIn('clave', array_keys(self::CAMPOS))->findAll() as $fila) {
            $mapa[$fila['clave']] = $fila;
        }

        $campos = [];

        foreach (self::CAMPOS as $clave => $campo) {
            $fila = $mapa[$clave] ?? [];

            $campos[] = [
                'clave'       => $clave,
                'label'       => $campo['label'],
                'valor'       => old($clave, (string) ($fila['valor'] ?? '')),
                'descripcion' => (string) ($fila['descripcion'] ?? 'Sin descripción.'),
                'min'         => $campo['min'],
                'max'         => $campo['max'],
            ];
        }

        return $campos;
    }

    /**
     * @return array<string, int>
     */
    private function obtenerValores(): array
    {
        $valores = [];

        foreach ($this->configuracionModel->whereIn('clave', array_keys(self::CAMPOS))->findAll() as $fila) {
            $valores[$fila['clave']] = (int) $fila['valor'];
        }

        return $valores;
    }
}
