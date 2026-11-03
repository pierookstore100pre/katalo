<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
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

    public function __construct()
    {
        $this->usuarioModel        = model(UsuarioModel::class);
        $this->negocioModel        = model(NegocioModel::class);
        $this->negocioUsuarioModel = model(NegocioUsuarioModel::class);
        $this->logModel            = model(LogActividadModel::class);
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

        $usuario = $this->usuarioModel
            ->where('email', $data['email'])
            ->where('activo', 1)
            ->first();

        if ($usuario === null || ! password_verify($data['password'], $usuario['password_hash'])) {
            return redirect()->back()->withInput()->with(
                'error',
                'Correo o contraseña incorrectos.'
            );
        }

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

        $this->usuarioModel->update((int) $usuario['id_usuario'], [
            'intentos_fallidos' => 0,
            'ultimo_login'      => date('Y-m-d H:i:s'),
        ]);

        try {
            $this->logModel->insert([
                'usuario_id' => (int) $usuario['id_usuario'],
                'negocio_id' => (int) $negocio['id_negocio'],
                'accion'     => 'login',
                'tabla_afectada' => 'usuarios',
                'registro_id' => (int) $usuario['id_usuario'],
                'descripcion' => 'Inicio de sesión.',
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'No se pudo registrar el log de login: {message}', ['message' => $e->getMessage()]);
        }

        $session = session();
        $session->regenerate();
        $session->set([
            'id_usuario'       => (int) $usuario['id_usuario'],
            'id_negocio'       => (int) $negocio['id_negocio'],
            'rol_negocio'      => $membresia['rol_negocio'],
            'nombre_comercial' => $negocio['nombre_comercial'],
            'email'            => $usuario['email'],
        ]);

        return redirect()->to(base_url('dashboard'));
    }
}
