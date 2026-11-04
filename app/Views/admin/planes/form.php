<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$precioActual       = old('precio_mensual', $plan['precio_mensual']);
$descripcionActual  = old('descripcion', $plan['descripcion'] ?? '');
$maxProductos       = old('max_productos', $plan['max_productos'] ?? '');
$maxImagenes        = old('max_imagenes_por_producto', $plan['max_imagenes_por_producto']);
$maxUsuarios        = old('max_usuarios', $plan['max_usuarios'] ?? '');
$maxPedidos         = old('max_pedidos_mes', $plan['max_pedidos_mes'] ?? '');

$dominioMarcado     = (string) old('tiene_dominio_propio', $plan['tiene_dominio_propio']) === '1';
$analyticsMarcado   = (string) old('tiene_analytics', $plan['tiene_analytics']) === '1';
$reportesMarcado    = (string) old('tiene_reportes', $plan['tiene_reportes']) === '1';
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Comercial</p>
            <h1>Editar plan</h1>
            <p class="subtitle">El nombre y el slug no se pueden modificar para no romper referencias.</p>
        </div>
    </div>

    <form action="<?= esc($accion, 'attr') ?>" method="post">
        <?= csrf_field() ?>

        <div class="grid-2">
            <div class="field">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" value="<?= esc($plan['nombre'], 'attr') ?>" disabled>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input type="text" id="slug" value="<?= esc($plan['slug'], 'attr') ?>" disabled>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="precio_mensual">Precio mensual (S/) <span aria-hidden="true">*</span></label>
                <input
                    type="number"
                    id="precio_mensual"
                    name="precio_mensual"
                    min="0"
                    step="0.01"
                    value="<?= esc((string) $precioActual, 'attr') ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="max_imagenes_por_producto">Máx. imágenes por producto <span aria-hidden="true">*</span></label>
                <input
                    type="number"
                    id="max_imagenes_por_producto"
                    name="max_imagenes_por_producto"
                    min="1"
                    max="255"
                    value="<?= esc((string) $maxImagenes, 'attr') ?>"
                    required
                >
            </div>
        </div>

        <div class="field">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" maxlength="255"><?= esc((string) $descripcionActual) ?></textarea>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="max_productos">Máx. productos</label>
                <input
                    type="number"
                    id="max_productos"
                    name="max_productos"
                    min="0"
                    placeholder="Vacío = ilimitado"
                    value="<?= esc((string) $maxProductos, 'attr') ?>"
                >
                <span class="hint">Deja vacío para representar ilimitado (NULL).</span>
            </div>

            <div class="field">
                <label for="max_usuarios">Máx. usuarios</label>
                <input
                    type="number"
                    id="max_usuarios"
                    name="max_usuarios"
                    min="0"
                    placeholder="Vacío = ilimitado"
                    value="<?= esc((string) $maxUsuarios, 'attr') ?>"
                >
                <span class="hint">Deja vacío para representar ilimitado (NULL).</span>
            </div>
        </div>

        <div class="field">
            <label for="max_pedidos_mes">Máx. pedidos por mes</label>
            <input
                type="number"
                id="max_pedidos_mes"
                name="max_pedidos_mes"
                min="0"
                placeholder="Vacío = ilimitado"
                value="<?= esc((string) $maxPedidos, 'attr') ?>"
            >
            <span class="hint">Deja vacío para representar ilimitado (NULL).</span>
        </div>

        <div class="section-title" style="margin-top:18px">
            <h2>Features</h2>
        </div>

        <div class="checkbox-row">
            <input type="hidden" name="tiene_dominio_propio" value="0">
            <input type="checkbox" id="tiene_dominio_propio" name="tiene_dominio_propio" value="1" <?= $dominioMarcado ? 'checked' : '' ?>>
            <label for="tiene_dominio_propio">Dominio propio</label>
        </div>
        <div class="checkbox-row">
            <input type="hidden" name="tiene_analytics" value="0">
            <input type="checkbox" id="tiene_analytics" name="tiene_analytics" value="1" <?= $analyticsMarcado ? 'checked' : '' ?>>
            <label for="tiene_analytics">Analytics</label>
        </div>
        <div class="checkbox-row">
            <input type="hidden" name="tiene_reportes" value="0">
            <input type="checkbox" id="tiene_reportes" name="tiene_reportes" value="1" <?= $reportesMarcado ? 'checked' : '' ?>>
            <label for="tiene_reportes">Reportes</label>
        </div>

        <div class="notice">
            Las suscripciones existentes conservan su precio pactado aunque cambies el precio del plan.
        </div>

        <div class="form-actions actions">
            <button type="submit" class="btn">Guardar cambios</button>
            <a class="btn btn-secondary" href="<?= base_url('admin/planes') ?>">Cancelar</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
