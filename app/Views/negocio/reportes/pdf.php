<?php
$etiquetasEstado = [
    'nuevo'      => 'Nuevo',
    'confirmado' => 'Confirmado',
    'en_proceso' => 'En proceso',
    'entregado'  => 'Entregado',
    'cancelado'  => 'Cancelado',
];
$inicial = mb_strtoupper(mb_substr($nombreComercial, 0, 1));

$logoSrc = null;

if (! empty($logo)) {
    $rutaLogo = (string) $logo;

    if (is_file($rutaLogo)) {
        $mime      = @mime_content_type($rutaLogo);
        $contenido = @file_get_contents($rutaLogo);

        if (is_string($mime)
            && $contenido !== false
            && in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
        ) {
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode($contenido);
        }
    } elseif (str_starts_with($rutaLogo, 'data:image/')) {
        $logoSrc = $rutaLogo;
    }
}

$barra = static function (float $valor, float $maximo, string $color = '#0f766e'): string {
    $porcentaje = $maximo > 0 ? min(100, max(0, ($valor / $maximo) * 100)) : 0;

    return '<div style="background-color:' . $color . ';height:9px;width:' . number_format($porcentaje, 2, '.', '') . '%;"></div>';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de <?= esc($tipoTitulo) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 26px 24px;
            color: #0f172a;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .header-table td { vertical-align: middle; padding: 0; }
        .logo-cell { width: 160px; text-align: center; }
        .logo-box {
            width: 64px;
            margin: 0 auto;
            padding: 16px 0;
            border-radius: 12px;
            background-color: #0f766e;
            color: #ffffff;
            font-size: 26px;
            font-weight: bold;
            text-align: center;
            line-height: 1;
        }
        .brand { font-size: 17px; font-weight: bold; color: #115e59; }
        .report-title { font-size: 13px; font-weight: bold; margin-top: 3px; }
        .report-range { color: #64748b; margin-top: 3px; }
        .meta { text-align: right; color: #64748b; font-size: 9px; }
        .divider { height: 2px; background-color: #0f766e; margin-bottom: 18px; }
        h2 { font-size: 13px; margin: 18px 0 8px; color: #115e59; }
        .summary-table { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px 8px; }
        .summary-cell {
            width: 33%;
            padding: 10px;
            border: 1px solid #ccfbf1;
            border-radius: 8px;
            background-color: #f0fdfa;
            text-align: center;
        }
        .summary-label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .summary-value { color: #115e59; font-size: 15px; font-weight: bold; margin-top: 2px; }
        .bars { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .bars td { padding: 5px 4px; vertical-align: middle; border-bottom: 1px solid #eef4f2; }
        .bar-label { width: 32%; color: #334155; }
        .bar-track-cell { width: 50%; }
        .bar-track { width: 100%; background-color: #e6efec; height: 9px; }
        .bar-value { width: 18%; text-align: right; color: #115e59; font-weight: bold; white-space: nowrap; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .data-table th {
            background-color: #f0fdfa;
            color: #115e59;
            font-size: 9px;
            text-align: left;
            text-transform: uppercase;
            padding: 6px;
            border-bottom: 1px solid #ccfbf1;
        }
        .data-table td { padding: 6px; border-bottom: 1px solid #eef4f2; }
        .data-table td.num, .data-table th.num { text-align: right; }
        .empty { padding: 14px; border: 1px dashed #cbd5e1; color: #64748b; text-align: center; }
        .footer {
            margin-top: 22px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <?php if ($logoSrc !== null): ?>
                    <img src="<?= esc($logoSrc, 'attr') ?>" style="display: block; margin: 0 auto; max-width: 150px; height: auto; max-height: 100px; object-fit: contain;" alt="Logo">
                <?php else: ?>
                    <div class="logo-box"><?= esc($inicial) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <div class="brand"><?= esc($nombreComercial) ?></div>
                <div class="report-title">Reporte de <?= esc($tipoTitulo) ?></div>
                <div class="report-range">
                    Periodo: <?= esc(date('d/m/Y', strtotime($desde))) ?> al <?= esc(date('d/m/Y', strtotime($hasta))) ?>
                </div>
            </td>
            <td class="meta">
                Generado el<br><?= esc($generadoEn) ?><br>Katalo.pe
            </td>
        </tr>
    </table>
    <div class="divider"></div>

    <?php if ($tipo === 'ventas'): ?>
        <table class="summary-table">
            <tr>
                <td class="summary-cell">
                    <div class="summary-label">Total vendido</div>
                    <div class="summary-value">S/ <?= number_format((float) $metricas['total_vendido'], 2, '.', ',') ?></div>
                </td>
                <td class="summary-cell">
                    <div class="summary-label">Pedidos</div>
                    <div class="summary-value"><?= (int) $metricas['cantidad_pedidos'] ?></div>
                </td>
                <td class="summary-cell">
                    <div class="summary-label">Ticket promedio</div>
                    <div class="summary-value">S/ <?= number_format((float) $metricas['ticket_promedio'], 2, '.', ',') ?></div>
                </td>
            </tr>
        </table>

        <?php
        $ventasConDatos = array_values(array_filter(
            $ventasPorDia,
            static fn (array $fila): bool => (float) $fila['total_vendido'] > 0
        ));
        $maxVenta = 0.0;
        foreach ($ventasConDatos as $fila) {
            $maxVenta = max($maxVenta, (float) $fila['total_vendido']);
        }
        ?>

        <h2>Ventas por día</h2>

        <?php if ($ventasConDatos === []): ?>
            <div class="empty">No hay ventas registradas en este periodo.</div>
        <?php else: ?>
            <table class="bars">
                <?php foreach ($ventasConDatos as $fila): ?>
                    <tr>
                        <td class="bar-label"><?= esc(date('d/m/Y', strtotime($fila['fecha']))) ?></td>
                        <td class="bar-track-cell">
                            <div class="bar-track"><?= $barra((float) $fila['total_vendido'], $maxVenta) ?></div>
                        </td>
                        <td class="bar-value">S/ <?= number_format((float) $fila['total_vendido'], 2, '.', ',') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

    <?php elseif ($tipo === 'productos'): ?>
        <?php
        $maxTop = 0;
        foreach ($topProductos as $fila) {
            $maxTop = max($maxTop, (int) $fila['total_cantidad']);
        }
        $maxBottom = 0;
        foreach ($bottomProductos as $fila) {
            $maxBottom = max($maxBottom, (int) $fila['total_cantidad']);
        }
        ?>

        <h2>Top 5 productos más vendidos</h2>

        <?php if ($topProductos === []): ?>
            <div class="empty">Sin datos en este periodo.</div>
        <?php else: ?>
            <table class="bars">
                <?php foreach ($topProductos as $fila): ?>
                    <tr>
                        <td class="bar-label"><?= esc($fila['producto_titulo']) ?></td>
                        <td class="bar-track-cell">
                            <div class="bar-track"><?= $barra((float) $fila['total_cantidad'], (float) $maxTop) ?></div>
                        </td>
                        <td class="bar-value"><?= (int) $fila['total_cantidad'] ?> u.</td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h2>Bottom 5 productos menos vendidos</h2>

        <?php if ($bottomProductos === []): ?>
            <div class="empty">Sin datos en este periodo.</div>
        <?php else: ?>
            <table class="bars">
                <?php foreach ($bottomProductos as $fila): ?>
                    <tr>
                        <td class="bar-label"><?= esc($fila['producto_titulo']) ?></td>
                        <td class="bar-track-cell">
                            <div class="bar-track"><?= $barra((float) $fila['total_cantidad'], (float) $maxBottom, '#f59e0b') ?></div>
                        </td>
                        <td class="bar-value"><?= (int) $fila['total_cantidad'] ?> u.</td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

    <?php elseif ($tipo === 'pedidos'): ?>
        <?php
        $maxEstado = 0;
        $totalPedidos = 0;
        foreach ($conteoEstados as $fila) {
            $maxEstado    = max($maxEstado, (int) $fila['total']);
            $totalPedidos += (int) $fila['total'];
        }
        ?>

        <h2>Pedidos por estado (<?= (int) $totalPedidos ?> en total)</h2>

        <?php if ((int) $totalPedidos === 0): ?>
            <div class="empty">No hay pedidos registrados en este periodo.</div>
        <?php else: ?>
            <table class="bars">
                <?php foreach ($conteoEstados as $fila): ?>
                    <tr>
                        <td class="bar-label"><?= esc($etiquetasEstado[$fila['estado']] ?? ucfirst($fila['estado'])) ?></td>
                        <td class="bar-track-cell">
                            <div class="bar-track"><?= $barra((float) $fila['total'], (float) $maxEstado) ?></div>
                        </td>
                        <td class="bar-value"><?= (int) $fila['total'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <table class="data-table">
                <thead>
                    <tr><th>Estado</th><th class="num">Pedidos</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($conteoEstados as $fila): ?>
                        <tr>
                            <td><?= esc($etiquetasEstado[$fila['estado']] ?? ucfirst($fila['estado'])) ?></td>
                            <td class="num"><?= (int) $fila['total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php elseif ($tipo === 'clientes'): ?>
        <?php
        $maxCliente = 0.0;
        foreach ($topClientes as $fila) {
            $maxCliente = max($maxCliente, (float) $fila['total_gastado']);
        }
        ?>

        <h2>Top 10 clientes por total gastado</h2>

        <?php if ($topClientes === []): ?>
            <div class="empty">No hay clientes con compras en este periodo.</div>
        <?php else: ?>
            <table class="bars">
                <?php foreach ($topClientes as $fila): ?>
                    <tr>
                        <td class="bar-label"><?= esc($fila['cliente_nombre']) ?></td>
                        <td class="bar-track-cell">
                            <div class="bar-track"><?= $barra((float) $fila['total_gastado'], $maxCliente, '#14b8a6') ?></div>
                        </td>
                        <td class="bar-value">S/ <?= number_format((float) $fila['total_gastado'], 2, '.', ',') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th class="num">Compras</th>
                        <th class="num">Total gastado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topClientes as $fila): ?>
                        <tr>
                            <td><?= esc($fila['cliente_nombre']) ?></td>
                            <td><?= esc($fila['cliente_telefono']) ?></td>
                            <td class="num"><?= (int) $fila['cantidad_compras'] ?></td>
                            <td class="num">S/ <?= number_format((float) $fila['total_gastado'], 2, '.', ',') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <div class="footer">
        Reporte generado automáticamente por Katalo.pe · <?= esc($nombreComercial) ?>
    </div>
</body>
</html>
