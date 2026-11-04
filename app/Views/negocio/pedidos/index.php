<?= $this->extend('layouts/panel') ?>

<?= $this->section('styles') ?>
<style>
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

    .pedidos-wrap { margin-top: 16px; }

    .pedido-table {
        width: 100%;
        border-collapse: collapse;
    }

    .pedido-table thead { display: none; }
    .pedido-table tbody { display: block; }

    .pedido-table tr {
        display: block;
        margin-bottom: 10px;
        padding: 12px;
        border: 1px solid #e6efec;
        border-radius: 14px;
        background: #fff;
    }

    .pedido-table td {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 5px 0;
        border: 0;
        font-size: 0.86rem;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .pedido-table td::before {
        content: attr(data-label);
        flex: 0 0 auto;
        color: var(--muted);
        font-weight: 650;
        text-align: left;
    }

    .pedido-table td[data-label="Acciones"] { padding-top: 9px; }
    .pedido-table td[data-label="Acciones"]::before { display: none; }

    .pedido-codigo {
        font-weight: 800;
        color: var(--primary-dark);
    }

    .estado-badge {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .estado-nuevo { background: #fee2e2; color: #991b1b; }
    .estado-confirmado { background: #fef3c7; color: #92400e; }
    .estado-en_proceso { background: #dbeafe; color: #1e40af; }
    .estado-entregado { background: #dcfce7; color: #166534; }
    .estado-cancelado { background: #f1f5f9; color: #475569; }

    .pedido-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 6px;
        width: 100%;
    }

    .pedido-actions form { display: inline; }

    @media (min-width: 720px) {
        .pedido-table thead { display: table-header-group; }
        .pedido-table tbody { display: table-row-group; }

        .pedido-table th {
            padding: 10px 8px;
            border-bottom: 1px solid var(--line);
            color: var(--muted);
            font-size: 0.76rem;
            font-weight: 750;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .pedido-table tr {
            display: table-row;
            margin: 0;
            padding: 0;
            border: 0;
            border-bottom: 1px solid #eef4f2;
            border-radius: 0;
            background: transparent;
        }

        .pedido-table td {
            display: table-cell;
            padding: 11px 8px;
            border-bottom: 1px solid #eef4f2;
            text-align: left;
            vertical-align: middle;
        }

        .pedido-table td::before { display: none; }
        .pedido-actions { justify-content: flex-start; }
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

$opcionesFiltro = [
    'todos'      => 'Todos',
    'nuevo'      => 'Nuevo',
    'confirmado' => 'Confirmado',
    'en_proceso' => 'En proceso',
    'entregado'  => 'Entregado',
    'cancelado'  => 'Cancelado',
];
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Pedidos</p>
            <h1>Mis Pedidos</h1>
            <p class="subtitle">Revisa los pedidos recibidos desde tu catálogo y actualiza su estado.</p>
        </div>
    </div>

    <nav class="filters" aria-label="Filtros de pedidos">
        <?php foreach ($opcionesFiltro as $valor => $etiqueta): ?>
            <?php
            $urlFiltro = $valor === 'todos'
                ? base_url('negocio/pedidos')
                : base_url('negocio/pedidos') . '?estado=' . $valor;
            ?>
            <a href="<?= esc($urlFiltro, 'attr') ?>" class="<?= $filtro === $valor ? 'active' : '' ?>">
                <?= esc($etiqueta) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($pedidos === []): ?>
        <div class="empty-state">
            <h2><?= $filtro === 'todos' ? 'Aún no tienes pedidos' : 'No hay pedidos en este estado' ?></h2>
            <p>
                <?= $filtro === 'todos'
                    ? 'Cuando un cliente envíe un pedido desde tu catálogo, aparecerá aquí.'
                    : 'Prueba con otro filtro para ver tus pedidos.' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="pedidos-wrap">
            <table class="pedido-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <?php
                        $idPedido     = (int) $pedido['id_pedido'];
                        $estado       = (string) $pedido['estado'];
                        $etiqueta     = $etiquetasEstado[$estado] ?? $estado;
                        $siguiente    = $pedido['siguiente_estado'] ?? null;
                        $fecha        = $pedido['created_at'] !== null
                            ? date('d/m/Y H:i', strtotime((string) $pedido['created_at']))
                            : '—';
                        ?>
                        <tr>
                            <td data-label="Código">
                                <span class="pedido-codigo"><?= esc($pedido['codigo']) ?></span>
                            </td>
                            <td data-label="Cliente"><?= esc($pedido['cliente_nombre']) ?></td>
                            <td data-label="Teléfono"><?= esc($pedido['cliente_telefono']) ?></td>
                            <td data-label="Total">S/ <?= number_format((float) $pedido['total'], 2, '.', ',') ?></td>
                            <td data-label="Estado">
                                <span class="estado-badge estado-<?= esc($estado, 'attr') ?>"><?= esc($etiqueta) ?></span>
                            </td>
                            <td data-label="Fecha"><?= esc($fecha) ?></td>
                            <td data-label="Acciones">
                                <div class="pedido-actions">
                                    <a
                                        class="btn btn-secondary btn-small"
                                        href="<?= esc(base_url('negocio/pedidos/' . $idPedido), 'attr') ?>"
                                    >Ver detalle</a>

                                    <?php if ($siguiente !== null): ?>
                                        <form action="<?= esc(base_url('negocio/pedidos/' . $idPedido . '/estado'), 'attr') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="estado" value="<?= esc($siguiente, 'attr') ?>">
                                            <button type="submit" class="btn btn-small">
                                                Avanzar a <?= esc($etiquetasEstado[$siguiente] ?? $siguiente) ?>
                                            </button>
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
