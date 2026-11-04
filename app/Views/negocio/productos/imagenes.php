<?= $this->extend('layouts/panel') ?>

<?= $this->section('styles') ?>
<style>
    .plan-meter {
        margin: 0 0 16px;
        padding: 12px 14px;
        border: 1px solid #ccfbf1;
        border-radius: 13px;
        background: #f0fdfa;
        color: #115e59;
        font-size: 0.88rem;
    }

    .plan-meter strong { color: #0f766e; }

    .upload-card {
        margin-bottom: 18px;
        padding: 14px;
        border: 1px dashed #99f6e4;
        border-radius: 14px;
        background: #f8fffd;
    }

    .upload-card form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .upload-card input[type="file"] {
        flex: 1 1 220px;
        min-width: 0;
        padding: 9px 10px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: #fff;
        font: inherit;
        font-size: 0.9rem;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
        gap: 12px;
        margin-top: 16px;
    }

    .gallery-item {
        padding: 8px;
        border: 1px solid #e6efec;
        border-radius: 13px;
        background: #fff;
    }

    .gallery-thumb {
        display: grid;
        place-items: center;
        width: 100%;
        aspect-ratio: 1 / 1;
        overflow: hidden;
        border-radius: 10px;
        background: #f1f5f9;
        color: #94a3b8;
        font-size: 0.75rem;
        font-weight: 700;
        text-align: center;
    }

    .gallery-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .gallery-info {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 7px;
    }

    .gallery-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .gallery-actions form { display: inline; }

    .badge-principal {
        background: #ccfbf1;
        color: #115e59;
    }

    @media (min-width: 640px) {
        .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$idProducto = (int) $producto['id_producto'];
$limiteAlcanzado = $maxImagenes !== null && $totalImagenes >= (int) $maxImagenes;
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Imágenes</p>
            <h1><?= esc($producto['titulo']) ?></h1>
            <p class="subtitle">Gestiona la galería y elige la imagen principal del producto.</p>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px">
            <a class="btn btn-secondary" href="<?= base_url('negocio/productos/editar/' . $idProducto) ?>">Editar producto</a>
            <a class="btn btn-secondary" href="<?= base_url('negocio/productos') ?>">Volver</a>
        </div>
    </div>

    <div class="plan-meter">
        <?php if ($plan !== null): ?>
            Plan <strong><?= esc($plan['nombre']) ?></strong> ·
        <?php endif; ?>
        <?php if ($maxImagenes === null): ?>
            <strong><?= (int) $totalImagenes ?></strong> imágenes · ilimitado
        <?php else: ?>
            <strong><?= (int) $totalImagenes ?></strong> de <strong><?= (int) $maxImagenes ?></strong> imágenes
        <?php endif; ?>
    </div>

    <?php if ($limiteAlcanzado): ?>
        <div class="alert alert-error">
            Alcanzaste el límite de imágenes de tu plan. Elimina una imagen o mejora tu plan para subir más.
        </div>
    <?php else: ?>
        <div class="upload-card">
            <form
                action="<?= base_url('negocio/productos/' . $idProducto . '/imagenes') ?>"
                method="post"
                enctype="multipart/form-data"
            >
                <?= csrf_field() ?>
                <input
                    type="file"
                    name="imagen"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    required
                >
                <button type="submit" class="btn btn-small">Subir imagen</button>
            </form>
            <p class="hint">Formatos permitidos: JPG, PNG o WebP. Tamaño máximo: 2 MB.</p>
        </div>
    <?php endif; ?>

    <?php if ($imagenes === []): ?>
        <div class="empty-state">
            <h2>Aún no hay imágenes</h2>
            <p>Sube la primera imagen para mostrarla en tu catálogo.</p>
        </div>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($imagenes as $imagen): ?>
                <?php
                $idImagen = (int) $imagen['id_imagen'];
                $esPrincipal = (int) $imagen['es_principal'] === 1;
                ?>
                <div class="gallery-item">
                    <div class="gallery-thumb">
                        <?php if (! empty($imagen['data_uri'])): ?>
                            <img
                                src="<?= esc($imagen['data_uri'], 'attr') ?>"
                                alt="Imagen del producto <?= esc($producto['titulo'], 'attr') ?>"
                            >
                        <?php else: ?>
                            Imagen no disponible
                        <?php endif; ?>
                    </div>

                    <div class="gallery-info">
                        <?php if ($esPrincipal): ?>
                            <span class="badge badge-principal">Principal</span>
                        <?php endif; ?>
                        <span class="badge badge-hidden">Orden <?= (int) $imagen['orden'] ?></span>
                    </div>

                    <div class="gallery-actions">
                        <?php if (! $esPrincipal): ?>
                            <form action="<?= base_url('negocio/productos/' . $idProducto . '/imagenes/' . $idImagen . '/principal') ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-secondary btn-small">Hacer principal</button>
                            </form>
                        <?php endif; ?>

                        <form action="<?= base_url('negocio/productos/' . $idProducto . '/imagenes/' . $idImagen . '/eliminar') ?>" method="post">
                            <?= csrf_field() ?>
                            <button
                                type="submit"
                                class="btn btn-danger btn-small"
                                onclick="return confirm('¿Eliminar esta imagen?');"
                            >Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
