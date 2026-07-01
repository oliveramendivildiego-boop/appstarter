/**
 * Filtro en cliente para tablas de listados de reportes (/reports).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('filtro_listado_reporte');
        var table = document.getElementById('tabla_listado_reporte');
        if (!input || !table || !table.tBodies[0]) {
            return;
        }
        var tbody = table.tBodies[0];
        var info = document.getElementById('filtro_listado_reporte_info');

        function applyFilter() {
            var q = input.value.trim().toLowerCase();
            var visible = 0;
            Array.prototype.forEach.call(tbody.rows, function (tr) {
                var show = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
                tr.style.display = show ? '' : 'none';
                if (show) {
                    visible++;
                }
            });
            if (info) {
                info.textContent = q === ''
                    ? ''
                    : visible + ' fila(s) coinciden con «' + input.value.trim() + '»';
            }
        }

        input.addEventListener('input', applyFilter);
    });
})();
