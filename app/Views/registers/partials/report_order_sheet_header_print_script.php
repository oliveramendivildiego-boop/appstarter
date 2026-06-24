<?php
/**
 * Banda Paciente / No. Orden en impresión navegador (desde hoja 2, en flujo al inicio de cada hoja).
 *
 * @var bool $order_sheet_header_enabled
 */
if (empty($order_sheet_header_enabled)) {
    return;
}
?>
<script>
(function() {
    function buildOrderSheetBandNode(tpl, extraClass) {
        var wrap = document.createElement('div');
        wrap.className = 'pdf-order-sheet-footer-band pdf-order-sheet-header-injected'
            + (extraClass ? ' ' + extraClass : '');

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
        document.querySelectorAll('.pdf-ft-block.footer-grid .pdf-order-sheet-footer-band').forEach(function(node) {
            node.style.removeProperty('display');
        });
        document.body.classList.remove(
            'js-order-sheet-header-print',
            'js-order-sheet-header-in-flow',
            'js-order-sheet-footer-band',
            'js-order-sheet-footer-band-standalone'
        );
    }

    function hideEmbeddedFooterBand(footer) {
        if (!footer) {
            return;
        }
        footer.querySelectorAll('.pdf-order-sheet-footer-band').forEach(function(node) {
            node.style.setProperty('display', 'none', 'important');
        });
    }

    function firstElementAtOrAfter(root, minTopPx) {
        var selector = [
            '.report-grupo-inter-page-break',
            '.report-pdf-grupo-prueba',
            '.report-pdf-subgrupo-block',
            '.report-pdf-grupo-cabecera',
            '.pdf-hg-block',
            '.pdf-pd-block',
            '.lab-firmas-pdf-block',
            '.pdf-notes-block'
        ].join(', ');
        var candidates = root.querySelectorAll(selector);
        var best = null;
        var bestTop = Infinity;
        for (var i = 0; i < candidates.length; i++) {
            var el = candidates[i];
            var top = el.offsetTop || 0;
            if (top + 1 < minTopPx) {
                continue;
            }
            if (top < bestTop) {
                bestTop = top;
                best = el;
            }
        }
        return best;
    }

    function injectBandsInFlow(tpl, container) {
        if (!window.reportPrintPagination || typeof window.reportPrintPagination.buildBoundaries !== 'function') {
            return false;
        }

        var boundarySet = window.reportPrintPagination.buildBoundaries(container);
        var boundaries = boundarySet.boundaries || [];
        if (boundaries.length < 1) {
            return false;
        }

        for (var i = boundaries.length - 1; i >= 0; i--) {
            var pageStartPx = boundaries[i];
            if (!isFinite(pageStartPx) || pageStartPx <= 0) {
                continue;
            }
            var anchor = firstElementAtOrAfter(container, pageStartPx);
            if (!anchor) {
                continue;
            }
            container.insertBefore(buildOrderSheetBandNode(tpl), anchor);
        }

        return true;
    }

    window.injectOrderSheetHeadersFromPageTwo = function() {
        var tpl = document.getElementById('pdf-order-sheet-header-template');
        var footer = document.querySelector('.pdf-ft-block.footer-grid');
        var footerBand = footer ? footer.querySelector('.pdf-order-sheet-footer-band') : null;

        if (!tpl && footerBand) {
            tpl = document.createElement('div');
            var patientCell = footerBand.querySelector('.pdf-order-sheet-header-patient');
            var orderCell = footerBand.querySelector('.pdf-order-sheet-header-orden');
            tpl.setAttribute('data-patient-line', patientCell ? (patientCell.textContent || '').trim() : '');
            tpl.setAttribute('data-order-line', orderCell ? (orderCell.textContent || '').trim() : '');
        }

        if (!tpl) {
            return false;
        }

        clearOrderSheetHeaderPrintArtifacts();
        hideEmbeddedFooterBand(footer);

        var container = document.querySelector('.pdf-main-stack') || document.body;
        if (!injectBandsInFlow(tpl, container)) {
            return false;
        }

        document.body.classList.add('js-order-sheet-header-in-flow');

        if (typeof window.syncReportPrintLayoutMetrics === 'function') {
            window.syncReportPrintLayoutMetrics();
        }

        return true;
    };

    window.addEventListener('afterprint', clearOrderSheetHeaderPrintArtifacts);
})();
</script>
