<?php
declare(strict_types=1);

/**
 * Diagnóstico temporal de page-break (impresión navegador).
 * Activar con ?pb_diag=1 en registers/printreport/{id}
 */
$pbDiagEnabled = ! empty($pb_diag_enabled);
?>
<script>
(function() {
    var PB_DIAG = <?= $pbDiagEnabled ? 'true' : 'false' ?>;
    if (!PB_DIAG) {
        return;
    }

    window.REPORT_PAGE_BREAK_DIAG = true;

    function grupoLabel(el) {
        if (!el) {
            return '';
        }
        var sep = el.querySelector('.report-pdf-grupo-area-separator, .report-pdf-grupo-area-start-table .report-pdf-grupo-area-separator');
        if (sep) {
            return (sep.textContent || '').trim();
        }
        return (el.className || '').trim();
    }

    function readBreakProps(cs) {
        return {
            display: cs.display,
            position: cs.position,
            overflow: cs.overflow,
            breakInside: cs.breakInside || cs.getPropertyValue('break-inside'),
            pageBreakInside: cs.pageBreakInside || cs.getPropertyValue('page-break-inside'),
            breakBefore: cs.breakBefore || cs.getPropertyValue('break-before'),
            pageBreakBefore: cs.pageBreakBefore || cs.getPropertyValue('page-break-before'),
            breakAfter: cs.breakAfter || cs.getPropertyValue('break-after'),
            pageBreakAfter: cs.pageBreakAfter || cs.getPropertyValue('page-break-after')
        };
    }

    function ancestorChainFrom(startEl) {
        var rows = [];
        var node = startEl;
        var depth = 0;
        while (node) {
            var cs = window.getComputedStyle(node);
            var props = readBreakProps(cs);
            rows.push({
                depth: depth,
                node: node.tagName.toLowerCase()
                    + (node.id ? '#' + node.id : '')
                    + (node.className ? '.' + String(node.className).trim().replace(/\s+/g, '.') : ''),
                inlineStyle: (node.getAttribute && node.getAttribute('style')) || '',
                display: props.display,
                position: props.position,
                overflow: props.overflow,
                breakInside: props.breakInside,
                pageBreakInside: props.pageBreakInside,
                breakBefore: props.breakBefore,
                pageBreakBefore: props.pageBreakBefore
            });
            if (node === document.documentElement) {
                break;
            }
            node = node.parentElement;
            depth++;
        }
        return rows;
    }

    function flagSuspects(rows, label) {
        var suspects = [];
        rows.forEach(function(row) {
            var bi = String(row.breakInside || '').toLowerCase();
            var pbi = String(row.pageBreakInside || '').toLowerCase();
            var ov = String(row.overflow || '').toLowerCase();
            if (bi.indexOf('avoid') !== -1 || pbi.indexOf('avoid') !== -1) {
                suspects.push({
                    motivo: 'break-inside: avoid (impide fragmentar hijos / anula break-before internos)',
                    nodo: row.node,
                    depth: row.depth,
                    breakInside: row.breakInside,
                    pageBreakInside: row.pageBreakInside,
                    inlineStyle: row.inlineStyle
                });
            }
            if (ov === 'hidden' || ov === 'clip' || ov === 'auto') {
                suspects.push({
                    motivo: 'overflow puede interferir con paginación',
                    nodo: row.node,
                    depth: row.depth,
                    overflow: row.overflow
                });
            }
        });
        if (suspects.length) {
            console.warn('PB-DIAG sospechosos [' + label + ']:', suspects);
        } else {
            console.log('PB-DIAG: sin ancestros sospechosos obvios [' + label + ']');
        }
        return suspects;
    }

    function removeSeparators() {
        var removed = 0;
        document.querySelectorAll('.report-grupo-inter-page-break').forEach(function(node) {
            node.remove();
            removed++;
        });
        console.log('PB-DIAG: separadores .report-grupo-inter-page-break eliminados:', removed);
        return removed;
    }

    function applyDirectGrupoBreakBeforeTest() {
        var grupos = Array.from(document.querySelectorAll('.report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-first)'));
        var applied = [];
        grupos.forEach(function(grupo, idx) {
            if (idx > 1) {
                return;
            }
            grupo.style.setProperty('break-before', 'page', 'important');
            grupo.style.setProperty('page-break-before', 'always', 'important');
            applied.push({
                indice: idx + 2,
                grupo: grupoLabel(grupo),
                clases: grupo.className
            });
        });
        console.log('PB-DIAG: break-before directo (inline) en 2.º y 3.º grupo:', applied);
        return applied;
    }

    function runDomDiagnostic() {
        console.group('PB-DIAG: diagnóstico page-break');

        removeSeparators();
        applyDirectGrupoBreakBeforeTest();

        var leader = document.querySelector('.report-pdf-grupo-area-page-leader');
        if (leader) {
            var leaderChain = ancestorChainFrom(leader);
            console.log('PB-DIAG: cadena desde .report-pdf-grupo-area-page-leader → raíz');
            console.table(leaderChain);
            flagSuspects(leaderChain, 'page-leader');
        } else {
            console.warn('PB-DIAG: no existe .report-pdf-grupo-area-page-leader (modo browser_print usa .report-pdf-grupo-browser-print-area)');
        }

        var grupos = document.querySelectorAll('.report-pdf-grupo-prueba');
        [1, 2].forEach(function(i) {
            if (!grupos[i]) {
                return;
            }
            var g = grupos[i];
            var chain = ancestorChainFrom(g);
            console.log('PB-DIAG: cadena grupo #' + (i + 1) + ' (' + grupoLabel(g) + ') → raíz');
            console.table(chain);
            flagSuspects(chain, 'grupo-' + (i + 1));
        });

        var stack = document.querySelector('.pdf-main-stack');
        if (stack) {
            var cs = window.getComputedStyle(stack);
            console.log('PB-DIAG: .pdf-main-stack', readBreakProps(cs));
        }

        console.groupEnd();
    }

    window.runReportPageBreakDiagnostic = runDomDiagnostic;

    window.addEventListener('beforeprint', function() {
        runDomDiagnostic();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runDomDiagnostic);
    } else {
        runDomDiagnostic();
    }
})();
</script>
