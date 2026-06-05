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

    var MM_TO_PX = 96 / 25.4;
    var SEGMENT_SELECTOR = '.report-segment-table-wrap, .report-refs-matrix-wrap';

    function currentFooterReserveMm() {
        if (!cfg.footerEnabled) {
            return 0;
        }
        if (isFinite(cfg.footerReserveMm) && cfg.footerReserveMm > 0) {
            return cfg.footerReserveMm;
        }
        return 22;
    }

    function printablePageHeightPx() {
        var printableMm = cfg.pageHeightMm - cfg.marginTopMm - cfg.marginBottomMm - currentFooterReserveMm();
        if (!isFinite(printableMm) || printableMm <= 0) {
            printableMm = 240;
        }
        return printableMm * MM_TO_PX;
    }

    function pxToMm(px) {
        return px / MM_TO_PX;
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

    function buildPageBoundaries(container, pagePx) {
        var boundaries = [0];
        var maxBottom = container.scrollHeight || 0;
        var pos = pagePx;
        while (pos < maxBottom + pagePx) {
            boundaries.push(pos);
            pos += pagePx;
        }
        if (boundaries.length < 2) {
            boundaries.push(pagePx);
        }
        return boundaries;
    }

    function remainingOnPage(top, pagePx, boundaries) {
        var pageEnds = boundaries || buildPageBoundaries(
            document.querySelector('.pdf-main-stack') || document.body,
            pagePx
        );
        for (var i = 0; i < pageEnds.length; i++) {
            var pageStart = i === 0 ? 0 : pageEnds[i - 1];
            var pageEnd = pageEnds[i];
            if (top >= pageStart && top < pageEnd) {
                return pageEnd - top;
            }
        }
        var posOnPage = ((top % pagePx) + pagePx) % pagePx;
        var remaining = pagePx - posOnPage;
        if (remaining <= 0 || remaining > pagePx) {
            remaining = pagePx;
        }
        return remaining;
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

    function hasMeaningfulContentAbove(container, topPx) {
        return isFinite(topPx) && topPx > 12;
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

    function applyForceBreakForUnit(unit, seg, container, pagePx, boundaries) {
        if (unit.height > pagePx) {
            seg.classList.add('report-segment-allow-split');
            return;
        }

        var remaining = remainingOnPage(unit.top, pagePx, boundaries);
        if (unit.height <= remaining) {
            return;
        }

        // Evitar page-break-before al inicio del flujo: Chrome deja la hoja 1 en blanco.
        if (!hasMeaningfulContentAbove(container, unit.top)) {
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

    /**
     * Impresión navegador: cabecera de prueba + tabla deben ir juntas.
     * Si no caben en el espacio restante de la hoja, mover el bloque completo a la siguiente.
     */
    function applyCabeceraSegmentIntegrity(container, pagePx, boundaries) {
        container.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            var unit = segmentUnitMetrics(seg, container);
            applyForceBreakForUnit(unit, seg, container, pagePx, boundaries);
        });
    }

    function applySegmentPageBreaks(container, pagePx, boundaries, root) {
        var scope = root || container;
        scope.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            var unit = segmentUnitMetrics(seg, container);
            applyForceBreakForUnit(unit, seg, container, pagePx, boundaries);
        });
    }

    function applyIfFitsMode(container, pagePx, boundaries) {
        var keepOnPageGrupos = [];

        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            var top = topWithinContainer(grupo, container);
            var remaining = remainingOnPage(top, pagePx, boundaries);
            var height = grupo.offsetHeight;

            if (height > pagePx) {
                grupo.classList.add('report-pdf-grupo-prueba-allow-split');
                return;
            }

            if (height <= remaining) {
                grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                keepOnPageGrupos.push(grupo);
                return;
            }

            // No cabe en el espacio restante: rellenar la hoja actual por segmentos.
            grupo.classList.add('report-pdf-grupo-prueba-allow-split');
            applySegmentPageBreaks(container, pagePx, boundaries, grupo);
        });

        applySegmentPageBreaks(container, pagePx, boundaries);

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

    function fallbackFillGrupo(grupo, container, pagePx, boundaries) {
        grupo.classList.remove('report-pdf-grupo-prueba-force-break-before', 'report-pdf-grupo-prueba-keep-on-page');
        clearGrupoCompact(grupo);
        grupo.classList.add('report-pdf-grupo-prueba-allow-split');
        applySegmentPageBreaks(container, pagePx, boundaries, grupo);
    }

    function applyGrupoPageBreaks(container, pagePx, boundaries) {
        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            var top = topWithinContainer(grupo, container);
            var remaining = remainingOnPage(top, pagePx, boundaries);
            var height = grupo.offsetHeight;

            if (height > pagePx) {
                grupo.classList.add('report-pdf-grupo-prueba-allow-split');
                applySegmentPageBreaks(container, pagePx, boundaries, grupo);
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

            if (shouldForceBreakBefore(remaining) && hasMeaningfulContentAbove(container, top)) {
                grupo.classList.add('report-pdf-grupo-prueba-force-break-before');
            } else {
                fallbackFillGrupo(grupo, container, pagePx, boundaries);
            }
        });
    }

    function applyPageBreakRules() {
        clearPageBreakAdjustments();
        var container = document.querySelector('.pdf-main-stack') || document.body;
        var pagePx = printablePageHeightPx();
        if (!isFinite(pagePx) || pagePx <= 0) {
            return;
        }
        var boundaries = buildPageBoundaries(container, pagePx);

        if (!cfg.mode || cfg.mode === 'flow') {
            applyCabeceraSegmentIntegrity(container, pagePx, boundaries);
            return;
        }

        if (cfg.mode === 'keep_segment') {
            applySegmentPageBreaks(container, pagePx, boundaries);
            return;
        }

        if (cfg.mode === 'keep_together_if_fits') {
            applyIfFitsMode(container, pagePx, boundaries);
            return;
        }

        applyCabeceraSegmentIntegrity(container, pagePx, boundaries);

        applyGrupoPageBreaks(container, pagePx, boundaries);
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
