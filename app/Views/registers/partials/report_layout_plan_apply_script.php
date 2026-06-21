<?php
/**
 * Aplica LayoutPlan pre-calculado en el navegador (sin recalcular paginación).
 *
 * @var \App\Services\ReportLayout\LayoutPlanApplier|null $report_layout_applier
 */
$layoutApplier = ($report_layout_applier ?? null) instanceof \App\Services\ReportLayout\LayoutPlanApplier
    ? $report_layout_applier
    : null;
if ($layoutApplier === null || ! $layoutApplier->isActive()) {
    return;
}
$clientPlan = $layoutApplier->exportForClient();
?>
<script>
(function() {
    var plan = <?= json_encode($clientPlan, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    window.reportLayoutPlan = plan;

    function applyClass(el, className) {
        if (!el || !className) {
            return;
        }
        className.split(/\s+/).forEach(function(cls) {
            if (cls) {
                el.classList.add(cls);
            }
        });
    }

    function applyPlanMarkers() {
        if (!plan || !Array.isArray(plan.placements)) {
            return;
        }
        plan.placements.forEach(function(placement) {
            if (!placement || !placement.nodeId) {
                return;
            }
            var blockEl = document.querySelector('[data-layout-block-id="' + placement.nodeId + '"]');
            if (!blockEl || !placement.markers) {
                return;
            }
            applyClass(blockEl, placement.markers.subgrupoClass || '');
            var cabecera = blockEl.querySelector('.report-pdf-grupo-cabecera');
            applyClass(cabecera, placement.markers.cabeceraClass || '');
            var wraps = blockEl.querySelectorAll('.report-segment-table-wrap, .report-refs-matrix-wrap');
            var segmentClasses = placement.markers.segmentClasses || [];
            if (Array.isArray(segmentClasses)) {
                segmentClasses.forEach(function(segClass, idx) {
                    if (wraps[idx]) {
                        applyClass(wraps[idx], segClass || '');
                    }
                });
            }
        });
    }

    function applyTotalPages() {
        if (window.reportPrintPagination && typeof window.reportPrintPagination.applyPaginationLineTotals === 'function') {
            window.reportPrintPagination.applyPaginationLineTotals();
        }
        var total = window.reportPrintPagination && typeof window.reportPrintPagination.estimateTotalPages === 'function'
            ? window.reportPrintPagination.estimateTotalPages()
            : parseInt(plan.totalPages, 10);
        if (!isFinite(total) || total < 1) {
            return;
        }
        document.querySelectorAll('.pdf-counter-pages').forEach(function(el) {
            el.textContent = String(total);
        });
        document.body.classList.add('js-total-pages-ready');
    }

    window.applyReportLayoutPlan = function() {
        applyPlanMarkers();
        applyTotalPages();
    };

    document.addEventListener('DOMContentLoaded', function() {
        window.applyReportLayoutPlan();
    });
})();
</script>
