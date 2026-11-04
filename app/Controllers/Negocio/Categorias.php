<?php

namespace App\Controllers\Negocio;

use App\Controllers\BaseController;
use App\Models\CategoriaModel;
use App\Models\LogActividadModel;
use App\Services\ModerationService;
use RuntimeException;
use Throwable;

class Categorias extends BaseController
{
    protected CategoriaModel $categoriaModel;
    protected LogActividadModel $logModel;
    protected ModerationService $moderationService;

    public function __construct()
    {
        $this->categoriaModel    = model(CategoriaModel::class);
        $this->logModel          = model(LogActividadModel::class);
        $this->moderationService = new ModerationService();
    }

    public function index()
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        return view('negocio/categorias/index', [
            'titulo'     => 'Mis categorías',
            'categorias' => $this->categoriaModel->getArbolPorNegocio($idNegocio),
        ]);
    }

    public function crear()
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        return view('negocio/categorias/form', [
            'titulo'           => 'Nueva categoría',
            'esEdicion'        => false,
            'categoria'        => null,
            'padres'           => $this->categoriaModel->getArbolPorNegocio($idNegocio),
            'accion'           => base_url('negocio/categorias/crear'),
            'activoPorDefecto' => true,
        ]);
    }

    public function guardar()
    {
        $idNegocio = $this->negocioId();

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $idUsuario = (int) session()->get('id_usuario');
        $data      = $this->datosFormulario();

        if (! $this->validarCategoria($data, $idNegocio, null)) {
            return redirect()->back()->withInput();
        }

        $evaluacion = $this->moderationService->evaluarCampos($data);
        $bloqueado  = $evaluacion['bloqueado'];

        $db = db_connect();
        $db->transStart();

        try {
            $idCategoria = (int) $this->categoriaModel->insert([
                'negocio_id'         => $idNegocio,
                'nombre'             => $data['nombre'],
                'slug'               => $data['slug'],
                'descripcion'        => $data['descripcion'] !== '' ? $data['descripcion'] : null,
                'categoria_padre_id' => $this->idPadre($data['categoria_padre_id']),
                'activo'             => $bloqueado ? 0 : $data['activo'],
                'created_by'         => $idUsuario,
                'modified_by'        => $idUsuario,
            ], true);

            if ($idCategoria <= 0) {
                throw new RuntimeException('No se pudo crear la categoría.');
            }

            $this->registrarLog(
                'crear_categoria',
                $idUsuario,
                $idNegocio,
                $idCategoria,
                'Creación de la categoría ' . $data['nombre'] . '.'
            );

            if ($bloqueado) {
                $this->moderationService->registrarMarcado(
                    'categorias',
                    $idCategoria,
                    $evaluacion,
                    $idNegocio,
                    $idUsuario
                );
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción de la categoría no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al crear categoría: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos crear la categoría. Revisa los datos e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/categorias'))->with(
            'success',
            $bloqueado
                ? 'Tu categoría ha sido guardada, pero será revisada por un administrador antes de publicarse.'
                : 'Categoría creada correctamente.'
        );
    }

    public function editar($id = null)
    {
        $idNegocio  = $this->negocioId();
        $idCategoria = (int) $id;

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $categoria = $this->categoriaModel->findDelNegocio($idCategoria, $idNegocio);

        if ($categoria === null) {
            return redirect()->to(base_url('negocio/categorias'))->with(
                'error',
                'La categoría no existe o no pertenece a tu negocio.'
            );
        }

        return view('negocio/categorias/form', [
            'titulo'           => 'Editar categoría',
            'esEdicion'        => true,
            'categoria'        => $categoria,
            'padres'           => $this->categoriaModel->getArbolDisponibleComoPadre($idNegocio, $idCategoria),
            'accion'           => base_url('negocio/categorias/editar/' . $idCategoria),
            'activoPorDefecto' => (int) $categoria['activo'] === 1,
        ]);
    }

    public function actualizar($id = null)
    {
        $idNegocio   = $this->negocioId();
        $idCategoria = (int) $id;

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $categoria = $this->categoriaModel->findDelNegocio($idCategoria, $idNegocio);

        if ($categoria === null) {
            return redirect()->to(base_url('negocio/categorias'))->with(
                'error',
                'La categoría no existe o no pertenece a tu negocio.'
            );
        }

        $idUsuario = (int) session()->get('id_usuario');

        if ($this->request->getPost('accion') === 'estado') {
            return $this->cambiarEstado($idCategoria, $idNegocio, $idUsuario);
        }

        $data = $this->datosFormulario();

        if (! $this->validarCategoria($data, $idNegocio, $idCategoria)) {
            return redirect()->back()->withInput();
        }

        $evaluacion = $this->moderationService->evaluarCampos($data);
        $bloqueado  = $evaluacion['bloqueado'];

        $db = db_connect();
        $db->transStart();

        try {
            $actualizada = $this->categoriaModel->update($idCategoria, [
                'nombre'             => $data['nombre'],
                'slug'               => $data['slug'],
                'descripcion'        => $data['descripcion'] !== '' ? $data['descripcion'] : null,
                'categoria_padre_id' => $this->idPadre($data['categoria_padre_id']),
                'activo'             => $bloqueado ? 0 : $data['activo'],
                'modified_by'        => $idUsuario,
            ]);

            if (! $actualizada) {
                throw new RuntimeException('No se pudo actualizar la categoría.');
            }

            $this->registrarLog(
                'editar_categoria',
                $idUsuario,
                $idNegocio,
                $idCategoria,
                'Actualización de la categoría ' . $data['nombre'] . '.'
            );

            if ($bloqueado) {
                $this->moderationService->registrarMarcado(
                    'categorias',
                    $idCategoria,
                    $evaluacion,
                    $idNegocio,
                    $idUsuario
                );
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción de la categoría no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al actualizar categoría: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with(
                'error',
                'No pudimos actualizar la categoría. Revisa los datos e inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/categorias'))->with(
            'success',
            $bloqueado
                ? 'Tu categoría ha sido guardada, pero será revisada por un administrador antes de publicarse.'
                : 'Categoría actualizada correctamente.'
        );
    }

    public function eliminar($id = null)
    {
        $idNegocio   = $this->negocioId();
        $idCategoria = (int) $id;

        if ($idNegocio === null) {
            return $this->cerrarSesionSinNegocio();
        }

        $categoria = $this->categoriaModel->findDelNegocio($idCategoria, $idNegocio);

        if ($categoria === null) {
            return redirect()->to(base_url('negocio/categorias'))->with(
                'error',
                'La categoría no existe o no pertenece a tu negocio.'
            );
        }

        $idUsuario = (int) session()->get('id_usuario');
        $tieneRelaciones = $this->categoriaModel->tieneProductos($idCategoria, $idNegocio)
            || $this->categoriaModel->tieneHijos($idCategoria, $idNegocio);

        $db = db_connect();
        $db->transStart();

        try {
            if ($tieneRelaciones) {
                $this->categoriaModel->update($idCategoria, [
                    'activo'      => 0,
                    'modified_by' => $idUsuario,
                ]);

                $mensaje = 'La categoría se desactivó porque tiene productos o subcategorías asociadas.';
            } else {
                $this->categoriaModel->delete($idCategoria);

                $mensaje = 'Categoría eliminada correctamente.';
            }

            $this->registrarLog(
                'eliminar_categoria',
                $idUsuario,
                $idNegocio,
                $idCategoria,
                'Eliminación de la categoría ' . $categoria['nombre'] . '.'
            );

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción de eliminación no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al eliminar categoría: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('negocio/categorias'))->with(
                'error',
                'No pudimos eliminar la categoría. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/categorias'))->with('success', $mensaje);
    }

    private function cambiarEstado(int $idCategoria, int $idNegocio, int $idUsuario)
    {
        $activo = $this->request->getPost('activo') === '1' ? 1 : 0;

        $db = db_connect();
        $db->transStart();

        try {
            $this->categoriaModel->update($idCategoria, [
                'activo'      => $activo,
                'modified_by' => $idUsuario,
            ]);

            $this->registrarLog(
                'editar_categoria',
                $idUsuario,
                $idNegocio,
                $idCategoria,
                $activo === 1 ? 'Categoría activada.' : 'Categoría desactivada.'
            );

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new RuntimeException('La transacción de estado no se pudo confirmar.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al cambiar estado de categoría: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('negocio/categorias'))->with(
                'error',
                'No pudimos cambiar el estado de la categoría. Inténtalo nuevamente.'
            );
        }

        return redirect()->to(base_url('negocio/categorias'))->with(
            'success',
            $activo === 1 ? 'Categoría activada correctamente.' : 'Categoría desactivada correctamente.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function datosFormulario(): array
    {
        $padreId = trim((string) $this->request->getPost('categoria_padre_id'));

        if ($padreId === '0') {
            $padreId = '';
        }

        return [
            'nombre'             => trim((string) $this->request->getPost('nombre')),
            'slug'               => strtolower(trim((string) $this->request->getPost('slug'))),
            'descripcion'        => trim((string) $this->request->getPost('descripcion')),
            'categoria_padre_id' => $padreId,
            'activo'             => $this->request->getPost('activo') ? 1 : 0,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validarCategoria(array $data, int $idNegocio, ?int $idCategoria): bool
    {
        $rules = [
            'nombre'             => 'required|max_length[100]',
            'slug'               => 'required|max_length[120]|regex_match[/^[a-z0-9]+(-[a-z0-9]+)*$/]',
            'descripcion'        => 'permit_empty|max_length[255]',
            'categoria_padre_id' => 'permit_empty|is_natural_no_zero',
        ];

        $messages = [
            'nombre' => [
                'required'   => 'Ingresa el nombre de la categoría.',
                'max_length' => 'El nombre no debe superar los 100 caracteres.',
            ],
            'slug' => [
                'required'    => 'Ingresa el slug de la categoría.',
                'max_length'  => 'El slug no debe superar los 120 caracteres.',
                'regex_match' => 'El slug solo puede tener minúsculas, números y guiones.',
            ],
            'descripcion' => [
                'max_length' => 'La descripción no debe superar los 255 caracteres.',
            ],
            'categoria_padre_id' => [
                'is_natural_no_zero' => 'Selecciona una categoría padre válida.',
            ],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return false;
        }

        if (! $this->categoriaModel->slugDisponible($idNegocio, $data['slug'], $idCategoria)) {
            $this->validator->setError('slug', 'Ya existe una categoría con ese slug en tu negocio.');

            return false;
        }

        if ($data['categoria_padre_id'] !== '') {
            $padres = $this->categoriaModel->getArbolDisponibleComoPadre($idNegocio, $idCategoria);
            $idsPermitidos = array_map(
                static fn (array $categoria): int => (int) $categoria['id_categoria'],
                $padres
            );

            if (! in_array((int) $data['categoria_padre_id'], $idsPermitidos, true)) {
                $this->validator->setError(
                    'categoria_padre_id',
                    'Selecciona una categoría padre válida de tu negocio.'
                );

                return false;
            }
        }

        return true;
    }

    private function idPadre(string $padreId): ?int
    {
        if ($padreId === '') {
            return null;
        }

        return (int) $padreId;
    }

    private function negocioId(): ?int
    {
        $idNegocio = (int) session()->get('id_negocio');

        return $idNegocio > 0 ? $idNegocio : null;
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
            'tabla_afectada' => 'categorias',
            'registro_id'    => $registroId,
            'descripcion'    => mb_substr($descripcion, 0, 500),
            'ip_address'     => $this->request->getIPAddress(),
            'user_agent'     => mb_substr($this->request->getUserAgent()->getAgentString(), 0, 255),
        ]);
    }
}
