<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\ConfiguracionModel;
use App\Models\LogActividadModel;
use App\Models\NegocioModel;
use App\Models\NegocioUsuarioModel;
use App\Models\UsuarioModel;
use Throwable;

class Login extends BaseController
{
    protected UsuarioModel $usuarioModel;
    protected NegocioModel $negocioModel;
    protected NegocioUsuarioModel $negocioUsuarioModel;
    protected LogActividadModel $logModel;
    protected ConfiguracionModel $configuracionModel;

    public function __construct()
    {
        $this->usuarioModel        = model(UsuarioModel::class);
        $this->negocioModel        = model(NegocioModel::class);
        $this->negocioUsuarioModel = model(NegocioUsuarioModel::class);
        $this->logModel            = model(LogActividadModel::class);
        $this->configuracionModel  = model(ConfiguracionModel::class);
    }

    public function index()
    {
        if (session()->get('id_usuario')) {
            return redirect()->to(base_url('dashboard'));
        }

        return view('auth/login');
    }

    public function autenticar()
    {
        $data = [
            'email'    => strtolower(trim((string) $this->request->getPost('email'))),
            'password' => (string) $this->request->getPost('password'),
        ];

        $rules = [
            'email'    => 'required|valid_email|max_length[100]',
            'password' => 'required',
        ];

        $messages = [
            'email' => [
                'required'    => 'Ingresa tu correo electrónico.',
                'valid_email' => 'Ingresa un correo electrónico válido.',
                'max_length'  => 'El correo electrónico no debe superar los 100 caracteres.',
            ],
            'password' => [
                'required' => 'Ingresa tu contraseña.',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput();
        }

        $intentosMax    = max(1, (int) $this->configuracionModel->obtenerValor('login_intentos_max', 5));
        $bloqueoMinutos = max(1, (int) $this->configuracionModel->obtenerValor('login_bloqueo_minutos', 15));

        $usuario = $this->usuarioModel
            ->where('email', $data['email'])
            ->where('activo', 1)
            ->first();

        if ($usuario === null) {
            $this->registrarLoginFallido(null);

            return redirect()->back()->withInput()->with(
                'error',
                'Credenciales incorrectas.'
            );
        }

        $bloqueadoHasta = $usuario['bloqueado_hasta'] !== null
            ? strtotime((string) $usuario['bloqueado_hasta'])
            : null;

        if ($bloqueadoHasta !== null && $bloqueadoHasta > time()) {
            $minutosRestantes = max(1, (int) ceil(($bloqueadoHasta - time()) / 60));

            return redirect()->back()->withInput()->with(
                'error',
                'Cuenta bloqueada temporalmente. Intenta de nuevo en ' . $minutosRestantes . ' minutos.'
            );
        }

        if (! password_verify($data['password'], $usuario['password_hash'])) {
            $incrementos = ((int) $usuario['intentos_fallidos']) + 1;
            $actualizar  = ['intentos_fallidos' => $incrementos];

            if ($incrementos >= $intentosMax) {
                $actualizar['bloqueado_hasta'] = date('Y-m-d H:i:s', time() + ($bloqueoMinutos * 60));
            }

            $this->usuarioModel->update((int) $usuario['id_usuario'], $actualizar);
            $this->registrarLoginFallido((int) $usuario['id_usuario']);

            return redirect()->back()->withInput()->with(
                'error',
                'Credenciales incorrectas.'
            );
        }

        $this->usuarioModel->update((int) $usuario['id_usuario'], [
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
            'ultimo_login'      => date('Y-m-d H:i:s'),
        ]);

        $rolPlataforma = $this->obtenerRolPlataforma((int) $usuario['id_usuario']);

        $membresias = $this->negocioUsuarioModel
            ->where('usuario_id', (int) $usuario['id_usuario'])
            ->where('activo', 1)
            ->findAll();

        $membresia = null;
        foreach ($membresias as $item) {
            if ($item['rol_negocio'] === 'propietario') {
                $membresia = $item;
                break;
            }
        }
        $membresia ??= $membresias[0] ?? null;

        if ($membresia === null) {
            if ($rolPlataforma === 'administrador') {
                $this->registrarIngreso((int) $usuario['id_usuario'], null);

                $session = session();
                $session->regenerate();
                $session->set([
                    'id_usuario'       => (int) $usuario['id_usuario'],
                    'id_negocio'       => 0,
                    'rol_negocio'      => null,
                    'rol_plataforma'   => $rolPlataforma,
                    'nombre_comercial' => null,
                    'email'            => $usuario['email'],
                ]);

                return redirect()->to(base_url('admin/dashboard'));
            }

            return redirect()->back()->withInput()->with(
                'error',
                'Tu cuenta no tiene un negocio activo asociado. Contacta al administrador.'
            );
        }

        $negocio = $this->negocioModel
            ->where('id_negocio', (int) $membresia['negocio_id'])
            ->where('activo', 1)
            ->first();

        if ($negocio === null) {
            return redirect()->back()->withInput()->with(
                'error',
                'Tu negocio no se encuentra activo. Contacta al administrador.'
            );
        }

        $this->registrarIngreso((int) $usuario['id_usuario'], (int) $negocio['id_negocio']);

        $session = session();
        $session->regenerate();
        $session->set([
            'id_usuario'       => (int) $usuario['id_usuario'],
            'id_negocio'       => (int) $negocio['id_negocio'],
            'rol_negocio'      => $membresia['rol_negocio'],
            'rol_plataforma'   => $rolPlataforma,
            'nombre_comercial' => $negocio['nombre_comercial'],
            'email'            => $usuario['email'],
        ]);

        return redirect()->to(base_url('dashboard'));
    }

    private function obtenerRolPlataforma(int $idUsuario): string
    {
        $roles = db_connect()->table('usuario_roles ur')
            ->select('r.nombre')
            ->join('roles r', 'r.id_rol = ur.rol_id')
            ->where('ur.usuario_id', $idUsuario)
            ->where('r.activo', 1)
            ->where('r.deleted_at', null)
            ->get()
            ->getResultArray();

        $rolPlataforma = 'usuario_negocio';

        foreach ($roles as $rol) {
            if ($rol['nombre'] === 'administrador') {
                return 'administrador';
            }

            if ($rol['nombre'] === 'usuario_negocio') {
                $rolPlataforma = 'usuario_negocio';
            }
        }

        return $rolPlataforma;
    }

    private function registrarLoginFallido(?int $idUsuario): void
    {
        try {
            $this->logModel->insert([
                'usuario_id'     => $idUsuario,
                'negocio_id'     => null,
                'accion'         => 'login_fallido',
                'tabla_afectada' => 'usuarios',
                'registro_id'    => $idUsuario,
                'descripcion'    => 'Intento de inicio de sesión fallido.',
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'No se pudo registrar el log de login fallido: {message}', ['message' => $e->getMessage()]);
        }
    }

    private function registrarIngreso(int $idUsuario, ?int $idNegocio): void
    {
        try {
            $this->logModel->insert([
                'usuario_id'     => $idUsuario,
                'negocio_id'     => $idNegocio,
                'accion'         => 'login',
                'tabla_afectada' => 'usuarios',
                'registro_id'    => $idUsuario,
                'descripcion'    => 'Inicio de sesión.',
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'No se pudo registrar el log de login: {message}', ['message' => $e->getMessage()]);
        }
    }
}
