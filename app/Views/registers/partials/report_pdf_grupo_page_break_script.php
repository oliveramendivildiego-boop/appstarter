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

    function hasMeaningfulContentAbove(container, topPx, metrics) {
        var headerPx = metrics && metrics.headerHeightPx ? metrics.headerHeightPx : 0;
        return isFinite(topPx) && topPx > Math.max(12, headerPx + 1);
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

        if (!hasMeaningfulContentAbove(container, unit.top, layoutCtx.metrics)) {
            seg.classList.add('report-segment-allow-split');
            return;
        }

        if (unit.cabecera) {
            markForceBreakBeforeCabecera(unit.cabecera);
        } else {
            seg.classList.add('report-segment-force-break-before');
        }
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

    function clearPageBreakAdjustments() {
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

    function applyIfFitsMode(container, layoutCtx) {
        var keepOnPageGrupos = [];
        var maxSlicePx = layoutCtx.maxSlicePx;
        var boundarySet = layoutCtx.boundarySet;

        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            var top = topWithinContainer(grupo, container);
            var remaining = remainingOnPage(top, boundarySet);
            var height = grupo.offsetHeight;

            if (height > maxSlicePx) {
                grupo.classList.add('report-pdf-grupo-prueba-allow-split');
                return;
            }

            if (height <= remaining) {
                grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                keepOnPageGrupos.push(grupo);
                return;
            }

            grupo.classList.add('report-pdf-grupo-prueba-allow-split');
            applySegmentPageBreaks(container, layoutCtx, grupo);
        });

        applySegmentPageBreaks(container, layoutCtx);

        keepOnPageGrupos.forEach(function(grupo) {
            grupo.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
                seg.classList.remove('report-segment-force-break-before', 'report-segment-allow-split');
            });
            grupo.querySelectorAll('.report-pdf-grupo-cabecera').forEach(function(cabecera) {
                cabecera.classList.remove('report-cabecera-force-break-before');
            });
            grupo.querySelectorAll('.report-pdf-subgrupo-block').forEach(function(subgrupo) {
                subgrupo.classList.remove('report-subgrupo-force-break-before');
            });
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

            if (shouldForceBreakBefore(remaining) && hasMeaningfulContentAbove(container, top, metrics)) {
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
