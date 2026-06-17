<?php
/**
 * Cabecera Paciente / No. Orden en impresión navegador (hojas 2+), en flujo del documento.
 *
 * @var bool $order_sheet_header_enabled
 */
if (empty($order_sheet_header_enabled)) {
    return;
}
?>
<script>
(function() {
    var FLOW_ANCHOR_SELECTOR = [
        '.report-pdf-grupo-prueba',
        '.report-segment-table-wrap',
        '.report-refs-matrix-wrap',
        '.report-lab-firma-grupo-inline',
        '.pdf-notes-block',
        '.lab-firmas-pdf-block'
    ].join(', ');

    function isOrderSheetArtifact(node) {
        if (!node || !node.classList) {
            return false;
        }
        return node.classList.contains('pdf-order-sheet-header-injected')
            || node.classList.contains('report-order-sheet-page-leader')
            || node.classList.contains('pdf-order-sheet-header-print-fixed')
            || node.classList.contains('pdf-osh-page1-cover');
    }

    function topWithinContainer(el, container) {
        if (!el || !container) {
            return 0;
        }
        var top = 0;
        var node = el;
        while (node && node !== container) {
            top += node.offsetTop || 0;
            node = node.offsetParent;
            if (node && !container.contains(node)) {
                var elRect = el.getBoundingClientRect();
                var contRect = container.getBoundingClientRect();
                return elRect.top - contRect.top + (container.scrollTop || 0);
            }
        }
        return top;
    }

    function buildOrderSheetBandNode(tpl) {
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

    function buildPageLeaderNode() {
        var leader = document.createElement('div');
        leader.className = 'report-order-sheet-page-leader';
        leader.setAttribute('aria-hidden', 'true');
        return leader;
    }

    function clearOrderSheetHeaderPrintArtifacts() {
        document.querySelectorAll(
            '.pdf-order-sheet-header-injected, .report-order-sheet-page-leader, '
            + '.pdf-order-sheet-header-print-fixed, .pdf-osh-page1-cover'
        ).forEach(function(node) {
            node.remove();
        });
        document.body.classList.remove('js-order-sheet-header-print', 'js-order-sheet-header-in-flow');
    }

    function nodeStartsNewPrintedPage(anchor) {
        if (!anchor || !anchor.classList) {
            return false;
        }
        var cls = anchor.classList;
        if (cls.contains('report-segment-force-break-before')
            || cls.contains('report-pdf-grupo-prueba-force-break-before')
            || cls.contains('report-cabecera-force-break-before')
            || cls.contains('report-subgrupo-force-break-before')) {
            return true;
        }
        var prev = anchor.previousElementSibling;
        while (prev) {
            if (isOrderSheetArtifact(prev)) {
                prev = prev.previousElementSibling;
                continue;
            }
            if (prev.classList.contains('report-pdf-grupo-area-page-leader')
                || prev.classList.contains('report-lab-firma-page-leader')
                || prev.classList.contains('report-order-sheet-page-leader')) {
                return true;
            }
            break;
        }
        return false;
    }

    function alreadyHasBandBefore(anchor) {
        var prev = anchor.previousElementSibling;
        while (prev) {
            if (prev.classList && prev.classList.contains('pdf-order-sheet-header-injected')) {
                return true;
            }
            if (prev.classList && prev.classList.contains('report-order-sheet-page-leader')) {
                prev = prev.previousElementSibling;
                continue;
            }
            break;
        }
        return false;
    }

    function findFlowAnchorAtBoundary(container, boundaryY) {
        var tolerance = 4;
        var best = null;
        var bestTop = Infinity;

        container.querySelectorAll(FLOW_ANCHOR_SELECTOR).forEach(function(node) {
            if (isOrderSheetArtifact(node)) {
                return;
            }
            var top = topWithinContainer(node, container);
            if (top + 1 < boundaryY - tolerance) {
                return;
            }
            if (top < bestTop) {
                bestTop = top;
                best = node;
            }
        });

        return best;
    }

    function insertOrderSheetBefore(anchor, tpl) {
        if (!anchor || !anchor.parentNode || alreadyHasBandBefore(anchor)) {
            return;
        }

        var parent = anchor.parentNode;
        if (!nodeStartsNewPrintedPage(anchor)) {
            parent.insertBefore(buildPageLeaderNode(), anchor);
        }
        parent.insertBefore(buildOrderSheetBandNode(tpl), anchor);
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

        var boundarySet = window.reportPrintPagination.buildBoundaries(container, metrics);
        var boundaries = boundarySet && boundarySet.boundaries ? boundarySet.boundaries : [];
        if (boundaries.length < 1) {
            return;
        }

        document.body.classList.add('js-order-sheet-header-in-flow');

        for (var pageIdx = boundaries.length - 1; pageIdx >= 1; pageIdx--) {
            var boundaryY = boundaries[pageIdx - 1];
            if (!isFinite(boundaryY) || boundaryY <= 0) {
                continue;
            }
            var anchor = findFlowAnchorAtBoundary(container, boundaryY);
            if (!anchor) {
                continue;
            }
            insertOrderSheetBefore(anchor, tpl);
        }

        if (typeof window.reportPrintPagination.buildMetrics === 'function') {
            window.reportPrintPagination.buildMetrics(container);
        }
    };

    window.addEventListener('afterprint', clearOrderSheetHeaderPrintArtifacts);
})();
</script>
