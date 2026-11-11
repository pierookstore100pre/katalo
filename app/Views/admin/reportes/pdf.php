<?php
$totalNegocios = 0;
$totalPedidos  = 0;
$totalMrr      = 0.0;
$maxMrr        = 0.0;

foreach ($reporte as $fila) {
    $totalNegocios += (int) $fila['total_negocios'];
    $totalPedidos  += (int) $fila['total_pedidos'];
    $totalMrr      += (float) $fila['mrr'];
    $maxMrr         = max($maxMrr, (float) $fila['mrr']);
}

$filtrosTexto = 'Agrupación: ' . $etiquetaAgrupacion
    . ' · Ciudad: ' . $ciudadNombre
    . ' · Distrito: ' . $distritoNombre
    . ' · Plan: ' . $planNombre
    . ' · Pago: ' . $metodoPagoNombre;

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

$barra = static function (float $valor, float $maximo, string $color = '#0284c7'): string {
    $porcentaje = $maximo > 0 ? min(100, max(0, ($valor / $maximo) * 100)) : 0;

    return '<div style="background-color:' . $color . ';height:9px;width:'
        . number_format($porcentaje, 2, '.', '') . '%;"></div>';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte por <?= esc($etiquetaAgrupacion) ?> · Katalo.pe</title>
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
            background-color: #0284c7;
            color: #ffffff;
            font-size: 26px;
            font-weight: bold;
            text-align: center;
            line-height: 1;
        }
        .brand { font-size: 17px; font-weight: bold; color: #0c4a6e; }
        .report-title { font-size: 13px; font-weight: bold; margin-top: 3px; }
        .report-subtitle { color: #64748b; margin-top: 3px; }
        .meta { text-align: right; color: #64748b; font-size: 9px; }
        .divider { height: 2px; background-color: #0284c7; margin-bottom: 18px; }
        h2 { font-size: 13px; margin: 20px 0 8px; color: #0c4a6e; }
        .summary-table { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px 8px; }
        .summary-cell {
            width: 33%;
            padding: 10px;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            background-color: #f0f9ff;
            text-align: center;
        }
        .summary-label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .summary-value { color: #0c4a6e; font-size: 15px; font-weight: bold; margin-top: 2px; }
        .bars { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .bars td { padding: 5px 4px; vertical-align: middle; border-bottom: 1px solid #eef2f7; }
        .bar-label { width: 32%; color: #334155; }
        .bar-track-cell { width: 50%; }
        .bar-track { width: 100%; background-color: #e2e8f0; height: 9px; }
        .bar-value { width: 18%; text-align: right; color: #0369a1; font-weight: bold; white-space: nowrap; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .data-table th {
            background-color: #f0f9ff;
            color: #0c4a6e;
            font-size: 9px;
            text-align: left;
            text-transform: uppercase;
            padding: 6px;
            border-bottom: 1px solid #bae6fd;
        }
        .data-table td { padding: 6px; border-bottom: 1px solid #eef2f7; }
        .data-table td.num, .data-table th.num { text-align: right; }
        .data-table td.center, .data-table th.center { text-align: center; }
        .data-table tr.total-row td {
            background-color: #f8fafc;
            font-weight: bold;
        }
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
                    <div class="logo-box">K</div>
                <?php endif; ?>
            </td>
            <td>
                <div class="brand">Katalo.pe</div>
                <div class="report-title">Reporte por <?= esc($etiquetaAgrupacion) ?></div>
                <div class="report-subtitle"><?= esc($filtrosTexto) ?></div>
            </td>
            <td class="meta">
                Generado el<br><?= esc($generadoEn) ?><br>Katalo.pe
            </td>
        </tr>
    </table>
    <div class="divider"></div>

    <table class="summary-table">
        <tr>
            <td class="summary-cell">
                <div class="summary-label">Total Negocios</div>
                <div class="summary-value"><?= $totalNegocios ?></div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">MRR del Reporte</div>
                <div class="summary-value">S/ <?= number_format($totalMrr, 2, '.', ',') ?></div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Total Pedidos Mes</div>
                <div class="summary-value"><?= $totalPedidos ?></div>
            </td>
        </tr>
    </table>

    <h2><?= esc($tituloTabla) ?></h2>

    <?php if ($maxMrr === 0.0): ?>
        <div class="empty">No hay MRR registrado para los filtros seleccionados.</div>
    <?php else: ?>
        <table class="bars">
            <?php foreach ($reporte as $fila): ?>
                <tr>
                    <td class="bar-label"><?= esc($fila['etiqueta']) ?></td>
                    <td class="bar-track-cell">
                        <div class="bar-track"><?= $barra((float) $fila['mrr'], $maxMrr) ?></div>
                    </td>
                    <td class="bar-value">S/ <?= number_format((float) $fila['mrr'], 2, '.', ',') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

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
                        <td><?= esc($fila['etiqueta']) ?></td>
                        <td class="center"><?= (int) $fila['total_negocios'] ?></td>
                        <td class="center"><?= (int) $fila['total_pedidos'] ?></td>
                        <td class="num">S/ <?= number_format((float) $fila['mrr'], 2, '.', ',') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td>Total</td>
                    <td class="center"><?= $totalNegocios ?></td>
                    <td class="center"><?= $totalPedidos ?></td>
                    <td class="num">S/ <?= number_format($totalMrr, 2, '.', ',') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Reporte generado automáticamente por Katalo.pe · <?= esc($generadoEn) ?>
    </div>
</body>
</html>
