Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe (20 tablas). Bloques 0 a 9.1 implementados, incluido panel admin, moderación y configuración global.
Implementa SOLO el Bloque 7.6: impersonación de negocios desde el panel del administrador.
No modifiques el esquema ni crees migraciones.

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo las secciones de docs/database-dictionary.md para: negocios, usuarios, personas, negocio_usuarios, roles, usuario_roles, logs_actividad.
No leas docs/schema-v2.sql completo.
No modifiques el esquema. No agregues columnas. No crees migraciones.
No toques los Bloques 1, 2, 3, 4, 4.5, 5, 6, 7, 7.5, 9 ni 9.1 salvo los archivos indicados en este prompt.

========================================
ALCANCE
========================================
Objetivo: que el administrador pueda entrar al panel de un negocio como si fuera su propietario, sin cerrar su sesión de administrador, y salir de la impersonación para volver a su panel admin con la sesión intacta.

Es la solución profesional para que el admin pruebe la experiencia del negocio y dé soporte sin compartir credenciales ni cerrar sesión. No cambia el esquema ni los roles existentes.

NO implementar:
- Separar cookies de sesión por rol (Opción 2 descartada).
- Múltiples sesiones simultáneas del mismo admin en distintos negocios.
- Impersonar a otro administrador.
- Modificar los roles de plataforma en la BD.

========================================
REGLAS
========================================
- Solo un usuario con rol_plataforma = 'administrador' puede iniciar impersonación.
- Mientras impersona, la sesión activa es la del negocio objetivo: rol_plataforma = 'usuario_negocio', id_negocio = negocio objetivo, rol_negocio = 'propietario'.
- Mientras impersona, el admin NO debe poder acceder a /admin/*. El AdminFilter ya lo bloquea porque rol_plataforma deja de ser 'administrador'; no modifiques AdminFilter.
- La sesión original del admin se guarda en la clave de sesión 'admin_original' (array con todas las claves de sesión del admin antes de impersonar).
- La clave 'impersonando' = true indica que la sesión actual es una impersonación.
- Al salir, se restaura 'admin_original' en la sesión y se eliminan 'impersonando' y 'admin_original'.
- No se permite impersonar a un administrador. Si el negocio objetivo no existe, error 404.
- Si el admin cierra sesión mientras impersona, se destruye todo (incluida 'admin_original'). Documentar esta decisión en un comentario; es aceptable para el MVP.
- Toda impersonación y salida se registra en logs_actividad.
- CSRF obligatorio en los POST.
- No permitir impersonación por GET.

========================================
FLUJO: INICIAR IMPERSONACIÓN
========================================
Ruta: POST /admin/impersonar/{id_negocio} (AuthFilter + AdminFilter, CSRF).
Controlador: Admin\Impersonacion::iniciar($id_negocio).

Pasos:
1. Verificar que session('rol_plataforma') === 'administrador'.
2. Verificar que session('impersonando') !== true. Si ya está impersonando, redirigir a /admin/dashboard con mensaje "Ya estás en una impersonación activa".
3. Verificar que el negocio existe y no está eliminado:
   SELECT id_negocio, nombre_comercial, slug FROM negocios WHERE id_negocio = ? AND deleted_at IS NULL.
   Si no existe, 404.
4. Obtener el usuario propietario activo del negocio:
   SELECT nu.usuario_id, u.email, p.nombres, p.apellidos
   FROM negocio_usuarios nu
   JOIN usuarios u ON u.id_usuario = nu.usuario_id
   JOIN personas p ON p.id_persona = u.persona_id
   WHERE nu.negocio_id = ? AND nu.rol_negocio = 'propietario' AND nu.activo = 1
   LIMIT 1;
   Si no hay propietario activo (dato inconsistente), error 500 controlado: "El negocio no tiene propietario activo. Revisa la integridad en /admin/negocios."
5. Guardar la sesión actual del admin en 'admin_original':
   $session->set('admin_original', [
     'id_usuario'       => $session->get('id_usuario'),
     'id_negocio'       => $session->get('id_negocio'),
     'rol_negocio'      => $session->get('rol_negocio'),
     'rol_plataforma'   => $session->get('rol_plataforma'),
     'nombre_comercial' => $session->get('nombre_comercial'),
     'email'            => $session->get('email'),
   ]);
6. Cambiar la sesión a los datos del negocio:
   $session->set([
     'id_usuario'       => (int) $propietario['usuario_id'],
     'id_negocio'       => (int) $id_negocio,
     'rol_negocio'      => 'propietario',
     'rol_plataforma'   => 'usuario_negocio',
     'nombre_comercial' => $negocio['nombre_comercial'],
     'email'            => $propietario['email'],
     'impersonando'     => true,
   ]);
7. NO llamar a $session->regenerate(). La sesión es la misma; solo cambian las claves.
8. Registrar 'impersonacion_iniciada' en logs_actividad:
   - usuario_id = id del admin original (desde admin_original).
   - negocio_id = id_negocio objetivo.
   - tabla_afectada = 'negocios'.
   - registro_id = id_negocio.
   - descripcion = "Admin {email_admin} impersona a {nombre_comercial} (slug)".
9. Redirigir a /dashboard con mensaje "Ahora estás viendo el panel como {nombre_comercial}".

========================================
FLUJO: SALIR DE LA IMPERSONACIÓN
========================================
Ruta: POST /admin/impersonacion/salir (AuthFilter únicamente, CSRF).
IMPORTANTE: esta ruta NO debe usar AdminFilter porque durante la impersonación rol_plataforma = 'usuario_negocio'. Por eso va fuera del grupo admin, en una ruta propia con solo 'auth'.
Controlador: Admin\Impersonacion::salir().

Pasos:
1. Verificar que session('impersonando') === true. Si no, redirigir a /dashboard con mensaje "No hay impersonación activa".
2. Verificar que session('admin_original') existe y es array. Si no, error 500 controlado: "No se encontró la sesión original del administrador."
3. Restaurar la sesión del admin:
   $original = $session->get('admin_original');
   $session->set($original);
   $session->remove('impersonando');
   $session->remove('admin_original');
4. Registrar 'impersonacion_finalizada' en logs_actividad:
   - usuario_id = id del admin restaurado.
   - negocio_id = id del negocio que se estaba impersonando (leer antes de restaurar).
   - tabla_afectada = 'negocios'.
   - registro_id = id del negocio impersonado.
   - descripcion = "Admin {email_admin} finaliza impersonación de {nombre_comercial}".
5. Redirigir a /admin/dashboard con mensaje "Has vuelto a tu panel de administrador".

========================================
BANNER DE IMPERSONACIÓN EN EL PANEL DEL NEGOCIO
========================================
Modificar app/Views/layouts/panel.php:
- Si session('impersonando') === true, mostrar un banner fijo en la parte superior:
  Fondo rojo/amarillo, texto blanco:
  "Estás viendo como {nombre_comercial}. [Salir de impersonación]"
- El botón "Salir de impersonación" debe ser un formulario POST a /admin/impersonacion/salir con csrf_field(). No usar enlace GET.
- El banner debe verse en todas las vistas del panel del negocio (productos, pedidos, mi plan, categorías, perfil, imágenes, logs).
- Si no está impersonando, el banner no se muestra.

========================================
BOTÓN "VER COMO ESTE NEGOCIO"
========================================
Modificar app/Views/admin/negocios/detalle.php:
- Agregar un botón "Ver como este negocio" junto a Suspender/Reactivar.
- Debe ser un formulario POST a /admin/impersonar/{id_negocio} con csrf_field().
- Estilo secundario (no rojo), para diferenciarlo de Suspender.
- Si el negocio no tiene propietario activo, deshabilitar el botón y mostrar tooltip "Sin propietario activo".

========================================
RUTAS (app/Config/Routes.php)
========================================
Dentro del grupo admin existente (AuthFilter + AdminFilter), agregar:
POST /admin/impersonar/(:num)  → Admin\Impersonacion::iniciar/$1

Fuera del grupo admin, en una ruta propia con solo AuthFilter, agregar:
POST /admin/impersonacion/salir → Admin\Impersonacion::salir

Justificación: la salida debe funcionar mientras la sesión tiene rol_plataforma = 'usuario_negocio'; si estuviera dentro del grupo admin, AdminFilter la bloquearía.

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Controladores:
- app/Controllers/Admin/Impersonacion.php (nuevo): métodos iniciar($id_negocio) y salir().

Modelos:
- app/Models/NegocioModel.php (modificar): agregar método obtenerPropietarioActivo($id_negocio) que devuelva usuario_id, email, nombres, apellidos del propietario activo.
- app/Models/LogActividadModel.php (ya existe): sin cambios funcionales; usar el método existente para registrar.

Config:
- app/Config/Routes.php (agregar las dos rutas indicadas).

Vistas:
- app/Views/admin/negocios/detalle.php (agregar botón "Ver como este negocio").
- app/Views/layouts/panel.php (agregar banner condicional de impersonación).

========================================
SEGURIDAD
========================================
- /admin/impersonar/{id} solo accesible con AuthFilter + AdminFilter (rol_plataforma = 'administrador').
- /admin/impersonacion/salir solo accesible con AuthFilter y con session('impersonando') === true; el controlador valida admin_original.
- CSRF en ambos POST.
- No exponer el id de admin_original en la vista ni en la URL.
- No permitir impersonar por GET.
- No permitir impersonar a un administrador: si el negocio objetivo tiene como propietario a un usuario con rol_plataforma = 'administrador', rechazar con mensaje "No se puede impersonar a un administrador". Esto es defensa en profundidad; en la práctica un admin no debería ser propietario de un negocio, pero se valida.
- Mientras impersona, el admin no puede acceder a /admin/* (AdminFilter lo bloquea porque rol_plataforma cambió). No modificar AdminFilter.
- Los logs de impersonación deben incluir el email del admin original, no el del propietario impersonado.
- Si el negocio está suspendido (activo = 0), permitir la impersonación igual: el admin puede necesitar revisar el panel antes de reactivar. El catálogo público seguirá mostrando 404.
- Si el negocio está eliminado lógicamente (deleted_at no NULL), rechazar con 404.

========================================
PRUEBAS MANUALES ESPERADAS
========================================
1. Login como admin → /admin/dashboard.
2. Ir a /admin/negocios/{id} → ver botón "Ver como este negocio".
3. Clic → redirige a /dashboard con banner rojo "Estás viendo como {nombre_comercial}".
4. Navegar por /negocio/productos, /negocio/pedidos, /negocio/mi-plan, /negocio/categorias → todo funciona como ese negocio.
5. Intentar abrir /admin/dashboard mientras impersona → redirige a /dashboard con mensaje de acceso denegado (AdminFilter actúa).
6. Clic en "Salir de impersonación" → vuelve a /admin/dashboard con la sesión de admin intacta.
7. Verificar que después de salir, /admin/negocios, /admin/pagos, /admin/configuracion siguen funcionando.
8. Verificar en logs_actividad:
   - 'impersonacion_iniciada' con usuario_id del admin y negocio_id del negocio.
   - 'impersonacion_finalizada' con los mismos ids.
9. Intentar POST /admin/impersonar/{id} con un usuario no admin → debe redirigir a /dashboard con acceso denegado (AdminFilter).
10. Intentar abrir /admin/impersonacion/salir sin impersonación activa → mensaje "No hay impersonación activa" y redirige a /dashboard.
11. Cerrar sesión mientras impersona → debe destruir todo y volver al login. Al iniciar sesión de nuevo como admin, no debe quedar rastro de impersonación.
12. Intentar impersonar un negocio sin propietario activo (modificar negocio_usuarios en phpMyAdmin para desactivar el propietario) → error controlado, no rompe la app.

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Confirmación de que /admin/* y /negocio/* siguen funcionando.
- Plan de prueba con los 12 casos de arriba.
- No hagas commit.
