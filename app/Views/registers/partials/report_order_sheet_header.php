<?php
/**
 * Cabecera por hoja (desde la 2.ª): Paciente izquierda, No. Orden derecha.
 * PDF: Dompdf page_script. Impresión navegador: plantilla para inyección JS.
 *
 * @var array<string,mixed> $pdf_layout
 * @var object|null         $paciente
 * @var object|null         $register_info
 * @var string              $analisis_variant pdf|browser_print|...
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

$patientLine = 'Paciente: ' . $pacienteNombre;
$orderLine   = 'No. Orden: ' . $numeroOrden;
$variant     = (string) ($analisis_variant ?? 'pdf');
$mm          = is_array($pl['margins_mm'] ?? null)
    ? $pl['margins_mm']
    : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
$marginTopMm   = (float) ($mm['top'] ?? 15);
$marginLeftMm  = (float) ($mm['left'] ?? 15);
$marginRightMm = (float) ($mm['right'] ?? 15);

if ($variant === 'browser_print'): ?>
<div id="pdf-order-sheet-header-template" class="pdf-order-sheet-header-template" hidden
     data-patient-line="<?= esc($patientLine, 'attr') ?>"
     data-order-line="<?= esc($orderLine, 'attr') ?>"></div>
<?php
    return;
endif;

if ($variant !== 'pdf') {
    return;
}
?>
<!-- pdf-order-sheet-header-dompdf -->
<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script('
        if ($PAGE_NUM > 1) {
            $font = $fontMetrics->getFont("DejaVu Sans", "bold");
            $size = 9;
            $color = array(0.2, 0.2, 0.2);
            $mmToPt = 2.834645669;
            $patientText = <?= json_encode($patientLine, JSON_UNESCAPED_UNICODE) ?>;
            $orderText = <?= json_encode($orderLine, JSON_UNESCAPED_UNICODE) ?>;
            $xLeft = <?= json_encode($marginLeftMm) ?> * $mmToPt;
            $xRightPad = <?= json_encode($marginRightMm) ?> * $mmToPt;
            $y = <?= json_encode($marginTopMm) ?> * $mmToPt;
            $pdf->text($xLeft, $y, $patientText, $font, $size, $color);
            $orderWidth = $fontMetrics->getTextWidth($orderText, $font, $size);
            $xOrder = $pdf->get_width() - $xRightPad - $orderWidth;
            $pdf->text($xOrder, $y, $orderText, $font, $size, $color);
        }
    ');
}
</script>
