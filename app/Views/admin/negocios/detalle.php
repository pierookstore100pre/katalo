<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$activo = (int) $negocio['activo'] === 1;

$propietario = trim(
    (string) ($negocio['propietario_nombres'] ?? '')
    . ' ' . (string) ($negocio['propietario_apellidos'] ?? '')
);

$tienePropietarioActivo = ! empty($negocio['propietario_email']);

$etiquetasEstadoPedido = [
    'nuevo'      => 'Nuevo',
    'confirmado' => 'Confirmado',
    'en_proceso' => 'En proceso',
    'entregado'  => 'Entregado',
    'cancelado'  => 'Cancelado',
];

$clasesEstadoPedido = [
    'nuevo'      => 'badge-suspended',
    'confirmado' => 'badge-warning',
    'en_proceso' => 'badge-info',
    'entregado'  => 'badge-active',
    'cancelado'  => 'badge-inactive',
];

$estadoSuscripcion = [
    'prueba'         => 'Prueba',
    'pendiente_pago' => 'Pendiente de pago',
    'activa'         => 'Activa',
    'vencida'        => 'Vencida',
    'cancelada'      => 'Cancelada',
];
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Negocio #<?= (int) $negocio['id_negocio'] ?></p>
            <h1><?= esc($negocio['nombre_comercial']) ?></h1>
            <p class="subtitle">
                <?= esc($negocio['slug']) ?> ·
                <span class="badge <?= $activo ? 'badge-active' : 'badge-suspended' ?>">
                    <?= $activo ? 'Activo' : 'Suspendido' ?>
                </span>
            </p>
        </div>

        <div class="actions">
            <form action="<?= base_url('admin/impersonar/' . (int) $negocio['id_negocio']) ?>" method="post">
                <?= csrf_field() ?>
                <?php if ($tienePropietarioActivo): ?>
                    <button type="submit" class="btn btn-secondary">Ver como este negocio</button>
                <?php else: ?>
                    <button
                        type="button"
                        class="btn btn-secondary"
                        disabled
                        title="Sin propietario activo"
                        style="opacity:.55;cursor:not-allowed"
                    >Ver como este negocio</button>
                <?php endif; ?>
            </form>

            <?php if ($activo): ?>
                <form action="<?= base_url('admin/negocios/' . (int) $negocio['id_negocio'] . '/suspender') ?>" method="post">
                    <?= csrf_field() ?>
                    <button
                        type="submit"
                        class="btn btn-danger"
                        onclick="return confirm('¿Suspender este negocio? Su catálogo dejará de ser público.');"
                    >Suspender negocio</button>
                </form>
            <?php else: ?>
                <form action="<?= base_url('admin/negocios/' . (int) $negocio['id_negocio'] . '/reactivar') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success">Reactivar negocio</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="notice">
        Suspender un negocio lo oculta automáticamente del catálogo público sin eliminar su
        historial, productos ni pedidos. Puedes reactivarlo en cualquier momento.
    </div>
</section>

<section class="card">
    <h2>Datos del negocio</h2>
    <div class="meta-list">
        <div class="meta-item"><span>ID</span><span>#<?= (int) $negocio['id_negocio'] ?></span></div>
        <div class="meta-item"><span>Slug</span><span><?= esc($negocio['slug']) ?></span></div>
        <div class="meta-item"><span>Dirección</span><span><?= esc($negocio['direccion']) ?></span></div>
        <div class="meta-item"><span>Teléfono principal</span><span><?= esc($negocio['telefono_principal']) ?></span></div>
        <div class="meta-item"><span>Distrito</span><span><?= esc($negocio['distrito_nombre']) ?></span></div>
        <div class="meta-item"><span>Ciudad</span><span><?= esc($negocio['ciudad_nombre']) ?></span></div>
        <div class="meta-item">
            <span>Propietario</span>
            <span>
                <?= $propietario !== '' ? esc($propietario) : '—' ?>
                <?php if (! empty($negocio['propietario_email'])): ?>
                    <br><small><?= esc($negocio['propietario_email']) ?></small>
                <?php endif; ?>
            </span>
        </div>
        <div class="meta-item">
            <span>Fecha de registro</span>
            <span><?= esc(date('d/m/Y H:i', strtotime((string) $negocio['created_at']))) ?></span>
        </div>
    </div>
</section>

<section class="card">
    <h2>Suscripción</h2>

    <div class="meta-list">
        <div class="meta-item">
            <span>Suscripción vigente</span>
            <span>
                <?php if ($suscripcionVigente !== null): ?>
                    <?= esc($planVigente['nombre'] ?? 'Plan #' . (int) $suscripcionVigente['plan_id']) ?>
                    · <?= esc($estadoSuscripcion[$suscripcionVigente['estado']] ?? $suscripcionVigente['estado']) ?>
                    · S/ <?= number_format((float) $suscripcionVigente['precio_pactado'], 2, '.', ',') ?>
                    <?php if ($suscripcionVigente['fecha_fin'] !== null): ?>
                        <br><small>Vence: <?= esc($suscripcionVigente['fecha_fin']) ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    Sin suscripción vigente
                <?php endif; ?>
            </span>
        </div>

        <div class="meta-item">
            <span>Última suscripción</span>
            <span>
                <?php if ($ultimaSuscripcion !== null): ?>
                    <?= esc($planUltima['nombre'] ?? 'Plan #' . (int) $ultimaSuscripcion['plan_id']) ?>
                    · <?= esc($estadoSuscripcion[$ultimaSuscripcion['estado']] ?? $ultimaSuscripcion['estado']) ?>
                    · S/ <?= number_format((float) $ultimaSuscripcion['precio_pactado'], 2, '.', ',') ?>
                <?php else: ?>
                    Sin suscripciones registradas
                <?php endif; ?>
            </span>
        </div>
    </div>
</section>

<section class="card">
    <h2>Actividad comercial</h2>

    <div class="stat-grid">
        <article class="stat-card">
            <p class="stat-label">Productos</p>
            <p class="stat-value"><?= (int) $productos['total'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Visibles</p>
            <p class="stat-value"><?= (int) $productos['visibles'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Ocultos</p>
            <p class="stat-value"><?= (int) $productos['ocultos'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Agotados</p>
            <p class="stat-value"><?= (int) $productos['agotados'] ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Pedidos totales</p>
            <p class="stat-value"><?= (int) $pedidosTotal ?></p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Pedidos del mes</p>
            <p class="stat-value"><?= (int) $pedidosMes ?></p>
        </article>
    </div>
</section>

<section class="card">
    <h2>Últimos 10 pedidos</h2>

    <?php if ($ultimosPedidos === []): ?>
        <div class="empty-state">
            <p>Este negocio aún no tiene pedidos registrados.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimosPedidos as $pedido): ?>
                        <?php
                        $estado = (string) $pedido['estado'];
                        $fecha  = $pedido['created_at'] !== null
                            ? date('d/m/Y H:i', strtotime((string) $pedido['created_at']))
                            : '—';
                        ?>
                        <tr>
                            <td data-label="Código"><strong><?= esc($pedido['codigo']) ?></strong></td>
                            <td data-label="Cliente"><?= esc($pedido['cliente_nombre']) ?></td>
                            <td data-label="Total">S/ <?= number_format((float) $pedido['total'], 2, '.', ',') ?></td>
                            <td data-label="Estado">
                                <span class="badge <?= esc($clasesEstadoPedido[$estado] ?? 'badge-inactive', 'attr') ?>">
                                    <?= esc($etiquetasEstadoPedido[$estado] ?? $estado) ?>
                                </span>
                            </td>
                            <td data-label="Fecha"><?= esc($fecha) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="form-actions">
        <a class="btn btn-secondary" href="<?= base_url('admin/negocios') ?>">Volver a negocios</a>
    </div>
</section>
<?= $this->endSection() ?>
