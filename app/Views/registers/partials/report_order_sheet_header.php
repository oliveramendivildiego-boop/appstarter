<?php
/**
 * Cabecera fija por hoja: Paciente (izquierda) y No. Orden (derecha).
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
?>
<div class="pdf-order-sheet-header-wrap" aria-hidden="true">
    <table class="pdf-order-sheet-header" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td class="pdf-order-sheet-header-patient" align="left" valign="middle">Paciente: <?= esc($pacienteNombre) ?></td>
            <td class="pdf-order-sheet-header-orden" align="right" valign="middle">No. Orden: <?= esc($numeroOrden) ?></td>
        </tr>
    </table>
</div>
