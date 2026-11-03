Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe (20 tablas). Bloque 1 ya está implementado y funcionando (registro, login, logout, recuperación).
Implementa SOLO el Bloque 2: perfil del negocio y categorías. No implementes productos, catálogo público, pedidos, pagos ni admin.

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo la sección "Bloque 2 — Perfil del negocio y categorías" de docs/requerimientos.md.
Lee solo las secciones de docs/database-dictionary.md para estas tablas: negocios, distritos, ciudades, categorias, logs_actividad.
No leas docs/schema-v2.sql completo salvo que necesites un CHECK o ENUM que no esté en el diccionario.
No inventes columnas ni tablas. No modifiques el esquema ni crees migraciones.
No incluyas columnas generadas en allowedFields.

========================================
ALCANCE
========================================
Requerimientos a implementar: RF-07, RF-12.
Historias a cumplir: HU-05, HU-10.

NO implementar:
- Productos, imágenes, catálogo público, pedidos, pagos, panel admin.
- No modifiques los archivos del Bloque 1 (Auth, Login, Registro, Logout, Dashboard) salvo que necesites enlazar el menú del dashboard a las nuevas vistas.

========================================
REGLAS DE NEGOCIO
========================================
- RN-02: slug único global en negocios.
- RN-27: el slug de un negocio eliminado no se reutiliza.
- RN-16: solo el propietario o colaborador autorizado del negocio puede editar su perfil y sus categorías.
- RNF-10: toda consulta filtra por negocio_id (obtenido de la sesión).
- RN-04/RN-14: las categorías inactivas no se muestran en el catálogo (aunque el catálogo público se implementa en el Bloque 4, deja el campo activo listo).

========================================
RNF APLICABLES
========================================
- RNF-05: CSRF en formularios y escape de salida con esc().
- RNF-08: el guardado de perfil y la creación de categorías usan transacciones si afectan más de una tabla (en este bloque, el perfil solo toca negocios; las categorías solo tocan categorias, así que transacción simple).
- RNF-10: aislamiento por negocio_id.
- RNF-18: Nivel 1 en negocios y categorias; Nivel 2 en usuario_roles y negocio_usuarios (no se tocan aquí); Nivel 3 en logs_actividad.

========================================
FLUJO DE PERFIL (RF-07 / HU-05)
========================================
1. Ruta GET /negocio/perfil: cargar los datos actuales del negocio desde la sesión (id_negocio). Si no hay sesión, redirigir al login.
2. Formulario con:
   - Nombre comercial (obligatorio, ≤ 150).
   - Slug (obligatorio, minúsculas, números y guiones, único global, no reutilizar eliminados).
   - Descripción (opcional, TEXT).
   - Dirección (obligatoria, ≤ 255).
   - Distrito (select obligatorio, activo, ciudad activa).
   - Teléfono/WhatsApp (obligatorio, solo dígitos, 7–15).
   - Horario en texto (opcional, ≤ 255).
3. Al guardar (POST /negocio/perfil):
   - Validar en servidor.
   - Verificar que el slug no exista en otro negocio (incluyendo eliminados lógicamente).
   - Actualizar la fila de negocios con modified_by = id_usuario de la sesión.
   - Registrar 'editar_perfil' en logs_actividad.
   - Redirigir con mensaje de éxito.

========================================
FLUJO DE CATEGORÍAS (RF-12 / HU-10)
========================================
1. Ruta GET /negocio/categorias: listar todas las categorías del negocio (activas e inactivas).
2. Ruta GET /negocio/categorias/crear: formulario de creación.
3. Ruta POST /negocio/categorias/crear: validar y guardar:
   - Nombre (obligatorio, ≤ 100).
   - Slug (obligatorio, único por negocio, minúsculas y guiones).
   - Descripción (opcional, ≤ 255).
   - Categoría padre (opcional, debe ser del mismo negocio y no puede ser ella misma).
   - Activo (checkbox, por defecto 1).
   - Registrar 'crear_categoria' en logs_actividad.
4. Ruta GET /negocio/categorias/editar/{id}: formulario con datos actuales. Verificar que la categoría pertenece al negocio de la sesión.
5. Ruta POST /negocio/categorias/editar/{id}: actualizar con validaciones; registrar 'editar_categoria'.
6. Ruta GET /negocio/categorias/eliminar/{id}: borrado lógico (deleted_at) o cambio a activo = 0. Preferir borrado lógico si no hay productos asociados; si hay productos, solo permitir desactivar. Registrar 'eliminar_categoria'.
7. El listado debe mostrar jerarquía (padre → hijos) y el estado (activo/inactivo).

========================================
RUTAS (app/Config/Routes.php)
========================================
GET  /negocio/perfil              → Negocio\Perfil::index
POST /negocio/perfil              → Negocio\Perfil::guardar
GET  /negocio/categorias          → Negocio\Categorias::index
GET  /negocio/categorias/crear    → Negocio\Categorias::crear
POST /negocio/categorias/crear    → Negocio\Categorias::guardar
GET  /negocio/categorias/editar/{id} → Negocio\Categorias::editar
POST /negocio/categorias/editar/{id} → Negocio\Categorias::actualizar
GET  /negocio/categorias/eliminar/{id} → Negocio\Categorias::eliminar

Todas estas rutas deben estar protegidas por AuthFilter.

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Modelos (app/Models/):
- NegocioModel.php (ya existe; agregar métodos para actualizar perfil y verificar slug único incluyendo eliminados).
- CategoriaModel.php (nuevo).
- DistritoModel.php (ya existe; agregar método para listar distritos activos de ciudades activas).

Controladores (app/Controllers/Negocio/):
- Perfil.php
- Categorias.php

Vistas (app/Views/negocio/):
- perfil.php
- categorias/index.php
- categorias/form.php (crear y editar)

Layout:
- Reutiliza layouts/auth.php o crea layouts/panel.php si necesitas menú lateral. No dupliques layout.

Config:
- app/Config/Routes.php (agregar las rutas del bloque).
- Menú del dashboard: agregar enlaces a "Mi Perfil" y "Mis Categorías" en app/Views/dashboard/index.php (solo enlaces, no cambiar la lógica del Bloque 1).

========================================
CONVENCIONES DE MODELOS
========================================
- NegocioModel y CategoriaModel: Nivel 1, $useTimestamps = true con created_at y modified_at, $useSoftDeletes = true con deleted_at.
- No incluir columnas generadas en allowedFields.
- En CategoriaModel, el slug debe validarse único por negocio_id. La tabla tiene UNIQUE (negocio_id, slug), así que la BD ya lo protege; maneja el error de duplicado con un mensaje claro.

========================================
VISTAS
========================================
- Mobile-first, HTML5, CSS3 y JS vanilla.
- Perfil: formulario con campos actuales; select de distrito cargado desde distritos activos de ciudades activas; botón guardar fijo abajo.
- Categorías: listado con jerarquía; botón "Nueva categoría"; en cada fila, acciones editar/activar/desactivar/eliminar.
- Formulario de categoría: nombre, slug, descripción, categoría padre (select de categorías del mismo negocio), activo.
- Incluye csrf_field() en todos los formularios.
- Mensajes de error claros en español.

========================================
SEGURIDAD
========================================
- Validación server-side.
- CSRF en formularios.
- Escape de salida con esc().
- Verificar que el negocio_id de la sesión sea el dueño de la categoría antes de editar o eliminar.
- No exponer SQL ni rutas internas en mensajes de error.

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Plan de prueba manual en localhost con los criterios de aceptación de HU-05 y HU-10.
- No hagas commit; lo hago yo después de probar.
