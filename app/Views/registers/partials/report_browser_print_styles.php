<?php
/**
 * Estilos exclusivos de impresión directa desde navegador (@page, @media print, toolbar).
 * Los estilos base de plantilla (márgenes, pie, variables) vienen de report_pdf_theme_styles.php.
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
/* Solo @page define márgenes de hoja; body sin margen extra (evita doble margen en vista previa). */
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
    /*
     * Pie fijo dentro del área imprimible; el margen inferior de @page es solo el de plantilla (config).
     * La reserva de pie para paginación se calcula en JS, no se suma a @page.
     */
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
        padding-left: 0 !important;
        padding-right: 0 !important;
        box-sizing: border-box !important;
        z-index: 5 !important;
    }
    html,
    body.report-browser-print,
    body.report-browser-print .pdf-main-stack {
        overflow: visible !important;
        margin: 0 !important;
        padding-bottom: 0 !important;
        padding-top: 0 !important;
    }
    body.report-browser-print .pdf-main-stack {
        isolation: auto !important;
    }
    body.report-browser-print .report-pdf-grupo-cabecera {
        display: flow-root;
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
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
    body.report-browser-print.pdf-gpb-segment-rules .report-segment-table-wrap:not(.report-segment-allow-split),
    body.report-browser-print.pdf-gpb-segment-rules .report-refs-matrix-wrap:not(.report-segment-allow-split),
    body.report-browser-print.pdf-gpb-keep-segment .report-segment-table-wrap:not(.report-segment-allow-split),
    body.report-browser-print.pdf-gpb-keep-segment .report-refs-matrix-wrap:not(.report-segment-allow-split) {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print.pdf-gpb-segment-rules .report-segment-table-wrap:not(.report-segment-allow-split) table.results,
    body.report-browser-print.pdf-gpb-segment-rules .report-refs-matrix-wrap:not(.report-segment-allow-split) table.results,
    body.report-browser-print.pdf-gpb-keep-segment .report-segment-table-wrap:not(.report-segment-allow-split) table.results {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print.pdf-gpb-keep-segment .report-segment-table-wrap.report-segment-allow-split,
    body.report-browser-print.pdf-gpb-keep-segment .report-refs-matrix-wrap.report-segment-allow-split,
    body.report-browser-print.pdf-gpb-segment-rules .report-segment-table-wrap.report-segment-allow-split,
    body.report-browser-print.pdf-gpb-segment-rules .report-refs-matrix-wrap.report-segment-allow-split {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.report-browser-print.pdf-gpb-keep-segment .report-segment-table-wrap.report-segment-force-break-before,
    body.report-browser-print.pdf-gpb-keep-segment .report-refs-matrix-wrap.report-segment-force-break-before,
    body.report-browser-print.pdf-gpb-segment-rules .report-segment-table-wrap.report-segment-force-break-before,
    body.report-browser-print.pdf-gpb-segment-rules .report-refs-matrix-wrap.report-segment-force-break-before {
        break-before: page !important;
        page-break-before: always !important;
    }
    body.report-browser-print table.results tbody tr {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print table.results > thead:first-of-type + tbody > tr:first-child {
        break-before: avoid !important;
        page-break-before: avoid !important;
    }
    body.report-browser-print .report-pdf-grupo-cabecera + .report-segment-table-wrap,
    body.report-browser-print .report-pdf-grupo-cabecera + .report-refs-matrix-wrap {
        break-before: avoid !important;
        page-break-before: avoid !important;
    }
    body.report-browser-print .report-pdf-grupo-cabecera.report-cabecera-force-break-before {
        break-before: page !important;
        page-break-before: always !important;
    }
    body.report-browser-print .report-pdf-subgrupo-block.report-subgrupo-force-break-before {
        break-before: page !important;
        page-break-before: always !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba.report-pdf-grupo-browser-print-area:not(.report-pdf-grupo-prueba-first) {
        page-break-before: always !important;
        break-before: page !important;
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-area-start-table {
        width: 100% !important;
        border-collapse: collapse !important;
        page-break-before: avoid !important;
        break-before: avoid !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
        break-after: avoid !important;
        page-break-after: avoid !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-area-start-table td {
        padding: 0 !important;
        margin: 0 !important;
        border: 0 !important;
        vertical-align: top !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-area-start-table .report-pdf-grupo-area-separator {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        margin-top: 0 !important;
        padding-top: 6px !important;
        padding-bottom: 6px !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
        box-shadow: none !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-browser-print-area .report-pdf-grupo-area-start-table + .report-pdf-subgrupo-block,
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-browser-print-area .report-pdf-grupo-area-start-table + .report-segment-table-wrap {
        break-before: avoid !important;
        page-break-before: avoid !important;
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) .report-segment-table-wrap,
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) .report-refs-matrix-wrap,
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) table.results {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-first):not(.report-pdf-grupo-browser-print-area) {
        break-before: auto !important;
        page-break-before: auto !important;
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-area-page-leader,
    body.report-browser-print.pdf-gpb-grupo-intact .report-area-page-leader-force-break-before {
        display: block !important;
        /* Altura real (no padding en el título): Chrome recorta padding-top al inicio de hoja. */
        height: calc(var(--pdf-results-grupo-gap, 10px) + var(--pdf-grupo-area-separator-margin-top, 10px)) !important;
        min-height: calc(var(--pdf-results-grupo-gap, 10px) + var(--pdf-grupo-area-separator-margin-top, 10px)) !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        line-height: 0 !important;
        font-size: 0 !important;
        overflow: visible !important;
        break-before: page !important;
        page-break-before: always !important;
        break-after: avoid !important;
        page-break-after: avoid !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-area-separator,
    body.report-browser-print.pdf-gpb-grupo-intact .report-area-separator-force-break-before {
        break-before: avoid !important;
        page-break-before: avoid !important;
        break-after: avoid-page !important;
        page-break-after: avoid !important;
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
        margin-top: 0 !important;
        padding-top: 6px !important;
        padding-bottom: 6px !important;
        margin-bottom: var(--pdf-grupo-area-separator-margin-bottom, 10px) !important;
        overflow: visible !important;
        box-shadow: none !important;
        -webkit-box-decoration-break: clone;
        box-decoration-break: clone;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-area-page-leader + .report-pdf-grupo-prueba > .report-pdf-grupo-area-separator {
        padding-top: 6px !important;
        margin-top: 0 !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-first):not(:has(> .report-pdf-grupo-area-separator)),
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba.report-pdf-grupo-prueba-force-break-before:not(:has(> .report-pdf-grupo-area-separator)) {
        break-before: page !important;
        page-break-before: always !important;
        margin-top: 0 !important;
        padding-top: var(--pdf-results-grupo-gap, 10px) !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) .report-segment-table-wrap.report-segment-force-break-before,
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) .report-refs-matrix-wrap.report-segment-force-break-before,
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) .report-pdf-grupo-cabecera.report-cabecera-force-break-before,
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-allow-split) .report-pdf-subgrupo-block.report-subgrupo-force-break-before {
        break-before: auto !important;
        page-break-before: auto !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.report-browser-print.pdf-gpb-keep-together-if-fits .report-pdf-grupo-prueba.report-pdf-grupo-prueba-keep-on-page,
    body.report-browser-print.pdf-gpb-keep-together-if-fits-auto-order .report-pdf-grupo-prueba.report-pdf-grupo-prueba-keep-on-page {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print.pdf-gpb-keep-together-if-fits .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split,
    body.report-browser-print.pdf-gpb-keep-together-if-fits-auto-order .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.report-browser-print.pdf-gpb-keep-together-if-fits .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split .report-segment-table-wrap,
    body.report-browser-print.pdf-gpb-keep-together-if-fits .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split .report-refs-matrix-wrap,
    body.report-browser-print.pdf-gpb-keep-together-if-fits-auto-order .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split .report-segment-table-wrap,
    body.report-browser-print.pdf-gpb-keep-together-if-fits-auto-order .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split .report-refs-matrix-wrap {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.report-browser-print.pdf-gpb-keep-together-if-fits .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split .report-segment-table-wrap.report-segment-allow-split,
    body.report-browser-print.pdf-gpb-keep-together-if-fits .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split .report-refs-matrix-wrap.report-segment-allow-split,
    body.report-browser-print.pdf-gpb-keep-together-if-fits-auto-order .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split .report-segment-table-wrap.report-segment-allow-split,
    body.report-browser-print.pdf-gpb-keep-together-if-fits-auto-order .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split .report-refs-matrix-wrap.report-segment-allow-split {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.report-browser-print.pdf-gpb-keep-together-if-fits .report-pdf-grupo-prueba-first .report-cabecera-force-break-before,
    body.report-browser-print.pdf-gpb-keep-together-if-fits .report-pdf-grupo-prueba-first .report-subgrupo-force-break-before,
    body.report-browser-print.pdf-gpb-keep-together-if-fits-auto-order .report-pdf-grupo-prueba-first .report-cabecera-force-break-before,
    body.report-browser-print.pdf-gpb-keep-together-if-fits-auto-order .report-pdf-grupo-prueba-first .report-subgrupo-force-break-before {
        break-before: auto !important;
        page-break-before: auto !important;
    }
    body.report-browser-print .report-lab-firma-grupo-inline {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print .lab-firmas-pdf-block .lab-firmas-title-grid,
    body.report-browser-print .lab-firmas-pdf-block .lab-firmas-body-grid,
    body.report-browser-print .lab-firmas-pdf-block .pdf-section-table {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
    body.report-browser-print .report-lab-firma-page-leader {
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
    .report-print-toolbar {
        display: none !important;
    }
    body.report-browser-print.js-order-sheet-header-print .pdf-order-sheet-header-print-fixed {
        display: block !important;
        position: fixed !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        z-index: 3 !important;
        bottom: calc(
            (var(--print-margin-bottom-mm, <?= esc((string) $mb) ?>)
            + var(--print-footer-reserve-mm, <?= esc((string) $pdfFooterReserveMm) ?>)
            + var(--print-order-sheet-gap-mm, <?= esc((string) $orderSheetGapMm) ?>)) * 1mm
        ) !important;
        background: #ffffff !important;
        box-sizing: border-box !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    body.report-browser-print.js-order-sheet-header-print .pdf-osh-page1-cover {
        position: absolute !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        z-index: 4 !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        pointer-events: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-area-page-leader + .report-pdf-grupo-prueba > .report-pdf-grupo-area-separator {
        position: relative !important;
        z-index: 6 !important;
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
