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
$comprobante_style = isset($comprobante_style) && is_array($comprobante_style) ? $comprobante_style : [];
$primaryColor = $comprobante_style['primary'] ?? '#0f766e';
$secondaryColor = $comprobante_style['secondary'] ?? '#134e4a';
$textColor = $comprobante_style['text'] ?? '#1e293b';
$tagline = trim((string) ($comprobante_style['tagline'] ?? 'Constancia de pago'));
$footerNote = trim((string) ($comprobante_style['footer_note'] ?? 'Documento interno de constancia de pago emitido por el laboratorio. No reemplaza un comprobante fiscal electrónico ni factura validada ante el SIN.'));
$showDoctor = (bool) ($comprobante_style['show_doctor'] ?? true);
$layout = is_array($comprobante_style['layout'] ?? null) ? $comprobante_style['layout'] : (new \App\Services\ComprobanteLayoutService())->getDefaultLayout();
$layoutSvc = new \App\Services\ComprobanteLayoutService();
$pdfCss = $layoutSvc->buildPdfCss($layout, [
    'primary'   => $primaryColor,
    'secondary' => $secondaryColor,
    'text'      => $textColor,
]);
$sectionSpacing = $layoutSvc->normalizeSectionSpacing($layout['section_spacing'] ?? null);
$tableRowGap = (float) ($sectionSpacing['table']['row_gap_pt'] ?? 4);
$totalsRowGap = (float) ($sectionSpacing['totals']['row_gap_pt'] ?? 4);
$dimensions = $layoutSvc->normalizeDimensions($layout['dimensions'] ?? null);
$itemsCellPad = (float) ($dimensions['items_cell_padding_pt'] ?? 8);
$itemsHeaderBg = $layoutSvc->itemsTableHeaderBgColor($layout, $secondaryColor);
$thStyle = $layoutSvc->inlineStyleFor($layout, 'table', 'items_header');
$tdBodyStyle = $layoutSvc->inlineStyleFor($layout, 'table', 'items_body');
$tdNumStyle = $layoutSvc->inlineStyleFor($layout, 'table', 'items_num');
$tdPad = 'padding:' . $itemsCellPad . 'pt 10px;';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo — Orden <?= esc($doc->ordenNumero) ?></title>
    <style><?= $pdfCss ?></style>
</head>
<body style="<?= $layoutSvc->inlineStyleFor($layout, 'header', 'body', ['color' => $textColor]) ?>;line-height:1.45;margin:0;padding:0;">
    <div class="accent-bar"></div>

    <div class="comp-sec-header comp-sec-block comp-sec-block-header" style="<?= $layoutSvc->headerSectionOuterStyle($layout) ?>">
    <?= $layoutSvc->renderHeaderGridHtml($layout, $doc, $tagline, $textColor, $primaryColor) ?>
    <?= $layoutSvc->renderSectionSeparatorHtml($layout, 'header') ?>
    </div>

    <div class="comp-sec-client comp-sec-block comp-sec-block-client" style="<?= $layoutSvc->sectionOuterStyle($layout, 'client') ?>">
    <?php if ($layoutSvc->matrixItemEnabled($layout, 'section_client')): ?>
    <p class="section-title" style="<?= $layoutSvc->inlineStyleFor($layout, 'client', 'section_title') ?>"><?= esc($layoutSvc->matrixText($layout, 'section_client', 'Cliente y atención')) ?></p>
    <?php endif; ?>
    <div class="panel">
        <?= $layoutSvc->renderClientGridHtml($layout, $doc, $showDoctor, $textColor) ?>
    </div>
    <?= $layoutSvc->renderSectionSeparatorHtml($layout, 'client') ?>
    </div>

    <div class="comp-sec-table comp-sec-block comp-sec-block-table" style="<?= $layoutSvc->sectionOuterStyle($layout, 'table') ?>">
    <?php if ($layoutSvc->matrixItemEnabled($layout, 'section_items')): ?>
    <p class="section-title" style="<?= $layoutSvc->inlineStyleFor($layout, 'table', 'section_title') ?>"><?= esc($layoutSvc->matrixText($layout, 'section_items', 'Detalle de conceptos')) ?></p>
    <?php endif; ?>
    <?php $lineaCount = count($doc->lineas); ?>
    <table class="tbl-items" style="<?= $layoutSvc->matrixTableSpacingStyle($tableRowGap) ?>">
        <?php if ($layoutSvc->matrixItemEnabled($layout, 'items_header_desc') || $layoutSvc->matrixItemEnabled($layout, 'items_header_amount')): ?>
        <thead>
            <tr>
                <?php if ($layoutSvc->matrixItemEnabled($layout, 'items_header_desc')): ?>
                <th style="<?= $thStyle ?>;background:<?= esc($itemsHeaderBg) ?>;<?= $tdPad ?>"><?= esc($layoutSvc->matrixText($layout, 'items_header_desc', 'Descripción')) ?></th>
                <?php else: ?>
                <th></th>
                <?php endif; ?>
                <?php if ($layoutSvc->matrixItemEnabled($layout, 'items_header_amount')): ?>
                <th style="<?= $thStyle ?>;background:<?= esc($itemsHeaderBg) ?>;text-align:right;<?= $tdPad ?>"><?= esc($layoutSvc->matrixText($layout, 'items_header_amount', 'Importe (' . $sym . ')')) ?></th>
                <?php else: ?>
                <th></th>
                <?php endif; ?>
            </tr>
        </thead>
        <?php endif; ?>
        <tbody>
        <?php foreach ($doc->lineas as $lineaIdx => $ln):
            $rowPad = $layoutSvc->matrixCellGapPadding($tableRowGap, (int) $lineaIdx, $lineaCount);
            ?>
            <tr>
                <td style="<?= $tdBodyStyle ?>;<?= $tdPad ?><?= $rowPad ?>"><?= esc($ln['descripcion'] ?? '') ?></td>
                <td class="num" style="<?= $tdNumStyle ?>;text-align:right;<?= $tdPad ?><?= $rowPad ?>"><?= esc($fmt((float) ($ln['importe'] ?? 0))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?= $layoutSvc->renderSectionSeparatorHtml($layout, 'table') ?>
    </div>

    <div class="comp-sec-totals comp-sec-block comp-sec-block-totals" style="<?= $layoutSvc->sectionOuterStyle($layout, 'totals') ?>">
    <?php
    $totalsRows = [];
    if ($descuentoMonto > 0 && $doc->totalRecomendado > 0) {
        $totalsRows[] = ['final' => false, 'lbl' => 'Total recomendado', 'val' => $sym . ' ' . $fmt($doc->totalRecomendado)];
    }
    if ($descuentoMonto > 0) {
        $totalsRows[] = ['final' => false, 'lbl' => $descuentoLabel, 'val' => '- ' . $sym . ' ' . $fmt($descuentoMonto)];
    }
    if ($layoutSvc->matrixItemEnabled($layout, 'total_orden')) {
        $totalsRows[] = ['final' => false, 'lbl' => $layoutSvc->matrixText($layout, 'total_orden', 'Total orden'), 'val' => $sym . ' ' . $fmt($doc->total)];
    }
    if ($layoutSvc->matrixItemEnabled($layout, 'total_pagado')) {
        $totalsRows[] = ['final' => false, 'lbl' => $layoutSvc->matrixText($layout, 'total_pagado', 'Monto pagado'), 'val' => $sym . ' ' . $fmt($doc->montoPagado)];
    }
    if ($layoutSvc->matrixItemEnabled($layout, 'total_saldo')) {
        $totalsRows[] = ['final' => true, 'lbl' => $layoutSvc->matrixText($layout, 'total_saldo', 'Saldo'), 'val' => $sym . ' ' . $fmt($doc->saldo)];
    }
    $totalsRowCount = count($totalsRows);
    $lblStyle = $layoutSvc->inlineStyleFor($layout, 'totals', 'totals_label');
    $valStyle = $layoutSvc->inlineStyleFor($layout, 'totals', 'totals_value');
    $finalStyle = $layoutSvc->inlineStyleFor($layout, 'totals', 'totals_final', ['color' => '#FFFFFF']);
    ?>
    <table class="totals-wrap"><tr><td>
        <div class="totals-box">
            <table class="totals-inner-table" style="<?= $layoutSvc->matrixTableSpacingStyle($totalsRowGap) ?>">
                <?php foreach ($totalsRows as $totIdx => $totRow):
                    $rowPad = $layoutSvc->matrixCellGapPadding($totalsRowGap, (int) $totIdx, $totalsRowCount);
                    $isFinal = ! empty($totRow['final']);
                    $cellLbl = $isFinal ? $finalStyle : $lblStyle;
                    $cellVal = $isFinal ? $finalStyle : $valStyle;
                    $bg = $isFinal ? 'background:' . esc($primaryColor) . ';' : '';
                    ?>
                <tr<?= $isFinal ? ' class="total-final"' : '' ?>>
                    <td class="t-lbl" style="<?= $cellLbl ?>;<?= $bg ?><?= $rowPad ?>"><?= esc($totRow['lbl']) ?></td>
                    <td class="t-val" style="<?= $cellVal ?>;text-align:right;<?= $bg ?><?= $rowPad ?>"><?= esc($totRow['val']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </td></tr></table>
    <?= $layoutSvc->renderSectionSeparatorHtml($layout, 'totals') ?>
    </div>

    <div class="comp-sec-footer comp-sec-block comp-sec-block-footer" style="<?= $layoutSvc->sectionOuterStyle($layout, 'footer') ?>">
        <?= $layoutSvc->renderSectionGridHtml($layout, 'footer', $doc, ['footer_note' => $footerNote]) ?>
        <?= $layoutSvc->renderSectionSeparatorHtml($layout, 'footer') ?>
    </div>
</body>
</html>
