Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe. Bloques 0 a 7.5 implementados.
Implementa SOLO el ajuste 7.5.1: mostrar el contenido exacto en el panel de moderación y guardar el detalle en logs_actividad.datos.
No modifiques el esquema ni crees migraciones. La columna logs_actividad.datos (JSON) ya existe.

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo las secciones de docs/database-dictionary.md para: productos, imagenes_producto, negocios, categorias, logs_actividad.
No leas docs/schema-v2.sql completo.
No modifiques el esquema.

========================================
ALCANCE
========================================
1. ModerationService debe devolver las palabras detectadas y el texto exacto de los campos marcados.
2. registrarMarcado debe guardar en logs_actividad.datos un JSON con:
   {
     "campos": {"descripcion": "texto exacto", "titulo": "..."},
     "palabras_detectadas": ["spam", "xxx"]
   }
3. El panel /admin/moderacion debe mostrar el contenido completo del producto/categoría/negocio marcado, no solo el motivo.
4. El admin debe ver las palabras detectadas en cada ítem.

========================================
MODIFICAR app/Services/ModerationService.php
========================================
- contieneContenidoBloqueado(string $texto): bool → mantener.
- evaluarCampos(array $datos): array → devolver:
  [
    'bloqueado' => bool,
    'campos' => ['descripcion', 'titulo'],   // nombres de campos afectados
    'textos' => ['descripcion' => 'texto exacto', 'titulo' => '...'], // valores
    'palabras' => ['spam', 'xxx'],           // palabras que dispararon
  ]
- registrarMarcado(...) → guardar en logs_actividad:
  - descripcion: "Contenido marcado automáticamente. Campos afectados: descripcion, titulo"
  - datos: json_encode([
      'campos' => ['descripcion' => 'texto exacto', ...],
      'palabras_detectadas' => ['spam', ...],
    ])
  - No cambiar el resto de columnas del log.

========================================
MODIFICAR app/Controllers/Admin/Moderacion.php
========================================
- En index(), al cargar productos marcados, incluir:
  - El producto completo: titulo, descripcion, precio_referencial, categoria_id, nombre de categoría, imagen principal (id_imagen).
  - El log más reciente con accion = 'contenido_marcado' para ese producto, incluyendo datos JSON decodificado.
- Hacer lo mismo para categorías y negocios marcados.
- Para imágenes recientes, incluir id_imagen, producto_titulo, negocio_nombre, created_at.

========================================
MODIFICAR app/Views/admin/moderacion/index.php
========================================
Para cada producto marcado, mostrar:
- Título.
- Negocio (nombre_comercial).
- Categoría.
- Descripción completa (si supera 300 caracteres, mostrar los primeros 300 con un enlace "ver más" que expanda el resto con JS vanilla).
- Precio.
- Miniatura de la imagen principal usando /c/imagen/{id_imagen} (si existe).
- Palabras detectadas: mostrar como badges rojos (leer de datos.palabras_detectadas).
- Botones: Aprobar, Rechazar, Ver negocio (enlace a /admin/negocios/{id}).

Para cada categoría marcada:
- Nombre, negocio, descripción completa, palabras detectadas, botones Aprobar/Rechazar.

Para cada negocio marcado:
- Nombre comercial, descripción completa, palabras detectadas, botones Aprobar/Suspender.

Para cada imagen reciente:
- Miniatura, producto, negocio, fecha, botones Aprobar/Rechazar.

Si datos está vacío o no es JSON válido, mostrar "Sin detalle disponible" sin romper la vista.

========================================
NO MODIFICAR
========================================
- No tocar la lógica de aprobar/rechazar (solo agregar visualización).
- No cambiar rutas.
- No cambiar el esquema.
- No modificar el layout admin.

========================================
PRUEBAS MANUALES
========================================
1. Crear producto con descripción "Este es un producto spam de prueba".
2. Verificar en phpMyAdmin que logs_actividad.datos contiene:
   {"campos":{"descripcion":"Este es un producto spam de prueba"},"palabras_detectadas":["spam"]}
3. Abrir /admin/moderacion → ver el producto con:
   - Descripción completa visible.
   - Badge "spam".
   - Botones Aprobar/Rechazar.
4. Aprobar → el producto se publica.
5. Crear otro producto con palabra bloqueada en título y descripción → verificar que ambos textos aparecen y ambas palabras se listan.
6. Verificar que las categorías y negocios marcados también muestran su contenido.
7. Verificar que imágenes recientes siguen funcionando.

========================================
AL FINALIZAR
========================================
- Lista de archivos modificados.
- Confirmación de que la vista no rompe si datos está vacío.
- No hagas commit.
