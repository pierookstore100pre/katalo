# Plan de pruebas manuales — Aislamiento entre negocios (HU-47)

Objetivo: confirmar que los datos de un negocio nunca son visibles ni modificables
desde otro negocio, y que el administrador conserva la visión global.

## Preparación

1. Crear dos negocios de prueba: **A** y **B**, cada uno con su propio usuario propietario.
2. En el negocio A crear al menos una categoría, un producto y un pedido.
3. En el negocio B crear al menos una categoría, un producto y un pedido.
4. Anotar los identificadores (`id_categoria`, `id_producto`, `id_pedido`) de cada negocio.

## Casos de prueba

### 1. Producto con categoría de otro negocio

- Iniciar sesión como propietario de A.
- Intentar crear o editar un producto de A enviando como `categoria_id` una categoría de B.
- **Resultado esperado:** la validación rechaza la operación y no se crea ni actualiza el producto.

### 2. Acceso a la edición de un producto de otro negocio

- Iniciar sesión como propietario de A.
- Abrir `GET /negocio/productos/editar/{id_producto_de_B}`.
- **Resultado esperado:** la aplicación deniega el acceso y muestra un mensaje de error.
- Repetir con un `id` inexistente y confirmar que tampoco se filtra información.

### 3. Acceso al detalle de un pedido de otro negocio

- Iniciar sesión como propietario de A.
- Abrir `GET /negocio/pedidos/{id_pedido_de_B}`.
- **Resultado esperado:** la aplicación deniega el acceso y no muestra datos del pedido de B.

### 4. Cambio de estado de un pedido de otro negocio

- Iniciar sesión como propietario de A.
- Enviar una petición de cambio de estado para `{id_pedido_de_B}` (por ejemplo, confirmar o entregar).
- **Resultado esperado:** la aplicación deniega la operación y el pedido de B no cambia de estado.

### 5. Catálogo público

- Abrir `/tacna/slug-A`.
- **Resultado esperado:** se muestran únicamente categorías y productos del negocio A.
- Verificar que ningún producto, categoría ni dato de B aparece en la página pública de A.

### 6. Visión del administrador

- Iniciar sesión como administrador.
- Abrir `/admin/negocios`.
- **Resultado esperado:** se muestran los negocios A y B sin restricciones de tenant.

## Verificación automatizada de solo lectura

Ejecutar:

```bash
php spark katalo:verificar-aislamiento
```

El comando revisa inconsistencias de negocio en productos, detalle de pedidos,
propietarios activos, suscripciones vigentes e imágenes principales.

- Si todas las verificaciones están en **OK**, el código de salida es `0`.
- Si alguna verificación está en **FALLA**, el código de salida es `1`.

## Criterio de aceptación

- Todos los casos de acceso cruzado deben ser denegados.
- El catálogo público no debe mezclar datos entre negocios.
- El administrador debe seguir viendo ambos negocios.
- `php spark katalo:verificar-aislamiento` debe terminar con todas las verificaciones en OK.
