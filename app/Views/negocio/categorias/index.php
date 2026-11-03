<?= $this->extend('layouts/panel') ?>

<?= $this->section('content') ?>
<?php
$nombresCategorias = [];
foreach ($categorias as $item) {
    $nombresCategorias[(int) $item['id_categoria']] = $item['nombre'];
}
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Catálogo</p>
            <h1>Mis categorías</h1>
            <p class="subtitle">Organiza tu catálogo con categorías y subcategorías.</p>
        </div>
        <a class="btn" href="<?= base_url('negocio/categorias/crear') ?>">Nueva categoría</a>
    </div>

    <?php if ($categorias === []): ?>
        <div class="empty-state">
            <h2>Aún no tienes categorías</h2>
            <p>Crea tu primera categoría para empezar a ordenar tus productos.</p>
            <a class="btn" href="<?= base_url('negocio/categorias/crear') ?>">Crear categoría</a>
        </div>
    <?php else: ?>
        <div class="category-list">
            <?php foreach ($categorias as $categoria): ?>
                <?php
                $idCategoria = (int) $categoria['id_categoria'];
                $activa      = (int) $categoria['activo'] === 1;
                $padreId     = $categoria['categoria_padre_id'] !== null ? (int) $categoria['categoria_padre_id'] : 0;
                $padreNombre = $padreId > 0 ? ($nombresCategorias[$padreId] ?? null) : null;
                ?>
                <div class="category-row" style="--nivel: <?= (int) $categoria['nivel'] ?>">
                    <div class="category-main">
                        <div class="category-title">
                            <?php if ((int) $categoria['nivel'] > 0): ?>
                                <span class="tree-line">↳</span>
                            <?php endif; ?>
                            <span><?= esc($categoria['nombre']) ?></span>
                            <span class="badge <?= $activa ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $activa ? 'Activa' : 'Inactiva' ?>
                            </span>
                        </div>
                        <div class="category-meta">
                            /<?= esc($categoria['slug']) ?>
                            <?php if ($padreNombre !== null): ?>
                                · Padre: <?= esc($padreNombre) ?>
                            <?php endif; ?>
                            <?php if (! empty($categoria['descripcion'])): ?>
                                · <?= esc($categoria['descripcion']) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="category-actions">
                        <a class="btn btn-secondary btn-small" href="<?= base_url('negocio/categorias/editar/' . $idCategoria) ?>">Editar</a>

                        <form action="<?= base_url('negocio/categorias/editar/' . $idCategoria) ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="estado">
                            <input type="hidden" name="activo" value="<?= $activa ? '0' : '1' ?>">
                            <button type="submit" class="btn btn-secondary btn-small">
                                <?= $activa ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>

                        <a
                            class="btn btn-danger btn-small"
                            href="<?= base_url('negocio/categorias/eliminar/' . $idCategoria) ?>"
                            onclick="return confirm('¿Confirmas que deseas eliminar esta categoría?');"
                        >Eliminar</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
