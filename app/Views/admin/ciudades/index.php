<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$opcionesFiltro = [
    'todas'     => 'Todas',
    'activas'   => 'Activas',
    'inactivas' => 'Inactivas',
];
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Geografía</p>
            <h1>Ciudades</h1>
            <p class="subtitle">Activa o desactiva el lanzamiento geográfico de la plataforma.</p>
        </div>
    </div>

    <nav class="quick-links" aria-label="Filtros de ciudades" style="margin:0 0 16px">
        <?php foreach ($opcionesFiltro as $valor => $etiqueta): ?>
            <?php
            $urlFiltro = $valor === 'todas'
                ? base_url('admin/ciudades')
                : base_url('admin/ciudades') . '?estado=' . $valor;
            ?>
            <a
                class="btn btn-secondary btn-small"
                href="<?= esc($urlFiltro, 'attr') ?>"
                style="<?= $filtro === $valor ? 'border-color:#0284c7;color:#0369a1;background:#e0f2fe' : '' ?>"
            ><?= esc($etiqueta) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($ciudades === []): ?>
        <div class="empty-state">
            <h2>No hay ciudades para este filtro</h2>
            <p>Prueba con otro estado o registra ciudades desde la base de datos.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ciudad</th>
                        <th>Slug</th>
                        <th>Región</th>
                        <th>Estado</th>
                        <th>Lanzamiento</th>
                        <th>Negocios activos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ciudades as $ciudad): ?>
                        <?php $activa = (int) $ciudad['activo'] === 1; ?>
                        <tr>
                            <td data-label="Ciudad"><strong><?= esc($ciudad['nombre']) ?></strong></td>
                            <td data-label="Slug"><?= esc($ciudad['slug']) ?></td>
                            <td data-label="Región"><?= esc($ciudad['region'] ?? '—') ?></td>
                            <td data-label="Estado">
                                <span class="badge <?= $activa ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $activa ? 'Activa' : 'Inactiva' ?>
                                </span>
                            </td>
                            <td data-label="Lanzamiento"><?= esc($ciudad['fecha_lanzamiento'] ?? '—') ?></td>
                            <td data-label="Negocios activos"><?= (int) $ciudad['negocios_activos'] ?></td>
                            <td data-label="Acciones">
                                <div class="actions">
                                    <?php if ($activa): ?>
                                        <form action="<?= base_url('admin/ciudades/' . (int) $ciudad['id_ciudad'] . '/desactivar') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-small"
                                                onclick="return confirm('¿Desactivar esta ciudad? Sus negocios dejarán de aparecer públicamente.');"
                                            >Desactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= base_url('admin/ciudades/' . (int) $ciudad['id_ciudad'] . '/activar') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-success btn-small">Activar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="notice">
        Al desactivar una ciudad, sus negocios dejan de aparecer en el catálogo público automáticamente.
        Al reactivarla, vuelven a mostrarse.
    </div>
</section>
<?= $this->endSection() ?>
