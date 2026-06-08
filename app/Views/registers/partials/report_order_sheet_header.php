<?php
/**
 * Cabecera por hoja (desde la 2.ª): Paciente izquierda, No. Orden derecha, encima del pie.
 * PDF: metadatos + page_script (PdfService). Impresión: banda fija en @media print.
 *
 * @var array<string,mixed> $pdf_layout
 * @var object|null         $paciente
 * @var object|null         $register_info
 * @var string              $analisis_variant pdf|browser_print|...
 */

declare(strict_types=1);

helper('registro');

$pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
if (! \App\Services\ReportPdfLayoutService::isOrderSheetHeaderEnabledForLayout($pl)) {
    return;
}

$pacienteNombre = '';
if (is_object($paciente ?? null)) {
    $pacienteNombre = trim(
        ($paciente->first_name ?? '') . ' '
        . ($paciente->last_name_fa ?? '') . ' '
        . ($paciente->last_name_mom ?? '')
    );
}
if ($pacienteNombre === '' && is_object($register_info ?? null)) {
    $pacienteNombre = trim(
        ($register_info->first_name ?? '') . ' '
        . ($register_info->last_name_fa ?? '') . ' '
        . ($register_info->last_name_mom ?? '')
    );
}
if ($pacienteNombre === '') {
    $pacienteNombre = '—';
}

$numeroOrden = registro_orden_display($register_info ?? null);
if ($numeroOrden === '') {
    $numeroOrden = '—';
}

$patientLine = 'Paciente: ' . $pacienteNombre;
$orderLine   = 'No. Orden: ' . $numeroOrden;
$variant     = (string) ($analisis_variant ?? 'pdf');
$mm          = is_array($pl['margins_mm'] ?? null)
    ? $pl['margins_mm']
    : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
$marginBottomMm = (float) ($mm['bottom'] ?? 15);
$marginLeftMm   = (float) ($mm['left'] ?? 15);
$marginRightMm  = (float) ($mm['right'] ?? 15);
$pdfFooterEnabled = \App\Services\ReportPdfLayoutService::isPdfFooterBlockEnabledForLayout($pl);
$footerReserveMm = $pdfFooterEnabled
    ? \App\Services\ReportPdfLayoutService::estimatePdfFooterReserveMm($pl)
    : 0.0;
$gapAboveFooterMm = \App\Services\ReportPdfLayoutService::ORDER_SHEET_HEADER_GAP_ABOVE_FOOTER_MM;

$renderBandTable = static function () use ($patientLine, $orderLine): void {
    ?>
<table class="pdf-order-sheet-header" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td class="pdf-order-sheet-header-patient" align="left"><?= esc($patientLine) ?></td>
        <td class="pdf-order-sheet-header-orden" align="right"><?= esc($orderLine) ?></td>
    </tr>
</table>
    <?php
};

if ($variant === 'browser_print'):
    ?>
<div id="pdf-order-sheet-header-band" class="pdf-order-sheet-header-band" aria-hidden="true">
    <?php $renderBandTable(); ?>
</div>
<?php
    return;
endif;

if ($variant !== 'pdf') {
    return;
}

$headerPayload = base64_encode((string) json_encode([
    'patient'             => $patientLine,
    'order'               => $orderLine,
    'margin_bottom_mm'    => $marginBottomMm,
    'margin_left_mm'      => $marginLeftMm,
    'margin_right_mm'     => $marginRightMm,
    'footer_enabled'      => $pdfFooterEnabled,
    'footer_reserve_mm'   => $footerReserveMm,
    'gap_above_footer_mm' => $gapAboveFooterMm,
], JSON_UNESCAPED_UNICODE));
?>
<!-- pdf-order-sheet-header-dompdf -->
<!-- pdf-order-sheet-header-data:<?= esc($headerPayload, 'attr') ?> -->
