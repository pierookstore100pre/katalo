Proyecto: Katalo.pe (CodeIgniter 4).
BD: katalo_pe. Bloques 0 a 8.8 implementados.
Implementa el Bloque 8.9: Ajustes de alineación en Reportes y corrección definitiva del logo en PDF.
No modifiques el esquema de la BD.

========================================
ALCANCE
========================================
1. Centrar las tarjetas de resumen en la web y en el PDF.
2. Centrar los datos de las columnas (Negocios Activos, Pedidos del Mes) en las tablas de la web y del PDF.
3. Corregir el recorte (descuadre) del logo en los PDF generados por Dompdf.

========================================
1. ALINEACIÓN EN LA VISTA WEB (app/Views/admin/reportes/index.php)
========================================
- En las tarjetas de resumen superiores (MRR ACTUAL, NEGOCIOS ACTIVOS, etc.):
  - Agregar la clase `text-center` o el estilo `text-align: center;` al contenedor de cada tarjeta.
- En la tabla comparativa:
  - Agregar `text-align: center;` a los `<th>` y `<td>` de las columnas "Negocios Activos" y "Pedidos del Mes".
  - (Opcional: centrar toda la tabla si se ve mejor, pero respetando la alineación a la izquierda de los nombres de Ciudades/Distritos).

========================================
2. ALINEACIÓN EN EL PDF (app/Views/admin/reportes/pdf.php y negocio/reportes/pdf.php)
========================================
- En las tarjetas de resumen (TOTAL NEGOCIOS, MRR, TOTAL PEDIDOS):
  - Cambiar `text-align: left;` por `text-align: center;` en los divs o tablas que las contienen.
- En las tablas comparativas:
  - Agregar `text-align: center;` a los `<th>` y `<td>` de "Negocios Activos" y "Pedidos del Mes".

========================================
3. CORRECCIÓN DEL LOGO EN EL PDF (Dompdf)
========================================
El logo actualmente se ve recortado (la "K" se corta abajo). Dompdf a veces calcula mal el alto de las imágenes si no se le da una proporción exacta.
En las vistas de PDF (admin y negocio):
- Buscar la etiqueta `<img>` del logo.
- Reemplazar sus estilos por EXACTAMENTE estos:
  `<img src="..." style="display: block; margin: 0 auto; max-width: 150px; height: auto; max-height: 100px; object-fit: contain;">`
- IMPORTANTE: En PHP, asegurarse de que la ruta del logo esté correctamente codificada. Si el logo es un PNG o JPG en `writable/uploads/logos/`, la ruta pasada al HTML debe ser absoluta en el servidor, por ejemplo: 
  `base64_encode(file_get_contents(WRITEPATH . '../uploads/logos/' . $ruta_imagen))` 
  (Usar base64 es la forma más segura y limpia de evitar problemas de rutas en Dompdf).

========================================
ARCHIVOS A MODIFICAR
========================================
- app/Views/admin/reportes/index.php
- app/Views/admin/reportes/pdf.php
- app/Views/negocio/reportes/pdf.php

========================================
AL FINALIZAR
========================================
- Lista de archivos modificados.
- No hagas commit.
