<?php

namespace App\Controllers\Negocio;

use App\Controllers\BaseController;
use App\Models\DistritoModel;
use App\Models\LogActividadModel;
use App\Models\NegocioModel;
use RuntimeException;
use Throwable;

class Perfil extends BaseController
{
    protected NegocioModel $negocioModel;
    protected DistritoModel $distritoModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->negocioModel  = model(NegocioModel::class);
        $this->distritoModel = model(DistritoModel::class);
        $this->logModel      = model(LogActividadModel::class);
    }

    public function index()
    {
        $negocio = $this->negocioActual();

        if ($negocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        return view('negocio/perfil', [
            'titulo'      => 'Mi perfil',
            'negocio'     => $negocio,
            'distritos'   => $this->distritoModel->getActivosConCiudadActiva(),
            'ciudad_slug' => $this->negocioModel->obtenerCiudadSlug((int) $negocio['id_negocio']),
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

        $distritos = $this->distritoModel->getActivosConCiudadActiva();
        $idsValidos = array_map(static fn (array $distrito): int => (int) $distrito['id_distrito'], $distritos);

        $data = [
            'nombre_comercial'   => trim((string) $this->request->getPost('nombre_comercial')),
            'slug'               => strtolower(trim((string) $this->request->getPost('slug'))),
            'descripcion'        => trim((string) $this->request->getPost('descripcion')),
            'direccion'          => trim((string) $this->request->getPost('direccion')),
            'distrito_id'        => trim((string) $this->request->getPost('distrito_id')),
            'telefono_principal' => trim((string) $this->request->getPost('telefono_principal')),
            'horario_texto'      => trim((string) $this->request->getPost('horario_texto')),
        ];

        $rules = [
            'nombre_comercial'   => 'required|max_length[150]',
            'slug'               => 'required|max_length[120]|regex_match[/^[a-z0-9]+(-[a-z0-9]+)*$/]',
            'direccion'          => 'required|max_length[255]',
            'telefono_principal' => 'required|regex_match[/^[0-9]{7,15}$/]',
            'horario_texto'      => 'permit_empty|max_length[255]',
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
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput();
        }

        if (! $this->negocioModel->slugDisponible($data['slug'], $idNegocio)) {
            $this->validator->setError('slug', 'El slug ya está en uso. Elige otro.');

            return redirect()->back()->withInput();
        }

        $db = db_connect();
        $db->transStart();

        try {
            $actualizado = $this->negocioModel->actualizarPerfil($idNegocio, [
                'nombre_comercial'   => $data['nombre_comercial'],
                'slug'               => $data['slug'],
                'descripcion'        => $data['descripcion'] !== '' ? $data['descripcion'] : null,
                'direccion'          => $data['direccion'],
                'distrito_id'        => (int) $data['distrito_id'],
                'telefono_principal' => $data['telefono_principal'],
                'horario_texto'      => $data['horario_texto'] !== '' ? $data['horario_texto'] : null,
                'modified_by'        => $idUsuario,
            ]);

            if (! $actualizado) {
                throw new RuntimeException('No se pudo actualizar el negocio.');
            }

            $this->registrarLog('editar_perfil', $idUsuario, $idNegocio, $idNegocio, 'Actualización del perfil del negocio.');

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción del perfil no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al actualizar perfil: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos guardar el perfil. Revisa tus datos e inténtalo nuevamente.'
            );
        }

        session()->set('nombre_comercial', $data['nombre_comercial']);

        return redirect()->to(base_url('negocio/perfil'))->with(
            'success',
            'Perfil del negocio actualizado correctamente.'
        );
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
            ->where('activo', 1)
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
