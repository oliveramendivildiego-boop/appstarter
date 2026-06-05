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
?>
<style>
:root {
    --print-margin-top-mm: <?= esc((string) $mt) ?>;
    --print-margin-right-mm: <?= esc((string) $mr) ?>;
    --print-margin-bottom-mm: <?= esc((string) $mb) ?>;
    --print-margin-left-mm: <?= esc((string) $ml) ?>;
    --print-footer-reserve-mm: <?= esc((string) $pdfFooterReserveMm) ?>;
}
@page {
    size: <?= esc($printPageCssSize) ?>;
    margin: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) ($mb + $pdfFooterReserveMm)) ?>mm <?= esc((string) $ml) ?>mm;
}
<?php if ($pdfFooterEnabled): ?>
body.report-browser-print .pdf-main-stack {
    padding-bottom: calc(var(--print-footer-reserve-mm) * 1mm);
    box-sizing: border-box;
}
<?php endif; ?>
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
    body.report-browser-print {
        margin: 0 !important;
        padding: 0 !important;
    }
    /*
     * El pie (position:fixed) se ancla al borde inferior del área de página (@page content box).
     * Con margin-bottom = mb + reserva pie, bottom:0 deja el pie ~reserva mm por encima del borde físico.
     * Desplazarlo hacia la zona de margen inferior alinea el borde del pie con el borde útil de plantilla (mb).
     */
    body.report-browser-print .pdf-ft-block.footer-grid {
        bottom: calc(-1 * var(--print-footer-reserve-mm, <?= esc((string) $pdfFooterReserveMm) ?>) * 1mm) !important;
    }
    html,
    body.report-browser-print,
    body.report-browser-print .pdf-main-stack {
        overflow: visible !important;
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
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba.report-pdf-grupo-prueba-force-break-before {
        break-before: page !important;
        page-break-before: always !important;
    }
    body.report-browser-print.pdf-gpb-grupo-intact .report-pdf-grupo-prueba.report-pdf-grupo-prueba-allow-split {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.report-browser-print.pdf-gpb-keep-together-if-fits .report-pdf-grupo-prueba.report-pdf-grupo-prueba-keep-on-page {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    .report-print-toolbar {
        display: none !important;
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
