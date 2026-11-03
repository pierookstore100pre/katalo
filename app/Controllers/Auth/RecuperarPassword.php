<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\LogActividadModel;
use App\Models\NegocioUsuarioModel;
use App\Models\PasswordResetModel;
use App\Models\UsuarioModel;
use Throwable;

class RecuperarPassword extends BaseController
{
    protected UsuarioModel $usuarioModel;
    protected PasswordResetModel $passwordResetModel;
    protected NegocioUsuarioModel $negocioUsuarioModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->usuarioModel        = model(UsuarioModel::class);
        $this->passwordResetModel  = model(PasswordResetModel::class);
        $this->negocioUsuarioModel = model(NegocioUsuarioModel::class);
        $this->logModel            = model(LogActividadModel::class);
    }

    public function index()
    {
        return view('auth/recuperar');
    }

    public function solicitar()
    {
        $data = [
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
        ];

        $rules = [
            'email' => 'required|valid_email|max_length[100]',
        ];

        $messages = [
            'email' => [
                'required'    => 'Ingresa tu correo electrónico.',
                'valid_email' => 'Ingresa un correo electrónico válido.',
                'max_length'  => 'El correo electrónico no debe superar los 100 caracteres.',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput();
        }

        $redirect = redirect()->to(base_url('auth/recuperar'))->with(
            'success',
            'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.'
        );

        $usuario = $this->usuarioModel
            ->where('email', $data['email'])
            ->where('activo', 1)
            ->first();

        if ($usuario === null) {
            return $redirect;
        }

        try {
            $token  = bin2hex(random_bytes(32));
            $enlace = base_url('auth/reset?token=' . $token);

            $this->passwordResetModel->insert([
                'usuario_id'   => (int) $usuario['id_usuario'],
                'token_hash'   => hash('sha256', $token),
                'expires_at'   => date('Y-m-d H:i:s', time() + (24 * 60 * 60)),
                'used_at'      => null,
                'ip_solicitud' => $this->request->getIPAddress(),
            ]);

            log_message('info', 'Enlace de recuperación para {email}: {enlace}', [
                'email'  => $usuario['email'],
                'enlace' => $enlace,
            ]);

            if (ENVIRONMENT === 'development') {
                $redirect = $redirect->with('reset_link', $enlace);
            }
        } catch (Throwable $e) {
            log_message('error', 'No se pudo generar el enlace de recuperación: {message}', ['message' => $e->getMessage()]);
        }

        return $redirect;
    }

    public function reset()
    {
        $token = (string) $this->request->getGet('token');
        $reset = $this->buscarResetValido($token);

        return view('auth/reset', [
            'token' => $token,
            'valido' => $reset !== null,
        ]);
    }

    public function actualizar()
    {
        $token = (string) $this->request->getPost('token');

        $data = [
            'password'         => (string) $this->request->getPost('password'),
            'password_confirm' => (string) $this->request->getPost('password_confirm'),
        ];

        $rules = [
            'password'         => 'required|min_length[8]|regex_match[/[0-9]/]',
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'password' => [
                'required'    => 'Ingresa tu nueva contraseña.',
                'min_length'  => 'La contraseña debe tener al menos 8 caracteres.',
                'regex_match' => 'La contraseña debe incluir al menos un número.',
            ],
            'password_confirm' => [
                'required' => 'Confirma tu nueva contraseña.',
                'matches'  => 'Las contraseñas no coinciden.',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput();
        }

        $reset = $this->buscarResetValido($token);

        if ($reset === null) {
            return redirect()->to(base_url('auth/login'))->with(
                'error',
                'El enlace es inválido, ya fue usado o ha vencido. Solicita uno nuevo.'
            );
        }

        $db = db_connect();
        $db->transStart();

        try {
            $this->usuarioModel->update((int) $reset['usuario_id'], [
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            ]);

            $this->passwordResetModel->update((int) $reset['id_reset'], [
                'used_at' => date('Y-m-d H:i:s'),
            ]);

            $membresia = $this->negocioUsuarioModel
                ->where('usuario_id', (int) $reset['usuario_id'])
                ->where('activo', 1)
                ->first();

            $this->logModel->insert([
                'usuario_id'  => (int) $reset['usuario_id'],
                'negocio_id'  => $membresia !== null ? (int) $membresia['negocio_id'] : null,
                'accion'      => 'reset_password',
                'tabla_afectada' => 'usuarios',
                'registro_id' => (int) $reset['usuario_id'],
                'descripcion' => 'Restablecimiento de contraseña.',
                'ip_address'  => $this->request->getIPAddress(),
                'user_agent'  => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('La transacción de restablecimiento no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al restablecer contraseña: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos actualizar tu contraseña. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('auth/login'))->with(
            'success',
            'Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión.'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buscarResetValido(string $token): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        return $this->passwordResetModel
            ->where('token_hash', hash('sha256', $token))
            ->where('used_at IS NULL', null, false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }
}
