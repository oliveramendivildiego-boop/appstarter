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



$isMpdfFooter = \App\Libraries\Pdf\PdfEngine::isMpdf();



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

    'pdf_footer_cell_pad_h_px' => max(0, min(12, (int) round(max(0, min(40, (int) ($sectionLayout['row_gap_px'] ?? 0))) / 2))),

    'pdf_layout'          => $layout,

    'mpdf_footer_mode'    => $isMpdfFooter,

];



$footerWrapperStyle = '';

$footerDompdfAnchorOpen = '';

$footerDompdfAnchorClose = '';



if (! empty($footer_dompdf_fixed)) {

    $footerDompdfAnchorOpen = '<div class="pdf-dompdf-footer-anchor" style="'

        . esc(\App\Services\ReportPdfLayoutService::footerDompdfFixedAnchorStyleAttr($layout), 'attr')

        . '">';

    $footerDompdfAnchorClose = '</div>';

    $footerWrapperStyle = \App\Services\ReportPdfLayoutService::footerDompdfFixedInnerStyleAttr($layout);

} elseif ($isMpdfFooter) {

    // mPDF: estilos en MpdfFooterStyles (SetHTMLFooter); sin inline de posicionamiento.

    $footerWrapperStyle = '';

}



$useDompdfOrderSheetCanvas = ! empty($footer_dompdf_fixed)

    && (string) ($elementCtx['pdf_analisis_variant'] ?? $analisis_variant ?? 'pdf') === 'pdf'

    && \App\Libraries\Pdf\PdfEngine::isDompdf();



$elementCtx['pdf_footer_prepended_row_mm'] = $useDompdfOrderSheetCanvas

    ? 0.0

    : \App\Services\ReportPdfLayoutService::estimateFooterPrependedRowMmForPagination($layout);



$orderSheetTableRowHtml = '';

if (! empty($footer_order_sheet_band)

    && \App\Services\ReportPdfLayoutService::isOrderSheetHeaderEnabledForLayout($layout)

    && ! $useDompdfOrderSheetCanvas

    && ! $isMpdfFooter) {

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



$footerWrapperClass = $isMpdfFooter
    ? 'mpdf-ft-root pdf-ft-block footer-grid footer'
    : 'footer footer-grid pdf-ft-block';



echo $footerDompdfAnchorOpen;

echo '<!-- report-pdf-footer:start -->';

echo view('registers/pdf/section_layout_grid', [

    'section_wrapper_class' => $footerWrapperClass,

    'section_key'           => 'footer',

    'section_wrapper_style'   => $footerWrapperStyle,

    'section_table_prepend_rows' => $orderSheetTableRowHtml,

    'n_columns'             => $n,

    'grid_items'            => $gridItems,

    'element_ctx'           => $elementCtx,

    'section_layout'        => $sectionLayout,

    'mpdf_footer_mode'      => $isMpdfFooter,

]);

echo '<!-- report-pdf-footer:end -->';

echo $footerDompdfAnchorClose;


