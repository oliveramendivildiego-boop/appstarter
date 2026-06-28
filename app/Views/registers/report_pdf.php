<?php
$pdfGrupoPageBreakBodyClass = \App\Services\ReportPdfLayoutService::grupoPruebaPageBreakBodyClass(
    is_array($pdf_layout ?? null) ? $pdf_layout : []
);
$pdfEngineBodyClass = \App\Libraries\Pdf\PdfEngine::isMpdf() ? 'pdf-engine-mpdf' : 'pdf-engine-dompdf';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados - <?= esc($paciente->first_name ?? '') ?> <?= esc($paciente->last_name_fa ?? '') ?></title>
    <?= view('registers/partials/report_pdf_theme_styles', [
        'pdf_layout'                     => $pdf_layout ?? [],
        'use_sheet_padding_for_margins' => false,
        'embed_stylesheet_for_pdf'      => true,
    ]) ?>
</head>
<body class="<?= esc(trim($pdfGrupoPageBreakBodyClass . ' pdf-dompdf-download ' . $pdfEngineBodyClass), 'attr') ?>">
<?php if (\App\Libraries\Pdf\PdfEngine::isMpdf()): ?>
<?php
$mpdfLayoutMarker = \App\Libraries\Pdf\MpdfLayoutSnapshot::marker(is_array($pdf_layout ?? null) ? $pdf_layout : []);
if ($mpdfLayoutMarker !== ''):
    echo $mpdfLayoutMarker . "\n";
endif;
?>
<?php endif; ?>
<?= view('registers/pdf/report_document', [
    'pdf_layout'          => $pdf_layout ?? [],
    'register_info'       => $register_info,
    'paciente'            => $paciente,
    'doctor'              => $doctor,
    'grupos'              => $grupos,
    'lab_config'          => $lab_config,
    'report_url'          => $report_url ?? '',
    'qr_data_uri'         => $qr_data_uri ?? '',
    'report_emitido_en'   => $report_emitido_en ?? \App\Services\RegisterService::formatNowForReport(),
    'pdf_watermark_uri'   => null,
    'pdf_logo_data_uri'   => $pdf_logo_data_uri ?? null,
    'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
    'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
    'report_lab_firmas'               => $report_lab_firmas ?? [],
    'report_pria_refs_consolidada'    => $report_pria_refs_consolidada ?? [],
    'report_layout_plan'              => $report_layout_plan ?? null,
    'report_layout_applier'           => $report_layout_applier ?? null,
    'report_categorical_heatmap'      => $report_categorical_heatmap ?? [],
    'report_tolerance_chart'          => $report_tolerance_chart ?? [],
    'report_graficar_modo'            => $report_graficar_modo ?? [],
]) ?>
</body>
</html>
