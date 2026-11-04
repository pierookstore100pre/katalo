<?php
$titulo           = (string) ($producto['titulo'] ?? '');
$descripcion      = trim((string) ($producto['descripcion'] ?? ''));
$descripcionCorta = $descripcion !== '' && mb_strlen($descripcion) > 90
    ? mb_substr($descripcion, 0, 90) . '…'
    : $descripcion;
$categoria        = (string) ($producto['categoria_nombre'] ?? '');
$imagenId         = $producto['imagen_id'] ?? null;
$precio           = number_format((float) ($producto['precio_referencial'] ?? 0), 2, '.', ',');
$precioNumerico   = number_format((float) ($producto['precio_referencial'] ?? 0), 2, '.', '');
$idProducto       = (int) ($producto['id_producto'] ?? 0);
?>
<article class="product-card">
    <div class="product-media">
        <?php if ($imagenId !== null): ?>
            <img
                src="<?= esc(base_url('c/imagen/' . (int) $imagenId), 'attr') ?>"
                alt="<?= esc($titulo, 'attr') ?>"
                loading="lazy"
                decoding="async"
            >
        <?php else: ?>
            <span class="product-no-image">Sin imagen</span>
        <?php endif; ?>
    </div>

    <div class="product-info">
        <?php if ($categoria !== ''): ?>
            <span class="product-category"><?= esc($categoria) ?></span>
        <?php endif; ?>

        <h2 class="product-title"><?= esc($titulo) ?></h2>

        <?php if ($descripcionCorta !== ''): ?>
            <p class="product-desc"><?= esc($descripcionCorta) ?></p>
        <?php endif; ?>

        <div class="product-footer">
            <span class="product-price">S/ <?= esc($precio) ?></span>
            <button
                type="button"
                class="product-add"
                data-id="<?= esc((string) $idProducto, 'attr') ?>"
                data-titulo="<?= esc($titulo, 'attr') ?>"
                data-precio="<?= esc($precioNumerico, 'attr') ?>"
                aria-label="Agregar <?= esc($titulo, 'attr') ?> al pedido"
            >+</button>
        </div>
    </div>
</article>
