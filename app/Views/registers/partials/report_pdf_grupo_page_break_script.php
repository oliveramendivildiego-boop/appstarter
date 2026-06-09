<?php
/**
 * Ajuste de saltos de página antes de imprimir en el navegador.
 *
 * @var array<string,mixed> $pdf_layout
 * @var float               $page_height_mm
 * @var float               $margin_top_mm
 * @var float               $margin_bottom_mm
 * @var float               $footer_reserve_mm
 * @var bool                $footer_enabled
 */
$pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$ps = is_array($pl['page_style'] ?? null) ? $pl['page_style'] : \App\Services\ReportPdfLayoutService::defaultPageStyleStatic();
$gpb = \App\Services\ReportPdfLayoutService::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
$pageHeightMm = isset($page_height_mm) ? (float) $page_height_mm : 279.4;
$marginTopMm = isset($margin_top_mm) ? (float) $margin_top_mm : 15.0;
$marginBottomMm = isset($margin_bottom_mm) ? (float) $margin_bottom_mm : 15.0;
$footerReserveMm = isset($footer_reserve_mm) ? (float) $footer_reserve_mm : 22.0;
$footerEnabled = ! empty($footer_enabled);
?>
<script>
(function() {
    var cfg = {
        mode: <?= json_encode($gpb['mode'], JSON_UNESCAPED_UNICODE) ?>,
        repeatHeader: <?= ! empty($gpb['repeat_header_on_split']) ? 'true' : 'false' ?>,
        compactMinScale: <?= json_encode(max(75, min(100, (int) ($gpb['compact_min_scale_percent'] ?? 85)))) ?>,
        compactCellPaddingPx: <?= json_encode(max(0, min(20, (int) ($gpb['compact_cell_padding_px'] ?? 0)))) ?>,
        compactAggressive: <?= ! empty($gpb['compact_aggressive']) ? 'true' : 'false' ?>,
        minRemainingMmToForceBreak: <?= json_encode((float) ($gpb['min_remaining_mm_to_force_break'] ?? 0.0)) ?>,
        pageHeightMm: <?= json_encode($pageHeightMm) ?>,
        marginTopMm: <?= json_encode($marginTopMm) ?>,
        marginBottomMm: <?= json_encode($marginBottomMm) ?>,
        footerReserveMm: <?= json_encode($footerReserveMm) ?>,
        footerEnabled: <?= $footerEnabled ? 'true' : 'false' ?>
    };

    var SEGMENT_SELECTOR = '.report-segment-table-wrap, .report-refs-matrix-wrap';
    var savedGrupoDomOrder = null;

    function paginationApi() {
        return window.reportPrintPagination || null;
    }

    function getLayoutContext(container) {
        var api = paginationApi();
        if (!api) {
            return null;
        }
        var metrics = api.buildMetrics(container);
        var boundarySet = api.buildBoundaries(container, metrics);
        return {
            metrics: metrics,
            boundaries: boundarySet.boundaries,
            boundarySet: boundarySet,
            maxSlicePx: metrics.nextPageContentPx
        };
    }

    function pxToMm(px) {
        var api = paginationApi();
        return api ? api.pxToMm(px) : (px / (96 / 25.4));
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

    function remainingOnPage(top, boundarySet) {
        var api = paginationApi();
        if (!api) {
            return 0;
        }
        return api.remainingOnPage(top, boundarySet);
    }

    function cabeceraForSegment(seg) {
        if (!seg) {
            return null;
        }
        var node = seg.previousElementSibling;
        while (node) {
            if (node.classList && node.classList.contains('report-pdf-grupo-cabecera')) {
                return node;
            }
            if (node.classList && node.classList.contains('report-segment-table-wrap')) {
                break;
            }
            node = node.previousElementSibling;
        }
        return null;
    }

    function subgrupoBlockForNode(node) {
        if (!node) {
            return null;
        }
        if (node.classList && node.classList.contains('report-pdf-subgrupo-block')) {
            return node;
        }
        if (node.parentElement && node.parentElement.classList
            && node.parentElement.classList.contains('report-pdf-subgrupo-block')) {
            return node.parentElement;
        }
        return null;
    }

    /**
     * ¿Hay contenido de resultados ya colocado en la misma hoja, encima de este bloque?
     * El encabezado del informe (logo/paciente) no cuenta: solo importa si ya hay segmentos/grupos
     * en la franja de resultados de la hoja actual.
     */
    function hasResultContentAboveOnCurrentPage(topPx, boundarySet, metrics) {
        if (!isFinite(topPx) || topPx < 0) {
            return false;
        }
        var boundaries = boundarySet && boundarySet.boundaries ? boundarySet.boundaries : [];
        var headerPx = metrics && metrics.headerHeightPx ? metrics.headerHeightPx : 0;
        var minGapPx = 10;

        for (var i = 0; i < boundaries.length; i++) {
            var pageStart = i === 0 ? 0 : boundaries[i - 1];
            var pageEnd = boundaries[i];
            if (topPx < pageStart || topPx >= pageEnd) {
                continue;
            }
            if (i === 0) {
                return topPx > (headerPx + minGapPx);
            }
            return topPx > (pageStart + minGapPx);
        }

        var lastEnd = boundaries.length > 0 ? boundaries[boundaries.length - 1] : 0;
        if (topPx >= lastEnd) {
            return topPx > (lastEnd + minGapPx);
        }
        return false;
    }

    function markForceBreakBeforeCabecera(cabecera) {
        if (!cabecera) {
            return;
        }
        cabecera.classList.add('report-cabecera-force-break-before');
        var subgrupo = subgrupoBlockForNode(cabecera);
        if (subgrupo) {
            subgrupo.classList.add('report-subgrupo-force-break-before');
        }
    }

    function applyForceBreakForUnit(unit, seg, container, layoutCtx) {
        var maxSlicePx = layoutCtx.maxSlicePx;
        var boundarySet = layoutCtx.boundarySet;

        if (unit.height > maxSlicePx) {
            seg.classList.add('report-segment-allow-split');
            return;
        }

        var remaining = remainingOnPage(unit.top, boundarySet);
        if (unit.height <= remaining) {
            return;
        }

        if (!hasResultContentAboveOnCurrentPage(unit.top, boundarySet, layoutCtx.metrics)) {
            seg.classList.add('report-segment-allow-split');
            return;
        }

        if (unit.cabecera) {
            markForceBreakBeforeCabecera(unit.cabecera);
        } else {
            seg.classList.add('report-segment-force-break-before');
        }
    }

    function pageEndPx(pageIndex, boundarySet, metrics) {
        var boundaries = boundarySet.boundaries || [];
        if (pageIndex < boundaries.length) {
            return boundaries[pageIndex];
        }
        var last = boundaries.length ? boundaries[boundaries.length - 1] : 0;
        return last + (metrics.nextPageContentPx || 0);
    }

    function pageStartPx(pageIndex, boundarySet) {
        if (pageIndex <= 0) {
            return 0;
        }
        var boundaries = boundarySet.boundaries || [];
        return boundaries[pageIndex - 1] || 0;
    }

    function atPageResultsStart(cursorPage, cursorY, boundarySet, metrics) {
        var epsilon = 12;
        if (cursorPage === 0) {
            return cursorY <= ((metrics && metrics.headerHeightPx) ? metrics.headerHeightPx : 0) + epsilon;
        }
        return cursorY <= pageStartPx(cursorPage, boundarySet) + epsilon;
    }

    function bumpCursorAfterPlace(cursorPage, cursorY, placedHeight, boundarySet, metrics) {
        var page = cursorPage;
        var y = cursorY;
        var left = Math.max(0, placedHeight || 0);
        var guard = 0;

        while (left > 0 && guard < 500) {
            var pageEnd = pageEndPx(page, boundarySet, metrics);
            var remaining = pageEnd - y;
            if (remaining <= 0) {
                page++;
                y = pageStartPx(page, boundarySet);
                guard++;
                continue;
            }
            if (left <= remaining) {
                y += left;
                left = 0;
            } else {
                left -= remaining;
                page++;
                y = pageStartPx(page, boundarySet);
            }
            guard++;
        }

        return { page: page, y: y };
    }

    function segmentUnitMetrics(seg, container) {
        var cabecera = cabeceraForSegment(seg);
        var measureEl = cabecera || seg;
        var top = topWithinContainer(measureEl, container);
        var height = seg.offsetHeight + (cabecera ? cabecera.offsetHeight : 0);
        return {
            cabecera: cabecera,
            top: top,
            height: height
        };
    }

    function usesGrupoIntactMode() {
        return cfg.mode === 'keep_together' || cfg.mode === 'keep_together_compact';
    }

    function shouldForceBreakBefore(remainingPx) {
        if (!usesGrupoIntactMode()) {
            return false;
        }
        var minMm = cfg.minRemainingMmToForceBreak;
        if (!isFinite(minMm) || minMm <= 0) {
            return true;
        }
        return pxToMm(remainingPx) >= minMm;
    }

    function usesPureGrupoIntact() {
        if (!usesGrupoIntactMode()) {
            return false;
        }
        var minMm = cfg.minRemainingMmToForceBreak;
        return !isFinite(minMm) || minMm <= 0;
    }

    function clearGrupoCompact(grupo) {
        grupo.classList.remove('report-pdf-grupo-prueba-compact', 'report-pdf-grupo-prueba-compact-aggressive');
        grupo.style.removeProperty('--pdf-gpb-compact-scale');
        grupo.style.removeProperty('--pdf-gpb-compact-cell-padding-v');
    }

    function applyCompactToGrupo(grupo) {
        var scale = cfg.compactMinScale / 100;
        grupo.classList.add('report-pdf-grupo-prueba-compact');
        grupo.style.setProperty('--pdf-gpb-compact-scale', String(scale));
        if (cfg.compactCellPaddingPx > 0) {
            grupo.style.setProperty('--pdf-gpb-compact-cell-padding-v', cfg.compactCellPaddingPx + 'px');
        }
        if (cfg.compactAggressive) {
            grupo.classList.add('report-pdf-grupo-prueba-compact-aggressive');
        }
    }

    function restoreGrupoDomOrder() {
        if (!savedGrupoDomOrder || !savedGrupoDomOrder.length) {
            return;
        }
        savedGrupoDomOrder.forEach(function(entry) {
            if (!entry || !entry.parent || !entry.el) {
                return;
            }
            if (entry.nextSibling && entry.nextSibling.parentNode === entry.parent) {
                entry.parent.insertBefore(entry.el, entry.nextSibling);
            } else {
                entry.parent.appendChild(entry.el);
            }
        });
        savedGrupoDomOrder = null;
        document.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo, idx) {
            if (idx === 0) {
                grupo.classList.add('report-pdf-grupo-prueba-first');
            } else {
                grupo.classList.remove('report-pdf-grupo-prueba-first');
            }
        });
    }

    function captureGrupoDomOrder(container) {
        var entries = [];
        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            entries.push({
                el: grupo,
                parent: grupo.parentNode,
                nextSibling: grupo.nextSibling
            });
        });
        return entries;
    }

    function syncFirstGrupoClass(container) {
        var grupos = container.querySelectorAll('.report-pdf-grupo-prueba');
        grupos.forEach(function(grupo, idx) {
            if (idx === 0) {
                grupo.classList.add('report-pdf-grupo-prueba-first');
            } else {
                grupo.classList.remove('report-pdf-grupo-prueba-first');
            }
        });
    }

    function removeFromPool(pool, item) {
        for (var i = 0; i < pool.length; i++) {
            if (pool[i].el === item.el) {
                pool.splice(i, 1);
                return;
            }
        }
    }

    function packWholeGruposOnPage(pool, remainingPx, maxSlicePx) {
        var packed = [];
        var space = remainingPx;
        var guard = 0;
        while (guard < pool.length + 2) {
            var bestI = -1;
            var bestH = -1;
            for (var i = 0; i < pool.length; i++) {
                var h = pool[i].height;
                if (h <= space && h <= maxSlicePx && h > bestH) {
                    bestH = h;
                    bestI = i;
                }
            }
            if (bestI < 0) {
                break;
            }
            packed.push(pool[bestI]);
            space -= pool[bestI].height;
            pool.splice(bestI, 1);
            guard++;
        }
        return packed;
    }

    function simulateGrupoBySegments(grupo, cursorPage, cursorY, layoutCtx) {
        var metrics = layoutCtx.metrics;
        var boundarySet = layoutCtx.boundarySet;
        var maxSlicePx = layoutCtx.maxSlicePx;
        var lastSubgrupo = null;
        var cabeceraCounted = false;
        var page = cursorPage;
        var y = cursorY;

        grupo.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            var cabecera = cabeceraForSegment(seg);
            var subgrupo = subgrupoBlockForNode(seg);
            if (subgrupo !== lastSubgrupo) {
                lastSubgrupo = subgrupo;
                cabeceraCounted = false;
            }

            var cabeceraH = (cabecera && !cabeceraCounted) ? (cabecera.offsetHeight || 0) : 0;
            if (cabecera && !cabeceraCounted) {
                cabeceraCounted = true;
            }
            var unitH = cabeceraH + (seg.offsetHeight || 0);
            var remaining = pageEndPx(page, boundarySet, metrics) - y;

            if (unitH > maxSlicePx) {
                // Segmento demasiado alto: se parte en varias hojas.
            } else if (unitH <= remaining) {
                // Cabe en la hoja actual simulada.
            } else if (atPageResultsStart(page, y, boundarySet, metrics)) {
                // Inicio de resultados en la hoja: permitir partir.
            } else {
                page++;
                y = pageStartPx(page, boundarySet);
            }

            var bumped = bumpCursorAfterPlace(page, y, unitH, boundarySet, metrics);
            page = bumped.page;
            y = bumped.y;
        });

        return { page: page, y: y };
    }

    function reorderGruposForAutoPack(container, layoutCtx) {
        var grupos = Array.from(container.querySelectorAll('.report-pdf-grupo-prueba'));
        if (grupos.length < 2) {
            return;
        }
        var parent = grupos[0].parentNode;
        if (!parent) {
            return;
        }

        savedGrupoDomOrder = captureGrupoDomOrder(container);

        var metrics = layoutCtx.metrics;
        var boundarySet = layoutCtx.boundarySet;
        var maxSlicePx = layoutCtx.maxSlicePx;
        var headerPx = metrics.headerHeightPx || 0;
        var items = grupos.map(function(grupo, idx) {
            return {
                el: grupo,
                height: grupo.offsetHeight || 0,
                origIdx: idx
            };
        });
        var remaining = items.slice();
        var ordered = [];
        var cursorPage = 0;
        var cursorY = headerPx;

        while (remaining.length > 0) {
            var remainingPx = pageEndPx(cursorPage, boundarySet, metrics) - cursorY;
            var pool = remaining.slice();
            var packed = packWholeGruposOnPage(pool, remainingPx, maxSlicePx);

            if (packed.length > 0) {
                ordered = ordered.concat(packed);
                packed.forEach(function(item) {
                    removeFromPool(remaining, item);
                });
                var packedHeight = 0;
                packed.forEach(function(item) {
                    packedHeight += item.height;
                });
                var bumpedPack = bumpCursorAfterPlace(cursorPage, cursorY, packedHeight, boundarySet, metrics);
                cursorPage = bumpedPack.page;
                cursorY = bumpedPack.y;
                continue;
            }

            remaining.sort(function(a, b) {
                return a.origIdx - b.origIdx;
            });
            var next = remaining.shift();
            ordered.push(next);

            if (next.height > maxSlicePx) {
                var placedHuge = simulateGrupoBySegments(next.el, cursorPage, cursorY, layoutCtx);
                cursorPage = placedHuge.page;
                cursorY = placedHuge.y;
            } else if (next.height <= remainingPx) {
                var bumpedFit = bumpCursorAfterPlace(cursorPage, cursorY, next.height, boundarySet, metrics);
                cursorPage = bumpedFit.page;
                cursorY = bumpedFit.y;
            } else {
                var placedSplit = simulateGrupoBySegments(next.el, cursorPage, cursorY, layoutCtx);
                cursorPage = placedSplit.page;
                cursorY = placedSplit.y;
            }
        }

        ordered.forEach(function(item) {
            parent.appendChild(item.el);
        });
        syncFirstGrupoClass(container);
    }

    function clearPageBreakAdjustments() {
        restoreGrupoDomOrder();
        document.querySelectorAll('.report-pdf-grupo-cabecera').forEach(function(cabecera) {
            cabecera.classList.remove('report-cabecera-force-break-before');
        });
        document.querySelectorAll('.report-pdf-subgrupo-block').forEach(function(subgrupo) {
            subgrupo.classList.remove('report-subgrupo-force-break-before');
        });
        document.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            grupo.classList.remove(
                'report-pdf-grupo-prueba-force-break-before',
                'report-pdf-grupo-prueba-allow-split',
                'report-pdf-grupo-prueba-keep-on-page'
            );
            clearGrupoCompact(grupo);
            grupo.querySelectorAll('.report-pdf-grupo-cabecera-continuacion-injected').forEach(function(node) {
                node.remove();
            });
        });
        document.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            seg.classList.remove('report-segment-force-break-before', 'report-segment-allow-split');
        });
    }

    function applyCabeceraSegmentIntegrity(container, layoutCtx) {
        container.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            var unit = segmentUnitMetrics(seg, container);
            applyForceBreakForUnit(unit, seg, container, layoutCtx);
        });
    }

    function applySegmentPageBreaks(container, layoutCtx, root) {
        var scope = root || container;
        scope.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            var unit = segmentUnitMetrics(seg, container);
            applyForceBreakForUnit(unit, seg, container, layoutCtx);
        });
    }

    function placeGrupoBySegments(grupo, cursorPage, cursorY, layoutCtx) {
        var metrics = layoutCtx.metrics;
        var boundarySet = layoutCtx.boundarySet;
        var maxSlicePx = layoutCtx.maxSlicePx;
        var lastSubgrupo = null;
        var cabeceraCounted = false;
        var page = cursorPage;
        var y = cursorY;

        grupo.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            var cabecera = cabeceraForSegment(seg);
            var subgrupo = subgrupoBlockForNode(seg);
            if (subgrupo !== lastSubgrupo) {
                lastSubgrupo = subgrupo;
                cabeceraCounted = false;
            }

            var cabeceraH = (cabecera && !cabeceraCounted) ? (cabecera.offsetHeight || 0) : 0;
            if (cabecera && !cabeceraCounted) {
                cabeceraCounted = true;
            }
            var unitH = cabeceraH + (seg.offsetHeight || 0);
            var remaining = pageEndPx(page, boundarySet, metrics) - y;

            seg.classList.remove('report-segment-force-break-before', 'report-segment-allow-split');
            if (cabecera) {
                cabecera.classList.remove('report-cabecera-force-break-before');
                if (subgrupo) {
                    subgrupo.classList.remove('report-subgrupo-force-break-before');
                }
            }

            if (unitH > maxSlicePx) {
                seg.classList.add('report-segment-allow-split');
            } else if (unitH <= remaining) {
                // Cabe en el espacio restante simulado de la hoja actual.
            } else if (atPageResultsStart(page, y, boundarySet, metrics)) {
                seg.classList.add('report-segment-allow-split');
            } else {
                if (cabecera) {
                    markForceBreakBeforeCabecera(cabecera);
                } else {
                    seg.classList.add('report-segment-force-break-before');
                }
                page++;
                y = pageStartPx(page, boundarySet);
                remaining = pageEndPx(page, boundarySet, metrics) - y;
                if (unitH > remaining || unitH > maxSlicePx) {
                    seg.classList.add('report-segment-allow-split');
                }
            }

            var bumped = bumpCursorAfterPlace(page, y, unitH, boundarySet, metrics);
            page = bumped.page;
            y = bumped.y;
        });

        return { page: page, y: y };
    }

    function applyIfFitsMode(container, layoutCtx) {
        var metrics = layoutCtx.metrics;
        var boundarySet = layoutCtx.boundarySet;
        var maxSlicePx = layoutCtx.maxSlicePx;
        var headerPx = metrics.headerHeightPx || 0;
        var cursorPage = 0;
        var cursorY = headerPx;

        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            var grupoHeight = grupo.offsetHeight || 0;
            var remaining = pageEndPx(cursorPage, boundarySet, metrics) - cursorY;

            grupo.classList.remove(
                'report-pdf-grupo-prueba-allow-split',
                'report-pdf-grupo-prueba-keep-on-page',
                'report-pdf-grupo-prueba-force-break-before'
            );

            if (grupoHeight > maxSlicePx) {
                grupo.classList.add('report-pdf-grupo-prueba-allow-split');
                var placedHuge = placeGrupoBySegments(grupo, cursorPage, cursorY, layoutCtx);
                cursorPage = placedHuge.page;
                cursorY = placedHuge.y;
                return;
            }

            if (grupoHeight <= remaining) {
                grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                var bumpedFit = bumpCursorAfterPlace(cursorPage, cursorY, grupoHeight, boundarySet, metrics);
                cursorPage = bumpedFit.page;
                cursorY = bumpedFit.y;
                return;
            }

            grupo.classList.add('report-pdf-grupo-prueba-allow-split');
            var placedSplit = placeGrupoBySegments(grupo, cursorPage, cursorY, layoutCtx);
            cursorPage = placedSplit.page;
            cursorY = placedSplit.y;
        });
    }

    function fallbackFillGrupo(grupo, container, layoutCtx) {
        grupo.classList.remove('report-pdf-grupo-prueba-force-break-before', 'report-pdf-grupo-prueba-keep-on-page');
        clearGrupoCompact(grupo);
        grupo.classList.add('report-pdf-grupo-prueba-allow-split');
        applySegmentPageBreaks(container, layoutCtx, grupo);
    }

    function applyGrupoPageBreaks(container, layoutCtx) {
        var maxSlicePx = layoutCtx.maxSlicePx;
        var boundarySet = layoutCtx.boundarySet;
        var metrics = layoutCtx.metrics;

        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            var top = topWithinContainer(grupo, container);
            var remaining = remainingOnPage(top, boundarySet);
            var height = grupo.offsetHeight;

            if (height > maxSlicePx) {
                grupo.classList.add('report-pdf-grupo-prueba-allow-split');
                applySegmentPageBreaks(container, layoutCtx, grupo);
                return;
            }

            if (height <= remaining) {
                if (usesPureGrupoIntact()) {
                    grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                }
                return;
            }

            if (cfg.mode === 'keep_together_compact') {
                applyCompactToGrupo(grupo);
                height = grupo.offsetHeight;
                if (height <= remaining) {
                    grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                    return;
                }
                clearGrupoCompact(grupo);
            }

            if (shouldForceBreakBefore(remaining) && hasResultContentAboveOnCurrentPage(top, boundarySet, metrics)) {
                grupo.classList.add('report-pdf-grupo-prueba-force-break-before');
            } else {
                fallbackFillGrupo(grupo, container, layoutCtx);
            }
        });
    }

    function applyPageBreakRules() {
        clearPageBreakAdjustments();
        var container = (paginationApi() && paginationApi().getPrintContainer)
            ? paginationApi().getPrintContainer()
            : (document.querySelector('.pdf-main-stack') || document.body);

        var layoutCtx = getLayoutContext(container);
        if (!layoutCtx || !isFinite(layoutCtx.maxSlicePx) || layoutCtx.maxSlicePx <= 0) {
            return;
        }

        if (!cfg.mode || cfg.mode === 'flow') {
            applyCabeceraSegmentIntegrity(container, layoutCtx);
            return;
        }

        if (cfg.mode === 'keep_segment') {
            applySegmentPageBreaks(container, layoutCtx);
            return;
        }

        if (cfg.mode === 'keep_together_if_fits') {
            applyIfFitsMode(container, layoutCtx);
            return;
        }

        if (cfg.mode === 'keep_together_if_fits_auto_order') {
            reorderGruposForAutoPack(container, layoutCtx);
            applyIfFitsMode(container, layoutCtx);
            return;
        }

        applyCabeceraSegmentIntegrity(container, layoutCtx);
        applyGrupoPageBreaks(container, layoutCtx);
    }

    window.updateReportPrintPageBreakMetrics = function(metrics) {
        if (!metrics || typeof metrics !== 'object') {
            return;
        }
        if (isFinite(metrics.footerReserveMm) && metrics.footerReserveMm >= 0) {
            cfg.footerReserveMm = metrics.footerReserveMm;
        }
    };

    window.applyReportPdfGrupoPageBreaks = applyPageBreakRules;
    window.addEventListener('beforeprint', applyPageBreakRules);
    window.addEventListener('afterprint', clearPageBreakAdjustments);
})();
</script>
