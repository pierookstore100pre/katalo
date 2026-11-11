Proyecto: Katalo.pe (CodeIgniter 4).
BD: katalo_pe. Bloques 0 a 8.5 implementados.
Implementa el Bloque 8.6: Reportes Avanzados y PDF para el Administrador.
No modifiques el esquema de la BD.

========================================
ALCANCE
========================================
1. Agregar métricas comparativas detalladas (por Ciudad y por Plan) en /admin/reportes.
2. Agregar exportación a PDF en el panel de reportes del administrador.

========================================
1. MÉTRICAS COMPARATIVAS EN /admin/reportes
========================================
Modificar app/Controllers/Admin/Reportes.php y app/Views/admin/reportes/index.php.
Agregar dos nuevas secciones (tablas + gráficos):

A. Progreso por Zona (Ciudades):
- Tabla con: Ciudad, Negocios Activos, Pedidos del Mes, MRR (Ingresos mensuales por suscripciones).
- Gráfico de Barras (Chart.js): Comparativa de Pedidos del Mes por Ciudad.

B. Rendimiento por Plan:
- Tabla con: Plan, Cantidad de Negocios, MRR generado.
- Gráfico de Dona (Chart.js): Distribución de Negocios por Plan.

Consultas SQL sugeridas (adaptar a los nombres exactos):
- Por ciudad: JOIN ciudades -> distritos -> negocios -> suscripciones (activas) y pedidos (mes actual).
- Por plan: JOIN planes -> suscripciones (activas).

========================================
2. EXPORTACIÓN A PDF (ADMIN)
========================================
- Agregar botón "Descargar Reporte PDF" en app/Views/admin/reportes/index.php.
- Ruta: GET /admin/reportes/pdf → Admin\Reportes::pdf()
- En el controlador Admin/Reportes.php, crear el método pdf():
  - Obtener las mismas métricas que en el index.
  - Cargar la vista app/Views/admin/reportes/pdf.php.
  - Usar Dompdf (\Dompdf\Dompdf) para generar el PDF (igual que en el reporte del negocio).
- Crear app/Views/admin/reportes/pdf.php:
  - Cabecera: Logo de Katalo.pe (opcional, si existe en public/assets/), título "Reporte Global Katalo.pe", fecha de generación.
  - Tarjetas de resumen: Total Negocios, MRR Global, Total Pedidos Mes.
  - Tabla 1: Progreso por Zona.
  - Tabla 2: Rendimiento por Plan.
  - Simular los gráficos con barras CSS (divs con porcentajes) para que Dompdf lo renderice.

========================================
ARCHIVOS A MODIFICAR / CREAR
========================================
- app/Controllers/Admin/Reportes.php (modificar: agregar métricas y método pdf)
- app/Views/admin/reportes/index.php (modificar: agregar tablas, gráficos y botón PDF)
- app/Views/admin/reportes/pdf.php (nuevo)
- app/Config/Routes.php (agregar ruta /admin/reportes/pdf)
- app/Models/NegocioModel.php o PedidoModel.php (agregar métodos para las nuevas consultas SQL).

========================================
AL FINALIZAR
========================================
- Lista de archivos modificados.
- Plan de prueba.
- No hagas commit.
