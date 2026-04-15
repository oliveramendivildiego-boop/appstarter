<?php
$tipoPagoMap = $tipoPagoMap ?? [];
$show_toolbar = !empty($show_toolbar);
$totalesIngresosCaja = $totalesIngresosCaja ?? (object) [];
$resumenIngresosCajaPorTipo = $resumenIngresosCajaPorTipo ?? [];
$ingresosCajaMov = $ingresosCajaMov ?? [];
$totalesEgresos = $totalesEgresos ?? (object) [];
$resumenEgresosPorTipo = $resumenEgresosPorTipo ?? [];
$egresos = $egresos ?? [];
$cajaResumen = $cajaResumen ?? ['ingresos' => 0, 'egresos' => 0, 'saldo_neto' => 0, 'estado' => 'positivo'];
$cajaPorTipo = $cajaPorTipo ?? [];
$cajaPorTipoTotales = $cajaPorTipoTotales ?? ['ingresos' => 0, 'egresos' => 0, 'saldo_neto' => 0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cierre de pagos<?= !empty($cierre_id) ? ' #' . (int) $cierre_id : '' ?> — <?= esc($periodo_texto ?? '') ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #111;
            margin: 12mm 14mm;
            line-height: 1.35;
        }
        h1 {
            font-size: 16pt;
            margin: 0 0 4px 0;
            font-weight: 700;
        }
        h2 {
            font-size: 11pt;
            margin: 16px 0 8px 0;
            border-bottom: 1px solid #333;
            padding-bottom: 2px;
        }
        .muted { color: #555; font-size: 9pt; }
        .box {
            border: 1px solid #333;
            padding: 10px 12px;
            margin: 12px 0;
            background: #f9f9f9;
        }
        .box strong { display: inline-block; min-width: 9em; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 4px;
        }
        th, td {
            border: 1px solid #333;
            padding: 5px 6px;
            text-align: left;
        }
        th { background: #e8e8e8; font-weight: 600; }
        .text-end { text-align: right; }
        .footer-note {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #999;
            font-size: 8pt;
            color: #444;
        }
        .no-print {
            margin-bottom: 16px;
            padding: 10px;
            background: #eef6ff;
            border: 1px solid #b8d4f0;
            border-radius: 4px;
        }
        .no-print a, .no-print button {
            margin-right: 8px;
            margin-bottom: 4px;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 10mm; }
        }
        .no-print button {
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 4px;
            border: 1px solid #0d6efd;
            background: #0d6efd;
            color: #fff;
            font-size: 14px;
        }
        .no-print a.pdf-btn {
            display: inline-block;
            padding: 6px 12px;
            background: #dc3545;
            color: #fff !important;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<?php if ($show_toolbar): ?>
<div class="no-print">
    <a href="<?= site_url('reports/pagosCierres') ?>">← Cierres guardados</a>
    <a class="ms-2" href="<?= site_url('reports/pagos?' . http_build_query(['start' => $startDate ?? '', 'end' => $endDate ?? ''])) ?>">Reporte de pagos</a>
    <button type="button" onclick="window.print()">Imprimir</button>
    <?php if (!empty($cierre_id)): ?>
        <a class="pdf-btn" href="<?= site_url('reports/pagosCierrePdf/' . (int) $cierre_id) ?>">Descargar PDF</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<header>
    <h1><?= esc($company_name ?? 'Laboratorio') ?></h1>
    <p class="muted" style="margin:0;">Cierre de pagos<?= !empty($cierre_id) ? ' #' . (int) $cierre_id : '' ?></p>
    <p class="muted" style="margin:4px 0 0 0;"><strong>Período:</strong> <?= esc($periodo_texto ?? '') ?></p>
</header>

<div class="box">
    <div><strong>Total facturado:</strong> <?= number_format((float) ($totales->total_facturado ?? 0), 2) ?> Bs</div>
    <div><strong>Total cobrado:</strong> <?= number_format((float) ($totales->total_cobrado ?? 0), 2) ?> Bs</div>
    <div><strong>Total pendiente:</strong> <?= number_format((float) ($totales->total_pendiente ?? 0), 2) ?> Bs</div>
    <div><strong>Órdenes (no anuladas):</strong> <?= (int) ($totales->total_registros ?? 0) ?></div>
    <div><strong>Pagadas (saldo ≤ 0):</strong> <?= (int) ($count_pagados ?? 0) ?> &nbsp;|&nbsp; <strong>Con saldo pendiente:</strong> <?= (int) ($count_pendientes ?? 0) ?></div>
</div>
<p class="muted">Los montos excluyen órdenes anuladas, alineado con el reporte de pagos.</p>

<h2>Cuadre de caja</h2>
<div class="box">
    <div><strong>Ingresos ventas (cobrado):</strong> <?= number_format((float) ($cajaResumen['ingresos_ventas'] ?? 0), 2) ?> Bs</div>
    <div><strong>Ingresos de caja:</strong> <?= number_format((float) ($cajaResumen['ingresos_movimientos'] ?? 0), 2) ?> Bs</div>
    <div><strong>Total ingresos:</strong> <?= number_format((float) ($cajaResumen['ingresos'] ?? 0), 2) ?> Bs</div>
    <div><strong>Egresos:</strong> <?= number_format((float) ($cajaResumen['egresos'] ?? 0), 2) ?> Bs</div>
    <div><strong>Saldo neto caja:</strong> <?= number_format((float) ($cajaResumen['saldo_neto'] ?? 0), 2) ?> Bs</div>
    <div class="muted">Fórmula: (total cobrado + ingresos de caja) - egresos.</div>
</div>

<h2>Cuadre por tipo de pago</h2>
<table>
    <thead>
        <tr>
            <th>Tipo</th>
            <th class="text-end">Ingresos</th>
            <th class="text-end">Egresos</th>
            <th class="text-end">Total disponible</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($cajaPorTipo)): ?>
            <?php foreach ($cajaPorTipo as $row): ?>
                <tr>
                    <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? ($row['tipopago'] ?? '-')) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['ingresos'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['egresos'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['saldo_neto'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td class="text-end"><strong>Total general</strong></td>
                <td class="text-end"><strong><?= number_format((float) ($cajaPorTipoTotales['ingresos'] ?? 0), 2) ?></strong></td>
                <td class="text-end"><strong><?= number_format((float) ($cajaPorTipoTotales['egresos'] ?? 0), 2) ?></strong></td>
                <td class="text-end"><strong><?= number_format((float) ($cajaPorTipoTotales['saldo_neto'] ?? 0), 2) ?></strong></td>
            </tr>
        <?php else: ?>
            <tr><td colspan="4" class="muted">Sin datos por tipo.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Resumen por tipo de pago</h2>
<table>
    <thead>
        <tr>
            <th>Tipo</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">Facturado</th>
            <th class="text-end">Cobrado</th>
            <th class="text-end">Pendiente</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($resumenPagosPorTipo)): ?>
            <?php foreach ($resumenPagosPorTipo as $row): ?>
                <tr>
                    <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? ($row['tipopago'] ?? '-')) ?></td>
                    <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_facturado'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_cobrado'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_pendiente'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="muted">Sin movimientos en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Resumen de ingresos de caja por tipo de pago</h2>
<table>
    <thead>
        <tr>
            <th>Tipo</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">Ingresos</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($resumenIngresosCajaPorTipo)): ?>
            <?php foreach ($resumenIngresosCajaPorTipo as $row): ?>
                <tr>
                    <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? ($row['tipopago'] ?? '-')) ?></td>
                    <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_ingresos'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2" class="text-end"><strong>Total ingresos de caja</strong></td>
                <td class="text-end"><strong><?= number_format((float) ($totalesIngresosCaja->total_ingresos ?? 0), 2) ?></strong></td>
            </tr>
        <?php else: ?>
            <tr><td colspan="3" class="muted">Sin ingresos de caja en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Detalle de ingresos de caja</h2>
<table>
    <thead>
        <tr>
            <th>No.</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Desglose</th>
            <th class="text-end">Monto</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($ingresosCajaMov)): ?>
            <?php foreach ($ingresosCajaMov as $row): ?>
                <tr>
                    <td><?= (int) ($row['egreso_id'] ?? 0) ?></td>
                    <td><?= esc(date('d/m/Y H:i', strtotime((string) ($row['fecha'] ?? '')))) ?></td>
                    <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? ($row['tipopago'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['desglose'] ?? '')) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['monto'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="muted">Sin ingresos de caja.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Resumen de egresos por tipo de pago</h2>
<table>
    <thead>
        <tr>
            <th>Tipo</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">Egresos</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($resumenEgresosPorTipo)): ?>
            <?php foreach ($resumenEgresosPorTipo as $row): ?>
                <tr>
                    <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? ($row['tipopago'] ?? '-')) ?></td>
                    <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_egresos'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2" class="text-end"><strong>Total egresos</strong></td>
                <td class="text-end"><strong><?= number_format((float) ($totalesEgresos->total_egresos ?? 0), 2) ?></strong></td>
            </tr>
        <?php else: ?>
            <tr><td colspan="3" class="muted">Sin egresos en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Detalle de egresos de caja</h2>
<table>
    <thead>
        <tr>
            <th>No.</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Desglose</th>
            <th class="text-end">Monto</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($egresos)): ?>
            <?php foreach ($egresos as $row): ?>
                <tr>
                    <td><?= (int) ($row['egreso_id'] ?? 0) ?></td>
                    <td><?= esc(date('d/m/Y H:i', strtotime((string) ($row['fecha'] ?? '')))) ?></td>
                    <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? ($row['tipopago'] ?? '-')) ?></td>
                    <td><?= esc((string) ($row['desglose'] ?? '')) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['monto'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="muted">Sin egresos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Resumen diario</h2>
<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th class="text-end">Órdenes</th>
            <th class="text-end">Facturado</th>
            <th class="text-end">Cobrado</th>
            <th class="text-end">Pendiente</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($resumenPagosPorDia)): ?>
            <?php foreach ($resumenPagosPorDia as $row): ?>
                <tr>
                    <td><?= esc(date('d/m/Y', strtotime($row['fecha'] ?? ''))) ?></td>
                    <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_facturado'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_cobrado'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_pendiente'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="muted">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Resumen por doctor</h2>
<table>
    <thead>
        <tr>
            <th>Doctor</th>
            <th class="text-end">Órdenes</th>
            <th class="text-end">Facturado</th>
            <th class="text-end">Cobrado</th>
            <th class="text-end">Pendiente</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($resumenPagosPorDoctor)): ?>
            <?php foreach ($resumenPagosPorDoctor as $row): ?>
                <tr>
                    <td><?= esc($row['doctor'] ?? '-') ?></td>
                    <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_facturado'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_cobrado'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total_pendiente'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="muted">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="footer-note">
    <div>Documento generado el <?= esc($generado_en ?? '') ?>.</div>
    <?php if (!empty($elaborado_por)): ?>
        <div>Elaborado por: <?= esc($elaborado_por) ?></div>
    <?php endif; ?>
</div>

</body>
</html>
