<?php
/**
 * Inyecta cabecera Paciente / No. Orden al inicio de cada hoja desde la 2.ª (impresión navegador).
 *
 * @var bool $order_sheet_header_enabled
 */
if (empty($order_sheet_header_enabled)) {
    return;
}
?>
<script>
(function() {
    function buildOrderSheetHeaderNode(tpl) {
        var wrap = document.createElement('div');
        wrap.className = 'pdf-order-sheet-header-injected';

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

    function findFlowInsertPoint(container, targetY) {
        var best = null;
        var nodes = container.querySelectorAll(
            '.header-grid, .patient-section, .pdf-notes-block, .pdf-lab-f-block, '
            + '.report-pdf-grupo-prueba, .report-pdf-grupo-cabecera, .report-segment-table-wrap, '
            + '.report-refs-matrix-wrap, .report-pdf-subgrupo-block'
        );

        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            if (el.classList && el.classList.contains('pdf-order-sheet-header-injected')) {
                continue;
            }
            var top = el.offsetTop || 0;
            if (top + (el.offsetHeight || 0) <= targetY + 1) {
                continue;
            }
            if (!best || top < best.offsetTop) {
                best = el;
            }
        }

        return best;
    }

    function clearInjectedOrderSheetHeaders() {
        document.querySelectorAll('.pdf-order-sheet-header-injected').forEach(function(node) {
            node.remove();
        });
    }

    window.injectOrderSheetHeadersFromPageTwo = function() {
        var tpl = document.getElementById('pdf-order-sheet-header-template');
        if (!tpl || !window.reportPrintPagination) {
            return;
        }

        clearInjectedOrderSheetHeaders();

        var container = window.reportPrintPagination.getPrintContainer();
        if (!container) {
            return;
        }

        var metrics = window.reportPrintPagination.buildMetrics(container);
        if (!metrics || !isFinite(metrics.estimatedPages) || metrics.estimatedPages < 2) {
            return;
        }

        var boundarySet = window.reportPrintPagination.buildBoundaries(container, metrics);
        var boundaries = boundarySet && boundarySet.boundaries ? boundarySet.boundaries : [];
        if (!boundaries.length) {
            return;
        }

        for (var p = 0; p < boundaries.length; p++) {
            var anchor = findFlowInsertPoint(container, boundaries[p]);
            if (!anchor || !anchor.parentNode) {
                continue;
            }
            anchor.parentNode.insertBefore(buildOrderSheetHeaderNode(tpl), anchor);
        }
    };
})();
</script>
