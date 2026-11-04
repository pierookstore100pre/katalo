Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe. Bloques 0 a 7.5 implementados.
Implementa SOLO el Bloque 9: hardening y calidad.
No modifiques el esquema ni crees migraciones. Usa solo columnas y tablas existentes.

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo las secciones de docs/database-dictionary.md para: usuarios, pedidos, negocios, productos, categorias, imagenes_producto, detalle_pedido, configuracion, logs_actividad.
No leas docs/schema-v2.sql completo.
No modifiques el esquema.

========================================
ALCANCE
========================================
RF: RF-29, RF-30, RF-31, RF-33 (refuerzo).
HU: HU-45, HU-46, HU-47, HU-48.

NO implementar:
- Reportes, colaboradores, dominio propio, analytics (Bloque 8, Post-MVP).
- Cambios en moderación ni en layout admin.
- Reescritura de bloques anteriores. Solo ajustes puntuales donde se indica.

========================================
HU-45 — BLOQUEO DE LOGIN POR INTENTOS FALLIDOS
========================================
Columnas existentes: usuarios.intentos_fallidos (TINYINT UNSIGNED), usuarios.bloqueado_hasta (DATETIME NULL).
Configuración existente en tabla configuracion:
- login_intentos_max = 5
- login_bloqueo_minutos = 15

Modificar app/Controllers/Auth/Login.php:
1. Al recibir POST /auth/login:
   - Buscar usuario por email (incluye eliminados? NO: solo activo = 1 y deleted_at IS NULL).
   - Si no existe: mensaje genérico "Credenciales incorrectas". No revelar.
   - Si existe y bloqueado_hasta > NOW(): mostrar "Cuenta bloqueada temporalmente. Intenta de nuevo en X minutos." donde X = ceil((bloqueado_hasta - now)/60). No incrementar contador.
   - Si password_verify() falla:
     - incrementos = intentos_fallidos + 1.
     - Si incrementos >= login_intentos_max: bloqueado_hasta = NOW() + login_bloqueo_minutos minutos; intentos_fallidos = incrementos.
     - Si no: solo intentos_fallidos = incrementos.
     - Registrar 'login_fallido' en logs_actividad con usuario_id (si existe) o NULL.
     - Mensaje genérico "Credenciales incorrectas" (no revelar si está cerca del bloqueo).
   - Si password_verify() es correcto:
     - intentos_fallidos = 0; bloqueado_hasta = NULL; ultimo_login = NOW().
     - Registrar 'login' como ya hace.
     - Continuar con el flujo actual (redirigir a /admin/dashboard o /dashboard).
2. Leer los valores de configuracion con un método obtenerValor($clave) en un ConfiguracionModel existente o nuevo; si no existe el modelo, crearlo (Nivel 1, tabla configuracion).
3. No aplicar bloqueo al admin de forma distinta; las mismas reglas aplican.
4. No bloquear por IP en este punto (eso es HU-46 pero solo para pedidos).

========================================
HU-46 — LÍMITE DE PEDIDOS POR IP POR HORA
========================================
Configuración existente: configuracion.pedidos_max_por_ip_hora = 10.

Modificar app/Controllers/Catalogo/Pedido.php:
1. Al inicio de crear($ciudad_slug, $slug), antes de validar items:
   - Obtener ip = $this->request->getIPAddress().
   - Obtener max = valor de configuracion.pedidos_max_por_ip_hora (default 10 si no existe).
   - Contar pedidos en la última hora:
     SELECT COUNT(*) FROM pedidos
     WHERE ip_cliente = ? AND created_at >= NOW() - INTERVAL 1 HOUR
     (incluye eliminados? NO: AND deleted_at IS NULL).
   - Si count >= max: devolver JSON {ok: false, mensaje: "Se alcanzó el límite de pedidos por hora desde esta conexión. Intenta más tarde."} con HTTP 429.
2. No cambiar el resto de la transacción.
3. No contar pedidos cancelados? Sí contarlos, porque el abuso no distingue estado. Documentar esta decisión en un comentario.
4. Asegurarse de que la IP se obtiene de forma confiable: usar $this->request->getIPAddress() que ya respeta proxy configurado en App::$proxyIPs. Si el proyecto no configura proxyIPs, dejar el default.

========================================
HU-47 — AISLAMIENTO ENTRE NEGOCIOS (VERIFICACIÓN)
========================================
No reescribir la lógica existente (ya usa negocio_id en todas las consultas).
Crear app/Commands/VerificarAislamiento.php con el comando:
spark katalo:verificar-aislamiento

El comando debe ejecutar verificaciones de solo lectura y reportar OK/FALLA:
1. Contar productos cuyo categoria_id no pertenezca al mismo negocio:
   SELECT COUNT(*) FROM productos p
   LEFT JOIN categorias c ON c.id_categoria = p.categoria_id AND c.negocio_id = p.negocio_id
   WHERE c.id_categoria IS NULL AND p.deleted_at IS NULL;
   → debe ser 0.
2. Contar detalle_pedido cuyo pedido_id o producto_id no pertenezcan al mismo negocio:
   - JOIN pedidos y productos por negocio_id y verificar nulos.
   → debe ser 0.
3. Contar negocio_usuarios con más de un propietario activo por negocio:
   SELECT negocio_id, COUNT(*) FROM negocio_usuarios
   WHERE rol_negocio = 'propietario' AND activo = 1 GROUP BY negocio_id HAVING COUNT(*) > 1;
   → debe devolver 0 filas.
4. Contar suscripciones vigentes duplicadas por negocio:
   SELECT negocio_id, COUNT(*) FROM suscripciones
   WHERE estado IN ('prueba','activa') AND deleted_at IS NULL
   GROUP BY negocio_id HAVING COUNT(*) > 1;
   → debe devolver 0 filas.
5. Contar imágenes con más de una principal por producto (la BD lo impide con columna generada; verificar que no haya inconsistencias):
   SELECT producto_id, COUNT(*) FROM imagenes_producto
   WHERE es_principal = 1 AND deleted_at IS NULL GROUP BY producto_id HAVING COUNT(*) > 1;
   → debe devolver 0 filas.

Salida: lista de verificaciones con OK/FALLA y conteo. Si hay FALLA, código de salida 1. Si todo OK, código de salida 0.

Documentar en docs/pruebas-aislamiento.md un plan de pruebas manuales con:
- Crear dos negocios A y B.
- En A, intentar crear un producto con categoría de B (debe rechazar).
- En A, intentar acceder a /negocio/productos/editar/{id de producto de B} (debe denegar).
- En A, intentar acceder a /negocio/pedidos/{id de pedido de B} (debe denegar).
- En A, intentar cambiar estado de pedido de B (debe denegar).
- Confirmar que /tacna/slug-A no muestra productos de B.
- Confirmar que /admin/negocios muestra ambos al admin.

========================================
HU-48 — RESPALDOS Y RESTAURACIÓN
========================================
Crear app/Commands/Backup.php con el comando:
spark katalo:backup

El comando debe:
1. Crear carpeta writable/backups/ si no existe.
2. Generar archivo SQL de respaldo usando mysqldump si está disponible en el sistema:
   - Detectar binario: 'mysqldump' en PATH o variable de entorno MYSQLDUMP_PATH.
   - Ejecutar: mysqldump --single-transaction --routines --triggers --default-character-set=utf8mb4 -u USER -pPASS DBNAME > writable/backups/katalo_pe_YYYYMMDD_HHMMSS.sql
   - Si mysqldump no está disponible, usar PHP para exportar: iterar tablas con SHOW TABLES, luego SHOW CREATE TABLE y SELECT * para generar INSERTs. Documentar que la versión PHP es más lenta y no incluye triggers.
3. Aplicar retención: mantener los últimos 7 archivos .sql en writable/backups/, borrar los más antiguos.
4. Registrar 'backup_generado' en logs_actividad con descripcion = nombre del archivo y tamaño.
5. Mostrar mensaje: "Respaldo generado: archivo (tamaño). Se conservan N archivos."

Crear app/Commands/RestaurarBackup.php NO es necesario; documentar restauración manual en docs/manual-respaldos.md.

Crear docs/manual-respaldos.md con:
- Cómo ejecutar spark katalo:backup.
- Cómo programar cron en Windows (Task Scheduler) y Linux (crontab).
- Cómo restaurar: mysql -u USER -p DBNAME < archivo.sql.
- Cómo verificar integridad: ejecutar spark katalo:verificar-aislamiento después de restaurar.
- Retención: 7 diarios; los respaldos mensuales se archivan manualmente.

No implementar cron automático en el código; solo el comando.

========================================
MODIFICAR Login.php (resumen de cambios)
========================================
- Agregar lectura de configuracion (login_intentos_max, login_bloqueo_minutos).
- Agregar verificación de bloqueado_hasta antes de password_verify.
- Agregar incremento y posible bloqueo al fallar password_verify.
- Resetear intentos_fallidos y bloqueado_hasta al éxito.
- Registrar 'login_fallido' en logs_actividad.
- Mantener los redirects actuales (admin → /admin/dashboard, negocio → /dashboard).

========================================
MODIFICAR Catalogo/Pedido.php (resumen de cambios)
========================================
- Agregar verificación de límite por IP al inicio de crear().
- Devolver HTTP 429 con JSON {ok:false, mensaje:...} si se excede.
- No cambiar la transacción ni el resto del flujo.

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Controladores:
- app/Controllers/Auth/Login.php (modificar).
- app/Controllers/Catalogo/Pedido.php (modificar).

Modelos:
- app/Models/ConfiguracionModel.php (crear si no existe): Nivel 1, soft delete; método obtenerValor($clave, $default = null).
- app/Models/PedidoModel.php (modificar): agregar contarPorIpEnUltimaHora($ip).

Comandos (app/Commands/):
- VerificarAislamiento.php (nuevo).
- Backup.php (nuevo).

Documentación (docs/):
- pruebas-aislamiento.md (nuevo).
- manual-respaldos.md (nuevo).

Config:
- app/Config/Routes.php: no se agregan rutas (los comandos se ejecutan por spark, no por URL).

========================================
VISTAS
========================================
- No se crean vistas nuevas. Solo se ajustan los mensajes de error en Login y en Pedido.
- Los mensajes de bloqueo y de límite por IP deben mostrarse en la misma pantalla donde ocurre el error, respetando el diseño existente.

========================================
SEGURIDAD
========================================
- Mensajes genéricos en login fallido (no revelar si el email existe ni cuántos intentos quedan).
- Mensaje claro cuando está bloqueado (con minutos restantes) sin revelar detalles técnicos.
- HTTP 429 en límite por IP.
- No exponer SQL en los comandos ni en los mensajes.
- El comando de backup no debe imprimir la contraseña de la BD.

========================================
PRUEBAS MANUALES ESPERADAS
========================================
HU-45:
1. Intentar login con contraseña incorrecta 5 veces seguidas.
2. Al quinto intento, el usuario debe quedar con bloqueado_hasta en el futuro (ver phpMyAdmin).
3. Intentar login con contraseña correcta durante el bloqueo → mensaje "Cuenta bloqueada temporalmente".
4. Esperar 15 minutos (o actualizar bloqueado_hasta a NOW() - 1 min en phpMyAdmin) → login correcto funciona.
5. Tras login exitoso, intentos_fallidos = 0 y bloqueado_hasta = NULL.
6. logs_actividad registra 'login_fallido' por cada intento fallido.

HU-46:
1. Configurar pedidos_max_por_ip_hora = 2 en configuracion para la prueba.
2. Crear 2 pedidos desde el catálogo → OK.
3. Intentar el tercero → HTTP 429 con mensaje de límite.
4. Volver a configurar a 10.
5. Verificar que pedidos con deleted_at NO cuentan (opcional, si tienes uno de prueba).

HU-47:
1. Ejecutar php spark katalo:verificar-aislamiento → todas las verificaciones OK.
2. Seguir el plan de docs/pruebas-aislamiento.md con dos negocios A y B.
3. Confirmar que A no puede acceder a datos de B en productos, pedidos ni categorías.

HU-48:
1. Ejecutar php spark katalo:backup → se genera archivo en writable/backups/.
2. Verificar que el archivo contiene CREATE TABLE e INSERTs.
3. Ejecutar 8 veces y verificar que solo quedan 7 archivos (retención).
4. Seguir docs/manual-respaldos.md para restaurar en una BD de prueba (puedes crear katalo_pe_test).
5. logs_actividad registra 'backup_generado'.

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Comandos spark nuevos y cómo usarlos.
- Plan de prueba con los casos de arriba.
- No hagas commit.
