Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe (20 tablas). Bloques 0 a 6 implementados.
Implementa SOLO el Bloque 4.5: cambiar la URL del catálogo público de /c/{slug} a /{ciudad_slug}/{negocio_slug}.

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo las secciones de docs/database-dictionary.md para: ciudades, distritos, negocios, productos, categorias, imagenes_producto, suscripciones, planes, pedidos, detalle_pedido.
No modifiques el esquema ni crees migraciones. La tabla ciudades ya tiene columna slug.
No toques los Bloques 1, 2, 3, 5 ni 6 salvo los enlaces y el JS del carrito que apuntan al catálogo.

========================================
ALCANCE
========================================
Objetivo: que el catálogo público se acceda como:
  http://localhost/katalo/public/{ciudad_slug}/{negocio_slug}
Ejemplo:
  http://localhost/katalo/public/tacna/los-mouses
  http://localhost/katalo/public/moquegua/pizzeria-napoli

El slug del negocio sigue siendo único global (RN-02), pero la URL incluye la ciudad para dar contexto geográfico y preparar el Bloque 7 (multi-ciudad).

========================================
REGLAS
========================================
- La ciudad de la URL debe coincidir con la ciudad del negocio (negocios.distrito_id → distritos.ciudad_id → ciudades.slug).
- Si el slug del negocio existe pero la ciudad no coincide, devolver 404 amigable.
- Si el negocio no está en v_negocios_publicos, 404 amigable.
- Las rutas antiguas /c/{slug} deben redirigir con 301 a /{ciudad_slug}/{slug} para no romper enlaces compartidos.
- El endpoint de imágenes puede quedarse como /c/imagen/{id_imagen} (es global por id único) o cambiarse a /{ciudad_slug}/imagen/{id_imagen}. Elige una sola estrategia y documéntala. Recomendado: mantener /c/imagen/{id_imagen} para no duplicar lógica.

========================================
RUTAS (app/Config/Routes.php)
========================================
Públicas:
GET  /{ciudad}                             → Catalogo\Catalogo::ciudad (opcional: listado de negocios de la ciudad)
GET  /{ciudad}/{slug}                      → Catalogo\Catalogo::index
GET  /{ciudad}/{slug}/productos            → Catalogo\Catalogo::productos (JSON, si el Bloque 4 usa AJAX)
POST /{ciudad}/{slug}/pedido               → Catalogo\Pedido::crear
GET  /c/imagen/{id_imagen}                 → Catalogo\Imagen::ver (sin cambio)
GET  /c/{slug}                             → redirección 301 a /{ciudad_slug}/{slug}

IMPORTANTE: las rutas con {ciudad} y {slug} no deben colisionar con /auth, /negocio, /admin, /dashboard ni /c/imagen. Coloca las rutas del catálogo al final de Routes.php y usa un filtro o validación en el controlador para rechazar slugs reservados (auth, negocio, admin, dashboard, c, api, assets, favicon.ico).

========================================
CONTROLADOR Catalogo\Catalogo
========================================
Modificar app/Controllers/Catalogo/Catalogo.php:

index($ciudad_slug, $slug):
1. Validar que $ciudad_slug no sea una palabra reservada (auth, negocio, admin, dashboard, c, api, assets).
2. Buscar el negocio en v_negocios_publicos por slug.
3. Si no existe → no_disponible.
4. Obtener la ciudad del negocio: JOIN distritos → ciudades.
5. Si ciudades.slug !== $ciudad_slug → no_disponible.
6. Cargar productos, categorías y datos del negocio como en el Bloque 4.
7. Pasar $ciudad_slug y $slug a la vista para construir enlaces.

productos($ciudad_slug, $slug):
- Igual que en Bloque 4 pero validando ciudad.

Crear método ciudad($ciudad_slug):
- Opcional: listado de negocios públicos de esa ciudad. Si no se implementa, devolver 404 amigable.

========================================
CONTROLADOR Catalogo\Pedido
========================================
Modificar app/Controllers/Catalogo/Pedido.php:
- La firma cambia a crear($ciudad_slug, $slug).
- Validar ciudad y negocio igual que en Catalogo::index.
- El resto de la transacción no cambia (sigue usando negocio_id, FOR UPDATE, etc.).

========================================
VISTAS DEL CATÁLOGO
========================================
En app/Views/catalogo/index.php y producto_card.php:
- El botón "Agregar" y el JS del carrito deben usar la nueva URL base: /{ciudad_slug}/{slug}/pedido.
- El contador flotante y el bottom sheet no cambian.
- El footer "Creado con Katalo.pe" puede enlazar a /.
- Las imágenes siguen usando /c/imagen/{id_imagen}.

En app/Views/catalogo/no_disponible.php:
- Mensaje: "Este catálogo no está disponible en esta ciudad o el negocio ya no opera en Katalo.pe".

========================================
ENLACES EN EL PANEL
========================================
Modificar los lugares donde se construye la URL del catálogo público:
- app/Views/dashboard/index.php: si hay enlace "Ver mi catálogo", usar base_url($ciudad_slug . '/' . $negocio_slug).
- app/Views/negocio/perfil.php: si muestra la URL pública, usar la nueva forma.
- app/Controllers/Negocio/Perfil.php: si devuelve la URL pública, incluir ciudad_slug.
- Cualquier otro lugar que use base_url('c/' . $slug) debe actualizarse.

Para obtener ciudad_slug en el panel, agregar un método en NegocioModel:
- obtenerCiudadSlug($id_negocio): JOIN negocios → distritos → ciudades, devuelve ciudades.slug.

========================================
REDIRECCIÓN 301
========================================
GET /c/{slug}:
- Buscar negocio por slug en v_negocios_publicos.
- Si existe, obtener ciudad_slug y redirigir 301 a /{ciudad_slug}/{slug}.
- Si no existe, 404 amigable.

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Controladores:
- app/Controllers/Catalogo/Catalogo.php (modificar index, productos; agregar ciudad si se implementa).
- app/Controllers/Catalogo/Pedido.php (modificar crear).
- app/Controllers/Catalogo/Redireccion.php (nuevo: maneja /c/{slug} → 301).

Modelos:
- app/Models/NegocioModel.php (agregar obtenerCiudadSlug($id_negocio)).
- app/Models/NegocioPublicoModel.php (agregar obtenerCiudadSlugPorNegocio($slug) o incluir ciudad_slug en la consulta).

Vistas:
- app/Views/catalogo/index.php (actualizar URLs del carrito y enlaces).
- app/Views/catalogo/producto_card.php (si aplica).
- app/Views/catalogo/no_disponible.php (mensaje actualizado).
- app/Views/dashboard/index.php (enlace al catálogo con ciudad).
- app/Views/negocio/perfil.php (URL pública con ciudad).

Config:
- app/Config/Routes.php (agregar rutas nuevas, redirección y ordenar al final).

========================================
VALIDACIONES
========================================
- No permitir que {ciudad} sea auth, negocio, admin, dashboard, c, api, assets, favicon.ico. Si lo es, 404.
- No permitir que {slug} sea una palabra reservada.
- Si el negocio existe pero la ciudad no coincide → 404 amigable, no redirección.
- Si el negocio existe y la ciudad coincide → mostrar catálogo.
- Si el negocio no está público (suspendido, ciudad inactiva, suscripción vencida) → 404 amigable.

========================================
PRUEBAS MANUALES ESPERADAS
========================================
1. Abrir /tacna/los-mouses → catálogo carga.
2. Abrir /moquegua/los-mouses → 404 amigable (ciudad no coincide).
3. Abrir /c/los-mouses → redirección 301 a /tacna/los-mouses.
4. Agregar producto al carrito, enviar pedido → POST va a /tacna/los-mouses/pedido y el pedido se registra igual que antes.
5. En el dashboard, clic en "Ver mi catálogo" → abre /tacna/los-mouses.
6. Suspender el negocio (activo = 0) → /tacna/los-mouses muestra 404 amigable.
7. Ciudad inactiva (ciudades.activo = 0) → /tacna/los-mouses muestra 404 amigable.
8. Ruta /auth/login sigue funcionando (no colisiona con {ciudad}/{slug}).

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Confirmación de que las rutas /auth, /negocio, /admin y /dashboard siguen funcionando.
- Plan de prueba con los 8 casos de arriba.
- No hagas commit.
