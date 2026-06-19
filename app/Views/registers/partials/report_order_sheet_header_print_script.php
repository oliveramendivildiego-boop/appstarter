<?php
/**
 * Banda Paciente / No. Orden en impresión navegador, fija encima del pie (en todas las hojas).
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
            '.pdf-order-sheet-header-print-fixed, .pdf-order-sheet-header-injected, '
            + '.pdf-order-sheet-footer-band-standalone, '
            + '.report-order-sheet-page-leader, .pdf-osh-page1-cover'
        ).forEach(function(node) {
            node.remove();
        });
        document.body.classList.remove(
            'js-order-sheet-header-print',
            'js-order-sheet-header-in-flow',
            'js-order-sheet-footer-band',
            'js-order-sheet-footer-band-standalone'
        );
    }

    window.injectOrderSheetHeadersFromPageTwo = function() {
        var tpl = document.getElementById('pdf-order-sheet-header-template');
        if (!tpl) {
            return false;
        }

        clearOrderSheetHeaderPrintArtifacts();

        var footer = document.querySelector('.pdf-ft-block.footer-grid');
        if (footer && footer.querySelector('.pdf-order-sheet-footer-band')) {
            document.body.classList.add('js-order-sheet-footer-band');
            if (typeof window.syncReportPrintLayoutMetrics === 'function') {
                window.syncReportPrintLayoutMetrics();
            }
            return true;
        }

        var band = buildOrderSheetBandNode(tpl);

        if (footer) {
            footer.insertBefore(band, footer.firstChild);
        } else {
            band.classList.add('pdf-order-sheet-footer-band-standalone');
            document.body.appendChild(band);
            document.body.classList.add('js-order-sheet-footer-band-standalone');
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
