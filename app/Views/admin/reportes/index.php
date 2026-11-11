<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<style>
    .report-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .section-head {
        margin-bottom: 16px;
    }

    .section-head h2 {
        margin: 0 0 4px;
        font-size: 1.08rem;
    }

    .section-head p {
        margin: 0;
        color: var(--muted);
        font-size: 0.86rem;
    }

    .report-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .chart-card {
        padding: 16px;
        border: 1px solid #e6edf5;
        border-radius: 14px;
        background: #fff;
    }

    .chart-card h3 {
        margin: 0 0 12px;
        color: #334155;
        font-size: 0.92rem;
    }

    .chart-box {
        position: relative;
        height: 320px;
    }

    .chart-box canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .chart-empty {
        display: grid;
        place-items: center;
        height: 320px;
        padding: 20px;
        border: 1px dashed var(--line);
        border-radius: 13px;
        color: var(--muted);
        font-size: 0.9rem;
        text-align: center;
    }

    .data-table th.num,
    .data-table td.num {
        text-align: right;
    }

    .data-table th.center,
    .data-table td.center {
        text-align: center;
    }

    .stat-card.text-center {
        text-align: center;
    }

    .data-table tr.total-row td {
        background: #f8fafc;
        font-weight: 800;
    }

    @media (min-width: 980px) {
        .report-grid { grid-template-columns: 1.2fr 1fr; align-items: start; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$pdfUrl = base_url('admin/reportes/pdf') . '?' . http_build_query([
    'agrupar_por' => $filtros['agrupar_por'],
    'ciudad_id'   => $filtros['ciudad_id'],
    'distrito_id' => $filtros['distrito_id'],
    'plan_id'     => $filtros['plan_id'],
    'metodo_pago' => $filtros['metodo_pago'],
]);

$etiquetas    = array_map(static fn (array $fila): string => $fila['etiqueta'], $reporte);
$mrrValues    = array_map(static fn (array $fila): float => (float) $fila['mrr'], $reporte);
$pedidosValues = array_map(static fn (array $fila): int => (int) $fila['total_pedidos'], $reporte);

$totalNegocios = 0;
$totalPedidos  = 0;
$totalMrr      = 0.0;

foreach ($reporte as $fila) {
    $totalNegocios += (int) $fila['total_negocios'];
    $totalPedidos  += (int) $fila['total_pedidos'];
    $totalMrr      += (float) $fila['mrr'];
}

$hayMetricas = $totalMrr > 0 || $totalPedidos > 0;
?>
<section class="card">
    <div class="page-head">
        <div>
            <p class="eyebrow">Administración global</p>
            <h1>Reportes</h1>
            <p class="subtitle">Genera reportes personalizados de negocios, pedidos y suscripciones.</p>
        </div>
        <div class="report-actions">
            <a class="btn" href="<?= esc($pdfUrl, 'attr') ?>">Descargar Reporte PDF</a>
        </div>
    </div>

    <form class="filter-form" action="<?= base_url('admin/reportes') ?>" method="get">
        <div class="filter-field">
            <label for="agrupar_por">Agrupar por:</label>
            <select id="agrupar_por" name="agrupar_por">
                <?php foreach (['ciudad' => 'Ciudad', 'distrito' => 'Distrito', 'plan' => 'Plan'] as $valor => $etiqueta): ?>
                    <option value="<?= esc($valor, 'attr') ?>" <?= $filtros['agrupar_por'] === $valor ? 'selected' : '' ?>>
                        <?= esc($etiqueta) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="ciudad_id">Filtrar por Ciudad</label>
            <select id="ciudad_id" name="ciudad_id">
                <option value="0" <?= (int) $filtros['ciudad_id'] === 0 ? 'selected' : '' ?>>Todas</option>
                <?php foreach ($ciudades as $ciudad): ?>
                    <option
                        value="<?= (int) $ciudad['id_ciudad'] ?>"
                        <?= (int) $filtros['ciudad_id'] === (int) $ciudad['id_ciudad'] ? 'selected' : '' ?>
                    ><?= esc($ciudad['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="distrito_id">Filtrar por Distrito</label>
            <select id="distrito_id" name="distrito_id">
                <option value="0" data-ciudad="0" <?= (int) $filtros['distrito_id'] === 0 ? 'selected' : '' ?>>Todos</option>
                <?php foreach ($distritos as $distrito): ?>
                    <option
                        value="<?= (int) $distrito['id_distrito'] ?>"
                        data-ciudad="<?= (int) $distrito['ciudad_id'] ?>"
                        <?= (int) $filtros['distrito_id'] === (int) $distrito['id_distrito'] ? 'selected' : '' ?>
                    ><?= esc($distrito['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="plan_id">Filtrar por Plan</label>
            <select id="plan_id" name="plan_id">
                <option value="0" <?= (int) $filtros['plan_id'] === 0 ? 'selected' : '' ?>>Todos</option>
                <?php foreach ($planes as $plan): ?>
                    <option
                        value="<?= (int) $plan['id_plan'] ?>"
                        <?= (int) $filtros['plan_id'] === (int) $plan['id_plan'] ? 'selected' : '' ?>
                    ><?= esc($plan['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="metodo_pago">Filtrar por Pago</label>
            <select id="metodo_pago" name="metodo_pago">
                <option value="" <?= $filtros['metodo_pago'] === '' ? 'selected' : '' ?>>Todos</option>
                <?php foreach ($metodosPago as $valor => $etiqueta): ?>
                    <option
                        value="<?= esc($valor, 'attr') ?>"
                        <?= $filtros['metodo_pago'] === $valor ? 'selected' : '' ?>
                    ><?= esc($etiqueta) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-small">Aplicar Filtros</button>
            <a class="btn btn-secondary btn-small" href="<?= base_url('admin/reportes') ?>">Limpiar</a>
        </div>
    </form>

    <div class="stat-grid">
        <article class="stat-card text-center">
            <p class="stat-label">MRR actual</p>
            <p class="stat-value small">S/ <?= number_format((float) $metricas['mrr'], 2, '.', ',') ?></p>
        </article>
        <article class="stat-card text-center">
            <p class="stat-label">Negocios activos</p>
            <p class="stat-value"><?= (int) $metricas['negocios_activos'] ?></p>
        </article>
        <article class="stat-card text-center">
            <p class="stat-label">Negocios suspendidos</p>
            <p class="stat-value"><?= (int) $metricas['negocios_suspendidos'] ?></p>
        </article>
        <article class="stat-card text-center">
            <p class="stat-label">Pedidos del mes</p>
            <p class="stat-value"><?= (int) $metricas['pedidos_mes'] ?></p>
        </article>
        <article class="stat-card text-center">
            <p class="stat-label">Ingresos por ventas</p>
            <p class="stat-value small">S/ <?= number_format((float) $metricas['ingresos'], 2, '.', ',') ?></p>
        </article>
    </div>
</section>

<section class="card">
    <div class="section-head">
        <h2><?= esc($tituloTabla) ?></h2>
        <p>
            Agrupación: <?= esc($etiquetaAgrupacion) ?>
            · Ciudad: <?= esc($ciudadNombre) ?>
            · Distrito: <?= esc($distritoNombre) ?>
            · Plan: <?= esc($planNombre) ?>
            · Pago: <?= esc($metodoPagoNombre) ?>
        </p>
    </div>

    <div class="report-grid">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= esc($etiquetaAgrupacion) ?></th>
                        <th class="center">Negocios Activos</th>
                        <th class="center">Pedidos del Mes</th>
                        <th class="num">MRR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reporte === []): ?>
                        <tr>
                            <td colspan="4">No hay datos para los filtros seleccionados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reporte as $fila): ?>
                            <tr>
                                <td data-label="<?= esc($etiquetaAgrupacion, 'attr') ?>"><strong><?= esc($fila['etiqueta']) ?></strong></td>
                                <td class="center" data-label="Negocios Activos"><?= (int) $fila['total_negocios'] ?></td>
                                <td class="center" data-label="Pedidos del Mes"><?= (int) $fila['total_pedidos'] ?></td>
                                <td class="num" data-label="MRR">S/ <?= number_format((float) $fila['mrr'], 2, '.', ',') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td data-label="Total"><strong>Total</strong></td>
                            <td class="center" data-label="Negocios Activos"><?= $totalNegocios ?></td>
                            <td class="center" data-label="Pedidos del Mes"><?= $totalPedidos ?></td>
                            <td class="num" data-label="MRR">S/ <?= number_format($totalMrr, 2, '.', ',') ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <article class="chart-card">
            <h3>MRR y pedidos por <?= esc(strtolower($etiquetaAgrupacion)) ?></h3>

            <?php if (! $hayMetricas): ?>
                <div class="chart-empty">No hay métricas para los filtros seleccionados.</div>
            <?php else: ?>
                <div class="chart-box">
                    <canvas id="chart-reporte" aria-label="MRR y pedidos por <?= esc($etiquetaAgrupacion, 'attr') ?>" role="img"></canvas>
                </div>
            <?php endif; ?>
        </article>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    'use strict';

    const ciudadSelect   = document.getElementById('ciudad_id');
    const distritoSelect = document.getElementById('distrito_id');

    if (!ciudadSelect || !distritoSelect) {
        return;
    }

    const opciones = Array.from(distritoSelect.options);

    const sincronizarDistritos = () => {
        const ciudadId = ciudadSelect.value;

        opciones.forEach((opcion) => {
            const ciudadOpcion = opcion.dataset.ciudad || '0';
            const visible = ciudadOpcion === '0' || ciudadId === '0' || ciudadOpcion === ciudadId;

            opcion.hidden   = !visible;
            opcion.disabled = !visible;
        });

        const seleccionada = distritoSelect.options[distritoSelect.selectedIndex];

        if (seleccionada && seleccionada.disabled) {
            distritoSelect.value = '0';
        }
    };

    ciudadSelect.addEventListener('change', sincronizarDistritos);
    sincronizarDistritos();
})();
</script>
<script>
(() => {
    'use strict';

    if (typeof Chart === 'undefined') {
        return;
    }

    const canvas = document.getElementById('chart-reporte');

    if (!canvas) {
        return;
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: <?= json_encode($etiquetas, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    type: 'bar',
                    label: 'MRR (S/)',
                    data: <?= json_encode($mrrValues) ?>,
                    backgroundColor: '#0284c7',
                    borderRadius: 6,
                    maxBarThickness: 44,
                    yAxisID: 'y'
                },
                {
                    type: 'line',
                    label: 'Pedidos del mes',
                    data: <?= json_encode($pedidosValues) ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: '#f59e0b',
                    borderWidth: 2,
                    pointRadius: 3,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (contexto) => {
                            const valor = contexto.dataset.yAxisID === 'y'
                                ? 'S/ ' + Number(contexto.raw).toFixed(2)
                                : contexto.raw;

                            return contexto.dataset.label + ': ' + valor;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: { display: true, text: 'MRR (S/)' },
                    ticks: { callback: (valor) => 'S/ ' + valor }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Pedidos' },
                    ticks: { precision: 0 }
                }
            }
        }
    });
})();
</script>
<?= $this->endSection() ?>
