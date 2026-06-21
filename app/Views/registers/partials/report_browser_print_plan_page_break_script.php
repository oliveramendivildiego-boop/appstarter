<?php
/**
 * Ajusta saltos planificados en impresión navegador para evitar hojas en blanco
 * cuando el contenido ya quedó al inicio de la página correcta.
 */
?>
<script>
(function() {
    function resolveTargetAfterLeader(leader) {
        var node = leader.nextElementSibling;
        while (node && node.classList && node.classList.contains('report-browser-print-plan-page-break')) {
            node = node.nextElementSibling;
        }
        return node;
    }

    function pageStartPx(pageIndex, boundaries, headerPx) {
        if (pageIndex <= 0) {
            return 0;
        }
        if (!boundaries || boundaries.length < pageIndex) {
            return 0;
        }
        return boundaries[pageIndex - 1] || 0;
    }

    window.syncBrowserPrintPlanPageBreaks = function() {
        if (!document.body.classList.contains('report-browser-print')) {
            return;
        }
        if (!document.body.classList.contains('pdf-layout-engine')) {
            return;
        }
        var leaders = document.querySelectorAll('.report-browser-print-plan-page-break');
        if (!leaders.length) {
            return;
        }
        if (!window.reportPrintPagination || typeof window.reportPrintPagination.buildBoundaries !== 'function') {
            return;
        }

        var container = window.reportPrintPagination.getPrintContainer();
        var boundarySet = window.reportPrintPagination.buildBoundaries(container);
        var boundaries = boundarySet.boundaries || [];
        var containerTop = container.offsetTop || 0;
        var tolerancePx = 3;

        leaders.forEach(function(leader) {
            var target = resolveTargetAfterLeader(leader);
            if (!target) {
                leader.classList.add('is-suppressed');
                return;
            }

            var planPage = parseInt(leader.getAttribute('data-plan-page'), 10);
            if (!isFinite(planPage) || planPage < 0) {
                planPage = 0;
            }

            var topPx = (target.offsetTop || 0) - containerTop;
            var expectedStart = pageStartPx(planPage, boundaries, boundarySet.metrics ? boundarySet.metrics.headerHeightPx : 0);

            if (topPx >= expectedStart - tolerancePx) {
                leader.classList.add('is-suppressed');
            } else {
                leader.classList.remove('is-suppressed');
            }
        });
    };
})();
</script>
