/**
 * Herramientas de tabla para los reportes analíticos (/reports - nuevos).
 * Buscador, ordenamiento por columna y paginación 100% en cliente.
 * No depende de librerías externas; al imprimir se muestran todas las filas.
 */
(function () {
    'use strict';

    function textOf(cell) {
        return (cell ? cell.textContent : '').trim();
    }

    function numOf(cell) {
        var t = textOf(cell).replace(/[^\d.,-]/g, '').replace(/\.(?=\d{3}(\D|$))/g, '').replace(',', '.');
        var n = parseFloat(t);
        return isNaN(n) ? null : n;
    }

    function initTable(tools) {
        var tableId = tools.getAttribute('data-table');
        var table = document.getElementById(tableId);
        if (!table) { return; }

        var tbody = table.tBodies[0];
        if (!tbody) { return; }

        var allRows = Array.prototype.slice.call(tbody.rows);
        var searchInput = tools.querySelector('.an-search');
        var pageSizeSel = tools.querySelector('.an-pagesize');
        var pagWrap = document.querySelector('.an-pagination[data-table="' + tableId + '"]');
        var infoEl = pagWrap ? pagWrap.querySelector('.an-info') : null;
        var navEl = pagWrap ? pagWrap.querySelector('.an-nav') : null;

        var state = { query: '', page: 1, pageSize: pageSizeSel ? parseInt(pageSizeSel.value, 10) : 25, sortCol: -1, sortDir: 1 };

        function visibleRows() {
            if (!state.query) { return allRows; }
            var q = state.query.toLowerCase();
            return allRows.filter(function (tr) {
                return tr.textContent.toLowerCase().indexOf(q) !== -1;
            });
        }

        function render() {
            var rows = visibleRows();

            if (state.sortCol >= 0) {
                var col = state.sortCol;
                var dir = state.sortDir;
                rows = rows.slice().sort(function (a, b) {
                    var na = numOf(a.cells[col]);
                    var nb = numOf(b.cells[col]);
                    if (na !== null && nb !== null) { return (na - nb) * dir; }
                    return textOf(a.cells[col]).localeCompare(textOf(b.cells[col]), 'es', { numeric: true }) * dir;
                });
            }

            var total = rows.length;
            var size = state.pageSize > 0 ? state.pageSize : total || 1;
            var pages = Math.max(1, Math.ceil(total / size));
            if (state.page > pages) { state.page = pages; }
            var start = (state.page - 1) * size;
            var pageRows = state.pageSize > 0 ? rows.slice(start, start + size) : rows;

            while (tbody.firstChild) { tbody.removeChild(tbody.firstChild); }
            pageRows.forEach(function (tr) { tbody.appendChild(tr); });

            if (infoEl) {
                infoEl.textContent = total === 0
                    ? 'Sin filas'
                    : 'Mostrando ' + (total === 0 ? 0 : start + 1) + '–' + Math.min(start + pageRows.length, total) + ' de ' + total + ' filas';
            }
            if (navEl) {
                navEl.innerHTML = '';
                if (pages > 1 && state.pageSize > 0) {
                    var mk = function (label, page, disabled, active) {
                        var li = document.createElement('li');
                        li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                        var a = document.createElement('a');
                        a.className = 'page-link';
                        a.href = '#';
                        a.textContent = label;
                        a.addEventListener('click', function (e) {
                            e.preventDefault();
                            if (!disabled && !active) { state.page = page; render(); }
                        });
                        li.appendChild(a);
                        navEl.appendChild(li);
                    };
                    mk('«', state.page - 1, state.page <= 1, false);
                    var from = Math.max(1, state.page - 3);
                    var to = Math.min(pages, from + 6);
                    from = Math.max(1, to - 6);
                    for (var p = from; p <= to; p++) { mk(String(p), p, false, p === state.page); }
                    mk('»', state.page + 1, state.page >= pages, false);
                }
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                state.query = searchInput.value.trim();
                state.page = 1;
                render();
            });
        }
        if (pageSizeSel) {
            pageSizeSel.addEventListener('change', function () {
                state.pageSize = parseInt(pageSizeSel.value, 10);
                state.page = 1;
                render();
            });
        }

        // Ordenamiento al hacer clic en el encabezado
        var headRow = table.tHead ? table.tHead.rows[table.tHead.rows.length - 1] : null;
        if (headRow) {
            Array.prototype.forEach.call(headRow.cells, function (th, idx) {
                if (th.hasAttribute('data-nosort')) { return; }
                th.style.cursor = 'pointer';
                th.title = 'Clic para ordenar';
                th.addEventListener('click', function () {
                    if (state.sortCol === idx) {
                        state.sortDir = -state.sortDir;
                    } else {
                        state.sortCol = idx;
                        state.sortDir = 1;
                    }
                    Array.prototype.forEach.call(headRow.cells, function (h) {
                        h.classList.remove('an-sort-asc', 'an-sort-desc');
                    });
                    th.classList.add(state.sortDir === 1 ? 'an-sort-asc' : 'an-sort-desc');
                    render();
                });
            });
        }

        // Al imprimir: mostrar todas las filas filtradas; restaurar después
        window.addEventListener('beforeprint', function () {
            var rows = visibleRows();
            while (tbody.firstChild) { tbody.removeChild(tbody.firstChild); }
            rows.forEach(function (tr) { tbody.appendChild(tr); });
        });
        window.addEventListener('afterprint', render);

        render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(document.querySelectorAll('.an-tools[data-table]'), initTable);
    });
})();
