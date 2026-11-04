<?= $this->extend('layouts/panel') ?>

<?= $this->section('styles') ?>
<style>
    .plan-current {
        margin-bottom: 18px;
        padding: 18px;
        border: 1px solid #ccfbf1;
        border-radius: 16px;
        background: linear-gradient(145deg, #f0fdfa, #ffffff);
    }

    .plan-current-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .plan-name {
        margin: 0;
        font-size: 1.35rem;
        letter-spacing: -0.02em;
    }

    .plan-price {
        margin: 4px 0 0;
        color: var(--primary-dark);
        font-size: 0.95rem;
        font-weight: 750;
    }

    .plan-dates {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 16px;
        margin: 14px 0 0;
        color: var(--muted);
        font-size: 0.83rem;
    }

    .usage-list {
        display: grid;
        gap: 14px;
        margin-top: 18px;
    }

    .usage-item {
        padding: 12px 13px;
        border: 1px solid #e6efec;
        border-radius: 13px;
        background: #fff;
    }

    .usage-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
        font-size: 0.88rem;
    }

    .usage-head strong { color: var(--ink); }
    .usage-value { color: var(--muted); font-size: 0.82rem; }

    .progress {
        height: 9px;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8e6;
    }

    .progress span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #0f766e, #14b8a6);
    }

    .progress.progress-unlimited span {
        background: linear-gradient(90deg, #99f6e4, #5eead4);
    }

    .pending-banner {
        margin-bottom: 18px;
        padding: 14px 15px;
        border: 1px solid #fde68a;
        border-radius: 14px;
        background: #fffbeb;
        color: #92400e;
        font-size: 0.9rem;
    }

    .pending-banner strong { display: block; margin-bottom: 3px; }

    .plans-grid {
        display: grid;
        gap: 12px;
        margin-top: 14px;
    }

    .plan-card {
        display: flex;
        flex-direction: column;
        padding: 15px;
        border: 1px solid #e6efec;
        border-radius: 15px;
        background: #fff;
    }

    .plan-card.is-current {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.08);
    }

    .plan-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .plan-card h3 {
        margin: 0;
        font-size: 1.05rem;
    }

    .plan-card-price {
        margin: 3px 0 0;
        color: var(--primary-dark);
        font-weight: 800;
    }

    .feature-list {
        margin: 12px 0 15px;
        padding: 0;
        list-style: none;
        color: #334155;
        font-size: 0.83rem;
    }

    .feature-list li {
        position: relative;
        padding: 3px 0 3px 17px;
    }

    .feature-list li::before {
        position: absolute;
        top: 3px;
        left: 0;
        color: var(--primary);
        content: "✓";
        font-weight: 800;
    }

    .plan-card form { margin-top: auto; }

    .plan-card .btn { width: 100%; }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-expired {
        background: #fee2e2;
        color: #991b1b;
    }

    @media (min-width: 680px) {
        .plans-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (min-width: 860px) {
        .plans-grid { grid-template-columns: repeat(3, 1fr); }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$etiquetasEstado = [
    'prueba'         => 'Prueba',
    'activa'         => 'Activa',
    'pendiente_pago' => 'Pendiente de pago',
    'vencida'        => 'Vencida',
    'cancelada'      => 'Cancelada',
];

$clasesEstado = [
    'prueba'         => 'badge-active',
    'activa'         => 'badge-active',
    'pendiente_pago' => 'badge-pending',
    'vencida'        => 'badge-expired',
    'cancelada'      => 'badge-expired',
];

$limite = static fn ($valor): string => $valor === null ? 'Ilimitado' : (string) (int) $valor;

$progreso = static function (int $actual, $maximo): ?int {
    if ($maximo === null) {
        return null;
    }

    $maximo = (int) $maximo;

    if ($maximo <= 0) {
        return 100;
    }

    return min(100, max(0, (int) round(($actual / $maximo) * 100)));
};
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Suscripción</p>
            <h1>Mi Plan</h1>
            <p class="subtitle">Revisa tu plan actual, tus límites y las opciones para mejorar.</p>
        </div>
        <a class="btn btn-secondary" href="<?= base_url('dashboard') ?>">Volver al panel</a>
    </div>

    <?php if ($suscripcion === null): ?>
        <div class="empty-state">
            <h2>Sin suscripción vigente</h2>
            <p>No encontramos un plan activo para tu negocio. Contacta al administrador.</p>
        </div>
    <?php else: ?>
        <div class="plan-current">
            <div class="plan-current-head">
                <div>
                    <h2 class="plan-name"><?= esc($plan['nombre'] ?? 'Plan actual') ?></h2>
                    <p class="plan-price">
                        S/ <?= number_format((float) $suscripcion['precio_pactado'], 2, '.', ',') ?>
                        <?= (float) $suscripcion['precio_pactado'] > 0 ? ' / mes' : '' ?>
                    </p>
                </div>
                <span class="badge <?= esc($clasesEstado[$suscripcion['estado']] ?? 'badge-inactive', 'attr') ?>">
                    <?= esc($etiquetasEstado[$suscripcion['estado']] ?? $suscripcion['estado']) ?>
                </span>
            </div>

            <div class="plan-dates">
                <span>Inicio: <strong><?= esc($suscripcion['fecha_inicio']) ?></strong></span>
                <span>
                    Fin:
                    <strong><?= $suscripcion['fecha_fin'] !== null ? esc($suscripcion['fecha_fin']) : 'Sin vencimiento' ?></strong>
                </span>
            </div>

            <div class="usage-list">
                <div class="usage-item">
                    <div class="usage-head">
                        <strong>Productos</strong>
                        <span class="usage-value">
                            <?= (int) $uso['productos'] ?> / <?= esc($limite($plan['max_productos'] ?? null)) ?>
                        </span>
                    </div>
                    <?php $porcentajeProductos = $progreso((int) $uso['productos'], $plan['max_productos'] ?? null); ?>
                    <div class="progress <?= $porcentajeProductos === null ? 'progress-unlimited' : '' ?>">
                        <span style="width: <?= $porcentajeProductos ?? 100 ?>%"></span>
                    </div>
                </div>

                <div class="usage-item">
                    <div class="usage-head">
                        <strong>Usuarios activos</strong>
                        <span class="usage-value">
                            <?= (int) $uso['usuarios'] ?> / <?= esc($limite($plan['max_usuarios'] ?? null)) ?>
                        </span>
                    </div>
                    <?php $porcentajeUsuarios = $progreso((int) $uso['usuarios'], $plan['max_usuarios'] ?? null); ?>
                    <div class="progress <?= $porcentajeUsuarios === null ? 'progress-unlimited' : '' ?>">
                        <span style="width: <?= $porcentajeUsuarios ?? 100 ?>%"></span>
                    </div>
                </div>

                <div class="usage-item">
                    <div class="usage-head">
                        <strong>Pedidos este mes</strong>
                        <span class="usage-value">
                            <?= (int) $uso['pedidos'] ?> / <?= esc($limite($plan['max_pedidos_mes'] ?? null)) ?>
                        </span>
                    </div>
                    <?php $porcentajePedidos = $progreso((int) $uso['pedidos'], $plan['max_pedidos_mes'] ?? null); ?>
                    <div class="progress <?= $porcentajePedidos === null ? 'progress-unlimited' : '' ?>">
                        <span style="width: <?= $porcentajePedidos ?? 100 ?>%"></span>
                    </div>
                </div>

                <div class="usage-item">
                    <div class="usage-head">
                        <strong>Imágenes por producto</strong>
                        <span class="usage-value"><?= esc($limite($plan['max_imagenes_por_producto'] ?? null)) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($pendiente !== null): ?>
            <div class="pending-banner">
                <strong>Tienes una solicitud de upgrade pendiente de confirmación de pago</strong>
                Plan solicitado: <?= esc($planPendiente['nombre'] ?? 'Plan') ?>
                · S/ <?= number_format((float) $pendiente['precio_pactado'], 2, '.', ',') ?>
                · Solicitado el <?= esc($pendiente['created_at']) ?>
            </div>
        <?php endif; ?>

        <div class="page-head" style="margin-top:22px; margin-bottom:0">
            <div>
                <p class="eyebrow">Mejorar mi plan</p>
                <h2 style="margin:0; font-size:1.1rem">Planes disponibles</h2>
            </div>
        </div>

        <div class="plans-grid">
            <?php foreach ($planes as $planDisponible): ?>
                <?php
                $idPlanDisponible = (int) $planDisponible['id_plan'];
                $esActual         = (bool) $planDisponible['es_actual'];
                $esSuperior       = (bool) $planDisponible['es_superior'];
                ?>
                <article class="plan-card <?= $esActual ? 'is-current' : '' ?>">
                    <div class="plan-card-head">
                        <div>
                            <h3><?= esc($planDisponible['nombre']) ?></h3>
                            <p class="plan-card-price">
                                S/ <?= number_format((float) $planDisponible['precio_mensual'], 2, '.', ',') ?>
                                <?= (float) $planDisponible['precio_mensual'] > 0 ? ' / mes' : '' ?>
                            </p>
                        </div>
                        <?php if ($esActual): ?>
                            <span class="badge badge-active">Plan actual</span>
                        <?php endif; ?>
                    </div>

                    <?php if (! empty($planDisponible['descripcion'])): ?>
                        <p class="subtitle" style="margin:6px 0 0; font-size:.82rem"><?= esc($planDisponible['descripcion']) ?></p>
                    <?php endif; ?>

                    <ul class="feature-list">
                        <li>Productos: <?= esc($limite($planDisponible['max_productos'])) ?></li>
                        <li>Imágenes por producto: <?= esc($limite($planDisponible['max_imagenes_por_producto'])) ?></li>
                        <li>Usuarios: <?= esc($limite($planDisponible['max_usuarios'])) ?></li>
                        <li>Pedidos al mes: <?= esc($limite($planDisponible['max_pedidos_mes'])) ?></li>
                        <?php if ((int) $planDisponible['tiene_dominio_propio'] === 1): ?><li>Dominio propio</li><?php endif; ?>
                        <?php if ((int) $planDisponible['tiene_analytics'] === 1): ?><li>Analytics</li><?php endif; ?>
                        <?php if ((int) $planDisponible['tiene_reportes'] === 1): ?><li>Reportes</li><?php endif; ?>
                    </ul>

                    <?php if ($esActual): ?>
                        <button type="button" class="btn btn-secondary" disabled>Plan actual</button>
                    <?php elseif ($pendiente !== null): ?>
                        <button type="button" class="btn btn-secondary" disabled>Esperando confirmación</button>
                    <?php elseif ($esSuperior): ?>
                        <form action="<?= base_url('negocio/mi-plan/upgrade/' . $idPlanDisponible) ?>" method="post">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn">Solicitar upgrade</button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled>Incluido en tu plan</button>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
