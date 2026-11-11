Proyecto: Katalo.pe (CodeIgniter 4).
BD: katalo_pe. Bloques 0 a 9.1 implementados. La librería dompdf/dompdf ya fue instalada vía Composer.
Implementa el Bloque 8 DEFINITIVO: Logo, Colaboradores, Analytics, 4 Reportes con Gráficos y PDF.
No modifiques el esquema de la BD.

========================================
ALCANCE
========================================
1. Subida de Logo del negocio.
2. Gestión de Colaboradores (Plan Pro).
3. Configuración de Google Analytics (Plan Pro).
4. Módulo de Reportes (4 tipos: Ventas, Productos, Pedidos, Clientes) con gráficos web (Chart.js) y exportación a PDF (Dompdf con barras CSS).
5. Arreglar botones en el Dashboard principal.

========================================
1. BOTONES DEL DASHBOARD (app/Views/dashboard/index.php)
========================================
- Agregar botón "Reportes" (enlace a /negocio/reportes). Mostrar solo si el plan tiene 'tiene_reportes' = 1.
- Agregar botón "Colaboradores" (enlace a /negocio/colaboradores). Mostrar solo si el plan es Pro.
- Asegurar que el controlador Dashboard.php envíe las variables del plan a la vista.

========================================
2. LOGO DEL NEGOCIO
========================================
- Modificar app/Controllers/Negocio/Perfil.php: validar y subir archivo 'logo' (MIME real JPG/PNG/WebP, ≤ 2 MB) a writable/uploads/logos/{id_negocio}/. Guardar ruta en negocios.logo_ruta.
- Modificar app/Views/negocio/perfil.php: agregar campo de subida de imagen.
- Modificar app/Views/catalogo/index.php: mostrar el logo en la cabecera si existe.

========================================
3. COLABORADORES (Solo Plan Pro)
========================================
- Crear app/Controllers/Negocio/Colaboradores.php (index, crear, guardar, desactivar).
- Rutas correspondientes en Routes.php.
- Validar plan Pro y límite max_usuarios.
- Crear persona, usuario, asignar rol 'usuario_negocio' y 'colaborador' en negocio_usuarios.
- Vistas index.php y form.php.
- Enlace en layouts/panel.php.

========================================
4. GOOGLE ANALYTICS (Solo Plan Pro)
========================================
- Modificar app/Controllers/Negocio/Perfil.php: validar y guardar 'google_analytics_id' (formato G-XXXX o UA-XXXX).
- Modificar app/Views/negocio/perfil.php: agregar input visible solo si el plan tiene 'tiene_analytics' = 1.
- Modificar app/Views/catalogo/index.php: inyectar script de Google Analytics en el <head> si el ID existe.

========================================
5. MÓDULO DE REPORTES (Básico y Pro)
========================================
- Crear app/Controllers/Negocio/Reportes.php. Validar que el plan tenga 'tiene_reportes' = 1.
- Recibir parámetro `tipo` por GET (ventas, productos, pedidos, clientes). Por defecto: ventas.
- Implementar métodos en PedidoModel para:
  1. Ventas: Total vendido, pedidos, ticket promedio, ventas por día.
  2. Productos: Top 5 y Bottom 5 (por cantidad en detalle_pedido).
  3. Pedidos: Conteo agrupado por estado (nuevo, confirmado, en_proceso, entregado, cancelado).
  4. Clientes: Top 10 clientes (nombre, teléfono, cantidad de compras, total gastado).
- Filtro de fechas (start_date, end_date) por defecto el mes actual.

========================================
6. GRÁFICOS Y PDF
========================================
- VISTA WEB (index.php): 
  - Usar Chart.js vía CDN.
  - Crear un canvas y renderizar un gráfico distinto por pestaña (Barras para ventas, Dona para productos, Pastel para pedidos, Barras horizontales para clientes).
  - Usar pestañas de navegación.
- EXPORTACIÓN PDF (método pdf() en el controlador y vista pdf.php):
  - Usar Dompdf. 
  - Cabecera con Logo (usar FCPATH/WRITEPATH), nombre comercial y fechas.
  - Para simular gráficos, usar barras HTML/CSS (divs con width porcentual calculado en PHP).
  - El PDF debe adaptarse al tipo de reporte seleccionado.

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
- app/Controllers/Negocio/Reportes.php (nuevo)
- app/Controllers/Negocio/Colaboradores.php (nuevo)
- app/Controllers/Negocio/Perfil.php (modificar)
- app/Models/PedidoModel.php (modificar)
- app/Models/NegocioModel.php (modificar)
- app/Models/NegocioUsuarioModel.php (modificar)
- app/Views/dashboard/index.php (modificar)
- app/Views/layouts/panel.php (modificar)
- app/Views/negocio/reportes/index.php (nuevo)
- app/Views/negocio/reportes/pdf.php (nuevo)
- app/Views/negocio/colaboradores/index.php (nuevo)
- app/Views/negocio/colaboradores/form.php (nuevo)
- app/Views/negocio/perfil.php (modificar)
- app/Views/catalogo/index.php (modificar)
- app/Config/Routes.php (agregar rutas de reportes y colaboradores)

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Confirmación de que Dompdf y Chart.js están integrados.
- No hagas commit.
