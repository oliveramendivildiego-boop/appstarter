<?php
/**
 * Cabecera por hoja: Paciente izquierda, No. Orden derecha (desde hoja 2).
 * Impresión: plantilla JS + bandas en flujo. PDF: callback Dompdf (hojas 2+).
 *
 * @var array<string,mixed> $pdf_layout
 * @var object|null         $paciente
 * @var object|null         $register_info
 * @var string              $analisis_variant pdf|browser_print|...
 */

declare(strict_types=1);

$pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
if (! \App\Services\ReportPdfLayoutService::isOrderSheetHeaderEnabledForLayout($pl)) {
    return;
}

$lines   = \App\Services\ReportPdfLayoutService::buildOrderSheetHeaderDisplayLines(
    is_object($paciente ?? null) ? $paciente : null,
    is_object($register_info ?? null) ? $register_info : null
);
$variant = (string) ($analisis_variant ?? 'pdf');

if ($variant === 'browser_print'): ?>
<div id="pdf-order-sheet-header-template" class="pdf-order-sheet-header-template" hidden
     data-patient-line="<?= esc($lines['patient'], 'attr') ?>"
     data-order-line="<?= esc($lines['order'], 'attr') ?>"></div>
<?php
    return;
endif;

if ($variant !== 'pdf') {
    return;
}

$mm = is_array($pl['margins_mm'] ?? null)
    ? $pl['margins_mm']
    : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
$footerReserveMm = \App\Services\ReportPdfLayoutService::isPdfFooterBlockEnabledForLayout($pl)
    ? \App\Services\ReportPdfLayoutService::estimatePdfFooterReserveMm($pl)
    : 0.0;
$markerPayload = json_encode([
    'patient'         => $lines['patient'],
    'order'           => $lines['order'],
    'ml'              => (float) ($mm['left'] ?? 15),
    'mr'              => (float) ($mm['right'] ?? 15),
    'mb'              => (float) ($mm['bottom'] ?? 15),
    'footerReserveMm' => $footerReserveMm,
    'gapMm'           => \App\Services\ReportPdfLayoutService::ORDER_SHEET_HEADER_GAP_ABOVE_FOOTER_MM,
], JSON_UNESCAPED_UNICODE);
if (is_string($markerPayload) && $markerPayload !== ''): ?>
<!-- pdf-order-sheet-header:<?= base64_encode($markerPayload) ?> -->
<?php endif;
