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
 * @var bool   $order_sheet_header_enabled
 * @var float  $order_sheet_band_default_mm
 */
$pageHeightMm = isset($page_height_mm) ? (float) $page_height_mm : 279.4;
$marginTopMm = isset($margin_top_mm) ? (float) $margin_top_mm : 15.0;
$marginBottomMm = isset($margin_bottom_mm) ? (float) $margin_bottom_mm : 15.0;
$defaultFooterReserveMm = isset($footer_reserve_mm) ? (float) $footer_reserve_mm : 22.0;
$footerEnabled = ! empty($footer_enabled);
$orderSheetHeaderEnabled = ! empty($order_sheet_header_enabled);
$orderSheetBandDefaultMm = isset($order_sheet_band_default_mm)
    ? (float) $order_sheet_band_default_mm
    : \App\Services\ReportPdfLayoutService::orderSheetHeaderPaginationReserveMm();
$orderSheetBandMinMm = \App\Services\ReportPdfLayoutService::ORDER_SHEET_HEADER_HEIGHT_MM;
$orderSheetBufferMm = 0.5;
?>
<script>
(function() {
    var cfg = {
        pageHeightMm: <?= json_encode($pageHeightMm) ?>,
        marginTopMm: <?= json_encode($marginTopMm) ?>,
        marginBottomMm: <?= json_encode($marginBottomMm) ?>,
        defaultFooterReserveMm: <?= json_encode($defaultFooterReserveMm) ?>,
        footerEnabled: <?= $footerEnabled ? 'true' : 'false' ?>,
        orderSheetHeaderEnabled: <?= $orderSheetHeaderEnabled ? 'true' : 'false' ?>,
        orderSheetBandDefaultMm: <?= json_encode($orderSheetBandDefaultMm) ?>,
        orderSheetBandMinMm: <?= json_encode((float) $orderSheetBandMinMm) ?>,
        orderSheetBufferMm: <?= json_encode((float) $orderSheetBufferMm) ?>
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

    function resolveOrderSheetBandReserveMm() {
        if (!cfg.orderSheetHeaderEnabled) {
            return 0;
        }

        var bandNode = document.querySelector('.pdf-order-sheet-header-injected .pdf-order-sheet-header')
            || document.querySelector('.pdf-order-sheet-header-injected');
        if (bandNode) {
            var measuredMm = pxToMm(bandNode.offsetHeight || bandNode.scrollHeight || 0);
            if (isFinite(measuredMm) && measuredMm > 0) {
                return roundMm(Math.max(cfg.orderSheetBandMinMm, measuredMm + cfg.orderSheetBufferMm));
            }
        }

        return roundMm(cfg.orderSheetBandDefaultMm);
    }

    /**
     * Reserva de pie para paginación: altura medida + colchón (sin duplicar margen inferior).
     * El margen inferior de plantilla ya se descuenta aparte en basePageContentMm.
     * Hojas 2+: se descuenta la banda en flujo al inicio de cada continuación (orderSheetBandReserveMm).
     */
    function buildReportPrintPaginationMetrics(container) {
        var root = container || getPrintContainer();
        if (typeof window.syncReportPrintLayoutMetrics === 'function') {
            window.syncReportPrintLayoutMetrics();
        }

        var footerReserveMm = resolveFooterReserveMm();
        var orderSheetBandReserveMm = 0;
        if (cfg.orderSheetHeaderEnabled) {
            orderSheetBandReserveMm = resolveOrderSheetBandReserveMm();
        }
        var footerHeightMm = roundMm(pxToMm(measureFooterHeightPx()));
        var headerHeightMm = roundMm(pxToMm(measureHeaderHeightPx(root)));

        var basePageContentMm = cfg.pageHeightMm - cfg.marginTopMm - cfg.marginBottomMm - footerReserveMm;
        if (!isFinite(basePageContentMm) || basePageContentMm <= 0) {
            basePageContentMm = 240;
        }
        basePageContentMm = roundMm(basePageContentMm);

        // Hoja 1: sin banda. Hojas 2+ (con banda inyectada): menos alto solo en métricas JS, sin tocar @page.
        var firstPageContentMm = roundMm(Math.max(0, basePageContentMm - headerHeightMm));
        var firstPageFlowCapacityMm = roundMm(basePageContentMm);
        var nextPageContentMm = roundMm(Math.max(0, basePageContentMm - orderSheetBandReserveMm));

        var totalContentPx = root.scrollHeight || 0;
        var totalContentMm = roundMm(pxToMm(totalContentPx));

        var estimatedPages = estimatePagesFromMetrics(
            totalContentMm,
            firstPageFlowCapacityMm,
            nextPageContentMm
        );

        var metrics = {
            pageHeightMM: roundMm(cfg.pageHeightMm),
            marginTopMM: roundMm(cfg.marginTopMm),
            marginBottomMM: roundMm(cfg.marginBottomMm),
            headerHeightMM: headerHeightMm,
            footerHeightMM: footerHeightMm,
            footerReserveMM: roundMm(footerReserveMm),
            orderSheetBandReserveMM: roundMm(orderSheetBandReserveMm),
            basePageContentMM: basePageContentMm,
            firstPageContentMM: firstPageContentMm,
            firstPageFlowCapacityMM: firstPageFlowCapacityMm,
            nextPageContentMM: nextPageContentMm,
            totalContentMM: totalContentMm,
            estimatedPages: estimatedPages,
            headerHeightPx: measureHeaderHeightPx(root),
            firstPageContentPx: mmToPx(firstPageContentMm),
            nextPageContentPx: mmToPx(nextPageContentMm)
        };

        document.documentElement.style.setProperty(
            '--print-order-sheet-band-reserve-mm',
            String(metrics.orderSheetBandReserveMM)
        );

        console.log('[report-print-pagination]', {
            pageHeightMM: metrics.pageHeightMM,
            marginTopMM: metrics.marginTopMM,
            marginBottomMM: metrics.marginBottomMM,
            headerHeightMM: metrics.headerHeightMM,
            footerHeightMM: metrics.footerHeightMM,
            orderSheetBandReserveMM: metrics.orderSheetBandReserveMM,
            firstPageContentMM: metrics.firstPageContentMM,
            nextPageContentMM: metrics.nextPageContentMM,
            totalContentMM: metrics.totalContentMM,
            estimatedPages: metrics.estimatedPages
        });

        return metrics;
    }

    function estimatePagesFromMetrics(totalContentMm, firstPageFlowCapacityMm, nextPageContentMm) {
        if (!isFinite(totalContentMm) || totalContentMm <= 0) {
            return 1;
        }
        var firstCap = firstPageFlowCapacityMm;
        if (!isFinite(firstCap) || firstCap <= 0) {
            firstCap = nextPageContentMm;
        }
        if (totalContentMm <= firstCap) {
            return 1;
        }
        if (!isFinite(nextPageContentMm) || nextPageContentMm <= 0) {
            return 1;
        }
        var remaining = totalContentMm - firstCap;
        return 1 + Math.ceil(remaining / nextPageContentMm);
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
        estimatePages: estimatePagesFromMetrics,
        resolveOrderSheetBandReserveMm: resolveOrderSheetBandReserveMm
    };
})();
</script>
