# Diccionario de datos — katalo_pe (BD v2)

Referencia compacta para desarrollo. Fuente completa: `docs/schema-v2.sql`.
**No modificar el esquema.** `NULL` en límites de plan = ilimitado.

## Convenciones de auditoría
- **N1 (completo):** created_by, created_at, modified_by, modified_at, deleted_at.
- **N2 (relación):** created_by, created_at, modified_by, modified_at (sin deleted_at).
- **N3 (solo inserción):** created_at (sin timestamps automáticos).
- `created_by` / `modified_by`: INT UNSIGNED NULL, sin FK.

## Dominio geográfico y usuarios

### ciudades — N1
- PK: `id_ciudad` INT UNSIGNED AI
- UK: `nombre` VARCHAR(100), `slug` VARCHAR(100)
- Columnas: `region` VARCHAR(100) NULL, `activo` TINYINT(1) DEFAULT 0, `fecha_lanzamiento` DATE NULL

### distritos — N1
- PK: `id_distrito` INT UNSIGNED AI
- FK: `ciudad_id` → ciudades (RESTRICT)
- UK: (`ciudad_id`, `nombre`)
- Columnas: `nombre` VARCHAR(100), `descripcion` VARCHAR(255) NULL, `activo` TINYINT(1) DEFAULT 1

### personas — N1
- PK: `id_persona` INT UNSIGNED AI
- FK: `distrito_id` → distritos (SET NULL)
- Columnas: `nombres` VARCHAR(100), `apellidos` VARCHAR(100), `telefono` VARCHAR(15) CHECK `^[0-9]{7,15}$`, `direccion` VARCHAR(255) NULL
- Sin DNI.

### usuarios — N1
- PK: `id_usuario` INT UNSIGNED AI
- FK: `persona_id` → personas (RESTRICT)
- UK: `persona_id`, `email`
- Columnas: `email` VARCHAR(100) CHECK formato, `password_hash` VARCHAR(255) bcrypt, `activo` TINYINT(1) DEFAULT 1, `intentos_fallidos` TINYINT UNSIGNED DEFAULT 0, `bloqueado_hasta` DATETIME NULL, `ultimo_login` DATETIME NULL

### roles — N1
- PK: `id_rol` INT UNSIGNED AI
- UK: `nombre` VARCHAR(50)
- Valores: `administrador`, `usuario_negocio`, `administrador_ciudad` (inactivo, fase 2)
- Columnas: `descripcion` VARCHAR(255) NULL, `activo` TINYINT(1) DEFAULT 1

### usuario_roles — N2
- PK: `id_usuario_rol` INT UNSIGNED AI
- FK: `usuario_id` → usuarios (CASCADE), `rol_id` → roles (RESTRICT)
- UK: (`usuario_id`, `rol_id`)

### password_resets — N3
- PK: `id_reset` INT UNSIGNED AI
- FK: `usuario_id` → usuarios (CASCADE)
- UK: `token_hash` CHAR(64)
- Columnas: `expires_at` DATETIME, `used_at` DATETIME NULL, `ip_solicitud` VARCHAR(45) NULL, `created_at` TIMESTAMP
- **No usar `$useTimestamps = true`.**

### logs_actividad — N3
- PK: `id_log` BIGINT UNSIGNED AI
- FK: `usuario_id` → usuarios (SET NULL), `negocio_id` → negocios (SET NULL)
- Columnas: `accion` VARCHAR(50), `tabla_afectada` VARCHAR(64) NULL, `registro_id` INT UNSIGNED NULL, `descripcion` VARCHAR(500) NULL, `datos` JSON NULL, `ip_address` VARCHAR(45) NULL, `user_agent` VARCHAR(255) NULL, `created_at` TIMESTAMP
- **No usar `$useTimestamps = true`.**

### configuracion — N1
- PK: `id_config` INT UNSIGNED AI
- UK: `clave` VARCHAR(100)
- Columnas: `valor` TEXT, `descripcion` VARCHAR(255) NULL
- Claves seed: `sesion_minutos`=60, `dias_prueba`=14, `reset_password_horas`=24, `pedidos_max_por_ip_hora`=10, `login_intentos_max`=5, `login_bloqueo_minutos`=15

## Dominio del negocio (tenant)

### negocios — N1
- PK: `id_negocio` INT UNSIGNED AI
- FK: `distrito_id` → distritos (RESTRICT)
- UK: `slug`, `dominio_propio`
- Columnas: `nombre_comercial` VARCHAR(150), `slug` VARCHAR(120) CHECK `^[a-z0-9]+(-[a-z0-9]+)*$` (minúsculas, números, guiones), `razon_social` VARCHAR(150) NULL, `descripcion` TEXT NULL, `direccion` VARCHAR(255), `telefono_principal` VARCHAR(15) CHECK `^[0-9]{7,15}$`, `telefono_secundario` VARCHAR(15) NULL, `horario_texto` VARCHAR(255) NULL, `latitud` DECIMAL(10,8) NULL CHECK -90..90, `longitud` DECIMAL(11,8) NULL CHECK -180..180, `dominio_propio` VARCHAR(190) NULL, `google_analytics_id` VARCHAR(20) NULL, `siguiente_numero_pedido` INT UNSIGNED DEFAULT 1, `activo` TINYINT(1) DEFAULT 1

### negocio_usuarios — N2
- PK: `id_negocio_usuario` INT UNSIGNED AI
- FK: `negocio_id` → negocios (RESTRICT), `usuario_id` → usuarios (RESTRICT)
- UK: (`negocio_id`, `usuario_id`), `propietario_negocio_id` (generada)
- Columnas: `rol_negocio` ENUM('propietario','colaborador') DEFAULT 'colaborador', `activo` TINYINT(1) DEFAULT 1
- **GENERADA (no en `allowedFields`):** `propietario_negocio_id` = `negocio_id` si rol propietario y activo; garantiza un solo propietario (RN-29).

### horarios_atencion — N1
- PK: `id_horario` INT UNSIGNED AI
- FK: `negocio_id` → negocios (RESTRICT)
- Columnas: `dia_semana` TINYINT UNSIGNED CHECK 1..7, `hora_apertura` TIME, `hora_cierre` TIME CHECK ≠ apertura, `activo` TINYINT(1) DEFAULT 1

## Dominio del catálogo

### categorias — N1
- PK: `id_categoria` INT UNSIGNED AI
- FK: `negocio_id` → negocios (RESTRICT); (`negocio_id`, `categoria_padre_id`) → categorias (RESTRICT)
- UK: (`negocio_id`, `slug`), (`negocio_id`, `id_categoria`)
- Columnas: `nombre` VARCHAR(100), `slug` VARCHAR(120), `descripcion` VARCHAR(255) NULL, `categoria_padre_id` INT UNSIGNED NULL, `activo` TINYINT(1) DEFAULT 1
- Categorías **por negocio**, no globales.

### productos — N1
- PK: `id_producto` INT UNSIGNED AI
- FK: `negocio_id` → negocios (RESTRICT); (`negocio_id`, `categoria_id`) → categorias (RESTRICT)
- UK: (`negocio_id`, `id_producto`)
- Columnas: `categoria_id` INT UNSIGNED, `titulo` VARCHAR(200), `descripcion` TEXT NULL, `precio_referencial` DECIMAL(10,2) CHECK ≥0, `estado` ENUM('activo','pausado','agotado') DEFAULT 'activo', `visible` TINYINT(1) DEFAULT 1, `destacado` TINYINT(1) DEFAULT 0
- Borrado lógico con `deleted_at`; no existe estado "eliminado".

### imagenes_producto — N1
- PK: `id_imagen` INT UNSIGNED AI
- FK: `producto_id` → productos (CASCADE)
- UK: `principal_producto_id` (generada)
- Columnas: `ruta_imagen` VARCHAR(255), `nombre_archivo` VARCHAR(100), `es_principal` TINYINT(1) DEFAULT 0, `orden` TINYINT UNSIGNED DEFAULT 0
- **GENERADA (no en `allowedFields`):** `principal_producto_id` = `producto_id` si es principal y no eliminada.

## Dominio comercial (SaaS)

### planes — N1
- PK: `id_plan` INT UNSIGNED AI
- UK: `nombre`, `slug`
- Columnas: `descripcion` VARCHAR(255) NULL, `precio_mensual` DECIMAL(10,2) DEFAULT 0.00 CHECK ≥0, `max_productos` INT UNSIGNED NULL, `max_imagenes_por_producto` TINYINT UNSIGNED DEFAULT 1, `max_usuarios` SMALLINT UNSIGNED NULL DEFAULT 1, `max_pedidos_mes` INT UNSIGNED NULL, `tiene_dominio_propio` TINYINT(1) DEFAULT 0, `tiene_analytics` TINYINT(1) DEFAULT 0, `tiene_reportes` TINYINT(1) DEFAULT 0, `activo` TINYINT(1) DEFAULT 1
- Seed: Gratis (0.00, 10 prod, 1 img, 1 usuario, 30 pedidos/mes), Básico (29.00, NULL, 3 img, 1 usuario, NULL), Pro (59.00, NULL, 10 img, 5 usuarios, NULL, dominio/analytics/reportes = 1).

### suscripciones — N1
- PK: `id_suscripcion` INT UNSIGNED AI
- FK: `negocio_id` → negocios (RESTRICT), `plan_id` → planes (RESTRICT)
- UK: `vigente_negocio_id` (generada)
- Columnas: `estado` ENUM('prueba','pendiente_pago','activa','vencida','cancelada') DEFAULT 'prueba', `fecha_inicio` DATE, `fecha_fin` DATE NULL CHECK ≥ fecha_inicio, `precio_pactado` DECIMAL(10,2) DEFAULT 0.00 CHECK ≥0
- **GENERADA (no en `allowedFields`):** `vigente_negocio_id` = `negocio_id` si estado prueba/activa; garantiza una sola vigente (RN-23).

### pagos — N1
- PK: `id_pago` INT UNSIGNED AI
- FK: `suscripcion_id` → suscripciones (RESTRICT)
- Columnas: `monto` DECIMAL(10,2) CHECK >0, `metodo` ENUM('transferencia','yape','plin','efectivo','otro') DEFAULT 'transferencia', `referencia` VARCHAR(100) NULL, `comprobante_ruta` VARCHAR(255) NULL, `periodo_desde` DATE, `periodo_hasta` DATE CHECK ≥ periodo_desde, `fecha_pago` DATE, `estado` ENUM('pendiente','confirmado','rechazado') DEFAULT 'pendiente', `confirmado_por` INT UNSIGNED NULL, `confirmado_at` DATETIME NULL

### pedidos — N1
- PK: `id_pedido` INT UNSIGNED AI
- FK: `negocio_id` → negocios (RESTRICT)
- UK: (`negocio_id`, `numero`), (`negocio_id`, `codigo`), (`negocio_id`, `id_pedido`)
- Columnas: `numero` INT UNSIGNED, `codigo` VARCHAR(20) (ej. KTL-2026-0001), `cliente_nombre` VARCHAR(150), `cliente_telefono` VARCHAR(15) CHECK `^[0-9]{7,15}$`, `cliente_direccion` VARCHAR(255) NULL, `comentario` TEXT NULL, `total` DECIMAL(10,2) DEFAULT 0.00 CHECK ≥0, `estado` ENUM('nuevo','confirmado','en_proceso','entregado','cancelado') DEFAULT 'nuevo', `canal` ENUM('whatsapp','web','presencial') DEFAULT 'whatsapp', `ip_cliente` VARCHAR(45) NULL
- Cancelado no vuelve a otro estado (regla de aplicación, RN-17).

### detalle_pedido — N3
- PK: `id_detalle` INT UNSIGNED AI
- FK: (`negocio_id`, `pedido_id`) → pedidos (RESTRICT); (`negocio_id`, `producto_id`) → productos (RESTRICT)
- Columnas: `producto_titulo` VARCHAR(200) (snapshot), `cantidad` INT UNSIGNED DEFAULT 1 CHECK >0, `precio_unitario` DECIMAL(10,2) CHECK ≥0 (snapshot), `subtotal` DECIMAL(12,2) GENERATED STORED (cantidad × precio_unitario), `nota` VARCHAR(255) NULL, `created_at` TIMESTAMP
- **No usar `$useTimestamps = true`.**

## Vistas
- `v_negocios_publicos`: negocios activos, no eliminados, distrito/ciudad activos y suscripción vigente.
- `v_productos_publicos`: productos visibles, activos y no eliminados de negocios públicos.

## Reglas de integridad clave
- Aislamiento por `negocio_id` en toda consulta del panel (RNF-10).
- FK compuestas impiden usar categoría/producto/pedido de otro negocio.
- Una sola suscripción vigente y un solo propietario activo por negocio.
- `planes` NULL = ilimitado (RN-28).
- Borrado lógico con `deleted_at`; las FK RESTRICT protegen historial.
