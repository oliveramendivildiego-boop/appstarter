<?php
/**
 * Paginación LayoutEngine — reglas por modo (body.pdf-pagination-*).
 * Solo aplica saltos que el LayoutPlan marca en HTML; nada genérico fuera de modo.
 *
 * @var bool $dompdf_download_only Si true, omite reglas solo para impresión navegador.
 */
$__dompdfCssOnly = ! empty($dompdf_download_only);
if ($__dompdfCssOnly) {
    ob_start();
}
?>
/* ——— Común: separador inter-área (MODE 3–4) ——— */
@media print {
    body.pdf-layout-engine.report-browser-print .report-grupo-inter-page-break {
        display: block !important;
        width: 100% !important;
        height: 1px !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        line-height: 0 !important;
        font-size: 0 !important;
        overflow: hidden !important;
        clear: both !important;
        break-before: page !important;
        page-break-before: always !important;
        break-after: avoid !important;
        page-break-after: avoid !important;
    }
    body.pdf-layout-engine.report-browser-print .report-grupo-inter-page-break + .report-pdf-grupo-prueba {
        break-before: avoid !important;
        page-break-before: avoid !important;
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    body.pdf-layout-engine.report-browser-print table.results thead {
        display: table-header-group !important;
    }
}
body.pdf-layout-engine.pdf-dompdf-download .report-grupo-inter-page-break.report-grupo-inter-page-break-pdf {
    page-break-before: always !important;
    break-before: page !important;
    height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    line-height: 0 !important;
    font-size: 0 !important;
    overflow: hidden !important;
}
body.pdf-layout-engine.pdf-dompdf-download .report-grupo-inter-page-break.report-grupo-inter-page-break-pdf + .report-pdf-grupo-prueba {
    page-break-before: avoid !important;
    break-before: avoid !important;
    margin-top: 0 !important;
    padding-top: 0 !important;
}
body.pdf-layout-engine.pdf-dompdf-download table.results thead {
    display: table-header-group !important;
}

/* ——— MODE 1 y 2: flujo continuo ——— */
@media print {
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-subgrupo-block.report-subgrupo-force-break-before,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-subgrupo-block.report-subgrupo-force-break-before {
        break-before: page !important;
        page-break-before: always !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-grupo-cabecera.report-cabecera-force-break-before,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-grupo-cabecera.report-cabecera-force-break-before {
        break-before: auto !important;
        page-break-before: auto !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-subgrupo-block,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-subgrupo-block {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-grupo-cabecera,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-grupo-cabecera {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-segment-table-wrap,
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-refs-matrix-wrap,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-segment-table-wrap,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-refs-matrix-wrap {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-grupo-cabecera + .report-segment-table-wrap,
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-grupo-cabecera + .report-refs-matrix-wrap,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-grupo-cabecera + .report-segment-table-wrap,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-grupo-cabecera + .report-refs-matrix-wrap {
        break-before: auto !important;
        page-break-before: auto !important;
    }
    /* Flujo compacto: sub-pruebas del mismo área sin separación visual */
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-subgrupo-block.report-pdf-subgrupo-prueba,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-subgrupo-block.report-pdf-subgrupo-prueba {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-subgrupo-prueba table.results,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-subgrupo-prueba table.results {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print table.results tbody tr,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print table.results tbody tr {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-first),
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-first) {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-grupo-area-separator,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-grupo-area-separator {
        break-after: avoid-page !important;
        page-break-after: avoid !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.report-browser-print .report-pdf-grupo-area-separator + .report-pdf-subgrupo-block,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-pdf-grupo-area-separator + .report-pdf-subgrupo-block {
        break-before: avoid-page !important;
        page-break-before: avoid !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-signature-tail-bundle {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-signature-tail-bundle .report-lab-firma-grupo-inline {
        break-before: avoid-page !important;
        page-break-before: avoid !important;
    }
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-signature-tail-bundle .report-pdf-grupo-cabecera,
    body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.report-browser-print .report-signature-tail-bundle .report-segment-title {
        break-after: avoid-page !important;
        page-break-after: avoid !important;
    }
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-subgrupo-block.report-subgrupo-force-break-before,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-subgrupo-block.report-subgrupo-force-break-before {
    page-break-before: always !important;
    break-before: page !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-grupo-cabecera.report-cabecera-force-break-before,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-grupo-cabecera.report-cabecera-force-break-before {
    page-break-before: auto !important;
    break-before: auto !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-subgrupo-block,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-subgrupo-block {
    page-break-inside: auto !important;
    break-inside: auto !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-grupo-cabecera,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-grupo-cabecera {
    page-break-inside: auto !important;
    break-inside: auto !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-segment-table-wrap,
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-refs-matrix-wrap,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-segment-table-wrap,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-refs-matrix-wrap {
    page-break-inside: auto !important;
    break-inside: auto !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-grupo-cabecera + .report-segment-table-wrap,
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-grupo-cabecera + .report-refs-matrix-wrap,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-grupo-cabecera + .report-segment-table-wrap,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-grupo-cabecera + .report-refs-matrix-wrap {
    page-break-before: auto !important;
    break-before: auto !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-subgrupo-block.report-pdf-subgrupo-prueba,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-subgrupo-block.report-pdf-subgrupo-prueba {
    padding-top: 0 !important;
    margin-top: 0 !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-subgrupo-prueba table.results,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-subgrupo-prueba table.results {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download table.results tbody tr,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download table.results tbody tr {
    page-break-inside: auto !important;
    break-inside: auto !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-first),
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-first) {
    margin-top: 0 !important;
    padding-top: 0 !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-grupo-area-separator,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-grupo-area-separator {
    page-break-after: avoid !important;
    break-after: avoid-page !important;
}
body.pdf-layout-engine.pdf-pagination-flow-continuous-signature-last-page.pdf-dompdf-download .report-pdf-grupo-area-separator + .report-pdf-subgrupo-block,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-pdf-grupo-area-separator + .report-pdf-subgrupo-block {
    page-break-before: avoid !important;
    break-before: avoid-page !important;
}
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-signature-tail-bundle {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
}
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-signature-tail-bundle .report-lab-firma-grupo-inline {
    page-break-before: avoid !important;
    break-before: avoid-page !important;
}
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-signature-tail-bundle .report-pdf-grupo-cabecera,
body.pdf-layout-engine.pdf-pagination-flow-no-lone-signature.pdf-dompdf-download .report-signature-tail-bundle .report-segment-title {
    page-break-after: avoid !important;
    break-after: avoid-page !important;
}
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-signature-tail-bundle {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
}
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-signature-tail-bundle .report-lab-firma-grupo-inline {
    page-break-before: avoid !important;
    break-before: avoid-page !important;
}
/* ——— Impresión navegador: saltos explícitos según LayoutPlan (PDF usa flujo Dompdf) ——— */
@media print {
    body.report-browser-print.pdf-layout-engine .report-browser-print-plan-page-break:not(.is-suppressed) {
        display: block !important;
        width: 100% !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        line-height: 0 !important;
        font-size: 0 !important;
        overflow: hidden !important;
        clear: both !important;
        break-before: page !important;
        page-break-before: always !important;
        break-after: avoid !important;
        page-break-after: avoid !important;
    }
    body.report-browser-print.pdf-layout-engine .report-browser-print-plan-page-break.is-suppressed {
        display: none !important;
        break-before: auto !important;
        page-break-before: auto !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    body.report-browser-print.pdf-layout-engine .report-browser-print-plan-page-break:not(.is-suppressed) + .report-pdf-grupo-area-separator,
    body.report-browser-print.pdf-layout-engine .report-browser-print-plan-page-break:not(.is-suppressed) + .report-pdf-subgrupo-block {
        break-before: avoid !important;
        page-break-before: avoid !important;
    }
}

/* ——— MODE 3 y 4: área en página nueva + subgrupos íntegros ——— */
@media print {
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print .report-pdf-grupo-cabecera.report-cabecera-force-break-before,
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print .report-pdf-subgrupo-block.report-subgrupo-force-break-before,
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print .report-pdf-grupo-cabecera.report-cabecera-force-break-before,
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print .report-pdf-subgrupo-block.report-subgrupo-force-break-before {
        break-before: page !important;
        page-break-before: always !important;
    }
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print .report-segment-table-wrap.report-segment-force-break-before,
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print .report-refs-matrix-wrap.report-segment-force-break-before,
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print .report-segment-table-wrap.report-segment-force-break-before,
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print .report-refs-matrix-wrap.report-segment-force-break-before {
        break-before: page !important;
        page-break-before: always !important;
    }
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print .report-pdf-subgrupo-block,
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print .report-pdf-subgrupo-block {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print .report-pdf-subgrupo-block.report-subgrupo-keep-intact,
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print .report-pdf-subgrupo-block.report-subgrupo-keep-intact {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print .report-segment-table-wrap.report-segment-allow-split,
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print .report-refs-matrix-wrap.report-segment-allow-split,
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print .report-segment-table-wrap.report-segment-allow-split,
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print .report-refs-matrix-wrap.report-segment-allow-split {
        break-inside: auto !important;
        page-break-inside: auto !important;
    }
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print .report-segment-table-wrap:not(.report-segment-allow-split),
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print .report-refs-matrix-wrap:not(.report-segment-allow-split),
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print .report-segment-table-wrap:not(.report-segment-allow-split),
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print .report-refs-matrix-wrap:not(.report-segment-allow-split) {
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }
    body.pdf-layout-engine.pdf-pagination-area-hard-page-break.report-browser-print table.results tbody tr,
    body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.report-browser-print table.results tbody tr {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
    body.pdf-layout-engine.report-browser-print tr.report-results-thead-continuation-injected {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
}
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-pdf-grupo-cabecera.report-cabecera-force-break-before,
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-pdf-subgrupo-block.report-subgrupo-force-break-before,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-pdf-grupo-cabecera.report-cabecera-force-break-before,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-pdf-subgrupo-block.report-subgrupo-force-break-before {
    page-break-before: always !important;
    break-before: page !important;
}
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-pdf-grupo-area-separator,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-pdf-grupo-area-separator {
    page-break-after: avoid !important;
    break-after: avoid-page !important;
}
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-pdf-grupo-area-separator + .report-pdf-subgrupo-block,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-pdf-grupo-area-separator + .report-pdf-subgrupo-block {
    page-break-before: avoid !important;
    break-before: avoid-page !important;
}
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-segment-table-wrap.report-segment-force-break-before,
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-refs-matrix-wrap.report-segment-force-break-before,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-segment-table-wrap.report-segment-force-break-before,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-refs-matrix-wrap.report-segment-force-break-before {
    page-break-before: always !important;
    break-before: page !important;
}
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-pdf-subgrupo-block,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-pdf-subgrupo-block {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
}
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-pdf-subgrupo-block.report-cultivo-area-page-start,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-pdf-subgrupo-block.report-cultivo-area-page-start {
    page-break-inside: auto !important;
    break-inside: auto !important;
}
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-pdf-subgrupo-block.report-cultivo-area-page-start > .report-segment-table-wrap:not(.report-segment-allow-split),
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-pdf-subgrupo-block.report-cultivo-area-page-start > .report-cultivo-seccion .report-segment-table-wrap:not(.report-segment-allow-split) {
    page-break-inside: auto !important;
    break-inside: auto !important;
}
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-pdf-subgrupo-block.report-subgrupo-keep-intact,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-pdf-subgrupo-block.report-subgrupo-keep-intact {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
}
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-segment-table-wrap.report-segment-allow-split,
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-refs-matrix-wrap.report-segment-allow-split,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-segment-table-wrap.report-segment-allow-split,
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-refs-matrix-wrap.report-segment-allow-split {
    page-break-inside: auto !important;
    break-inside: auto !important;
}
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-segment-table-wrap:not(.report-segment-allow-split),
body.pdf-layout-engine.pdf-pagination-area-hard-page-break.pdf-dompdf-download .report-refs-matrix-wrap:not(.report-segment-allow-split),
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-segment-table-wrap:not(.report-segment-allow-split),
body.pdf-layout-engine.pdf-pagination-area-soft-fit-signature.pdf-dompdf-download .report-refs-matrix-wrap:not(.report-segment-allow-split) {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
}
<?php if ($__dompdfCssOnly):
    $css = (string) ob_get_clean();
    $lines = explode("\n", $css);
    $filtered = [];
    $mediaDepth = 0;
    foreach ($lines as $line) {
        if (preg_match('/@media\s+print\b/', $line)) {
            $mediaDepth++;
            continue;
        }
        if ($mediaDepth > 0) {
            if (preg_match('/^\s*\}\s*$/', $line)) {
                $mediaDepth--;
            }
            continue;
        }
        if (str_contains($line, 'report-browser-print')) {
            continue;
        }
        $filtered[] = $line;
    }
    echo implode("\n", $filtered);
endif; ?>
