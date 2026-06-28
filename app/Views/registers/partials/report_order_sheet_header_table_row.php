<?php
/**
 * Fila Paciente / No. Orden dentro de pdf-section-table (pie).
 *
 * @var string $patient_line
 * @var string $order_line
 * @var int    $n_columns
 * @var float  $margin_left_mm
 * @var float  $margin_right_mm
 * @var float  $line_height
 * @var bool   $dompdf_from_page_two Si true, Dompdf elimina la fila en hoja 1 (begin_page_reflow).
 * @var bool   $mpdf_from_page_two   Si true, mPDF muestra la fila solo desde hoja 2 (SetHTMLFooter).
 * @var bool   $mpdf_footer_row      Si true, fila en SetHTMLFooter (sin padding de márgenes de hoja).
 * @var bool   $mpdf_footer_above_table Si true, banda sobre la línea verde del pie (tabla aparte).
 */
declare(strict_types=1);

$patientLine = trim((string) ($patient_line ?? ''));
$orderLine   = trim((string) ($order_line ?? ''));
if ($patientLine === '' && $orderLine === '') {
    return;
}

$nCols     = max(1, (int) ($n_columns ?? 2));
$leftSpan  = max(1, (int) floor($nCols / 2));
$rightSpan = max(1, $nCols - $leftSpan);

$mpdfFooterRow = ! empty($mpdf_footer_row);
$mpdfFooterAboveTable = ! empty($mpdf_footer_above_table);
$ml = $mpdfFooterRow ? 0.0 : max(0.0, (float) ($margin_left_mm ?? 15));
$mr = $mpdfFooterRow ? 0.0 : max(0.0, (float) ($margin_right_mm ?? 15));
$lh = max(1.0, min(3.0, (float) ($line_height ?? 1.35)));

$fontWeight = $mpdfFooterRow ? 'bold' : '600';
$borderBottom = $mpdfFooterAboveTable ? '' : 'border-bottom:1px solid rgba(0,0,0,0.12);padding-bottom:4px;margin-bottom:2px;';
$cellBase = 'line-height:' . (string) $lh
    . ';vertical-align:middle !important;font-family:dejavusans,sans-serif'
    . ';font-size:8pt;font-weight:' . $fontWeight . ';color:#333333;white-space:nowrap;'
    . ';' . $borderBottom;
$leftPad  = $mpdfFooterRow ? 'padding-left:0;' : ('padding-left:' . (string) $ml . 'mm;padding-right:0;');
$rightPad = $mpdfFooterRow ? 'padding-right:0;' : ('padding-right:' . (string) $mr . 'mm;padding-left:0;');
$leftStyle  = $cellBase . 'text-align:left !important;' . $leftPad;
$rightStyle = $cellBase . 'text-align:right !important;' . $rightPad;

$styleAttr = static function (string $css): string {
    return ' style="' . htmlspecialchars($css, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
};

$patientCellHtml = $mpdfFooterRow ? ('<b>' . esc($patientLine) . '</b>') : esc($patientLine);
$orderCellHtml   = $mpdfFooterRow ? ('<b>' . esc($orderLine) . '</b>') : esc($orderLine);
$dompdfFromPageTwo = ! empty($dompdf_from_page_two);
$mpdfFromPageTwo   = ! empty($mpdf_from_page_two);
$rowAttrs = 'class="pdf-section-row pdf-order-sheet-table-row mpdf-order-sheet-row" data-pdf-row="order-sheet"';
if ($dompdfFromPageTwo) {
    $rowAttrs .= ' data-order-sheet-from-page-two="1"';
}
if ($mpdfFromPageTwo) {
    echo '{IF {PAGENO}>1}';
}
?>
    <tr <?= $rowAttrs ?>>
<?php if ($mpdfFooterAboveTable && $nCols === 3): ?>
        <td class="pdf-order-sheet-header-patient pdf-cell pdf-cell--left mpdf-order-sheet-patient" align="left" width="40%"<?= $styleAttr($cellBase . 'text-align:left !important;padding:0;width:40%;max-width:40%;box-sizing:border-box;') ?>><?= $patientCellHtml ?></td>
        <td class="pdf-order-sheet-header-spacer mpdf-order-sheet-spacer" aria-hidden="true" width="20%" style="padding:0;width:20%;max-width:20%;box-sizing:border-box;border:0;">&#8203;</td>
        <td class="pdf-order-sheet-header-orden pdf-cell pdf-cell--right mpdf-order-sheet-order" align="right" width="40%"<?= $styleAttr($cellBase . 'text-align:right !important;padding:0;width:40%;max-width:40%;box-sizing:border-box;') ?>><?= $orderCellHtml ?></td>
<?php else: ?>
        <td class="pdf-order-sheet-header-patient pdf-cell pdf-cell--left mpdf-order-sheet-patient" colspan="<?= $leftSpan ?>" align="left"<?= $styleAttr($leftStyle) ?>><?= $patientCellHtml ?></td>
        <td class="pdf-order-sheet-header-orden pdf-cell pdf-cell--right mpdf-order-sheet-order" colspan="<?= $rightSpan ?>" align="right"<?= $styleAttr($rightStyle) ?>><?= $orderCellHtml ?></td>
<?php endif; ?>
    </tr>
<?php
if ($mpdfFromPageTwo) {
    echo '{ENDIF}';
}
