Proyecto: Katalo.pe. CodeIgniter 4, PHP 8, MySQL 8.
Implementa SOLO el Bloque 9.1: panel de configuración global para el administrador.
No modifiques el esquema. La tabla configuracion ya existe.

========================================
ALCANCE
========================================
RF: apoyo a RF-25 y RNF-20 (límites configurables sin recompilar).
HU: apoyo a HU-25 y HU-40.

Crear la pantalla /admin/configuracion donde el administrador pueda editar:
- sesion_minutos (int, 1–1440)
- dias_prueba (int, 0–90)
- reset_password_horas (int, 1–72)
- pedidos_max_por_ip_hora (int, 0–1000; 0 = sin límite)
- login_intentos_max (int, 1–20)
- login_bloqueo_minutos (int, 1–1440)

Cada campo debe mostrar su descripcion desde configuracion.descripcion.

========================================
COMPORTAMIENTO
========================================
- GET /admin/configuracion: formulario con los valores actuales.
- POST /admin/configuracion: validar y actualizar cada valor.
- Si un valor es 0 en pedidos_max_por_ip_hora, interpretarlo como "sin límite" en Catalogo/Pedido.php:
  - Modificar Catalogo/Pedido.php: si max <= 0, no aplicar el límite.
- Registrar 'editar_configuracion' en logs_actividad con los cambios (datos JSON con antes/después).
- AuthFilter + AdminFilter obligatorios.

========================================
ARCHIVOS
========================================
- app/Controllers/Admin/Configuracion.php (nuevo)
- app/Views/admin/configuracion/index.php (nuevo)
- app/Models/ConfiguracionModel.php (modificar si ya existe: agregar actualizarPorClave)
- app/Config/Routes.php (agregar rutas dentro del grupo admin)
- app/Views/layouts/admin.php (agregar enlace "Configuración")
- app/Controllers/Catalogo/Pedido.php (modificar: respetar 0 = sin límite)

========================================
RUTAS
========================================
GET  /admin/configuracion  → Admin\Configuracion::index
POST /admin/configuracion  → Admin\Configuracion::guardar

========================================
VISTAS
========================================
- Extiende layouts/admin, misma sección que las demás vistas admin.
- Formulario con los 6 campos, su descripción y valor actual.
- Nota visible: "0 en pedidos por IP significa sin límite".
- CSRF.
- Escape con esc().
- Mensaje de éxito al guardar.

========================================
PRUEBAS
========================================
1. Abrir /admin/configuracion → ver valores actuales.
2. Cambiar pedidos_max_por_ip_hora a 0 → guardar.
3. Crear 10 pedidos seguidos → no debe bloquear.
4. Volver a 10 → el límite se aplica de nuevo.
5. Cambiar login_intentos_max a 3 → probar bloqueo en 3 intentos.
6. logs_actividad registra 'editar_configuracion'.

========================================
AL FINALIZAR
========================================
- Lista de archivos.
- Plan de prueba.
- No hagas commit.
