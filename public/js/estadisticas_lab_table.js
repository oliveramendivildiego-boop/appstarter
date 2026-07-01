/**
 * Ordenamiento del desglose por prueba en Estadísticas de laboratorio.
 * Clic en el botón junto a cada encabezado: ascendente → descendente → orden inicial.
 */
(function () {
    'use strict';

    function textVal(row, col) {
        var cell = row.cells[col];
        return cell ? cell.textContent.trim() : '';
    }

    function numVal(row, col) {
        var n = parseInt(textVal(row, col).replace(/\D/g, ''), 10);
        return isNaN(n) ? 0 : n;
    }

    function renumberRows(tbody) {
        var n = 0;
        Array.prototype.forEach.call(tbody.rows, function (tr) {
            if (tr.cells[0]) {
                tr.cells[0].textContent = String(++n);
            }
        });
    }

    function initTable(table) {
        var tbody = table.tBodies[0];
        if (!tbody) {
            return;
        }

        var originalRows = Array.prototype.slice.call(tbody.rows);
        var sortState = { col: -1, dir: 0 };

        function applySort() {
            var rows = originalRows.slice();
            if (sortState.col >= 0 && sortState.dir !== 0) {
                var col = sortState.col;
                var dir = sortState.dir;
                var btn = table.querySelector('.est-sort-btn[data-col="' + col + '"]');
                var type = btn ? btn.getAttribute('data-type') : 'text';
                rows.sort(function (a, b) {
                    var cmp;
                    if (type === 'number') {
                        cmp = numVal(a, col) - numVal(b, col);
                    } else {
                        cmp = textVal(a, col).localeCompare(textVal(b, col), 'es', { sensitivity: 'base', numeric: true });
                    }
                    return cmp * dir;
                });
            }

            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            rows.forEach(function (tr) {
                tbody.appendChild(tr);
            });
            renumberRows(tbody);
        }

        function updateButtons() {
            Array.prototype.forEach.call(table.querySelectorAll('.est-sort-btn'), function (btn) {
                var col = parseInt(btn.getAttribute('data-col'), 10);
                btn.classList.remove('active', 'asc', 'desc');
                btn.textContent = '⇅';
                btn.setAttribute('aria-label', 'Ordenar columna');
                if (col === sortState.col && sortState.dir !== 0) {
                    btn.classList.add('active', sortState.dir === 1 ? 'asc' : 'desc');
                    btn.textContent = sortState.dir === 1 ? '↑' : '↓';
                    btn.setAttribute('aria-label', sortState.dir === 1 ? 'Orden ascendente (clic para descendente)' : 'Orden descendente (clic para restaurar)');
                }
            });
        }

        Array.prototype.forEach.call(table.querySelectorAll('.est-sort-btn'), function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var col = parseInt(btn.getAttribute('data-col'), 10);
                if (sortState.col !== col) {
                    sortState.col = col;
                    sortState.dir = 1;
                } else if (sortState.dir === 1) {
                    sortState.dir = -1;
                } else if (sortState.dir === -1) {
                    sortState.col = -1;
                    sortState.dir = 0;
                } else {
                    sortState.dir = 1;
                }
                updateButtons();
                applySort();
            });
        });

        window.addEventListener('beforeprint', function () {
            applySort();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var table = document.getElementById('tabla-estadisticas-prueba');
        if (table) {
            initTable(table);
        }
    });
})();
