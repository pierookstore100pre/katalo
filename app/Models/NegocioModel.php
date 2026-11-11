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
     * @return list<array{ciudad: string, negocios_activos: int, pedidos_mes: int, mrr: float}>
     */
    public function obtenerProgresoPorCiudad(): array
    {
        $filas = $this->db->query(
            'SELECT c.id_ciudad, c.nombre AS ciudad,
                    (
                        SELECT COUNT(*)
                        FROM negocios n
                        JOIN distritos d ON d.id_distrito = n.distrito_id
                        WHERE d.ciudad_id = c.id_ciudad
                          AND n.activo = 1
                          AND n.deleted_at IS NULL
                    ) AS negocios_activos,
                    (
                        SELECT COUNT(*)
                        FROM pedidos p
                        JOIN negocios n ON n.id_negocio = p.negocio_id
                        JOIN distritos d ON d.id_distrito = n.distrito_id
                        WHERE d.ciudad_id = c.id_ciudad
                          AND p.deleted_at IS NULL
                          AND n.deleted_at IS NULL
                          AND YEAR(p.created_at) = YEAR(CURDATE())
                          AND MONTH(p.created_at) = MONTH(CURDATE())
                    ) AS pedidos_mes,
                    (
                        SELECT COALESCE(SUM(s.precio_pactado), 0)
                        FROM suscripciones s
                        JOIN negocios n ON n.id_negocio = s.negocio_id
                        JOIN distritos d ON d.id_distrito = n.distrito_id
                        WHERE d.ciudad_id = c.id_ciudad
                          AND s.estado = \'activa\'
                          AND s.deleted_at IS NULL
                          AND n.deleted_at IS NULL
                    ) AS mrr
             FROM ciudades c
             WHERE c.deleted_at IS NULL
             ORDER BY c.nombre ASC'
        )->getResultArray();

        return array_map(static function (array $fila): array {
            return [
                'ciudad'           => (string) $fila['ciudad'],
                'negocios_activos' => (int) $fila['negocios_activos'],
                'pedidos_mes'      => (int) $fila['pedidos_mes'],
                'mrr'              => (float) $fila['mrr'],
            ];
        }, $filas);
    }

    /**
     * @return list<array{plan: string, negocios: int, mrr: float}>
     */
    public function obtenerRendimientoPorPlan(): array
    {
        $filas = $this->db->query(
            'SELECT planes.nombre AS plan,
                    COUNT(s.id_suscripcion) AS negocios,
                    COALESCE(SUM(s.precio_pactado), 0) AS mrr
             FROM planes
             LEFT JOIN suscripciones s
               ON s.plan_id = planes.id_plan
              AND s.estado = \'activa\'
              AND s.deleted_at IS NULL
             WHERE planes.deleted_at IS NULL
             GROUP BY planes.id_plan, planes.nombre, planes.precio_mensual
             ORDER BY planes.precio_mensual ASC, planes.id_plan ASC'
        )->getResultArray();

        return array_map(static function (array $fila): array {
            return [
                'plan'     => (string) $fila['plan'],
                'negocios' => (int) $fila['negocios'],
                'mrr'      => (float) $fila['mrr'],
            ];
        }, $filas);
    }

    /**
     * @return list<array{etiqueta: string, total_negocios: int, mrr: float, total_pedidos: int}>
     */
    public function obtenerReporteDinamico(
        string $agrupar,
        int $ciudadId = 0,
        int $planId = 0,
        int $distritoId = 0,
        string $metodoPago = ''
    ): array {
        $campos = [
            'ciudad'   => 'c.nombre',
            'distrito' => 'd.nombre',
            'plan'     => 'COALESCE(planes.nombre, \'Sin plan\')',
        ];

        $grupos = [
            'ciudad'   => 'c.id_ciudad, c.nombre',
            'distrito' => 'd.id_distrito, d.nombre',
            'plan'     => 'planes.id_plan, COALESCE(planes.nombre, \'Sin plan\')',
        ];

        if (! array_key_exists($agrupar, $campos)) {
            $agrupar = 'ciudad';
        }

        $campo = $campos[$agrupar];
        $grupo = $grupos[$agrupar];

        $sql = 'SELECT ' . $campo . ' AS etiqueta,
                       COUNT(DISTINCT n.id_negocio) AS total_negocios,
                       COALESCE(SUM(s.precio_pactado), 0) AS mrr,
                       COALESCE(SUM(p.total_pedidos), 0) AS total_pedidos
                FROM negocios n
                JOIN distritos d ON d.id_distrito = n.distrito_id
                JOIN ciudades c ON c.id_ciudad = d.ciudad_id
                LEFT JOIN suscripciones s
                  ON s.negocio_id = n.id_negocio
                 AND s.estado = \'activa\'
                 AND s.deleted_at IS NULL
                LEFT JOIN planes ON planes.id_plan = s.plan_id
                LEFT JOIN (
                    SELECT negocio_id, COUNT(*) AS total_pedidos
                    FROM pedidos
                    WHERE deleted_at IS NULL
                      AND MONTH(created_at) = MONTH(CURDATE())
                      AND YEAR(created_at) = YEAR(CURDATE())
                    GROUP BY negocio_id
                ) p ON p.negocio_id = n.id_negocio';

        $parametros        = [];
        $metodosPagoValidos = ['transferencia', 'yape', 'plin', 'efectivo'];

        if ($metodoPago !== '' && in_array($metodoPago, $metodosPagoValidos, true)) {
            $sql .= ' JOIN (
                        SELECT DISTINCT sp.negocio_id
                        FROM pagos pg
                        JOIN suscripciones sp ON sp.id_suscripcion = pg.suscripcion_id
                        WHERE pg.metodo = ?
                          AND pg.estado = \'confirmado\'
                          AND pg.deleted_at IS NULL
                          AND sp.deleted_at IS NULL
                      ) pago_filtro ON pago_filtro.negocio_id = n.id_negocio';
            $parametros[] = $metodoPago;
        }

        $sql .= ' WHERE n.deleted_at IS NULL
                  AND n.activo = 1';

        if ($ciudadId > 0) {
            $sql .= ' AND c.id_ciudad = ?';
            $parametros[] = $ciudadId;
        }

        if ($planId > 0) {
            $sql .= ' AND s.plan_id = ?';
            $parametros[] = $planId;
        }

        if ($distritoId > 0) {
            $sql .= ' AND d.id_distrito = ?';
            $parametros[] = $distritoId;
        }

        $sql .= ' GROUP BY ' . $grupo . ' ORDER BY mrr DESC, etiqueta ASC';

        $filas = $this->db->query($sql, $parametros)->getResultArray();

        return array_map(static function (array $fila): array {
            return [
                'etiqueta'       => (string) $fila['etiqueta'],
                'total_negocios' => (int) $fila['total_negocios'],
                'mrr'            => (float) $fila['mrr'],
                'total_pedidos'  => (int) $fila['total_pedidos'],
            ];
        }, $filas);
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
