<?= $this->extend('layouts/panel') ?>

<?= $this->section('styles') ?>
<style>
    .date-filter {
        display: grid;
        gap: 10px;
        margin-bottom: 18px;
        padding: 14px;
        border: 1px solid #e6efec;
        border-radius: 14px;
        background: #fff;
    }

    .date-filter label {
        margin-bottom: 4px;
        font-size: 0.8rem;
    }

    .date-filter input[type="date"] {
        width: 100%;
        min-height: 46px;
        padding: 10px 12px;
        border: 1px solid var(--line);
        border-radius: 11px;
        background: #fff;
        color: var(--ink);
        font: inherit;
        font-size: 1rem;
        outline: none;
    }

    .date-filter input[type="date"]:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.13);
    }

    .report-range {
        margin: 0 0 16px;
        color: var(--muted);
        font-size: 0.84rem;
    }

    .tabs {
        display: flex;
        gap: 7px;
        overflow-x: auto;
        margin: 0 0 16px;
        padding: 0 0 4px;
        scrollbar-width: none;
    }

    .tabs::-webkit-scrollbar { display: none; }

    .tab {
        flex: 0 0 auto;
        padding: 9px 15px;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: #fff;
        color: var(--muted);
        font: inherit;
        font-size: 0.86rem;
        font-weight: 700;
        cursor: pointer;
    }

    .tab.active {
        border-color: var(--primary);
        background: var(--primary-soft);
        color: var(--primary-dark);
    }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    .metric-grid {
        display: grid;
        gap: 10px;
        margin-bottom: 18px;
    }

    .metric-card {
        padding: 15px;
        border: 1px solid #ccfbf1;
        border-radius: 15px;
        background: linear-gradient(145deg, #f0fdfa, #ffffff);
    }

    .metric-label {
        margin: 0;
        color: var(--muted);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .metric-value {
        margin: 5px 0 0;
        color: var(--primary-dark);
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .metric-value small {
        color: var(--muted);
        font-size: 0.75rem;
        font-weight: 600;
    }

    .chart-box {
        position: relative;
        height: 300px;
        margin-bottom: 18px;
        padding: 12px;
        border: 1px solid #e6efec;
        border-radius: 15px;
        background: #fff;
    }

    .chart-box canvas { width: 100% !important; height: 100% !important; }

    .panel-actions {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 14px;
    }

    .section-title {
        margin: 0 0 10px;
        font-size: 1rem;
        letter-spacing: -0.01em;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 18px;
        font-size: 0.86rem;
    }

    .data-table th {
        padding: 9px 8px;
        border-bottom: 1px solid var(--line);
        color: var(--muted);
        font-size: 0.74rem;
        font-weight: 750;
        text-align: left;
        text-transform: uppercase;
    }

    .data-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #eef4f2;
        overflow-wrap: anywhere;
    }

    .data-table td:not(:first-child),
    .data-table th:not(:first-child) {
        text-align: right;
        white-space: nowrap;
    }

    .two-columns {
        display: grid;
        gap: 18px;
    }

    .locked-state {
        padding: 34px 20px;
        border: 1px dashed #99f6e4;
        border-radius: 16px;
        background: #fff;
        text-align: center;
    }

    .locked-state h2 {
        margin: 0 0 7px;
        font-size: 1.15rem;
    }

    .locked-state p {
        margin: 0 auto 18px;
        max-width: 460px;
        color: var(--muted);
        font-size: 0.9rem;
    }

    .empty-note {
        padding: 22px 16px;
        border: 1px dashed var(--line);
        border-radius: 13px;
        color: var(--muted);
        font-size: 0.9rem;
        text-align: center;
    }

    @media (min-width: 620px) {
        .date-filter {
            grid-template-columns: repeat(3, 1fr) auto;
            align-items: end;
        }

        .metric-grid { grid-template-columns: repeat(3, 1fr); }
        .two-columns { grid-template-columns: 1fr 1fr; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$tipoActivo   = $tipos[$tipo] ?? 'Ventas';
$tipoActual   = array_key_exists($tipo, $tipos) ? $tipo : 'ventas';
$queryPdf     = static function (string $tipoReporte) use ($desde, $hasta): string {
    return base_url('negocio/reportes/pdf') . '?' . http_build_query([
        'tipo'       => $tipoReporte,
        'start_date' => $desde,
        'end_date'   => $hasta,
    ]);
};
$etiquetasEstado = [
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
            <p class="eyebrow">Reportes</p>
            <h1>Reportes de <?= esc(strtolower($tipoActivo)) ?></h1>
            <p class="subtitle">Analiza el rendimiento de tu catálogo en el periodo que elijas.</p>
        </div>
    </div>

    <?php if ($bloqueado): ?>
        <div class="locked-state">
            <h2>Función exclusiva de planes de pago</h2>
            <p>
                Los reportes con gráficos y exportación a PDF están disponibles en los planes
                Básico y Pro. Mejora tu plan para ver tus métricas.
            </p>
            <a class="btn" href="<?= base_url('negocio/mi-plan') ?>">Ver planes</a>
        </div>
    <?php else: ?>
        <form class="date-filter" method="get" action="<?= base_url('negocio/reportes') ?>">
            <input type="hidden" name="tipo" value="<?= esc($tipoActual, 'attr') ?>">
            <div>
                <label for="start_date">Desde</label>
                <input type="date" id="start_date" name="start_date" value="<?= esc($desde, 'attr') ?>" required>
            </div>
            <div>
                <label for="end_date">Hasta</label>
                <input type="date" id="end_date" name="end_date" value="<?= esc($hasta, 'attr') ?>" required>
            </div>
            <div>
                <button type="submit" class="btn">Aplicar filtro</button>
            </div>
        </form>

        <p class="report-range">
            Periodo consultado:
            <strong><?= esc($desde) ?></strong> al <strong><?= esc($hasta) ?></strong>
            · Plan <?= esc($plan['nombre'] ?? '') ?>
        </p>

        <div class="tabs" role="tablist" aria-label="Tipos de reporte">
            <?php foreach ($tipos as $clave => $etiqueta): ?>
                <button
                    type="button"
                    class="tab <?= $clave === $tipoActual ? 'active' : '' ?>"
                    role="tab"
                    data-tab="<?= esc($clave, 'attr') ?>"
                    aria-selected="<?= $clave === $tipoActual ? 'true' : 'false' ?>"
                ><?= esc($etiqueta) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="tab-panel <?= $tipoActual === 'ventas' ? 'active' : '' ?>" id="panel-ventas" role="tabpanel">
            <div class="panel-actions">
                <a class="btn btn-secondary btn-small" href="<?= esc($queryPdf('ventas'), 'attr') ?>">Descargar PDF</a>
            </div>

            <div class="metric-grid">
                <article class="metric-card">
                    <p class="metric-label">Total vendido</p>
                    <p class="metric-value">S/ <?= number_format((float) $metricas['total_vendido'], 2, '.', ',') ?></p>
                </article>
                <article class="metric-card">
                    <p class="metric-label">Pedidos</p>
                    <p class="metric-value">
                        <?= (int) $metricas['cantidad_pedidos'] ?>
                        <small><?= (int) $metricas['cantidad_pedidos'] === 1 ? 'pedido' : 'pedidos' ?></small>
                    </p>
                </article>
                <article class="metric-card">
                    <p class="metric-label">Ticket promedio</p>
                    <p class="metric-value">S/ <?= number_format((float) $metricas['ticket_promedio'], 2, '.', ',') ?></p>
                </article>
            </div>

            <?php if ($ventasPorDia === []): ?>
                <div class="empty-note">No hay ventas registradas en este periodo.</div>
            <?php else: ?>
                <div class="chart-box">
                    <canvas id="chart-ventas" aria-label="Ventas por día" role="img"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-panel <?= $tipoActual === 'productos' ? 'active' : '' ?>" id="panel-productos" role="tabpanel">
            <div class="panel-actions">
                <a class="btn btn-secondary btn-small" href="<?= esc($queryPdf('productos'), 'attr') ?>">Descargar PDF</a>
            </div>

            <?php if ($topProductos === [] && $bottomProductos === []): ?>
                <div class="empty-note">No hay productos vendidos en este periodo.</div>
            <?php else: ?>
                <div class="chart-box">
                    <canvas id="chart-productos" aria-label="Productos más vendidos" role="img"></canvas>
                </div>

                <div class="two-columns">
                    <div>
                        <h2 class="section-title">Top 5 más vendidos</h2>
                        <?php if ($topProductos === []): ?>
                            <div class="empty-note">Sin datos.</div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr><th>Producto</th><th>Cantidad</th><th>Importe</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topProductos as $producto): ?>
                                        <tr>
                                            <td><?= esc($producto['producto_titulo']) ?></td>
                                            <td><?= (int) $producto['total_cantidad'] ?></td>
                                            <td>S/ <?= number_format((float) $producto['total_importe'], 2, '.', ',') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <div>
                        <h2 class="section-title">Bottom 5 menos vendidos</h2>
                        <?php if ($bottomProductos === []): ?>
                            <div class="empty-note">Sin datos.</div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr><th>Producto</th><th>Cantidad</th><th>Importe</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bottomProductos as $producto): ?>
                                        <tr>
                                            <td><?= esc($producto['producto_titulo']) ?></td>
                                            <td><?= (int) $producto['total_cantidad'] ?></td>
                                            <td>S/ <?= number_format((float) $producto['total_importe'], 2, '.', ',') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-panel <?= $tipoActual === 'pedidos' ? 'active' : '' ?>" id="panel-pedidos" role="tabpanel">
            <div class="panel-actions">
                <a class="btn btn-secondary btn-small" href="<?= esc($queryPdf('pedidos'), 'attr') ?>">Descargar PDF</a>
            </div>

            <?php if ((int) $totalPedidos === 0): ?>
                <div class="empty-note">No hay pedidos registrados en este periodo.</div>
            <?php else: ?>
                <div class="chart-box">
                    <canvas id="chart-pedidos" aria-label="Pedidos por estado" role="img"></canvas>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Estado</th><th>Pedidos</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conteoEstados as $fila): ?>
                            <tr>
                                <td><?= esc($etiquetasEstado[$fila['estado']] ?? ucfirst($fila['estado'])) ?></td>
                                <td><?= (int) $fila['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="tab-panel <?= $tipoActual === 'clientes' ? 'active' : '' ?>" id="panel-clientes" role="tabpanel">
            <div class="panel-actions">
                <a class="btn btn-secondary btn-small" href="<?= esc($queryPdf('clientes'), 'attr') ?>">Descargar PDF</a>
            </div>

            <?php if ($topClientes === []): ?>
                <div class="empty-note">No hay clientes con compras en este periodo.</div>
            <?php else: ?>
                <div class="chart-box">
                    <canvas id="chart-clientes" aria-label="Top clientes" role="img"></canvas>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Cliente</th><th>Teléfono</th><th>Compras</th><th>Total gastado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topClientes as $cliente): ?>
                            <tr>
                                <td><?= esc($cliente['cliente_nombre']) ?></td>
                                <td><?= esc($cliente['cliente_telefono']) ?></td>
                                <td><?= (int) $cliente['cantidad_compras'] ?></td>
                                <td>S/ <?= number_format((float) $cliente['total_gastado'], 2, '.', ',') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (! $bloqueado): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    'use strict';

    const DATA = {
        ventas: {
            labels: <?= json_encode(array_map(static fn (array $fila): string => date('d/m', strtotime($fila['fecha'])), $ventasPorDia), JSON_UNESCAPED_UNICODE) ?>,
            values: <?= json_encode(array_map(static fn (array $fila): float => (float) $fila['total_vendido'], $ventasPorDia)) ?>
        },
        productos: {
            labels: <?= json_encode(array_map(static fn (array $fila): string => $fila['producto_titulo'], $topProductos), JSON_UNESCAPED_UNICODE) ?>,
            values: <?= json_encode(array_map(static fn (array $fila): int => (int) $fila['total_cantidad'], $topProductos)) ?>
        },
        pedidos: {
            labels: <?= json_encode(array_map(static fn (array $fila): string => $etiquetasEstado[$fila['estado']] ?? ucfirst($fila['estado']), $conteoEstados), JSON_UNESCAPED_UNICODE) ?>,
            values: <?= json_encode(array_map(static fn (array $fila): int => (int) $fila['total'], $conteoEstados)) ?>
        },
        clientes: {
            labels: <?= json_encode(array_map(static fn (array $fila): string => $fila['cliente_nombre'], $topClientes), JSON_UNESCAPED_UNICODE) ?>,
            values: <?= json_encode(array_map(static fn (array $fila): float => (float) $fila['total_gastado'], $topClientes)) ?>
        }
    };

    const COLORS = ['#0f766e', '#14b8a6', '#5eead4', '#f59e0b', '#ef4444', '#6366f1', '#ec4899', '#84cc16', '#06b6d4', '#a855f7'];
    const charts  = {};

    const configs = {
        ventas: {
            type: 'bar',
            data: {
                labels: DATA.ventas.labels,
                datasets: [{
                    label: 'Total vendido (S/)',
                    data: DATA.ventas.values,
                    backgroundColor: '#0f766e',
                    borderRadius: 6,
                    maxBarThickness: 34
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (contexto) => 'S/ ' + Number(contexto.raw).toFixed(2)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: (valor) => 'S/ ' + valor }
                    },
                    x: {
                        ticks: { autoSkip: true, maxTicksLimit: 12, maxRotation: 60 }
                    }
                }
            }
        },
        productos: {
            type: 'doughnut',
            data: {
                labels: DATA.productos.labels,
                datasets: [{
                    data: DATA.productos.values,
                    backgroundColor: COLORS,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        },
        pedidos: {
            type: 'pie',
            data: {
                labels: DATA.pedidos.labels,
                datasets: [{
                    data: DATA.pedidos.values,
                    backgroundColor: ['#0f766e', '#14b8a6', '#f59e0b', '#6366f1', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        },
        clientes: {
            type: 'bar',
            data: {
                labels: DATA.clientes.labels,
                datasets: [{
                    label: 'Total gastado (S/)',
                    data: DATA.clientes.values,
                    backgroundColor: '#14b8a6',
                    borderRadius: 6,
                    maxBarThickness: 28
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (contexto) => 'S/ ' + Number(contexto.raw).toFixed(2)
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { callback: (valor) => 'S/ ' + valor }
                    }
                }
            }
        }
    };

    function initChart(clave) {
        if (charts[clave] || typeof Chart === 'undefined') {
            return;
        }

        const canvas = document.getElementById('chart-' + clave);

        if (!canvas || !configs[clave]) {
            return;
        }

        charts[clave] = new Chart(canvas, configs[clave]);
    }

    function activarTab(clave) {
        document.querySelectorAll('.tab').forEach((tab) => {
            const activo = tab.dataset.tab === clave;
            tab.classList.toggle('active', activo);
            tab.setAttribute('aria-selected', activo ? 'true' : 'false');
        });

        document.querySelectorAll('.tab-panel').forEach((panel) => {
            panel.classList.toggle('active', panel.id === 'panel-' + clave);
        });

        initChart(clave);

        if (charts[clave]) {
            charts[clave].resize();
        }
    }

    document.querySelectorAll('.tab').forEach((tab) => {
        tab.addEventListener('click', () => activarTab(tab.dataset.tab));
    });

    const activoInicial = document.querySelector('.tab.active');

    if (activoInicial) {
        initChart(activoInicial.dataset.tab);
    }
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
