/**
 * Mejora visual de tablas (.lab-grid-enhanced): filtro local y cabecera sticky.
 * No reemplaza paginación ni búsqueda del servidor.
 */
(function () {
    'use strict';

    var SKIP_ROW_SEL = '.audit-detail-row, .lab-grid-skip';
    var DEBOUNCE_MS = 120;

    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments;
            var ctx = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function isSkipRow(tr) {
        if (!tr || tr.nodeName !== 'TR') return true;
        if (tr.matches(SKIP_ROW_SEL)) return true;
        var first = tr.cells && tr.cells[0];
        if (first && first.colSpan > 1) return true;
        return false;
    }

    function rowSearchText(tr) {
        if (!tr.cells || !tr.cells.length) return '';
        var parts = [];
        for (var i = 0; i < tr.cells.length; i++) {
            parts.push((tr.cells[i].textContent || '').replace(/\s+/g, ' ').trim());
        }
        return parts.join(' ').toLowerCase();
    }

    function hideDetailForRow(tr, hide) {
        var next = tr.nextElementSibling;
        if (next && next.classList && next.classList.contains('audit-detail-row')) {
            next.style.display = hide ? 'none' : '';
        }
    }

    function hasToolbar(wrapper) {
        var first = wrapper.firstElementChild;
        return first && first.classList.contains('lab-grid-toolbar');
    }

    function initTable(table) {
        if (!table || table.dataset.labGridInit === '1') return;

        var wrapper = table.closest('.lab-grid-enhanced');
        if (!wrapper) return;

        table.dataset.labGridInit = '1';

        var filterInput;
        var countEl;

        if (!hasToolbar(wrapper)) {
            var filterId = 'lab-grid-filter-' + Math.random().toString(36).slice(2);
            var toolbar = document.createElement('div');
            toolbar.className = 'lab-grid-toolbar';
            toolbar.innerHTML = '<label class="visually-hidden" for="' + filterId + '">Filtrar filas</label>'
                + '<input type="text" id="' + filterId + '" class="form-control form-control-sm lab-grid-quick-filter" '
                + 'placeholder="Filtrar filas visibles…" autocomplete="off" inputmode="search">'
                + '<span class="lab-grid-count"></span>';
            wrapper.insertBefore(toolbar, wrapper.firstChild);
        }

        filterInput = wrapper.querySelector('.lab-grid-quick-filter');
        countEl = wrapper.querySelector('.lab-grid-count');
        var tbody = table.tBodies[0];
        if (!tbody || !filterInput) return;

        function updateCount(visible, total) {
            if (!countEl) return;
            countEl.textContent = visible === total
                ? total + ' filas'
                : visible + ' de ' + total + ' filas';
        }

        function applyFilterNow() {
            var q = (filterInput.value || '').trim().toLowerCase();
            var rows = tbody.querySelectorAll('tr');
            var visible = 0;
            var total = 0;

            for (var i = 0; i < rows.length; i++) {
                var tr = rows[i];
                if (isSkipRow(tr)) {
                    if (!tr.classList.contains('audit-detail-row')) {
                        tr.style.display = '';
                    }
                    continue;
                }
                total++;
                var text = rowSearchText(tr);
                var show = !q || text.indexOf(q) !== -1;
                tr.style.display = show ? '' : 'none';
                hideDetailForRow(tr, !show);
                if (show) visible++;
            }
            updateCount(visible, total);
        }

        if (filterInput.dataset.labGridBound === '1') {
            applyFilterNow();
            return;
        }
        filterInput.dataset.labGridBound = '1';

        var applyFilter = debounce(applyFilterNow, DEBOUNCE_MS);

        filterInput.addEventListener('input', function (e) {
            e.stopPropagation();
            applyFilter();
        });
        filterInput.addEventListener('keydown', function (e) {
            e.stopPropagation();
            if (e.key === 'Enter') e.preventDefault();
        });

        applyFilterNow();

        if (typeof MutationObserver !== 'undefined' && !tbody.dataset.labGridObserved) {
            tbody.dataset.labGridObserved = '1';
            var moTimer;
            var obs = new MutationObserver(function () {
                clearTimeout(moTimer);
                moTimer = setTimeout(applyFilterNow, 80);
            });
            obs.observe(tbody, { childList: true });
        }
    }

    function boot() {
        document.querySelectorAll('.lab-grid-enhanced table').forEach(initTable);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
