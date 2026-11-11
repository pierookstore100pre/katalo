<?= $this->extend('layouts/panel') ?>

<?= $this->section('styles') ?>
<style>
    .estado-badge {
        display: inline-block;
        padding: 4px 11px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .estado-nuevo { background: #fee2e2; color: #991b1b; }
    .estado-confirmado { background: #fef3c7; color: #92400e; }
    .estado-en_proceso { background: #dbeafe; color: #1e40af; }
    .estado-entregado { background: #dcfce7; color: #166534; }
    .estado-cancelado { background: #f1f5f9; color: #475569; }

    .resumen-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        margin-top: 18px;
    }

    .resumen-item {
        padding: 11px 12px;
        border: 1px solid #e6efec;
        border-radius: 12px;
        background: #fff;
    }

    .resumen-item span {
        display: block;
        color: var(--muted);
        font-size: 0.74rem;
        font-weight: 750;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .resumen-item strong {
        display: block;
        margin-top: 3px;
        font-size: 0.94rem;
        overflow-wrap: anywhere;
    }

    .detalle-block { margin-top: 24px; }

    .detalle-block h2 {
        margin: 0 0 10px;
        font-size: 1.05rem;
    }

    .detalle-list {
        display: grid;
        gap: 8px;
    }

    .detalle-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 12px;
        border: 1px solid #e6efec;
        border-radius: 12px;
        background: #fff;
    }

    .detalle-item-main { min-width: 0; }

    .detalle-item-title {
        margin: 0;
        font-size: 0.94rem;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .detalle-item-meta {
        margin: 3px 0 0;
        color: var(--muted);
        font-size: 0.8rem;
    }

    .detalle-item-nota {
        margin: 5px 0 0;
        color: #475569;
        font-size: 0.8rem;
        overflow-wrap: anywhere;
    }

    .detalle-item-subtotal {
        flex: 0 0 auto;
        color: var(--primary-dark);
        font-size: 0.94rem;
        font-weight: 800;
        text-align: right;
        white-space: nowrap;
    }

    .detalle-total {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        margin-top: 12px;
        padding: 14px 4px 0;
        border-top: 1px solid var(--line);
        font-size: 1rem;
    }

    .detalle-total strong {
        color: var(--primary-dark);
        font-size: 1.3rem;
    }

    .estado-acciones {
        margin-top: 24px;
        padding-top: 18px;
        border-top: 1px solid var(--line);
    }

    .estado-acciones h2 {
        margin: 0 0 10px;
        font-size: 1.05rem;
    }

    .estado-botones {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .estado-botones form { display: inline; }

    .estado-botones .btn { min-height: 40px; }

    .sin-acciones {
        margin: 0;
        color: var(--muted);
        font-size: 0.88rem;
    }

    .page-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .print-only { display: none; }

    @page { margin: 14mm; }

    @media print {
        body {
            padding: 0 !important;
            background: #fff !important;
            color: #000;
        }

        body.has-impersonation { padding-top: 0 !important; }

        .impersonation-banner,
        .topbar,
        .panel-nav,
        .logout,
        .estado-acciones,
        .page-head,
        .no-print {
            display: none !important;
        }

        .shell {
            max-width: none;
            margin: 0;
            padding: 0;
        }

        .card {
            padding: 0;
            border: 0;
            border-radius: 0;
            background: #fff;
            box-shadow: none;
        }

        .print-only { display: block !important; }

        .print-header {
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid #0f172a;
        }

        .print-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .print-brand img {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }

        .print-brand strong {
            display: block;
            font-size: 1.15rem;
        }

        .print-brand span {
            display: block;
            color: #475569;
            font-size: 0.85rem;
        }

        .resumen-item,
        .detalle-item {
            border-color: #cbd5e1;
            break-inside: avoid;
        }

        .detalle-list { gap: 6px; }

        .estado-badge {
            border: 1px solid #0f172a;
            background: #fff !important;
            color: #000 !important;
        }

        .detalle-total strong { color: #000; }
    }

    @media (min-width: 640px) {
        .resumen-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$etiquetasEstado = [
    'nuevo'      => 'Nuevo',
    'confirmado' => 'Confirmado',
    'en_proceso' => 'En proceso',
    'entregado'  => 'Entregado',
    'cancelado'  => 'Cancelado',
];

$accionesEstado = [
    'confirmado' => 'Confirmar pedido',
    'en_proceso' => 'Pasar a en proceso',
    'entregado'  => 'Marcar como entregado',
    'cancelado'  => 'Cancelar pedido',
];

$estado  = (string) $pedido['estado'];
$fecha   = $pedido['created_at'] !== null
    ? date('d/m/Y H:i', strtotime((string) $pedido['created_at']))
    : '—';
$direccion = trim((string) ($pedido['cliente_direccion'] ?? ''));
$comentario = trim((string) ($pedido['comentario'] ?? ''));
$idNegocio = (int) session()->get('id_negocio');
$nombreComercial = trim((string) (session()->get('nombre_comercial') ?? ''));
$logoUrl = $idNegocio > 0 ? base_url('c/logo/' . $idNegocio) : null;
?>
<section class="card">
    <div class="print-only print-header">
        <div class="print-brand">
            <?php if ($logoUrl !== null): ?>
                <img src="<?= esc($logoUrl, 'attr') ?>" alt="Logo" onerror="this.style.display='none'">
            <?php endif; ?>
            <div>
                <strong><?= esc($nombreComercial !== '' ? $nombreComercial : 'Katalo.pe') ?></strong>
                <span>Pedido <?= esc($pedido['codigo']) ?> · <?= esc($fecha) ?></span>
            </div>
        </div>
    </div>

    <div class="page-head">
        <div>
            <p class="eyebrow">Detalle del pedido</p>
            <h1><?= esc($pedido['codigo']) ?></h1>
            <p class="subtitle">Registrado el <?= esc($fecha) ?></p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn no-print" onclick="window.print()">Imprimir Pedido</button>
            <a class="btn btn-secondary" href="<?= base_url('negocio/pedidos') ?>">Volver a pedidos</a>
        </div>
    </div>

    <div class="resumen-grid">
        <div class="resumen-item">
            <span>Estado</span>
            <strong>
                <span class="estado-badge estado-<?= esc($estado, 'attr') ?>">
                    <?= esc($etiquetasEstado[$estado] ?? $estado) ?>
                </span>
            </strong>
        </div>
        <div class="resumen-item">
            <span>Total</span>
            <strong>S/ <?= number_format((float) $pedido['total'], 2, '.', ',') ?></strong>
        </div>
        <div class="resumen-item">
            <span>Cliente</span>
            <strong><?= esc($pedido['cliente_nombre']) ?></strong>
        </div>
        <div class="resumen-item">
            <span>Teléfono</span>
            <strong><?= esc($pedido['cliente_telefono']) ?></strong>
        </div>
        <div class="resumen-item">
            <span>Dirección de entrega</span>
            <strong><?= $direccion !== '' ? nl2br(esc($direccion)) : '—' ?></strong>
        </div>
        <div class="resumen-item">
            <span>Comentario</span>
            <strong><?= $comentario !== '' ? nl2br(esc($comentario)) : '—' ?></strong>
        </div>
    </div>

    <div class="detalle-block">
        <h2>Productos del pedido</h2>

        <div class="detalle-list">
            <?php foreach ($detalles as $detalle): ?>
                <article class="detalle-item">
                    <div class="detalle-item-main">
                        <h3 class="detalle-item-title"><?= esc($detalle['producto_titulo']) ?></h3>
                        <p class="detalle-item-meta">
                            <?= (int) $detalle['cantidad'] ?> × S/ <?= number_format((float) $detalle['precio_unitario'], 2, '.', ',') ?>
                        </p>
                        <?php if (! empty($detalle['nota'])): ?>
                            <p class="detalle-item-nota"><?= esc($detalle['nota']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="detalle-item-subtotal">
                        S/ <?= number_format((float) $detalle['subtotal'], 2, '.', ',') ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="detalle-total">
            <span>Total</span>
            <strong>S/ <?= number_format((float) $pedido['total'], 2, '.', ',') ?></strong>
        </div>
    </div>

    <div class="estado-acciones">
        <h2>Cambiar estado</h2>

        <?php if ($transiciones === []): ?>
            <p class="sin-acciones">
                Este pedido está en estado <?= esc($etiquetasEstado[$estado] ?? $estado) ?> y no tiene más cambios disponibles.
            </p>
        <?php else: ?>
            <div class="estado-botones">
                <?php foreach ($transiciones as $nuevoEstado): ?>
                    <form action="<?= esc(base_url('negocio/pedidos/' . (int) $pedido['id_pedido'] . '/estado'), 'attr') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="estado" value="<?= esc($nuevoEstado, 'attr') ?>">
                        <button
                            type="submit"
                            class="btn <?= $nuevoEstado === 'cancelado' ? 'btn-danger' : '' ?>"
                        ><?= esc($accionesEstado[$nuevoEstado] ?? $nuevoEstado) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
