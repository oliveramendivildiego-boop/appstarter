<?php
/**
 * Banda Paciente / No. Orden encima del pie (impresión navegador y PDF Dompdf).
 *
 * @var string $patient_line
 * @var string $order_line
 * @var float  $margin_left_mm
 * @var float  $margin_right_mm
 */
declare(strict_types=1);

$patientLine = trim((string) ($patient_line ?? ''));
$orderLine   = trim((string) ($order_line ?? ''));
if ($patientLine === '' && $orderLine === '') {
    return;
}

$ml = max(0.0, (float) ($margin_left_mm ?? 15));
$mr = max(0.0, (float) ($margin_right_mm ?? 15));
$tablePad = 'padding-left:' . esc((string) $ml, 'attr') . 'mm;padding-right:' . esc((string) $mr, 'attr') . 'mm;';
?>
<div class="pdf-order-sheet-footer-band pdf-order-sheet-footer-band-dompdf" aria-hidden="false">
    <table class="pdf-order-sheet-header" width="100%" cellpadding="0" cellspacing="0" style="<?= $tablePad ?>">
        <tr>
            <td class="pdf-order-sheet-header-patient" align="left"><?= esc($patientLine) ?></td>
            <td class="pdf-order-sheet-header-orden" align="right"><?= esc($orderLine) ?></td>
        </tr>
    </table>
</div>
