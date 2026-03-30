<?php
declare(strict_types=1);

$layout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
if (empty($layout['instances']) || ! is_array($layout['instances'])) {
    $layout = (new \App\Services\ReportPdfLayoutService())->getDefaultLayout();
}

[$n, $gridItems] = \App\Services\ReportPdfLayoutService::gridItemsForSection($layout, 'footer');
$n               = max(1, $n);
$secLayouts      = is_array($layout['section_layouts'] ?? null) ? $layout['section_layouts'] : [];
$sectionLayout   = is_array($secLayouts['footer'] ?? null) ? $secLayouts['footer'] : [];

$elementCtx = [
    'lab_config'        => $lab_config ?? [],
    'paciente'          => $paciente ?? null,
    'doctor'            => $doctor ?? null,
    'register_info'     => $register_info ?? null,
    'report_url'        => $report_url ?? '',
    'qr_data_uri'       => $qr_data_uri ?? '',
    'pdf_logo_data_uri' => $pdf_logo_data_uri ?? '',
];

echo view('registers/pdf/section_layout_grid', [
    'section_wrapper_class' => 'footer footer-grid',
    'n_columns'             => $n,
    'grid_items'            => $gridItems,
    'element_ctx'           => $elementCtx,
    'section_layout'        => $sectionLayout,
]);
