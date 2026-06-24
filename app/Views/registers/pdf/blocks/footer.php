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
    'pdf_footer_grid_style' => \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle($ps['footer_grid'] ?? []),
    'pdf_header_grid_style' => \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle($ps['header_grid'] ?? []),
    'pdf_analisis_variant'  => (string) ($analisis_variant ?? 'pdf'),
    'pdf_margins_mm'        => is_array($layout['margins_mm'] ?? null)
        ? $layout['margins_mm']
        : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic(),
    'pdf_footer_reserve_mm' => \App\Services\ReportPdfLayoutService::estimatePdfFooterReserveMm($layout),
    'pdf_footer_columns'    => $n,
    'pdf_footer_rows'       => max(1, min(50, (int) ($sectionLayout['rows'] ?? 2))),
    'pdf_footer_row_gap_px' => max(0, min(40, (int) ($sectionLayout['row_gap_px'] ?? 0))),
];

$footerWrapperStyle = ! empty($footer_dompdf_fixed)
    ? \App\Services\ReportPdfLayoutService::footerDompdfFixedStyleAttr($layout)
    : '';

$orderSheetTableRowHtml = '';
if (! empty($footer_order_sheet_band)
    && \App\Services\ReportPdfLayoutService::isOrderSheetHeaderEnabledForLayout($layout)) {
    $mm = is_array($layout['margins_mm'] ?? null)
        ? $layout['margins_mm']
        : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
    $ftGrid = \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle($ps['footer_grid'] ?? []);
    $lines = \App\Services\ReportPdfLayoutService::buildOrderSheetHeaderDisplayLines(
        is_object($paciente ?? null) ? $paciente : null,
        is_object($register_info ?? null) ? $register_info : null
    );
    $orderSheetTableRowHtml = view('registers/partials/report_order_sheet_header_table_row', [
        'patient_line'    => $lines['patient'],
        'order_line'      => $lines['order'],
        'margin_left_mm'  => (float) ($mm['left'] ?? 15),
        'margin_right_mm' => (float) ($mm['right'] ?? 15),
        'n_columns'       => $n,
        'line_height'     => (float) ($ftGrid['line_height'] ?? 1.35),
        'dompdf_from_page_two' => (string) ($elementCtx['pdf_analisis_variant'] ?? $analisis_variant ?? 'pdf') === 'pdf',
    ]);
}

echo view('registers/pdf/section_layout_grid', [
    'section_wrapper_class' => 'footer footer-grid pdf-ft-block',
    'section_key'           => 'footer',
    'section_wrapper_style'   => $footerWrapperStyle,
    'section_table_prepend_rows' => $orderSheetTableRowHtml,
    'n_columns'             => $n,
    'grid_items'            => $gridItems,
    'element_ctx'           => $elementCtx,
    'section_layout'        => $sectionLayout,
]);
