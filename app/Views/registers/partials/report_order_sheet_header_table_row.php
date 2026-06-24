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
 */
declare(strict_types=1);

$patientLine = trim((string) ($patient_line ?? ''));
$orderLine   = trim((string) ($order_line ?? ''));
if ($patientLine === '' && $orderLine === '') {
    return;
}

$nCols    = max(1, (int) ($n_columns ?? 2));
$leftSpan = max(1, (int) floor($nCols / 2));
$rightSpan = max(1, $nCols - $leftSpan);

$ml = max(0.0, (float) ($margin_left_mm ?? 15));
$mr = max(0.0, (float) ($margin_right_mm ?? 15));
$lh = max(1.0, min(3.0, (float) ($line_height ?? 1.35)));

$cellBase = 'line-height:' . esc((string) $lh, 'attr')
    . ';vertical-align:middle !important;font-family:"DejaVu Sans",Helvetica,Arial,sans-serif'
    . ';font-size:9pt;font-weight:600;color:#333333;white-space:nowrap'
    . ';border-bottom:1px solid rgba(0,0,0,0.12);padding-bottom:4px;margin-bottom:2px;';
$leftStyle  = $cellBase . 'text-align:left !important;padding-left:' . esc((string) $ml, 'attr') . 'mm;padding-right:0;';
$rightStyle = $cellBase . 'text-align:right !important;padding-right:' . esc((string) $mr, 'attr') . 'mm;padding-left:0;';
?>
    <tr class="pdf-section-row pdf-order-sheet-table-row" data-pdf-row="order-sheet">
        <td class="pdf-order-sheet-header-patient pdf-cell pdf-cell--left" colspan="<?= $leftSpan ?>" style="<?= esc($leftStyle, 'attr') ?>"><?= esc($patientLine) ?></td>
        <td class="pdf-order-sheet-header-orden pdf-cell pdf-cell--right" colspan="<?= $rightSpan ?>" style="<?= esc($rightStyle, 'attr') ?>"><?= esc($orderLine) ?></td>
    </tr>
