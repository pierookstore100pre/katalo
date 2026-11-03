<?= $this->extend('layouts/panel') ?>

<?= $this->section('content') ?>
<?php
$errors = validation_errors();
$nombre = old('nombre', $categoria['nombre'] ?? '');
$slug = old('slug', $categoria['slug'] ?? '');
$descripcion = old('descripcion', $categoria['descripcion'] ?? '');
$padreSeleccionado = (string) old('categoria_padre_id', $categoria['categoria_padre_id'] ?? '');
$activoEnviado = old('activo_enviado');
$activoMarcado = $activoEnviado !== null ? old('activo') !== null : (bool) $activoPorDefecto;
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Categorías</p>
            <h1><?= $esEdicion ? 'Editar categoría' : 'Nueva categoría' ?></h1>
            <p class="subtitle">
                <?= $esEdicion ? 'Actualiza los datos de esta categoría.' : 'Agrega una categoría o subcategoría a tu catálogo.' ?>
            </p>
        </div>
        <a class="btn btn-secondary" href="<?= base_url('negocio/categorias') ?>">Volver</a>
    </div>

    <form action="<?= esc($accion, 'attr') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="grid-2">
            <div class="field">
                <label for="nombre">Nombre</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    value="<?= esc($nombre) ?>"
                    maxlength="100"
                    required
                    class="<?= isset($errors['nombre']) ? 'input-error' : '' ?>"
                >
                <?php if (isset($errors['nombre'])): ?>
                    <p class="field-error"><?= esc($errors['nombre']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="slug">Slug</label>
                <input
                    type="text"
                    id="slug"
                    name="slug"
                    value="<?= esc($slug) ?>"
                    maxlength="120"
                    pattern="[a-z0-9]+(-[a-z0-9]+)*"
                    required
                    class="<?= isset($errors['slug']) ? 'input-error' : '' ?>"
                >
                <span class="hint">Minúsculas, números y guiones. Único dentro de tu negocio.</span>
                <?php if (isset($errors['slug'])): ?>
                    <p class="field-error"><?= esc($errors['slug']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="descripcion">Descripción</label>
            <textarea
                id="descripcion"
                name="descripcion"
                maxlength="255"
                placeholder="Descripción opcional de la categoría"
                class="<?= isset($errors['descripcion']) ? 'input-error' : '' ?>"
            ><?= esc($descripcion) ?></textarea>
            <?php if (isset($errors['descripcion'])): ?>
                <p class="field-error"><?= esc($errors['descripcion']) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="categoria_padre_id">Categoría padre</label>
            <select
                id="categoria_padre_id"
                name="categoria_padre_id"
                class="<?= isset($errors['categoria_padre_id']) ? 'input-error' : '' ?>"
            >
                <option value="">Sin categoría padre</option>
                <?php foreach ($padres as $padre): ?>
                    <option
                        value="<?= esc($padre['id_categoria'], 'attr') ?>"
                        <?= set_select('categoria_padre_id', (string) $padre['id_categoria'], $padreSeleccionado === (string) $padre['id_categoria']) ?>
                    >
                        <?= esc(str_repeat('— ', (int) $padre['nivel']) . $padre['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="hint">Opcional. Solo se muestran categorías de tu negocio.</span>
            <?php if (isset($errors['categoria_padre_id'])): ?>
                <p class="field-error"><?= esc($errors['categoria_padre_id']) ?></p>
            <?php endif; ?>
        </div>

        <input type="hidden" name="activo_enviado" value="1">
        <div class="checkbox-row">
            <input type="checkbox" id="activo" name="activo" value="1" <?= $activoMarcado ? 'checked' : '' ?>>
            <label for="activo">Categoría activa</label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn"><?= $esEdicion ? 'Guardar cambios' : 'Crear categoría' ?></button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
    const nombre = document.getElementById('nombre');
    const slug = document.getElementById('slug');

    if (!nombre || !slug) {
        return;
    }

    let slugEditado = slug.value.trim() !== '';

    slug.addEventListener('input', () => {
        slugEditado = true;
    });

    nombre.addEventListener('input', () => {
        if (slugEditado) {
            return;
        }

        slug.value = nombre.value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
})();
</script>
<?= $this->endSection() ?>
