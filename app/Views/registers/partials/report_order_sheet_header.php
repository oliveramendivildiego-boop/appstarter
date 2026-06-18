<?php
/**
 * Cabecera por hoja: Paciente izquierda, No. Orden derecha (banda en pie fijo).
 * Impresión: plantilla JS. PDF: HTML dentro del pie (footer.php).
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

// PDF: la banda visible se renderiza dentro de footer.php (pie fijo Dompdf).
