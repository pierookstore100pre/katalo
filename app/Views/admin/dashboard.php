<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Administración global</p>
            <h1>Dashboard</h1>
            <p class="subtitle">Resumen de la plataforma y desempeño por ciudad.</p>
        </div>
    </div>

    <div class="stat-grid">
        <article class="stat-card">
            <p class="stat-label">Negocios activos</p>
            <p class="stat-value"><?= (int) $metricas['negocios_activos'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Negocios suspendidos</p>
            <p class="stat-value"><?= (int) $metricas['negocios_suspendidos'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Productos visibles</p>
            <p class="stat-value"><?= (int) $metricas['productos'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Pedidos del mes</p>
            <p class="stat-value"><?= (int) $metricas['pedidos_mes'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">MRR actual</p>
            <p class="stat-value small">S/ <?= number_format((float) $metricas['mrr'], 2, '.', ',') ?></p>
        </article>
    </div>

    <div class="quick-links">
        <a class="btn btn-secondary btn-small" href="<?= base_url('admin/ciudades') ?>">Ciudades</a>
        <a class="btn btn-secondary btn-small" href="<?= base_url('admin/distritos') ?>">Distritos</a>
        <a class="btn btn-secondary btn-small" href="<?= base_url('admin/negocios') ?>">Negocios</a>
        <a class="btn btn-secondary btn-small" href="<?= base_url('admin/planes') ?>">Planes</a>
        <a class="btn btn-secondary btn-small" href="<?= base_url('admin/pagos') ?>">Pagos</a>
        <a class="btn btn-secondary btn-small" href="<?= base_url('admin/logs') ?>">Registro de actividad</a>
    </div>
</section>

<section class="card">
    <h2>Resumen por ciudad</h2>

    <?php if ($porCiudad === []): ?>
        <div class="empty-state">
            <p>No hay ciudades registradas.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ciudad</th>
                        <th>Estado</th>
                        <th>Negocios activos</th>
                        <th>Productos</th>
                        <th>Pedidos del mes</th>
                        <th>MRR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($porCiudad as $ciudad): ?>
                        <tr>
                            <td data-label="Ciudad"><strong><?= esc($ciudad['nombre']) ?></strong></td>
                            <td data-label="Estado">
                                <span class="badge <?= (int) $ciudad['activo'] === 1 ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= (int) $ciudad['activo'] === 1 ? 'Activa' : 'Inactiva' ?>
                                </span>
                            </td>
                            <td data-label="Negocios activos"><?= (int) $ciudad['negocios_activos'] ?></td>
                            <td data-label="Productos"><?= (int) $ciudad['productos'] ?></td>
                            <td data-label="Pedidos del mes"><?= (int) $ciudad['pedidos_mes'] ?></td>
                            <td data-label="MRR">S/ <?= number_format((float) $ciudad['mrr'], 2, '.', ',') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
