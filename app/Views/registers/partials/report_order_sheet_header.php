<?php
/**
 * Cabecera fija por hoja: nombre del paciente (izquierda) y nº de orden (derecha).
 *
 * @var array<string,mixed> $pdf_layout
 * @var object|null         $paciente
 * @var object|null         $register_info
 */

declare(strict_types=1);

helper('registro');

$pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$ps = is_array($pl['page_style'] ?? null) ? $pl['page_style'] : \App\Services\ReportPdfLayoutService::defaultPageStyleStatic();
$osh = \App\Services\ReportPdfLayoutService::normalizeOrderSheetHeaderStyle($ps['order_sheet_header'] ?? []);

if (empty($osh['enabled'])) {
    return;
}

$pacienteNombre = trim(
    ($paciente->first_name ?? '') . ' '
    . ($paciente->last_name_fa ?? '') . ' '
    . ($paciente->last_name_mom ?? '')
);
if ($pacienteNombre === '') {
    $pacienteNombre = '—';
}

$numeroOrden = registro_orden_display($register_info ?? null);
if ($numeroOrden === '') {
    $numeroOrden = '—';
}
?>
<div class="pdf-order-sheet-header" aria-hidden="true">
    <span class="pdf-order-sheet-header-patient"><?= esc($pacienteNombre) ?></span>
    <span class="pdf-order-sheet-header-orden"><?= esc($numeroOrden) ?></span>
</div>
