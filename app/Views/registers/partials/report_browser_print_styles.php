<?php
/**
 * Estilos exclusivos de impresión directa desde navegador (@page, @media print, toolbar).
 * Los estilos base de plantilla (márgenes, pie, variables) vienen de report_pdf_theme_styles.php.
 * Paginación de resultados: report_layout_engine_print_styles.php (LayoutEngine).
 *
 * @var float  $mt
 * @var float  $mr
 * @var float  $mb
 * @var float  $ml
 * @var string $printPageCssSize
 * @var bool   $pdfFooterEnabled
 * @var float  $pdfFooterReserveMm
 * @var string $printSegmentBreakInside
 * @var string $printPagLabelCssPos
 * @var string $printPagValueCssPos
 * @var bool   $order_sheet_header_enabled
 */
$mt = (float) ($mt ?? 15);
$mr = (float) ($mr ?? 15);
$mb = (float) ($mb ?? 15);
$ml = (float) ($ml ?? 15);
$printPageCssSize = (string) ($printPageCssSize ?? 'letter portrait');
$pdfFooterEnabled = ! empty($pdfFooterEnabled);
$pdfFooterReserveMm = $pdfFooterEnabled
    ? max(10.0, (float) ($pdfFooterReserveMm ?? 22.0))
    : 0.0;
$printSegmentBreakInside = (string) ($printSegmentBreakInside ?? 'auto');
$printPagLabelCssPos = (string) ($printPagLabelCssPos ?? '');
$printPagValueCssPos = (string) ($printPagValueCssPos ?? '');
$orderSheetGapMm = \App\Services\ReportPdfLayoutService::ORDER_SHEET_HEADER_GAP_ABOVE_FOOTER_MM;
$orderSheetBandReserveMm = \App\Services\ReportPdfLayoutService::orderSheetHeaderPaginationReserveMm();
?>
<style>
:root {
    --print-margin-top-mm: <?= esc((string) $mt) ?>;
    --print-margin-right-mm: <?= esc((string) $mr) ?>;
    --print-margin-bottom-mm: <?= esc((string) $mb) ?>;
    --print-margin-left-mm: <?= esc((string) $ml) ?>;
    --print-footer-reserve-mm: <?= esc((string) $pdfFooterReserveMm) ?>;
    --print-order-sheet-gap-mm: <?= esc((string) $orderSheetGapMm) ?>;
    --print-order-sheet-band-reserve-mm: <?= esc((string) $orderSheetBandReserveMm) ?>;
}
@page {
    size: <?= esc($printPageCssSize) ?>;
    margin-top: <?= esc((string) $mt) ?>mm;
    margin-right: <?= esc((string) $mr) ?>mm;
    margin-bottom: <?= esc((string) $mb) ?>mm;
    margin-left: <?= esc((string) $ml) ?>mm;
}
html,
body.report-browser-print {
    margin: 0 !important;
    padding: 0 !important;
}
body.report-browser-print .pdf-main-stack {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    box-sizing: border-box;
}
.report-print-toolbar {
    padding: 10px 12px;
    margin: -8px -8px 16px -8px;
    background: #f1f3f5;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.report-print-browser-hint {
    flex-basis: 100%;
    line-height: 1.35;
    padding-top: 2px;
}
.report-print-btn-primary {
    padding: 8px 16px;
    background: #0d6efd;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}
.report-print-btn-secondary {
    padding: 8px 16px;
    background: #fff;
    color: #333;
    border: 1px solid #ced4da;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}
body.js-total-pages-ready .pdf-counter-pages::before {
    content: '' !important;
}
.print-pagination-fixed {
    position: fixed;
    z-index: 20;
    font-size: 9pt;
    color: #333;
    background: rgba(255, 255, 255, 0.8);
    border: 1px solid #d6d6d6;
    border-radius: 4px;
    padding: 2px 6px;
    line-height: 1.2;
}
.print-pagination-label-fixed {
    <?= esc($printPagLabelCssPos, 'css') ?>
}
.print-pagination-value-fixed {
    <?= esc($printPagValueCssPos, 'css') ?>
}
@media print {
    body.report-browser-print .pdf-ft-block.footer-grid {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        bottom: calc(var(--print-margin-bottom-mm, <?= esc((string) $mb) ?>) * 1mm) !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding-left: calc(var(--print-margin-left-mm, <?= esc((string) $ml) ?>) * 1mm) !important;
        padding-right: calc(var(--print-margin-right-mm, <?= esc((string) $mr) ?>) * 1mm) !important;
        box-sizing: border-box !important;
        z-index: 100 !important;
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    body.report-browser-print.js-print-footer-fixed .pdf-ft-block.footer-grid {
        position: fixed !important;
    }
    html,
    body.report-browser-print {
        overflow: visible !important;
        margin: 0 !important;
        padding-bottom: 0 !important;
        padding-top: 0 !important;
    }
    body.report-browser-print .pdf-main-stack {
        overflow: visible !important;
        margin: 0 !important;
        padding-top: 0 !important;
        padding-bottom: calc(var(--print-footer-reserve-mm, <?= esc((string) $pdfFooterReserveMm) ?>) * 1mm) !important;
        isolation: auto !important;
    }
    body.report-browser-print .report-pdf-grupo-cabecera {
        display: flow-root;
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page .report-pdf-grupo-cabecera,
    body.report-browser-print.pdf-layout-engine.pdf-pagination-flow-no-lone-signature .report-pdf-grupo-cabecera {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.report-browser-print .report-segment-table-wrap > .report-segment-title {
        break-after: avoid-page !important;
        page-break-after: avoid !important;
    }
    body.report-browser-print .report-segment-table-wrap > .report-segment-title + table.results {
        break-before: avoid !important;
        page-break-before: avoid !important;
    }
    body.report-browser-print .report-segment-table-wrap,
    body.report-browser-print .report-refs-matrix-wrap {
        break-inside: <?= esc($printSegmentBreakInside, 'css') ?> !important;
        page-break-inside: <?= esc($printSegmentBreakInside, 'css') ?> !important;
    }
    body.report-browser-print table.results thead {
        display: table-header-group !important;
    }
    body.report-browser-print table.results tbody {
        display: table-row-group !important;
    }
    body.report-browser-print table.results tbody tr {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page table.results tbody tr,
    body.report-browser-print.pdf-layout-engine.pdf-pagination-flow-no-lone-signature table.results tbody tr {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.report-browser-print table.results > thead:first-of-type + tbody > tr:first-child:not(.report-results-thead-continuation-injected) {
        break-before: avoid !important;
        page-break-before: avoid !important;
    }
    body.report-browser-print tr.report-results-thead-continuation-injected {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
        break-after: avoid !important;
        page-break-after: avoid !important;
    }
    body.report-browser-print tr.report-results-thead-continuation-injected + tr {
        break-before: avoid !important;
        page-break-before: avoid !important;
    }
    body.report-browser-print tr.report-results-thead-continuation-injected th {
        background: var(--pdf-results-header-bg, #0066cc) !important;
        color: var(--pdf-results-header-color, #fff) !important;
        border: 1px solid var(--pdf-results-border-color, #ddd) !important;
        font-family: var(--pdf-results-font-family, "DejaVu Sans"), sans-serif !important;
        font-size: var(--pdf-results-font-size, 9pt) !important;
        font-weight: var(--pdf-results-font-weight, normal) !important;
        vertical-align: middle !important;
    }
    body.report-browser-print table.results.report-results-split-active thead {
        display: table-row-group !important;
    }
    body.report-browser-print .report-lab-firma-grupo-inline,
    body.report-browser-print .lab-firmas-pdf-block-global {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print .lab-firmas-pdf-block .lab-firmas-title-grid,
    body.report-browser-print .lab-firmas-pdf-block .lab-firmas-body-grid,
    body.report-browser-print .lab-firmas-pdf-block .pdf-section-table {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print .report-lab-firma-page-leader,
    body.report-browser-print .report-analysis-firma-page-leader {
        display: block !important;
        break-before: page !important;
        page-break-before: always !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        line-height: 0 !important;
        font-size: 0 !important;
        overflow: hidden !important;
    }
    .no-print,
    .report-print-toolbar,
    .report-print-browser-hint {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        max-height: 0 !important;
        overflow: hidden !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
    }
    body.report-browser-print.js-order-sheet-footer-band .pdf-ft-block.footer-grid .pdf-order-sheet-footer-band,
    body.report-browser-print .pdf-ft-block.footer-grid .pdf-order-sheet-footer-band-dompdf {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: 100% !important;
        margin: 0 0 4px 0 !important;
        padding: 0 0 5px 0 !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12) !important;
        box-sizing: border-box !important;
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    body.report-browser-print.js-order-sheet-header-in-flow .pdf-ft-block.footer-grid .pdf-order-sheet-footer-band,
    body.report-browser-print.js-order-sheet-header-in-flow .pdf-ft-block.footer-grid .pdf-order-sheet-footer-band-dompdf {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        overflow: hidden !important;
    }
    body.report-browser-print.js-order-sheet-header-in-flow .pdf-order-sheet-header-injected {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: 100% !important;
        margin: 0 0 4px 0 !important;
        padding: 0 0 5px 0 !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12) !important;
        box-sizing: border-box !important;
        break-after: avoid-page !important;
        page-break-after: avoid !important;
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    body.report-browser-print.js-order-sheet-footer-band .pdf-ft-block.footer-grid .pdf-order-sheet-footer-band .pdf-order-sheet-header,
    body.report-browser-print .pdf-ft-block.footer-grid .pdf-order-sheet-footer-band-dompdf .pdf-order-sheet-header {
        width: 100% !important;
    }
    body.report-browser-print.js-order-sheet-footer-band-standalone > .pdf-order-sheet-footer-band-standalone {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        bottom: calc(var(--print-margin-bottom-mm, <?= esc((string) $mb) ?>) * 1mm) !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 0 5px 0 !important;
        padding-left: calc(var(--print-margin-left-mm, <?= esc((string) $ml) ?>) * 1mm) !important;
        padding-right: calc(var(--print-margin-right-mm, <?= esc((string) $mr) ?>) * 1mm) !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12) !important;
        box-sizing: border-box !important;
        z-index: 100 !important;
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    body.report-browser-print.js-order-sheet-footer-band-standalone .pdf-main-stack {
        padding-bottom: calc(
            var(--print-order-sheet-band-reserve-mm, <?= esc((string) $orderSheetBandReserveMm) ?>) * 1mm
        ) !important;
    }
    body.report-browser-print .header-piece-pagination {
        z-index: 120 !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
    }
    body.report-browser-print .pdf-hg-block .header-piece-pagination,
    body.report-browser-print .pdf-pd-block .header-piece-pagination,
    body.report-browser-print .lab-firmas-pdf-block .header-piece-pagination {
        position: fixed !important;
    }
    body.report-browser-print .pdf-ft-block .header-piece-pagination {
        position: static !important;
    }
    body.report-browser-print .pdf-hg-block .header-piece-pagination,
    body.report-browser-print .pdf-pd-block .header-piece-pagination {
        top: calc(var(--print-margin-top-mm, <?= esc((string) $mt) ?>) * 1mm) !important;
        bottom: auto !important;
    }
    body.report-browser-print .lab-firmas-pdf-block .header-piece-pagination {
        top: auto !important;
        bottom: calc((var(--print-margin-bottom-mm, <?= esc((string) $mb) ?>) + var(--print-footer-reserve-mm, <?= esc((string) $pdfFooterReserveMm) ?>)) * 1mm) !important;
    }
    body.report-browser-print .pdf-cell--left .header-piece-pagination {
        left: calc(var(--print-margin-left-mm, <?= esc((string) $ml) ?>) * 1mm) !important;
        right: auto !important;
        text-align: left !important;
    }
    body.report-browser-print .pdf-cell--center .header-piece-pagination {
        left: calc(var(--print-margin-left-mm, <?= esc((string) $ml) ?>) * 1mm) !important;
        right: calc(var(--print-margin-right-mm, <?= esc((string) $mr) ?>) * 1mm) !important;
        text-align: center !important;
    }
    body.report-browser-print .pdf-cell--right .header-piece-pagination {
        left: auto !important;
        right: calc(var(--print-margin-right-mm, <?= esc((string) $mr) ?>) * 1mm) !important;
        text-align: right !important;
    }
    body,
    table.results th,
    table.results td,
    .report-segment-title,
    .pdf-card-header,
    .pdf-notes-title,
    .pdf-notes-cell,
    .pdf-lab-f-title,
    .pdf-lab-f-cell {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}
</style>
<style>
<?= view('registers/partials/report_layout_engine_print_styles') ?>
</style>
