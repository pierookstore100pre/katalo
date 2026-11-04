<?= $this->extend('layouts/panel') ?>

<?= $this->section('styles') ?>
<style>
    .plan-meter {
        margin: 0 0 18px;
        padding: 12px 14px;
        border: 1px solid #ccfbf1;
        border-radius: 13px;
        background: #f0fdfa;
        color: #115e59;
        font-size: 0.88rem;
    }

    .plan-meter strong { color: #0f766e; }

    .filters {
        display: flex;
        gap: 7px;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: none;
    }

    .filters::-webkit-scrollbar { display: none; }

    .filters a {
        flex: 0 0 auto;
        padding: 8px 12px;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: #fff;
        color: var(--muted);
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 650;
    }

    .filters a.active {
        border-color: var(--primary);
        background: var(--primary-soft);
        color: var(--primary-dark);
    }

    .product-grid {
        display: grid;
        gap: 11px;
        margin-top: 16px;
    }

    .product-card {
        display: flex;
        gap: 12px;
        padding: 11px;
        border: 1px solid #e6efec;
        border-radius: 14px;
        background: #fff;
    }

    .product-thumb {
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        width: 84px;
        height: 84px;
        overflow: hidden;
        border-radius: 11px;
        background: #f1f5f9;
        color: #94a3b8;
        font-size: 0.68rem;
        font-weight: 700;
        text-align: center;
    }

    .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-body { min-width: 0; flex: 1; }

    .product-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .product-meta {
        margin: 2px 0 0;
        color: var(--muted);
        font-size: 0.8rem;
        overflow-wrap: anywhere;
    }

    .product-price {
        margin: 4px 0 0;
        color: var(--primary-dark);
        font-size: 0.95rem;
        font-weight: 800;
    }

    .product-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 6px;
    }

    .badge-paused {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-soldout {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-hidden {
        background: #f1f5f9;
        color: #475569;
    }

    .badge-featured {
        background: #ede9fe;
        color: #5b21b6;
    }

    .product-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 9px;
    }

    .product-actions form { display: inline; }

    @media (min-width: 620px) {
        .product-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Catálogo</p>
            <h1>Mis productos</h1>
            <p class="subtitle">Administra los productos visibles, ocultos y agotados de tu negocio.</p>
        </div>
        <a class="btn" href="<?= base_url('negocio/productos/crear') ?>">Nuevo producto</a>
    </div>

    <div class="plan-meter">
        <?php if ($plan !== null): ?>
            Plan <strong><?= esc($plan['nombre']) ?></strong> ·
        <?php endif; ?>
        <?php if ($maxProductos === null): ?>
            <strong><?= (int) $totalProductos ?></strong> productos · ilimitado
        <?php else: ?>
            <strong><?= (int) $totalProductos ?></strong> de <strong><?= (int) $maxProductos ?></strong> productos
        <?php endif; ?>
    </div>

    <?php
    $opcionesFiltro = [
        'todos'    => 'Todos',
        'visibles' => 'Visibles',
        'ocultos'  => 'Ocultos',
        'agotados' => 'Agotados',
    ];
    ?>
    <nav class="filters" aria-label="Filtros de productos">
        <?php foreach ($opcionesFiltro as $valor => $etiqueta): ?>
            <a
                href="<?= base_url('negocio/productos') . '?filtro=' . $valor ?>"
                class="<?= $filtro === $valor ? 'active' : '' ?>"
            ><?= esc($etiqueta) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($productos === []): ?>
        <div class="empty-state">
            <h2><?= $filtro === 'todos' ? 'Aún no tienes productos' : 'No hay productos en este filtro' ?></h2>
            <p><?= $filtro === 'todos' ? 'Crea tu primer producto para empezar a armar tu catálogo.' : 'Prueba con otro filtro o crea un producto nuevo.' ?></p>
            <a class="btn" href="<?= base_url('negocio/productos/crear') ?>">Crear producto</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($productos as $producto): ?>
                <?php
                $idProducto   = (int) $producto['id_producto'];
                $visible      = (int) $producto['visible'] === 1;
                $estado       = $producto['estado'];
                $destacado    = (int) $producto['destacado'] === 1;
                $imagen       = $producto['imagen_principal_data_uri'] ?? null;
                $categoria    = $producto['categoria_nombre'] ?? 'Sin categoría';
                $etiquetaEstado = [
                    'activo'  => 'Activo',
                    'pausado' => 'Pausado',
                    'agotado' => 'Agotado',
                ][$estado] ?? $estado;
                ?>
                <article class="product-card">
                    <div class="product-thumb">
                        <?php if ($imagen !== null): ?>
                            <img src="<?= esc($imagen, 'attr') ?>" alt="<?= esc($producto['titulo'], 'attr') ?>">
                        <?php else: ?>
                            Sin imagen
                        <?php endif; ?>
                    </div>

                    <div class="product-body">
                        <h2 class="product-title"><?= esc($producto['titulo']) ?></h2>
                        <p class="product-meta"><?= esc($categoria) ?></p>
                        <p class="product-price">S/ <?= number_format((float) $producto['precio_referencial'], 2, '.', ',') ?></p>

                        <div class="product-badges">
                            <span class="badge <?= $estado === 'activo' ? 'badge-active' : ($estado === 'agotado' ? 'badge-soldout' : 'badge-paused') ?>">
                                <?= esc($etiquetaEstado) ?>
                            </span>
                            <span class="badge <?= $visible ? 'badge-active' : 'badge-hidden' ?>">
                                <?= $visible ? 'Visible' : 'Oculto' ?>
                            </span>
                            <?php if ($destacado): ?>
                                <span class="badge badge-featured">Destacado</span>
                            <?php endif; ?>
                        </div>

                        <div class="product-actions">
                            <a class="btn btn-secondary btn-small" href="<?= base_url('negocio/productos/editar/' . $idProducto) ?>">Editar</a>
                            <a class="btn btn-secondary btn-small" href="<?= base_url('negocio/productos/' . $idProducto . '/imagenes') ?>">Imágenes</a>

                            <form action="<?= base_url('negocio/productos/' . ($visible ? 'ocultar/' : 'reactivar/') . $idProducto) ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-secondary btn-small"><?= $visible ? 'Ocultar' : 'Reactivar' ?></button>
                            </form>

                            <form action="<?= base_url('negocio/productos/' . ($estado === 'agotado' ? 'reactivar-estado/' : 'agotar/') . $idProducto) ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-secondary btn-small"><?= $estado === 'agotado' ? 'Reactivar estado' : 'Marcar agotado' ?></button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
