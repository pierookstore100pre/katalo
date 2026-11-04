Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe (20 tablas). Bloques 0 a 3 implementados: registro, login, perfil, categorías, productos e imágenes funcionando.
Implementa SOLO el Bloque 4: catálogo público por slug. No implementes carrito, pedidos, pagos ni admin.

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo la sección "Bloque 4 — Catálogo público" de docs/requerimientos.md.
Lee solo las secciones de docs/database-dictionary.md para: negocios, distritos, ciudades, productos, categorias, imagenes_producto, suscripciones, planes, y las vistas v_negocios_publicos y v_productos_publicos.
No leas docs/schema-v2.sql completo salvo que necesites un CHECK o ENUM.
No inventes columnas. No modifiques el esquema ni crees migraciones.
No uses la sesión: este bloque es público. No leas negocio_id desde la sesión.

========================================
ALCANCE
========================================
RF: RF-14, RF-15, RF-16, RF-17, RF-18.
HU: HU-12, HU-13, HU-14, HU-15, HU-16.

NO implementar: carrito, botón de WhatsApp con pedido, pedidos, admin.

========================================
REGLAS DE NEGOCIO
========================================
- RN-04 / RN-14: solo productos con visible = 1, estado = 'activo' y deleted_at IS NULL.
- RN-21: solo negocios activos, no eliminados, de ciudad activa y con suscripción vigente (prueba o activa con fecha_fin NULL o >= CURDATE()).
- RN-12: si la ciudad está inactiva, el negocio no aparece aunque esté activo.
- RN-27: si el slug fue eliminado lógicamente, no debe ser accesible.
- RNF-10: no aplica aquí porque es público, pero usa las vistas v_negocios_publicos y v_productos_publicos que ya aplican RN-21.

========================================
RNF APLICABLES
========================================
- RNF-02: diseño mobile-first.
- RNF-03: catálogo carga en < 3 s; imágenes con lazy load.
- RNF-05: escape de salida con esc() en todas las vistas.
- RNF-19: mensajes en español.
- No hay CSRF porque no hay formularios que modifiquen datos en este bloque.

========================================
FLUJO DEL CATÁLOGO PÚBLICO
========================================
1. GET /c/{slug}:
   - Buscar el negocio en v_negocios_publicos por slug.
   - Si no existe → mostrar vista 404 amigable: "Este catálogo no está disponible o el negocio ya no opera en Katalo.pe".
   - Si existe → cargar:
     - Datos del negocio: nombre_comercial, descripcion, direccion, distrito, ciudad, telefono_principal, horario_texto, latitud, longitud.
     - Productos desde v_productos_publicos filtrados por id_negocio.
     - Categorías activas del negocio que tengan al menos un producto visible (para no mostrar filtros vacíos).
   - Renderizar la vista con estos datos.

2. GET /c/{slug}/productos?categoria={slug}&q={texto}:
   - Endpoint opcional (AJAX) para filtrar y buscar sin recargar la página.
   - Devuelve JSON con los productos filtrados.
   - Si no se implementa AJAX, el filtro se puede resolver en el mismo GET /c/{slug} con query params. Elige una sola estrategia y documéntala en el controlador.
   - Filtro por categoría: comparar por categorias.slug del negocio.
   - Búsqueda: LIKE sobre titulo y descripcion, con escape de comodines (% y _).

========================================
SERVIR IMÁGENES
========================================
Las imágenes están en writable/uploads/productos/{negocio_id}/{id_producto}/, fuera de public/.
No las expongas directamente. Crea un endpoint controlado:

GET /c/imagen/{id_imagen}:
- Buscar la imagen en imagenes_producto (deleted_at IS NULL).
- Verificar que el producto asociado sea visible, activo y no eliminado, y que el negocio esté en v_negocios_publicos.
- Leer el archivo desde writable/uploads/productos/{negocio_id}/{id_producto}/{nombre_archivo}.
- Enviar con el MIME correcto y cabeceras de caché (Cache-Control: public, max-age=86400).
- Si no existe o no cumple, devolver 404.

========================================
RUTAS (app/Config/Routes.php)
========================================
GET /c/{slug}                → Catalogo\Catalogo::index
GET /c/{slug}/productos      → Catalogo\Catalogo::productos (JSON, opcional si usas AJAX)
GET /c/imagen/{id_imagen}    → Catalogo\Imagen::ver

Todas son públicas: NO aplicar AuthFilter.

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Controladores (app/Controllers/Catalogo/):
- Catalogo.php
- Imagen.php

Modelos (app/Models/):
- NegocioPublicoModel.php (nuevo): usa la vista v_negocios_publicos como $table, sin escritura.
- ProductoPublicoModel.php (nuevo): usa la vista v_productos_publicos como $table, sin escritura.
- CategoriaModel.php (ya existe): agregar método listarActivasConProductosVisibles($id_negocio).

Vistas (app/Views/catalogo/):
- layout.php (layout público, sin menú del panel, con footer "Creado con Katalo.pe")
- index.php (catálogo)
- producto_card.php (parcial reutilizable para cada producto)
- no_disponible.php (vista 404 amigable)

Config:
- app/Config/Routes.php (agregar rutas).
- No tocar app/Config/Filters.php.

========================================
DISEÑO DE LAS VISTAS
========================================
- Mobile-first.
- Cabecera: nombre del negocio, dirección, distrito, horario, teléfono (botón WhatsApp de contacto general, sin carrito).
- Buscador: input de texto con placeholder "Buscar en el catálogo...".
- Categorías: chips deslizables horizontalmente (Todos, Pizzas, Bebidas...). Solo activas con productos visibles.
- Productos: tarjetas con imagen principal (lazy load), título, descripción corta, precio y un botón "+" deshabilitado o con mensaje "Próximamente" (el carrito va en el Bloque 5; no lo implementes aquí).
- Imágenes: usar el endpoint /c/imagen/{id_imagen}, no la ruta física.
- Estados: si no hay productos, mostrar mensaje "Este negocio aún no tiene productos publicados".
- Footer: "Creado con Katalo.pe" con enlace a /.
- Todas las salidas con esc().
- Sin Bootstrap ni frameworks; CSS propio mobile-first.

========================================
SEGURIDAD Y RENDIMIENTO
========================================
- Escape de salida con esc() en todos los campos.
- Búsqueda: escapar % y _ en LIKE para evitar inyección de comodines; usar bindings.
- No exponer writable/ directamente.
- Cache-Control en imágenes.
- Índices: usar los que ya existen en productos (negocio_id, visible, estado, deleted_at) y categorias (negocio_id, activo). No crear índices nuevos.
- Si la búsqueda por palabra clave se resuelve en PHP sobre el array ya cargado, está bien para el MVP; documenta que si el catálogo crece se migrará a FULLTEXT.

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Plan de prueba con los criterios de aceptación de HU-12, HU-13, HU-14, HU-15 y HU-16.
- No hagas commit.
