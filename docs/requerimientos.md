# Requerimientos — Katalo.pe (BD v2)

Documento de referencia para desarrollo. Fuente de esquema: `docs/database-dictionary.md` y `docs/schema-v2.sql`.

## Índice
- [1. Requerimientos funcionales (RF)](#1-requerimientos-funcionales-rf)
- [2. Requerimientos no funcionales (RNF)](#2-requerimientos-no-funcionales-rnf)
- [3. Reglas de negocio (RN)](#3-reglas-de-negocio-rn)
- [4. Historias de usuario por bloque](#4-historias-de-usuario-por-bloque)

---

## 1. Requerimientos funcionales (RF)

| ID | Requerimiento | Módulo |
|---|---|---|
| RF-01 | Registrar negocio con nombres, apellidos, teléfono/WhatsApp, email, contraseña, nombre comercial, distrito y dirección. Sin DNI. Email único. | Autenticación |
| RF-02 | Generar automáticamente un slug único para cada negocio. | Autenticación |
| RF-03 | Asignar automáticamente el plan Gratis y una suscripción vigente al registrarse. | Suscripciones |
| RF-04 | Iniciar sesión con email y contraseña cifrada. | Autenticación |
| RF-05 | Recuperar contraseña con enlace enviado por email. El enlace se guarda como hash, vence a las 24 h y se invalida tras el primer uso. | Autenticación |
| RF-06 | Cerrar sesión destruyendo la sesión activa. | Autenticación |
| RF-07 | Editar perfil del negocio: nombre comercial, slug, descripción, dirección, distrito, teléfono/WhatsApp y horario. | Panel del negocio |
| RF-08 | Registrar productos con título, categoría y precio obligatorios; descripción e imagen opcionales. | Panel del negocio |
| RF-09 | Editar datos de un producto. | Panel del negocio |
| RF-10 | Ocultar/reactivar un producto sin eliminarlo físicamente. | Panel del negocio |
| RF-11 | Subir múltiples imágenes por producto según `planes.max_imagenes_por_producto` y elegir una principal. | Panel del negocio |
| RF-12 | Crear y administrar categorías propias del negocio, con subcategorías opcionales. | Panel del negocio |
| RF-13 | Listar todos los productos del negocio: visibles, ocultos y agotados. | Panel del negocio |
| RF-14 | Exponer catálogo público por URL amigable `/c/{slug}`. | Catálogo público |
| RF-15 | Mostrar información pública del negocio: nombre, dirección, distrito, horario y teléfono. | Catálogo público |
| RF-16 | Mostrar solo productos visibles, activos y no eliminados de negocios públicos. | Catálogo público |
| RF-17 | Filtrar productos por categoría. | Catálogo público |
| RF-18 | Buscar productos por palabra clave. | Catálogo público |
| RF-19 | Permitir al cliente final seleccionar productos y cantidades en un carrito simple. | Pedidos |
| RF-20 | Generar mensaje estructurado de WhatsApp con productos, cantidades, precios y total. | Pedidos |
| RF-21 | Permitir al dueño ver y gestionar los pedidos recibidos. | Pedidos |
| RF-22 | Cambiar estado del pedido: nuevo → confirmado → en_proceso → entregado, o cancelado. | Pedidos |
| RF-23 | Mostrar al dueño su plan actual, límites, estado y fecha de fin de suscripción. | Suscripciones |
| RF-24 | Permitir solicitar upgrade. La solicitud crea una suscripción en pendiente_pago hasta que el administrador confirme el pago. | Suscripciones |
| RF-25 | Permitir al administrador gestionar planes: precios, límites, activación. | Administración |
| RF-26 | Permitir al administrador activar/desactivar ciudades. | Administración |
| RF-27 | Permitir al administrador gestionar negocios: activar, suspender y ver detalle. | Administración |
| RF-28 | Mostrar métricas por ciudad: negocios activos, productos, pedidos y MRR. | Administración |
| RF-29 | Validar formularios en el servidor. | Seguridad |
| RF-30 | Restringir acceso según rol de plataforma y rol dentro del negocio. | Seguridad |
| RF-31 | Mostrar mensajes de error claros sin exponer información técnica. | Seguridad |
| RF-32 | Validar tamaño y tipo de imágenes subidas. | Seguridad |
| RF-33 | Registrar acciones críticas en `logs_actividad`: login, crear/editar/eliminar, cambio de plan, pagos. | Auditoría |
| RF-34 | Permitir al administrador registrar y confirmar pagos manuales de suscripción. | Suscripciones |
| RF-35 | Permitir al propietario de un negocio con plan Pro agregar y desactivar colaboradores. | Panel del negocio |

---

## 2. Requerimientos no funcionales (RNF)

| ID | Categoría | Requerimiento | Criterio verificable |
|---|---|---|---|
| RNF-01 | Usabilidad | Intuitivo para una mype sin conocimientos técnicos. | Un usuario nuevo registra un producto en < 3 min. |
| RNF-02 | Usabilidad | Diseño mobile-first. | 100 % de vistas correctas en 375 px. |
| RNF-03 | Rendimiento | Catálogo público carga en < 3 s en 4G. | Lighthouse Performance ≥ 80. |
| RNF-04 | Seguridad | Contraseñas no en texto plano. | `password_hash()` bcrypt y `password_verify()`. |
| RNF-05 | Seguridad | Protección CSRF/XSS. | Token CSRF y escape con `esc()`/`htmlspecialchars()`. |
| RNF-06 | Seguridad | Forzar HTTPS en producción. | Redirección 301 HTTP → HTTPS. |
| RNF-07 | Autorización | Validar permisos en servidor en cada operación. | Prueba de acceso denegado por rol. |
| RNF-08 | Integridad | Operaciones críticas transaccionales. | `transStart()`/`transComplete()` en registro, pedidos, pagos y cambios de plan. |
| RNF-09 | Escalabilidad | Agregar ciudades sin modificar el núcleo. | Crear ciudad = insertar fila en `ciudades`. |
| RNF-10 | Escalabilidad | Aislamiento multi-tenant. | Toda consulta filtra por `negocio_id`; FK compuestas impiden cruces. |
| RNF-11 | Compatibilidad | Navegadores modernos Chrome, Firefox, Edge y Safari. | Pruebas en últimas 2 generaciones. |
| RNF-12 | Compatibilidad | Funcionar en Android e iOS. | Pruebas en Chrome Mobile y Safari iOS. |
| RNF-13 | Mantenibilidad | Código organizado por módulos y documentado. | Estructura clara y comentarios. |
| RNF-14 | Respaldo | Respaldo diario de BD. | Backup automático configurado y verificado. |
| RNF-15 | Recuperación | Restauración probada. | Prueba exitosa al menos 1 vez al mes. |
| RNF-16 | Disponibilidad | Disponibilidad mínima 99 % mensual. | Monitoreo con UptimeRobot o similar. |
| RNF-17 | Privacidad | Solo datos mínimos necesarios. | Auditoría del formulario de registro. |
| RNF-18 | Auditoría | Nivel 1: tablas modificables con `created_by, created_at, modified_by, modified_at, deleted_at`. Nivel 2: tablas de relación sin borrado lógico. Nivel 3: tablas de solo inserción con `created_at` (`logs_actividad`, `password_resets`, `detalle_pedido`). | Verificación contra diccionario de datos y script SQL v2. |
| RNF-19 | Localización | Idioma español Perú. | Todos los mensajes en español. |
| RNF-20 | Escalabilidad de planes | Límites por plan configurables desde administración. | Cambio de límites sin recompilar; NULL = ilimitado. |

---

## 3. Reglas de negocio (RN)

| ID | Regla | Justificación |
|---|---|---|
| RN-01 | Un correo electrónico solo puede pertenecer a una cuenta de usuario. Un usuario puede participar en uno o más negocios mediante `negocio_usuarios`; en el MVP, el registro crea un único negocio con el usuario como propietario. | Reemplaza “Un email solo puede asociarse a un negocio”. |
| RN-02 | El slug de un negocio debe ser único a nivel global. | Garantiza URL pública única. |
| RN-03 | Todo negocio nuevo se asigna al plan Gratis por defecto. | Motor de adquisición freemium. |
| RN-04 | Solo los productos visibles y activos aparecen en el catálogo público. | Productos ocultos no se muestran. |
| RN-05 | Cada negocio solo puede ver y editar sus propios productos. | Aislamiento multi-tenant. |
| RN-06 | El precio de un producto debe ser ≥ 0. | Evita precios inválidos. |
| RN-07 | Un negocio no puede superar los límites de su plan (productos, imágenes, usuarios, pedidos). | Regla del modelo SaaS. |
| RN-08 | Un pedido debe tener al menos un producto en el detalle. | Integridad del pedido. |
| RN-09 | El código de un pedido debe ser único dentro del negocio. | Identificación del pedido. |
| RN-10 | El teléfono del cliente en un pedido debe tener formato válido (7–15 dígitos). | Validación con CHECK. |
| RN-11 | Un negocio suspendido no aparece públicamente. | Control administrativo. |
| RN-12 | Un negocio solo puede operar si pertenece a una ciudad activa. | Coherencia geográfica. |
| RN-13 | Al eliminar lógicamente un negocio, su catálogo deja de ser público (RN-21) y sus datos se conservan, incluidos pedidos y suscripciones. La depuración física es un proceso administrativo posterior. | Protege el historial. |
| RN-14 | Los productos con `deleted_at` no se consideran (borrado lógico). | Auditoría y recuperación. |
| RN-15 | Las contraseñas se almacenan cifradas con bcrypt. | Seguridad mínima. |
| RN-16 | Solo el propietario o colaborador autorizado del negocio puede cambiar sus productos y perfil. | Control de acceso. |
| RN-17 | Un pedido cancelado no puede volver a estado anterior. | Flujo unidireccional. |
| RN-18 | Los pedidos registrados se conservan aunque el producto se elimine; se usa snapshot (`producto_titulo`, `precio_unitario`). | Historial íntegro. |
| RN-19 | Los teléfonos se guardan solo con dígitos, 7–15; la aplicación exige 9 dígitos y antepone 51. | Compatibilidad internacional. |
| RN-20 | Los límites de cada plan son configurables por el administrador. | Flexibilidad comercial. |
| RN-21 | El catálogo público solo muestra negocios activos, no eliminados, de ciudad activa y con suscripción vigente. | Vista `v_negocios_publicos`. |
| RN-22 | Una misma IP puede registrar como máximo N pedidos por hora (`pedidos_max_por_ip_hora`). | Limita pedidos falsos. |
| RN-23 | Un negocio solo puede tener una suscripción vigente (en prueba o activa) a la vez. Al cambiar de plan se cancela la anterior en la misma transacción. | Restricción única en BD. |
| RN-24 | Un upgrade genera una suscripción en `pendiente_pago`; pasa a `activa` cuando el administrador confirma el pago registrado. | Pagos manuales. |
| RN-25 | El precio que paga un negocio queda fijado en la suscripción (`precio_pactado`). Cambiar el precio de un plan no altera suscripciones existentes. | Métricas confiables. |
| RN-26 | Cada pedido recibe un número correlativo propio del negocio y un código legible `KTL-AAAA-NNNN`, único dentro del negocio. | Evita colisiones. |
| RN-27 | El email de un usuario y el slug de un negocio eliminados lógicamente no pueden reutilizarse. | Evita suplantación. |
| RN-28 | Los límites de cada plan (productos, imágenes, usuarios, pedidos) se leen de `planes`; NULL significa ilimitado. | Límites configurables. |
| RN-29 | Cada negocio tiene exactamente un usuario propietario activo. | Restricción única en BD. |

---

## 4. Historias de usuario por bloque

> Lee solo la sección del bloque que estés implementando. No leas los demás bloques.

### Bloque 0 — Infraestructura (ya completado)
- CodeIgniter 4 instalado, `.env` configurado, conexión a `katalo_pe` verificada con `api/ping`.

---

### Bloque 1 — Autenticación y registro

**RF:** RF-01, RF-02, RF-03, RF-04, RF-05, RF-06, RF-29, RF-30, RF-31, RF-33  
**RNF clave:** RNF-04, RNF-05, RNF-08, RNF-10, RNF-18  
**Tablas:** `personas`, `usuarios`, `roles`, `usuario_roles`, `negocios`, `negocio_usuarios`, `suscripciones`, `planes`, `password_resets`, `logs_actividad`, `distritos`

- **HU-01** — Como dueño de negocio, quiero registrarme con mis nombres y apellidos, teléfono/WhatsApp, email, contraseña y datos básicos de mi negocio (nombre, distrito y dirección), para acceder y crear mi catálogo.
  - CA: email único y válido; contraseña ≥ 8 con número; nombres, apellidos, teléfono, nombre comercial, distrito y dirección obligatorios; sin DNI; slug único; plan Gratis; usuario propietario del negocio.
- **HU-02** — Como dueño de negocio, quiero iniciar sesión con email y contraseña, para acceder a mi panel.
  - CA: credenciales válidas permiten acceso; error genérico si falla; sesión activa; respeta bloqueo por intentos (HU-45, se implementa en Bloque 9).
- **HU-03** — Como dueño de negocio, quiero recuperar mi contraseña con un enlace enviado a mi email, para acceder si la olvido.
  - CA: email registrado; token guardado como hash; vence 24 h; uso único; mensaje genérico; invalida token usado.
- **HU-04** — Como dueño de negocio, quiero cerrar sesión de forma segura, para proteger mi cuenta.
  - CA: destruye sesión; redirige al login; no permite volver con botón atrás.

---

### Bloque 2 — Perfil del negocio y categorías

**RF:** RF-07, RF-12  
**RNF clave:** RNF-10, RNF-18  
**Tablas:** `negocios`, `distritos`, `categorias`, `logs_actividad`

- **HU-05** — Como dueño de negocio, quiero editar el perfil de mi negocio (nombre comercial, slug, descripción, dirección, distrito, teléfono/WhatsApp y horario), para mantener actualizada mi información pública.
  - CA: campos actuales cargados; slug único y no reutilizable si fue eliminado; distrito/dirección/teléfono válidos; mensaje de éxito; URL pública refleja nuevo slug.
- **HU-10** — Como dueño de negocio, quiero crear y administrar mis propias categorías (con subcategorías opcionales), para organizar mi catálogo.
  - CA: categorías por negocio; slug único por negocio; subcategoría del mismo negocio; activar/desactivar; no globales.

---

### Bloque 3 — Productos e imágenes

**RF:** RF-08, RF-09, RF-10, RF-11, RF-13, RF-32  
**RNF clave:** RNF-05, RNF-10, RNF-18  
**Tablas:** `productos`, `imagenes_producto`, `categorias`, `planes`, `logs_actividad`

- **HU-06** — Como dueño de negocio, quiero registrar productos (título, categoría y precio obligatorios; descripción e imagen), para armar mi catálogo digital.
  - CA: título, categoría y precio obligatorios; precio ≥ 0; categoría del mismo negocio; respeta `max_productos`; imagen opcional; confirmación.
- **HU-07** — Como dueño de negocio, quiero editar los datos de un producto, para corregir información o actualizar precios.
  - CA: solo propietario/colaborador autorizado; campos actuales; validaciones; mensaje de éxito.
- **HU-08** — Como dueño de negocio, quiero ocultar temporalmente un producto o marcarlo como agotado sin borrarlo, para gestionar stock sin perder el registro.
  - CA: cambia `visible`/`estado`; no aparece en catálogo público; sí en panel; no hay borrado físico.
- **HU-09** — Como dueño de negocio, quiero subir hasta N imágenes por producto y elegir la principal, para mostrar mis productos de forma atractiva.
  - CA: hasta `max_imagenes_por_producto`; JPG/PNG/WebP; ≤ 2 MB; una principal; orden; validación server-side.
- **HU-11** — Como dueño de negocio, quiero ver un listado de todos mis productos (visibles, ocultos y agotados), para administrar mi catálogo.
  - CA: lista todos los productos del negocio autenticado; distingue visibles/ocultos/agotados; acciones editar/ocultar/reactivar; muestra consumo del plan.

---

### Bloque 4 — Catálogo público

**RF:** RF-14, RF-15, RF-16, RF-17, RF-18  
**RNF clave:** RNF-02, RNF-03, RNF-09, RNF-10  
**Tablas/vistas:** `v_negocios_publicos`, `v_productos_publicos`, `categorias`

- **HU-12** — Como cliente final, quiero abrir el catálogo de un negocio desde un enlace (`katalo.pe/c/mi-negocio`), para ver sus productos sin instalar app.
  - CA: URL `/c/{slug}`; carga < 3 s; muestra si negocio activo, no eliminado, ciudad activa y suscripción vigente; mensaje “Negocio no disponible” si no; mobile-first.
- **HU-13** — Como cliente final, quiero ver la información del negocio (nombre, dirección, distrito, horario y teléfono), para saber quién vende y dónde está.
  - CA: información visible sin scroll excesivo; datos desde `v_negocios_publicos`.
- **HU-14** — Como cliente final, quiero ver el catálogo en formato mobile-first, para navegarlo cómodamente desde mi celular.
  - CA: correcto en ≥ 375 px; botón de pedido fijo/visible; imágenes con lazy load.
- **HU-15** — Como cliente final, quiero filtrar productos por categoría, para encontrar lo que busco rápido.
  - CA: categorías activas del negocio; filtro instantáneo; solo productos visibles/activos/no eliminados.
- **HU-16** — Como cliente final, quiero buscar productos por palabra clave, para encontrar productos específicos.
  - CA: búsqueda en título/descripción; mensaje si no hay resultados; solo catálogo cargado.

---

### Bloque 5 — Pedidos por WhatsApp

**RF:** RF-19, RF-20, RF-21, RF-22  
**RNF clave:** RNF-08, RNF-10  
**Tablas:** `pedidos`, `detalle_pedido`, `negocios`, `productos`, `logs_actividad`

- **HU-17** — Como cliente final, quiero seleccionar varios productos y cantidades en un carrito simple, para armar mi pedido antes de enviarlo.
  - CA: agregar producto/cantidad; modificar/eliminar; subtotal y total; carrito vacío deshabilita botón.
- **HU-18** — Como cliente final, quiero enviar mi pedido por WhatsApp con un mensaje ya armado, indicando mi nombre y teléfono, para que el negocio lo reciba y lo atienda.
  - CA: nombre y teléfono obligatorios; mensaje incluye productos, cantidades, precios y total; abre `wa.me`; registra pedido antes de abrir WhatsApp; código único por negocio.
- **HU-19** — Como sistema, debo registrar cada pedido en el panel del negocio con estado **nuevo** y un código único, antes de abrir WhatsApp, para no perder ningún pedido.
  - CA: estado `nuevo`; `numero` y `codigo` únicos por negocio; guarda `ip_cliente`; detalle con snapshot; transacción.
- **HU-20** — Como dueño de negocio, quiero ver un listado de los pedidos recibidos en mi panel, para llevar un registro ordenado.
  - CA: lista por fecha; muestra estado, cliente, total; filtros básicos; solo pedidos del negocio.
- **HU-21** — Como dueño de negocio, quiero cambiar el estado de un pedido (nuevo → confirmado → en proceso → entregado, o cancelado), para gestionar el flujo de atención.
  - CA: flujo permitido; cancelado no vuelve; registra log; solo negocio propietario.

---

### Bloque 6 — Suscripciones y pagos

**RF:** RF-23, RF-24, RF-34  
**RNF clave:** RNF-08, RNF-10, RNF-18  
**Tablas:** `suscripciones`, `planes`, `pagos`, `negocios`, `configuracion`, `logs_actividad`

- **HU-22** — Como dueño de negocio, quiero que se me asigne automáticamente el plan Gratis al registrarme, para probar la plataforma sin pagar.
  - CA: al registrar se crea suscripción vigente con plan Gratis; sin pago; una sola vigente por negocio. *(Ya cubierta en Bloque 1; verificar que sigue funcionando.)*
- **HU-23** — Como dueño de negocio, quiero ver mi plan actual y mis límites, para saber cuántos productos puedo publicar.
  - CA: muestra plan, estado, fecha fin y uso vs límites; NULL = ilimitado; muestra opciones de upgrade.
- **HU-24** — Como dueño de negocio, quiero cambiar de plan (upgrade), para desbloquear más funcionalidades cuando crezca mi catálogo.
  - CA: crea suscripción `pendiente_pago`; cancela anterior en misma transacción; no activa hasta confirmar pago; guarda `precio_pactado`.
- **HU-38** — Como administrador, quiero registrar y confirmar pagos manuales de suscripción, para activar upgrades.
  - CA: registra monto > 0, método, referencia, periodo; confirmar activa suscripción; rechazar deja registro; auditoría.
- **HU-39** — Como sistema, quiero marcar suscripciones vencidas y asignar el plan Gratis, para mantener consistencia comercial.
  - CA: cron diario: `fecha_fin < hoy` → vencida; asigna Gratis; no altera pagos históricos.
- **HU-40** — Como sistema, quiero aplicar los límites del plan (productos, imágenes, usuarios y pedidos/mes), para cumplir RN-28.
  - CA: lee límites de `planes`; NULL ilimitado; bloquea al exceder; mensaje sugiere upgrade.

---

### Bloque 7 — Panel admin multi-ciudad

**RF:** RF-25, RF-26, RF-27, RF-28  
**RNF clave:** RNF-09, RNF-10, RNF-18  
**Tablas:** `ciudades`, `distritos`, `negocios`, `planes`, `pedidos`, `suscripciones`, `logs_actividad`

- **HU-25** — Como administrador, quiero gestionar los planes (precios, límites, activación), para ajustar la oferta comercial.
  - CA: CRUD de planes; límites y features; activar/desactivar; no altera `precio_pactado` de suscripciones existentes.
- **HU-26** — Como administrador, quiero activar/desactivar ciudades, para controlar el lanzamiento geográfico.
  - CA: solo admin; cambia `activo`; negocios de ciudad inactiva no aparecen públicamente; al reactivar vuelven.
- **HU-27** — Como administrador, quiero ver métricas por ciudad (negocios activos, productos, pedidos, MRR), para monitorear el crecimiento.
  - CA: métricas por ciudad/periodo; solo admin; datos consistentes con BD.
- **HU-28** — Como administrador, quiero gestionar negocios (activar, suspender, ver detalle), para controlar la plataforma.
  - CA: activar/suspender; suspender oculta catálogo; no borra historial; detalle con suscripción y pedidos.
- **HU-29** — Como administrador, quiero ver un panel con estadísticas globales, para tomar decisiones de negocio.
  - CA: MRR, negocios activos, pedidos, ciudades; solo admin.
- **HU-43** — Como administrador, quiero gestionar distritos por ciudad, para mantener la integridad geográfica.
  - CA: CRUD distritos; único `(ciudad_id, nombre)`; no eliminar si tiene negocios (RESTRICT); activo/inactivo.
- **HU-44** — Como administrador/negocio, quiero consultar el registro de actividad, para auditar acciones críticas.
  - CA: registra login/crear/editar/eliminar/cambio plan/pago; admin ve global; negocio solo lo suyo; incluye IP y fecha.

---

### Bloque 8 — Post-MVP (no implementar hasta cerrar Bloques 1–7)

**RF:** RF-35  
**Tablas:** `negocio_usuarios`, `negocios`, `planes`

- **HU-36** — Como dueño de negocio con plan Básico o Pro, quiero ver reportes básicos de mis pedidos (por periodo, productos más pedidos y total vendido), para entender cómo va mi negocio.
  - CA: solo Básico/Pro; filtro por periodo; productos más pedidos; total vendido; solo datos del negocio autenticado.
- **HU-37** — Como dueño de negocio con plan Pro, quiero agregar y desactivar colaboradores, para que mi equipo gestione catálogo y pedidos.
  - CA: solo Pro; propietario agrega/desactiva; respeta `max_usuarios`; un propietario activo por negocio; colaborador accede al negocio; no puede cambiar propietario.
- **HU-41** — Como dueño de negocio Pro, quiero configurar un dominio propio, para reforzar mi marca.
  - CA: solo Pro/`tiene_dominio_propio`; dominio único; validación DNS; no rompe slug público.
- **HU-42** — Como dueño de negocio Pro, quiero configurar mi ID de Google Analytics, para medir visitas.
  - CA: solo Pro/`tiene_analytics`; ID válido; se inserta solo en catálogo del negocio; no expone datos.

---

### Bloque 9 — Hardening y calidad

**HU:** HU-45, HU-46, HU-47, HU-48  
**Tablas:** `usuarios`, `pedidos`, `configuracion`

- **HU-45** — Como sistema, debo bloquear el login tras intentos fallidos, para proteger cuentas.
  - CA: `intentos_fallidos`; `bloqueado_hasta`; config 5 intentos/15 min; desbloqueo automático.
- **HU-46** — Como sistema, debo limitar pedidos por IP por hora, para evitar pedidos falsos.
  - CA: config `pedidos_max_por_ip_hora`=10; cuenta por `ip_cliente`; bloquea con mensaje; no afecta panel.
- **HU-47** — Como sistema, debo garantizar el aislamiento entre negocios, para evitar cruces de datos.
  - CA: consultas filtran `negocio_id`; FK compuestas impiden categoría/producto/pedido de otro negocio; prueba con 2 negocios.
- **HU-48** — Como operador, quiero respaldos diarios y restauración probada, para proteger la información.
  - CA: backup diario BD; retención 7 días; restauración mensual documentada.
  