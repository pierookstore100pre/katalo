Proyecto: Katalo.pe (CodeIgniter 4).
BD: katalo_pe. Bloques 0 a 8.7 implementados.
Implementa el Bloque 8.8: Ajustes de UI en Reportes Dinámicos, Filtro de Pagos y Corrección de Logo en PDF.
No modifiques el esquema de la BD.

========================================
ALCANCE
========================================
1. Mejorar los textos y la lógica de los filtros (Ciudad -> Distrito en cascada).
2. Agregar un filtro por método de pago (transferencia, yape, plin, efectivo).
3. Corregir la alineación y el tamaño del logo en los PDF generados (Dompdf).

========================================
1. AJUSTES EN LA VISTA WEB (app/Views/admin/reportes/index.php)
========================================
- Cambiar la etiqueta "Agrupar por (Eje X)" a simplemente "Agrupar por:".
- Cambiar el filtro de búsqueda:
  - Mantener "Filtrar por Ciudad:" (Todas, Tacna, Moquegua, Ilo).
  - Agregar "Filtrar por Distrito:" (Todas, y los distritos correspondientes). 
    - Usar un poco de JavaScript (vanilla) para que, al seleccionar una ciudad, el select de distritos se filtre y solo muestre los de esa ciudad (usando atributos data-ciudad en las opciones).
- Agregar un nuevo filtro: "Filtrar por Pago:" (Todos, Transferencia, Yape, Plin, Efectivo).
- El botón "Descargar Reporte PDF" debe incluir estos nuevos filtros en la URL.

========================================
2. LÓGICA DEL CONTROLADOR (app/Controllers/Admin/Reportes.php)
========================================
- Recibir el nuevo parámetro `distrito_id` (0 = todos) y `metodo_pago` (vacío = todos).
- Modificar la consulta SQL dinámica para incluir estos filtros:
  - Si `distrito_id > 0`: AND d.id_distrito = X
  - Si `metodo_pago` no está vacío: 
    - Hacer un JOIN con la tabla `pagos` (pg) a través de la suscripción.
    - AND pg.metodo = 'metodo_seleccionado' AND pg.estado = 'confirmado'.
    - (Nota: Si un negocio tiene varios pagos, agrupar para no duplicar el MRR, o sumar solo los pagos confirmados).
- Asegurarse de que el método `pdf()` reciba y use estos mismos filtros.

========================================
3. CORRECCIÓN DEL LOGO EN LOS PDFs (Dompdf)
========================================
El logo actualmente se ve descuadrado o desproporcionado. Dompdf tiene problemas con Flexbox y posicionamiento absoluto. Debemos usar propiedades CSS compatibles.
Modificar app/Views/admin/reportes/pdf.php Y app/Views/negocio/reportes/pdf.php:

- Envolver la cabecera en una tabla o en un div con `text-align: center;`.
- Para la imagen del logo, usar exactamente estos estilos en línea:
  `<img src="..." style="max-width: 150px; max-height: 80px; display: block; margin: 0 auto; object-fit: contain;">`
  (Dompdf respeta mejor `max-width` y `margin: 0 auto` que `width: 100%` o flexbox).
- Asegurarse de que la ruta de la imagen sea absoluta en el sistema de archivos (ej. FCPATH . '../writable/...'). Si el logo no existe, mostrar solo el texto.

========================================
ARCHIVOS A MODIFICAR
========================================
- app/Controllers/Admin/Reportes.php (agregar filtros a la consulta)
- app/Views/admin/reportes/index.php (UI de filtros y JS en cascada)
- app/Views/admin/reportes/pdf.php (corregir CSS del logo)
- app/Views/negocio/reportes/pdf.php (corregir CSS del logo)

========================================
AL FINALIZAR
========================================
- Lista de archivos modificados.
- Plan de prueba.
- No hagas commit.
