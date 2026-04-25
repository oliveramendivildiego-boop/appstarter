<?php
/** @var \App\Models\ReciboComprobanteModel $doc */
$sym = $doc->monedaSimbolo;
$fmt = static function (float $n): string {
    return number_format($n, 2, ',', '.');
};
$descuentoMonto = (float) ($doc->institucionDescuentoMonto ?? 0);
$descuentoLabel = 'Descuento';
if (($doc->institucionDescuentoPct ?? 0) > 0) {
    $descuentoLabel = 'Descuento institución (' . ($doc->institucionNombre ?: 'Paciente') . ', ' . number_format((float) $doc->institucionDescuentoPct, 2, '.', '') . '%)';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo — Orden <?= esc($doc->ordenNumero) ?></title>
    <style>
        @page { margin: 14mm 16mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .accent-bar {
            height: 5px;
            background: #0f766e;
            margin: 0 0 16px 0;
        }
        .doc-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .doc-header td { vertical-align: top; padding: 0; }
        .brand-name {
            font-size: 16pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin: 0 0 4px 0;
        }
        .brand-tagline {
            font-size: 8.5pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin: 0;
        }
        .receipt-badge {
            text-align: right;
        }
        .receipt-badge-inner {
            display: inline-block;
            text-align: right;
            border: 2px solid #0f766e;
            border-radius: 6px;
            padding: 10px 14px;
            background: #f0fdfa;
        }
        .receipt-badge-title {
            font-size: 8pt;
            font-weight: bold;
            color: #0f766e;
            letter-spacing: 0.18em;
            margin: 0 0 6px 0;
        }
        .receipt-badge-orden {
            font-size: 14pt;
            font-weight: bold;
            color: #134e4a;
            margin: 0;
        }
        .receipt-badge-fecha {
            font-size: 8.5pt;
            color: #475569;
            margin: 6px 0 0 0;
        }
        .section-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            margin: 0 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
        }
        .panel {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }
        table.pair-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        table.pair-table td {
            width: 50%;
            vertical-align: top;
            padding: 10px 16px 10px 0;
            line-height: 1.5;
        }
        table.pair-table td:nth-child(2) { padding-right: 0; padding-left: 8px; }
        table.pair-table tr:first-child td { padding-top: 4px; }
        .pair-k {
            color: #475569;
            font-weight: 600;
        }
        .pair-v {
            color: #0f172a;
            font-weight: normal;
        }
        table.tbl-items {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 14px 0;
            font-size: 9.5pt;
        }
        table.tbl-items thead th {
            background: #134e4a;
            color: #fff;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 9px 10px;
            text-align: left;
        }
        table.tbl-items thead th:last-child { text-align: right; }
        table.tbl-items tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.tbl-items tbody tr:nth-child(even) td { background: #fafafa; }
        table.tbl-items tbody td.num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            color: #0f172a;
            font-weight: 600;
        }
        .totals-wrap {
            width: 100%;
            margin-top: 4px;
        }
        .totals-wrap td { vertical-align: top; }
        .totals-box {
            width: 280px;
            margin-left: auto;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }
        .totals-box table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .totals-box tr td {
            padding: 6px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .totals-box tr:last-child td { border-bottom: none; }
        .totals-box .t-lbl { color: #64748b; text-align: left; }
        .totals-box .t-val {
            text-align: right;
            font-variant-numeric: tabular-nums;
            color: #334155;
            font-weight: 600;
            white-space: nowrap;
        }
        .totals-box tr.total-final td {
            background: #0f766e;
            color: #fff;
            font-size: 10.5pt;
            font-weight: bold;
            padding: 10px 12px;
        }
        .totals-box tr.total-final .t-lbl { color: #ecfdf5; }
        .totals-box tr.total-final .t-val { color: #fff; }
        .pay-method {
            margin-top: 12px;
            padding: 10px 14px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            font-size: 9.5pt;
        }
        .pay-method strong { color: #92400e; }
        .foot {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #64748b;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="accent-bar"></div>

    <table class="doc-header">
        <tr>
            <td>
                <p class="brand-name"><?= esc($doc->empresaNombre) ?></p>
                <p class="brand-tagline">Constancia de pago</p>
            </td>
            <td class="receipt-badge">
                <div class="receipt-badge-inner">
                    <p class="receipt-badge-title">Recibo de pago</p>
                    <p class="receipt-badge-orden">N.º <?= esc($doc->ordenNumero) ?></p>
                    <p class="receipt-badge-fecha"><?= esc($doc->fechaEmision) ?></p>
                </div>
            </td>
        </tr>
    </table>

    <p class="section-title">Cliente y atención</p>
    <div class="panel">
        <?= view('registers/billing/_datos_cliente_atencion_pdf', ['doc' => $doc]) ?>
    </div>

    <p class="section-title">Detalle de conceptos</p>
    <table class="tbl-items">
        <thead>
            <tr>
                <th>Descripción</th>
                <th style="width:26%;">Importe (<?= esc($sym) ?>)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($doc->lineas as $ln): ?>
            <tr>
                <td><?= esc($ln['descripcion'] ?? '') ?></td>
                <td class="num"><?= esc($fmt((float) ($ln['importe'] ?? 0))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals-wrap"><tr><td>
        <div class="totals-box">
            <table>
                <?php if ($descuentoMonto > 0 && $doc->totalRecomendado > 0): ?>
                <tr>
                    <td class="t-lbl">Total recomendado</td>
                    <td class="t-val"><?= esc($sym) ?> <?= esc($fmt($doc->totalRecomendado)) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($descuentoMonto > 0): ?>
                <tr>
                    <td class="t-lbl"><?= esc($descuentoLabel) ?></td>
                    <td class="t-val">- <?= esc($sym) ?> <?= esc($fmt($descuentoMonto)) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="t-lbl">Total orden</td>
                    <td class="t-val"><?= esc($sym) ?> <?= esc($fmt($doc->total)) ?></td>
                </tr>
                <tr>
                    <td class="t-lbl">Monto pagado</td>
                    <td class="t-val"><?= esc($sym) ?> <?= esc($fmt($doc->montoPagado)) ?></td>
                </tr>
                <tr class="total-final">
                    <td class="t-lbl">Saldo</td>
                    <td class="t-val"><?= esc($sym) ?> <?= esc($fmt($doc->saldo)) ?></td>
                </tr>
            </table>
        </div>
        <div class="pay-method">
            <strong>Forma de pago:</strong> <?= esc($doc->formaPagoEtiqueta) ?>
        </div>
    </td></tr></table>

    <div class="foot">
        Documento interno de constancia de pago emitido por el laboratorio.<br/>
        No reemplaza un comprobante fiscal electrónico ni factura validada ante el SIN.
    </div>
</body>
</html>
