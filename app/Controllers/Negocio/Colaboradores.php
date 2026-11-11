<?php

namespace App\Controllers\Negocio;

use App\Controllers\BaseController;
use App\Models\LogActividadModel;
use App\Models\NegocioUsuarioModel;
use App\Models\PersonaModel;
use App\Models\PlanModel;
use App\Models\RolModel;
use App\Models\UsuarioModel;
use App\Models\UsuarioRolModel;
use RuntimeException;
use Throwable;

class Colaboradores extends BaseController
{
    protected NegocioUsuarioModel $negocioUsuarioModel;
    protected UsuarioModel $usuarioModel;
    protected PersonaModel $personaModel;
    protected UsuarioRolModel $usuarioRolModel;
    protected RolModel $rolModel;
    protected PlanModel $planModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->negocioUsuarioModel = model(NegocioUsuarioModel::class);
        $this->usuarioModel        = model(UsuarioModel::class);
        $this->personaModel        = model(PersonaModel::class);
        $this->usuarioRolModel     = model(UsuarioRolModel::class);
        $this->rolModel            = model(RolModel::class);
        $this->planModel           = model(PlanModel::class);
        $this->logModel            = model(LogActividadModel::class);
    }

    public function index()
    {
        $ctx = $this->verificarAcceso();

        if ($ctx === null) {
            return $this->respuestaSinAcceso();
        }

        $colaboradores = $this->negocioUsuarioModel->listarColaboradores($ctx['idNegocio']);
        $totalActivos  = 0;

        foreach ($colaboradores as $colaborador) {
            if ((int) $colaborador['activo'] === 1) {
                $totalActivos++;
            }
        }

        return view('negocio/colaboradores/index', [
            'titulo'        => 'Colaboradores',
            'colaboradores' => $colaboradores,
            'plan'          => $ctx['plan'],
            'totalActivos'  => $totalActivos,
            'maxUsuarios'   => $ctx['plan']['max_usuarios'] !== null
                ? (int) $ctx['plan']['max_usuarios']
                : null,
        ]);
    }

    public function crear()
    {
        $ctx = $this->verificarAcceso();

        if ($ctx === null) {
            return $this->respuestaSinAcceso();
        }

        $totalActivos = $this->negocioUsuarioModel->contarActivosPorNegocio($ctx['idNegocio']);
        $maxUsuarios  = $ctx['plan']['max_usuarios'] !== null
            ? (int) $ctx['plan']['max_usuarios']
            : null;

        if ($maxUsuarios !== null && $totalActivos >= $maxUsuarios) {
            return redirect()->to(base_url('negocio/colaboradores'))->with(
                'error',
                'Alcanzaste el límite de ' . $maxUsuarios . ' usuarios de tu plan. Mejora tu plan para agregar más colaboradores.'
            );
        }

        return view('negocio/colaboradores/form', [
            'titulo' => 'Nuevo colaborador',
            'accion' => base_url('negocio/colaboradores/crear'),
        ]);
    }

    public function guardar()
    {
        $ctx = $this->verificarAcceso();

        if ($ctx === null) {
            return $this->respuestaSinAcceso();
        }

        $idNegocio      = $ctx['idNegocio'];
        $idUsuarioOwner = (int) session()->get('id_usuario');

        $data = [
            'nombres'   => trim((string) $this->request->getPost('nombres')),
            'apellidos' => trim((string) $this->request->getPost('apellidos')),
            'telefono'  => trim((string) $this->request->getPost('telefono')),
            'email'     => strtolower(trim((string) $this->request->getPost('email'))),
            'password'  => (string) $this->request->getPost('password'),
        ];

        $rules = [
            'nombres'   => 'required|max_length[100]',
            'apellidos' => 'required|max_length[100]',
            'telefono'  => 'required|regex_match[/^[0-9]{7,15}$/]',
            'email'     => 'required|valid_email|max_length[100]',
            'password'  => 'required|min_length[8]|regex_match[/[0-9]/]',
        ];

        $messages = [
            'nombres' => [
                'required'   => 'Ingresa los nombres del colaborador.',
                'max_length' => 'Los nombres no deben superar los 100 caracteres.',
            ],
            'apellidos' => [
                'required'   => 'Ingresa los apellidos del colaborador.',
                'max_length' => 'Los apellidos no deben superar los 100 caracteres.',
            ],
            'telefono' => [
                'required'    => 'Ingresa el teléfono del colaborador.',
                'regex_match' => 'El teléfono debe contener solo números, entre 7 y 15 dígitos.',
            ],
            'email' => [
                'required'    => 'Ingresa el correo electrónico del colaborador.',
                'valid_email' => 'Ingresa un correo electrónico válido.',
                'max_length'  => 'El correo electrónico no debe superar los 100 caracteres.',
            ],
            'password' => [
                'required'    => 'Ingresa una contraseña temporal.',
                'min_length'  => 'La contraseña debe tener al menos 8 caracteres.',
                'regex_match' => 'La contraseña debe incluir al menos un número.',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput();
        }

        $existeEmail = $this->usuarioModel
            ->withDeleted()
            ->where('email', $data['email'])
            ->countAllResults() > 0;

        if ($existeEmail) {
            $this->validator->setError('email', 'Este correo electrónico ya está registrado.');

            return redirect()->back()->withInput();
        }

        $totalActivos = $this->negocioUsuarioModel->contarActivosPorNegocio($idNegocio);
        $maxUsuarios  = $ctx['plan']['max_usuarios'] !== null
            ? (int) $ctx['plan']['max_usuarios']
            : null;

        if ($maxUsuarios !== null && $totalActivos >= $maxUsuarios) {
            return redirect()->back()->withInput()->with(
                'error',
                'Alcanzaste el límite de ' . $maxUsuarios . ' usuarios de tu plan. Mejora tu plan para agregar más colaboradores.'
            );
        }

        $rol = $this->rolModel
            ->where('nombre', 'usuario_negocio')
            ->where('activo', 1)
            ->first();

        if ($rol === null) {
            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos asignar el rol de colaborador. Contacta al administrador.'
            );
        }

        $db = db_connect();
        $db->transStart();

        try {
            $idPersona = (int) $this->personaModel->insert([
                'nombres'     => $data['nombres'],
                'apellidos'   => $data['apellidos'],
                'telefono'    => $data['telefono'],
                'direccion'   => null,
                'distrito_id' => null,
                'created_by'  => $idUsuarioOwner,
                'modified_by' => $idUsuarioOwner,
            ], true);

            if ($idPersona <= 0) {
                throw new RuntimeException('No se pudo registrar la persona.');
            }

            $idUsuario = (int) $this->usuarioModel->insert([
                'persona_id'    => $idPersona,
                'email'         => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
                'activo'        => 1,
                'created_by'    => $idUsuarioOwner,
                'modified_by'   => $idUsuarioOwner,
            ], true);

            if ($idUsuario <= 0) {
                throw new RuntimeException('No se pudo registrar el usuario.');
            }

            $this->usuarioRolModel->insert([
                'usuario_id'  => $idUsuario,
                'rol_id'      => (int) $rol['id_rol'],
                'created_by'  => $idUsuarioOwner,
                'modified_by' => $idUsuarioOwner,
            ]);

            $idNegocioUsuario = (int) $this->negocioUsuarioModel->insert([
                'negocio_id'  => $idNegocio,
                'usuario_id'  => $idUsuario,
                'rol_negocio' => 'colaborador',
                'activo'      => 1,
                'created_by'  => $idUsuarioOwner,
                'modified_by' => $idUsuarioOwner,
            ], true);

            if ($idNegocioUsuario <= 0) {
                throw new RuntimeException('No se pudo vincular el colaborador al negocio.');
            }

            $this->registrarLog(
                'crear_colaborador',
                $idUsuarioOwner,
                $idNegocio,
                $idNegocioUsuario,
                'Creación del colaborador ' . $data['nombres'] . ' ' . $data['apellidos'] . '.'
            );

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción del colaborador no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al crear colaborador: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos crear el colaborador. Revisa los datos e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/colaboradores'))->with(
            'success',
            'Colaborador creado correctamente. Comparte con él su correo y contraseña temporal.'
        );
    }

    public function desactivar($id = null)
    {
        $ctx = $this->verificarAcceso();

        if ($ctx === null) {
            return $this->respuestaSinAcceso();
        }

        $idNegocio = $ctx['idNegocio'];
        $idUsuario = (int) session()->get('id_usuario');
        $colaborador = $this->negocioUsuarioModel->findColaboradorDelNegocio((int) $id, $idNegocio);

        if ($colaborador === null) {
            return redirect()->to(base_url('negocio/colaboradores'))->with(
                'error',
                'El colaborador no existe o no pertenece a tu negocio.'
            );
        }

        $db = db_connect();
        $db->transStart();

        try {
            $actualizado = $this->negocioUsuarioModel->update((int) $colaborador['id_negocio_usuario'], [
                'activo'      => 0,
                'modified_by' => $idUsuario,
            ]);

            if (! $actualizado) {
                throw new RuntimeException('No se pudo desactivar el colaborador.');
            }

            $this->registrarLog(
                'desactivar_colaborador',
                $idUsuario,
                $idNegocio,
                (int) $colaborador['id_negocio_usuario'],
                'Desactivación del colaborador con usuario ID ' . (int) $colaborador['usuario_id'] . '.'
            );

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción de desactivación no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al desactivar colaborador: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('negocio/colaboradores'))->with(
                'error',
                'No pudimos desactivar el colaborador. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/colaboradores'))->with(
            'success',
            'Colaborador desactivado correctamente.'
        );
    }

    /**
     * @return array{idNegocio: int, plan: array<string, mixed>}|null
     */
    private function verificarAcceso(): ?array
    {
        $idNegocio = (int) session()->get('id_negocio');

        if ($idNegocio <= 0 || session()->get('rol_negocio') !== 'propietario') {
            return null;
        }

        $plan = $this->planModel->obtenerVigentePorNegocio($idNegocio);

        if ($plan === null) {
            return null;
        }

        $esPro = ($plan['slug'] ?? '') === 'pro'
            || ($plan['max_usuarios'] !== null && (int) $plan['max_usuarios'] > 1);

        if (! $esPro) {
            return null;
        }

        return ['idNegocio' => $idNegocio, 'plan' => $plan];
    }

    private function respuestaSinAcceso()
    {
        if ((int) session()->get('id_negocio') <= 0) {
            return $this->cerrarSesionSinNegocio();
        }

        return redirect()->to(base_url('dashboard'))->with(
            'error',
            'La gestión de colaboradores es exclusiva del propietario y del plan Pro.'
        );
    }

    private function cerrarSesionSinNegocio()
    {
        session()->destroy();

        return redirect()->to(base_url('auth/login'));
    }

    private function registrarLog(
        string $accion,
        int $idUsuario,
        int $idNegocio,
        int $registroId,
        string $descripcion
    ): void {
        $this->logModel->insert([
            'usuario_id'     => $idUsuario,
            'negocio_id'     => $idNegocio,
            'accion'         => $accion,
            'tabla_afectada' => 'negocio_usuarios',
            'registro_id'    => $registroId,
            'descripcion'    => mb_substr($descripcion, 0, 500),
            'ip_address'     => $this->request->getIPAddress(),
            'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
        ]);
    }
}
