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
?>
<style>
<?php if ($useSheetPadding): ?>
.viewreport-pdf-sheet {
    max-width: 210mm;
    margin-left: auto;
    margin-right: auto;
    padding: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) $mb) ?>mm <?= esc((string) $ml) ?>mm;
    box-sizing: border-box;
    background: #fff;
    position: relative;
}
<?php else: ?>
body { margin: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) $mb) ?>mm <?= esc((string) $ml) ?>mm !important; position: relative; }
<?php endif; ?>
:root {
    --pdf-card-header-bg: <?= esc($ch['bg_color']) ?>;
    --pdf-card-header-color: <?= esc($ch['text_color']) ?>;
    --pdf-card-header-font-family: "<?= esc($ch['font_family']) ?>";
    --pdf-card-header-font-size: <?= esc((string) $ch['font_size_pt']) ?>pt;
    --pdf-card-header-font-weight: <?= esc($ch['font_weight']) ?>;
    --pdf-card-header-font-style: <?= esc($ch['font_style']) ?>;
    --pdf-card-header-transform: <?= esc($ch['text_transform']) ?>;
    --pdf-header-separator-color: <?= esc($hs['separator_color']) ?>;
    --pdf-notes-title-bg: <?= esc($ns['title_bg_color']) ?>;
    --pdf-notes-title-color: <?= esc($ns['title_text_color']) ?>;
    --pdf-notes-body-bg: <?= esc($ns['body_bg_color']) ?>;
    --pdf-notes-body-color: <?= esc($ns['body_text_color']) ?>;
    --pdf-notes-font-family: "<?= esc($ns['font_family']) ?>";
    --pdf-notes-font-size: <?= esc((string) $ns['font_size_pt']) ?>pt;
    --pdf-notes-font-weight: <?= esc($ns['font_weight']) ?>;
    --pdf-notes-font-style: <?= esc($ns['font_style']) ?>;
    --pdf-notes-transform: <?= esc($ns['text_transform']) ?>;
    --pdf-notes-line-height: <?= esc((string) $ns['line_height']) ?>;
    --pdf-lf-title-bg: <?= esc($lf['title_bg_color']) ?>;
    --pdf-lf-title-color: <?= esc($lf['title_text_color']) ?>;
    --pdf-lf-body-bg: <?= esc($lf['body_bg_color']) ?>;
    --pdf-lf-body-color: <?= esc($lf['body_text_color']) ?>;
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
table.results td.out-range,
table.results .out-range {
    color: #c00 !important;
    font-weight: 700 !important;
}
.header-grid {
    border-bottom-color: <?= esc($hs['separator_color']) ?> !important;
}
.report-segment-title,
.pdf-card-header {
    background: <?= esc($rsSegBg) ?> !important;
    border-color: <?= esc($rs['segment_border_color']) ?> !important;
    border-width: <?= esc((string) $rs['segment_border_width_px']) ?>px !important;
    box-shadow: <?= esc($rsSegShadow) ?> !important;
}
</style>
