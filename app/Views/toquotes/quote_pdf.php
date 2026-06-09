<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización - <?= esc($lab_config['company'] ?? 'Laboratorio') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            color: #1e293b;
            padding: 22px 28px 28px;
            line-height: 1.45;
        }
        .sheet { max-width: 100%; }

        /* Cabecera con logo */
        .header-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .header-wrap td { vertical-align: middle; padding: 0; }
        .logo-box {
            width: 32%;
            padding-right: 16px;
        }
        .logo-box img {
            max-height: 68px;
            max-width: 200px;
            width: auto;
            height: auto;
            display: block;
        }
        .logo-placeholder {
            width: 68px;
            height: 68px;
            background: #0d9488;
            border-radius: 10px;
            color: #fff;
            font-size: 22pt;
            font-weight: bold;
            text-align: center;
            line-height: 68px;
        }
        .company-block { padding-left: 8px; }
        .company-name {
            font-size: 17pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }
        .company-line {
            font-size: 8.5pt;
            color: #64748b;
            margin: 2px 0;
        }
        .company-line strong { color: #475569; font-weight: normal; }

        .accent-line {
            height: 4px;
            background: #0d9488;
            margin: 14px 0 18px;
            border-radius: 2px;
        }

        /* Título del documento */
        .doc-title-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .doc-title-row td { vertical-align: middle; }
        .doc-badge {
            background: #0f766e;
            color: #fff;
            font-size: 11pt;
            font-weight: bold;
            padding: 10px 16px;
            letter-spacing: 0.04em;
        }
        .doc-meta {
            text-align: right;
            font-size: 9pt;
            color: #64748b;
        }
        .doc-meta strong { color: #334155; }

        /* Tabla de ítems */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 10pt;
        }
        table.data-table thead th {
            background: #f0fdfa;
            color: #0f766e;
            border: 1px solid #99f6e4;
            padding: 10px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 9.5pt;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        table.data-table thead th.text-right { text-align: right; }
        table.data-table tbody td {
            border: 1px solid #e2e8f0;
            padding: 9px 12px;
            vertical-align: top;
        }
        table.data-table tbody tr:nth-child(even) { background: #f8fafc; }
        table.data-table tbody tr:nth-child(odd) { background: #fff; }
        table.data-table .col-num {
            width: 6%;
            text-align: center;
            color: #64748b;
            font-weight: bold;
        }
        table.data-table .text-right { text-align: right; font-variant-numeric: tabular-nums; }

        /* Totales */
        .totales-wrap {
            width: 100%;
            margin-top: 8px;
        }
        .totales-inner {
            margin-left: auto;
            width: 58%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }
        .totales-inner td {
            padding: 11px 14px;
            font-size: 10.5pt;
        }
        .totales-inner .t-label {
            background: #f1f5f9;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            font-weight: bold;
        }
        .totales-inner .t-val {
            text-align: right;
            font-weight: bold;
            color: #0f766e;
            font-size: 11.5pt;
            border-bottom: 1px solid #e2e8f0;
        }
        .totales-inner tr:last-child .t-label,
        .totales-inner tr:last-child .t-val { border-bottom: none; }

        .footer-note {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 8.5pt;
            color: #94a3b8;
        }
        .footer-note .web { color: #0d9488; }

        .text-right { text-align: right; }

        .recomendaciones-section {
            margin-top: 22px;
            page-break-inside: avoid;
        }
        .recomendaciones-title {
            font-size: 10pt;
            font-weight: bold;
            color: #b45309;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #fcd34d;
        }
        .recomendacion-item {
            margin-bottom: 12px;
            border: 1px solid #fde68a;
            border-radius: 4px;
            overflow: hidden;
        }
        .recomendacion-item-name {
            background: #fffbeb;
            color: #92400e;
            font-weight: bold;
            font-size: 9.5pt;
            padding: 8px 12px;
            border-bottom: 1px solid #fde68a;
        }
        .recomendacion-item-body {
            padding: 10px 12px;
            font-size: 9.5pt;
            color: #334155;
            line-height: 1.5;
        }
        .recomendacion-item-body ul,
        .recomendacion-item-body ol {
            margin: 4px 0 4px 18px;
            padding: 0;
        }
    </style>
</head>
<body>
<?php
helper('layout');
$layoutCfg = layout_config();

$currencySym = (isset($layoutCfg['currency_symbol']) && (string) $layoutCfg['currency_symbol'] !== '')
    ? $layoutCfg['currency_symbol']
    : '$';
$currencySide = isset($layoutCfg['currency_side']) ? (string) $layoutCfg['currency_side'] : 'left';
$currencyIsRight = strtolower(trim($currencySide)) === 'right';

$logoRel = $layoutCfg['logo'] ?? 'images/logo-john.png';
$logoPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);
$pdf_logo_data_uri = '';
if (is_file($logoPath)) {
    $logoData = base64_encode((string) file_get_contents($logoPath));
    $finfo    = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $mime     = $finfo ? finfo_file($finfo, $logoPath) : false;
    if ($finfo) {
        finfo_close($finfo);
    }
    $pdf_logo_data_uri = 'data:' . ($mime ?: 'image/png') . ';base64,' . $logoData;
}

$company = (string) ($lab_config['company'] ?? $layoutCfg['company'] ?? 'Laboratorio');
$initial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($company, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($company, 0, 1));

$precioTipoVal = $precioTipo ?? null;
$mostrarRefe   = ($precioTipoVal === 'total') ? false : true;
$mostrarCost   = ($precioTipoVal === 'refe') ? false : true;

$subtituloPdf = '';
if ($precioTipoVal === 'total') {
    $subtituloPdf = 'Listado con precio de costo';
} elseif ($precioTipoVal === 'refe') {
    $subtituloPdf = 'Listado con precio de referencia';
} else {
    $subtituloPdf = 'Costo y precio de referencia';
}
?>
<div class="sheet">
    <table class="header-wrap">
        <tr>
            <td class="logo-box">
                <?php if ($pdf_logo_data_uri !== ''): ?>
                    <img src="<?= $pdf_logo_data_uri ?>" alt="">
                <?php else: ?>
                    <div class="logo-placeholder"><?= esc($initial) ?></div>
                <?php endif; ?>
            </td>
            <td class="company-block">
                <div class="company-name"><?= esc($company) ?></div>
                <?php if (!empty($lab_config['address'])): ?>
                    <p class="company-line"><?= esc($lab_config['address']) ?></p>
                <?php endif; ?>
                <?php if (!empty($lab_config['phone'])): ?>
                    <p class="company-line"><strong>Tel.</strong> <?= esc($lab_config['phone']) ?></p>
                <?php endif; ?>
                <?php if (!empty($lab_config['email'])): ?>
                    <p class="company-line"><strong>Email</strong> <?= esc($lab_config['email']) ?></p>
                <?php endif; ?>
                <?php if (!empty($lab_config['website'])): ?>
                    <p class="company-line"><strong>Web</strong> <?= esc($lab_config['website']) ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <div class="accent-line"></div>

    <table class="doc-title-row">
        <tr>
            <td>
                <span class="doc-badge">COTIZACIÓN &nbsp;·&nbsp; ANÁLISIS CLÍNICOS</span>
            </td>
            <td class="doc-meta">
                <?php if (!empty($cotizacionId)): ?>
                    <strong>No. cotización: <?= (int) $cotizacionId ?></strong><br>
                <?php endif; ?>
                <strong><?= esc($subtituloPdf) ?></strong><br>
                Fecha: <?= esc($fecha) ?>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width:6%">#</th>
                <th>Análisis</th>
                <?php if ($mostrarCost): ?>
                    <th class="text-right" style="width:20%">Costo (<?= esc($currencySym) ?>)</th>
                <?php endif; ?>
                <?php if ($mostrarRefe): ?>
                    <th class="text-right" style="width:20%">Ref. (<?= esc($currencySym) ?>)</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php $n = 1;
            foreach ($items as $it): ?>
                <tr>
                    <td class="col-num"><?= $n++ ?></td>
                    <td><?= esc($it['name'] ?? '') ?></td>
                    <?php if ($mostrarCost): ?>
                        <td class="text-right"><?= number_format((int) ($it['cost'] ?? 0)) ?></td>
                    <?php endif; ?>
                    <?php if ($mostrarRefe): ?>
                        <td class="text-right"><?= number_format((int) ($it['refe'] ?? 0)) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totales-wrap">
        <tr>
            <td></td>
            <td style="width:58%;">
                <table class="totales-inner" align="right">
                    <?php if ($mostrarCost): ?>
                        <tr>
                            <td class="t-label" width="50%">Costo total</td>
                            <td class="t-val" width="50%">
                                <?php if ($currencyIsRight): ?>
                                    <?= number_format($totalCost) ?> <?= esc($currencySym) ?>
                                <?php else: ?>
                                    <?= esc($currencySym) ?> <?= number_format($totalCost) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($mostrarRefe): ?>
                        <tr>
                            <td class="t-label">Total referencia</td>
                            <td class="t-val">
                                <?php if ($currencyIsRight): ?>
                                    <?= number_format($totalRefe) ?> <?= esc($currencySym) ?>
                                <?php else: ?>
                                    <?= esc($currencySym) ?> <?= number_format($totalRefe) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>

    <?php
    $itemsConRecomendaciones = [];
    foreach ($items as $it) {
        $rec = (string) ($it['recomendaciones'] ?? '');
        if (\App\Models\LabotestModel::recomendacionTieneContenido($rec)) {
            $itemsConRecomendaciones[] = ['name' => $it['name'] ?? '', 'recomendaciones' => $rec];
        }
    }
    ?>
    <?php if (! empty($itemsConRecomendaciones)): ?>
    <div class="recomendaciones-section">
        <div class="recomendaciones-title">Recomendaciones previas al examen</div>
        <?php foreach ($itemsConRecomendaciones as $recItem): ?>
        <div class="recomendacion-item">
            <div class="recomendacion-item-name"><?= esc($recItem['name']) ?></div>
            <div class="recomendacion-item-body"><?= $recItem['recomendaciones'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <p class="footer-note">
        Documento generado electrónicamente. Los precios pueden variar según políticas vigentes.
        <?php if (!empty($lab_config['website'])): ?>
            <span class="web"><?= esc($lab_config['website']) ?></span>
        <?php endif; ?>
    </p>
</div>
</body>
</html>
