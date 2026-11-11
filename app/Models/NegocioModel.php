<?php

namespace App\Models;

use CodeIgniter\Model;

class NegocioModel extends Model
{
    protected $table            = 'negocios';
    protected $primaryKey       = 'id_negocio';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre_comercial',
        'slug',
        'descripcion',
        'logo_ruta',
        'direccion',
        'distrito_id',
        'telefono_principal',
        'horario_texto',
        'google_analytics_id',
        'siguiente_numero_pedido',
        'activo',
        'created_by',
        'modified_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';

    public function actualizarPerfil(int $idNegocio, array $datos): bool
    {
        return $this->update($idNegocio, $datos) !== false;
    }

    public function slugDisponible(string $slug, ?int $excluirId = null): bool
    {
        $builder = $this->withDeleted()
            ->where('slug', $slug);

        if ($excluirId !== null) {
            $builder->where('id_negocio !=', $excluirId);
        }

        return $builder->countAllResults() === 0;
    }

    public function obtenerCiudadSlug(int $idNegocio): ?string
    {
        $fila = $this->db->query(
            'SELECT ciudades.slug
             FROM negocios
             JOIN distritos ON distritos.id_distrito = negocios.distrito_id
             JOIN ciudades ON ciudades.id_ciudad = distritos.ciudad_id
             WHERE negocios.id_negocio = ? AND negocios.deleted_at IS NULL
             LIMIT 1',
            [$idNegocio]
        )->getRowArray();

        return $fila === null ? null : (string) $fila['slug'];
    }

    public function obtenerSiguienteNumeroConBloqueo(int $idNegocio): ?int
    {
        $fila = $this->db->query(
            'SELECT siguiente_numero_pedido FROM negocios WHERE id_negocio = ? AND deleted_at IS NULL FOR UPDATE',
            [$idNegocio]
        )->getRowArray();

        return $fila === null ? null : (int) $fila['siguiente_numero_pedido'];
    }

    /**
     * @param array{ciudad?: int|null, estado?: string, q?: string} $filtros
     *
     * @return list<array<string, mixed>>
     */
    public function listarConFiltros(array $filtros = [], int $perPage = 20): array
    {
        $builder = $this->select(
            'negocios.id_negocio, negocios.nombre_comercial, negocios.slug, negocios.activo, '
            . 'negocios.created_at, negocios.distrito_id, '
            . 'distritos.nombre AS distrito_nombre, distritos.ciudad_id, '
            . 'ciudades.nombre AS ciudad_nombre, '
            . 'u.email AS propietario_email, '
            . 'personas.nombres AS propietario_nombres, personas.apellidos AS propietario_apellidos, '
            . 'planes.nombre AS plan_nombre, '
            . '(
                SELECT COUNT(*)
                FROM productos p
                WHERE p.negocio_id = negocios.id_negocio
                  AND p.deleted_at IS NULL
            ) AS productos_count, '
            . '(
                SELECT COUNT(*)
                FROM pedidos pe
                WHERE pe.negocio_id = negocios.id_negocio
                  AND pe.deleted_at IS NULL
            ) AS pedidos_count'
        )
            ->join('distritos', 'distritos.id_distrito = negocios.distrito_id')
            ->join('ciudades', 'ciudades.id_ciudad = distritos.ciudad_id')
            ->join(
                'negocio_usuarios nu',
                "nu.negocio_id = negocios.id_negocio AND nu.rol_negocio = 'propietario' AND nu.activo = 1",
                'left'
            )
            ->join('usuarios u', 'u.id_usuario = nu.usuario_id', 'left')
            ->join('personas', 'personas.id_persona = u.persona_id', 'left')
            ->join(
                'suscripciones s',
                "s.negocio_id = negocios.id_negocio AND s.estado IN ('prueba', 'activa') AND s.deleted_at IS NULL",
                'left'
            )
            ->join('planes', 'planes.id_plan = s.plan_id', 'left');

        $ciudad = $filtros['ciudad'] ?? null;
        if ($ciudad !== null && (int) $ciudad > 0) {
            $builder->where('distritos.ciudad_id', (int) $ciudad);
        }

        $estado = $filtros['estado'] ?? 'todos';
        if ($estado === 'activo') {
            $builder->where('negocios.activo', 1);
        } elseif ($estado === 'suspendido') {
            $builder->where('negocios.activo', 0);
        }

        $busqueda = trim((string) ($filtros['q'] ?? ''));
        if ($busqueda !== '') {
            $builder->groupStart()
                ->like('negocios.nombre_comercial', $busqueda)
                ->orLike('negocios.slug', $busqueda)
                ->groupEnd();
        }

        return $builder
            ->orderBy('negocios.created_at', 'DESC')
            ->orderBy('negocios.id_negocio', 'DESC')
            ->paginate($perPage, 'negocios_admin');
    }

    public function contarActivos(): int
    {
        return $this->where('activo', 1)->countAllResults();
    }

    public function contarSuspendidos(): int
    {
        return $this->where('activo', 0)->countAllResults();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPropietarioActivo(int $idNegocio): ?array
    {
        return $this->db->query(
            'SELECT nu.usuario_id, u.email, p.nombres, p.apellidos, '
            . 'EXISTS('
            . '    SELECT 1 FROM usuario_roles ur '
            . '    JOIN roles r ON r.id_rol = ur.rol_id '
            . "    WHERE ur.usuario_id = u.id_usuario AND r.nombre = 'administrador' "
            . '      AND r.activo = 1 AND r.deleted_at IS NULL'
            . ') AS es_administrador '
            . 'FROM negocio_usuarios nu '
            . 'JOIN usuarios u ON u.id_usuario = nu.usuario_id '
            . 'JOIN personas p ON p.id_persona = u.persona_id '
            . "WHERE nu.negocio_id = ? AND nu.rol_negocio = 'propietario' AND nu.activo = 1 "
            . 'LIMIT 1',
            [$idNegocio]
        )->getRowArray();
    }

    public function obtenerLogoAbsoluto(int $idNegocio): ?string
    {
        $negocio = $this->select('logo_ruta')
            ->where('id_negocio', $idNegocio)
            ->first();

        if ($negocio === null || empty($negocio['logo_ruta'])) {
            return null;
        }

        $partes = explode(
            DIRECTORY_SEPARATOR,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim((string) $negocio['logo_ruta']))
        );

        if (count($partes) !== 4
            || $partes[0] !== 'uploads'
            || $partes[1] !== 'logos'
            || ! ctype_digit($partes[2])
            || basename($partes[3]) !== $partes[3]
        ) {
            return null;
        }

        $absoluta = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $partes);

        return is_file($absoluta) ? $absoluta : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerDetalleAdmin(int $idNegocio): ?array
    {
        return $this->db->query(
            'SELECT negocios.*, '
            . 'distritos.nombre AS distrito_nombre, distritos.ciudad_id, '
            . 'ciudades.nombre AS ciudad_nombre, ciudades.slug AS ciudad_slug, '
            . 'u.email AS propietario_email, '
            . 'personas.nombres AS propietario_nombres, personas.apellidos AS propietario_apellidos, '
            . 'personas.telefono AS propietario_telefono '
            . 'FROM negocios '
            . 'JOIN distritos ON distritos.id_distrito = negocios.distrito_id '
            . 'JOIN ciudades ON ciudades.id_ciudad = distritos.ciudad_id '
            . "LEFT JOIN negocio_usuarios nu ON nu.negocio_id = negocios.id_negocio AND nu.rol_negocio = 'propietario' AND nu.activo = 1 "
            . 'LEFT JOIN usuarios u ON u.id_usuario = nu.usuario_id '
            . 'LEFT JOIN personas ON personas.id_persona = u.persona_id '
            . 'WHERE negocios.id_negocio = ? AND negocios.deleted_at IS NULL '
            . 'LIMIT 1',
            [$idNegocio]
        )->getRowArray();
    }
}
