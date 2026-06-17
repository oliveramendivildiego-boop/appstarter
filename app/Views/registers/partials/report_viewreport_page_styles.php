<?php
/**
 * Estilos de vista previa paginada (viewreport) — hojas apiladas como el PDF.
 *
 * @var float  $mt
 * @var float  $mr
 * @var float  $mb
 * @var float  $ml
 * @var float  $page_width_mm
 * @var float  $page_height_mm
 * @var bool   $pdf_footer_enabled
 */
$mt = (float) ($mt ?? 15);
$mr = (float) ($mr ?? 15);
$mb = (float) ($mb ?? 15);
$ml = (float) ($ml ?? 15);
$pageWidthMm  = max(50.0, (float) ($page_width_mm ?? 215.9));
$pageHeightMm = max(50.0, (float) ($page_height_mm ?? 279.4));
$pdfFooterEnabled = ! empty($pdf_footer_enabled);
?>
<style>
.viewreport-pdf-shell--paginated {
    width: 100%;
    overflow-x: auto;
    padding: 8px 0 24px;
}
.viewreport-pdf-shell--paginated.viewreport-pdf-ready .viewreport-pdf-source {
    position: absolute !important;
    left: -99999px !important;
    top: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
    height: 0 !important;
    overflow: hidden !important;
}
.viewreport-pdf-shell--paginated .viewreport-pdf-source .viewreport-pdf-sheet {
    max-width: none;
    width: <?= esc((string) $pageWidthMm) ?>mm;
    margin: 0;
    box-shadow: none;
    min-height: 0;
    display: block;
    box-sizing: border-box;
}
.viewreport-pdf-pages {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
}
.viewreport-pdf-page {
    position: relative;
    width: <?= esc((string) $pageWidthMm) ?>mm;
    height: <?= esc((string) $pageHeightMm) ?>mm;
    box-sizing: border-box;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.14);
    overflow: hidden;
    padding: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) $mb) ?>mm <?= esc((string) $ml) ?>mm;
    display: flex;
    flex-direction: column;
}
.viewreport-pdf-page .viewreport-page-watermark {
    position: absolute;
    left: <?= esc((string) $ml) ?>mm;
    right: <?= esc((string) $mr) ?>mm;
    top: <?= esc((string) $mt) ?>mm;
    bottom: <?= esc((string) $mb) ?>mm;
    width: auto;
    height: auto;
    max-height: none;
    z-index: 0;
    pointer-events: none;
    margin: 0;
    padding: 0;
}
.viewreport-pdf-page .viewreport-page-watermark .pdf-watermark-inner,
.viewreport-pdf-page .viewreport-page-watermark .pdf-watermark-table,
.viewreport-pdf-page .viewreport-page-watermark .pdf-watermark-td {
    height: 100% !important;
    min-height: 0 !important;
    max-height: none !important;
}
.viewreport-pdf-page-body {
    position: relative;
    z-index: 1;
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
}
.viewreport-pdf-page .viewreport-page-footer {
    position: relative;
    z-index: 2;
    flex-shrink: 0;
    margin-top: auto;
    padding-top: 6px;
    box-sizing: border-box;
}
<?php if ($pdfFooterEnabled): ?>
.viewreport-pdf-page .viewreport-page-footer.pdf-ft-block.footer-grid {
    position: static !important;
    left: auto !important;
    right: auto !important;
    bottom: auto !important;
    width: 100% !important;
    min-height: 0 !important;
    margin-top: auto !important;
}
<?php endif; ?>
.viewreport-pdf-shell--paginated .viewreport-pdf-page .pdf-main-stack {
    position: relative;
    z-index: 1;
}
</style>
