<?php
/**
 * Métricas y utilidades de paginación para impresión directa (navegador).
 * Mide alturas reales en el DOM y calcula espacio disponible por hoja.
 *
 * @var float  $page_height_mm
 * @var float  $margin_top_mm
 * @var float  $margin_bottom_mm
 * @var float  $footer_reserve_mm
 * @var bool   $footer_enabled
 */
$pageHeightMm = isset($page_height_mm) ? (float) $page_height_mm : 279.4;
$marginTopMm = isset($margin_top_mm) ? (float) $margin_top_mm : 15.0;
$marginBottomMm = isset($margin_bottom_mm) ? (float) $margin_bottom_mm : 15.0;
$defaultFooterReserveMm = isset($footer_reserve_mm) ? (float) $footer_reserve_mm : 22.0;
$footerEnabled = ! empty($footer_enabled);
?>
<script>
(function() {
    var cfg = {
        pageHeightMm: <?= json_encode($pageHeightMm) ?>,
        marginTopMm: <?= json_encode($marginTopMm) ?>,
        marginBottomMm: <?= json_encode($marginBottomMm) ?>,
        defaultFooterReserveMm: <?= json_encode($defaultFooterReserveMm) ?>,
        footerEnabled: <?= $footerEnabled ? 'true' : 'false' ?>
    };

    var MM_TO_PX = 96 / 25.4;

    function pxToMm(px) {
        return px / MM_TO_PX;
    }

    function mmToPx(mm) {
        return mm * MM_TO_PX;
    }

    function roundMm(mm) {
        return Math.round(mm * 100) / 100;
    }

    function getPrintContainer() {
        return document.querySelector('.pdf-main-stack') || document.body;
    }

    function measureHeaderHeightPx(container) {
        var root = container || getPrintContainer();
        var firstGrupo = root.querySelector('.report-pdf-grupo-prueba');
        if (firstGrupo) {
            return Math.max(0, firstGrupo.offsetTop || 0);
        }
        var footer = root.querySelector('.pdf-ft-block.footer-grid');
        if (footer) {
            return Math.max(0, footer.offsetTop || 0);
        }
        return 0;
    }

    function measureFooterHeightPx() {
        if (!cfg.footerEnabled) {
            return 0;
        }
        var footer = document.querySelector('.pdf-ft-block.footer-grid');
        if (!footer) {
            return 0;
        }
        return Math.max(0, footer.offsetHeight || footer.scrollHeight || 0);
    }

    function resolveFooterReserveMm() {
        if (!cfg.footerEnabled) {
            return 0;
        }
        if (typeof window.measureReportPrintFooterReserveMm === 'function') {
            return window.measureReportPrintFooterReserveMm();
        }
        var raw = getComputedStyle(document.documentElement).getPropertyValue('--print-footer-reserve-mm');
        var parsed = parseFloat(raw);
        if (isFinite(parsed) && parsed > 0) {
            return parsed;
        }
        return cfg.defaultFooterReserveMm;
    }

    /**
     * Reserva de pie para paginación: altura medida + colchón (sin duplicar margen inferior).
     * El margen inferior de plantilla ya se descuenta aparte en nextPageContentMm.
     */
    function buildReportPrintPaginationMetrics(container) {
        var root = container || getPrintContainer();
        if (typeof window.syncReportPrintLayoutMetrics === 'function') {
            window.syncReportPrintLayoutMetrics();
        }

        var footerReserveMm = resolveFooterReserveMm();
        var footerHeightMm = roundMm(pxToMm(measureFooterHeightPx()));
        var headerHeightMm = roundMm(pxToMm(measureHeaderHeightPx(root)));

        var nextPageContentMm = cfg.pageHeightMm - cfg.marginTopMm - cfg.marginBottomMm - footerReserveMm;
        if (!isFinite(nextPageContentMm) || nextPageContentMm <= 0) {
            nextPageContentMm = 240;
        }
        nextPageContentMm = roundMm(nextPageContentMm);

        var firstPageContentMm = roundMm(Math.max(0, nextPageContentMm - headerHeightMm));

        var totalContentPx = root.scrollHeight || 0;
        var totalContentMm = roundMm(pxToMm(totalContentPx));

        var estimatedPages = estimatePagesFromMetrics(totalContentMm, firstPageContentMm, nextPageContentMm);

        var metrics = {
            pageHeightMM: roundMm(cfg.pageHeightMm),
            marginTopMM: roundMm(cfg.marginTopMm),
            marginBottomMM: roundMm(cfg.marginBottomMm),
            headerHeightMM: headerHeightMm,
            footerHeightMM: footerHeightMm,
            footerReserveMM: roundMm(footerReserveMm),
            firstPageContentMM: firstPageContentMm,
            nextPageContentMM: nextPageContentMm,
            totalContentMM: totalContentMm,
            estimatedPages: estimatedPages,
            headerHeightPx: measureHeaderHeightPx(root),
            firstPageContentPx: mmToPx(firstPageContentMm),
            nextPageContentPx: mmToPx(nextPageContentMm)
        };

        console.log('[report-print-pagination]', {
            pageHeightMM: metrics.pageHeightMM,
            marginTopMM: metrics.marginTopMM,
            marginBottomMM: metrics.marginBottomMM,
            headerHeightMM: metrics.headerHeightMM,
            footerHeightMM: metrics.footerHeightMM,
            firstPageContentMM: metrics.firstPageContentMM,
            nextPageContentMM: metrics.nextPageContentMM,
            totalContentMM: metrics.totalContentMM,
            estimatedPages: metrics.estimatedPages
        });

        return metrics;
    }

    function estimatePagesFromMetrics(totalContentMm, firstPageContentMm, nextPageContentMm) {
        if (!isFinite(totalContentMm) || totalContentMm <= 0) {
            return 1;
        }
        if (!isFinite(nextPageContentMm) || nextPageContentMm <= 0) {
            return 1;
        }
        // La hoja 1 admite header + resultados = nextPageContentMm en coordenadas del flujo.
        if (totalContentMm <= nextPageContentMm) {
            return 1;
        }
        var remaining = totalContentMm - nextPageContentMm;
        var extra = Math.ceil(remaining / nextPageContentMm);
        return 1 + extra;
    }

    /**
     * Límites acumulados del flujo (coordenadas px desde el inicio de .pdf-main-stack).
     * Hoja 1 termina tras header + firstPageContent; las siguientes avanzan nextPageContent.
     */
    function buildVariablePageBoundaries(container, metrics) {
        var root = container || getPrintContainer();
        var m = metrics || buildReportPrintPaginationMetrics(root);
        var maxBottom = root.scrollHeight || 0;
        var headerPx = m.headerHeightPx || 0;
        var firstSlicePx = m.firstPageContentPx || 0;
        var nextSlicePx = m.nextPageContentPx || firstSlicePx;

        if (!isFinite(firstSlicePx) || firstSlicePx <= 0) {
            firstSlicePx = mmToPx(240);
        }
        if (!isFinite(nextSlicePx) || nextSlicePx <= 0) {
            nextSlicePx = firstSlicePx;
        }

        var boundaries = [];
        var pos = headerPx + firstSlicePx;
        if (!isFinite(pos) || pos <= 0) {
            pos = firstSlicePx;
        }
        boundaries.push(pos);

        while (pos < maxBottom + nextSlicePx) {
            pos += nextSlicePx;
            boundaries.push(pos);
        }

        if (boundaries.length < 1) {
            boundaries.push(firstSlicePx);
        }

        return {
            boundaries: boundaries,
            metrics: m
        };
    }

    function remainingOnVariablePage(topPx, boundarySet) {
        var boundaries = boundarySet.boundaries || [];
        if (!isFinite(topPx) || topPx < 0) {
            topPx = 0;
        }
        for (var i = 0; i < boundaries.length; i++) {
            var pageStart = i === 0 ? 0 : boundaries[i - 1];
            var pageEnd = boundaries[i];
            if (topPx >= pageStart && topPx < pageEnd) {
                return pageEnd - topPx;
            }
        }
        var lastEnd = boundaries[boundaries.length - 1] || 0;
        if (topPx >= lastEnd) {
            var nextSlice = boundarySet.metrics ? boundarySet.metrics.nextPageContentPx : 0;
            if (!isFinite(nextSlice) || nextSlice <= 0) {
                nextSlice = lastEnd;
            }
            var overflow = topPx - lastEnd;
            var posOnPage = overflow % nextSlice;
            return nextSlice - posOnPage;
        }
        return boundarySet.metrics ? boundarySet.metrics.nextPageContentPx : 0;
    }

    window.reportPrintPagination = {
        MM_TO_PX: MM_TO_PX,
        pxToMm: pxToMm,
        mmToPx: mmToPx,
        getPrintContainer: getPrintContainer,
        measureHeaderHeightPx: measureHeaderHeightPx,
        measureFooterHeightPx: measureFooterHeightPx,
        buildMetrics: buildReportPrintPaginationMetrics,
        buildBoundaries: buildVariablePageBoundaries,
        remainingOnPage: remainingOnVariablePage,
        estimatePages: estimatePagesFromMetrics
    };
})();
</script>
