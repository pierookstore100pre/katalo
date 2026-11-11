<?php

namespace App\Controllers\Negocio;

use App\Controllers\BaseController;
use App\Models\DistritoModel;
use App\Models\LogActividadModel;
use App\Models\NegocioModel;
use App\Models\PlanModel;
use App\Services\ModerationService;
use RuntimeException;
use Throwable;

class Perfil extends BaseController
{
    private const LOGO_TAMANO_MAXIMO = 2 * 1024 * 1024;

    private const LOGO_MIMES_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    protected NegocioModel $negocioModel;
    protected DistritoModel $distritoModel;
    protected PlanModel $planModel;
    protected LogActividadModel $logModel;
    protected ModerationService $moderationService;

    public function __construct()
    {
        $this->negocioModel      = model(NegocioModel::class);
        $this->distritoModel     = model(DistritoModel::class);
        $this->planModel         = model(PlanModel::class);
        $this->logModel          = model(LogActividadModel::class);
        $this->moderationService = new ModerationService();
    }

    public function index()
    {
        $negocio = $this->negocioActual();

        if ($negocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $plan = $this->planModel->obtenerVigentePorNegocio((int) $negocio['id_negocio']);

        return view('negocio/perfil', [
            'titulo'           => 'Mi perfil',
            'negocio'          => $negocio,
            'distritos'        => $this->distritoModel->getActivosConCiudadActiva(),
            'ciudad_slug'      => $this->negocioModel->obtenerCiudadSlug((int) $negocio['id_negocio']),
            'plan'             => $plan,
            'tiene_analytics'  => $plan !== null && (int) $plan['tiene_analytics'] === 1,
        ]);
    }

    public function guardar()
    {
        $negocio = $this->negocioActual();

        if ($negocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $idNegocio = (int) $negocio['id_negocio'];
        $idUsuario = (int) session()->get('id_usuario');

        $plan            = $this->planModel->obtenerVigentePorNegocio($idNegocio);
        $tieneAnalytics  = $plan !== null && (int) $plan['tiene_analytics'] === 1;

        $distritos = $this->distritoModel->getActivosConCiudadActiva();
        $idsValidos = array_map(static fn (array $distrito): int => (int) $distrito['id_distrito'], $distritos);

        $data = [
            'nombre_comercial'     => trim((string) $this->request->getPost('nombre_comercial')),
            'slug'                 => strtolower(trim((string) $this->request->getPost('slug'))),
            'descripcion'          => trim((string) $this->request->getPost('descripcion')),
            'direccion'            => trim((string) $this->request->getPost('direccion')),
            'distrito_id'          => trim((string) $this->request->getPost('distrito_id')),
            'telefono_principal'   => trim((string) $this->request->getPost('telefono_principal')),
            'horario_texto'        => trim((string) $this->request->getPost('horario_texto')),
            'google_analytics_id'  => strtoupper(trim((string) $this->request->getPost('google_analytics_id'))),
        ];

        if (! $tieneAnalytics) {
            $data['google_analytics_id'] = '';
        }

        $rules = [
            'nombre_comercial'   => 'required|max_length[150]',
            'slug'               => 'required|max_length[120]|regex_match[/^[a-z0-9]+(-[a-z0-9]+)*$/]',
            'direccion'          => 'required|max_length[255]',
            'telefono_principal' => 'required|regex_match[/^[0-9]{7,15}$/]',
            'horario_texto'      => 'permit_empty|max_length[255]',
            'google_analytics_id' => 'permit_empty|max_length[20]|regex_match[/^(G-[A-Z0-9]{4,20}|UA-[0-9]{4,12}-[0-9]{1,4})$/]',
        ];

        if ($idsValidos !== []) {
            $rules['distrito_id'] = 'required|is_natural_no_zero|in_list[' . implode(',', $idsValidos) . ']';
        } else {
            $rules['distrito_id'] = 'required|is_natural_no_zero';
        }

        $messages = [
            'nombre_comercial' => [
                'required'   => 'Ingresa el nombre comercial de tu negocio.',
                'max_length' => 'El nombre comercial no debe superar los 150 caracteres.',
            ],
            'slug' => [
                'required'    => 'Ingresa el slug de tu negocio.',
                'max_length'  => 'El slug no debe superar los 120 caracteres.',
                'regex_match' => 'El slug solo puede tener minúsculas, números y guiones.',
            ],
            'direccion' => [
                'required'   => 'Ingresa la dirección de tu negocio.',
                'max_length' => 'La dirección no debe superar los 255 caracteres.',
            ],
            'distrito_id' => [
                'required'           => 'Selecciona tu distrito.',
                'is_natural_no_zero' => 'Selecciona un distrito válido.',
                'in_list'            => 'Selecciona un distrito válido y activo.',
            ],
            'telefono_principal' => [
                'required'    => 'Ingresa el teléfono o WhatsApp de tu negocio.',
                'regex_match' => 'El teléfono debe contener solo números, entre 7 y 15 dígitos.',
            ],
            'horario_texto' => [
                'max_length' => 'El horario no debe superar los 255 caracteres.',
            ],
            'google_analytics_id' => [
                'max_length'  => 'El ID de Google Analytics no debe superar los 20 caracteres.',
                'regex_match' => 'Ingresa un ID válido de Google Analytics (G-XXXXXXXXXX o UA-XXXXXX-X).',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput();
        }

        if (! $this->negocioModel->slugDisponible($data['slug'], $idNegocio)) {
            $this->validator->setError('slug', 'El slug ya está en uso. Elige otro.');

            return redirect()->back()->withInput();
        }

        $logoNuevoAbsoluto = null;
        $logoRutaNueva     = null;
        $archivoLogo       = $this->request->getFile('logo');

        if ($archivoLogo !== null && $archivoLogo->getError() !== UPLOAD_ERR_NO_FILE) {
            if (! $archivoLogo->isValid()) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'No pudimos recibir el logo. Inténtalo nuevamente.'
                );
            }

            if ($archivoLogo->getSize() > self::LOGO_TAMANO_MAXIMO) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'El logo no debe superar los 2 MB.'
                );
            }

            try {
                $rutaTemporal = $archivoLogo->getTempName();

                if (! is_file($rutaTemporal)) {
                    throw new RuntimeException('El archivo temporal del logo no existe.');
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                if ($finfo === false) {
                    throw new RuntimeException('No se pudo validar el tipo de archivo.');
                }

                $mime = finfo_file($finfo, $rutaTemporal);
                finfo_close($finfo);

                if (! is_string($mime) || ! isset(self::LOGO_MIMES_PERMITIDOS[$mime])) {
                    return redirect()->back()->withInput()->with(
                        'error',
                        'Formato no permitido. Sube un logo JPG, PNG o WebP.'
                    );
                }

                $extension  = self::LOGO_MIMES_PERMITIDOS[$mime];
                $directorio = rtrim(WRITEPATH, '/\\')
                    . DIRECTORY_SEPARATOR . 'uploads'
                    . DIRECTORY_SEPARATOR . 'logos'
                    . DIRECTORY_SEPARATOR . $idNegocio;

                if (! is_dir($directorio)) {
                    @mkdir($directorio, 0775, true);
                }

                if (! is_dir($directorio) || ! is_writable($directorio)) {
                    throw new RuntimeException('La carpeta de logos no es escribible.');
                }

                $nombreArchivo = hash('sha256', time() . random_bytes(8)) . '.' . $extension;
                $archivoLogo->move($directorio, $nombreArchivo);

                $logoNuevoAbsoluto = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;

                if (! is_file($logoNuevoAbsoluto)) {
                    throw new RuntimeException('No se pudo guardar el logo.');
                }

                $logoRutaNueva = 'uploads/logos/' . $idNegocio . '/' . $nombreArchivo;
            } catch (Throwable $e) {
                if ($logoNuevoAbsoluto !== null && is_file($logoNuevoAbsoluto)) {
                    @unlink($logoNuevoAbsoluto);
                }

                log_message('error', 'Error al subir logo: {message}', ['message' => $e->getMessage()]);

                return redirect()->back()->withInput()->with(
                    'error',
                    'No pudimos subir el logo. Revisa el archivo e inténtalo nuevamente.'
                );
            }
        }

        $evaluacion = $this->moderationService->evaluarCampos($data);
        $bloqueado  = $evaluacion['bloqueado'];

        $datosPerfil = [
            'nombre_comercial'   => $data['nombre_comercial'],
            'slug'               => $data['slug'],
            'descripcion'        => $data['descripcion'] !== '' ? $data['descripcion'] : null,
            'direccion'          => $data['direccion'],
            'distrito_id'        => (int) $data['distrito_id'],
            'telefono_principal' => $data['telefono_principal'],
            'horario_texto'      => $data['horario_texto'] !== '' ? $data['horario_texto'] : null,
            'modified_by'        => $idUsuario,
        ];

        if ($logoRutaNueva !== null) {
            $datosPerfil['logo_ruta'] = $logoRutaNueva;
        }

        if ($tieneAnalytics) {
            $datosPerfil['google_analytics_id'] = $data['google_analytics_id'] !== ''
                ? $data['google_analytics_id']
                : null;
        }

        if ($bloqueado) {
            $datosPerfil['activo'] = 0;
        }

        $db = db_connect();
        $db->transStart();

        try {
            $actualizado = $this->negocioModel->actualizarPerfil($idNegocio, $datosPerfil);

            if (! $actualizado) {
                throw new RuntimeException('No se pudo actualizar el negocio.');
            }

            $this->registrarLog('editar_perfil', $idUsuario, $idNegocio, $idNegocio, 'Actualización del perfil del negocio.');

            if ($bloqueado) {
                $this->moderationService->registrarMarcado(
                    'negocios',
                    $idNegocio,
                    $evaluacion,
                    $idNegocio,
                    $idUsuario
                );
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción del perfil no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();

            if ($logoNuevoAbsoluto !== null && is_file($logoNuevoAbsoluto)) {
                @unlink($logoNuevoAbsoluto);
            }

            log_message('error', 'Error al actualizar perfil: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos guardar el perfil. Revisa tus datos e inténtalo nuevamente.'
            );
        }

        if ($logoRutaNueva !== null) {
            $rutaLogoAnterior = $this->absolutaLogo((string) ($negocio['logo_ruta'] ?? ''));

            if ($rutaLogoAnterior !== null && is_file($rutaLogoAnterior)) {
                @unlink($rutaLogoAnterior);
            }
        }

        session()->set('nombre_comercial', $data['nombre_comercial']);

        return redirect()->to(base_url('negocio/perfil'))->with(
            'success',
            $bloqueado
                ? 'Tu perfil ha sido actualizado, pero será revisado antes de publicarse.'
                : 'Perfil del negocio actualizado correctamente.'
        );
    }

    private function absolutaLogo(?string $ruta): ?string
    {
        if ($ruta === null || trim($ruta) === '') {
            return null;
        }

        $partes = explode(
            DIRECTORY_SEPARATOR,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($ruta))
        );

        if (count($partes) !== 4
            || $partes[0] !== 'uploads'
            || $partes[1] !== 'logos'
            || ! ctype_digit($partes[2])
            || basename($partes[3]) !== $partes[3]
        ) {
            return null;
        }

        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $partes);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function negocioActual(): ?array
    {
        $idNegocio = (int) session()->get('id_negocio');

        if ($idNegocio <= 0) {
            return null;
        }

        return $this->negocioModel
            ->where('id_negocio', $idNegocio)
            ->first();
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
            'tabla_afectada' => 'negocios',
            'registro_id'    => $registroId,
            'descripcion'    => $descripcion,
            'ip_address'     => $this->request->getIPAddress(),
            'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
        ]);
    }
}
