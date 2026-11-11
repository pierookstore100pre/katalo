Proyecto: Katalo.pe (CodeIgniter 4).
BD: katalo_pe. Bloques 0 a 8 implementados (reportes de negocio, pdf, gráficos, logo, colaboradores).
Implementa el Bloque 8.5: Reportes de Administrador, Pedido Imprimible y ajustes de UI en Logs.
No modifiques el esquema de la BD.

========================================
ALCANCE
========================================
1. Agregar módulo de Reportes Globales para el Administrador.
2. Agregar función de "Imprimir Pedido" en el panel del negocio.
3. Renombrar la columna "Tabla" a "Módulo" en las vistas de logs (admin y negocio).

========================================
1. REPORTES EN EL PANEL DE ADMINISTRADOR
========================================
- Crear app/Controllers/Admin/Reportes.php.
- Ruta: GET /admin/reportes (AuthFilter + AdminFilter).
- Métricas globales:
  - MRR actual (SUM precio_pactado de suscripciones activas).
  - Total de negocios activos vs suspendidos.
  - Total de pedidos del mes actual.
  - Ingresos por ventas (SUM total de pedidos entregados).
- Gráficos (Chart.js):
  - Gráfico de Pastel: Negocios por Plan (Gratis, Básico, Pro).
  - Gráfico de Barras: Top 5 Ciudades con más pedidos.
- Vista app/Views/admin/reportes/index.php (extendiendo layouts/admin).
- Agregar enlace "Reportes" en layouts/admin.php.

========================================
2. IMPRIMIR PEDIDO
========================================
- En app/Views/negocio/pedidos/detalle.php, agregar un botón "Imprimir Pedido" (puede usar `onclick="window.print()"`).
- En app/Views/layouts/panel.php o en la misma vista de detalle, agregar una regla CSS `@media print` para ocultar los menús, botones y barras laterales, dejando solo el contenido del pedido limpio (idealmente un recuadro con el logo del negocio, datos del cliente, productos y total).
- Opcional: crear una ruta /negocio/pedidos/{id}/imprimir si el diseño con CSS `@media print` no es suficiente, pero intentar primero con CSS.

========================================
3. AJUSTES EN EL REGISTRO DE ACTIVIDAD (LOGS)
========================================
- En app/Views/admin/logs/index.php: cambiar el encabezado de la tabla que dice "Tabla" por "Módulo".
- En app/Views/negocio/logs/index.php: cambiar el encabezado de la tabla que dice "Tabla" por "Módulo".
- (Opcional si es fácil): Si el valor de `tabla_afectada` es 'productos', mostrar 'Productos'; si es 'pedidos', mostrar 'Pedidos', etc. Pero con cambiar el título a "Módulo" es suficiente.

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
- app/Controllers/Admin/Reportes.php (nuevo)
- app/Views/admin/reportes/index.php (nuevo)
- app/Views/admin/logs/index.php (modificar título)
- app/Views/negocio/logs/index.php (modificar título)
- app/Views/negocio/pedidos/detalle.php (agregar botón imprimir y CSS)
- app/Config/Routes.php (agregar /admin/reportes)
- app/Views/layouts/admin.php (agregar enlace)

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Plan de prueba.
- No hagas commit.
