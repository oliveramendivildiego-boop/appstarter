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
    static $reportPdfEmbeddedCss = null;
    static $reportPdfEmbeddedCssMtime = 0;
    $cssMtime = (int) filemtime($reportPdfCssFs);
    if ($reportPdfEmbeddedCss === null || $reportPdfEmbeddedCssMtime !== $cssMtime) {
        $reportPdfEmbeddedCss       = (string) file_get_contents($reportPdfCssFs);
        $reportPdfEmbeddedCssMtime  = $cssMtime;
    }
    echo '<style>' . "\n" . $reportPdfEmbeddedCss . "\n" . '</style>' . "\n";
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
$ftRowGapPx  = max(0, min(40, (int) ($ftSecLayout['row_gap_px'] ?? 0)));
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
$orderSheetHeaderEnabled = \App\Services\ReportPdfLayoutService::isOrderSheetHeaderEnabledForLayout($pl);
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
<?php if ($orderSheetHeaderEnabled): ?>
.viewreport-pdf-sheet .pdf-ft-block.footer-grid .pdf-order-sheet-table-row {
    display: table-row;
}
.viewreport-pdf-sheet .pdf-ft-block.footer-grid .pdf-order-sheet-table-row td {
    font-family: "DejaVu Sans", Helvetica, Arial, sans-serif;
    font-size: 9pt;
    font-weight: 600;
    line-height: 1.2;
    color: #333333;
}
<?php endif; ?>
<?php endif; ?>
<?php elseif ($browserPrintMode): ?>
body.report-browser-print {
    margin: 0 !important;
    padding: 0 !important;
    position: relative;
}
body.report-browser-print .pdf-main-stack {
    position: relative;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    box-sizing: border-box;
}
<?php if ($pdfFooterEnabled): ?>
body.report-browser-print .pdf-ft-block.footer-grid {
    display: block;
    visibility: visible;
    position: fixed;
    bottom: calc(var(--print-margin-bottom-mm, <?= esc((string) $mb) ?>) * 1mm);
    left: 0;
    right: 0;
    width: 100%;
    z-index: 5;
    margin-top: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding-top: 6px;
    padding-left: calc(var(--print-margin-left-mm, <?= esc((string) $ml) ?>) * 1mm);
    padding-right: calc(var(--print-margin-right-mm, <?= esc((string) $mr) ?>) * 1mm);
    background: <?= esc($pdfFooterStripBg) ?>;
    box-sizing: border-box;
}
<?php endif; ?>
<?php else: ?>
<?php
$bodyMarginBottomMm = $pdfFooterEnabled ? ($mb + $pdfFooterReserveMm) : $mb;
?>
@page {
    margin-top: <?= esc((string) $mt) ?>mm;
    margin-right: <?= esc((string) $mr) ?>mm;
    margin-bottom: <?= esc((string) $bodyMarginBottomMm) ?>mm;
    margin-left: <?= esc((string) $ml) ?>mm;
}
body {
    margin: 0 !important;
    padding: 0 !important;
    position: relative;
}
<?php if ($pdfFooterEnabled): ?>
.pdf-ft-block.footer-grid {
    position: fixed !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
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
    --pdf-results-segment-padding-top: <?= (int) ($rs['segment_padding_top_px'] ?? 6) ?>px;
    --pdf-results-segment-padding-bottom: <?= (int) ($rs['segment_padding_bottom_px'] ?? 6) ?>px;
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
    --pdf-grupo-area-separator-margin-top: <?= (int) ($rs['grupo_area_separator_margin_top_px'] ?? 10) ?>px;
    --pdf-grupo-area-separator-margin-bottom: <?= (int) ($rs['grupo_area_separator_margin_bottom_px'] ?? 10) ?>px;
    --pdf-grupo-cabecera-title-margin-top: <?= (int) ($rs['grupo_cabecera_title_margin_top_px'] ?? 0) ?>px;
    --pdf-grupo-cabecera-title-margin-bottom: <?= (int) ($rs['grupo_cabecera_title_margin_bottom_px'] ?? 6) ?>px;
    --pdf-grupo-cabecera-title-first-margin-top: <?= (int) ($rs['grupo_prueba_gap_px'] ?? 10) ?>px;
    --pdf-grupo-cabecera-tipo-margin-top: <?= (int) ($rs['grupo_cabecera_tipo_muestra_margin_top_px'] ?? 0) ?>px;
    --pdf-grupo-cabecera-tipo-margin-bottom: <?= (int) ($rs['grupo_cabecera_tipo_muestra_margin_bottom_px'] ?? 10) ?>px;
    --pdf-grupo-cabecera-metodo-margin-top: <?= (int) ($rs['grupo_cabecera_metodo_margin_top_px'] ?? 0) ?>px;
    --pdf-grupo-cabecera-metodo-margin-bottom: <?= (int) ($rs['grupo_cabecera_metodo_margin_bottom_px'] ?? 10) ?>px;
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
table.results td.resultado-texto-rico-cell .resultado-texto-rico em,
table.results td.resultado-texto-rico-cell .resultado-texto-rico i,
table.results td.resultado-texto-rico-cell .resultado-texto-rico span[style*="italic"],
table.results td.resultado-texto-rico-cell .resultado-texto-rico span[style*="oblique"] {
    font-style: italic !important;
}
table.results td.resultado-texto-rico-cell .resultado-texto-rico strong,
table.results td.resultado-texto-rico-cell .resultado-texto-rico b {
    font-weight: 700 !important;
}
table.results td.resultado-texto-rico-cell .resultado-texto-rico u {
    text-decoration: underline !important;
}
table.results:not(.pdf-notes-table) th,
table.results:not(.pdf-notes-table) td {
    padding-top: <?= (int) ($rs['cell_padding_v_px'] ?? 6) ?>px !important;
    padding-bottom: <?= (int) ($rs['cell_padding_v_px'] ?? 6) ?>px !important;
    padding-left: 8px !important;
    padding-right: 8px !important;
}
table.results.results-cols-3,
table.results.results-cols-4 {
    table-layout: fixed !important;
    width: 100% !important;
}
table.results.results-cols-3 th:nth-child(1),
table.results.results-cols-3 td:nth-child(1) {
    width: 42% !important;
    max-width: 42% !important;
}
table.results.results-cols-3 th:nth-child(2),
table.results.results-cols-3 td:nth-child(2) {
    width: 25% !important;
    max-width: 25% !important;
}
table.results.results-cols-3 th:nth-child(3),
table.results.results-cols-3 td:nth-child(3) {
    width: 33% !important;
    max-width: 33% !important;
}
table.results.results-cols-3 td.resultado-colspan-rest[colspan="2"] {
    width: 58% !important;
    max-width: 58% !important;
}
table.results.results-cols-4 td.resultado-colspan-rest[colspan="3"] {
    width: 66% !important;
    max-width: 66% !important;
}
table.results td.out-range,
table.results .out-range {
    color: #c00 !important;
    font-weight: 700 !important;
}
table.results td.report-interpretacion-alto {
    color: #dc3545 !important;
    font-weight: 700 !important;
}
table.results td.report-interpretacion-bajo {
    color: #0d6efd !important;
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
    padding-top: <?= (int) ($rs['segment_padding_top_px'] ?? 6) ?>px !important;
    padding-bottom: <?= (int) ($rs['segment_padding_bottom_px'] ?? 6) ?>px !important;
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
    page-break-after: avoid;
    break-after: avoid;
}
.report-pdf-grupo-cabecera .report-tipo-muestra,
.report-pdf-grupo-cabecera .report-metodo-prueba {
    font-size: 9pt;
    color: #555;
    line-height: 1.3;
}
<?= view('registers/partials/report_layout_engine_print_styles', [
    'dompdf_download_only' => $embedStylesheetForPdf,
]) ?>
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
<?php if ($orderSheetHeaderEnabled): ?>
.pdf-order-sheet-table-row td {
    padding: 0;
    vertical-align: middle;
    white-space: nowrap;
    font-family: "DejaVu Sans", Helvetica, Arial, sans-serif;
    font-size: 9pt;
    font-weight: 600;
    line-height: 1.2;
    color: #333333;
}
.pdf-order-sheet-header,
.pdf-order-sheet-footer-band .pdf-order-sheet-header,
.pdf-order-sheet-header-print-fixed .pdf-order-sheet-header,
.pdf-order-sheet-header-injected .pdf-order-sheet-header,
.pdf-order-sheet-footer-band-dompdf .pdf-order-sheet-header {
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
.pdf-order-sheet-footer-band .pdf-order-sheet-header td,
.pdf-order-sheet-header-print-fixed .pdf-order-sheet-header td,
.pdf-order-sheet-header-injected .pdf-order-sheet-header td,
.pdf-order-sheet-footer-band-dompdf .pdf-order-sheet-header td {
    padding: 0;
    vertical-align: middle;
    white-space: nowrap;
}
.pdf-order-sheet-table-row .pdf-order-sheet-header-patient,
.pdf-order-sheet-header-patient,
.pdf-order-sheet-footer-band .pdf-order-sheet-header-patient,
.pdf-order-sheet-header-print-fixed .pdf-order-sheet-header-patient,
.pdf-order-sheet-header-injected .pdf-order-sheet-header-patient,
.pdf-order-sheet-footer-band-dompdf .pdf-order-sheet-header-patient {
    width: 50%;
    text-align: left;
}
.pdf-order-sheet-table-row .pdf-order-sheet-header-orden,
.pdf-order-sheet-header-orden,
.pdf-order-sheet-footer-band .pdf-order-sheet-header-orden,
.pdf-order-sheet-header-print-fixed .pdf-order-sheet-header-orden,
.pdf-order-sheet-header-injected .pdf-order-sheet-header-orden,
.pdf-order-sheet-footer-band-dompdf .pdf-order-sheet-header-orden {
    width: 50%;
    text-align: right;
}
.pdf-order-sheet-header-print-fixed,
.pdf-order-sheet-header-injected,
.pdf-order-sheet-footer-band:not(.pdf-order-sheet-footer-band-dompdf) {
    display: none;
    width: 100%;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
<?php if ($browserPrintMode): ?>
body.report-browser-print.js-order-sheet-footer-table-row .pdf-ft-block.footer-grid .pdf-order-sheet-table-row,
body.report-browser-print.js-order-sheet-footer-band .pdf-ft-block.footer-grid .pdf-order-sheet-footer-band {
    display: table-row !important;
}
body.report-browser-print.js-order-sheet-footer-table-row .pdf-ft-block.footer-grid .pdf-order-sheet-table-row td {
    display: table-cell !important;
    visibility: visible !important;
    opacity: 1 !important;
    break-inside: avoid-page !important;
    page-break-inside: avoid !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}
<?php endif; ?>
<?php if ($embedStylesheetForPdf && ! $browserPrintMode): ?>
body.pdf-dompdf-download .pdf-ft-block.footer-grid .pdf-order-sheet-table-row {
    display: table-row !important;
    visibility: visible !important;
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
}
body.pdf-dompdf-download .pdf-ft-block.footer-grid .pdf-order-sheet-table-row td {
    padding-top: 0 !important;
    background: transparent !important;
}
<?php endif; ?>
<?php endif; ?>
<?php if ($embedStylesheetForPdf && ! $browserPrintMode): ?>
<?php
$paginationModeDompdf = \App\Services\ReportPdfLayoutService::resolvePaginationModeFromLayout($pl);
$gpbDompdf = \App\Services\ReportPdfLayoutService::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
if ($paginationModeDompdf === \App\Services\ReportLayout\ReportPaginationMode::AREA_SOFT_FIT_SIGNATURE):
    $dompdfCompactScale = max(75, min(100, (int) ($gpbDompdf['compact_min_scale_percent'] ?? 85))) / 100;
    $dompdfCompactFontPt = round((float) ($rs['font_size_pt'] ?? 9) * $dompdfCompactScale, 2);
    $dompdfCompactPadPx  = (int) ($gpbDompdf['compact_cell_padding_px'] ?? 0) > 0
        ? (int) $gpbDompdf['compact_cell_padding_px']
        : (int) round((int) ($rs['cell_padding_v_px'] ?? 6) * $dompdfCompactScale);
    $dompdfCompactLh     = round((float) ($rs['line_height'] ?? 1.35) * (! empty($gpbDompdf['compact_aggressive']) ? 0.92 : 0.98), 3);
?>
body.pdf-dompdf-download.pdf-layout-engine .report-pdf-grupo-prueba.report-pdf-grupo-prueba-compact table.results th,
body.pdf-dompdf-download.pdf-layout-engine .report-pdf-grupo-prueba.report-pdf-grupo-prueba-compact table.results td {
    font-size: <?= esc((string) $dompdfCompactFontPt) ?>pt !important;
    padding-top: <?= (int) $dompdfCompactPadPx ?>px !important;
    padding-bottom: <?= (int) $dompdfCompactPadPx ?>px !important;
    line-height: <?= esc((string) $dompdfCompactLh) ?> !important;
}
body.pdf-dompdf-download.pdf-layout-engine .report-pdf-grupo-prueba.report-pdf-grupo-prueba-split-segments-only table.results th,
body.pdf-dompdf-download.pdf-layout-engine .report-pdf-grupo-prueba.report-pdf-grupo-prueba-split-segments-only table.results td {
    font-size: <?= esc((string) $dompdfCompactFontPt) ?>pt !important;
    padding-top: <?= (int) $dompdfCompactPadPx ?>px !important;
    padding-bottom: <?= (int) $dompdfCompactPadPx ?>px !important;
    line-height: <?= esc((string) $dompdfCompactLh) ?> !important;
}
<?php endif; ?>
<?php
$dompdfPdBodyFsMax = (float) ($pd['font_size_pt'] ?? 9.5);
foreach (is_array($pl['instances'] ?? null) ? $pl['instances'] : [] as $pdInst) {
    if (! is_array($pdInst) || ($pdInst['section'] ?? '') !== 'patient_doctor' || empty($pdInst['enabled'])) {
        continue;
    }
    $pdTs = is_array($pdInst['text_style'] ?? null) ? $pdInst['text_style'] : [];
    if (isset($pdTs['font_size_pt']) && is_numeric($pdTs['font_size_pt'])) {
        $dompdfPdBodyFsMax = max($dompdfPdBodyFsMax, (float) $pdTs['font_size_pt']);
    }
}
$dompdfPdBodyFs = round(max(7.0, $dompdfPdBodyFsMax) * 0.96, 2);
$dompdfPdBodyLh = max(1.0, round((float) ($pd['line_height'] ?? 1.35) * 0.88, 3));
$dompdfPdLblFs  = round(max(7.0, (float) ($pd['label_paciente_nombre_font_size_pt'] ?? $pd['font_size_pt'] ?? 9.5)) * 0.96, 2);
?>
/* Dompdf: paciente/médico — DejaVu y márgenes en «em» dejan más aire que Chrome al imprimir. */
body.pdf-dompdf-download .pdf-pd-block .pdf-section-table td.pdf-cell > .pdf-el-item:not(:last-child) {
    margin-bottom: 0 !important;
}
body.pdf-dompdf-download .pdf-pd-block .pdf-section-table td.pdf-cell > .pdf-el-item {
    font-size: <?= esc((string) $dompdfPdBodyFs) ?>pt !important;
    line-height: <?= esc((string) $dompdfPdBodyLh) ?> !important;
}
body.pdf-dompdf-download .pdf-pd-block .pdf-section-table .patient-line,
body.pdf-dompdf-download .pdf-pd-block .pdf-section-table .patient-line span {
    line-height: inherit !important;
}
body.pdf-dompdf-download .pdf-pd-block .pdf-section-table .patient-line .label {
    font-size: <?= esc((string) $dompdfPdLblFs) ?>pt !important;
    line-height: inherit !important;
}
body.pdf-dompdf-download .pdf-hg-block .header-piece-pagination,
body.pdf-dompdf-download .pdf-pd-block .header-piece-pagination,
body.pdf-dompdf-download .lab-firmas-pdf-block .header-piece-pagination {
    z-index: 120;
    margin: 0;
    padding: 0;
}
body.pdf-dompdf-download .pdf-hg-block .header-piece-pagination,
body.pdf-dompdf-download .pdf-pd-block .header-piece-pagination,
body.pdf-dompdf-download .lab-firmas-pdf-block .header-piece-pagination {
    position: fixed;
}
body.pdf-dompdf-download .pdf-hg-block .header-piece-pagination,
body.pdf-dompdf-download .pdf-pd-block .header-piece-pagination {
    top: <?= esc((string) $mt) ?>mm;
    bottom: auto;
}
body.pdf-dompdf-download .lab-firmas-pdf-block .header-piece-pagination {
    top: auto;
    bottom: <?= esc((string) $mb) ?>mm;
}
body.pdf-dompdf-download .pdf-hg-block .pdf-cell--left .header-piece-pagination,
body.pdf-dompdf-download .pdf-pd-block .pdf-cell--left .header-piece-pagination,
body.pdf-dompdf-download .lab-firmas-pdf-block .pdf-cell--left .header-piece-pagination {
    left: <?= esc((string) $ml) ?>mm;
    right: auto;
    text-align: left;
}
body.pdf-dompdf-download .pdf-hg-block .pdf-cell--center .header-piece-pagination,
body.pdf-dompdf-download .pdf-pd-block .pdf-cell--center .header-piece-pagination,
body.pdf-dompdf-download .lab-firmas-pdf-block .pdf-cell--center .header-piece-pagination {
    left: <?= esc((string) $ml) ?>mm;
    right: <?= esc((string) $mr) ?>mm;
    text-align: center;
}
body.pdf-dompdf-download .pdf-hg-block .pdf-cell--right .header-piece-pagination,
body.pdf-dompdf-download .pdf-pd-block .pdf-cell--right .header-piece-pagination,
body.pdf-dompdf-download .lab-firmas-pdf-block .pdf-cell--right .header-piece-pagination {
    left: auto;
    right: <?= esc((string) $mr) ?>mm;
    text-align: right;
}
<?php endif; ?>
</style>
