Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe (20 tablas). Bloques 0 a 7 implementados, incluido panel admin con suspensión de negocios.
Implementa SOLO el Bloque 7.5: moderación de contenido. No modifiques el esquema ni crees migraciones.

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo las secciones de docs/database-dictionary.md para: productos, imagenes_producto, negocios, categorias, logs_actividad, usuarios, roles, usuario_roles.
No leas docs/schema-v2.sql completo.
No modifiques el esquema. No agregues columnas. No crees migraciones.
Usa solo columnas existentes: productos.visible, productos.estado, productos.deleted_at, imagenes_producto.deleted_at, negocios.activo, categorias.activo, logs_actividad.

========================================
ALCANCE
========================================
Objetivo: dar al administrador herramientas para detectar y gestionar contenido inapropiado sin aprobación previa (Opción C: registro inmediato, moderación posterior).

Componentes:
1. Servicio de filtro de texto con lista negra configurable.
2. Detección automática al guardar productos, categorías y perfiles de negocio.
3. Panel /admin/moderacion con:
   - Productos marcados por el filtro (revisión pendiente).
   - Imágenes recientes para revisión manual.
   - Acciones: aprobar, rechazar, suspender negocio.
4. Registro de todas las acciones en logs_actividad.

NO implementar:
- API externa de moderación de imágenes (AWS Rekognition, Cloudinary, etc.). La revisión de imágenes es manual por el admin en este bloque.
- Aprobación previa de negocios (Opción A descartada).
- Colaboradores, dominio propio, analytics (Post-MVP).

========================================
FILTRO DE TEXTO
========================================
Crear app/Config/Moderation.php:
- Propiedad $palabrasBloqueadas = ['insulto1', 'insulto2', 'spam', 'xxx', ...] con al menos 20 términos de ejemplo.
- Propiedad $camposARevisar = ['titulo', 'descripcion', 'nombre_comercial', 'nombre'].
- Documentar que la lista es editable y que en producción se puede cargar desde configuracion o un archivo externo.

Crear app/Services/ModerationService.php:
- Método contieneContenidoBloqueado(string $texto): bool.
  - Normalizar a minúsculas y quitar tildes.
  - Buscar cada palabra de la lista negra con límites de palabra (evitar falsos positivos).
  - Devolver true si encuentra al menos una.
- Método evaluarCampos(array $datos): array.
  - Recorre los campos indicados y devuelve ['bloqueado' => bool, 'campos' => ['titulo', 'descripcion']].
- Método registrarMarcado(string $tabla, int $registroId, array $campos, ?int $negocioId, ?int $usuarioId): void.
  - Inserta en logs_actividad: accion = 'contenido_marcado', tabla_afectada, registro_id, descripcion con los campos afectados, negocio_id, usuario_id, ip_address, user_agent.

========================================
INTEGRACIÓN EN PRODUCTOS
========================================
Modificar app/Controllers/Negocio/Productos.php:
- Antes de guardar (crear o editar), llamar a ModerationService::evaluarCampos con titulo y descripcion.
- Si bloqueado:
  - Guardar el producto igual, pero con visible = 0 (si es creación) o mantener visible = 0 (si es edición y ya estaba visible, forzar a 0).
  - Llamar a ModerationService::registrarMarcado.
  - Mostrar mensaje al usuario: "Tu producto ha sido guardado, pero será revisado por un administrador antes de publicarse."
- Si no bloqueado: comportamiento actual.
- No cambiar la lógica de límites de plan ni de imágenes.

========================================
INTEGRACIÓN EN CATEGORÍAS
========================================
Modificar app/Controllers/Negocio/Categorias.php:
- Antes de guardar, evaluar nombre y descripcion.
- Si bloqueado: guardar con activo = 0, registrar marcado, mensaje al usuario.
- No cambiar el resto.

========================================
INTEGRACIÓN EN PERFIL DEL NEGOCIO
========================================
Modificar app/Controllers/Negocio/Perfil.php:
- Antes de guardar, evaluar nombre_comercial y descripcion.
- Si bloqueado: guardar los cambios pero con activo = 0 (el catálogo deja de ser público), registrar marcado, mensaje al usuario: "Tu perfil ha sido actualizado, pero será revisado antes de publicarse."
- Permitir que el admin reactive desde el panel de negocios (ya existe).

========================================
PANEL DE MODERACIÓN
========================================
Crear app/Controllers/Admin/Moderacion.php:
- GET /admin/moderacion (AuthFilter + AdminFilter).
- Cargar:
  - Productos marcados: productos con visible = 0 que tengan un log reciente (últimos 30 días) con accion = 'contenido_marcado' y tabla_afectada = 'productos'. JOIN con negocios para mostrar nombre_comercial. Ordenar por fecha de log DESC. Limitar a 50.
  - Categorías marcadas: similar con tabla_afectada = 'categorias' y categorias.activo = 0.
  - Negocios marcados: similar con tabla_afectada = 'negocios' y negocios.activo = 0.
  - Imágenes recientes: imagenes_producto con deleted_at IS NULL, creadas en los últimos 7 días, JOIN con productos y negocios. Mostrar thumbnail vía /c/imagen/{id}. Limitar a 50.
- Para cada producto marcado: botones "Aprobar" (visible = 1) y "Rechazar" (mantener visible = 0 y agregar log 'contenido_rechazado'). Ambos con POST y CSRF.
- Para cada categoría marcada: botones "Aprobar" (activo = 1) y "Rechazar" (mantener activo = 0).
- Para cada negocio marcado: botones "Aprobar" (activo = 1) y "Suspender" (mantener activo = 0, ya existe desde Bloque 7).
- Para cada imagen reciente: botones "Aprobar" (solo registrar log 'imagen_aprobada') y "Rechazar" (soft delete: deleted_at = NOW(), registrar 'imagen_rechazada').

Métodos POST:
- POST /admin/moderacion/producto/{id}/aprobar → visible = 1, log 'contenido_aprobado'.
- POST /admin/moderacion/producto/{id}/rechazar → mantener visible = 0, log 'contenido_rechazado'.
- POST /admin/moderacion/categoria/{id}/aprobar → activo = 1, log 'contenido_aprobado'.
- POST /admin/moderacion/categoria/{id}/rechazar → mantener activo = 0, log 'contenido_rechazado'.
- POST /admin/moderacion/negocio/{id}/aprobar → activo = 1, log 'negocio_aprobado'.
- POST /admin/moderacion/imagen/{id}/aprobar → log 'imagen_aprobada'.
- POST /admin/moderacion/imagen/{id}/rechazar → deleted_at = NOW(), log 'imagen_rechazada'.

========================================
RUTAS (app/Config/Routes.php)
========================================
Dentro del grupo admin existente, agregar:

GET  /admin/moderacion                              → Admin\Moderacion::index
POST /admin/moderacion/producto/(:num)/aprobar      → Admin\Moderacion::aprobarProducto/$1
POST /admin/moderacion/producto/(:num)/rechazar     → Admin\Moderacion::rechazarProducto/$1
POST /admin/moderacion/categoria/(:num)/aprobar     → Admin\Moderacion::aprobarCategoria/$1
POST /admin/moderacion/categoria/(:num)/rechazar    → Admin\Moderacion::rechazarCategoria/$1
POST /admin/moderacion/negocio/(:num)/aprobar       → Admin\Moderacion::aprobarNegocio/$1
POST /admin/moderacion/imagen/(:num)/aprobar        → Admin\Moderacion::aprobarImagen/$1
POST /admin/moderacion/imagen/(:num)/rechazar       → Admin\Moderacion::rechazarImagen/$1

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Config:
- app/Config/Moderation.php (nuevo).
- app/Config/Routes.php (agregar rutas dentro del grupo admin existente).

Servicios:
- app/Services/ModerationService.php (nuevo).

Controladores:
- app/Controllers/Admin/Moderacion.php (nuevo).
- app/Controllers/Negocio/Productos.php (modificar: evaluar antes de guardar).
- app/Controllers/Negocio/Categorias.php (modificar: evaluar antes de guardar).
- app/Controllers/Negocio/Perfil.php (modificar: evaluar antes de guardar).

Modelos:
- LogActividadModel.php (ya existe): agregar método obtenerMarcadosPorTabla($tabla, $dias = 30).
- ImagenProductoModel.php (ya existe): agregar método listarRecientes($dias = 7).

Vistas:
- app/Views/admin/moderacion/index.php (nuevo).
- app/Views/layouts/admin.php (modificar: agregar enlace "Moderación" al menú).

========================================
VISTA DE MODERACIÓN
========================================
- Layout admin (extend layouts/admin, misma sección que las demás vistas admin).
- Cuatro secciones con tabs o acordeón:
  1. Productos marcados.
  2. Categorías marcadas.
  3. Negocios marcados.
  4. Imágenes recientes.
- Cada ítem muestra: thumbnail o título, negocio, fecha del log, motivo (campos afectados).
- Botones Aprobar / Rechazar con confirmación.
- Badge de color: pendiente (amarillo), aprobado (verde), rechazado (rojo).
- Si no hay ítems en una sección, mensaje "No hay contenido pendiente de revisión."
- CSRF en todos los formularios POST.
- Escape de salida con esc().

========================================
SEGURIDAD
========================================
- Todas las rutas /admin/moderacion protegidas por AuthFilter + AdminFilter.
- CSRF en formularios POST.
- Validación server-side de ids.
- Registrar todas las acciones en logs_actividad.
- No exponer SQL ni rutas internas.

========================================
PRUEBAS MANUALES ESPERADAS
========================================
1. Como negocio, crear un producto con título "producto spam" (incluir una palabra de la lista negra).
2. El producto se guarda con visible = 0; el usuario ve mensaje "será revisado".
3. En logs_actividad aparece 'contenido_marcado'.
4. Como admin, abrir /admin/moderacion → ver el producto marcado.
5. Clic en "Aprobar" → visible = 1; log 'contenido_aprobado'; el producto aparece en el catálogo público.
6. Crear otro producto con palabra bloqueada; en el panel admin, clic en "Rechazar" → visible sigue 0; log 'contenido_rechazado'.
7. Repetir con categoría y con perfil de negocio.
8. Subir una imagen; como admin, en /admin/moderacion ver la imagen reciente.
9. Clic en "Rechazar" imagen → deleted_at se llena; la imagen desaparece del catálogo.
10. Verificar que el producto aprobado se ve en /{ciudad}/{slug}.
11. Verificar que las rutas /admin/ciudades, /admin/negocios, /admin/pagos siguen funcionando.
12. Verificar que el panel del negocio no cambió su comportamiento para contenido no bloqueado.

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Plan de prueba con los 12 casos de arriba.
- No hagas commit.
