<?php
/**
 * CSS de plantilla PDF (variables + overrides). Reutilizable en PDF, impresión y vista reporte web.
 *
 * @var array<string,mixed> $pdf_layout
 * @var bool                $use_sheet_padding Si true, márgenes en .viewreport-pdf-sheet (vista embebida); si no, en body (PDF/impresión).
 * @var bool                $browser_print_mode Si true, impresión directa navegador: sin margin/padding en body (solo @page).
 * @var bool                $embed_stylesheet_for_pdf Si true, incrusta report_pdf.css (Dompdf no siempre carga &lt;link&gt; remoto).
 */

$pdf_layout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$useSheetPadding = ! empty($use_sheet_padding_for_margins);
$browserPrintMode = ! empty($browser_print_mode);
$embedStylesheetForPdf = ! empty($embed_stylesheet_for_pdf);

$reportPdfCssRel = 'assets/css/report_pdf.css';
$reportPdfCssFs  = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $reportPdfCssRel);
$reportPdfCssVer = is_file($reportPdfCssFs) ? (int) filemtime($reportPdfCssFs) : (int) time();
if ($embedStylesheetForPdf && is_file($reportPdfCssFs)) {
    echo '<style>' . "\n" . (string) file_get_contents($reportPdfCssFs) . "\n" . '</style>' . "\n";
} else {
    echo '<link rel="stylesheet" href="' . esc(base_url($reportPdfCssRel) . '?v=' . $reportPdfCssVer, 'attr') . '" />' . "\n";
}
?>
<?php
$pl = $pdf_layout;
$mm = is_array($pl['margins_mm'] ?? null)
    ? $pl['margins_mm']
    : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
$mt = (float) ($mm['top'] ?? 15);
$mr = (float) ($mm['right'] ?? 15);
$mb = (float) ($mm['bottom'] ?? 15);
$ml = (float) ($mm['left'] ?? 15);
$ps = is_array($pl['page_style'] ?? null) ? $pl['page_style'] : \App\Services\ReportPdfLayoutService::defaultPageStyleStatic();
$ch = \App\Services\ReportPdfLayoutService::normalizeCardHeaderStyle($ps['card_header'] ?? []);
$hs = \App\Services\ReportPdfLayoutService::normalizeHeaderSectionStyle($ps['header_section'] ?? []);
$ns = \App\Services\ReportPdfLayoutService::normalizeNotesStyle($ps['notes'] ?? []);
$lf = \App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle($ps['lab_firmas'] ?? []);
$lfBodyBg = ! empty($lf['body_transparent']) ? 'transparent' : (string) $lf['body_bg_color'];
$lfColBorderW = (int) ($lf['column_border_width_px'] ?? 1);
$lfColBorderW = max(0, min(4, $lfColBorderW));
$lfColBorderColor = (string) ($lf['column_border_color'] ?? '#DDDDDD');
$hg = \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle($ps['header_grid'] ?? []);
$pd = \App\Services\ReportPdfLayoutService::normalizePatientDoctorGridStyle($ps['patient_doctor_grid'] ?? []);
$secLayoutsTheme = is_array($pl['section_layouts'] ?? null) ? $pl['section_layouts'] : \App\Services\ReportPdfLayoutService::defaultSectionLayoutsStatic();
$hgSecLayout = is_array($secLayoutsTheme['header'] ?? null) ? $secLayoutsTheme['header'] : [];
$pdSecLayout = is_array($secLayoutsTheme['patient_doctor'] ?? null) ? $secLayoutsTheme['patient_doctor'] : [];
$ftSecLayout = is_array($secLayoutsTheme['footer'] ?? null) ? $secLayoutsTheme['footer'] : [];
$lfSecLayout = is_array($secLayoutsTheme['lab_firmas'] ?? null) ? $secLayoutsTheme['lab_firmas'] : [];
$hgRowGapPx  = max(0, min(40, (int) ($hgSecLayout['row_gap_px'] ?? 6)));
$pdRowGapPx  = max(0, min(40, (int) ($pdSecLayout['row_gap_px'] ?? 2)));
$ftRowGapPx  = max(0, min(40, (int) ($ftSecLayout['row_gap_px'] ?? 6)));
$lfRowGapPx  = max(0, min(40, (int) ($lfSecLayout['row_gap_px'] ?? 6)));
$ft = \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle($ps['footer_grid'] ?? []);
$hgBodyBg = ! empty($hg['body_transparent']) ? 'transparent' : (string) $hg['body_bg_color'];
$pdBodyBg = ! empty($pd['body_transparent']) ? 'transparent' : (string) $pd['body_bg_color'];
$ftBodyBg = ! empty($ft['body_transparent']) ? 'transparent' : (string) $ft['body_bg_color'];
$hgColW = max(0, min(4, (int) ($hg['column_border_width_px'] ?? 0)));
$pdColW = max(0, min(4, (int) ($pd['column_border_width_px'] ?? 0)));
$ftColW = max(0, min(4, (int) ($ft['column_border_width_px'] ?? 0)));
$nsTitleBg = ! empty($ns['title_transparent']) ? 'transparent' : (string) $ns['title_bg_color'];
$nsBodyBg = ! empty($ns['body_transparent']) ? 'transparent' : (string) $ns['body_bg_color'];
$nsColW = max(0, min(4, (int) ($ns['column_border_width_px'] ?? 1)));
$nsColColor = (string) ($ns['column_border_color'] ?? '#DDDDDD');
$chCardBg = ! empty($ch['bg_transparent']) ? 'transparent' : (string) $ch['bg_color'];
$rs = \App\Services\ReportPdfLayoutService::normalizeResultsTableStyle($ps['results_table'] ?? []);
$gpb = \App\Services\ReportPdfLayoutService::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
$osh = \App\Services\ReportPdfLayoutService::normalizeOrderSheetHeaderStyle($ps['order_sheet_header'] ?? []);
$orderSheetHeaderEnabled = ! empty($osh['enabled']);
$orderSheetHeaderReserveMm = 6.0;
$pageMarginTopRestMm = $orderSheetHeaderEnabled ? $mt + $orderSheetHeaderReserveMm : $mt;
$gpbCompactScale = round(max(75, min(100, (int) ($gpb['compact_min_scale_percent'] ?? 85))) / 100, 3);
$rsBodyBg = ! empty($rs['body_transparent']) ? 'transparent' : (string) $rs['body_bg_color'];
$rsSegBg  = ! empty($rs['segment_transparent']) ? 'transparent' : (string) $rs['segment_bg_color'];
$segShadowMap = [
    'none'   => 'none',
    'soft'   => '0 1px 2px rgba(0,0,0,0.18)',
    'medium' => '0 1.5px 3px rgba(0,0,0,0.26)',
    'strong' => '0 2px 5px rgba(0,0,0,0.34)',
];
$rsSegShadow = $segShadowMap[$rs['segment_shadow']] ?? 'none';

$pdfFooterEnabled = false;
foreach (is_array($pl['blocks'] ?? null) ? $pl['blocks'] : [] as $fb) {
    if (! empty($fb['enabled']) && (string) ($fb['id'] ?? '') === 'footer') {
        $pdfFooterEnabled = true;
        break;
    }
}
$pdfFooterReserveMm = $pdfFooterEnabled
    ? \App\Services\ReportPdfLayoutService::estimatePdfFooterReserveMm($pl)
    : 0.0;
$pdfFooterStripBg   = ($ftBodyBg !== 'transparent') ? $ftBodyBg : '#ffffff';
$ftTopOn            = ! empty($ft['section_top_border_enabled']);
$ftTopW             = max(0, min(6, (int) ($ft['section_top_border_width_px'] ?? 1)));
$ftTopColor         = (string) ($ft['section_top_border_color'] ?? '#DDDDDD');
$ftTopStyleCss      = ($ftTopOn && $ftTopW > 0) ? 'solid' : 'none';
$ftTopWpx           = ($ftTopOn && $ftTopW > 0) ? $ftTopW : 0;
?>
<style>
<?php if ($useSheetPadding): ?>
.viewreport-pdf-sheet {
    max-width: 8.5in;
    margin-left: auto;
    margin-right: auto;
    padding: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) $mb) ?>mm <?= esc((string) $ml) ?>mm;
    box-sizing: border-box;
    background: #fff;
    position: relative;
}
<?php if ($pdfFooterEnabled): ?>
.viewreport-pdf-sheet {
    min-height: 11in;
    display: flex;
    flex-direction: column;
}
.viewreport-pdf-sheet .pdf-main-stack {
    flex: 1 1 auto;
    padding-bottom: 0;
    box-sizing: border-box;
    min-height: 0;
}
.viewreport-pdf-sheet .pdf-ft-block.footer-grid {
    position: static;
    flex-shrink: 0;
    margin-top: auto;
    z-index: 2;
    padding-top: 6px;
    background: <?= esc($pdfFooterStripBg) ?>;
    box-sizing: border-box;
}
<?php endif; ?>
<?php elseif ($browserPrintMode): ?>
body.report-browser-print {
    margin: 0 !important;
    padding: 0 !important;
    position: relative;
}
body.report-browser-print .pdf-main-stack {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    box-sizing: border-box;
}
<?php if ($pdfFooterEnabled): ?>
body.report-browser-print .pdf-ft-block.footer-grid {
    position: fixed;
    z-index: 2;
    margin-top: 0 !important;
    padding-top: 6px;
    background: <?= esc($pdfFooterStripBg) ?>;
    box-sizing: border-box;
}
<?php endif; ?>
<?php else: ?>
<?php
$bodyMarginBottomMm = $pdfFooterEnabled ? ($mb + $pdfFooterReserveMm) : $mb;
?>
<?php if ($orderSheetHeaderEnabled): ?>
@page :first {
    margin-top: <?= esc((string) $mt) ?>mm;
    margin-right: <?= esc((string) $mr) ?>mm;
    margin-bottom: <?= esc((string) $bodyMarginBottomMm) ?>mm;
    margin-left: <?= esc((string) $ml) ?>mm;
}
@page {
    margin-top: <?= esc((string) $pageMarginTopRestMm) ?>mm;
    margin-right: <?= esc((string) $mr) ?>mm;
    margin-bottom: <?= esc((string) $bodyMarginBottomMm) ?>mm;
    margin-left: <?= esc((string) $ml) ?>mm;
}
<?php else: ?>
@page {
    margin-top: <?= esc((string) $mt) ?>mm;
    margin-right: <?= esc((string) $mr) ?>mm;
    margin-bottom: <?= esc((string) $bodyMarginBottomMm) ?>mm;
    margin-left: <?= esc((string) $ml) ?>mm;
}
<?php endif; ?>
body {
    margin: 0 !important;
    padding: 0 !important;
    position: relative;
}
<?php if ($pdfFooterEnabled): ?>
.pdf-ft-block.footer-grid {
    position: fixed !important;
    left: <?= esc((string) $ml) ?>mm !important;
    right: <?= esc((string) $mr) ?>mm !important;
    bottom: -<?= esc((string) $pdfFooterReserveMm) ?>mm !important;
    min-height: <?= esc((string) $pdfFooterReserveMm) ?>mm !important;
    z-index: 2;
    margin: 0 !important;
    padding-top: 6px;
    padding-bottom: 0;
    background: <?= esc($pdfFooterStripBg) ?>;
    box-sizing: border-box;
}
<?php endif; ?>
<?php endif; ?>
:root {
    --pdf-card-header-bg: <?= esc($chCardBg) ?>;
    --pdf-card-header-color: <?= esc($ch['text_color']) ?>;
    --pdf-card-header-font-family: "<?= esc($ch['font_family']) ?>";
    --pdf-card-header-font-size: <?= esc((string) $ch['font_size_pt']) ?>pt;
    --pdf-card-header-font-weight: <?= esc($ch['font_weight']) ?>;
    --pdf-card-header-font-style: <?= esc($ch['font_style']) ?>;
    --pdf-card-header-transform: <?= esc($ch['text_transform']) ?>;
    --pdf-header-separator-color: <?= esc($hs['separator_color']) ?>;
    --pdf-notes-title-bg: <?= esc($nsTitleBg) ?>;
    --pdf-notes-title-color: <?= esc($ns['title_text_color']) ?>;
    --pdf-notes-body-bg: <?= esc($nsBodyBg) ?>;
    --pdf-notes-body-color: <?= esc($ns['body_text_color']) ?>;
    --pdf-notes-column-border-width: <?= esc((string) $nsColW) ?>px;
    --pdf-notes-column-border-color: <?= esc($nsColColor) ?>;
    --pdf-notes-font-family: "<?= esc($ns['font_family']) ?>";
    --pdf-notes-font-size: <?= esc((string) $ns['font_size_pt']) ?>pt;
    --pdf-notes-font-weight: <?= esc($ns['font_weight']) ?>;
    --pdf-notes-font-style: <?= esc($ns['font_style']) ?>;
    --pdf-notes-transform: <?= esc($ns['text_transform']) ?>;
    --pdf-notes-line-height: <?= esc((string) $ns['line_height']) ?>;
    --pdf-lf-title-bg: <?= esc($lf['title_bg_color']) ?>;
    --pdf-lf-title-color: <?= esc($lf['title_text_color']) ?>;
    --pdf-lf-body-bg: <?= esc($lfBodyBg) ?>;
    --pdf-lf-body-color: <?= esc($lf['body_text_color']) ?>;
    --pdf-lf-column-border-width: <?= esc((string) $lfColBorderW) ?>px;
    --pdf-lf-column-border-color: <?= esc($lfColBorderColor) ?>;
    --pdf-hg-body-bg: <?= esc($hgBodyBg) ?>;
    --pdf-hg-body-color: <?= esc($hg['body_text_color']) ?>;
    --pdf-hg-font-family: "<?= esc($hg['font_family']) ?>";
    --pdf-hg-font-size: <?= esc((string) $hg['font_size_pt']) ?>pt;
    --pdf-hg-font-weight: <?= esc($hg['font_weight']) ?>;
    --pdf-hg-font-style: <?= esc($hg['font_style']) ?>;
    --pdf-hg-transform: <?= esc($hg['text_transform']) ?>;
    --pdf-hg-line-height: <?= esc((string) $hg['line_height']) ?>;
    --pdf-hg-row-gap: <?= esc((string) $hgRowGapPx) ?>px;
    --pdf-hg-column-border-width: <?= esc((string) $hgColW) ?>px;
    --pdf-hg-column-border-color: <?= esc((string) ($hg['column_border_color'] ?? '#DDDDDD')) ?>;
    --pdf-qr-size-percent: <?= (int) ($hg['qr_size_percent'] ?? 100) ?>;
    --pdf-pd-body-bg: <?= esc($pdBodyBg) ?>;
    --pdf-pd-body-color: <?= esc($pd['body_text_color']) ?>;
    --pdf-pd-font-family: "<?= esc($pd['font_family']) ?>";
    --pdf-pd-font-size: <?= esc((string) $pd['font_size_pt']) ?>pt;
    --pdf-pd-font-weight: <?= esc($pd['font_weight']) ?>;
    --pdf-pd-font-style: <?= esc($pd['font_style']) ?>;
    --pdf-pd-transform: <?= esc($pd['text_transform']) ?>;
    --pdf-pd-line-height: <?= esc((string) $pd['line_height']) ?>;
    --pdf-pd-row-gap: <?= esc((string) $pdRowGapPx) ?>px;
    --pdf-pd-column-border-width: <?= esc((string) $pdColW) ?>px;
    --pdf-pd-column-border-color: <?= esc((string) ($pd['column_border_color'] ?? '#DDDDDD')) ?>;
    --pdf-ft-body-bg: <?= esc($ftBodyBg) ?>;
    --pdf-ft-body-color: <?= esc($ft['body_text_color']) ?>;
    --pdf-ft-font-family: "<?= esc($ft['font_family']) ?>";
    --pdf-ft-font-size: <?= esc((string) $ft['font_size_pt']) ?>pt;
    --pdf-ft-font-weight: <?= esc($ft['font_weight']) ?>;
    --pdf-ft-font-style: <?= esc($ft['font_style']) ?>;
    --pdf-ft-transform: <?= esc($ft['text_transform']) ?>;
    --pdf-ft-line-height: <?= esc((string) $ft['line_height']) ?>;
    --pdf-ft-row-gap: <?= esc((string) $ftRowGapPx) ?>px;
    --pdf-ft-column-border-width: <?= esc((string) $ftColW) ?>px;
    --pdf-ft-column-border-color: <?= esc((string) ($ft['column_border_color'] ?? '#DDDDDD')) ?>;
    --pdf-ft-company-color: <?= esc((string) ($ft['footer_company_text_color'] ?? $ft['body_text_color'])) ?>;
    --pdf-ft-label-generated-color: <?= esc((string) ($ft['label_footer_generated_color'] ?? $ft['body_text_color'])) ?>;
    --pdf-ft-datetime-color: <?= esc((string) ($ft['label_footer_datetime_color'] ?? $ft['body_text_color'])) ?>;
    --pdf-ft-policy-color: <?= esc((string) ($ft['footer_policy_text_color'] ?? $ft['body_text_color'])) ?>;
    --pdf-ft-section-top-border-width: <?= esc((string) $ftTopWpx) ?>px;
    --pdf-ft-section-top-border-style: <?= esc($ftTopStyleCss) ?>;
    --pdf-ft-section-top-border-color: <?= esc($ftTopColor) ?>;
    --pdf-lf-font-family: "<?= esc($lf['font_family']) ?>";
    --pdf-lf-font-size: <?= esc((string) $lf['font_size_pt']) ?>pt;
    --pdf-lf-font-weight: <?= esc($lf['font_weight']) ?>;
    --pdf-lf-font-style: <?= esc($lf['font_style']) ?>;
    --pdf-lf-transform: <?= esc($lf['text_transform']) ?>;
    --pdf-lf-line-height: <?= esc((string) $lf['line_height']) ?>;
    --pdf-lf-row-gap: <?= esc((string) $lfRowGapPx) ?>px;
    --pdf-lf-inline-margin-top: <?= esc((string) ($lf['inline_margin_top_pt'] ?? 8)) ?>pt;
    --pdf-lf-inline-margin-bottom: <?= esc((string) ($lf['inline_margin_bottom_pt'] ?? 6)) ?>pt;
    --pdf-lf-seal-max-height: <?= (int) ($lf['seal_max_height_px'] ?? 110) ?>px;
    --pdf-lf-signature-max-height: <?= (int) ($lf['signature_max_height_px'] ?? 72) ?>px;
    --pdf-lf-signature-max-width: <?= (int) ($lf['signature_max_width_px'] ?? 220) ?>px;
    --pdf-lf-area-heading-color: <?= esc((string) ($lf['area_heading_color'] ?? '#664D03')) ?>;
    --pdf-lf-area-heading-font-size: <?= esc((string) ($lf['area_heading_font_size_pt'] ?? 9)) ?>pt;
    --pdf-lf-area-heading-font-weight: <?= esc((string) ($lf['area_heading_font_weight'] ?? '600')) ?>;
    --pdf-lf-area-heading-transform: <?= esc((string) ($lf['area_heading_text_transform'] ?? 'uppercase')) ?>;
    --pdf-results-header-bg: <?= esc($rs['header_bg_color']) ?>;
    --pdf-results-header-color: <?= esc($rs['header_text_color']) ?>;
    --pdf-results-body-bg: <?= esc($rsBodyBg) ?>;
    --pdf-results-body-color: <?= esc($rs['body_text_color']) ?>;
    --pdf-results-border-color: <?= esc($rs['border_color']) ?>;
    --pdf-results-segment-bg: <?= esc($rsSegBg) ?>;
    --pdf-results-segment-border: <?= esc($rs['segment_border_color']) ?>;
    --pdf-results-segment-border-width: <?= esc((string) $rs['segment_border_width_px']) ?>px;
    --pdf-results-segment-shadow: <?= esc($rsSegShadow) ?>;
    --pdf-results-font-family: "<?= esc($rs['font_family']) ?>";
    --pdf-results-font-size: <?= esc((string) $rs['font_size_pt']) ?>pt;
    --pdf-results-font-weight: <?= esc($rs['font_weight']) ?>;
    --pdf-results-font-style: <?= esc($rs['font_style']) ?>;
    --pdf-results-transform: <?= esc($rs['text_transform']) ?>;
    --pdf-results-line-height: <?= esc((string) $rs['line_height']) ?>;
    --pdf-results-cell-padding-v: <?= (int) ($rs['cell_padding_v_px'] ?? 6) ?>px;
    --pdf-results-table-margin-top: <?= (int) ($rs['table_margin_top_px'] ?? 15) ?>px;
    --pdf-results-table-margin-bottom: <?= (int) ($rs['table_margin_bottom_px'] ?? 15) ?>px;
    --pdf-results-grupo-gap: <?= (int) ($rs['grupo_prueba_gap_px'] ?? 10) ?>px;
            --pdf-gpb-compact-scale: <?= esc((string) $gpbCompactScale, 'attr') ?>;
            --pdf-gpb-compact-cell-padding-v: <?= (int) ($gpb['compact_cell_padding_px'] ?? 0) ?>px;
    --pdf-results-matrix-align: <?= esc((string) ($rs['matrix_text_align'] ?? 'center')) ?>;
    --pdf-results-matrix-vertical-align: <?= esc((string) ($rs['matrix_vertical_align'] ?? 'middle')) ?>;
    --pdf-results-matrix-color: <?= esc((string) ($rs['matrix_text_color'] ?? $rs['body_text_color'])) ?>;
    --pdf-results-matrix-font-size: <?= esc((string) ($rs['matrix_font_size_pt'] ?? $rs['font_size_pt'])) ?>pt;
    --pdf-results-matrix-font-weight: <?= esc((string) ($rs['matrix_font_weight'] ?? $rs['font_weight'])) ?>;
    --pdf-results-matrix-font-style: <?= esc((string) ($rs['matrix_font_style'] ?? $rs['font_style'])) ?>;
    --pdf-results-matrix-transform: <?= esc((string) ($rs['matrix_text_transform'] ?? $rs['text_transform'])) ?>;
    --pdf-results-matrix-header-color: <?= esc((string) ($rs['matrix_header_text_color'] ?? $rs['header_text_color'])) ?>;
    --pdf-results-matrix-header-font-family: "<?= esc((string) ($rs['matrix_header_font_family'] ?? $rs['font_family'])) ?>";
    --pdf-results-matrix-header-font-size: <?= esc((string) ($rs['matrix_header_font_size_pt'] ?? $rs['font_size_pt'])) ?>pt;
    --pdf-results-matrix-header-font-weight: <?= esc((string) ($rs['matrix_header_font_weight'] ?? $rs['font_weight'])) ?>;
    --pdf-results-matrix-header-font-style: <?= esc((string) ($rs['matrix_header_font_style'] ?? $rs['font_style'])) ?>;
    --pdf-results-matrix-header-transform: <?= esc((string) ($rs['matrix_header_text_transform'] ?? $rs['text_transform'])) ?>;
    --pdf-results-matrix-col-population-align: <?= esc((string) ($rs['matrix_col_population_align'] ?? 'left')) ?>;
    --pdf-results-matrix-col-parameter-align: <?= esc((string) ($rs['matrix_col_parameter_align'] ?? 'left')) ?>;
    --pdf-results-matrix-col-sex-align: <?= esc((string) ($rs['matrix_col_sex_align'] ?? 'center')) ?>;
    --pdf-results-matrix-col-reference-align: <?= esc((string) ($rs['matrix_col_reference_align'] ?? 'center')) ?>;
    --pdf-results-matrix-hdr-population-align: <?= esc((string) ($rs['matrix_hdr_population_align'] ?? 'left')) ?>;
    --pdf-results-matrix-hdr-parameter-align: <?= esc((string) ($rs['matrix_hdr_parameter_align'] ?? 'left')) ?>;
    --pdf-results-matrix-hdr-sex-align: <?= esc((string) ($rs['matrix_hdr_sex_align'] ?? 'center')) ?>;
    --pdf-results-matrix-hdr-reference-align: <?= esc((string) ($rs['matrix_hdr_reference_align'] ?? 'center')) ?>;
}
table.results {
    margin-top: var(--pdf-results-table-margin-top, 15px) !important;
    margin-bottom: var(--pdf-results-table-margin-bottom, 15px) !important;
}
table.results th {
    background: <?= esc($rs['header_bg_color']) ?> !important;
    color: <?= esc($rs['header_text_color']) ?> !important;
}
table.results td {
    background: <?= esc($rsBodyBg) ?> !important;
    color: <?= esc($rs['body_text_color']) ?> !important;
}
table.results th,
table.results td {
    border-color: <?= esc($rs['border_color']) ?> !important;
    font-family: "<?= esc($rs['font_family']) ?>", sans-serif !important;
    font-size: <?= esc((string) $rs['font_size_pt']) ?>pt !important;
    font-weight: <?= esc($rs['font_weight']) ?> !important;
    font-style: <?= esc($rs['font_style']) ?> !important;
    text-transform: <?= esc($rs['text_transform']) ?> !important;
    line-height: <?= esc((string) $rs['line_height']) ?> !important;
}
table.results:not(.pdf-notes-table) th,
table.results:not(.pdf-notes-table) td {
    padding-top: <?= (int) ($rs['cell_padding_v_px'] ?? 6) ?>px !important;
    padding-bottom: <?= (int) ($rs['cell_padding_v_px'] ?? 6) ?>px !important;
    padding-left: 8px !important;
    padding-right: 8px !important;
}
table.results td.out-range,
table.results .out-range {
    color: #c00 !important;
    font-weight: 700 !important;
}
.report-refs-matrix th,
.report-refs-matrix td {
    vertical-align: var(--pdf-results-matrix-vertical-align) !important;
    color: var(--pdf-results-matrix-color) !important;
}
.report-refs-matrix td {
    font-size: var(--pdf-results-matrix-font-size) !important;
    font-weight: var(--pdf-results-matrix-font-weight) !important;
    font-style: var(--pdf-results-matrix-font-style) !important;
    text-transform: var(--pdf-results-matrix-transform) !important;
}
.report-refs-matrix th {
    color: var(--pdf-results-matrix-header-color) !important;
    font-family: var(--pdf-results-matrix-header-font-family), sans-serif !important;
    font-size: var(--pdf-results-matrix-header-font-size) !important;
    font-weight: var(--pdf-results-matrix-header-font-weight) !important;
    font-style: var(--pdf-results-matrix-header-font-style) !important;
    text-transform: var(--pdf-results-matrix-header-transform) !important;
}
.report-refs-matrix th.matrix-col-population { text-align: var(--pdf-results-matrix-hdr-population-align) !important; }
.report-refs-matrix th.matrix-col-parameter { text-align: var(--pdf-results-matrix-hdr-parameter-align) !important; }
.report-refs-matrix th.matrix-col-sex { text-align: var(--pdf-results-matrix-hdr-sex-align) !important; }
.report-refs-matrix th.matrix-col-reference { text-align: var(--pdf-results-matrix-hdr-reference-align) !important; }
.report-refs-matrix td.matrix-col-population { text-align: var(--pdf-results-matrix-col-population-align) !important; }
.report-refs-matrix td.matrix-col-parameter { text-align: var(--pdf-results-matrix-col-parameter-align) !important; }
.report-refs-matrix td.matrix-col-sex { text-align: var(--pdf-results-matrix-col-sex-align) !important; }
.report-refs-matrix td.matrix-col-reference { text-align: var(--pdf-results-matrix-col-reference-align) !important; }
table.results.pdf-notes-table td.pdf-notes-cell {
    background: <?= esc($nsBodyBg) ?> !important;
    color: <?= esc($ns['body_text_color']) ?> !important;
    border-color: <?= esc($nsColColor) ?> !important;
    font-family: "<?= esc($ns['font_family']) ?>", sans-serif !important;
    font-size: <?= esc((string) $ns['font_size_pt']) ?>pt !important;
    font-weight: <?= esc($ns['font_weight']) ?> !important;
    font-style: <?= esc($ns['font_style']) ?> !important;
    text-transform: <?= esc($ns['text_transform']) ?> !important;
    line-height: <?= esc((string) $ns['line_height']) ?> !important;
}
.header-grid {
    border-bottom-color: <?= esc($hs['separator_color']) ?> !important;
}
.report-segment-title {
    background: <?= esc($rsSegBg) ?> !important;
    border-color: <?= esc($rs['segment_border_color']) ?> !important;
    border-width: <?= esc((string) $rs['segment_border_width_px']) ?>px !important;
    box-shadow: <?= esc($rsSegShadow) ?> !important;
}
.report-segment-title.pdf-card-header {
    background: <?= esc($rsSegBg) ?> !important;
    color: <?= esc($ch['text_color']) ?> !important;
    font-family: "<?= esc($ch['font_family']) ?>", sans-serif !important;
    font-size: <?= esc((string) $ch['font_size_pt']) ?>pt !important;
    font-weight: <?= esc($ch['font_weight']) ?> !important;
    font-style: <?= esc($ch['font_style']) ?> !important;
    text-transform: <?= esc($ch['text_transform']) ?> !important;
}
.report-pdf-grupo-area-separator.report-segment-title {
    text-align: center !important;
    border-bottom-color: <?= esc($rs['segment_border_color']) ?> !important;
    border-bottom-width: <?= esc((string) $rs['segment_border_width_px']) ?>px !important;
}
.report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-first) {
    margin-top: <?= (int) ($rs['grupo_prueba_gap_px'] ?? 10) ?>px !important;
}
.report-pdf-subgrupo-block.report-pdf-subgrupo-prueba {
    padding-top: <?= (int) ($rs['subgrupo_prueba_gap_px'] ?? 18) ?>px !important;
}
.report-pdf-grupo-cabecera .group-title {
    margin-top: 0 !important;
    margin-bottom: <?= (int) ($rs['cell_padding_v_px'] ?? 6) ?>px !important;
}
.report-pdf-grupo-prueba-first .report-pdf-subgrupo-block:not(.report-pdf-subgrupo-prueba) .report-pdf-grupo-cabecera .group-title {
    margin-top: <?= (int) ($rs['grupo_prueba_gap_px'] ?? 10) ?>px !important;
}
<?php if (\App\Services\ReportPdfLayoutService::grupoPruebaPageBreakUsesPureGrupoIntact($gpb)): ?>
.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split),
.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) .report-segment-table-wrap,
.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) .report-refs-matrix-wrap,
.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) table.results {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
}
.pdf-gpb-grupo-intact .report-pdf-grupo-prueba.report-pdf-grupo-prueba-force-break-before {
    page-break-before: always !important;
    break-before: page !important;
}
<?php endif; ?>
<?php if (\App\Services\ReportPdfLayoutService::grupoPruebaPageBreakUsesSegmentIntactCss($gpb)): ?>
.pdf-gpb-segment-rules .report-segment-table-wrap:not(.report-segment-allow-split),
.pdf-gpb-segment-rules .report-refs-matrix-wrap:not(.report-segment-allow-split),
.pdf-gpb-keep-segment .report-segment-table-wrap:not(.report-segment-allow-split),
.pdf-gpb-keep-segment .report-refs-matrix-wrap:not(.report-segment-allow-split) {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
}
.pdf-gpb-segment-rules .report-segment-table-wrap:not(.report-segment-allow-split) table.results,
.pdf-gpb-segment-rules .report-refs-matrix-wrap:not(.report-segment-allow-split) table.results,
.pdf-gpb-keep-segment .report-segment-table-wrap:not(.report-segment-allow-split) table.results {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
}
.pdf-gpb-segment-rules .report-segment-table-wrap.report-segment-force-break-before,
.pdf-gpb-segment-rules .report-refs-matrix-wrap.report-segment-force-break-before,
.pdf-gpb-keep-segment .report-segment-table-wrap.report-segment-force-break-before,
.pdf-gpb-keep-segment .report-refs-matrix-wrap.report-segment-force-break-before {
    page-break-before: always !important;
    break-before: page !important;
}
<?php endif; ?>
.report-cultivo-seccion .report-cultivo-banda,
.report-cultivo-seccion .report-cultivo-columnas {
    width: 100%;
}
.report-cultivo-seccion .report-cultivo-banda .table,
.report-cultivo-seccion .report-cultivo-banda table.results {
    width: 100%;
    table-layout: fixed;
    margin-bottom: 0;
}
.report-cultivo-layout-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    border-spacing: 0;
    margin-top: 0;
}
.report-cultivo-layout-table td.report-cultivo-columna-td {
    vertical-align: top;
    padding: 0;
    border: none;
}
.report-cultivo-layout-table .report-cultivo-columna-td > table.results {
    width: 100%;
    table-layout: fixed;
    margin-top: 0;
    margin-bottom: 0;
}
.report-cultivo-seccion .report-cultivo-banda + .report-cultivo-columnas .report-cultivo-columna-td > table.results {
    border-top: none;
}
.report-cultivo-seccion .report-cultivo-banda + .report-cultivo-columnas .report-cultivo-columna-td > table.results thead tr:first-child th {
    border-top: none;
}
.report-cultivo-seccion.report-cultivo-alineacion-centro .report-cultivo-columna-td td {
    text-align: center;
}
.report-cultivo-seccion.report-cultivo-alineacion-bordes .report-cultivo-columna-td td {
    text-align: left;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}
.cultivo-celda-bordes {
    display: table;
    width: 100%;
    table-layout: fixed;
}
.cultivo-celda-bordes .cultivo-celda-izq,
.cultivo-celda-bordes .cultivo-celda-der {
    display: table-cell;
    vertical-align: middle;
}
.cultivo-celda-bordes .cultivo-celda-izq {
    width: 99%;
    text-align: left;
}
.cultivo-celda-bordes .cultivo-celda-der {
    text-align: right;
    white-space: nowrap;
}
<?php if ($orderSheetHeaderEnabled && $browserPrintMode): ?>
.pdf-order-sheet-header,
.pdf-order-sheet-header-injected .pdf-order-sheet-header {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    font-family: "DejaVu Sans", Helvetica, Arial, sans-serif;
    font-size: 9pt;
    font-weight: 600;
    line-height: 1.2;
    color: #333333;
}
.pdf-order-sheet-header td,
.pdf-order-sheet-header-injected .pdf-order-sheet-header td {
    padding: 0;
    vertical-align: middle;
    white-space: nowrap;
}
.pdf-order-sheet-header-patient,
.pdf-order-sheet-header-injected .pdf-order-sheet-header-patient {
    width: 50%;
    text-align: left;
}
.pdf-order-sheet-header-orden,
.pdf-order-sheet-header-injected .pdf-order-sheet-header-orden {
    width: 50%;
    text-align: right;
}
.pdf-order-sheet-header-injected {
    margin: 0 0 2mm;
    break-after: avoid-page;
    page-break-after: avoid;
}
<?php endif; ?>
</style>
