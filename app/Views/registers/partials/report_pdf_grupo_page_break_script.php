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
    var ANALYSIS_UNIT_SELECTOR = '.report-pdf-subgrupo-block';
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

    function subgrupoKeepIntactForNode(node) {
        var subgrupo = subgrupoBlockForNode(node);
        return !!(subgrupo && subgrupo.classList && subgrupo.classList.contains('report-subgrupo-keep-intact'));
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

    function orderedSubgrupoBlocksInGrupo(grupo) {
        if (!grupo) {
            return [];
        }
        return Array.from(grupo.querySelectorAll(':scope > .report-pdf-subgrupo-block'));
    }

    function keepIntactSubgrupoBlocksInGrupo(grupo) {
        return orderedSubgrupoBlocksInGrupo(grupo).filter(function(block) {
            return block.classList && block.classList.contains('report-subgrupo-keep-intact');
        });
    }

    function isSubgrupoAtOrAfterInGrupo(subgrupo, anchor, grupo) {
        var blocks = orderedSubgrupoBlocksInGrupo(grupo);
        var subIdx = blocks.indexOf(subgrupo);
        var anchorIdx = blocks.indexOf(anchor);
        return subIdx >= 0 && anchorIdx >= 0 && subIdx >= anchorIdx;
    }

    /**
     * Primer subgrupo keep-intact del bloque final (p. ej. GGT antes de FOSFATASA + firma).
     */
    function tailBreakAnchorSubgrupo(grupo) {
        if (!grupo) {
            return null;
        }
        var keepIntactBlocks = keepIntactSubgrupoBlocksInGrupo(grupo);
        if (!keepIntactBlocks.length) {
            return null;
        }

        var lastUnit = lastAnalysisUnitInGrupo(grupo);
        if (!lastUnit) {
            return keepIntactBlocks[keepIntactBlocks.length - 1];
        }

        var lastSub = lastUnit.classList && lastUnit.classList.contains('report-pdf-subgrupo-block')
            ? lastUnit
            : subgrupoBlockForNode(lastUnit);
        if (!lastSub || !lastSub.classList.contains('report-subgrupo-keep-intact')) {
            return keepIntactBlocks[keepIntactBlocks.length - 1];
        }

        var idx = keepIntactBlocks.indexOf(lastSub);
        if (idx < 0) {
            return keepIntactBlocks[keepIntactBlocks.length - 1];
        }
        if (idx === 0) {
            return keepIntactBlocks[0];
        }

        if (labFirmaBlockInGrupo(grupo)) {
            return keepIntactBlocks[idx - 1];
        }

        return lastSub;
    }

    function previousKeepIntactSubgrupoSibling(subgrupo) {
        if (!subgrupo || !subgrupo.parentNode) {
            return null;
        }
        var prev = subgrupo.previousElementSibling;
        while (prev) {
            if (prev.classList && prev.classList.contains('report-pdf-subgrupo-block')) {
                return prev.classList.contains('report-subgrupo-keep-intact') ? prev : null;
            }
            prev = prev.previousElementSibling;
        }
        return null;
    }

    function markForceBreakBeforeSubgrupoBlock(subgrupo) {
        if (!subgrupo) {
            return;
        }
        var grupo = subgrupo.closest('.report-pdf-grupo-prueba');
        var target = subgrupo;

        if (grupo) {
            var anchor = tailBreakAnchorSubgrupo(grupo);
            if (anchor) {
                if (isSubgrupoAtOrAfterInGrupo(subgrupo, anchor, grupo)) {
                    target = anchor;
                }
            } else {
                var prevKi = previousKeepIntactSubgrupoSibling(subgrupo);
                if (prevKi) {
                    target = prevKi;
                }
            }
        }

        if (target.classList.contains('report-subgrupo-force-break-before')) {
            return;
        }
        target.classList.add('report-subgrupo-force-break-before');
        var cabecera = target.querySelector('.report-pdf-grupo-cabecera');
        if (cabecera) {
            cabecera.classList.add('report-cabecera-force-break-before');
        }
    }

    function markForceBreakBeforeCabecera(cabecera) {
        if (!cabecera) {
            return;
        }
        var subgrupo = subgrupoBlockForNode(cabecera);
        if (subgrupo) {
            markForceBreakBeforeSubgrupoBlock(subgrupo);
            return;
        }
        cabecera.classList.add('report-cabecera-force-break-before');
    }

    function elementHeight(el) {
        if (!el) {
            return 0;
        }
        var h = el.offsetHeight || 0;
        if ((!isFinite(h) || h <= 0) && el.getBoundingClientRect) {
            h = el.getBoundingClientRect().height || 0;
        }
        return Math.max(0, h);
    }

    function elementVerticalMarginsPx(el) {
        if (!el || !window.getComputedStyle) {
            return { top: 0, bottom: 0 };
        }
        var st = getComputedStyle(el);
        return {
            top: parseFloat(st.marginTop) || 0,
            bottom: parseFloat(st.marginBottom) || 0
        };
    }

    function elementOuterHeight(el) {
        if (!el) {
            return 0;
        }
        var margins = elementVerticalMarginsPx(el);
        return elementHeight(el) + margins.top + margins.bottom;
    }

    function mmToPx(mm) {
        var api = paginationApi();
        if (api && typeof api.mmToPx === 'function') {
            return api.mmToPx(mm);
        }
        return (mm * 96) / 25.4;
    }

    function layoutFooterSafetyBufferPx(layoutCtx) {
        var base = mmToPx(3);
        if (!cfg.footerEnabled) {
            return base;
        }
        var api = paginationApi();
        var reserveMm = (layoutCtx.metrics && layoutCtx.metrics.footerReserveMM) || cfg.footerReserveMm || 0;
        var reservePx = mmToPx(reserveMm);
        var measuredPx = api && typeof api.measureFooterHeightPx === 'function'
            ? (api.measureFooterHeightPx() || 0)
            : 0;
        return base + Math.max(0, measuredPx - reservePx + mmToPx(2));
    }

    function lastPlacementNodeInGrupo(grupo) {
        var nodes = placementNodesInGrupo(grupo);
        return nodes.length ? nodes[nodes.length - 1] : null;
    }

    function isKeepIntactTailBeforeFirma(subgrupo, seg) {
        if (!subgrupo || !subgrupo.classList || !subgrupo.classList.contains('report-subgrupo-keep-intact')) {
            return false;
        }
        var grupo = subgrupo.closest('.report-pdf-grupo-prueba');
        if (!grupo || !labFirmaBlockInGrupo(grupo)) {
            return false;
        }
        var lastNode = lastPlacementNodeInGrupo(grupo);
        return !!(lastNode && (lastNode === seg || subgrupo.contains(lastNode)));
    }

    function firmaPullTargetUnit(grupoEl, block, container, layoutCtx) {
        var node = lastPlacementNodeInGrupo(grupoEl);
        if (!node) {
            return null;
        }
        var subgrupo = subgrupoBlockForNode(node);
        if (!subgrupo) {
            return node;
        }
        if (subgrupo.classList.contains('report-subgrupo-keep-intact')) {
            var firmaOuterH = block ? elementOuterHeight(block) : 0;
            var subH = analysisUnitHeight(subgrupo, container);
            if (firmaOuterH <= 0 || subH + firmaOuterH > layoutCtx.maxSlicePx) {
                return node;
            }
            return subgrupo;
        }
        if (subgrupo.querySelector('.report-pdf-grupo-cabecera')) {
            return subgrupo;
        }
        return node;
    }

    function firmaTailMetrics(grupoEl, block, container, layoutCtx) {
        var target = firmaPullTargetUnit(grupoEl, block, container, layoutCtx);
        if (!target) {
            return null;
        }
        var unitTop = analysisUnitTop(target, container);
        var unitH = analysisUnitHeight(target, container);
        var firmaOuterH = block ? elementOuterHeight(block) : 0;
        var firmaTop = block ? topWithinContainer(block, container) : (unitTop + unitH);
        var gap = block ? Math.max(0, firmaTop - (unitTop + unitH)) : 0;
        return {
            target: target,
            unitTop: unitTop,
            unitH: unitH,
            firmaOuterH: firmaOuterH,
            gap: gap,
            combined: unitH + gap + firmaOuterH
        };
    }

    function grupoTitleBlock(grupo) {
        if (!grupo || !grupo.querySelector) {
            return null;
        }
        var direct = grupo.querySelector(':scope > .report-pdf-grupo-area-start-table');
        if (direct) {
            return direct;
        }
        direct = grupo.querySelector(':scope > .report-pdf-grupo-area-separator');
        return direct || null;
    }

    function analysisUnitsInGrupo(grupo) {
        if (!grupo) {
            return [];
        }
        var subgrupos = grupo.querySelectorAll(ANALYSIS_UNIT_SELECTOR);
        if (subgrupos.length) {
            return Array.from(subgrupos);
        }
        var segs = [];
        grupo.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            if (!subgrupoBlockForNode(seg)) {
                segs.push(seg);
            }
        });
        if (segs.length) {
            return segs;
        }
        return Array.from(grupo.querySelectorAll(SEGMENT_SELECTOR));
    }

    function firstAnalysisUnit(grupo) {
        var units = analysisUnitsInGrupo(grupo);
        return units.length ? units[0] : null;
    }

    function lastAnalysisUnitInGrupo(grupo) {
        var units = analysisUnitsInGrupo(grupo);
        return units.length ? units[units.length - 1] : null;
    }

    function placementNodesInGrupo(grupo) {
        var nodes = [];
        if (!grupo) {
            return nodes;
        }
        Array.from(grupo.children).forEach(function(child) {
            if (!child || !child.classList) {
                return;
            }
            if (child.classList.contains('report-lab-firma-grupo-inline')
                || child.classList.contains('lab-firmas-pdf-block-global')) {
                return;
            }
            if (child.classList.contains('report-pdf-grupo-area-start-table')
                || child.classList.contains('report-pdf-grupo-area-separator')
                || child.classList.contains('report-pdf-grupo-area-page-leader')) {
                return;
            }
            if (child.classList.contains('report-pdf-subgrupo-block')) {
                var segs = child.querySelectorAll(SEGMENT_SELECTOR);
                if (segs.length) {
                    Array.from(segs).forEach(function(seg) {
                        nodes.push(seg);
                    });
                } else {
                    nodes.push(child);
                }
                return;
            }
            if (child.classList.contains('report-segment-table-wrap')
                || child.classList.contains('report-refs-matrix-wrap')) {
                nodes.push(child);
            }
        });
        return nodes;
    }

    function analysisUnitTop(unit, container) {
        if (!unit) {
            return 0;
        }
        if (unit.classList && unit.classList.contains('report-pdf-subgrupo-block')) {
            var cabecera = unit.querySelector('.report-pdf-grupo-cabecera');
            return topWithinContainer(cabecera || unit, container);
        }
        return segmentUnitMetrics(unit, container).top;
    }

    function analysisUnitHeight(unit, container) {
        if (!unit) {
            return 0;
        }
        if (unit.classList && unit.classList.contains('report-pdf-subgrupo-block')) {
            return elementHeight(unit);
        }
        return segmentUnitMetrics(unit, container).height;
    }

    function grupoHeadMinHeight(grupo, container) {
        var height = 0;
        var title = grupoTitleBlock(grupo);
        if (title) {
            height += elementHeight(title);
        }
        var first = firstAnalysisUnit(grupo);
        if (first) {
            height += analysisUnitHeight(first, container);
        }
        return height;
    }

    function analysisFirmaPageLeaderFor(unit) {
        if (!unit) {
            return null;
        }
        var prev = unit.previousElementSibling;
        if (prev && prev.classList && prev.classList.contains('report-analysis-firma-page-leader')) {
            return prev;
        }
        return null;
    }

    function clearAnalysisFirmaPageLeader(unit) {
        var leader = analysisFirmaPageLeaderFor(unit);
        if (leader && leader.parentNode) {
            leader.parentNode.removeChild(leader);
        }
    }

    function ensureAnalysisFirmaPageLeaderBefore(unit) {
        if (!unit || !unit.parentNode) {
            return null;
        }
        var leader = analysisFirmaPageLeaderFor(unit);
        if (leader) {
            return leader;
        }
        leader = document.createElement('div');
        leader.className = 'report-analysis-firma-page-leader report-lab-firma-page-leader';
        leader.setAttribute('aria-hidden', 'true');
        unit.parentNode.insertBefore(leader, unit);
        return leader;
    }

    function pullAnalysisAndFirmaToNextPage(grupo, unit, container) {
        if (!unit) {
            return;
        }
        if (grupo
            && grupo.classList.contains('report-pdf-grupo-prueba-keep-on-page')
            && !grupo.classList.contains('report-pdf-grupo-prueba-split-segments-only')) {
            return;
        }
        ensureGrupoAllowsFirmaSegmentBreak(grupo, 'pullAnalysisAndFirmaToNextPage');

        var isSubgrupo = unit.classList && unit.classList.contains('report-pdf-subgrupo-block');
        var subgrupo = isSubgrupo ? unit : subgrupoBlockForNode(unit);

        if (subgrupo) {
            subgrupo.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
                seg.classList.remove('report-segment-force-break-before');
                clearAnalysisFirmaPageLeader(seg);
            });
        }

        if (isSubgrupo) {
            markForceBreakBeforeAnalysisUnit(unit, container);
            return;
        }

        if (subgrupo && subgrupo.querySelector('.report-pdf-grupo-cabecera')) {
            markForceBreakBeforeSubgrupoBlock(subgrupo);
            return;
        }

        if (subgrupo) {
            subgrupo.classList.remove('report-subgrupo-force-break-before');
            var parentCabecera = subgrupo.querySelector('.report-pdf-grupo-cabecera');
            if (parentCabecera) {
                parentCabecera.classList.remove('report-cabecera-force-break-before');
            }
        }

        unit.classList.remove('report-segment-force-break-before');
        ensureAnalysisFirmaPageLeaderBefore(unit);
    }

    function markForceBreakBeforeAnalysisUnit(unit, container) {
        if (!unit) {
            return;
        }
        if (unit.classList && unit.classList.contains('report-pdf-subgrupo-block')) {
            markForceBreakBeforeSubgrupoBlock(unit);
            return;
        }
        markForceBreakBeforeSegmentUnit(unit, container);
    }

    function clearAnalysisUnitBreaksInGrupo(grupo) {
        if (!grupo) {
            return;
        }
        grupo.querySelectorAll(ANALYSIS_UNIT_SELECTOR).forEach(function(unit) {
            unit.classList.remove('report-subgrupo-force-break-before');
        });
        grupo.querySelectorAll('.report-pdf-grupo-cabecera').forEach(function(cabecera) {
            cabecera.classList.remove('report-cabecera-force-break-before');
        });
    }

    function sealKeepOnPageGrupoIntact(grupo) {
        if (!grupo
            || !grupo.classList.contains('report-pdf-grupo-prueba-keep-on-page')
            || grupo.classList.contains('report-pdf-grupo-prueba-split-segments-only')
            || grupo.classList.contains('report-pdf-grupo-prueba-allow-split')) {
            return;
        }
        clearSegmentBreaksInGrupo(grupo);
        var inlineFirma = labFirmaBlockInGrupo(grupo);
        if (inlineFirma) {
            clearLabFirmaPageLeader(inlineFirma);
        }
        grupo.querySelectorAll('.report-pdf-subgrupo-block').forEach(function(subgrupo) {
            subgrupo.style.setProperty('break-before', 'avoid', 'important');
            subgrupo.style.setProperty('page-break-before', 'avoid', 'important');
            subgrupo.style.setProperty('break-inside', 'avoid', 'important');
            subgrupo.style.setProperty('page-break-inside', 'avoid', 'important');
        });
        grupo.querySelectorAll('.report-lab-firma-grupo-inline').forEach(function(firma) {
            firma.style.setProperty('break-before', 'avoid', 'important');
            firma.style.setProperty('page-break-before', 'avoid', 'important');
            firma.style.setProperty('break-inside', 'avoid', 'important');
            firma.style.setProperty('page-break-inside', 'avoid', 'important');
        });
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

    function usesGrupoInterPageBreakMode() {
        return usesGrupoIntactMode()
            || cfg.mode === 'keep_together_if_fits'
            || cfg.mode === 'keep_together_if_fits_auto_order';
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
        clearAnalysisUnitBreaksInGrupo(grupo);
        grupo.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            seg.classList.remove('report-segment-force-break-before', 'report-segment-allow-split');
        });
        grupo.querySelectorAll('.report-pdf-grupo-cabecera').forEach(function(cabecera) {
            cabecera.classList.remove('report-cabecera-force-break-before');
        });
        grupo.querySelectorAll('.report-pdf-subgrupo-block').forEach(function(subgrupo) {
            subgrupo.classList.remove('report-subgrupo-force-break-before');
        });
        grupo.querySelectorAll('.report-analysis-firma-page-leader').forEach(function(leader) {
            leader.remove();
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

    function clearInterGrupoPageBreaks(container) {
        var root = container || document;
        root.querySelectorAll('.report-grupo-inter-page-break:not(.report-grupo-inter-page-break-server)').forEach(function(node) {
            node.remove();
        });
    }

    function clearResultsTableContinuationHeaders() {
        document.querySelectorAll('tr.report-results-thead-continuation-injected').forEach(function(tr) {
            if (tr.parentNode) {
                tr.parentNode.removeChild(tr);
            }
        });
    }

    function pageIndexForTop(topPx, boundarySet) {
        var boundaries = boundarySet && boundarySet.boundaries ? boundarySet.boundaries : [];
        if (!isFinite(topPx) || topPx < 0) {
            topPx = 0;
        }
        for (var i = 0; i < boundaries.length; i++) {
            var pageStart = i === 0 ? 0 : boundaries[i - 1];
            var pageEnd = boundaries[i];
            if (topPx >= pageStart && topPx < pageEnd) {
                return i;
            }
        }
        return boundaries.length > 0 ? boundaries.length : 0;
    }

    function tableResultsCanSplit(table) {
        if (!table || !table.classList || !table.classList.contains('results')) {
            return false;
        }
        var grupo = table.closest('.report-pdf-grupo-prueba');
        if (grupo && (
            grupo.classList.contains('report-pdf-grupo-prueba-split-segments-only')
            || grupo.classList.contains('report-pdf-grupo-prueba-allow-split')
        )) {
            return true;
        }
        var wrap = table.closest('.report-segment-table-wrap, .report-refs-matrix-wrap');
        return !!(wrap && wrap.classList.contains('report-segment-allow-split'));
    }

    function buildResultsTheadContinuationRow(thead) {
        if (!thead) {
            return null;
        }
        var srcTr = thead.querySelector('tr');
        if (!srcTr) {
            return null;
        }
        var tr = srcTr.cloneNode(true);
        tr.classList.add('report-results-thead-continuation-injected');
        tr.setAttribute('aria-hidden', 'true');
        return tr;
    }

    function releaseAllowSplitSegmentWrapStyles(container) {
        container.querySelectorAll(
            '.report-segment-table-wrap.report-segment-allow-split, .report-refs-matrix-wrap.report-segment-allow-split'
        ).forEach(function(wrap) {
            wrap.style.removeProperty('break-inside');
            wrap.style.removeProperty('page-break-inside');
        });
    }

    function applyResultsTableContinuationHeaders(container, layoutCtx) {
        if (!cfg.repeatHeader || !container || !layoutCtx || !layoutCtx.boundarySet) {
            return;
        }

        clearResultsTableContinuationHeaders();

        var boundarySet = layoutCtx.boundarySet;
        container.querySelectorAll('table.results').forEach(function(table) {
            if (!tableResultsCanSplit(table)) {
                return;
            }

            var thead = table.querySelector('thead');
            var tbody = table.querySelector('tbody');
            if (!thead || !tbody) {
                return;
            }

            var rows = Array.from(tbody.querySelectorAll(':scope > tr')).filter(function(tr) {
                return !tr.classList.contains('report-results-thead-continuation-injected');
            });
            if (rows.length < 2) {
                return;
            }

            for (var i = 1; i < rows.length; i++) {
                var row = rows[i];
                var prev = rows[i - 1];
                var prevBottom = topWithinContainer(prev, container) + (prev.offsetHeight || 0);
                var rowTop = topWithinContainer(row, container);
                if (pageIndexForTop(rowTop, boundarySet) <= pageIndexForTop(prevBottom - 1, boundarySet)) {
                    continue;
                }

                var headerRow = buildResultsTheadContinuationRow(thead);
                if (!headerRow) {
                    continue;
                }
                tbody.insertBefore(headerRow, row);
                table.classList.add('report-results-split-active');
                rows.splice(i, 0, headerRow);
                i++;
            }
        });
    }

    function clearPageBreakAdjustments() {
        restoreGrupoDomOrder();
        clearBrowserPrintAreaSeparatorFix();
        clearInterGrupoPageBreaks();
        clearResultsTableContinuationHeaders();
        document.querySelectorAll('table.results.report-results-split-active').forEach(function(table) {
            table.classList.remove('report-results-split-active');
        });
        document.querySelectorAll('.report-pdf-grupo-cabecera').forEach(function(cabecera) {
            cabecera.classList.remove('report-cabecera-force-break-before');
        });
        document.querySelectorAll('.report-pdf-subgrupo-block').forEach(function(subgrupo) {
            subgrupo.classList.remove('report-subgrupo-force-break-before');
            subgrupo.style.removeProperty('break-before');
            subgrupo.style.removeProperty('page-break-before');
            subgrupo.style.removeProperty('break-inside');
            subgrupo.style.removeProperty('page-break-inside');
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
            clearAnalysisFirmaPageLeader(seg);
        });
        document.querySelectorAll(LAB_FIRMA_SELECTOR).forEach(function(block) {
            clearLabFirmaPageLeader(block);
            block.classList.remove('report-pdf-grupo-page-end-seal');
            block.style.removeProperty('break-after');
            block.style.removeProperty('page-break-after');
            block.style.removeProperty('break-before');
            block.style.removeProperty('page-break-before');
            block.style.removeProperty('break-inside');
            block.style.removeProperty('page-break-inside');
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

    function lastPruebaAnalysisUnitInGrupo(grupo) {
        if (!grupo || !grupo.querySelector) {
            return null;
        }
        var unit = grupo.querySelector(':scope > .report-pdf-subgrupo-block.report-pdf-subgrupo-prueba:last-of-type');
        if (unit) {
            return unit;
        }
        return lastAnalysisUnitInGrupo(grupo);
    }

    function grupoNombreFromGrupo(grupo) {
        var title = grupoTitleBlock(grupo);
        if (!title) {
            return '(sin nombre)';
        }
        var sep = title.querySelector('.report-pdf-grupo-area-separator');
        if (sep) {
            return (sep.textContent || '').trim();
        }
        return (title.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 120);
    }

    function resolveCursorAtTop(topPx, boundarySet, metrics) {
        var boundaries = boundarySet && boundarySet.boundaries ? boundarySet.boundaries : [];
        if (!isFinite(topPx) || topPx < 0) {
            topPx = 0;
        }
        for (var i = 0; i < boundaries.length; i++) {
            var pageStart = i === 0 ? 0 : boundaries[i - 1];
            var pageEnd = boundaries[i];
            if (topPx >= pageStart && topPx < pageEnd) {
                return { page: i, y: topPx };
            }
        }
        var lastEnd = boundaries.length ? boundaries[boundaries.length - 1] : 0;
        if (topPx >= lastEnd) {
            var nextSlice = metrics && metrics.nextPageContentPx ? metrics.nextPageContentPx : 0;
            if (isFinite(nextSlice) && nextSlice > 0) {
                var overflow = topPx - lastEnd;
                var extraPages = Math.floor(overflow / nextSlice);
                return {
                    page: boundaries.length + extraPages,
                    y: lastEnd + (overflow % nextSlice)
                };
            }
        }
        return { page: 0, y: topPx };
    }

    function makePlacementCursor(page, y) {
        return { page: page, y: y };
    }

    function espacioRestanteEnPaginaActual(cursor, layoutCtx) {
        var boundarySet = layoutCtx.boundarySet;
        var metrics = layoutCtx.metrics;
        return pageEndPx(cursor.page, boundarySet, metrics) - cursor.y;
    }

    function forceCursorToNextPage(cursor, layoutCtx) {
        var boundarySet = layoutCtx.boundarySet;
        return makePlacementCursor(cursor.page + 1, pageStartPx(cursor.page + 1, boundarySet));
    }

    function markGrupoAllowSplit(grupo, origen) {
        if (grupo && grupo.classList) {
            grupo.classList.add('report-pdf-grupo-prueba-allow-split');
        }
    }

    function ensureGrupoAllowsFirmaSegmentBreak(grupo, origen) {
        if (!grupo || !grupo.classList) {
            return;
        }
        if (grupo.classList.contains('report-pdf-grupo-prueba-keep-on-page')
            && !grupo.classList.contains('report-pdf-grupo-prueba-split-segments-only')) {
            return;
        }
        if (grupo.classList.contains('report-pdf-grupo-prueba-keep-on-page')) {
            grupo.classList.remove('report-pdf-grupo-prueba-keep-on-page');
            markGrupoAllowSplit(grupo, origen || 'ensureGrupoAllowsFirmaSegmentBreak');
        }
    }

    function placeSegmentOnCursor(cursor, seg, layoutCtx, state) {
        var metrics = layoutCtx.metrics;
        var boundarySet = layoutCtx.boundarySet;
        var maxSlicePx = layoutCtx.maxSlicePx;
        var markBreaks = state.markBreaks !== false;
        var container = state.container || null;
        var page = cursor.page;
        var y = cursor.y;

        var cabecera = cabeceraForSegment(seg);
        var subgrupo = subgrupoBlockForNode(seg);
        if (subgrupo !== state.lastSubgrupo) {
            state.lastSubgrupo = subgrupo;
            state.cabeceraCounted = false;
        }

        var cabeceraH = (cabecera && !state.cabeceraCounted) ? (cabecera.offsetHeight || 0) : 0;
        if (cabecera && !state.cabeceraCounted) {
            state.cabeceraCounted = true;
        }
        var unitH = cabeceraH + (seg.offsetHeight || 0);
        var remaining = pageEndPx(page, boundarySet, metrics) - y;

        if (markBreaks) {
            seg.classList.remove('report-segment-force-break-before', 'report-segment-allow-split');
            if (cabecera) {
                cabecera.classList.remove('report-cabecera-force-break-before');
                if (subgrupo) {
                    subgrupo.classList.remove('report-subgrupo-force-break-before');
                }
            }
        }

        if (unitH > maxSlicePx) {
            if (!subgrupoKeepIntactForNode(seg) && markBreaks) {
                seg.classList.add('report-segment-allow-split');
            }
        } else if (unitH <= remaining) {
            // Cabe en la hoja simulada actual.
        } else if (atPageResultsStart(page, y, boundarySet, metrics)) {
            if (!subgrupoKeepIntactForNode(seg) && markBreaks) {
                seg.classList.add('report-segment-allow-split');
            }
        } else if (markBreaks) {
            if (subgrupo && subgrupo.classList.contains('report-subgrupo-keep-intact')) {
                if (!isKeepIntactTailBeforeFirma(subgrupo, seg)) {
                    markForceBreakBeforeAnalysisUnit(subgrupo, container);
                }
            } else if (cabecera) {
                markForceBreakBeforeCabecera(cabecera);
            } else {
                seg.classList.add('report-segment-force-break-before');
            }
            page++;
            y = pageStartPx(page, boundarySet);
            remaining = pageEndPx(page, boundarySet, metrics) - y;
            if (!subgrupoKeepIntactForNode(seg) && (unitH > remaining || unitH > maxSlicePx)) {
                seg.classList.add('report-segment-allow-split');
            }
        } else {
            page++;
            y = pageStartPx(page, boundarySet);
        }

        return bumpCursorAfterPlace(page, y, unitH, boundarySet, metrics);
    }

    function placeOversizedAnalysisUnitOnCursor(cursor, unit, container, layoutCtx, markBreaks) {
        if (unit.classList && unit.classList.contains('report-pdf-subgrupo-block')
            && unit.classList.contains('report-subgrupo-keep-intact')) {
            var unitH = analysisUnitHeight(unit, container);
            var maxSlicePx = layoutCtx.maxSlicePx;
            var boundarySet = layoutCtx.boundarySet;
            var metrics = layoutCtx.metrics;
            var espacioKi = espacioRestanteEnPaginaActual(cursor, layoutCtx);
            if (markBreaks && unitH > espacioKi && unitH <= maxSlicePx * 1.02) {
                markForceBreakBeforeAnalysisUnit(unit, container);
                var cursorKi = forceCursorToNextPage(cursor, layoutCtx);
                return bumpCursorAfterPlace(cursorKi.page, cursorKi.y, unitH, boundarySet, metrics);
            }
            return bumpCursorAfterPlace(cursor.page, cursor.y, unitH, boundarySet, metrics);
        }
        if (!unit.classList || !unit.classList.contains('report-pdf-subgrupo-block')) {
            if (markBreaks) {
                unit.classList.add('report-segment-allow-split');
            }
            return bumpCursorAfterPlace(
                cursor.page,
                cursor.y,
                analysisUnitHeight(unit, container),
                layoutCtx.boundarySet,
                layoutCtx.metrics
            );
        }

        var state = {
            lastSubgrupo: null,
            cabeceraCounted: false,
            markBreaks: markBreaks
        };
        unit.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            cursor = placeSegmentOnCursor(cursor, seg, layoutCtx, state);
        });
        return cursor;
    }

    function placeAnalysisUnitOnCursor(cursor, unit, container, layoutCtx, opts) {
        opts = opts || {};
        var isLast = !!opts.isLast;
        var firmaH = opts.firmaH || 0;
        var grupoNombre = opts.grupoNombre || '';
        var markBreaks = opts.markBreaks !== false;
        var maxSlicePx = layoutCtx.maxSlicePx;
        var boundarySet = layoutCtx.boundarySet;
        var metrics = layoutCtx.metrics;
        var unitH = analysisUnitHeight(unit, container);

        if (markBreaks) {
            clearAnalysisUnitSplitMarks(unit);
        }

        if (isLast && firmaH > 0) {
            var firmaOuterH = opts.firmaNode ? elementOuterHeight(opts.firmaNode) : firmaH;
            var footerBuffer = layoutFooterSafetyBufferPx(layoutCtx);
            var espacioRestante = espacioRestanteEnPaginaActual(cursor, layoutCtx) - footerBuffer;
            var firmaNode = opts.firmaNode || null;
            var grupoEl = opts.grupo || null;
            var tail = (firmaNode && grupoEl)
                ? firmaTailMetrics(grupoEl, firmaNode, container, layoutCtx)
                : null;
            var pullUnit = tail ? tail.target : unit;
            var bloqueConFirma = tail ? tail.combined : (unitH + firmaOuterH);
            var measureTop = tail ? tail.unitTop : analysisUnitTop(unit, container);
            var measureUnitH = tail ? tail.unitH : unitH;
            var decision;
            var nextCursor;

            if (firmaNode && container) {
                var firmaTopDom = topWithinContainer(firmaNode, container);
                var firmaBottomDom = firmaTopDom + elementOuterHeight(firmaNode);
                var pageEndDom = measureTop + remainingOnPage(measureTop, boundarySet);
                if (firmaBottomDom > pageEndDom - footerBuffer && bloqueConFirma <= maxSlicePx) {
                    decision = 'mover_junto_con_firma_dom';
                    if (markBreaks) {
                        pullAnalysisAndFirmaToNextPage(grupoEl, pullUnit, container);
                    }
                    var nuevaPaginaDom = forceCursorToNextPage(cursor, layoutCtx);
                    nextCursor = bumpCursorAfterPlace(
                        nuevaPaginaDom.page, nuevaPaginaDom.y, bloqueConFirma, boundarySet, metrics
                    );
                    return nextCursor;
                }
            }

            if (bloqueConFirma > maxSlicePx) {
                decision = 'mantener';
                if (markBreaks) {
                    var subBlk = subgrupoBlockForNode(unit);
                    if (subBlk && subBlk.querySelector('.report-pdf-grupo-cabecera')
                        && !atPageResultsStart(cursor.page, cursor.y, boundarySet, metrics)) {
                        markForceBreakBeforeSubgrupoBlock(subBlk);
                        cursor = forceCursorToNextPage(cursor, layoutCtx);
                    }
                    nextCursor = placeOversizedAnalysisUnitOnCursor(cursor, unit, container, layoutCtx, true);
                } else {
                    nextCursor = bumpCursorAfterPlace(cursor.page, cursor.y, unitH, boundarySet, metrics);
                }
            } else if (bloqueConFirma <= espacioRestante) {
                decision = 'mantener';
                nextCursor = bumpCursorAfterPlace(cursor.page, cursor.y, bloqueConFirma, boundarySet, metrics);
            } else {
                decision = 'mover_junto_con_firma';
                if (markBreaks) {
                    pullAnalysisAndFirmaToNextPage(grupoEl, pullUnit, container);
                }
                var nuevaPagina = forceCursorToNextPage(cursor, layoutCtx);
                nextCursor = bumpCursorAfterPlace(nuevaPagina.page, nuevaPagina.y, bloqueConFirma, boundarySet, metrics);
            }

            return nextCursor;
        }

        var placementH = unitH;
        if (unitH > maxSlicePx) {
            if (unit.classList && unit.classList.contains('report-subgrupo-keep-intact')) {
                var espacioKi = espacioRestanteEnPaginaActual(cursor, layoutCtx);
                if (unitH <= maxSlicePx * 1.02 && markBreaks && unitH > espacioKi) {
                    markForceBreakBeforeAnalysisUnit(unit, container);
                    var cursorKi = forceCursorToNextPage(cursor, layoutCtx);
                    return bumpCursorAfterPlace(cursorKi.page, cursorKi.y, unitH, boundarySet, metrics);
                }
            }
            return placeOversizedAnalysisUnitOnCursor(cursor, unit, container, layoutCtx, markBreaks);
        }

        var remaining = espacioRestanteEnPaginaActual(cursor, layoutCtx);
        if (placementH <= remaining) {
            return bumpCursorAfterPlace(cursor.page, cursor.y, placementH, boundarySet, metrics);
        }

        if (atPageResultsStart(cursor.page, cursor.y, boundarySet, metrics)) {
            if (markBreaks
                && !(unit.classList && unit.classList.contains('report-pdf-subgrupo-block'))
                && !(unit.classList && unit.classList.contains('report-subgrupo-keep-intact'))) {
                unit.classList.add('report-segment-allow-split');
            }
            return bumpCursorAfterPlace(cursor.page, cursor.y, placementH, boundarySet, metrics);
        }

        if (markBreaks) {
            var subForBreak = subgrupoBlockForNode(unit);
            if (subForBreak && subForBreak.querySelector('.report-pdf-grupo-cabecera')
                && !(unit.classList && unit.classList.contains('report-pdf-subgrupo-block'))) {
                markForceBreakBeforeSubgrupoBlock(subForBreak);
            } else {
                markForceBreakBeforeAnalysisUnit(unit, container);
            }
        }
        var paginaNueva = forceCursorToNextPage(cursor, layoutCtx);
        return bumpCursorAfterPlace(paginaNueva.page, paginaNueva.y, placementH, boundarySet, metrics);
    }

    function clearAnalysisUnitSplitMarks(unit) {
        if (!unit) {
            return;
        }
        unit.classList.remove('report-segment-allow-split');
        if (unit.classList && unit.classList.contains('report-pdf-subgrupo-block')) {
            unit.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
                seg.classList.remove('report-segment-allow-split');
            });
        }
    }

    function placeGrupoTitleOnCursor(cursor, grupo, layoutCtx) {
        var title = grupoTitleBlock(grupo);
        if (!title) {
            return cursor;
        }
        return bumpCursorAfterPlace(
            cursor.page,
            cursor.y,
            elementHeight(title),
            layoutCtx.boundarySet,
            layoutCtx.metrics
        );
    }

    function enforceGrupoHeadOnCursor(grupo, container, layoutCtx, cursor, isFirstGrupo) {
        if (isFirstGrupo) {
            return cursor;
        }
        var headMin = grupoHeadMinHeight(grupo, container);
        if (headMin <= 0 || headMin > layoutCtx.maxSlicePx) {
            return cursor;
        }
        if (headMin <= espacioRestanteEnPaginaActual(cursor, layoutCtx)) {
            return cursor;
        }
        var breaker = grupo.previousElementSibling;
        if (breaker && breaker.classList && breaker.classList.contains('report-grupo-inter-page-break')) {
            strengthenInterPageBreakNode(breaker);
        } else {
            markGrupoStartBreakBefore(grupo);
        }
        return forceCursorToNextPage(cursor, layoutCtx);
    }

    function applyGrupoFirmaPageBreaks(grupo) {
        var firma = labFirmaBlockInGrupo(grupo);
        if (firma) {
            clearLabFirmaPageLeader(firma);
        }
    }

    function simulateCursorBeforeUnit(grupo, container, layoutCtx, targetUnit, startCursor) {
        var cursor = startCursor
            ? makePlacementCursor(startCursor.page, startCursor.y)
            : makePlacementCursor(0, layoutCtx.metrics.headerHeightPx || 0);
        var units = analysisUnitsInGrupo(grupo);

        for (var i = 0; i < units.length; i++) {
            if (units[i] === targetUnit) {
                break;
            }
            cursor = placeAnalysisUnitOnCursor(cursor, units[i], container, layoutCtx, {
                markBreaks: false,
                firmaH: 0,
                isLast: false
            });
        }

        return cursor;
    }

    function inlineFirmaNeedsPageBreak(block, grupoEl, container, layoutCtx) {
        var tail = firmaTailMetrics(grupoEl, block, container, layoutCtx);
        if (!tail || !block) {
            return false;
        }

        var buffer = layoutFooterSafetyBufferPx(layoutCtx);
        var boundarySet = layoutCtx.boundarySet;
        var firmaTop = topWithinContainer(block, container);
        var firmaBottom = firmaTop + elementOuterHeight(block);
        var pageEnd = tail.unitTop + remainingOnPage(tail.unitTop, boundarySet);

        if (firmaBottom > pageEnd - buffer) {
            return true;
        }

        var remaining = remainingOnPage(tail.unitTop, boundarySet);
        return tail.combined > remaining - buffer;
    }

    function applyInlineGrupoFirmaPageBreak(block, grupoEl, container, layoutCtx) {
        if (!inlineFirmaNeedsPageBreak(block, grupoEl, container, layoutCtx)) {
            return;
        }

        var tail = firmaTailMetrics(grupoEl, block, container, layoutCtx);
        if (!tail || !tail.target) {
            return;
        }

        if (tail.combined > layoutCtx.maxSlicePx) {
            if (tail.unitH + tail.firmaOuterH <= layoutCtx.maxSlicePx) {
                pullAnalysisAndFirmaToNextPage(grupoEl, tail.target, container);
            }
            return;
        }

        pullAnalysisAndFirmaToNextPage(grupoEl, tail.target, container);
    }

    function lastGrupoInContainer(container) {
        var grupos = container ? container.querySelectorAll('.report-pdf-grupo-prueba') : [];
        return grupos.length ? grupos[grupos.length - 1] : null;
    }

    function lastGrupoIsKeepOnPageOnly(container) {
        var last = lastGrupoInContainer(container);
        return !!(last
            && last.classList.contains('report-pdf-grupo-prueba-keep-on-page')
            && !last.classList.contains('report-pdf-grupo-prueba-split-segments-only'));
    }

    function segmentInKeepOnPageGrupo(seg) {
        var grupo = seg && seg.closest ? seg.closest('.report-pdf-grupo-prueba') : null;
        return !!(grupo
            && grupo.classList.contains('report-pdf-grupo-prueba-keep-on-page')
            && !grupo.classList.contains('report-pdf-grupo-prueba-split-segments-only'));
    }

    function applyLabFirmasPageBreaks(container, layoutCtx) {
        var maxSlicePx = layoutCtx.maxSlicePx;
        var boundarySet = layoutCtx.boundarySet;

        container.querySelectorAll(LAB_FIRMA_SELECTOR).forEach(function(block) {
            var grupoEl = block.closest('.report-pdf-grupo-prueba');
            if (grupoEl && usesGrupoIntactMode()) {
                return;
            }

            clearLabFirmaPageLeader(block);

            if (!grupoEl && block.classList.contains('lab-firmas-pdf-block-global')
                && lastGrupoIsKeepOnPageOnly(container)) {
                return;
            }

            if (grupoEl && block.classList.contains('report-lab-firma-grupo-inline')) {
                if (grupoEl.classList.contains('report-pdf-grupo-prueba-keep-on-page')
                    && !grupoEl.classList.contains('report-pdf-grupo-prueba-split-segments-only')) {
                    clearLabFirmaPageLeader(block);
                    return;
                }
                if (!usesGrupoIntactMode()) {
                    applyInlineGrupoFirmaPageBreak(block, grupoEl, container, layoutCtx);
                }
                return;
            }

            if (grupoEl) {
                if ((cfg.mode === 'keep_together_if_fits' || cfg.mode === 'keep_together_if_fits_auto_order')
                    && grupoEl.classList.contains('report-pdf-grupo-prueba-allow-split')) {
                    return;
                }
            }

            var height = elementHeight(block);
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
            if (lastSeg && !segmentInKeepOnPageGrupo(lastSeg)) {
                var unit = segmentUnitMetrics(lastSeg, container);
                if ((unit.height + height) <= maxSlicePx) {
                    markForceBreakBeforeSegmentUnit(lastSeg, container);
                    pulled = true;
                }
            }

            if (!pulled && !segmentInKeepOnPageGrupo(lastSeg)) {
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

    function usesUnifiedCursorPlacement() {
        var mode = cfg.mode || '';
        return mode === 'keep_together'
            || mode === 'keep_together_compact'
            || mode === 'keep_together_if_fits'
            || mode === 'keep_together_if_fits_auto_order';
    }

    function remainingForGrupoPlacement(grupo, container, layoutCtx, cursor, isFirstGrupo) {
        var buffer = layoutFooterSafetyBufferPx(layoutCtx);
        if (isFirstGrupo) {
            return Math.max(0, espacioRestanteEnPaginaActual(cursor, layoutCtx) - buffer);
        }
        var top = topWithinContainer(grupo, container);
        return Math.max(0, remainingOnPage(top, layoutCtx.boundarySet) - buffer);
    }

    /**
     * Regla base: cada área (grupo prueba) empieza en hoja nueva salvo la primera.
     * Respeta .report-grupo-inter-page-break si ya existe (evita doble salto).
     */
    function forceGrupoToNewPageStart(grupo, cursor, layoutCtx) {
        if (grupo) {
            var breaker = grupo.previousElementSibling;
            if (breaker && breaker.classList && breaker.classList.contains('report-grupo-inter-page-break')) {
                strengthenInterPageBreakNode(breaker);
            } else {
                markGrupoStartBreakBefore(grupo);
            }
            grupo.classList.add('report-pdf-grupo-prueba-new-page-start');
        }
        return forceCursorToNextPage(cursor, layoutCtx);
    }

    function measureGrupoHeightForPlacement(grupo, layoutCtx) {
        var maxSlicePx = layoutCtx.maxSlicePx;
        var compactMode = cfg.mode === 'keep_together_compact';
        clearGrupoCompact(grupo);
        var height = grupo.offsetHeight || 0;
        if (compactMode && height > maxSlicePx) {
            applyCompactToGrupo(grupo);
            var compactH = grupo.offsetHeight || 0;
            if (compactH <= maxSlicePx * 1.02) {
                return { height: compactH, compact: true };
            }
            clearGrupoCompact(grupo);
            height = grupo.offsetHeight || 0;
        }
        return { height: height, compact: false };
    }

    function applyUnifiedCursorPlacement(container, layoutCtx) {
        var metrics = layoutCtx.metrics;
        var maxSlicePx = layoutCtx.maxSlicePx;
        var cursor = makePlacementCursor(0, metrics.headerHeightPx || 0);

        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo, idx) {
            clearSegmentBreaksInGrupo(grupo);
            clearGrupoStartBreakMarks(grupo);
            grupo.classList.remove(
                'report-pdf-grupo-prueba-allow-split',
                'report-pdf-grupo-prueba-keep-on-page',
                'report-pdf-grupo-prueba-split-segments-only',
                'report-pdf-grupo-prueba-new-page-start',
                'report-pdf-grupo-prueba-force-break-before'
            );
            clearGrupoCompact(grupo);

            var isFirstGrupo = idx === 0 || grupo.classList.contains('report-pdf-grupo-prueba-first');
            var measured = measureGrupoHeightForPlacement(grupo, layoutCtx);
            if (measured.compact) {
                applyCompactToGrupo(grupo);
            }
            var grupoH = measured.height;

            if (!isFirstGrupo) {
                cursor = forceGrupoToNewPageStart(grupo, cursor, layoutCtx);
            }

            var espacioAntesGrupo = remainingForGrupoPlacement(grupo, container, layoutCtx, cursor, isFirstGrupo);

            if (grupoH <= espacioAntesGrupo && grupoH <= maxSlicePx) {
                grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                clearAnalysisUnitBreaksInGrupo(grupo);
                var placedPage = cursor.page;
                var placedY = cursor.y;
                cursor = bumpCursorAfterPlace(
                    placedPage,
                    placedY,
                    grupo.offsetHeight || 0,
                    layoutCtx.boundarySet,
                    layoutCtx.metrics
                );
                sealKeepOnPageGrupoIntact(grupo);
                applyGrupoFirmaPageBreaks(grupo);
                return;
            }

            if (measured.compact) {
                clearGrupoCompact(grupo);
            }
            if (grupoH <= maxSlicePx) {
                grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                grupo.classList.add('report-pdf-grupo-prueba-split-segments-only');
            } else {
                grupo.classList.add('report-pdf-grupo-prueba-split-segments-only');
            }
            cursor = placeGrupoTitleOnCursor(cursor, grupo, layoutCtx);
            cursor = applyAnalysisUnitPageBreaks(grupo, container, layoutCtx, cursor);
            applyGrupoFirmaPageBreaks(grupo);
        });
    }

    function applyAnalysisUnitPageBreaks(grupo, container, layoutCtx, cursor) {
        var nodes = placementNodesInGrupo(grupo);
        if (!nodes.length) {
            return cursor;
        }

        var firma = labFirmaBlockInGrupo(grupo);
        var firmaH = firma ? elementHeight(firma) : 0;
        var grupoNombre = grupoNombreFromGrupo(grupo);
        var state = {
            lastSubgrupo: null,
            cabeceraCounted: false,
            markBreaks: true,
            container: container
        };

        nodes.forEach(function(node, idx) {
            var isLast = idx === nodes.length - 1;
            if (isLast && firmaH > 0) {
                cursor = placeAnalysisUnitOnCursor(cursor, node, container, layoutCtx, {
                    isLast: true,
                    firmaH: firmaH,
                    firmaNode: firma,
                    grupo: grupo,
                    grupoNombre: grupoNombre,
                    markBreaks: true
                });
                return;
            }
            cursor = placeSegmentOnCursor(cursor, node, layoutCtx, state);
        });

        return cursor;
    }

    function enforceGrupoHeadFitsOnPage(grupo, container, layoutCtx) {
        var headMin = grupoHeadMinHeight(grupo, container);
        if (headMin <= 0 || headMin > layoutCtx.maxSlicePx) {
            return;
        }
        var top = topWithinContainer(grupo, container);
        var remaining = remainingOnPage(top, layoutCtx.boundarySet);
        if (headMin <= remaining) {
            return;
        }
        var breaker = grupo.previousElementSibling;
        if (breaker && breaker.classList && breaker.classList.contains('report-grupo-inter-page-break')) {
            strengthenInterPageBreakNode(breaker);
        } else {
            markGrupoStartBreakBefore(grupo);
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
        var cursor = makePlacementCursor(cursorPage, cursorY);
        var state = {
            lastSubgrupo: null,
            cabeceraCounted: false,
            markBreaks: true,
            container: null
        };

        grupo.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            cursor = placeSegmentOnCursor(cursor, seg, layoutCtx, state);
        });

        return cursor;
    }

    function placeGrupoIfFitsSplit(grupo, container, layoutCtx, cursorPage, cursorY) {
        clearSegmentBreaksInGrupo(grupo);
        var cursor = makePlacementCursor(cursorPage, cursorY);
        cursor = placeGrupoTitleOnCursor(cursor, grupo, layoutCtx);
        cursor = applyAnalysisUnitPageBreaks(grupo, container, layoutCtx, cursor);
        applyGrupoFirmaPageBreaks(grupo);
        return cursor;
    }

    function placementCursorAfterKeepOnPageFirmaCheck(grupo, container, layoutCtx, cursorPage, cursorY) {
        var boundarySet = layoutCtx.boundarySet;
        var metrics = layoutCtx.metrics;
        var firma = labFirmaBlockInGrupo(grupo);
        var firmaH = firma ? elementHeight(firma) : 0;

        if (firmaH <= 0) {
            return bumpCursorAfterPlace(
                cursorPage,
                cursorY,
                grupo.offsetHeight || 0,
                boundarySet,
                metrics
            );
        }

        var nodes = placementNodesInGrupo(grupo);
        if (!nodes.length) {
            return bumpCursorAfterPlace(
                cursorPage,
                cursorY,
                grupo.offsetHeight || 0,
                boundarySet,
                metrics
            );
        }

        var cursor = makePlacementCursor(cursorPage, cursorY);
        cursor = placeGrupoTitleOnCursor(cursor, grupo, layoutCtx);
        var state = {
            lastSubgrupo: null,
            cabeceraCounted: false,
            markBreaks: false,
            container: container
        };
        var grupoNombre = grupoNombreFromGrupo(grupo);

        for (var i = 0; i < nodes.length; i++) {
            if (i === nodes.length - 1) {
                return placeAnalysisUnitOnCursor(cursor, nodes[i], container, layoutCtx, {
                    isLast: true,
                    firmaH: firmaH,
                    firmaNode: firma,
                    grupo: grupo,
                    grupoNombre: grupoNombre,
                    markBreaks: true
                });
            }
            cursor = placeSegmentOnCursor(cursor, nodes[i], layoutCtx, state);
        }

        return cursor;
    }

    function applyIfFitsMode(container, layoutCtx) {
        var metrics = layoutCtx.metrics;
        var boundarySet = layoutCtx.boundarySet;
        var maxSlicePx = layoutCtx.maxSlicePx;
        var headerPx = metrics.headerHeightPx || 0;
        var cursorPage = 0;
        var cursorY = headerPx;

        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo, idx) {
            var isFirstGrupo = idx === 0 || grupo.classList.contains('report-pdf-grupo-prueba-first');
            if (!isFirstGrupo) {
                var forcedStart = forceCursorToNextPage(makePlacementCursor(cursorPage, cursorY), layoutCtx);
                cursorPage = forcedStart.page;
                cursorY = forcedStart.y;
            }

            var grupoHeight = grupo.offsetHeight || 0;
            var remaining = pageEndPx(cursorPage, boundarySet, metrics) - cursorY;

            grupo.classList.remove(
                'report-pdf-grupo-prueba-allow-split',
                'report-pdf-grupo-prueba-keep-on-page',
                'report-pdf-grupo-prueba-force-break-before'
            );

            if (grupoHeight > maxSlicePx) {
                markGrupoAllowSplit(grupo, 'applyIfFitsMode:grupoHeight>maxSlicePx');
                var placedHuge = placeGrupoIfFitsSplit(grupo, container, layoutCtx, cursorPage, cursorY);
                cursorPage = placedHuge.page;
                cursorY = placedHuge.y;
                return;
            }

            if (grupoHeight <= remaining) {
                grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                var placedKeep = placementCursorAfterKeepOnPageFirmaCheck(
                    grupo, container, layoutCtx, cursorPage, cursorY
                );
                cursorPage = placedKeep.page;
                cursorY = placedKeep.y;
                return;
            }

            markGrupoAllowSplit(grupo, 'applyIfFitsMode:grupoHeight>remaining');
            var placedSplit = placeGrupoIfFitsSplit(grupo, container, layoutCtx, cursorPage, cursorY);
            cursorPage = placedSplit.page;
            cursorY = placedSplit.y;
        });
    }

    function fallbackFillGrupo(grupo, container, layoutCtx) {
        clearGrupoStartBreakMarks(grupo);
        grupo.classList.remove('report-pdf-grupo-prueba-keep-on-page');
        clearGrupoCompact(grupo);
        markGrupoAllowSplit(grupo, 'fallbackFillGrupo');
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
                markGrupoAllowSplit(grupo, 'applyGrupoPageBreaks:effectiveHeight>maxSlicePx');
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

    function allowSplitGrupoWithSegments(grupo, container, layoutCtx, cursor) {
        if (!grupo) {
            return cursor;
        }
        if (usesGrupoIntactMode()) {
            grupo.classList.add('report-pdf-grupo-prueba-split-segments-only');
            grupo.classList.remove('report-pdf-grupo-prueba-allow-split');
            return applyAnalysisUnitPageBreaks(grupo, container, layoutCtx, cursor);
        }
        markGrupoAllowSplit(grupo, 'allowSplitGrupoWithSegments:no-intact');
        applySegmentPageBreaks(container, layoutCtx, grupo);
        return cursor;
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

    function applyGrupoInterPageBreaks(container) {
        if (window.REPORT_PAGE_BREAK_DIAG) {
            return;
        }
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
            grupo.style.setProperty('break-before', 'avoid', 'important');
            grupo.style.setProperty('page-break-before', 'avoid', 'important');
            grupo.style.setProperty('margin-top', '0', 'important');
            grupo.style.setProperty('padding-top', '0', 'important');
        });
    }

    function applyBrowserPrintGrupoIntactPageBreaks(container, layoutCtx) {
        var maxSlicePx = layoutCtx.maxSlicePx;
        var compactMode = cfg.mode === 'keep_together_compact';
        var cursor = makePlacementCursor(0, layoutCtx.metrics.headerHeightPx || 0);

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

            cursor = enforceGrupoHeadOnCursor(grupo, container, layoutCtx, cursor, isFirstGrupo);

            if (compactMode && height > maxSlicePx) {
                var compactH = measureGrupoHeight(grupo, true);
                if (compactH <= maxSlicePx) {
                    grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                    cursor = placeGrupoTitleOnCursor(cursor, grupo, layoutCtx);
                    cursor = applyAnalysisUnitPageBreaks(grupo, container, layoutCtx, cursor);
                    applyGrupoFirmaPageBreaks(grupo);
                    return;
                }
                clearGrupoCompact(grupo);
                height = measureGrupoHeight(grupo, false);
            }

            var espacioAntesGrupo = espacioRestanteEnPaginaActual(cursor, layoutCtx);

            if (height <= espacioAntesGrupo && height <= maxSlicePx) {
                grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                clearAnalysisUnitBreaksInGrupo(grupo);
                cursor = placeGrupoTitleOnCursor(cursor, grupo, layoutCtx);
                cursor = bumpCursorAfterPlace(
                    cursor.page,
                    cursor.y,
                    grupo.offsetHeight || 0,
                    layoutCtx.boundarySet,
                    layoutCtx.metrics
                );
                applyGrupoFirmaPageBreaks(grupo);
                return;
            } else if (height <= maxSlicePx) {
                grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                grupo.classList.add('report-pdf-grupo-prueba-split-segments-only');
            } else {
                grupo.classList.add('report-pdf-grupo-prueba-split-segments-only');
            }

            cursor = placeGrupoTitleOnCursor(cursor, grupo, layoutCtx);
            cursor = applyAnalysisUnitPageBreaks(grupo, container, layoutCtx, cursor);
            applyGrupoFirmaPageBreaks(grupo);
        });
    }

    function applyBrowserPrintAreaSeparatorFix() {
        if (!usesGrupoIntactMode()) {
            return;
        }

        document.querySelectorAll('.report-pdf-grupo-prueba.report-pdf-grupo-browser-print-area:not(.report-pdf-grupo-prueba-first)').forEach(function(grupo) {
            if (grupo.classList.contains('report-pdf-grupo-prueba-keep-on-page')
                && !grupo.classList.contains('report-pdf-grupo-prueba-split-segments-only')) {
                grupo.style.setProperty('page-break-before', 'avoid', 'important');
                grupo.style.setProperty('break-before', 'avoid', 'important');
                grupo.style.setProperty('margin-top', '0', 'important');
                grupo.style.setProperty('padding-top', '0', 'important');
                return;
            }
            grupo.style.setProperty('page-break-before', 'always', 'important');
            grupo.style.setProperty('break-before', 'page', 'important');
            grupo.style.setProperty('margin-top', '0', 'important');
            grupo.style.setProperty('padding-top', '0', 'important');
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
            if (!leader.classList.contains('report-area-page-leader-force-break-before')) {
                leader.style.setProperty('page-break-before', 'auto', 'important');
                leader.style.setProperty('break-before', 'auto', 'important');
                leader.style.setProperty('height', '0', 'important');
                return;
            }
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
            if (usesGrupoInterPageBreakMode()) {
                applyGrupoInterPageBreaks(container);
                if (usesGrupoIntactMode()) {
                    applyBrowserPrintAreaSeparatorFix();
                }
            }
            return;
        }

        if (!cfg.mode || cfg.mode === 'flow') {
            applyCabeceraSegmentIntegrity(container, layoutCtx);
        } else if (cfg.mode === 'keep_segment') {
            applySegmentPageBreaks(container, layoutCtx);
        } else if (usesUnifiedCursorPlacement()) {
            applyGrupoInterPageBreaks(container);
            applyUnifiedCursorPlacement(container, layoutCtx);
        } else if (!usesGrupoIntactMode()) {
            applyCabeceraSegmentIntegrity(container, layoutCtx);
            applyGrupoPageBreaks(container, layoutCtx);
        } else {
            applyGrupoInterPageBreaks(container);
            applyUnifiedCursorPlacement(container, layoutCtx);
        }

        applyLabFirmasPageBreaks(container, layoutCtx);

        container.querySelectorAll('.report-pdf-grupo-prueba.report-pdf-grupo-prueba-keep-on-page:not(.report-pdf-grupo-prueba-split-segments-only)').forEach(function(grupo) {
            sealKeepOnPageGrupoIntact(grupo);
        });

        if (usesGrupoIntactMode()) {
            applyBrowserPrintAreaSeparatorFix();
        }

        releaseAllowSplitSegmentWrapStyles(container);
        applyResultsTableContinuationHeaders(container, layoutCtx);
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
    window.addEventListener('beforeprint', function() {
        applyPageBreakRules();
    });
    window.addEventListener('afterprint', function() {
        clearPageBreakAdjustments();
    });
})();
</script>
