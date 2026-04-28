<?php
/**
 * CSS de plantilla PDF (variables + overrides). Reutilizable en PDF, impresión y vista reporte web.
 *
 * @var array<string,mixed> $pdf_layout
 * @var bool                $use_sheet_padding Si true, márgenes en .viewreport-pdf-sheet (vista embebida); si no, en body (PDF/impresión).
 */

$pdf_layout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$useSheetPadding = ! empty($use_sheet_padding_for_margins);

$reportPdfCssRel = 'assets/css/report_pdf.css';
$reportPdfCssFs  = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $reportPdfCssRel);
$reportPdfCssVer = is_file($reportPdfCssFs) ? (int) filemtime($reportPdfCssFs) : (int) time();
?>
<link rel="stylesheet" href="<?= base_url($reportPdfCssRel) ?>?v=<?= $reportPdfCssVer ?>" />
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
$pdfFooterReserveMm = 22.0;
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
}
.viewreport-pdf-sheet .pdf-main-stack {
    padding-bottom: <?= esc((string) $pdfFooterReserveMm) ?>mm;
    box-sizing: border-box;
}
.viewreport-pdf-sheet .pdf-ft-block.footer-grid {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 2;
    margin-top: 0 !important;
    padding-top: 6px;
    background: <?= esc($pdfFooterStripBg) ?>;
    box-sizing: border-box;
}
<?php endif; ?>
<?php else: ?>
body { margin: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) $mb) ?>mm <?= esc((string) $ml) ?>mm !important; position: relative; }
<?php if ($pdfFooterEnabled): ?>
.pdf-main-stack {
    padding-bottom: <?= esc((string) $pdfFooterReserveMm) ?>mm;
    box-sizing: border-box;
}
.pdf-ft-block.footer-grid {
    position: fixed;
    left: <?= esc((string) $ml) ?>mm;
    right: <?= esc((string) $mr) ?>mm;
    bottom: <?= esc((string) $mb) ?>mm;
    z-index: 2;
    margin-top: 0 !important;
    padding-top: 6px;
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
    padding: <?= (int) ($rs['cell_padding_v_px'] ?? 6) ?>px 8px !important;
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
</style>
