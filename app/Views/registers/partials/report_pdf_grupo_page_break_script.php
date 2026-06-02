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
        compactMinScale: <?= json_encode(max(85, min(100, (int) ($gpb['compact_min_scale_percent'] ?? 92)))) ?>,
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

    function topWithinContainer(el, container) {
        var top = 0;
        var node = el;
        while (node && node !== container) {
            top += node.offsetTop;
            node = node.offsetParent;
            if (node && !container.contains(node)) {
                break;
            }
        }
        return top;
    }

    function remainingOnPage(top, pagePx) {
        var remaining = pagePx - (top % pagePx);
        if (remaining >= pagePx) {
            remaining = pagePx;
        }
        return remaining;
    }

    function clearPageBreakAdjustments() {
        document.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            grupo.classList.remove(
                'report-pdf-grupo-prueba-compact',
                'report-pdf-grupo-prueba-force-break-before',
                'report-pdf-grupo-prueba-allow-split'
            );
            grupo.style.removeProperty('--pdf-gpb-compact-scale');
            grupo.querySelectorAll('.report-pdf-grupo-cabecera-continuacion-injected').forEach(function(node) {
                node.remove();
            });
        });
        document.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
            seg.classList.remove('report-segment-force-break-before', 'report-segment-allow-split');
        });
    }

    function cloneContinuationHeader(sourceHeader) {
        var clone = sourceHeader.cloneNode(true);
        clone.classList.add('report-pdf-grupo-cabecera-continuacion', 'report-pdf-grupo-cabecera-continuacion-injected');
        clone.setAttribute('aria-hidden', 'true');
        return clone;
    }

    function injectContinuationHeaders(grupo, container, pagePx) {
        if (!cfg.repeatHeader) {
            return;
        }
        var headers = grupo.querySelectorAll(':scope > .report-pdf-subgrupo-block > .report-pdf-grupo-cabecera, :scope > .report-pdf-grupo-cabecera');
        if (!headers.length) {
            return;
        }
        var refHeader = headers[0];
        var blocks = grupo.querySelectorAll(':scope > .report-pdf-subgrupo-block, :scope > .report-segment-table-wrap');
        blocks.forEach(function(block) {
            var top = topWithinContainer(block, container);
            var pageStart = Math.floor(top / pagePx) * pagePx;
            if (pageStart > 0 && Math.abs(top - pageStart) < 2) {
                var prev = block.previousElementSibling;
                if (prev && prev.classList && prev.classList.contains('report-pdf-grupo-cabecera-continuacion-injected')) {
                    return;
                }
                block.parentNode.insertBefore(cloneContinuationHeader(refHeader), block);
            }
        });
    }

    function applySegmentPageBreaks(container, pagePx) {
        container.querySelectorAll(SEGMENT_SELECTOR).forEach(function(seg) {
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

    function applyGrupoPageBreaks(container, pagePx) {
        container.querySelectorAll('.report-pdf-grupo-prueba').forEach(function(grupo) {
            var top = topWithinContainer(grupo, container);
            var remaining = remainingOnPage(top, pagePx);
            var height = grupo.offsetHeight;

            if (height > pagePx) {
                grupo.classList.add('report-pdf-grupo-prueba-allow-split');
                injectContinuationHeaders(grupo, container, pagePx);
                return;
            }

            if (height <= remaining) {
                return;
            }

            if (cfg.mode === 'keep_together_compact') {
                var scale = cfg.compactMinScale / 100;
                grupo.classList.add('report-pdf-grupo-prueba-compact');
                grupo.style.setProperty('--pdf-gpb-compact-scale', String(scale));
                height = grupo.offsetHeight;
                if (height <= remaining) {
                    return;
                }
            }

            grupo.classList.remove('report-pdf-grupo-prueba-compact');
            grupo.style.removeProperty('--pdf-gpb-compact-scale');
            grupo.classList.add('report-pdf-grupo-prueba-force-break-before');
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

        applyGrupoPageBreaks(container, pagePx);
    }

    window.applyReportPdfGrupoPageBreaks = applyPageBreakRules;
    window.addEventListener('beforeprint', applyPageBreakRules);
    window.addEventListener('afterprint', clearPageBreakAdjustments);
})();
</script>
