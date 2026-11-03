<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\DistritoModel;
use App\Models\LogActividadModel;
use App\Models\NegocioModel;
use App\Models\NegocioUsuarioModel;
use App\Models\PersonaModel;
use App\Models\PlanModel;
use App\Models\RolModel;
use App\Models\SuscripcionModel;
use App\Models\UsuarioModel;
use App\Models\UsuarioRolModel;
use RuntimeException;
use Throwable;

class Registro extends BaseController
{
    protected DistritoModel $distritoModel;
    protected PersonaModel $personaModel;
    protected UsuarioModel $usuarioModel;
    protected UsuarioRolModel $usuarioRolModel;
    protected NegocioModel $negocioModel;
    protected NegocioUsuarioModel $negocioUsuarioModel;
    protected SuscripcionModel $suscripcionModel;
    protected PlanModel $planModel;
    protected RolModel $rolModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->distritoModel        = model(DistritoModel::class);
        $this->personaModel         = model(PersonaModel::class);
        $this->usuarioModel         = model(UsuarioModel::class);
        $this->usuarioRolModel      = model(UsuarioRolModel::class);
        $this->negocioModel         = model(NegocioModel::class);
        $this->negocioUsuarioModel  = model(NegocioUsuarioModel::class);
        $this->suscripcionModel     = model(SuscripcionModel::class);
        $this->planModel            = model(PlanModel::class);
        $this->rolModel             = model(RolModel::class);
        $this->logModel             = model(LogActividadModel::class);
    }

    public function index()
    {
        if (session()->get('id_usuario')) {
            return redirect()->to(base_url('dashboard'));
        }

        return view('auth/registro', [
            'distritos' => $this->distritoModel->getActivosConCiudadActiva(),
        ]);
    }

    public function guardar()
    {
        $distritos = $this->distritoModel->getActivosConCiudadActiva();
        $idsValidos = array_map(static fn (array $distrito): int => (int) $distrito['id_distrito'], $distritos);

        $data = [
            'nombres'          => trim((string) $this->request->getPost('nombres')),
            'apellidos'        => trim((string) $this->request->getPost('apellidos')),
            'telefono'         => trim((string) $this->request->getPost('telefono')),
            'email'            => strtolower(trim((string) $this->request->getPost('email'))),
            'password'         => (string) $this->request->getPost('password'),
            'nombre_comercial' => trim((string) $this->request->getPost('nombre_comercial')),
            'distrito_id'      => trim((string) $this->request->getPost('distrito_id')),
            'direccion'        => trim((string) $this->request->getPost('direccion')),
        ];

        $rules = [
            'nombres'          => 'required|max_length[100]',
            'apellidos'        => 'required|max_length[100]',
            'telefono'         => 'required|regex_match[/^[0-9]{7,15}$/]',
            'email'            => 'required|valid_email|max_length[100]|is_unique[usuarios.email]',
            'password'         => 'required|min_length[8]|regex_match[/[0-9]/]',
            'nombre_comercial' => 'required|max_length[150]',
            'direccion'        => 'required|max_length[255]',
        ];

        if ($idsValidos !== []) {
            $rules['distrito_id'] = 'required|is_natural_no_zero|in_list[' . implode(',', $idsValidos) . ']';
        } else {
            $rules['distrito_id'] = 'required|is_natural_no_zero';
        }

        $messages = [
            'nombres' => [
                'required'   => 'Ingresa tus nombres.',
                'max_length' => 'Los nombres no deben superar los 100 caracteres.',
            ],
            'apellidos' => [
                'required'   => 'Ingresa tus apellidos.',
                'max_length' => 'Los apellidos no deben superar los 100 caracteres.',
            ],
            'telefono' => [
                'required'    => 'Ingresa tu teléfono o WhatsApp.',
                'regex_match' => 'El teléfono debe contener solo números, entre 7 y 15 dígitos.',
            ],
            'email' => [
                'required'    => 'Ingresa tu correo electrónico.',
                'valid_email' => 'Ingresa un correo electrónico válido.',
                'max_length'  => 'El correo electrónico no debe superar los 100 caracteres.',
                'is_unique'   => 'Este correo electrónico ya está registrado.',
            ],
            'password' => [
                'required'    => 'Crea una contraseña.',
                'min_length'  => 'La contraseña debe tener al menos 8 caracteres.',
                'regex_match' => 'La contraseña debe incluir al menos un número.',
            ],
            'nombre_comercial' => [
                'required'   => 'Ingresa el nombre comercial de tu negocio.',
                'max_length' => 'El nombre comercial no debe superar los 150 caracteres.',
            ],
            'distrito_id' => [
                'required'            => 'Selecciona tu distrito.',
                'is_natural_no_zero'  => 'Selecciona un distrito válido.',
                'in_list'             => 'Selecciona un distrito válido y activo.',
            ],
            'direccion' => [
                'required'   => 'Ingresa la dirección de tu negocio.',
                'max_length' => 'La dirección no debe superar los 255 caracteres.',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput();
        }

        $db = db_connect();
        $db->transStart();

        try {
            $idPersona = (int) $this->personaModel->insert([
                'nombres'     => $data['nombres'],
                'apellidos'   => $data['apellidos'],
                'telefono'    => $data['telefono'],
                'direccion'   => $data['direccion'],
                'distrito_id' => (int) $data['distrito_id'],
            ], true);

            if ($idPersona <= 0) {
                throw new RuntimeException('No se pudo registrar la persona.');
            }

            $idUsuario = (int) $this->usuarioModel->insert([
                'persona_id'    => $idPersona,
                'email'         => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
                'activo'        => 1,
                'created_by'    => $idPersona,
                'modified_by'   => $idPersona,
            ], true);

            if ($idUsuario <= 0) {
                throw new RuntimeException('No se pudo registrar el usuario.');
            }

            $rol = $this->rolModel
                ->where('nombre', 'usuario_negocio')
                ->where('activo', 1)
                ->first();

            if ($rol === null) {
                throw new RuntimeException('No se encontró el rol de usuario de negocio.');
            }

            $this->usuarioRolModel->insert([
                'usuario_id' => $idUsuario,
                'rol_id'     => (int) $rol['id_rol'],
                'created_by' => $idUsuario,
                'modified_by' => $idUsuario,
            ]);

            $slug = $this->generarSlugUnico($data['nombre_comercial']);

            $idNegocio = (int) $this->negocioModel->insert([
                'nombre_comercial'           => $data['nombre_comercial'],
                'slug'                       => $slug,
                'direccion'                  => $data['direccion'],
                'distrito_id'                => (int) $data['distrito_id'],
                'telefono_principal'         => $data['telefono'],
                'siguiente_numero_pedido'    => 1,
                'activo'                     => 1,
                'created_by'                 => $idUsuario,
                'modified_by'                => $idUsuario,
            ], true);

            if ($idNegocio <= 0) {
                throw new RuntimeException('No se pudo registrar el negocio.');
            }

            $this->negocioUsuarioModel->insert([
                'negocio_id'  => $idNegocio,
                'usuario_id'  => $idUsuario,
                'rol_negocio' => 'propietario',
                'activo'      => 1,
                'created_by'  => $idUsuario,
                'modified_by' => $idUsuario,
            ]);

            $plan = $this->planModel
                ->where('slug', 'gratis')
                ->where('activo', 1)
                ->first();

            if ($plan === null) {
                throw new RuntimeException('No se encontró el plan Gratis.');
            }

            $this->suscripcionModel->insert([
                'negocio_id'     => $idNegocio,
                'plan_id'        => (int) $plan['id_plan'],
                'estado'         => 'activa',
                'fecha_inicio'   => date('Y-m-d'),
                'fecha_fin'      => null,
                'precio_pactado' => '0.00',
                'created_by'     => $idUsuario,
                'modified_by'    => $idUsuario,
            ]);

            $this->logModel->insert([
                'usuario_id'  => $idUsuario,
                'negocio_id'  => $idNegocio,
                'accion'      => 'registro',
                'tabla_afectada' => 'usuarios',
                'registro_id' => $idUsuario,
                'descripcion' => 'Registro de cuenta y negocio.',
                'ip_address'  => $this->request->getIPAddress(),
                'user_agent'  => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción de registro no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error en registro de negocio: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos completar tu registro. Revisa tus datos e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('auth/login'))->with(
            'success',
            'Tu cuenta fue creada correctamente. Ya puedes iniciar sesión.'
        );
    }

    private function generarSlugUnico(string $nombreComercial): string
    {
        $base  = $this->slugify($nombreComercial);
        $slug  = $base;
        $numero = 2;

        while ($this->negocioModel->withDeleted()->where('slug', $slug)->countAllResults() > 0) {
            $sufijo = '-' . $numero;
            $slug   = rtrim(mb_substr($base, 0, 120 - mb_strlen($sufijo)), '-') . $sufijo;
            $numero++;
        }

        return $slug;
    }

    private function slugify(string $texto): string
    {
        $texto = trim($texto);

        $texto = strtr($texto, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
            'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a',
            'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
            'Ñ' => 'n', 'Ç' => 'c',
        ]);

        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto) ?? '';
        $texto = trim($texto, '-');

        if ($texto === '') {
            $texto = 'negocio';
        }

        return rtrim(mb_substr($texto, 0, 120), '-') ?: 'negocio';
    }
}
