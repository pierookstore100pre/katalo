<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$limite = static function ($valor): string {
    return $valor === null ? 'Ilimitado' : (string) (int) $valor;
};
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Comercial</p>
            <h1>Planes</h1>
            <p class="subtitle">Ajusta precios, límites y features de la oferta comercial.</p>
        </div>
    </div>

    <div class="notice">
        Cambiar el precio de un plan no altera el precio pactado de las suscripciones existentes.
        Desactivar un plan no afecta a sus suscriptores actuales; solo impide ofrecerlo en upgrades.
    </div>

    <?php if ($planes === []): ?>
        <div class="empty-state">
            <h2>No hay planes registrados</h2>
            <p>Los planes se crean desde la base de datos inicial.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Precio mensual</th>
                        <th>Productos</th>
                        <th>Imágenes/producto</th>
                        <th>Usuarios</th>
                        <th>Pedidos/mes</th>
                        <th>Features</th>
                        <th>Estado</th>
                        <th>Suscripciones activas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($planes as $plan): ?>
                        <?php $activo = (int) $plan['activo'] === 1; ?>
                        <tr>
                            <td data-label="Plan">
                                <strong><?= esc($plan['nombre']) ?></strong><br>
                                <span style="color:#64748b;font-size:0.8rem"><?= esc($plan['slug']) ?></span>
                            </td>
                            <td data-label="Precio mensual">S/ <?= number_format((float) $plan['precio_mensual'], 2, '.', ',') ?></td>
                            <td data-label="Productos"><?= esc($limite($plan['max_productos'])) ?></td>
                            <td data-label="Imágenes/producto"><?= esc($limite($plan['max_imagenes_por_producto'])) ?></td>
                            <td data-label="Usuarios"><?= esc($limite($plan['max_usuarios'])) ?></td>
                            <td data-label="Pedidos/mes"><?= esc($limite($plan['max_pedidos_mes'])) ?></td>
                            <td data-label="Features">
                                <div class="actions">
                                    <?php if ((int) $plan['tiene_dominio_propio'] === 1): ?>
                                        <span class="badge badge-info">Dominio</span>
                                    <?php endif; ?>
                                    <?php if ((int) $plan['tiene_analytics'] === 1): ?>
                                        <span class="badge badge-info">Analytics</span>
                                    <?php endif; ?>
                                    <?php if ((int) $plan['tiene_reportes'] === 1): ?>
                                        <span class="badge badge-info">Reportes</span>
                                    <?php endif; ?>
                                    <?php if ((int) $plan['tiene_dominio_propio'] === 0 && (int) $plan['tiene_analytics'] === 0 && (int) $plan['tiene_reportes'] === 0): ?>
                                        <span class="badge badge-inactive">Básicas</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Estado">
                                <span class="badge <?= $activo ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $activo ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td data-label="Suscripciones activas"><?= (int) $plan['suscripciones_activas'] ?></td>
                            <td data-label="Acciones">
                                <div class="actions">
                                    <a
                                        class="btn btn-secondary btn-small"
                                        href="<?= base_url('admin/planes/editar/' . (int) $plan['id_plan']) ?>"
                                    >Editar</a>

                                    <?php if ($activo): ?>
                                        <form action="<?= base_url('admin/planes/' . (int) $plan['id_plan'] . '/desactivar') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-small"
                                                onclick="return confirm('¿Desactivar este plan? No se ofrecerá en upgrades.');"
                                            >Desactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= base_url('admin/planes/' . (int) $plan['id_plan'] . '/activar') ?>" method="post">
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
</section>
<?= $this->endSection() ?>
