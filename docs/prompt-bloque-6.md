Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe (20 tablas). Bloques 0 a 5 implementados: registro, login, perfil, categorías, productos, imágenes, catálogo público, carrito y pedidos funcionando.
Implementa SOLO el Bloque 6: suscripciones y pagos manuales. No implementes el panel admin completo (va en Bloque 7) ni colaboradores (Post-MVP).

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo la sección "Bloque 6 — Suscripciones y pagos" de docs/requerimientos.md.
Lee solo las secciones de docs/database-dictionary.md para: suscripciones, planes, pagos, negocios, usuarios, roles, usuario_roles, pedidos, productos, configuracion, logs_actividad.
No leas docs/schema-v2.sql completo salvo que necesites un CHECK o ENUM.
No inventes columnas. No modifiques el esquema ni crees migraciones.
No incluyas suscripciones.vigente_negocio_id en allowedFields (es generada).

========================================
ALCANCE
========================================
RF: RF-23, RF-24, RF-34.
HU: HU-23, HU-24, HU-38, HU-39, HU-40.
(HU-22 ya se implementó en el Bloque 1: al registrarse se crea suscripción Gratis activa. Solo verifica que sigue funcionando.)

NO implementar:
- Panel admin completo (HU-25, HU-26, HU-27, HU-28, HU-29 van en Bloque 7).
- Colaboradores (HU-37, Post-MVP).
- Dominio propio y Analytics (HU-41, HU-42, Post-MVP).
- Pasarela de pago en línea: los pagos son manuales.

========================================
REGLAS DE NEGOCIO
========================================
- RN-23: una sola suscripción vigente (prueba o activa) por negocio. La BD lo garantiza con vigente_negocio_id.
- RN-24: upgrade crea suscripción en 'pendiente_pago'; pasa a 'activa' cuando el admin confirma el pago.
- RN-25: precio_pactado se fija al crear la suscripción. Cambiar el precio del plan no altera suscripciones existentes.
- RN-28: límites desde planes; NULL = ilimitado.
- RN-07: respetar límites del plan en productos (ya implementado en Bloque 3), imágenes (ya implementado) y pedidos/mes (nuevo en este bloque).
- RNF-08: toda transacción que cambie suscripciones o pagos debe ser atómica.
- RNF-10: toda consulta del panel filtra por negocio_id de la sesión.
- RNF-18: Nivel 1 en suscripciones y pagos; Nivel 3 en logs_actividad.

========================================
MODIFICACIÓN NECESARIA EN BLOQUE 1 (LOGIN)
========================================
Modifica app/Controllers/Auth/Login.php para que, además de id_usuario, id_negocio, rol_negocio, nombre_comercial y email, guarde en sesión:
- rol_plataforma: 'administrador' o 'usuario_negocio' (obtenido de usuario_roles → roles.nombre).
Si el usuario tiene varios roles de plataforma, prioriza 'administrador' si existe.
No cambies el flujo de login; solo agrega este dato a la sesión.

========================================
COMANDO PARA CREAR ADMINISTRADOR
========================================
Crea app/Commands/CrearAdmin.php con el comando:
spark katalo:crear-admin --email=admin@katalo.pe --password=Admin1234 --nombres=Admin --apellidos=Katalo --telefono=999999999

El comando debe:
- Validar que email no exista; si existe, solo agregar el rol 'administrador' en usuario_roles (sin duplicar).
- Si no existe: crear personas (distrito_id = primer distrito activo de Tacna), usuarios, usuario_roles con rol 'administrador'.
- No crear negocio ni suscripción para el admin (el admin no es dueño de negocio).
- Mostrar mensaje de éxito.

========================================
FLUJO: VER MI PLAN (RF-23 / HU-23)
========================================
GET /negocio/mi-plan (protegido por AuthFilter):
- Obtener suscripción vigente del negocio (estado prueba o activa).
- Obtener plan de esa suscripción.
- Mostrar:
  - Nombre del plan, estado, fecha_inicio, fecha_fin (NULL = sin vencimiento).
  - Uso actual vs límites:
    - Productos: COUNT productos no eliminados / max_productos (NULL = Ilimitado).
    - Imágenes por producto: max_imagenes_por_producto.
    - Usuarios: COUNT negocio_usuarios activos / max_usuarios (NULL = Ilimitado).
    - Pedidos este mes: COUNT pedidos del mes actual / max_pedidos_mes (NULL = Ilimitado).
  - Sección "Mejorar mi plan": tarjetas de planes activos con precio y features; botón "Solicitar upgrade" para cada plan superior.
  - Si existe una suscripción en estado 'pendiente_pago', mostrar banner: "Tienes una solicitud de upgrade pendiente de confirmación de pago" y el detalle.
- Sin CSRF necesario aquí (solo lectura).

========================================
FLUJO: SOLICITAR UPGRADE (RF-24 / HU-24)
========================================
POST /negocio/mi-plan/upgrade/{id_plan} (protegido por AuthFilter, con CSRF):
1. Validar plan: existe, activo = 1, precio_mensual > 0 (no se puede "upgrade" a Gratis).
2. Verificar que el plan es superior al actual (precio_mensual > precio_pactado del plan vigente). Si no, error "Ya tienes este plan o uno superior".
3. Verificar que no exista ya una suscripción en 'pendiente_pago' para este negocio. Si existe, error "Ya tienes una solicitud de upgrade pendiente".
4. En transacción:
   - Insertar suscripciones con estado = 'pendiente_pago', fecha_inicio = CURDATE(), fecha_fin = NULL, precio_pactado = plan.precio_mensual.
   - NO cancelar la suscripción vigente actual; el negocio sigue con su plan actual hasta que el admin confirme el pago.
   - Registrar 'solicitar_upgrade' en logs_actividad con descripcion = "plan_anterior → plan_nuevo".
5. Redirigir a /negocio/mi-plan con mensaje de éxito.

Nota: la columna generada vigente_negocio_id solo aplica a estados 'prueba' y 'activa', por lo que 'pendiente_pago' no entra en conflicto con la suscripción vigente actual.

========================================
FLUJO: ADMIN REGISTRA Y CONFIRMA PAGOS (RF-34 / HU-38)
========================================
Crear AdminFilter (app/Filters/AdminFilter.php) que verifique session('rol_plataforma') === 'administrador'. Si no, redirigir a /dashboard con mensaje de acceso denegado.

Registrar el filtro en app/Config/Filters.php con alias 'admin'.

Rutas (protegidas por AuthFilter y AdminFilter):
GET  /admin/pagos                          → Admin\Pagos::index
GET  /admin/pagos/registrar/{id_suscripcion} → Admin\Pagos::registrar
POST /admin/pagos/registrar/{id_suscripcion} → Admin\Pagos::guardar
POST /admin/pagos/{id_pago}/confirmar      → Admin\Pagos::confirmar
POST /admin/pagos/{id_pago}/rechazar       → Admin\Pagos::rechazar

GET /admin/pagos:
- Lista suscripciones en estado 'pendiente_pago' con datos del negocio, plan solicitado, precio_pactado y fecha de solicitud.
- Lista pagos registrados (pendientes, confirmados, rechazados) con filtros por estado.
- Botón "Registrar pago" por cada suscripción pendiente.

GET /admin/pagos/registrar/{id_suscripcion}:
- Formulario: monto (default = precio_pactado del plan), metodo (transferencia/yape/plin/efectivo/otro), referencia, comprobante_ruta (opcional, solo texto por ahora; no implementes upload de archivo en este bloque), periodo_desde, periodo_hasta, fecha_pago.
- El monto debe ser > 0.
- periodo_hasta >= periodo_desde.

POST /admin/pagos/registrar/{id_suscripcion}:
- Insertar pagos con estado = 'pendiente', suscripcion_id, monto, metodo, referencia, periodo_desde, periodo_hasta, fecha_pago, created_by = admin_id.
- Registrar 'registrar_pago' en logs_actividad.
- Redirigir a /admin/pagos con mensaje.

POST /admin/pagos/{id_pago}/confirmar (transacción):
1. Verificar pago.estado = 'pendiente'.
2. Actualizar pagos: estado = 'confirmado', confirmado_por = admin_id, confirmado_at = NOW().
3. Actualizar suscripción del pago:
   - estado = 'activa', fecha_inicio = pago.periodo_desde, fecha_fin = pago.periodo_hasta.
   - precio_pactado ya está fijado; no cambiarlo.
4. Cancelar la suscripción vigente anterior del negocio (si existe y es distinta):
   - estado = 'cancelada', fecha_fin = pago.periodo_desde - INTERVAL 1 DAY (o CURDATE() si prefieres).
   - Esto libera la columna generada vigente_negocio_id.
5. Registrar 'confirmar_pago' en logs_actividad.
6. Commit.

POST /admin/pagos/{id_pago}/rechazar:
- Actualizar pagos: estado = 'rechazado'.
- La suscripción pendiente_pago se queda en ese estado; el admin puede registrar un nuevo pago o el negocio puede solicitar cancelación (no implementes cancelación en este bloque).
- Registrar 'rechazar_pago' en logs_actividad.

========================================
FLUJO: VENCER SUSCRIPCIONES (HU-39)
========================================
Crear app/Commands/VencerSuscripciones.php con el comando:
spark katalo:vencer-suscripciones

El comando debe:
1. Buscar suscripciones con estado IN ('prueba','activa') AND fecha_fin IS NOT NULL AND fecha_fin < CURDATE().
2. Para cada una:
   - Actualizar estado = 'vencida'.
   - Buscar plan Gratis (slug = 'gratis').
   - Insertar nueva suscripción con estado = 'activa', fecha_inicio = CURDATE(), fecha_fin = NULL, precio_pactado = 0.00, plan_id = gratis.
   - Registrar 'vencer_suscripcion' en logs_actividad con negocio_id.
3. Mostrar resumen: "X suscripciones vencidas, Y planes Gratis asignados".

No implementar cron del sistema operativo; solo el comando. El usuario lo ejecutará manualmente o configurará cron después.

========================================
FLUJO: APLICAR LÍMITES DE PEDIDOS (HU-40)
========================================
Crear app/Services/PlanLimites.php con métodos:
- obtenerPlanVigente(int $id_negocio): devuelve el plan de la suscripción vigente.
- puedeCrearProducto(int $id_negocio): ya implementado en Bloque 3; refactoriza para usar este servicio.
- puedeSubirImagen(int $id_negocio, int $id_producto): ya implementado en Bloque 3; refactoriza.
- puedeCrearPedido(int $id_negocio): COUNT pedidos del mes actual < max_pedidos_mes (si max_pedidos_mes no es NULL).

Modificar app/Controllers/Catalogo/Pedido.php (Bloque 5):
- Antes de iniciar la transacción, verificar puedeCrearPedido($id_negocio).
- Si no puede, devolver JSON {ok: false, mensaje: "Este negocio alcanzó el límite de pedidos de su plan este mes."}.
- No cambies el resto del flujo del pedido.

========================================
RUTAS (app/Config/Routes.php)
========================================
Protegidas por AuthFilter:
GET  /negocio/mi-plan                          → Negocio\MiPlan::index
POST /negocio/mi-plan/upgrade/{id_plan}        → Negocio\MiPlan::solicitarUpgrade

Protegidas por AuthFilter + AdminFilter:
GET  /admin/pagos                              → Admin\Pagos::index
GET  /admin/pagos/registrar/{id_suscripcion}   → Admin\Pagos::registrar
POST /admin/pagos/registrar/{id_suscripcion}   → Admin\Pagos::guardar
POST /admin/pagos/{id_pago}/confirmar          → Admin\Pagos::confirmar
POST /admin/pagos/{id_pago}/rechazar           → Admin\Pagos::rechazar

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Modelos (app/Models/):
- PagoModel.php (nuevo): Nivel 1, soft delete, timestamps.
- SuscripcionModel.php (ya existe): agregar métodos:
  - obtenerVigentePorNegocio($id_negocio)
  - obtenerPendientePagoPorNegocio($id_negocio)
  - listarPendientesPago()
- PlanModel.php (ya existe): agregar obtenerPorSlug($slug).
- NegocioModel.php (ya existe): sin cambios funcionales.

Servicios (app/Services/):
- PlanLimites.php (nuevo).

Controladores:
- Negocio/MiPlan.php (nuevo).
- Admin/Pagos.php (nuevo).
- Auth/Login.php (modificar: agregar rol_plataforma a la sesión).
- Catalogo/Pedido.php (modificar: verificar límite de pedidos).

Filtros (app/Filters/):
- AdminFilter.php (nuevo).

Comandos (app/Commands/):
- CrearAdmin.php (nuevo).
- VencerSuscripciones.php (nuevo).

Vistas:
- app/Views/negocio/mi_plan.php (nuevo).
- app/Views/admin/pagos/index.php (nuevo).
- app/Views/admin/pagos/form.php (nuevo).
- app/Views/dashboard/index.php (modificar: agregar enlace "Mi Plan").
- app/Views/layouts/auth.php o layouts/panel.php (agregar enlace "Mi Plan" si hay menú).

Config:
- app/Config/Routes.php.
- app/Config/Filters.php (registrar AdminFilter con alias 'admin').

========================================
CONVENCIONES DE MODELOS
========================================
- PagoModel: $useTimestamps = true, created_at/modified_at, $useSoftDeletes = true, deleted_at.
- SuscripcionModel: ya tiene timestamps; NO incluir vigente_negocio_id en allowedFields.
- No incluir columnas generadas en allowedFields.

========================================
VISTAS
========================================
- Mi Plan: mobile-first. Tarjeta de plan actual con badge de estado. Barras de progreso para productos, usuarios y pedidos del mes (si el límite es NULL, mostrar "Ilimitado"). Tarjetas de planes superiores con precio y botón "Solicitar upgrade". Si hay pendiente_pago, banner destacado.
- Admin Pagos: listado de pendientes y pagos. Formulario de registro con fecha y periodo. Botones confirmar/rechazar.
- CSRF en formularios POST.
- Escape de salida con esc().
- Mensajes claros en español.

========================================
SEGURIDAD
========================================
- Validación server-side.
- CSRF en formularios POST.
- AdminFilter verifica rol_plataforma = 'administrador'.
- AuthFilter verifica sesión activa.
- Verificar pertenencia: suscripción.negocio_id === session.negocio_id antes de solicitar upgrade.
- No exponer SQL ni rutas internas en mensajes de error.
- No permitir upgrade a un plan inferior o igual.
- No permitir más de una solicitud pendiente por negocio.

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Comandos spark creados y cómo usarlos.
- Plan de prueba con los criterios de aceptación de HU-23, HU-24, HU-38, HU-39 y HU-40.
- No hagas commit.
