<?= $this->extend('layouts/panel') ?>

<?= $this->section('styles') ?>
<style>
    .section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 22px 0 10px;
    }

    .section-title:first-of-type { margin-top: 0; }

    .section-title h2 {
        margin: 0;
        font-size: 1.05rem;
    }

    .pending-list,
    .payment-list {
        display: grid;
        gap: 10px;
    }

    .pending-card,
    .payment-card {
        padding: 14px;
        border: 1px solid #e6efec;
        border-radius: 14px;
        background: #fff;
    }

    .pending-card-head,
    .payment-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .pending-card h3,
    .payment-card h3 {
        margin: 0;
        font-size: 1rem;
        overflow-wrap: anywhere;
    }

    .meta-line {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 0.82rem;
        overflow-wrap: anywhere;
    }

    .pending-price {
        margin: 8px 0 0;
        color: var(--primary-dark);
        font-size: 0.94rem;
        font-weight: 800;
    }

    .pending-actions,
    .payment-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 11px;
    }

    .pending-actions form,
    .payment-actions form { display: inline; }

    .filters {
        display: flex;
        gap: 7px;
        overflow-x: auto;
        margin-bottom: 12px;
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

    .payment-detail {
        display: grid;
        gap: 4px;
        margin-top: 9px;
        color: #334155;
        font-size: 0.82rem;
    }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-confirmed {
        background: #dcfce7;
        color: #166534;
    }

    .badge-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    @media (min-width: 700px) {
        .pending-list,
        .payment-list { grid-template-columns: 1fr 1fr; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$etiquetasPago = [
    'pendiente'  => 'Pendiente',
    'confirmado' => 'Confirmado',
    'rechazado'  => 'Rechazado',
];

$clasesPago = [
    'pendiente'  => 'badge-pending',
    'confirmado' => 'badge-confirmed',
    'rechazado'  => 'badge-rejected',
];

$metodos = [
    'transferencia' => 'Transferencia',
    'yape'          => 'Yape',
    'plin'          => 'Plin',
    'efectivo'      => 'Efectivo',
    'otro'          => 'Otro',
];

$opcionesFiltro = [
    'todos'      => 'Todos',
    'pendiente'  => 'Pendientes',
    'confirmado' => 'Confirmados',
    'rechazado'  => 'Rechazados',
];
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Administración</p>
            <h1>Pagos de suscripción</h1>
            <p class="subtitle">Registra y confirma pagos manuales para activar upgrades.</p>
        </div>
    </div>

    <div class="section-title">
        <h2>Solicitudes pendientes de pago</h2>
        <span class="badge badge-pending"><?= count($pendientes) ?></span>
    </div>

    <?php if ($pendientes === []): ?>
        <div class="empty-state" style="padding:20px 12px">
            <p>No hay solicitudes de upgrade pendientes.</p>
        </div>
    <?php else: ?>
        <div class="pending-list">
            <?php foreach ($pendientes as $suscripcion): ?>
                <article class="pending-card">
                    <div class="pending-card-head">
                        <div>
                            <h3><?= esc($suscripcion['nombre_comercial']) ?></h3>
                            <p class="meta-line">
                                Plan solicitado: <strong><?= esc($suscripcion['plan_nombre']) ?></strong>
                                · Suscripción #<?= (int) $suscripcion['id_suscripcion'] ?>
                            </p>
                        </div>
                        <span class="badge badge-pending">Pendiente</span>
                    </div>

                    <p class="pending-price">
                        S/ <?= number_format((float) $suscripcion['precio_pactado'], 2, '.', ',') ?>
                    </p>
                    <p class="meta-line">Solicitado el <?= esc($suscripcion['created_at']) ?></p>

                    <div class="pending-actions">
                        <a
                            class="btn btn-small"
                            href="<?= base_url('admin/pagos/registrar/' . (int) $suscripcion['id_suscripcion']) ?>"
                        >Registrar pago</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="section-title">
        <h2>Pagos registrados</h2>
    </div>

    <nav class="filters" aria-label="Filtros de pagos">
        <?php foreach ($opcionesFiltro as $valor => $etiqueta): ?>
            <a
                href="<?= base_url('admin/pagos') . '?estado=' . $valor ?>"
                class="<?= $filtro === $valor ? 'active' : '' ?>"
            ><?= esc($etiqueta) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($pagos === []): ?>
        <div class="empty-state" style="padding:20px 12px">
            <p>No hay pagos registrados para este filtro.</p>
        </div>
    <?php else: ?>
        <div class="payment-list">
            <?php foreach ($pagos as $pago): ?>
                <?php
                $idPago      = (int) $pago['id_pago'];
                $estadoPago  = $pago['estado'];
                $esPendiente = $estadoPago === 'pendiente';
                ?>
                <article class="payment-card">
                    <div class="payment-card-head">
                        <div>
                            <h3><?= esc($pago['nombre_comercial']) ?></h3>
                            <p class="meta-line">
                                Pago #<?= $idPago ?> · <?= esc($pago['plan_nombre']) ?>
                            </p>
                        </div>
                        <span class="badge <?= esc($clasesPago[$estadoPago] ?? 'badge-inactive', 'attr') ?>">
                            <?= esc($etiquetasPago[$estadoPago] ?? $estadoPago) ?>
                        </span>
                    </div>

                    <div class="payment-detail">
                        <span>Monto: <strong>S/ <?= number_format((float) $pago['monto'], 2, '.', ',') ?></strong></span>
                        <span>Método: <?= esc($metodos[$pago['metodo']] ?? $pago['metodo']) ?></span>
                        <span>Periodo: <?= esc($pago['periodo_desde']) ?> al <?= esc($pago['periodo_hasta']) ?></span>
                        <span>Fecha de pago: <?= esc($pago['fecha_pago']) ?></span>
                        <?php if (! empty($pago['referencia'])): ?>
                            <span>Referencia: <?= esc($pago['referencia']) ?></span>
                        <?php endif; ?>
                        <?php if (! empty($pago['comprobante_ruta'])): ?>
                            <span>Comprobante: <?= esc($pago['comprobante_ruta']) ?></span>
                        <?php endif; ?>
                        <?php if ($pago['confirmado_at'] !== null): ?>
                            <span>Confirmado el: <?= esc($pago['confirmado_at']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($esPendiente): ?>
                        <div class="payment-actions">
                            <form action="<?= base_url('admin/pagos/' . $idPago . '/confirmar') ?>" method="post">
                                <?= csrf_field() ?>
                                <button
                                    type="submit"
                                    class="btn btn-small"
                                    onclick="return confirm('¿Confirmar este pago y activar la suscripción?');"
                                >Confirmar</button>
                            </form>

                            <form action="<?= base_url('admin/pagos/' . $idPago . '/rechazar') ?>" method="post">
                                <?= csrf_field() ?>
                                <button
                                    type="submit"
                                    class="btn btn-danger btn-small"
                                    onclick="return confirm('¿Rechazar este pago?');"
                                >Rechazar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
