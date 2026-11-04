Proyecto: Katalo.pe, SaaS de catálogos digitales.
Stack: CodeIgniter 4, PHP 8, MySQL 8, MVC, en http://localhost/katalo/public.
BD: katalo_pe (20 tablas). Bloques 0 a 4 implementados: registro, login, perfil, categorías, productos, imágenes y catálogo público funcionando.
Implementa SOLO el Bloque 5: carrito y pedidos por WhatsApp. No implementes pagos, admin ni colaboradores.

========================================
ANTES DE ESCRIBIR CÓDIGO
========================================
Lee solo la sección "Bloque 5 — Pedidos por WhatsApp" de docs/requerimientos.md.
Lee solo las secciones de docs/database-dictionary.md para: pedidos, detalle_pedido, negocios, productos, imagenes_producto, logs_actividad.
No leas docs/schema-v2.sql completo salvo que necesites un CHECK o ENUM.
No inventes columnas. No modifiques el esquema ni crees migraciones.
No incluyas columnas generadas en allowedFields.

========================================
ALCANCE
========================================
RF: RF-19, RF-20, RF-21, RF-22.
HU: HU-17, HU-18, HU-19, HU-20, HU-21.

NO implementar:
- Límite de pedidos por IP (HU-46, va en Bloque 9). Solo guarda ip_cliente.
- Bloqueo de login (HU-45).
- Pagos, suscripciones, admin.
- Notificaciones al negocio por correo o push.

========================================
REGLAS DE NEGOCIO
========================================
- RN-08: un pedido debe tener al menos un producto en el detalle.
- RN-09 / RN-26: numero y codigo únicos por negocio. Formato del código: KTL-AAAA-NNNN.
- RN-10: teléfono del cliente con 7–15 dígitos (CHECK en BD).
- RN-17: cancelado no vuelve a otro estado; flujo unidireccional.
- RN-18: snapshot en detalle_pedido (producto_titulo, precio_unitario); no depende de cambios futuros del producto.
- RN-04 / RN-14: solo se puede pedir productos visibles, activos, no eliminados del negocio.
- RNF-08: todo el registro del pedido va en una transacción, incluyendo la lectura y actualización de siguiente_numero_pedido con FOR UPDATE.
- RNF-10: el panel de pedidos filtra por negocio_id de la sesión.

========================================
RNF APLICABLES
========================================
- RNF-05: CSRF en formularios del panel. En el catálogo público (POST /c/{slug}/pedido) no hay formulario tradicional pero igual valida en servidor.
- RNF-08: transacción obligatoria para insertar pedido + detalle + actualizar siguiente_numero_pedido.
- RNF-10: aislamiento por negocio_id.
- RNF-18: Nivel 1 en pedidos; Nivel 3 en detalle_pedido (solo created_at); Nivel 3 en logs_actividad.

========================================
FLUJO DEL CARRITO Y PEDIDO (RF-19, RF-20, HU-17, HU-18, HU-19)
========================================
El carrito vive en el frontend (localStorage o variable JS en la página del catálogo). No se guarda en sesión del servidor.

1. En el catálogo público (app/Views/catalogo/index.php), cada producto tiene botón "Agregar". Al hacer clic:
   - Agrega {id_producto, titulo, precio, cantidad} al carrito en localStorage.
   - Actualiza contador visible: "Ver mi pedido (N) - S/ XX.XX".
2. Al abrir el carrito, se muestra bottom sheet con:
   - Lista de items: título, cantidad (editable), subtotal.
   - Total general.
   - Input obligatorio: "Tu nombre".
   - Input obligatorio: "Tu teléfono / WhatsApp".
   - Input opcional: "Comentario o dirección de entrega".
   - Botón: "Enviar pedido por WhatsApp" (verde, grande).
3. Al presionar el botón:
   - Validar en frontend: carrito no vacío, nombre y teléfono presentes.
   - POST a /c/{slug}/pedido con JSON: {cliente_nombre, cliente_telefono, cliente_direccion, comentario, items: [{id_producto, cantidad}]}.
   - Backend procesa (ver transacción abajo) y devuelve JSON con {ok: true, codigo, mensaje_whatsapp, telefono_negocio}.
   - Si ok, abrir WhatsApp: window.open('https://wa.me/51' + telefono_negocio + '?text=' + encodeURIComponent(mensaje_whatsapp), '_blank').
   - Si error, mostrar mensaje claro (sin abrir WhatsApp).

========================================
TRANSACCIÓN DEL PEDIDO (RF-19, RF-20, HU-19)
========================================
El backend (POST /c/{slug}/pedido) debe hacer exactamente esto dentro de una transacción:

1. Validar: slug en v_negocios_publicos; items no vacío; nombre ≤ 150; teléfono 7–15 dígitos; dirección ≤ 255; comentario ≤ 500.
2. Verificar que cada id_producto pertenece al negocio, es visible, activo y no eliminado; obtener su titulo y precio_referencial actuales. Si un producto cambió de precio entre el carrito y el POST, usar el precio actual de BD (no el del carrito). Si un producto ya no está disponible, devolver error claro.
3. Calcular total = suma(cantidad × precio_unitario).
4. Iniciar transacción.
5. SELECT siguiente_numero_pedido FROM negocios WHERE id_negocio = ? FOR UPDATE.
6. numero = ese valor. codigo = CONCAT('KTL-', YEAR(CURDATE()), '-', LPAD(numero, 4, '0')).
7. UPDATE negocios SET siguiente_numero_pedido = siguiente_numero_pedido + 1 WHERE id_negocio = ?.
8. INSERT INTO pedidos (negocio_id, numero, codigo, cliente_nombre, cliente_telefono, cliente_direccion, comentario, total, estado, canal, ip_cliente) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'nuevo', 'whatsapp', ?).
9. Obtener id_pedido insertado.
10. INSERT INTO detalle_pedido (negocio_id, pedido_id, producto_id, producto_titulo, cantidad, precio_unitario, nota) para cada item. (subtotal es columna generada, no la insertes).
11. Registrar en logs_actividad la acción 'crear_pedido' con negocio_id, descripcion = codigo.
12. Confirmar transacción.
13. Construir mensaje de WhatsApp con este formato:
    Hola {nombre_negocio}, quiero hacer un pedido:
    
    Pedido {codigo}
    
    - 2x Pizza Familiar - S/ 70.00
    - 1x Gaseosa 1.5L - S/ 8.00
    
    Total: S/ 78.00
    
    Nombre: {cliente_nombre}
    Teléfono: {cliente_telefono}
    {comentario o dirección si aplica}
    
    Enviado desde Katalo.pe
14. Devolver JSON: {ok: true, codigo, mensaje_whatsapp, telefono_negocio, id_pedido}.

Si cualquier paso falla: rollback, mensaje claro, no abrir WhatsApp.

IMPORTANTE: el pedido se registra ANTES de abrir WhatsApp. Si el cliente no envía el mensaje por WhatsApp, el pedido igual queda en el panel con estado 'nuevo'. Esto cumple HU-19.

========================================
PANEL DE PEDIDOS (RF-21, RF-22, HU-20, HU-21)
========================================
1. GET /negocio/pedidos:
   - Lista pedidos del negocio de la sesión, ordenados por created_at DESC.
   - Filtros: Todos / Nuevo / Confirmado / En proceso / Entregado / Cancelado.
   - Cada fila muestra: codigo, cliente_nombre, cliente_telefono, total, estado (badge de color), fecha.
   - Acciones: ver detalle, avanzar estado.

2. GET /negocio/pedidos/{id}:
   - Verificar que pedido.negocio_id === session.negocio_id.
   - Mostrar cabecera (codigo, cliente, teléfono, dirección, comentario, total, estado, fecha) y detalle (producto_titulo, cantidad, precio_unitario, subtotal, nota).
   - Botón para cambiar estado según el flujo permitido.

3. POST /negocio/pedidos/{id}/estado:
   - Verificar pertenencia.
   - Validar transición permitida:
     - nuevo → confirmado, cancelado
     - confirmado → en_proceso, cancelado
     - en_proceso → entregado, cancelado
     - entregado → (sin cambio, solo lectura)
     - cancelado → (sin cambio)
   - Actualizar estado.
   - Registrar 'cambiar_estado_pedido' en logs_actividad con descripcion = "codigo: estado_anterior → estado_nuevo".

========================================
RUTAS (app/Config/Routes.php)
========================================
Públicas (sin AuthFilter):
POST /c/{slug}/pedido            → Catalogo\Pedido::crear

Protegidas (con AuthFilter):
GET  /negocio/pedidos                    → Negocio\Pedidos::index
GET  /negocio/pedidos/{id}               → Negocio\Pedidos::ver
POST /negocio/pedidos/{id}/estado        → Negocio\Pedidos::cambiarEstado

========================================
ARCHIVOS A CREAR / MODIFICAR
========================================
Controladores (app/Controllers/):
- Catalogo/Pedido.php (nuevo): recibe el POST del carrito, ejecuta la transacción, devuelve JSON.
- Negocio/Pedidos.php (nuevo): panel de pedidos.

Modelos (app/Models/):
- PedidoModel.php (nuevo): Nivel 1, soft delete, timestamps.
- DetallePedidoModel.php (nuevo): Nivel 3, $useTimestamps = false (solo created_at que la BD llena), no incluir subtotal en allowedFields (es generada).
- NegocioModel.php (ya existe): agregar método obtenerSiguienteNumeroConBloqueo($id_negocio) con SELECT ... FOR UPDATE, o manejar la consulta en el controlador si es más claro.
- ProductoModel.php (ya existe): agregar método obtenerDisponiblesPorIds($id_negocio, $ids).

Vistas:
- app/Views/catalogo/index.php (modificar): agregar carrito JS, bottom sheet, botones "Agregar", contador flotante, formulario de nombre/teléfono/dirección/comentario, botón WhatsApp.
- app/Views/negocio/pedidos/index.php (nuevo): listado con filtros.
- app/Views/negocio/pedidos/detalle.php (nuevo): detalle + acciones de estado.

Config:
- app/Config/Routes.php (agregar las rutas del bloque).
- app/Views/dashboard/index.php (agregar enlace "Mis Pedidos" al menú).

========================================
CONVENCIONES DE MODELOS
========================================
- PedidoModel: $useTimestamps = true, $createdField = 'created_at', $updatedField = 'modified_at', $useSoftDeletes = true, $deletedField = 'deleted_at'.
- DetallePedidoModel: $useTimestamps = false. NO incluir 'subtotal' en allowedFields (es columna GENERATED STORED).
- No incluir columnas generadas en allowedFields.

========================================
VISTAS DEL CARRITO
========================================
- Mobile-first.
- Contador flotante fijo abajo: "Ver mi pedido (N) - S/ XX.XX".
- Bottom sheet modal con overlay oscuro.
- Inputs grandes, botón verde "Enviar pedido por WhatsApp".
- Si carrito está vacío, botón deshabilitado.
- Mensaje de error claro si falta nombre o teléfono.
- Usar localStorage con clave 'katalo_carrito_{slug}'. Limpiar carrito después de enviar pedido exitosamente.
- JS vanilla, sin frameworks.

========================================
VISTAS DEL PANEL DE PEDIDOS
========================================
- Mobile-first.
- Listado en cards en móvil, tabla en desktop.
- Badge de color por estado: nuevo (rojo), confirmado (amarillo), en_proceso (azul), entregado (verde), cancelado (gris).
- Detalle con botones que solo muestren transiciones permitidas según estado actual.
- CSRF en formularios del panel.
- Escape de salida con esc().

========================================
SEGURIDAD
========================================
- Validación server-side completa.
- CSRF en formularios del panel.
- Escape de salida con esc().
- Verificar negocio_id del pedido antes de mostrar o cambiar estado.
- Validar transiciones de estado en servidor (no solo ocultar botones en HTML).
- No exponer SQL ni rutas internas en mensajes de error.
- Escapar el mensaje de WhatsApp correctamente con json_encode antes de devolverlo.

========================================
AL FINALIZAR
========================================
- Lista de archivos creados y modificados.
- Plan de prueba con los criterios de aceptación de HU-17, HU-18, HU-19, HU-20 y HU-21.
- No hagas commit.
