<?php
/**
 * Paginación visual del preview viewreport (hojas apiladas + marca de agua y pie por hoja).
 *
 * @var array<string,mixed> $pdf_layout
 * @var float               $page_height_mm
 * @var float               $margin_top_mm
 * @var float               $margin_bottom_mm
 * @var float               $footer_reserve_mm
 * @var bool                $footer_enabled
 */
$pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$pageHeightMm = isset($page_height_mm) ? (float) $page_height_mm : 279.4;
$marginTopMm = isset($margin_top_mm) ? (float) $margin_top_mm : 15.0;
$marginBottomMm = isset($margin_bottom_mm) ? (float) $margin_bottom_mm : 15.0;
$footerReserveMm = isset($footer_reserve_mm) ? (float) $footer_reserve_mm : 22.0;
$footerEnabled = ! empty($footer_enabled);
?>
<?= view('registers/partials/report_print_pagination_metrics', [
    'page_height_mm'    => $pageHeightMm,
    'margin_top_mm'     => $marginTopMm,
    'margin_bottom_mm'  => $marginBottomMm,
    'footer_reserve_mm' => $footerReserveMm,
    'footer_enabled'    => $footerEnabled,
]) ?>
<script>
(function() {
    function measureFooterReserveMm() {
        if (!<?= $footerEnabled ? 'true' : 'false' ?>) {
            return 0;
        }
        var footer = document.querySelector('.viewreport-pdf-source .pdf-ft-block.footer-grid');
        if (!footer) {
            return <?= json_encode($footerReserveMm) ?>;
        }
        var mmToPx = window.reportPrintPagination ? window.reportPrintPagination.MM_TO_PX : (96 / 25.4);
        var heightMm = (footer.offsetHeight || footer.scrollHeight || 0) / mmToPx;
        if (!isFinite(heightMm) || heightMm <= 0) {
            return <?= json_encode($footerReserveMm) ?>;
        }
        return Math.max(10, Math.min(90, Math.ceil((heightMm + 4) * 10) / 10));
    }

    function normalizeWatermarkClone(wm) {
        if (!wm) {
            return;
        }
        wm.classList.add('viewreport-page-watermark');
        wm.style.position = 'absolute';
        wm.style.height = 'auto';
        wm.style.maxHeight = 'none';
        var inner = wm.querySelector('.pdf-watermark-inner');
        if (inner) {
            inner.style.height = '100%';
            inner.style.minHeight = '0';
        }
        var table = wm.querySelector('.pdf-watermark-table');
        if (table) {
            table.style.height = '100%';
        }
        var td = wm.querySelector('.pdf-watermark-td');
        if (td) {
            td.style.height = '100%';
        }
    }


    function packNodesIntoPages(measures, pageBodies, pageLimits) {
        var pageIdx = 0;
        var used = 0;
        measures.forEach(function(item) {
            var h = item.height;
            while (
                pageIdx < pageBodies.length - 1
                && used > 0
                && used + h > pageLimits[pageIdx]
            ) {
                pageIdx++;
                used = 0;
            }
            if (pageIdx >= pageBodies.length) {
                pageIdx = pageBodies.length - 1;
            }
            pageBodies[pageIdx].appendChild(item.node);
            used += h;
        });
    }

    function buildPageLimits(totalPages, boundaries, metrics) {
        var limits = [];
        for (var p = 0; p < totalPages; p++) {
            var start = p === 0 ? 0 : (boundaries[p - 1] || 0);
            var end = boundaries[p];
            if (!isFinite(end) || end <= start) {
                end = start + (metrics.nextPageContentPx || 0);
            }
            var span = end - start;
            if (!isFinite(span) || span <= 0) {
                span = metrics.nextPageContentPx || 900;
            }
            limits.push(span);
        }
        if (limits.length < 1) {
            limits.push(metrics.nextPageContentPx || 900);
        }
        return limits;
    }

    function buildViewreportPages() {
        var shell = document.querySelector('.viewreport-pdf-shell--paginated');
        var source = shell ? shell.querySelector('.viewreport-pdf-source') : null;
        var stack = source ? source.querySelector('.pdf-main-stack') : null;
        if (!shell || !source || !stack || !window.reportPrintPagination) {
            return null;
        }

        var footerReserveMm = measureFooterReserveMm();
        if (typeof window.updateReportPrintPageBreakMetrics === 'function') {
            window.updateReportPrintPageBreakMetrics({ footerReserveMm: footerReserveMm });
        }

        var metrics = window.reportPrintPagination.buildMetrics(stack);
        if (footerReserveMm > 0) {
            metrics.footerReserveMM = footerReserveMm;
            metrics.nextPageContentMM = metrics.pageHeightMM - metrics.marginTopMM - metrics.marginBottomMM - footerReserveMm;
            if (!isFinite(metrics.nextPageContentMM) || metrics.nextPageContentMM <= 0) {
                metrics.nextPageContentMM = 240;
            }
            metrics.firstPageContentMM = Math.max(0, metrics.nextPageContentMM - metrics.headerHeightMM);
            metrics.firstPageContentPx = window.reportPrintPagination.mmToPx(metrics.firstPageContentMM);
            metrics.nextPageContentPx = window.reportPrintPagination.mmToPx(metrics.nextPageContentMM);
            metrics.estimatedPages = window.reportPrintPagination.estimatePages(
                metrics.totalContentMM,
                metrics.firstPageContentMM,
                metrics.nextPageContentMM
            );
        }

        var boundarySet = window.reportPrintPagination.buildBoundaries(stack, metrics);
        var boundaries = boundarySet.boundaries || [];
        var totalPages = metrics.estimatedPages || 1;
        if (!isFinite(totalPages) || totalPages < 1) {
            totalPages = 1;
        }

        var wmTemplate = source.querySelector('.pdf-watermark-layer');
        var footerTemplate = source.querySelector('.pdf-ft-block.footer-grid');

        var pagesHost = shell.querySelector('.viewreport-pdf-pages');
        if (!pagesHost) {
            pagesHost = document.createElement('div');
            pagesHost.className = 'viewreport-pdf-pages';
            shell.insertBefore(pagesHost, source);
        }
        pagesHost.innerHTML = '';

        var pageBodies = [];
        for (var p = 0; p < totalPages; p++) {
            var pageEl = document.createElement('div');
            pageEl.className = 'viewreport-pdf-page';
            pageEl.setAttribute('data-page', String(p + 1));

            if (wmTemplate) {
                var wmClone = wmTemplate.cloneNode(true);
                normalizeWatermarkClone(wmClone);
                pageEl.appendChild(wmClone);
            }

            var bodyEl = document.createElement('div');
            bodyEl.className = 'viewreport-pdf-page-body';
            pageEl.appendChild(bodyEl);
            pageBodies.push(bodyEl);

            if (footerTemplate) {
                var ftClone = footerTemplate.cloneNode(true);
                ftClone.classList.add('viewreport-page-footer');
                ftClone.removeAttribute('id');
                pageEl.appendChild(ftClone);
            }

            pagesHost.appendChild(pageEl);
        }

        var movable = Array.prototype.slice.call(stack.children).filter(function(node) {
            return node.nodeType === 1;
        });

        var measures = movable.map(function(node) {
            return {
                node: node,
                top: node.offsetTop || 0,
                height: Math.max(node.offsetHeight || 0, node.scrollHeight || 0)
            };
        });

        var pageLimits = buildPageLimits(totalPages, boundaries, metrics);
        packNodesIntoPages(measures, pageBodies, pageLimits);

        if (wmTemplate) {
            wmTemplate.parentNode.removeChild(wmTemplate);
        }
        if (footerTemplate) {
            footerTemplate.parentNode.removeChild(footerTemplate);
        }

        shell.classList.add('viewreport-pdf-ready');
        return metrics;
    }

    function applyBrowserTotalPages(metrics) {
        var total = metrics && metrics.estimatedPages ? metrics.estimatedPages : 1;
        document.querySelectorAll('.pdf-counter-pages').forEach(function(el) {
            el.textContent = String(total);
        });
        document.body.classList.add('js-total-pages-ready');
    }

    function runViewreportPagination() {
        if (document.querySelector('.viewreport-pdf-shell--paginated.viewreport-pdf-ready')) {
            return null;
        }
        var metrics = buildViewreportPages();
        applyBrowserTotalPages(metrics);
        return metrics;
    }

    window.buildViewreportPages = buildViewreportPages;
    window.runViewreportPagination = runViewreportPagination;

    document.addEventListener('DOMContentLoaded', function() {
        runViewreportPagination();
    });
    window.addEventListener('load', function() {
        runViewreportPagination();
    });
})();
</script>
