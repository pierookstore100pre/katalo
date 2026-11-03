Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe (20 tablas). Bloques 0, 1 y 2 implementados: registro, login, perfil del negocio y categorías propias funcionando.
Implementa SOLO el Bloque 3: productos e imágenes. No implementes catálogo público, pedidos, pagos ni admin.

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo la sección "Bloque 3 — Productos e imágenes" de docs/requerimientos.md.
Lee solo las secciones de docs/database-dictionary.md para: productos, imagenes_producto, categorias, planes, logs_actividad.
No leas docs/schema-v2.sql completo salvo que necesites un CHECK o ENUM.
No inventes columnas. No modifiques el esquema ni crees migraciones.
No incluyas imagenes_producto.principal_producto_id en allowedFields (es columna generada).

========================================
ALCANCE
========================================
RF: RF-08, RF-09, RF-10, RF-11, RF-13, RF-32.
HU: HU-06, HU-07, HU-08, HU-09, HU-11.

NO implementar: catálogo público (Bloque 4), carrito, pedidos, pagos, admin.

========================================
REGLAS DE NEGOCIO
========================================
- RN-06: precio ≥ 0.
- RN-07 / RN-28: respetar planes.max_productos y planes.max_imagenes_por_producto (NULL = ilimitado).
- RN-14: borrado lógico con deleted_at; no existe estado "eliminado".
- RN-16: solo propietario/colaborador del negocio puede crear/editar productos.
- RN-04: solo productos visibles y activos aparecerán en catálogo (Bloque 4); en este bloque solo gestionas el campo.
- RNF-10: toda consulta filtra por negocio_id de la sesión.

========================================
RNF APLICABLES
========================================
- RNF-05: CSRF y escape con esc().
- RNF-08: transacción al crear producto + registrar log (si es una sola tabla, transacción simple).
- RNF-10: aislamiento por negocio_id.
- RNF-18: Nivel 1 en productos e imagenes_producto; Nivel 3 en logs_actividad.

========================================
FLUJO DE PRODUCTOS
========================================
1. GET /negocio/productos: listado con todos los productos del negocio (visibles, ocultos y agotados). Muestra consumo del plan (X de N productos; N = NULL → "ilimitado"). Filtros: Todos / Visibles / Ocultos / Agotados. Acciones por fila: editar, ocultar/reactivar, marcar agotado, gestionar imágenes.
2. GET /negocio/productos/crear: formulario con:
   - Título (obligatorio, ≤ 200).
   - Categoría (select obligatorio, solo categorías activas del mismo negocio).
   - Precio referencial (obligatorio, ≥ 0).
   - Descripción (opcional, TEXT).
   - Estado: activo / pausado / agotado (default activo).
   - Visible (checkbox, default 1).
   - Destacado (checkbox, default 0).
3. POST /negocio/productos/crear:
   - Validar en servidor.
   - Verificar que la categoría pertenece al negocio (FK compuesta lo exige; maneja el error).
   - Verificar límite planes.max_productos; si se excede, error con mensaje que sugiere upgrade.
   - Insertar producto.
   - Registrar 'crear_producto' en logs_actividad.
   - Redirigir al listado con mensaje de éxito.
4. GET /negocio/productos/editar/{id}: formulario con datos actuales. Verificar que el producto pertenece al negocio de la sesión.
5. POST /negocio/productos/editar/{id}: actualizar con validaciones; registrar 'editar_producto'.
6. POST /negocio/productos/ocultar/{id}: visible = 0; registrar 'ocultar_producto'.
7. POST /negocio/productos/reactivar/{id}: visible = 1; registrar 'reactivar_producto'.
8. POST /negocio/productos/agotar/{id}: estado = 'agotado'; registrar 'agotar_producto'.
9. POST /negocio/productos/reactivar-estado/{id}: estado = 'activo'.

========================================
FLUJO DE IMÁGENES (RF-11 / HU-09)
========================================
1. GET /negocio/productos/{id}/imagenes: galería del producto con imágenes actuales, botón "Subir imagen", marca de principal, acción eliminar. Muestra el límite del plan (X de N imágenes).
2. POST /negocio/productos/{id}/imagenes:
   - Validar MIME real con finfo_file (JPG, PNG, WebP).
   - Tamaño ≤ 2 MB.
   - Verificar límite planes.max_imagenes_por_producto.
   - Guardar archivo en writable/uploads/productos/{negocio_id}/{id_producto}/ con nombre seguro (hash o UUID + extensión).
   - Insertar fila en imagenes_producto con ruta_imagen, nombre_archivo, es_principal (0 si ya hay principal, 1 si es la primera), orden.
   - Registrar 'subir_imagen' en logs_actividad.
3. POST /negocio/productos/{id}/imagenes/{id_imagen}/principal:
   - Desmarcar la principal actual del producto (es_principal = 0).
   - Marcar la nueva (es_principal = 1).
   - La BD garantiza una sola principal con la columna generada; maneja el error si algo falla.
4. POST /negocio/productos/{id}/imagenes/{id_imagen}/eliminar:
   - Borrado lógico (deleted_at).
   - Si era la principal, permitir que la siguiente sea principal o dejar sin principal (la columna generada permite NULL).

========================================
RUTAS (app/Config/Routes.php)
========================================
GET  /negocio/productos                                   → Negocio\Productos::index
GET  /negocio/productos/crear                             → Negocio\Productos::crear
POST /negocio/productos/crear                             → Negocio\Productos::guardar
GET  /negocio/productos/editar/{id}                       → Negocio\Productos::editar
POST /negocio/productos/editar/{id}                       → Negocio\Productos::actualizar
POST /negocio/productos/ocultar/{id}                      → Negocio\Productos::ocultar
POST /negocio/productos/reactivar/{id}                    → Negocio\Productos::reactivar
POST /negocio/productos/agotar/{id}                       → Negocio\Productos::agotar
POST /negocio/productos/reactivar-estado/{id}             → Negocio\Productos::reactivarEstado
GET  /negocio/productos/{id}/imagenes                     → Negocio\Imagenes::index
POST /negocio/productos/{id}/imagenes                     → Negocio\Imagenes::subir
POST /negocio/productos/{id}/imagenes/{id_imagen}/principal → Negocio\Imagenes::principal
POST /negocio/productos/{id}/imagenes/{id_imagen}/eliminar  → Negocio\Imagenes::eliminar

Todas protegidas por AuthFilter.

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Modelos (app/Models/):
- ProductoModel.php (nuevo): Nivel 1, soft delete, timestamps.
- ImagenProductoModel.php (nuevo): Nivel 1, soft delete, timestamps. No incluir principal_producto_id en allowedFields.
- CategoriaModel.php (ya existe): agregar método listarActivasPorNegocio($id_negocio).
- PlanModel.php (ya existe): agregar método obtenerVigentePorNegocio($id_negocio) que devuelva el plan de la suscripción vigente.

Controladores (app/Controllers/Negocio/):
- Productos.php
- Imagenes.php

Vistas (app/Views/negocio/productos/):
- index.php
- form.php
- imagenes.php

Config:
- app/Config/Routes.php (agregar rutas).
- app/Views/dashboard/index.php: agregar enlace "Mis Productos" al menú.

========================================
CONVENCIONES DE MODELOS
========================================
- ProductoModel: $useTimestamps = true, $createdField = 'created_at', $updatedField = 'modified_at', $useSoftDeletes = true, $deletedField = 'deleted_at'.
- ImagenProductoModel: igual, pero sin principal_producto_id en allowedFields.
- Timestamps automáticos en PHP: complementan los de la BD; no los desactives.

========================================
UPLOADS
========================================
- Guarda archivos en writable/uploads/productos/{negocio_id}/{id_producto}/.
- Verifica que la carpeta exista y sea escribible; créala si no existe.
- Nombre de archivo: hash('sha256', time() . random_bytes(8)) . '.' . $ext.
- No uses el nombre original del archivo del usuario.
- Si más adelante el catálogo público necesita mostrar imágenes, se servirá a través de un controlador que lea desde writable/uploads (no exponer writable directamente).

========================================
VISTAS
========================================
- Mobile-first, HTML5, CSS3, JS vanilla.
- Listado: tabla o cards; en móvil, cards. Muestra título, categoría, precio, estado, visible, imagen principal (miniatura).
- Formulario: campos del flujo; botón guardar fijo abajo.
- Galería: thumbnails con indicador de principal; botón subir; acciones.
- CSRF en todos los formularios.
- Mensajes de error claros en español.

========================================
SEGURIDAD
========================================
- Validación server-side.
- Verificar pertenencia: producto.negocio_id === session.negocio_id, categoría.negocio_id === session.negocio_id.
- Validar MIME real (finfo_file), no solo extensión.
- Tamaño máximo 2 MB.
- Nombre seguro de archivo (hash/UUID).
- No exponer rutas internas ni SQL en mensajes de error.

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Plan de prueba con los criterios de aceptación de HU-06, HU-07, HU-08, HU-09 y HU-11.
- No hagas commit.
