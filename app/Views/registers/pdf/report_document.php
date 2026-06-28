<?php
declare(strict_types=1);

helper('registro');

$pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$wm = is_array($pl['watermark'] ?? null) ? $pl['watermark'] : \App\Services\ReportPdfLayoutService::defaultWatermarkStatic();
$opacityW = (float) ($wm['opacity'] ?? 0.12);
$sizeW    = (int) ($wm['size_percent'] ?? 45);

$labForLogo = is_array($lab_config ?? null) ? $lab_config : [];
$logoRel    = $labForLogo['logo'] ?? 'images/logo-john.png';
$logoPath   = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);
$pdf_logo_data_uri = $pdf_logo_data_uri ?? '';
$analisisVariantForAssets = (string) ($analisis_variant ?? 'pdf');
if ($pdf_logo_data_uri === '' && is_file($logoPath)) {
    if ($analisisVariantForAssets !== 'pdf' || \App\Libraries\Pdf\PdfEngine::isMpdf()) {
        $pdf_logo_data_uri = report_image_data_uri($logoRel);
    }
}

$wmPayload = \App\Services\ReportPdfLayoutService::watermarkRenderPayloadForLayout(
    $pl,
    $pdf_logo_data_uri,
    file_exists($logoPath) ? $logoPath : null
);
$wmUri = $pdf_watermark_uri ?? ($wmPayload['uri'] ?? null);

$pdfBlockViews = [
    'header'         => 'registers/pdf/blocks/header',
    'patient_doctor' => 'registers/pdf/blocks/patient_doctor',
    'results'        => 'registers/pdf/blocks/results',
    'notes'          => 'registers/pdf/blocks/notes',
    'lab_firmas'     => 'registers/pdf/blocks/lab_firmas',
    'footer'         => 'registers/pdf/blocks/footer',
];

$footerBlockEnabled = false;
$headerBlockEnabled = false;
$patientDoctorBlockEnabled = false;
foreach (is_array($pl['blocks'] ?? null) ? $pl['blocks'] : [] as $fb) {
    if (empty($fb['enabled'])) {
        continue;
    }
    $fbid = (string) ($fb['id'] ?? '');
    if ($fbid === 'footer') {
        $footerBlockEnabled = true;
    } elseif ($fbid === 'header') {
        $headerBlockEnabled = true;
    } elseif ($fbid === 'patient_doctor') {
        $patientDoctorBlockEnabled = true;
    }
}

$ctx = [
    'register_info'     => $register_info,
    'paciente'          => $paciente,
    'doctor'            => $doctor,
    'grupos'            => $grupos,
    'lab_config'        => $lab_config,
    'report_url'        => $report_url ?? '',
    'qr_data_uri'       => $qr_data_uri ?? '',
    'report_emitido_en' => $report_emitido_en ?? \App\Services\RegisterService::formatNowForReport(),
    'pdf_layout'        => $pl,
    'pdf_logo_data_uri' => $pdf_logo_data_uri,
    'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
    'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
    'report_lab_firmas'               => $report_lab_firmas ?? [],
    'report_pria_refs_consolidada'    => $report_pria_refs_consolidada ?? [],
    'report_categorical_heatmap'      => $report_categorical_heatmap ?? [],
    'report_tolerance_chart'          => $report_tolerance_chart ?? [],
    'report_graficar_modo'            => $report_graficar_modo ?? [],
    'analisis_variant'                => $analisis_variant ?? 'pdf',
    'pb_diag_no_separators'           => $pb_diag_no_separators ?? false,
    'report_layout_plan'              => $report_layout_plan ?? null,
    'report_layout_applier'           => $report_layout_applier ?? null,
];
?>
<?php
$analisisVariant = (string) ($analisis_variant ?? 'pdf');
$useDompdfWatermarkCallback = $analisisVariant === 'pdf';
$isPdfDownloadVariant = \App\Libraries\Pdf\PdfEngine::isPdfDownloadVariant($analisisVariant);
$isDompdfEnginePdf = $isPdfDownloadVariant && \App\Libraries\Pdf\PdfEngine::isDompdf();
$isMpdfEnginePdf = $isPdfDownloadVariant && \App\Libraries\Pdf\PdfEngine::isMpdf();
$footerRenderCtx = array_merge($ctx, [
    'footer_dompdf_fixed'     => $isDompdfEnginePdf,
    'footer_order_sheet_band' => in_array($analisisVariant, ['pdf', 'browser_print', 'screen_pdf'], true),
]);
if ($wmUri !== null && $wmUri !== ''):
    $wmSize     = max(10, min(95, (int) $sizeW));
    $opacityCss = number_format(max(0.05, min(0.9, $opacityW)), 2, '.', '');
    if ($useDompdfWatermarkCallback):
        $wmDompdfData = is_array($wmPayload)
            ? $wmPayload
            : [
                'uri'          => (string) $wmUri,
                'path'         => file_exists($logoPath) ? $logoPath : null,
                'opacity'      => round(max(0.05, min(0.9, $opacityW)), 2),
                'size_percent' => max(10, min(95, (int) $sizeW)),
            ];
        $wmDompdfJson = json_encode($wmDompdfData, JSON_UNESCAPED_UNICODE);
        if ($wmDompdfJson !== false):
?>
<!-- pdf-watermark-dompdf:<?= base64_encode($wmDompdfJson) ?> -->
<?php
        endif;
    else:
?>
<div class="pdf-watermark-layer" aria-hidden="true">
    <div class="pdf-watermark-inner" style="opacity:<?= esc($opacityCss, 'attr') ?>;">
        <table class="pdf-watermark-table" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;height:11in;border-collapse:collapse;border-spacing:0;">
            <tr>
                <td class="pdf-watermark-td" align="center" valign="middle" style="height:11in;vertical-align:middle;">
                    <img src="<?= esc($wmUri, 'attr') ?>" alt="" class="pdf-watermark-img" style="width:<?= $wmSize ?>%;max-width:95%;height:auto;">
                </td>
            </tr>
        </table>
    </div>
</div>
<?php
    endif;
endif;
?>
<?php
$orderSheetHeaderCtx = [
    'pdf_layout'       => $pl,
    'paciente'         => $paciente,
    'register_info'    => $register_info,
    'analisis_variant' => $ctx['analisis_variant'] ?? 'pdf',
];
// Dompdf: cabecera + paciente/médico en flujo continuo; el pie fijo va después (no entre ambos).
$dompdfFlowTopEnabled = $isDompdfEnginePdf && ($headerBlockEnabled || $patientDoctorBlockEnabled);
if ($dompdfFlowTopEnabled): ?>
<div class="pdf-dompdf-flow-top">
<?php if ($headerBlockEnabled): ?>
<?= view($pdfBlockViews['header'], $ctx) ?>
<?php endif; ?>
<?php if ($patientDoctorBlockEnabled): ?>
<?= view($pdfBlockViews['patient_doctor'], $ctx) ?>
<?php endif; ?>
</div>
<?php endif; ?>
<?php if ($isDompdfEnginePdf && $footerBlockEnabled): ?>
<?= view('registers/partials/report_order_sheet_header', $orderSheetHeaderCtx) ?>
<?= view($pdfBlockViews['footer'], $footerRenderCtx) ?>
<?php endif; ?>
<?php $reportPipelineRegistroId = (int) ($register_info->registro_id ?? 0); ?>
<?= \App\Services\Report\ReportPdfHtmlCacheService::registroMarker($reportPipelineRegistroId) ?>
<div class="pdf-main-stack">
<?php foreach (($pl['blocks'] ?? []) as $block):
    if (empty($block['enabled'])) {
        continue;
    }
    $bid = (string) ($block['id'] ?? '');
    if ($bid === 'footer' || $bid === '' || ! isset($pdfBlockViews[$bid])) {
        continue;
    }
    if ($isDompdfEnginePdf && in_array($bid, ['header', 'patient_doctor'], true)) {
        continue;
    }
    echo view($pdfBlockViews[$bid], $ctx);
endforeach; ?>
</div>
<?php if ((! $isPdfDownloadVariant || $isMpdfEnginePdf) && $footerBlockEnabled): ?>
<?= view('registers/partials/report_order_sheet_header', $orderSheetHeaderCtx) ?>
<?= view($pdfBlockViews['footer'], $footerRenderCtx) ?>
<?php endif; ?>
