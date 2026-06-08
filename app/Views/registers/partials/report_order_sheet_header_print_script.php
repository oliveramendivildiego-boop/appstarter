<?php
/**
 * Inyecta Paciente / No. Orden al final del contenido de cada hoja 2+ (impresión navegador).
 *
 * @var bool $order_sheet_header_enabled
 */
if (empty($order_sheet_header_enabled)) {
    return;
}
?>
<script>
(function() {
    function offsetTopWithinContainer(el, container) {
        var y = 0;
        var node = el;
        while (node && node !== container) {
            y += node.offsetTop || 0;
            node = node.offsetParent;
            if (!node) {
                return null;
            }
        }
        return node === container ? y : null;
    }

    function elementBottomWithinContainer(el, container) {
        var top = offsetTopWithinContainer(el, container);
        if (top === null) {
            return null;
        }
        return top + (el.offsetHeight || 0);
    }

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

    function findLastFlowElementBefore(container, maxBottomY) {
        var best = null;
        var bestBottom = -1;
        var nodes = container.querySelectorAll(
            '.header-grid, .patient-section, .pdf-notes-block, .pdf-lab-f-block, '
            + '.report-pdf-grupo-prueba, .report-pdf-grupo-cabecera, .report-segment-table-wrap, '
            + '.report-refs-matrix-wrap, .report-pdf-subgrupo-block, table.results'
        );

        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            if (el.classList && el.classList.contains('pdf-order-sheet-header-injected')) {
                continue;
            }
            var bottom = elementBottomWithinContainer(el, container);
            if (bottom === null || bottom > maxBottomY + 1) {
                continue;
            }
            if (bottom > bestBottom) {
                bestBottom = bottom;
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

    function estimatePrintPages(container) {
        if (!window.reportPrintPagination || typeof window.reportPrintPagination.buildMetrics !== 'function') {
            return 1;
        }
        var metrics = window.reportPrintPagination.buildMetrics(container);
        if (!metrics) {
            return 1;
        }
        if (isFinite(metrics.estimatedPages) && metrics.estimatedPages >= 2) {
            return metrics.estimatedPages;
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

        clearInjectedOrderSheetHeaders();

        var container = window.reportPrintPagination.getPrintContainer();
        if (!container) {
            return;
        }

        var totalPages = estimatePrintPages(container);
        if (totalPages < 2) {
            return;
        }

        var metrics = window.reportPrintPagination.buildMetrics(container);
        var boundarySet = window.reportPrintPagination.buildBoundaries(container, metrics);
        var boundaries = boundarySet && boundarySet.boundaries ? boundarySet.boundaries : [];
        if (!boundaries.length) {
            return;
        }

        var maxBottom = container.scrollHeight || 0;
        var pageNum;
        for (pageNum = 2; pageNum <= totalPages; pageNum++) {
            var pageEndY = pageNum - 1 < boundaries.length
                ? boundaries[pageNum - 1]
                : maxBottom;
            if (pageNum === totalPages) {
                pageEndY = Math.min(pageEndY, maxBottom);
            }

            var anchor = findLastFlowElementBefore(container, pageEndY);
            if (!anchor || !anchor.parentNode) {
                continue;
            }

            var node = buildOrderSheetHeaderNode(tpl);
            if (anchor.nextSibling) {
                anchor.parentNode.insertBefore(node, anchor.nextSibling);
            } else {
                anchor.parentNode.appendChild(node);
            }
        }
    };
})();
</script>
