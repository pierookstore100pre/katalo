Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe (20 tablas). Bloques 0 a 6 y 4.5 implementados: auth, perfil, categorías, productos, imágenes, catálogo por ciudad, carrito, pedidos, suscripciones y pagos manuales funcionando.
Implementa SOLO el Bloque 7: panel admin multi-ciudad con moderación posterior (Opción C). No implementes colaboradores, dominio propio ni Analytics (Post-MVP).

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo la sección "Bloque 7 — Panel admin multi-ciudad" de docs/requerimientos.md.
Lee solo las secciones de docs/database-dictionary.md para: ciudades, distritos, negocios, planes, suscripciones, pagos, pedidos, productos, usuarios, roles, usuario_roles, logs_actividad, configuracion.
No leas docs/schema-v2.sql completo salvo que necesites un CHECK o ENUM.
No modifiques el esquema ni crees migraciones.
No toques los Bloques 1, 2, 3, 4, 4.5, 5 ni 6 salvo los enlaces del dashboard y la redirección del admin.

========================================
ALCANCE Y DECISIÓN DE NEGOCIO (OPCIÓN C)
========================================
El registro es inmediato (freemium). El administrador NO aprueba antes; modera después:
- Puede suspender un negocio en cualquier momento (negocios.activo = 0). Al suspenderse, su catálogo deja de ser público automáticamente porque v_negocios_publicos exige activo = 1.
- Puede reactivar un negocio suspendido (negocios.activo = 1).
- Puede activar/desactivar ciudades (ciudades.activo). Al desactivar una ciudad, sus negocios dejan de aparecer públicamente.
- Puede ver y filtrar el registro de actividad para auditar.
- Puede gestionar distritos por ciudad y planes.

RF: RF-25, RF-26, RF-27, RF-28.
HU: HU-25, HU-26, HU-27, HU-28, HU-29, HU-43, HU-44.

NO implementar:
- Colaboradores (HU-37), dominio propio (HU-41), Analytics (HU-42): Post-MVP.
- Pasarela de pago: los pagos son manuales (ya en Bloque 6).
- Eliminación física de negocios ni de distritos.

========================================
REGLAS DE NEGOCIO
========================================
- RN-11: un negocio suspendido (activo = 0) no aparece públicamente.
- RN-12: un negocio solo opera si su ciudad está activa.
- RN-21: el catálogo público exige negocio activo, ciudad activa y suscripción vigente.
- RN-25: cambiar el precio de un plan no altera suscripciones existentes (precio_pactado).
- RN-27: el slug de un negocio eliminado no se reutiliza (no aplica aquí, no hay eliminación física).
- RNF-10: los negocios solo ven sus datos; el admin ve todo.
- RNF-18: Nivel 1 en ciudades, distritos, negocios, planes; Nivel 3 en logs_actividad.

========================================
DASHBOARD DEL ADMIN
========================================
Crear un panel de administración separado del panel del negocio.

1. Modificar app/Controllers/Dashboard.php:
   - Si session('rol_plataforma') === 'administrador', redirigir a /admin/dashboard.
   - Si no, comportamiento actual (panel del negocio).

2. Crear app/Controllers/Admin/Dashboard.php:
   - GET /admin/dashboard (AuthFilter + AdminFilter).
   - Mostrar métricas globales:
     - Total negocios activos (negocios.activo = 1, deleted_at IS NULL).
     - Total negocios suspendidos.
     - Total productos visibles y activos.
     - Total pedidos del mes actual.
     - MRR actual: SUM(precio_pactado) de suscripciones activas no eliminadas.
   - Mostrar tabla resumen por ciudad: ciudad, negocios activos, productos, pedidos del mes, MRR.
   - Enlaces rápidos a Ciudades, Distritos, Negocios, Planes, Pagos, Logs.

3. Crear app/Views/layouts/admin.php:
   - Layout propio del admin con menú lateral o superior: Dashboard, Ciudades, Distritos, Negocios, Planes, Pagos, Registro de actividad.
   - Color/branding distinto al panel del negocio (por ejemplo, fondo oscuro).
   - Logout.

4. Crear app/Views/admin/dashboard.php con las métricas.

========================================
FLUJO: CIUDADES (RF-26 / HU-26)
========================================
GET  /admin/ciudades                      → Admin\Ciudades::index
POST /admin/ciudades/{id}/activar         → Admin\Ciudades::activar
POST /admin/ciudades/{id}/desactivar      → Admin\Ciudades::desactivar

index:
- Listar ciudades con: nombre, slug, region, activo, fecha_lanzamiento, cantidad de negocios activos.
- Filtro por estado (activas/inactivas/todas).

activar/desactivar:
- Cambiar ciudades.activo.
- Registrar 'activar_ciudad' / 'desactivar_ciudad' en logs_actividad.
- Al desactivar, los negocios de esa ciudad dejan de aparecer en el catálogo público (ya lo hace v_negocios_publicos).
- Redirigir con mensaje.

========================================
FLUJO: DISTRITOS (RF-26 complemento / HU-43)
========================================
GET  /admin/distritos                     → Admin\Distritos::index
GET  /admin/distritos/crear               → Admin\Distritos::crear
POST /admin/distritos/crear               → Admin\Distritos::guardar
GET  /admin/distritos/editar/{id}         → Admin\Distritos::editar
POST /admin/distritos/editar/{id}         → Admin\Distritos::actualizar
POST /admin/distritos/{id}/activar        → Admin\Distritos::activar
POST /admin/distritos/{id}/desactivar     → Admin\Distritos::desactivar

index:
- Listar distritos agrupados por ciudad.
- Mostrar cantidad de negocios por distrito.
- Filtro por ciudad y estado.

crear/editar:
- Campos: ciudad (select obligatorio), nombre (obligatorio, único por ciudad), descripcion (opcional), activo (checkbox).
- Validar unicidad de (ciudad_id, nombre). Manejar el error de la BD con mensaje claro.

activar/desactivar:
- Cambiar distritos.activo.
- Desactivar un distrito no oculta sus negocios existentes (la vista no filtra por distritos.activo), pero impide nuevas asignaciones en el registro y en el perfil. Documéntalo claramente en la vista.
- No permitir eliminación física. No hay botón eliminar.

========================================
FLUJO: NEGOCIOS (RF-27 / HU-28, Opción C)
========================================
GET  /admin/negocios                      → Admin\Negocios::index
GET  /admin/negocios/{id}                 → Admin\Negocios::ver
POST /admin/negocios/{id}/suspender       → Admin\Negocios::suspender
POST /admin/negocios/{id}/reactivar       → Admin\Negocios::reactivar

index:
- Listar negocios con: id, nombre_comercial, slug, ciudad, distrito, propietario (JOIN usuarios/personas), plan vigente, estado (activo/suspendido), cantidad de productos, cantidad de pedidos, fecha de registro.
- Filtros: por ciudad, por estado, búsqueda por nombre o slug.
- Paginación básica (20 por página).

ver:
- Datos del negocio (nombre, slug, dirección, teléfono, distrito, ciudad).
- Suscripción vigente y última suscripción.
- Cantidad de productos (visibles/ocultos/agotados).
- Cantidad de pedidos (total y del mes).
- Últimos 10 pedidos.
- Botón Suspender / Reactivar.

suspender:
- Verificar que el negocio existe.
- Cambiar negocios.activo = 0.
- Registrar 'suspender_negocio' en logs_actividad con descripcion = "nombre_comercial (slug)".
- Redirigir con mensaje.
- Efecto: el catálogo público desaparece automáticamente (v_negocios_publicos exige activo = 1).

reactivar:
- Cambiar negocios.activo = 1.
- Registrar 'reactivar_negocio' en logs_actividad.
- Redirigir con mensaje.

Restricción: el admin no puede eliminar negocios (no implementar DELETE).

========================================
FLUJO: PLANES (RF-25 / HU-25)
========================================
GET  /admin/planes                        → Admin\Planes::index
GET  /admin/planes/editar/{id}            → Admin\Planes::editar
POST /admin/planes/editar/{id}            → Admin\Planes::actualizar
POST /admin/planes/{id}/activar           → Admin\Planes::activar
POST /admin/planes/{id}/desactivar        → Admin\Planes::desactivar

index:
- Listar planes con: nombre, slug, precio_mensual, límites (max_productos, max_imagenes_por_producto, max_usuarios, max_pedidos_mes), features, activo, cantidad de suscripciones activas.

editar/actualizar:
- Editar: precio_mensual, max_productos, max_imagenes_por_producto, max_usuarios, max_pedidos_mes, tiene_dominio_propio, tiene_analytics, tiene_reportes, descripcion.
- NULL en límites = ilimitado. El formulario debe permitir dejar el campo vacío para representar NULL.
- No permitir editar slug ni nombre (evita romper referencias).
- Al cambiar precio_mensual, NO modificar precio_pactado de suscripciones existentes (RN-25).
- Registrar 'editar_plan' en logs_actividad.

activar/desactivar:
- Cambiar planes.activo.
- Desactivar un plan no afecta suscripciones existentes; solo impide que se ofrezca en upgrades.
- No permitir eliminar planes.

========================================
FLUJO: REGISTRO DE ACTIVIDAD (RF-33 lectura / HU-44)
========================================
GET /admin/logs                           → Admin\Logs::index

index:
- Listar logs_actividad ordenados por created_at DESC.
- Filtros: rango de fechas, accion (select con valores conocidos), negocio_id, usuario_id.
- Paginación básica (50 por página).
- Mostrar: fecha, usuario (email o "sistema"), negocio (nombre_comercial o "—"), accion, tabla_afectada, descripcion, ip_address.
- No permitir editar ni eliminar logs.

El panel del negocio (no admin) debe tener una vista similar pero solo con sus propios logs:
GET /negocio/logs → Negocio\Logs::index (AuthFilter)
- Filtra por negocio_id = session.negocio_id.
- Solo muestra acciones del negocio y de sus usuarios.

========================================
MÉTRICAS Y CONSULTAS
========================================
MRR global:
SELECT COALESCE(SUM(s.precio_pactado), 0)
FROM suscripciones s
WHERE s.estado = 'activa' AND s.deleted_at IS NULL;

MRR por ciudad:
SELECT c.id_ciudad, c.nombre, COALESCE(SUM(s.precio_pactado), 0) AS mrr
FROM ciudades c
LEFT JOIN distritos d ON d.ciudad_id = c.id_ciudad AND d.deleted_at IS NULL
LEFT JOIN negocios n ON n.distrito_id = d.id_distrito AND n.deleted_at IS NULL
LEFT JOIN suscripciones s ON s.negocio_id = n.id_negocio AND s.estado = 'activa' AND s.deleted_at IS NULL
GROUP BY c.id_ciudad, c.nombre;

Pedidos del mes por ciudad:
SELECT c.id_ciudad, COUNT(p.id_pedido) AS pedidos_mes
FROM ciudades c
LEFT JOIN distritos d ON d.ciudad_id = c.id_ciudad AND d.deleted_at IS NULL
LEFT JOIN negocios n ON n.distrito_id = d.id_distrito AND n.deleted_at IS NULL
LEFT JOIN pedidos p ON p.negocio_id = n.id_negocio
  AND p.deleted_at IS NULL
  AND YEAR(p.created_at) = YEAR(CURDATE())
  AND MONTH(p.created_at) = MONTH(CURDATE())
GROUP BY c.id_ciudad;

Usa estas consultas como base y ajústalas según los nombres exactos del diccionario.

========================================
RUTAS (app/Config/Routes.php)
========================================
Todas bajo /admin, protegidas por AuthFilter y AdminFilter (alias 'admin'):

GET  /admin/dashboard                       → Admin\Dashboard::index
GET  /admin/ciudades                        → Admin\Ciudades::index
POST /admin/ciudades/{id}/activar           → Admin\Ciudades::activar
POST /admin/ciudades/{id}/desactivar        → Admin\Ciudades::desactivar
GET  /admin/distritos                       → Admin\Distritos::index
GET  /admin/distritos/crear                 → Admin\Distritos::crear
POST /admin/distritos/crear                 → Admin\Distritos::guardar
GET  /admin/distritos/editar/{id}           → Admin\Distritos::editar
POST /admin/distritos/editar/{id}           → Admin\Distritos::actualizar
POST /admin/distritos/{id}/activar          → Admin\Distritos::activar
POST /admin/distritos/{id}/desactivar       → Admin\Distritos::desactivar
GET  /admin/negocios                        → Admin\Negocios::index
GET  /admin/negocios/{id}                   → Admin\Negocios::ver
POST /admin/negocios/{id}/suspender         → Admin\Negocios::suspender
POST /admin/negocios/{id}/reactivar         → Admin\Negocios::reactivar
GET  /admin/planes                          → Admin\Planes::index
GET  /admin/planes/editar/{id}              → Admin\Planes::editar
POST /admin/planes/editar/{id}              → Admin\Planes::actualizar
POST /admin/planes/{id}/activar             → Admin\Planes::activar
POST /admin/planes/{id}/desactivar          → Admin\Planes::desactivar
GET  /admin/logs                            → Admin\Logs::index

Ruta del negocio:
GET  /negocio/logs                          → Negocio\Logs::index (AuthFilter)

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Controladores:
- Admin/Dashboard.php (nuevo).
- Admin/Ciudades.php (nuevo).
- Admin/Distritos.php (nuevo).
- Admin/Negocios.php (nuevo).
- Admin/Planes.php (nuevo).
- Admin/Logs.php (nuevo).
- Negocio/Logs.php (nuevo).
- Dashboard.php (modificar: redirigir admin a /admin/dashboard).

Modelos:
- CiudadModel.php (nuevo si no existe): Nivel 1, soft delete.
- DistritoModel.php (ya existe): agregar métodos listarConNegocios, contarNegocios.
- NegocioModel.php (ya existe): agregar listarConFiltros, contarActivos, contarSuspendidos.
- PlanModel.php (ya existe): agregar listarConSuscripciones.
- LogActividadModel.php (ya existe): agregar listarConFiltros, listarPorNegocio.
- UsuarioModel.php (ya existe): agregar obtenerEmailPorId.

Vistas:
- layouts/admin.php (nuevo).
- admin/dashboard.php (nuevo).
- admin/ciudades/index.php (nuevo).
- admin/distritos/index.php (nuevo).
- admin/distritos/form.php (nuevo).
- admin/negocios/index.php (nuevo).
- admin/negocios/detalle.php (nuevo).
- admin/planes/index.php (nuevo).
- admin/planes/form.php (nuevo).
- admin/logs/index.php (nuevo).
- negocio/logs/index.php (nuevo).
- dashboard/index.php (modificar: si es admin, no aplica porque se redirige antes; si es negocio, agregar enlace "Registro de actividad").

Config:
- app/Config/Routes.php (agregar rutas).
- app/Config/Filters.php (verificar que AdminFilter ya está registrado con alias 'admin'; si no, registrarlo).

========================================
VISTAS
========================================
- Layout admin: menú lateral en desktop, menú colapsable en móvil. Color oscuro para distinguir del panel del negocio.
- Todas las tablas: responsive (cards en móvil, tabla en desktop).
- Badges de estado: activo (verde), suspendido (rojo), inactivo (gris).
- CSRF en formularios POST.
- Escape de salida con esc().
- Mensajes claros en español.
- Filtros visibles y botón "Limpiar".

========================================
SEGURIDAD
========================================
- Todas las rutas /admin/* protegidas por AuthFilter + AdminFilter.
- AdminFilter: verifica session('rol_plataforma') === 'administrador'.
- Si no es admin, redirigir a /dashboard con mensaje de acceso denegado.
- Validación server-side en todos los formularios.
- CSRF en formularios POST.
- Escape de salida con esc().
- No exponer SQL ni rutas internas.
- Registrar todas las acciones administrativas en logs_actividad.

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Plan de prueba con los criterios de aceptación de HU-25, HU-26, HU-27, HU-28, HU-29, HU-43 y HU-44.
- Confirmación de que /auth, /negocio, /dashboard, /{ciudad}/{slug} siguen funcionando.
- No hagas commit.
