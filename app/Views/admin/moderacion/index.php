<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<style>
    .mod-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 16px;
    }

    .mod-tab {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: #fff;
        color: #475569;
        font: inherit;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
    }

    .mod-tab:hover { border-color: #bae6fd; color: #0369a1; }

    .mod-tab.active {
        border-color: #0284c7;
        background: #e0f2fe;
        color: #075985;
    }

    .mod-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.72rem;
    }

    .mod-tab.active .mod-count { background: #fff; color: #0369a1; }

    .mod-panel { display: none; }
    .mod-panel.active { display: block; }

    .mod-list { display: grid; gap: 10px; }

    .mod-item {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        border: 1px solid #e6edf5;
        border-radius: 13px;
        background: #fff;
    }

    .mod-thumb {
        width: 88px;
        height: 88px;
        flex: 0 0 88px;
        border-radius: 10px;
        background: #f1f5f9;
        object-fit: cover;
    }

    .mod-body { flex: 1; min-width: 190px; }

    .mod-title {
        margin: 0 0 4px;
        font-size: 0.95rem;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .mod-meta {
        margin: 0;
        color: var(--muted);
        font-size: 0.8rem;
        overflow-wrap: anywhere;
    }

    .mod-meta + .mod-meta { margin-top: 3px; }

    .mod-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin-left: auto;
    }

    .mod-actions form { display: inline; }

    .mod-badge-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
    }

    .mod-description {
        margin: 6px 0 0;
        color: #334155;
        font-size: 0.84rem;
        line-height: 1.5;
        overflow-wrap: anywhere;
        white-space: pre-line;
    }

    .mod-description-toggle {
        display: inline;
        margin-left: 4px;
        padding: 0;
        border: 0;
        background: none;
        color: #0369a1;
        font: inherit;
        font-weight: 750;
        cursor: pointer;
        text-decoration: underline;
    }

    .mod-words {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
    }

    .mod-words-label {
        color: var(--muted);
        font-size: 0.78rem;
        font-weight: 700;
    }

    .mod-nodata {
        font-style: italic;
    }

    @media (min-width: 720px) {
        .mod-item { align-items: center; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$datosDe = static function (array $item): ?array {
    $datos = $item['datos'] ?? null;

    return is_array($datos) && $datos !== [] ? $datos : null;
};

$palabrasDe = static function (array $item): array {
    $datos = $item['datos'] ?? null;

    if (! is_array($datos)) {
        return [];
    }

    $palabras = $datos['palabras_detectadas'] ?? [];

    if (! is_array($palabras)) {
        return [];
    }

    return array_values(array_filter(array_map('strval', $palabras), static fn (string $palabra): bool => $palabra !== ''));
};

$descripcionDe = static function (array $item): string {
    return trim((string) ($item['descripcion'] ?? ''));
};
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Contenido</p>
            <h1>Moderación</h1>
            <p class="subtitle">Revisa el contenido marcado automáticamente y las imágenes recientes.</p>
        </div>
    </div>

    <div class="mod-tabs" role="tablist" aria-label="Secciones de moderación">
        <button type="button" class="mod-tab active" role="tab" aria-selected="true" aria-controls="panel-productos" data-tab="productos">
            Productos <span class="mod-count"><?= count($productos) ?></span>
        </button>
        <button type="button" class="mod-tab" role="tab" aria-selected="false" aria-controls="panel-categorias" data-tab="categorias">
            Categorías <span class="mod-count"><?= count($categorias) ?></span>
        </button>
        <button type="button" class="mod-tab" role="tab" aria-selected="false" aria-controls="panel-negocios" data-tab="negocios">
            Negocios <span class="mod-count"><?= count($negocios) ?></span>
        </button>
        <button type="button" class="mod-tab" role="tab" aria-selected="false" aria-controls="panel-imagenes" data-tab="imagenes">
            Imágenes <span class="mod-count"><?= count($imagenes) ?></span>
        </button>
    </div>

    <div class="mod-panel active" id="panel-productos" role="tabpanel">
        <?php if ($productos === []): ?>
            <div class="empty-state">
                <h2>No hay contenido pendiente de revisión.</h2>
                <p>Los productos marcados por el filtro aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <div class="mod-list">
                <?php foreach ($productos as $producto): ?>
                    <?php
                    $estado      = (string) ($producto['estado_moderacion'] ?? 'contenido_marcado');
                    $fecha       = $producto['log_created_at'] !== null
                        ? date('d/m/Y H:i', strtotime((string) $producto['log_created_at']))
                        : '—';
                    $datos       = $datosDe($producto);
                    $palabras    = $palabrasDe($producto);
                    $descripcion = $descripcionDe($producto);
                    $esLarga     = mb_strlen($descripcion) > 300;
                    $precio      = $producto['precio_referencial'] ?? null;
                    ?>
                    <article class="mod-item">
                        <?php if (! empty($producto['id_imagen'])): ?>
                            <img
                                class="mod-thumb"
                                src="<?= base_url('c/imagen/' . (int) $producto['id_imagen']) ?>"
                                alt="Imagen del producto <?= esc($producto['titulo'], 'attr') ?>"
                                loading="lazy"
                            >
                        <?php endif; ?>
                        <div class="mod-body">
                            <h2 class="mod-title"><?= esc($producto['titulo']) ?></h2>
                            <p class="mod-meta">Negocio: <?= esc($producto['negocio_nombre'] ?? '—') ?></p>
                            <p class="mod-meta">Categoría: <?= esc($producto['categoria_nombre'] ?? '—') ?></p>
                            <p class="mod-meta">Marcado: <?= esc($fecha) ?></p>
                            <p class="mod-meta">Motivo: <?= esc($producto['motivo'] ?? '—') ?></p>
                            <?php if ($descripcion === ''): ?>
                                <p class="mod-description">Sin descripción.</p>
                            <?php elseif ($esLarga): ?>
                                <div class="mod-description">
                                    <span class="mod-description-short"><?= esc(mb_substr($descripcion, 0, 300)) ?>…</span>
                                    <span class="mod-description-full" hidden><?= esc($descripcion) ?></span>
                                    <button type="button" class="mod-description-toggle" data-expanded="false">Ver más</button>
                                </div>
                            <?php else: ?>
                                <p class="mod-description"><?= esc($descripcion) ?></p>
                            <?php endif; ?>
                            <p class="mod-meta">
                                Precio: <?= ($precio !== null && $precio !== '') ? 'S/ ' . esc(number_format((float) $precio, 2)) : '—' ?>
                            </p>
                            <?php if ($datos === null): ?>
                                <p class="mod-meta mod-nodata">Sin detalle disponible</p>
                            <?php elseif ($palabras !== []): ?>
                                <div class="mod-words">
                                    <span class="mod-words-label">Palabras detectadas:</span>
                                    <?php foreach ($palabras as $palabra): ?>
                                        <span class="badge badge-suspended"><?= esc($palabra) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="mod-badge-row">
                                <span class="badge <?= $estado === 'contenido_rechazado' ? 'badge-suspended' : ($estado === 'contenido_aprobado' ? 'badge-active' : 'badge-warning') ?>">
                                    <?= $estado === 'contenido_rechazado' ? 'Rechazado' : ($estado === 'contenido_aprobado' ? 'Aprobado' : 'Pendiente') ?>
                                </span>
                            </div>
                        </div>
                        <div class="mod-actions">
                            <form action="<?= base_url('admin/moderacion/producto/' . (int) $producto['id'] . '/aprobar') ?>" method="post" onsubmit="return confirm('¿Aprobar este producto?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-success btn-small">Aprobar</button>
                            </form>
                            <form action="<?= base_url('admin/moderacion/producto/' . (int) $producto['id'] . '/rechazar') ?>" method="post" onsubmit="return confirm('¿Rechazar este producto?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger btn-small">Rechazar</button>
                            </form>
                            <a href="<?= base_url('admin/negocios/' . (int) $producto['negocio_id']) ?>" class="btn btn-secondary btn-small">Ver negocio</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mod-panel" id="panel-categorias" role="tabpanel">
        <?php if ($categorias === []): ?>
            <div class="empty-state">
                <h2>No hay contenido pendiente de revisión.</h2>
                <p>Las categorías marcadas por el filtro aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <div class="mod-list">
                <?php foreach ($categorias as $categoria): ?>
                    <?php
                    $estado      = (string) ($categoria['estado_moderacion'] ?? 'contenido_marcado');
                    $fecha       = $categoria['log_created_at'] !== null
                        ? date('d/m/Y H:i', strtotime((string) $categoria['log_created_at']))
                        : '—';
                    $datos       = $datosDe($categoria);
                    $palabras    = $palabrasDe($categoria);
                    $descripcion = $descripcionDe($categoria);
                    ?>
                    <article class="mod-item">
                        <div class="mod-body">
                            <h2 class="mod-title"><?= esc($categoria['titulo']) ?></h2>
                            <p class="mod-meta">Negocio: <?= esc($categoria['negocio_nombre'] ?? '—') ?></p>
                            <p class="mod-meta">Marcado: <?= esc($fecha) ?></p>
                            <p class="mod-meta">Motivo: <?= esc($categoria['motivo'] ?? '—') ?></p>
                            <?php if ($descripcion === ''): ?>
                                <p class="mod-description">Sin descripción.</p>
                            <?php else: ?>
                                <p class="mod-description"><?= esc($descripcion) ?></p>
                            <?php endif; ?>
                            <?php if ($datos === null): ?>
                                <p class="mod-meta mod-nodata">Sin detalle disponible</p>
                            <?php elseif ($palabras !== []): ?>
                                <div class="mod-words">
                                    <span class="mod-words-label">Palabras detectadas:</span>
                                    <?php foreach ($palabras as $palabra): ?>
                                        <span class="badge badge-suspended"><?= esc($palabra) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="mod-badge-row">
                                <span class="badge <?= $estado === 'contenido_rechazado' ? 'badge-suspended' : ($estado === 'contenido_aprobado' ? 'badge-active' : 'badge-warning') ?>">
                                    <?= $estado === 'contenido_rechazado' ? 'Rechazado' : ($estado === 'contenido_aprobado' ? 'Aprobado' : 'Pendiente') ?>
                                </span>
                            </div>
                        </div>
                        <div class="mod-actions">
                            <form action="<?= base_url('admin/moderacion/categoria/' . (int) $categoria['id'] . '/aprobar') ?>" method="post" onsubmit="return confirm('¿Aprobar esta categoría?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-success btn-small">Aprobar</button>
                            </form>
                            <form action="<?= base_url('admin/moderacion/categoria/' . (int) $categoria['id'] . '/rechazar') ?>" method="post" onsubmit="return confirm('¿Rechazar esta categoría?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger btn-small">Rechazar</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mod-panel" id="panel-negocios" role="tabpanel">
        <?php if ($negocios === []): ?>
            <div class="empty-state">
                <h2>No hay contenido pendiente de revisión.</h2>
                <p>Los negocios marcados por el filtro aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <div class="mod-list">
                <?php foreach ($negocios as $negocio): ?>
                    <?php
                    $estado      = (string) ($negocio['estado_moderacion'] ?? 'contenido_marcado');
                    $fecha       = $negocio['log_created_at'] !== null
                        ? date('d/m/Y H:i', strtotime((string) $negocio['log_created_at']))
                        : '—';
                    $esRechazado = in_array($estado, ['contenido_rechazado', 'suspender_negocio'], true);
                    $esAprobado  = $estado === 'negocio_aprobado';
                    $datos       = $datosDe($negocio);
                    $palabras    = $palabrasDe($negocio);
                    $descripcion = $descripcionDe($negocio);
                    ?>
                    <article class="mod-item">
                        <div class="mod-body">
                            <h2 class="mod-title"><?= esc($negocio['titulo']) ?></h2>
                            <p class="mod-meta">Marcado: <?= esc($fecha) ?></p>
                            <p class="mod-meta">Motivo: <?= esc($negocio['motivo'] ?? '—') ?></p>
                            <?php if ($descripcion === ''): ?>
                                <p class="mod-description">Sin descripción.</p>
                            <?php else: ?>
                                <p class="mod-description"><?= esc($descripcion) ?></p>
                            <?php endif; ?>
                            <?php if ($datos === null): ?>
                                <p class="mod-meta mod-nodata">Sin detalle disponible</p>
                            <?php elseif ($palabras !== []): ?>
                                <div class="mod-words">
                                    <span class="mod-words-label">Palabras detectadas:</span>
                                    <?php foreach ($palabras as $palabra): ?>
                                        <span class="badge badge-suspended"><?= esc($palabra) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="mod-badge-row">
                                <span class="badge <?= $esRechazado ? 'badge-suspended' : ($esAprobado ? 'badge-active' : 'badge-warning') ?>">
                                    <?= $esRechazado ? 'Rechazado' : ($esAprobado ? 'Aprobado' : 'Pendiente') ?>
                                </span>
                            </div>
                        </div>
                        <div class="mod-actions">
                            <form action="<?= base_url('admin/moderacion/negocio/' . (int) $negocio['id'] . '/aprobar') ?>" method="post" onsubmit="return confirm('¿Aprobar este negocio?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-success btn-small">Aprobar</button>
                            </form>
                            <form action="<?= base_url('admin/negocios/' . (int) $negocio['id'] . '/suspender') ?>" method="post" onsubmit="return confirm('¿Suspender este negocio?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger btn-small">Suspender</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mod-panel" id="panel-imagenes" role="tabpanel">
        <?php if ($imagenes === []): ?>
            <div class="empty-state">
                <h2>No hay imágenes recientes para revisar.</h2>
                <p>Las imágenes subidas en los últimos 7 días aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <div class="mod-list">
                <?php foreach ($imagenes as $imagen): ?>
                    <?php
                    $estado = (string) ($imagen['estado_moderacion'] ?? '');
                    $fecha  = $imagen['created_at'] !== null
                        ? date('d/m/Y H:i', strtotime((string) $imagen['created_at']))
                        : '—';
                    $esRechazada = $estado === 'imagen_rechazada';
                    $esAprobada  = $estado === 'imagen_aprobada';
                    ?>
                    <article class="mod-item">
                        <img
                            class="mod-thumb"
                            src="<?= base_url('c/imagen/' . (int) $imagen['id_imagen']) ?>"
                            alt="Imagen del producto <?= esc($imagen['producto_titulo'], 'attr') ?>"
                            loading="lazy"
                        >
                        <div class="mod-body">
                            <h2 class="mod-title"><?= esc($imagen['producto_titulo']) ?></h2>
                            <p class="mod-meta">Negocio: <?= esc($imagen['negocio_nombre'] ?? '—') ?></p>
                            <p class="mod-meta">Subida: <?= esc($fecha) ?></p>
                            <div class="mod-badge-row">
                                <span class="badge <?= $esRechazada ? 'badge-suspended' : ($esAprobada ? 'badge-active' : 'badge-warning') ?>">
                                    <?= $esRechazada ? 'Rechazado' : ($esAprobada ? 'Aprobado' : 'Pendiente') ?>
                                </span>
                            </div>
                        </div>
                        <div class="mod-actions">
                            <form action="<?= base_url('admin/moderacion/imagen/' . (int) $imagen['id_imagen'] . '/aprobar') ?>" method="post" onsubmit="return confirm('¿Aprobar esta imagen?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-success btn-small">Aprobar</button>
                            </form>
                            <form action="<?= base_url('admin/moderacion/imagen/' . (int) $imagen['id_imagen'] . '/rechazar') ?>" method="post" onsubmit="return confirm('¿Rechazar esta imagen?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger btn-small">Rechazar</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function () {
        var tabs = document.querySelectorAll('.mod-tab');
        var panels = document.querySelectorAll('.mod-panel');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (item) {
                    item.classList.remove('active');
                    item.setAttribute('aria-selected', 'false');
                });

                panels.forEach(function (panel) {
                    panel.classList.remove('active');
                });

                tab.classList.add('active');
                tab.setAttribute('aria-selected', 'true');

                var panel = document.getElementById('panel-' + tab.dataset.tab);
                if (panel) {
                    panel.classList.add('active');
                }
            });
        });

        document.querySelectorAll('.mod-description-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                var wrapper = button.closest('.mod-description');
                if (!wrapper) {
                    return;
                }

                var short = wrapper.querySelector('.mod-description-short');
                var full = wrapper.querySelector('.mod-description-full');
                var expanded = button.dataset.expanded === 'true';

                if (short) {
                    short.hidden = !expanded;
                }

                if (full) {
                    full.hidden = expanded;
                }

                button.dataset.expanded = expanded ? 'false' : 'true';
                button.textContent = expanded ? 'Ver más' : 'Ver menos';
            });
        });
    })();
</script>
<?= $this->endSection() ?>
