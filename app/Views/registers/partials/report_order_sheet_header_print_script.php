<?php
/**
 * Banda Paciente / No. Orden en impresión navegador (desde hoja 2, fila en pdf-section-table del pie).
 *
 * @var bool $order_sheet_header_enabled
 */
if (empty($order_sheet_header_enabled)) {
    return;
}
?>
<script>
(function() {
    function buildOrderSheetBandNode(tpl, extraClass) {
        var wrap = document.createElement('div');
        wrap.className = 'pdf-order-sheet-footer-band pdf-order-sheet-header-injected'
            + (extraClass ? ' ' + extraClass : '');

        var table = document.createElement('table');
        table.className = 'pdf-order-sheet-header';
        table.setAttribute('width', '100%');
        table.setAttribute('cellpadding', '0');
        table.setAttribute('cellspacing', '0');

        var row = document.createElement('tr');

        var tdPatient = document.createElement('td');
        tdPatient.className = 'pdf-order-sheet-header-patient';
        tdPatient.setAttribute('align', 'left');
        tdPatient.textContent = tpl.getAttribute('data-patient-line') || '';

        var tdOrder = document.createElement('td');
        tdOrder.className = 'pdf-order-sheet-header-orden';
        tdOrder.setAttribute('align', 'right');
        tdOrder.textContent = tpl.getAttribute('data-order-line') || '';

        row.appendChild(tdPatient);
        row.appendChild(tdOrder);
        table.appendChild(row);
        wrap.appendChild(table);

        return wrap;
    }

    function buildOrderSheetTemplateFromFooterRow(footerRow) {
        var tpl = document.createElement('div');
        var patientCell = footerRow.querySelector('.pdf-order-sheet-header-patient');
        var orderCell = footerRow.querySelector('.pdf-order-sheet-header-orden');
        tpl.setAttribute('data-patient-line', patientCell ? (patientCell.textContent || '').trim() : '');
        tpl.setAttribute('data-order-line', orderCell ? (orderCell.textContent || '').trim() : '');
        return tpl;
    }

    function clearOrderSheetHeaderPrintArtifacts() {
        document.querySelectorAll(
            '.pdf-order-sheet-header-print-fixed, .pdf-order-sheet-header-injected, '
            + '.pdf-order-sheet-footer-band-standalone, '
            + '.report-order-sheet-page-leader, .pdf-osh-page1-cover'
        ).forEach(function(node) {
            node.remove();
        });
        document.querySelectorAll('.pdf-ft-block.footer-grid .pdf-order-sheet-footer-band').forEach(function(node) {
            node.style.removeProperty('display');
        });
        document.querySelectorAll('.pdf-ft-block.footer-grid .pdf-order-sheet-table-row').forEach(function(node) {
            node.style.removeProperty('display');
        });
        document.body.classList.remove(
            'js-order-sheet-header-print',
            'js-order-sheet-header-in-flow',
            'js-order-sheet-footer-band',
            'js-order-sheet-footer-table-row',
            'js-order-sheet-footer-band-standalone'
        );
    }

    function hideEmbeddedFooterOrderSheet(footer) {
        if (!footer) {
            return;
        }
        footer.querySelectorAll('.pdf-order-sheet-footer-band, .pdf-order-sheet-table-row').forEach(function(node) {
            node.style.setProperty('display', 'none', 'important');
        });
    }

    function firstElementAtOrAfter(root, minTopPx) {
        var selector = [
            '.report-grupo-inter-page-break',
            '.report-pdf-grupo-prueba',
            '.report-pdf-subgrupo-block',
            '.report-pdf-grupo-cabecera',
            '.pdf-hg-block',
            '.pdf-pd-block',
            '.lab-firmas-pdf-block',
            '.pdf-notes-block'
        ].join(', ');
        var candidates = root.querySelectorAll(selector);
        var best = null;
        var bestTop = Infinity;
        for (var i = 0; i < candidates.length; i++) {
            var el = candidates[i];
            var top = el.offsetTop || 0;
            if (top + 1 < minTopPx) {
                continue;
            }
            if (top < bestTop) {
                bestTop = top;
                best = el;
            }
        }
        return best;
    }

    function insertPage1OrderSheetCover(container) {
        if (!window.reportPrintPagination || typeof window.reportPrintPagination.buildBoundaries !== 'function') {
            return;
        }
        var boundarySet = window.reportPrintPagination.buildBoundaries(container);
        var boundaries = boundarySet.boundaries || [];
        if (boundaries.length < 1) {
            return;
        }
        var page2Start = boundaries[0];
        if (!isFinite(page2Start) || page2Start <= 0) {
            return;
        }
        var anchor = firstElementAtOrAfter(container, page2Start);
        if (!anchor) {
            return;
        }
        var cover = document.createElement('div');
        cover.className = 'pdf-osh-page1-cover';
        cover.setAttribute('aria-hidden', 'true');
        container.insertBefore(cover, anchor);
    }

    function injectBandsInFlow(tpl, container) {
        if (!window.reportPrintPagination || typeof window.reportPrintPagination.buildBoundaries !== 'function') {
            return false;
        }

        var boundarySet = window.reportPrintPagination.buildBoundaries(container);
        var boundaries = boundarySet.boundaries || [];
        if (boundaries.length < 1) {
            return false;
        }

        for (var i = boundaries.length - 1; i >= 0; i--) {
            var pageStartPx = boundaries[i];
            if (!isFinite(pageStartPx) || pageStartPx <= 0) {
                continue;
            }
            var anchor = firstElementAtOrAfter(container, pageStartPx);
            if (!anchor) {
                continue;
            }
            container.insertBefore(buildOrderSheetBandNode(tpl), anchor);
        }

        return true;
    }

    window.injectOrderSheetHeadersFromPageTwo = function() {
        var tpl = document.getElementById('pdf-order-sheet-header-template');
        var footer = document.querySelector('.pdf-ft-block.footer-grid');
        var footerTableRow = footer ? footer.querySelector('.pdf-order-sheet-table-row') : null;

        if (!tpl && footerTableRow) {
            tpl = buildOrderSheetTemplateFromFooterRow(footerTableRow);
        }

        if (!tpl) {
            return false;
        }

        clearOrderSheetHeaderPrintArtifacts();

        if (footerTableRow) {
            document.body.classList.add('js-order-sheet-footer-table-row');
            var container = document.querySelector('.pdf-main-stack') || document.body;
            insertPage1OrderSheetCover(container);
            if (typeof window.syncReportPrintLayoutMetrics === 'function') {
                window.syncReportPrintLayoutMetrics();
            }
            return true;
        }

        hideEmbeddedFooterOrderSheet(footer);

        var container = document.querySelector('.pdf-main-stack') || document.body;
        if (!injectBandsInFlow(tpl, container)) {
            return false;
        }

        document.body.classList.add('js-order-sheet-header-in-flow');

        if (typeof window.syncReportPrintLayoutMetrics === 'function') {
            window.syncReportPrintLayoutMetrics();
        }

        return true;
    };

    window.addEventListener('afterprint', clearOrderSheetHeaderPrintArtifacts);
})();
</script>
