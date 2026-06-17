<?php
/**
 * Banda Paciente / No. Orden en impresión navegador (hojas 2+), fija encima del pie (parte del footer).
 *
 * @var bool $order_sheet_header_enabled
 */
if (empty($order_sheet_header_enabled)) {
    return;
}
?>
<script>
(function() {
    function buildOrderSheetBandNode(tpl) {
        var wrap = document.createElement('div');
        wrap.className = 'pdf-order-sheet-footer-band pdf-order-sheet-header-print-fixed';
        wrap.setAttribute('aria-hidden', 'false');

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
        document.querySelectorAll(
            '.pdf-order-sheet-footer-band, .pdf-order-sheet-header-injected, '
            + '.report-order-sheet-page-leader, .pdf-osh-page1-cover'
        ).forEach(function(node) {
            node.remove();
        });
        document.body.classList.remove(
            'js-order-sheet-header-print',
            'js-order-sheet-header-in-flow',
            'js-order-sheet-footer-band'
        );
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
            return false;
        }

        clearOrderSheetHeaderPrintArtifacts();

        var container = window.reportPrintPagination.getPrintContainer();
        if (!container) {
            return false;
        }

        var metrics = window.reportPrintPagination.buildMetrics(container);
        if (!metrics) {
            return false;
        }

        if (estimatePrintPages(container, metrics) < 2) {
            return false;
        }

        var footer = document.querySelector('.pdf-ft-block.footer-grid');
        var band = buildOrderSheetBandNode(tpl);

        if (footer) {
            footer.insertBefore(band, footer.firstChild);
        } else {
            document.body.appendChild(band);
        }

        document.body.classList.add('js-order-sheet-footer-band');

        if (typeof window.syncReportPrintLayoutMetrics === 'function') {
            window.syncReportPrintLayoutMetrics();
        }

        return true;
    };

    window.addEventListener('afterprint', clearOrderSheetHeaderPrintArtifacts);
})();
</script>
