Proyecto: Katalo.pe (CodeIgniter 4).
BD: katalo_pe. Bloques 0 a 8.6 implementados.
Implementa el Bloque 8.7: Generador de Reportes Dinámicos en el Panel de Administrador.
No modifiques el esquema de la BD.

========================================
ALCANCE
========================================
Permitir al administrador generar reportes personalizados cruzando datos de Negocios, Pedidos y Suscripciones, con filtros dinámicos y exportación a PDF idéntica a la pantalla.

========================================
1. FILTROS EN LA VISTA (app/Views/admin/reportes/index.php)
========================================
Agregar un formulario en la parte superior con los siguientes campos (usar método GET):
- "Agrupar por (Eje X)": Selector (Opciones: Ciudad, Distrito, Plan). Por defecto: Ciudad.
- "Filtrar por Ciudad": Selector (Todas, Tacna, Moquegua, Ilo).
- "Filtrar por Plan": Selector (Todos, Gratis, Básico, Pro).
- Botón "Aplicar Filtros".
El botón "Descargar Reporte PDF" debe enviar los mismos parámetros GET a la ruta del PDF.

========================================
2. LÓGICA DEL CONTROLADOR (app/Controllers/Admin/Reportes.php)
========================================
- Recibir `agrupar_por` (ciudad, distrito, plan), `ciudad_id` (0 = todas), `plan_id` (0 = todos).
- Construir la consulta SQL dinámicamente basándose en la agrupación:
  - SELECT {campo_agrupacion} AS etiqueta, 
           COUNT(DISTINCT n.id_negocio) AS total_negocios, 
           COALESCE(SUM(s.precio_pactado), 0) AS mrr, 
           COUNT(DISTINCT p.id_pedido) AS total_pedidos
  - FROM negocios n
  - JOIN distritos d ON d.id_distrito = n.distrito_id
  - JOIN ciudades c ON c.id_ciudad = d.ciudad_id
  - LEFT JOIN suscripciones s ON s.negocio_id = n.id_negocio AND s.estado = 'activa' AND s.deleted_at IS NULL
  - LEFT JOIN pedidos p ON p.negocio_id = n.id_negocio AND p.deleted_at IS NULL AND MONTH(p.created_at) = MONTH(CURDATE()) AND YEAR(p.created_at) = YEAR(CURDATE())
  - WHERE n.deleted_at IS NULL
  - {Si ciudad_id > 0: AND c.id_ciudad = X}
  - {Si plan_id > 0: AND s.plan_id = Y}
  - GROUP BY {campo_agrupacion}
  - ORDER BY mrr DESC
- Enviar los datos, los filtros y el campo de agrupación a las vistas.
- El método pdf() debe ejecutar la misma lógica pero cargar la vista del PDF.

========================================
3. GRÁFICOS Y VISTA WEB (index.php)
========================================
- Usar Chart.js.
- Gráfico de Barras: Eje X = etiquetas (Ciudades, Distritos o Planes), Eje Y = MRR (o Pedidos). Puedes mostrar ambas métricas si es posible.
- Tabla de datos con: Etiqueta, Negocios Activos, Pedidos del Mes, MRR.
- El título de la tabla debe cambiar dinámicamente (ej. "Comparativa por Distrito").

========================================
4. PDF (pdf.php)
========================================
- Cabecera: Título dinámico ("Reporte por {Agrupación}"), fecha de generación.
- Mostrar los filtros aplicados (ej. "Ciudad: Tacna").
- Tabla con los mismos datos de la web.
- Simular gráfico con barras CSS (ancho porcentual respecto al MRR máximo).

========================================
ARCHIVOS A MODIFICAR / CREAR
========================================
- app/Controllers/Admin/Reportes.php (modificar lógica)
- app/Views/admin/reportes/index.php (modificar formulario y gráficos)
- app/Views/admin/reportes/pdf.php (modificar para mostrar el reporte dinámico)
- app/Models/NegocioModel.php (agregar método obtenerReporteDinamico($agrupar, $ciudad_id, $plan_id))

========================================
AL FINALIZAR
========================================
- Lista de archivos modificados.
- Plan de prueba.
- No hagas commit.
