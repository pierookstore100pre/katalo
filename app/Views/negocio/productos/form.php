<?= $this->extend('layouts/panel') ?>

<?= $this->section('content') ?>
<?php
$errors = validation_errors();
$titulo = old('titulo', $producto['titulo'] ?? '');
$categoriaActual = (string) old('categoria_id', $producto['categoria_id'] ?? '');
$precio = old('precio_referencial', $producto['precio_referencial'] ?? '');
$descripcion = old('descripcion', $producto['descripcion'] ?? '');
$estado = old('estado', $producto['estado'] ?? 'activo');
$visibleEnviado = old('visible_enviado');
$visibleMarcado = $visibleEnviado !== null
    ? old('visible') !== null
    : ($producto === null || (int) $producto['visible'] === 1);
$destacadoEnviado = old('destacado_enviado');
$destacadoMarcado = $destacadoEnviado !== null
    ? old('destacado') !== null
    : ($producto !== null && (int) $producto['destacado'] === 1);
$categoriaEnLista = false;

foreach ($categorias as $categoria) {
    if ((string) $categoria['id_categoria'] === $categoriaActual) {
        $categoriaEnLista = true;
        break;
    }
}
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Productos</p>
            <h1><?= $esEdicion ? 'Editar producto' : 'Nuevo producto' ?></h1>
            <p class="subtitle">
                <?= $esEdicion ? 'Actualiza los datos de tu producto.' : 'Completa los datos para agregar un producto a tu catálogo.' ?>
            </p>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px">
            <?php if ($esEdicion): ?>
                <a class="btn btn-secondary" href="<?= base_url('negocio/productos/' . (int) $producto['id_producto'] . '/imagenes') ?>">Gestionar imágenes</a>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= base_url('negocio/productos') ?>">Volver</a>
        </div>
    </div>

    <?php if ($categorias === []): ?>
        <div class="alert alert-error">
            No tienes categorías activas. <a href="<?= base_url('negocio/categorias/crear') ?>">Crea una categoría</a> antes de registrar productos.
        </div>
    <?php elseif ($esEdicion && $categoriaActual !== '' && ! $categoriaEnLista): ?>
        <div class="alert alert-error">
            La categoría actual está inactiva. Selecciona una categoría activa para guardar los cambios.
        </div>
    <?php endif; ?>

    <form action="<?= esc($accion, 'attr') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="field">
            <label for="titulo">Título</label>
            <input
                type="text"
                id="titulo"
                name="titulo"
                value="<?= esc($titulo) ?>"
                maxlength="200"
                required
                class="<?= isset($errors['titulo']) ? 'input-error' : '' ?>"
            >
            <?php if (isset($errors['titulo'])): ?>
                <p class="field-error"><?= esc($errors['titulo']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="categoria_id">Categoría</label>
                <select
                    id="categoria_id"
                    name="categoria_id"
                    required
                    class="<?= isset($errors['categoria_id']) ? 'input-error' : '' ?>"
                >
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option
                            value="<?= esc($categoria['id_categoria'], 'attr') ?>"
                            <?= set_select('categoria_id', (string) $categoria['id_categoria'], $categoriaActual === (string) $categoria['id_categoria']) ?>
                        >
                            <?= esc(str_repeat('— ', (int) $categoria['nivel']) . $categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['categoria_id'])): ?>
                    <p class="field-error"><?= esc($errors['categoria_id']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="precio_referencial">Precio referencial</label>
                <input
                    type="number"
                    id="precio_referencial"
                    name="precio_referencial"
                    value="<?= esc($precio) ?>"
                    min="0"
                    max="99999999.99"
                    step="0.01"
                    inputmode="decimal"
                    required
                    class="<?= isset($errors['precio_referencial']) ? 'input-error' : '' ?>"
                >
                <span class="hint">Monto en soles. Debe ser mayor o igual a 0.</span>
                <?php if (isset($errors['precio_referencial'])): ?>
                    <p class="field-error"><?= esc($errors['precio_referencial']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="descripcion">Descripción</label>
            <textarea
                id="descripcion"
                name="descripcion"
                placeholder="Descripción opcional del producto"
                class="<?= isset($errors['descripcion']) ? 'input-error' : '' ?>"
            ><?= esc($descripcion) ?></textarea>
            <?php if (isset($errors['descripcion'])): ?>
                <p class="field-error"><?= esc($errors['descripcion']) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="estado">Estado</label>
            <select
                id="estado"
                name="estado"
                required
                class="<?= isset($errors['estado']) ? 'input-error' : '' ?>"
            >
                <option value="activo" <?= set_select('estado', 'activo', $estado === 'activo') ?>>Activo</option>
                <option value="pausado" <?= set_select('estado', 'pausado', $estado === 'pausado') ?>>Pausado</option>
                <option value="agotado" <?= set_select('estado', 'agotado', $estado === 'agotado') ?>>Agotado</option>
            </select>
            <?php if (isset($errors['estado'])): ?>
                <p class="field-error"><?= esc($errors['estado']) ?></p>
            <?php endif; ?>
        </div>

        <input type="hidden" name="visible_enviado" value="1">
        <div class="checkbox-row">
            <input type="checkbox" id="visible" name="visible" value="1" <?= $visibleMarcado ? 'checked' : '' ?>>
            <label for="visible">Producto visible en el catálogo</label>
        </div>

        <input type="hidden" name="destacado_enviado" value="1">
        <div class="checkbox-row">
            <input type="checkbox" id="destacado" name="destacado" value="1" <?= $destacadoMarcado ? 'checked' : '' ?>>
            <label for="destacado">Destacar este producto</label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn" <?= $categorias === [] ? 'disabled' : '' ?>>
                <?= $esEdicion ? 'Guardar cambios' : 'Crear producto' ?>
            </button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
