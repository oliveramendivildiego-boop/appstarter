<?php
declare(strict_types=1);

$layout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
if (empty($layout['instances']) || ! is_array($layout['instances'])) {
    $layout = (new \App\Services\ReportPdfLayoutService())->getDefaultLayout();
}

[$n, $gridItems] = \App\Services\ReportPdfLayoutService::gridItemsForSection($layout, 'patient_doctor');
$n               = max(1, $n);
$secLayouts      = is_array($layout['section_layouts'] ?? null) ? $layout['section_layouts'] : [];
$sectionLayout   = is_array($secLayouts['patient_doctor'] ?? null) ? $secLayouts['patient_doctor'] : [];
$ps              = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];

$elementCtx = [
    'lab_config'        => $lab_config ?? [],
    'paciente'          => $paciente ?? null,
    'doctor'            => $doctor ?? null,
    'register_info'     => $register_info ?? null,
    'report_url'        => $report_url ?? '',
    'qr_data_uri'       => $qr_data_uri ?? '',
    'pdf_logo_data_uri' => $pdf_logo_data_uri ?? '',
    'report_emitido_en' => $report_emitido_en ?? \App\Services\RegisterService::formatNowForReport(),
    'pdf_patient_doctor_grid_style' => \App\Services\ReportPdfLayoutService::normalizePatientDoctorGridStyle($ps['patient_doctor_grid'] ?? []),
];
?>
<div class="patient-section pdf-pd-block">
<?= view('registers/pdf/section_layout_grid', [
    'section_wrapper_class' => 'patient-columns patient-columns-grid',
    'n_columns'             => $n,
    'grid_items'            => $gridItems,
    'element_ctx'           => $elementCtx,
    'section_layout'        => $sectionLayout,
]) ?>
</div>
