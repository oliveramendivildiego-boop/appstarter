<?php
/**
 * Ajuste de saltos de página antes de imprimir en el navegador.
 *
 * @var array<string,mixed> $pdf_layout
 * @var float               $page_height_mm
 * @var float               $margin_top_mm
 * @var float               $margin_bottom_mm
 */
$pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$ps = is_array($pl['page_style'] ?? null) ? $pl['page_style'] : \App\Services\ReportPdfLayoutService::defaultPageStyleStatic();
$gpb = \App\Services\ReportPdfLayoutService::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
$pageHeightMm = isset($page_height_mm) ? (float) $page_height_mm : 279.4;
$marginTopMm = isset($margin_top_mm) ? (float) $margin_top_mm : 15.0;
$marginBottomMm = isset($margin_bottom_mm) ? (float) $margin_bottom_mm : 15.0;
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
        marginBottomMm: <?= json_encode($marginBottomMm) ?>
    };

    if (!cfg.mode || cfg.mode === 'flow') {
        return;
    }

    var MM_TO_PX = 96 / 25.4;
    var SEGMENT_SELECTOR = '.report-segment-table-wrap, .report-refs-matrix-wrap';

    function printablePageHeightPx() {
        var printableMm = cfg.pageHeightMm - cfg.marginTopMm - cfg.marginBottomMm;
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

    function remainingOnPage(top, pagePx) {
        var posOnPage = ((top % pagePx) + pagePx) % pagePx;
        var remaining = pagePx - posOnPage;
        if (remaining <= 0 || remaining > pagePx) {
            remaining = pagePx;
        }
        return remaining;
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

    function applySegmentPageBreaks(container, pagePx, root) {
        var scope = root || container;
        scope.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            var top = topWithinContainer(seg, container);
            var remaining = remainingOnPage(top, pagePx);
            var height = seg.offsetHeight;

            if (height > pagePx) {
                seg.classList.add('report-segment-allow-split');
                return;
            }

            if (height <= remaining) {
                return;
            }

            seg.classList.add('report-segment-force-break-before');
        });
    }

    /**
     * Grupo íntegro solo si cabe: si el área entera cabe, mantenerla junta;
     * si no, aplicar saltos por segmento (como flujo por segmentos).
     */
    function applyIfFitsMode(container, pagePx) {
        var keepOnPageGrupos = [];

        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            var top = topWithinContainer(grupo, container);
            var remaining = remainingOnPage(top, pagePx);
            var height = grupo.offsetHeight;

            if (height > pagePx) {
                grupo.classList.add('report-pdf-grupo-prueba-allow-split');
                return;
            }

            if (height <= remaining) {
                grupo.classList.add('report-pdf-grupo-prueba-keep-on-page');
                keepOnPageGrupos.push(grupo);
            }
        });

        applySegmentPageBreaks(container, pagePx);

        keepOnPageGrupos.forEach(function(grupo) {
            grupo.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
                seg.classList.remove('report-segment-force-break-before', 'report-segment-allow-split');
            });
        });
    }

    function fallbackFillGrupo(grupo, container, pagePx) {
        grupo.classList.remove('report-pdf-grupo-prueba-force-break-before', 'report-pdf-grupo-prueba-keep-on-page');
        clearGrupoCompact(grupo);
        grupo.classList.add('report-pdf-grupo-prueba-allow-split');
        applySegmentPageBreaks(container, pagePx, grupo);
    }

    function applyGrupoPageBreaks(container, pagePx) {
        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            var top = topWithinContainer(grupo, container);
            var remaining = remainingOnPage(top, pagePx);
            var height = grupo.offsetHeight;

            if (height > pagePx) {
                grupo.classList.add('report-pdf-grupo-prueba-allow-split');
                applySegmentPageBreaks(container, pagePx, grupo);
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

            if (shouldForceBreakBefore(remaining)) {
                grupo.classList.add('report-pdf-grupo-prueba-force-break-before');
            } else {
                fallbackFillGrupo(grupo, container, pagePx);
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

        if (cfg.mode === 'keep_segment') {
            applySegmentPageBreaks(container, pagePx);
            return;
        }

        if (cfg.mode === 'keep_together_if_fits') {
            applyIfFitsMode(container, pagePx);
            return;
        }

        applyGrupoPageBreaks(container, pagePx);
    }

    window.applyReportPdfGrupoPageBreaks = applyPageBreakRules;
    window.addEventListener('beforeprint', applyPageBreakRules);
    window.addEventListener('afterprint', clearPageBreakAdjustments);
})();
</script>
