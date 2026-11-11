Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe. Bloques 0 a 9.1 implementados.
Implementa SOLO el Bloque 8: Reportes, Colaboradores, Logo y Analytics.
No modifiques el esquema ni crees migraciones. (Nota: la columna negocios.logo_ruta ya fue agregada manualmente).
No toques la lógica de moderación, impersonación ni configuración global.

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo las secciones de docs/database-dictionary.md para: negocios, negocio_usuarios, usuarios, personas, pedidos, detalle_pedido, productos, planes, suscripciones, logs_actividad.
No leas docs/schema-v2.sql completo.
No modifiques el esquema.

========================================
ALCANCE
========================================
Implementaremos 4 requerimientos:
1. Logo del negocio (Nuevo, usando la columna logo_ruta).
2. HU-36: Reportes básicos (Básico/Pro).
3. HU-37: Gestión de colaboradores (Pro).
4. HU-42: Google Analytics (Pro).

NO implementar:
- HU-41 (Dominio propio): se posterga para Fase 3 por complejidad de DNS.
- Cualquier cambio en el esquema de la BD.

========================================
1. LOGO DEL NEGOCIO (NUEVO)
========================================
- Modificar app/Controllers/Negocio/Perfil.php:
  - Agregar validación y subida de archivo 'logo' en el método guardar.
  - Validar MIME real (JPG, PNG, WebP), tamaño ≤ 2 MB.
  - Guardar en writable/uploads/logos/{id_negocio}/.
  - Actualizar la columna logo_ruta en la tabla negocios.
- Modificar app/Views/negocio/perfil.php:
  - Agregar un campo de subida de imagen (con previsualización actual si ya tiene logo).
- Modificar app/Views/catalogo/index.php:
  - Si el negocio tiene logo_ruta, mostrarlo en la cabecera del catálogo (círculo o cuadrado redondeado).
  - Usar un endpoint protegido para servir el logo o servirlo vía /c/logo/{id_negocio}.

========================================
2. HU-36 — REPORTES BÁSICOS
========================================
- Crear app/Controllers/Negocio/Reportes.php.
- Ruta: GET /negocio/reportes (AuthFilter).
- Validar que el plan vigente sea Básico o Pro (tiene_reportes = 1). Si es Gratis, mostrar pantalla de "Función exclusiva de planes de pago" con botón a /negocio/mi-plan.
- Filtros de fecha: por defecto el mes actual (desde el día 1 hasta hoy). Inputs date.
- Métricas:
  1. Total vendido (SUM total de pedidos no cancelados en el rango).
  2. Cantidad de pedidos (COUNT).
  3. Ticket promedio.
  4. Top 5 productos más pedidos (JOIN detalle_pedido, SUM cantidad, agrupado por producto_titulo).
- Vista app/Views/negocio/reportes/index.php:
  - Mobile-first.
  - Tarjetas con las métricas.
  - Tabla del Top 5.
  - Filtro de fechas.
- Enlace en el layout del panel (layouts/panel.php) a "Reportes", visible solo si el plan lo permite.

========================================
3. HU-37 — COLABORADORES (PRO)
========================================
- Crear app/Controllers/Negocio/Colaboradores.php.
- Rutas:
  GET /negocio/colaboradores → index
  GET /negocio/colaboradores/crear → crear
  POST /negocio/colaboradores/crear → guardar
  POST /negocio/colaboradores/{id}/desactivar → desactivar
- Validar que el plan vigente sea Pro (max_usuarios > 1 o slug = 'pro'). Si es Gratis/Básico, denegar acceso.
- index: listar los colaboradores del negocio (JOIN negocio_usuarios, usuarios, personas).
- crear: formulario con nombres, apellidos, email, contraseña temporal.
  - Al guardar: crear persona, crear usuario, asignar rol 'usuario_negocio' en usuario_roles, asignar rol 'colaborador' en negocio_usuarios.
  - Verificar límite max_usuarios del plan.
  - Verificar email único.
- desactivar: cambiar negocio_usuarios.activo = 0. No borrar.
- Vista app/Views/negocio/colaboradores/index.php y form.php.
- Enlace en layouts/panel.php, visible solo si el plan es Pro.

========================================
4. HU-42 — GOOGLE ANALYTICS (PRO)
========================================
- Modificar app/Controllers/Negocio/Perfil.php:
  - Agregar validación del campo 'google_analytics_id' (formato G-XXXXXXXXXX o UA-...).
  - Guardar en la tabla negocios.
- Modificar app/Views/negocio/perfil.php:
  - Agregar input para el ID de Analytics, visible solo si el plan tiene tiene_analytics = 1.
- Modificar app/Views/catalogo/index.php:
  - Si el negocio tiene google_analytics_id, inyectar el script de seguimiento de Google Analytics en el <head> del catálogo público.

========================================
RUTAS (app/Config/Routes.php)
========================================
Dentro del grupo 'negocio' (AuthFilter):
GET  /negocio/reportes                          → Negocio\Reportes::index
GET  /negocio/colaboradores                     → Negocio\Colaboradores::index
GET  /negocio/colaboradores/crear               → Negocio\Colaboradores::crear
POST /negocio/colaboradores/crear               → Negocio\Colaboradores::guardar
POST /negocio/colaboradores/(:num)/desactivar   → Negocio\Colaboradores::desactivar

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Controladores:
- Negocio/Reportes.php (nuevo)
- Negocio/Colaboradores.php (nuevo)
- Negocio/Perfil.php (modificar: logo y analytics)

Modelos:
- NegocioModel.php (modificar: manejar logo_ruta y google_analytics_id)
- PedidoModel.php (modificar: agregar métodos obtenerMetricasReporte, obtenerTopProductos)
- NegocioUsuarioModel.php (modificar: métodos para listar colaboradores)

Vistas:
- negocio/reportes/index.php (nuevo)
- negocio/colaboradores/index.php (nuevo)
- negocio/colaboradores/form.php (nuevo)
- negocio/perfil.php (modificar)
- catalogo/index.php (modificar: logo y script Analytics)
- layouts/panel.php (modificar: enlaces a Reportes y Colaboradores)

========================================
SEGURIDAD
========================================
- Validación server-side en todos los formularios.
- CSRF en POST.
- Verificar pertenencia: un negocio solo ve sus reportes y colaboradores.
- Un colaborador no puede crear otros colaboradores (solo el propietario).
- Escape de salida con esc().

========================================
PRUEBAS MANUALES
========================================
1. Logo: subir un logo en el perfil; verificar que se guarda en writable/uploads/logos/ y se muestra en el catálogo público.
2. Reportes: con plan Gratis, intentar entrar a /negocio/reportes → bloqueado. Cambiar a Básico → acceder, filtrar por fechas y ver métricas reales.
3. Colaboradores: con plan Pro, crear un colaborador. Cerrar sesión, entrar con el email del colaborador → debe ver el panel y los productos, pero no la opción de crear colaboradores.
4. Analytics: con plan Pro, guardar un ID válido. Ver el código fuente del catálogo público → debe incluir el script de Google Analytics.
5. Verificar que /auth, /admin, /negocio/productos y el carrito siguen funcionando.

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Plan de prueba.
- No hagas commit.
