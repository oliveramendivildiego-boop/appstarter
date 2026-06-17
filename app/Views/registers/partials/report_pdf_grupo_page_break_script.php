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
    var LAB_FIRMA_SELECTOR = '.report-lab-firma-grupo-inline, .lab-firmas-pdf-block-global';
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

    function pageLeaderForGrupo(grupo) {
        if (!grupo) {
            return null;
        }
        var prev = grupo.previousElementSibling;
        if (prev && prev.classList && prev.classList.contains('report-pdf-grupo-area-page-leader')) {
            return prev;
        }
        return null;
    }

    function areaSeparatorForGrupo(grupo) {
        if (!grupo || !grupo.querySelector) {
            return null;
        }
        return grupo.querySelector('.report-pdf-grupo-area-separator');
    }

    function clearGrupoStartBreakMarks(grupo) {
        if (!grupo) {
            return;
        }
        grupo.classList.remove('report-pdf-grupo-prueba-force-break-before');
        var leader = pageLeaderForGrupo(grupo);
        if (leader) {
            leader.classList.remove('report-area-page-leader-force-break-before');
        }
        var separator = areaSeparatorForGrupo(grupo);
        if (separator) {
            separator.classList.remove('report-area-separator-force-break-before');
        }
    }

    function ensurePageLeaderBefore(grupo) {
        var leader = pageLeaderForGrupo(grupo);
        if (leader || !grupo || !grupo.parentNode) {
            return leader;
        }
        leader = document.createElement('div');
        leader.className = 'report-pdf-grupo-area-page-leader';
        leader.setAttribute('aria-hidden', 'true');
        grupo.parentNode.insertBefore(leader, grupo);
        return leader;
    }

    function markGrupoStartBreakBefore(grupo) {
        if (!grupo) {
            return;
        }
        clearGrupoStartBreakMarks(grupo);
        var leader = ensurePageLeaderBefore(grupo);
        if (leader) {
            leader.classList.add('report-area-page-leader-force-break-before');
            return;
        }
        var separator = areaSeparatorForGrupo(grupo);
        if (separator) {
            separator.classList.add('report-area-separator-force-break-before');
            return;
        }
        grupo.classList.add('report-pdf-grupo-prueba-force-break-before');
    }

    function clearSegmentBreaksInGrupo(grupo) {
        if (!grupo) {
            return;
        }
        grupo.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            seg.classList.remove('report-segment-force-break-before', 'report-segment-allow-split');
        });
        grupo.querySelectorAll('.report-pdf-grupo-cabecera').forEach(function(cabecera) {
            cabecera.classList.remove('report-cabecera-force-break-before');
        });
        grupo.querySelectorAll('.report-pdf-subgrupo-block').forEach(function(subgrupo) {
            subgrupo.classList.remove('report-subgrupo-force-break-before');
        });
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

    function measureGrupoHeight(grupo, withCompact) {
        if (cfg.mode === 'keep_together_compact') {
            if (withCompact) {
                applyCompactToGrupo(grupo);
            } else {
                clearGrupoCompact(grupo);
            }
        }
        return grupo.offsetHeight || 0;
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
            var leader = pageLeaderForGrupo(grupo);
            if (leader) {
                entries.push({
                    el: leader,
                    parent: leader.parentNode,
                    nextSibling: leader.nextSibling
                });
            }
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
            var leader = pageLeaderForGrupo(item.el);
            if (leader) {
                parent.appendChild(leader);
            }
            parent.appendChild(item.el);
        });
        syncFirstGrupoClass(container);
    }

    function clearPageBreakAdjustments() {
        restoreGrupoDomOrder();
        clearBrowserPrintAreaSeparatorFix();
        clearInterGrupoPageBreaks();
        document.querySelectorAll('.report-pdf-grupo-cabecera').forEach(function(cabecera) {
            cabecera.classList.remove('report-cabecera-force-break-before');
        });
        document.querySelectorAll('.report-pdf-subgrupo-block').forEach(function(subgrupo) {
            subgrupo.classList.remove('report-subgrupo-force-break-before');
        });
        document.querySelectorAll('.report-pdf-grupo-area-page-leader').forEach(function(leader) {
            leader.classList.remove('report-area-page-leader-force-break-before');
            leader.style.removeProperty('break-before');
            leader.style.removeProperty('page-break-before');
            leader.style.removeProperty('height');
            leader.style.removeProperty('min-height');
            leader.style.removeProperty('display');
            leader.style.removeProperty('width');
            leader.style.removeProperty('margin');
            leader.style.removeProperty('padding');
        });
        document.querySelectorAll('.report-pdf-grupo-area-separator').forEach(function(separator) {
            separator.classList.remove('report-area-separator-force-break-before');
        });
        document.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            grupo.classList.remove(
                'report-pdf-grupo-prueba-force-break-before',
                'report-pdf-grupo-prueba-allow-split',
                'report-pdf-grupo-prueba-keep-on-page',
                'report-pdf-grupo-prueba-new-page-start',
                'report-pdf-grupo-prueba-split-segments-only'
            );
            grupo.style.removeProperty('break-before');
            grupo.style.removeProperty('page-break-before');
            grupo.style.removeProperty('break-inside');
            grupo.style.removeProperty('page-break-inside');
            clearGrupoCompact(grupo);
            grupo.querySelectorAll('.report-pdf-grupo-cabecera-continuacion-injected').forEach(function(node) {
                node.remove();
            });
        });
        document.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            seg.classList.remove('report-segment-force-break-before', 'report-segment-allow-split');
        });
        document.querySelectorAll(LAB_FIRMA_SELECTOR).forEach(function(block) {
            clearLabFirmaPageLeader(block);
            block.classList.remove('report-pdf-grupo-page-end-seal');
            block.style.removeProperty('break-after');
            block.style.removeProperty('page-break-after');
        });
        document.querySelectorAll('.report-pdf-grupo-page-end-seal').forEach(function(node) {
            node.classList.remove('report-pdf-grupo-page-end-seal');
            node.style.removeProperty('break-after');
            node.style.removeProperty('page-break-after');
        });
    }

    function labFirmaPageLeaderFor(block) {
        if (!block) {
            return null;
        }
        var prev = block.previousElementSibling;
        if (prev && prev.classList && prev.classList.contains('report-lab-firma-page-leader')) {
            return prev;
        }
        return null;
    }

    function clearLabFirmaPageLeader(block) {
        var leader = labFirmaPageLeaderFor(block);
        if (leader && leader.parentNode) {
            leader.parentNode.removeChild(leader);
        }
    }

    function ensureLabFirmaPageLeaderBefore(block) {
        var leader = labFirmaPageLeaderFor(block);
        if (leader || !block || !block.parentNode) {
            return leader;
        }
        leader = document.createElement('div');
        leader.className = 'report-lab-firma-page-leader';
        leader.setAttribute('aria-hidden', 'true');
        block.parentNode.insertBefore(leader, block);
        return leader;
    }

    function applyLabFirmasPageBreaks(container, layoutCtx) {
        var maxSlicePx = layoutCtx.maxSlicePx;
        var boundarySet = layoutCtx.boundarySet;

        container.querySelectorAll(LAB_FIRMA_SELECTOR).forEach(function(block) {
            clearLabFirmaPageLeader(block);

            var height = block.offsetHeight || 0;
            if (!isFinite(height) || height <= 0) {
                return;
            }

            if (height > maxSlicePx) {
                return;
            }

            var top = topWithinContainer(block, container);
            var remaining = remainingOnPage(top, boundarySet);
            if (!isFinite(remaining) || remaining <= 0) {
                return;
            }

            if (height <= remaining) {
                return;
            }

            var lastSeg = findLastSegmentBeforeNode(container, block);
            var pulled = false;
            if (lastSeg) {
                var unit = segmentUnitMetrics(lastSeg, container);
                if ((unit.height + height) <= maxSlicePx) {
                    markForceBreakBeforeSegmentUnit(lastSeg, container);
                    pulled = true;
                }
            }

            if (!pulled) {
                ensureLabFirmaPageLeaderBefore(block);
            }
        });
    }

    function applyCabeceraSegmentIntegrity(container, layoutCtx) {
        container.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            var unit = segmentUnitMetrics(seg, container);
            applyForceBreakForUnit(unit, seg, container, layoutCtx);
        });
    }

    function labFirmaBlockInGrupo(grupo) {
        if (!grupo || !grupo.querySelector) {
            return null;
        }
        return grupo.querySelector('.report-lab-firma-grupo-inline');
    }

    function findLastSegmentBeforeNode(container, beforeNode) {
        if (!beforeNode) {
            return null;
        }
        var beforeTop = topWithinContainer(beforeNode, container);
        var lastSeg = null;
        var lastTop = -1;
        container.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            var t = topWithinContainer(seg, container);
            if (t < beforeTop - 0.5 && t >= lastTop) {
                lastTop = t;
                lastSeg = seg;
            }
        });
        return lastSeg;
    }

    function markForceBreakBeforeSegmentUnit(seg, container) {
        if (!seg) {
            return;
        }
        var unit = segmentUnitMetrics(seg, container);
        if (unit.cabecera) {
            markForceBreakBeforeCabecera(unit.cabecera);
        } else {
            seg.classList.add('report-segment-force-break-before');
        }
    }

    function applySegmentPageBreaks(container, layoutCtx, root) {
        var scope = root || container;
        var firmaEl = (root && root.classList && root.classList.contains('report-pdf-grupo-prueba'))
            ? labFirmaBlockInGrupo(root)
            : null;
        var firmaH = firmaEl ? (firmaEl.offsetHeight || 0) : 0;
        var maxSlicePx = layoutCtx.maxSlicePx;
        var segs = Array.from(scope.querySelectorAll(SEGMENT_SELECTOR));

        segs.forEach(function(seg, idx) {
            var unit = segmentUnitMetrics(seg, container);
            var isLastInScope = idx === segs.length - 1;

            if (isLastInScope && firmaH > 0 && (unit.height + firmaH) <= maxSlicePx) {
                applyForceBreakForUnit({
                    cabecera: unit.cabecera,
                    top: unit.top,
                    height: unit.height + firmaH
                }, seg, container, layoutCtx);
                return;
            }

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
        clearGrupoStartBreakMarks(grupo);
        grupo.classList.remove('report-pdf-grupo-prueba-keep-on-page');
        clearGrupoCompact(grupo);
        grupo.classList.add('report-pdf-grupo-prueba-allow-split');
        applySegmentPageBreaks(container, layoutCtx, grupo);
    }

    function applyGrupoPageBreaks(container, layoutCtx) {
        var maxSlicePx = layoutCtx.maxSlicePx;
        var boundarySet = layoutCtx.boundarySet;
        var metrics = layoutCtx.metrics;
        var pureIntact = usesPureGrupoIntact();
        var compactMode = cfg.mode === 'keep_together_compact';
        var intactMode = usesGrupoIntactMode();

        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo, idx) {
            clearSegmentBreaksInGrupo(grupo);

            var isFirstGrupo = idx === 0 || grupo.classList.contains('report-pdf-grupo-prueba-first');
            if (intactMode && !isFirstGrupo) {
                markGrupoStartBreakBefore(grupo);
            }

            var top = topWithinContainer(grupo, container);
            var remaining = remainingOnPage(top, boundarySet);
            var height = measureGrupoHeight(grupo, false);
            var effectiveHeight = height;

            if (height <= remaining) {
                if (pureIntact || compactMode) {
                    grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                }
                return;
            }

            if (compactMode) {
                effectiveHeight = measureGrupoHeight(grupo, true);
                if (effectiveHeight <= remaining) {
                    grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                    return;
                }
            }

            if (effectiveHeight > maxSlicePx) {
                grupo.classList.add('report-pdf-grupo-prueba-allow-split');
                applySegmentPageBreaks(container, layoutCtx, grupo);
                return;
            }

            if (pureIntact || compactMode) {
                markGrupoStartBreakBefore(grupo);
                return;
            }

            if (shouldForceBreakBefore(remaining) && hasResultContentAboveOnCurrentPage(top, boundarySet, metrics)) {
                markGrupoStartBreakBefore(grupo);
            } else {
                fallbackFillGrupo(grupo, container, layoutCtx);
            }
        });
    }

    function isNearPageStart(topPx, boundarySet) {
        var boundaries = boundarySet && boundarySet.boundaries ? boundarySet.boundaries : [];
        var epsilon = 18;
        if (topPx <= epsilon) {
            return true;
        }
        for (var i = 0; i < boundaries.length; i++) {
            var pageStart = i === 0 ? 0 : boundaries[i - 1];
            if (Math.abs(topPx - pageStart) <= epsilon) {
                return true;
            }
        }
        return false;
    }

    function allowSplitGrupoWithSegments(grupo, container, layoutCtx) {
        if (!grupo) {
            return;
        }
        if (usesGrupoIntactMode()) {
            grupo.classList.add('report-pdf-grupo-prueba-split-segments-only');
            grupo.classList.remove('report-pdf-grupo-prueba-allow-split');
            applySegmentPageBreaks(container, layoutCtx, grupo);
            return;
        }
        grupo.classList.add('report-pdf-grupo-prueba-allow-split');
        applySegmentPageBreaks(container, layoutCtx, grupo);
    }

    function strengthenInterPageBreakNode(breaker) {
        if (!breaker) {
            return;
        }
        breaker.style.setProperty('display', 'block', 'important');
        breaker.style.setProperty('width', '100%', 'important');
        breaker.style.setProperty('height', '1px', 'important');
        breaker.style.setProperty('min-height', '1px', 'important');
        breaker.style.setProperty('margin', '0', 'important');
        breaker.style.setProperty('padding', '0', 'important');
        breaker.style.setProperty('border', '0', 'important');
        breaker.style.setProperty('line-height', '0', 'important');
        breaker.style.setProperty('font-size', '0', 'important');
        breaker.style.setProperty('overflow', 'hidden', 'important');
        breaker.style.setProperty('clear', 'both', 'important');
        breaker.style.setProperty('break-before', 'page', 'important');
        breaker.style.setProperty('page-break-before', 'always', 'important');
        breaker.style.setProperty('break-after', 'avoid', 'important');
        breaker.style.setProperty('page-break-after', 'avoid', 'important');
    }

    function sealGrupoPageEndsBeforeNext(container) {
        var grupos = container.querySelectorAll('.report-pdf-grupo-prueba');
        for (var i = 0; i < grupos.length - 1; i++) {
            var grupo = grupos[i];
            var tail = labFirmaBlockInGrupo(grupo);
            if (!tail) {
                var segs = grupo.querySelectorAll(SEGMENT_SELECTOR);
                tail = segs.length ? segs[segs.length - 1] : grupo.lastElementChild;
            }
            if (!tail) {
                continue;
            }
            tail.classList.add('report-pdf-grupo-page-end-seal');
            tail.style.setProperty('break-after', 'page', 'important');
            tail.style.setProperty('page-break-after', 'always', 'important');
        }
    }

    function clearInterGrupoPageBreaks(container) {
        var root = container || document;
        root.querySelectorAll('.report-grupo-inter-page-break:not(.report-grupo-inter-page-break-server)').forEach(function(node) {
            node.remove();
        });
    }

    function applyGrupoInterPageBreaks(container) {
        var grupos = container.querySelectorAll('.report-pdf-grupo-prueba');
        grupos.forEach(function(grupo, idx) {
            var isFirstGrupo = idx === 0 || grupo.classList.contains('report-pdf-grupo-prueba-first');
            if (isFirstGrupo || !grupo.parentNode) {
                return;
            }

            clearGrupoStartBreakMarks(grupo);
            var oldLeader = pageLeaderForGrupo(grupo);
            if (oldLeader && oldLeader.parentNode) {
                oldLeader.parentNode.removeChild(oldLeader);
            }

            var breaker = grupo.previousElementSibling;
            if (!breaker || !breaker.classList || !breaker.classList.contains('report-grupo-inter-page-break')) {
                breaker = document.createElement('div');
                breaker.className = 'report-grupo-inter-page-break';
                breaker.setAttribute('aria-hidden', 'true');
                grupo.parentNode.insertBefore(breaker, grupo);
            }
            strengthenInterPageBreakNode(breaker);

            grupo.classList.add('report-pdf-grupo-prueba-new-page-start');
            grupo.classList.remove('report-pdf-grupo-prueba-allow-split');
            grupo.style.setProperty('break-before', 'page', 'important');
            grupo.style.setProperty('page-break-before', 'always', 'important');
            grupo.style.setProperty('margin-top', '0', 'important');
            grupo.style.setProperty('padding-top', '0', 'important');
            grupo.style.setProperty('break-inside', 'avoid-page', 'important');
            grupo.style.setProperty('page-break-inside', 'avoid', 'important');
        });

        sealGrupoPageEndsBeforeNext(container);
    }

    function applyBrowserPrintGrupoIntactPageBreaks(container, layoutCtx) {
        var maxSlicePx = layoutCtx.maxSlicePx;
        var boundarySet = layoutCtx.boundarySet;
        var compactMode = cfg.mode === 'keep_together_compact';

        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo, idx) {
            clearSegmentBreaksInGrupo(grupo);
            clearGrupoStartBreakMarks(grupo);
            clearGrupoCompact(grupo);
            grupo.classList.remove(
                'report-pdf-grupo-prueba-allow-split',
                'report-pdf-grupo-prueba-keep-on-page',
                'report-pdf-grupo-prueba-split-segments-only'
            );

            var isFirstGrupo = idx === 0 || grupo.classList.contains('report-pdf-grupo-prueba-first');

            var height = measureGrupoHeight(grupo, false);
            var effectiveHeight = height;

            if (!isFirstGrupo) {
                if (compactMode && height > maxSlicePx) {
                    effectiveHeight = measureGrupoHeight(grupo, true);
                    if (effectiveHeight <= maxSlicePx) {
                        grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                        return;
                    }
                    clearGrupoCompact(grupo);
                    effectiveHeight = height;
                }

                if (effectiveHeight > maxSlicePx) {
                    allowSplitGrupoWithSegments(grupo, container, layoutCtx);
                } else {
                    grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                }
                return;
            }

            var top = topWithinContainer(grupo, container);
            var remaining = remainingOnPage(top, boundarySet);

            if (compactMode) {
                effectiveHeight = measureGrupoHeight(grupo, true);
                if (effectiveHeight <= remaining) {
                    grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                    return;
                }
                clearGrupoCompact(grupo);
                effectiveHeight = measureGrupoHeight(grupo, false);
            }

            if (effectiveHeight <= remaining) {
                grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                return;
            }

            if (effectiveHeight > maxSlicePx) {
                allowSplitGrupoWithSegments(grupo, container, layoutCtx);
                return;
            }

            grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
        });
    }

    function applyBrowserPrintAreaSeparatorFix() {
        if (!usesGrupoIntactMode()) {
            return;
        }

        document.querySelectorAll('.report-pdf-grupo-prueba.report-pdf-grupo-browser-print-area:not(.report-pdf-grupo-prueba-first)').forEach(function(grupo) {
            grupo.style.setProperty('page-break-before', 'always', 'important');
            grupo.style.setProperty('break-before', 'page', 'important');
            grupo.style.setProperty('margin-top', '0', 'important');
            grupo.style.setProperty('padding-top', '0', 'important');
            grupo.style.setProperty('break-inside', 'avoid-page', 'important');
            grupo.style.setProperty('page-break-inside', 'avoid', 'important');
        });

        document.querySelectorAll('.report-pdf-grupo-prueba-first .report-pdf-grupo-area-start-table').forEach(function(table) {
            table.style.setProperty('page-break-before', 'avoid', 'important');
            table.style.setProperty('break-before', 'avoid', 'important');
            table.style.setProperty('break-inside', 'avoid', 'important');
            table.style.setProperty('page-break-inside', 'avoid', 'important');
            table.style.setProperty('break-after', 'avoid', 'important');
            table.style.setProperty('page-break-after', 'avoid', 'important');
        });

        document.querySelectorAll('.report-pdf-grupo-area-start-table .report-pdf-grupo-area-separator').forEach(function(sep) {
            sep.style.setProperty('margin-top', '0', 'important');
            sep.style.setProperty('padding-top', '6px', 'important');
            sep.style.setProperty('padding-bottom', '6px', 'important');
            sep.style.setProperty('break-inside', 'avoid', 'important');
            sep.style.setProperty('page-break-inside', 'avoid', 'important');
            sep.style.setProperty('box-shadow', 'none', 'important');
            sep.style.setProperty('visibility', 'visible', 'important');
            sep.style.setProperty('opacity', '1', 'important');
        });

        document.querySelectorAll('.report-pdf-grupo-area-page-leader').forEach(function(leader) {
            leader.style.setProperty('page-break-before', 'always', 'important');
            leader.style.setProperty('break-before', 'page', 'important');
        });

        document.querySelectorAll('.report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-first) > .report-pdf-grupo-area-separator').forEach(function(sep) {
            sep.style.setProperty('margin-top', '0', 'important');
            sep.style.setProperty('padding-top', '6px', 'important');
            sep.style.setProperty('box-shadow', 'none', 'important');
        });
    }

    function clearBrowserPrintAreaSeparatorFix() {
        document.querySelectorAll('.report-pdf-grupo-prueba.report-pdf-grupo-browser-print-area:not(.report-pdf-grupo-prueba-first)').forEach(function(grupo) {
            grupo.style.removeProperty('page-break-before');
            grupo.style.removeProperty('break-before');
        });
        document.querySelectorAll('.report-pdf-grupo-area-start-table').forEach(function(table) {
            table.style.removeProperty('page-break-before');
            table.style.removeProperty('break-before');
            table.style.removeProperty('break-inside');
            table.style.removeProperty('page-break-inside');
            table.style.removeProperty('break-after');
            table.style.removeProperty('page-break-after');
        });
        document.querySelectorAll('.report-pdf-grupo-area-start-table .report-pdf-grupo-area-separator').forEach(function(sep) {
            sep.style.removeProperty('margin-top');
            sep.style.removeProperty('padding-top');
            sep.style.removeProperty('padding-bottom');
            sep.style.removeProperty('break-inside');
            sep.style.removeProperty('page-break-inside');
            sep.style.removeProperty('box-shadow');
            sep.style.removeProperty('visibility');
            sep.style.removeProperty('opacity');
        });
    }

    function applyPageBreakRules() {
        clearPageBreakAdjustments();
        var container = (paginationApi() && paginationApi().getPrintContainer)
            ? paginationApi().getPrintContainer()
            : (document.querySelector('.pdf-main-stack') || document.body);

        var layoutCtx = getLayoutContext(container);
        if (!layoutCtx || !isFinite(layoutCtx.maxSlicePx) || layoutCtx.maxSlicePx <= 0) {
            if (usesGrupoIntactMode()) {
                applyGrupoInterPageBreaks(container);
                applyBrowserPrintAreaSeparatorFix();
            }
            return;
        }

        if (!cfg.mode || cfg.mode === 'flow') {
            applyCabeceraSegmentIntegrity(container, layoutCtx);
        } else if (cfg.mode === 'keep_segment') {
            applySegmentPageBreaks(container, layoutCtx);
        } else if (cfg.mode === 'keep_together_if_fits') {
            applyIfFitsMode(container, layoutCtx);
        } else if (cfg.mode === 'keep_together_if_fits_auto_order') {
            reorderGruposForAutoPack(container, layoutCtx);
            applyIfFitsMode(container, layoutCtx);
        } else if (!usesGrupoIntactMode()) {
            applyCabeceraSegmentIntegrity(container, layoutCtx);
            applyGrupoPageBreaks(container, layoutCtx);
        } else {
            // Impresión navegador + grupo íntegro: salto por área + medición para no dejar título huérfano.
            applyBrowserPrintGrupoIntactPageBreaks(container, layoutCtx);
        }

        applyLabFirmasPageBreaks(container, layoutCtx);

        if (usesGrupoIntactMode()) {
            applyGrupoInterPageBreaks(container);
            applyBrowserPrintAreaSeparatorFix();
        }
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
