<?php
/**
 * Cabecera Paciente / No. Orden en impresión navegador (hojas 2+), fija encima del pie.
 *
 * @var bool $order_sheet_header_enabled
 */
if (empty($order_sheet_header_enabled)) {
    return;
}

$orderSheetGapMm = \App\Services\ReportPdfLayoutService::ORDER_SHEET_HEADER_GAP_ABOVE_FOOTER_MM;
$orderSheetBandMm = \App\Services\ReportPdfLayoutService::ORDER_SHEET_HEADER_HEIGHT_MM;
?>
<script>
(function() {
    var ORDER_SHEET_GAP_MM = <?= json_encode((float) $orderSheetGapMm) ?>;
    var ORDER_SHEET_BAND_MM = <?= json_encode((float) $orderSheetBandMm) ?>;

    function buildOrderSheetHeaderNode(tpl) {
        var wrap = document.createElement('div');
        wrap.className = 'pdf-order-sheet-header-print-fixed';

        var table = document.createElement('table');
        table.className = 'pdf-order-sheet-header';
        table.setAttribute('width', '100%');
        table.setAttribute('cellpadding', '0');
        table.setAttribute('cellspacing', '0');

        var row = document.createElement('tr');

        var tdPatient = document.createElement('td');
        tdPatient.className = 'pdf-order-sheet-header-patient';
        tdPatient.setAttribute('align', 'left');
        tdPatient.textContent = tpl.getAttribute('data-patient-line') || '';

        var tdOrder = document.createElement('td');
        tdOrder.className = 'pdf-order-sheet-header-orden';
        tdOrder.setAttribute('align', 'right');
        tdOrder.textContent = tpl.getAttribute('data-order-line') || '';

        row.appendChild(tdPatient);
        row.appendChild(tdOrder);
        table.appendChild(row);
        wrap.appendChild(table);

        return wrap;
    }

    function clearOrderSheetHeaderPrintArtifacts() {
        document.querySelectorAll('.pdf-order-sheet-header-print-fixed, .pdf-osh-page1-cover').forEach(function(node) {
            node.remove();
        });
        document.body.classList.remove('js-order-sheet-header-print');
    }

    function estimatePrintPages(container, metrics) {
        if (metrics && isFinite(metrics.estimatedPages) && metrics.estimatedPages >= 2) {
            return metrics.estimatedPages;
        }
        if (!metrics) {
            return 1;
        }
        var totalPx = container.scrollHeight || 0;
        var slicePx = metrics.nextPageContentPx || metrics.firstPageContentPx || 0;
        if (slicePx > 0 && totalPx > slicePx * 1.08) {
            return Math.max(2, Math.ceil(totalPx / slicePx));
        }
        return 1;
    }

    window.injectOrderSheetHeadersFromPageTwo = function() {
        var tpl = document.getElementById('pdf-order-sheet-header-template');
        if (!tpl || !window.reportPrintPagination) {
            return;
        }

        clearOrderSheetHeaderPrintArtifacts();

        var container = window.reportPrintPagination.getPrintContainer();
        if (!container) {
            return;
        }

        var metrics = window.reportPrintPagination.buildMetrics(container);
        if (!metrics) {
            return;
        }

        var totalPages = estimatePrintPages(container, metrics);
        if (totalPages < 2) {
            return;
        }

        document.body.classList.add('js-order-sheet-header-print');
        document.body.appendChild(buildOrderSheetHeaderNode(tpl));

        if (typeof window.syncReportPrintLayoutMetrics === 'function') {
            window.syncReportPrintLayoutMetrics();
        }
        if (typeof window.reportPrintPagination.buildMetrics === 'function') {
            window.reportPrintPagination.buildMetrics(container);
        }
    };

    window.addEventListener('afterprint', clearOrderSheetHeaderPrintArtifacts);
})();
</script>
