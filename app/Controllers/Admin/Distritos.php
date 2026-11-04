<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CiudadModel;
use App\Models\DistritoModel;
use App\Models\LogActividadModel;
use RuntimeException;
use Throwable;

class Distritos extends BaseController
{
    protected DistritoModel $distritoModel;
    protected CiudadModel $ciudadModel;
    protected LogActividadModel $logModel;

    public function __construct()
    {
        $this->distritoModel = model(DistritoModel::class);
        $this->ciudadModel   = model(CiudadModel::class);
        $this->logModel      = model(LogActividadModel::class);
    }

    public function index()
    {
        $filtroCiudad = (int) $this->request->getGet('ciudad');
        $filtroEstado = (string) $this->request->getGet('estado');

        if (! in_array($filtroEstado, ['todos', 'activos', 'inactivos'], true)) {
            $filtroEstado = 'todos';
        }

        return view('admin/distritos/index', [
            'titulo'       => 'Distritos',
            'distritos'    => $this->distritoModel->listarConNegocios(
                $filtroCiudad > 0 ? $filtroCiudad : null,
                $filtroEstado
            ),
            'ciudades'     => $this->ciudadModel->listarTodas(),
            'filtroCiudad' => $filtroCiudad,
            'filtroEstado' => $filtroEstado,
        ]);
    }

    public function crear()
    {
        return view('admin/distritos/form', [
            'titulo'   => 'Nuevo distrito',
            'distrito' => null,
            'ciudades' => $this->ciudadModel->listarTodas(),
            'accion'   => base_url('admin/distritos/crear'),
        ]);
    }

    public function guardar()
    {
        $data = [
            'ciudad_id'  => trim((string) $this->request->getPost('ciudad_id')),
            'nombre'     => trim((string) $this->request->getPost('nombre')),
            'descripcion' => trim((string) $this->request->getPost('descripcion')),
        ];

        if (! $this->validar($data)) {
            return redirect()->back()->withInput();
        }

        $ciudad = $this->ciudadModel->find((int) $data['ciudad_id']);

        if ($ciudad === null) {
            return redirect()->back()->withInput()->with(
                'error',
                'La ciudad seleccionada no existe.'
            );
        }

        if ($this->existeNombre((int) $data['ciudad_id'], $data['nombre'])) {
            return redirect()->back()->withInput()->with(
                'error',
                'Ya existe un distrito con ese nombre en la ciudad seleccionada.'
            );
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            $idDistrito = (int) $this->distritoModel->insert([
                'ciudad_id'   => (int) $data['ciudad_id'],
                'nombre'      => $data['nombre'],
                'descripcion' => $data['descripcion'] !== '' ? $data['descripcion'] : null,
                'activo'      => $this->request->getPost('activo') === '1' ? 1 : 0,
                'created_by'  => $idAdmin,
                'modified_by' => $idAdmin,
            ], true);

            if ($idDistrito <= 0) {
                throw new RuntimeException('No se pudo crear el distrito.');
            }

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => null,
                'accion'         => 'crear_distrito',
                'tabla_afectada' => 'distritos',
                'registro_id'    => $idDistrito,
                'descripcion'    => mb_substr(
                    'Distrito ' . $data['nombre'] . ' creado en ' . $ciudad['nombre'] . '.',
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad del distrito.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al crear distrito: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos crear el distrito. Verifica que el nombre no esté repetido en la ciudad.'
            );
        }

        return redirect()->to(base_url('admin/distritos'))->with(
            'success',
            'Distrito creado correctamente.'
        );
    }

    public function editar($id = null)
    {
        $distrito = $this->distritoModel->find((int) $id);

        if ($distrito === null) {
            return redirect()->to(base_url('admin/distritos'))->with(
                'error',
                'El distrito no existe.'
            );
        }

        return view('admin/distritos/form', [
            'titulo'   => 'Editar distrito',
            'distrito' => $distrito,
            'ciudades' => $this->ciudadModel->listarTodas(),
            'accion'   => base_url('admin/distritos/editar/' . (int) $distrito['id_distrito']),
        ]);
    }

    public function actualizar($id = null)
    {
        $idDistrito = (int) $id;
        $distrito   = $this->distritoModel->find($idDistrito);

        if ($distrito === null) {
            return redirect()->to(base_url('admin/distritos'))->with(
                'error',
                'El distrito no existe.'
            );
        }

        $data = [
            'ciudad_id'  => trim((string) $this->request->getPost('ciudad_id')),
            'nombre'     => trim((string) $this->request->getPost('nombre')),
            'descripcion' => trim((string) $this->request->getPost('descripcion')),
        ];

        if (! $this->validar($data)) {
            return redirect()->back()->withInput();
        }

        $ciudad = $this->ciudadModel->find((int) $data['ciudad_id']);

        if ($ciudad === null) {
            return redirect()->back()->withInput()->with(
                'error',
                'La ciudad seleccionada no existe.'
            );
        }

        if ($this->existeNombre((int) $data['ciudad_id'], $data['nombre'], $idDistrito)) {
            return redirect()->back()->withInput()->with(
                'error',
                'Ya existe un distrito con ese nombre en la ciudad seleccionada.'
            );
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            $this->distritoModel->update($idDistrito, [
                'ciudad_id'   => (int) $data['ciudad_id'],
                'nombre'      => $data['nombre'],
                'descripcion' => $data['descripcion'] !== '' ? $data['descripcion'] : null,
                'activo'      => $this->request->getPost('activo') === '1' ? 1 : 0,
                'modified_by' => $idAdmin,
            ]);

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => null,
                'accion'         => 'editar_distrito',
                'tabla_afectada' => 'distritos',
                'registro_id'    => $idDistrito,
                'descripcion'    => mb_substr(
                    'Distrito ' . $data['nombre'] . ' actualizado en ' . $ciudad['nombre'] . '.',
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad del distrito.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al actualizar distrito: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos actualizar el distrito. Verifica que el nombre no esté repetido en la ciudad.'
            );
        }

        return redirect()->to(base_url('admin/distritos'))->with(
            'success',
            'Distrito actualizado correctamente.'
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

    /**
     * @param array{ciudad_id: string, nombre: string, descripcion: string} $data
     */
    private function validar(array $data): bool
    {
        $rules = [
            'ciudad_id'   => 'required|is_natural_no_zero',
            'nombre'      => 'required|max_length[100]',
            'descripcion' => 'permit_empty|max_length[255]',
        ];

        $messages = [
            'ciudad_id' => [
                'required'           => 'Selecciona una ciudad.',
                'is_natural_no_zero' => 'Selecciona una ciudad válida.',
            ],
            'nombre' => [
                'required'   => 'Ingresa el nombre del distrito.',
                'max_length' => 'El nombre no debe superar los 100 caracteres.',
            ],
            'descripcion' => [
                'max_length' => 'La descripción no debe superar los 255 caracteres.',
            ],
        ];

        return $this->validateData($data, $rules, $messages);
    }

    private function existeNombre(int $idCiudad, string $nombre, ?int $excluirId = null): bool
    {
        $builder = $this->distritoModel
            ->where('ciudad_id', $idCiudad)
            ->where('nombre', $nombre);

        if ($excluirId !== null) {
            $builder->where('id_distrito !=', $excluirId);
        }

        return $builder->countAllResults() > 0;
    }

    private function cambiarEstado(int $idDistrito, bool $activar)
    {
        $distrito = $this->distritoModel->find($idDistrito);

        if ($distrito === null) {
            return redirect()->to(base_url('admin/distritos'))->with(
                'error',
                'El distrito no existe.'
            );
        }

        $idAdmin = (int) session()->get('id_usuario');
        $db      = db_connect();
        $db->transBegin();

        try {
            $this->distritoModel->update($idDistrito, [
                'activo'      => $activar ? 1 : 0,
                'modified_by' => $idAdmin,
            ]);

            $accion = $activar ? 'activar_distrito' : 'desactivar_distrito';

            if ($this->logModel->insert([
                'usuario_id'     => $idAdmin,
                'negocio_id'     => null,
                'accion'         => $accion,
                'tabla_afectada' => 'distritos',
                'registro_id'    => $idDistrito,
                'descripcion'    => mb_substr(
                    'Distrito ' . $distrito['nombre'] . ' '
                    . ($activar ? 'activado' : 'desactivado') . '.',
                    0,
                    500
                ),
                'ip_address'     => $this->request->getIPAddress(),
                'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
            ]) === false) {
                throw new RuntimeException('No se pudo registrar la actividad del distrito.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al cambiar estado de distrito: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/distritos'))->with(
                'error',
                'No pudimos actualizar el distrito. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('admin/distritos'))->with(
            'success',
            'El distrito ' . $distrito['nombre'] . ' fue '
            . ($activar ? 'activado' : 'desactivado') . ' correctamente.'
        );
    }
}
