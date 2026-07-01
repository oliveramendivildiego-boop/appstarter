/**
 * Gráficos del tablero SNIS — estadísticas de laboratorio.
 * Requiere Chart.js y window.SNIS_CHART_DATA.
 */
(function () {
    'use strict';

    var data = window.SNIS_CHART_DATA || {};
    if (typeof Chart === 'undefined') {
        return;
    }

    var palette = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#0dcaf0', '#6610f2', '#6c757d'];
    var defaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
    };

    function truncate(s, max) {
        s = String(s || '');
        return s.length > max ? s.slice(0, max - 1) + '…' : s;
    }

    // Evolución diaria
    var evo = data.evolucion_diaria || {};
    var elEvo = document.getElementById('chartEvolucion');
    if (elEvo && (evo.labels || []).length) {
        new Chart(elEvo, {
            type: 'line',
            data: {
                labels: evo.labels,
                datasets: [
                    { label: 'Órdenes solicitadas', data: evo.ordenes_solicitadas || [], borderColor: palette[0], backgroundColor: 'rgba(13,110,253,.1)', tension: 0.25, fill: true },
                    { label: 'Órdenes procesadas', data: evo.ordenes_procesadas || [], borderColor: palette[1], tension: 0.25 },
                    { label: 'Pruebas solicitadas', data: evo.pruebas_solicitadas || [], borderColor: palette[2], borderDash: [4, 2], tension: 0.25 }
                ]
            },
            options: Object.assign({}, defaults, { scales: { x: { ticks: { maxRotation: 45, font: { size: 10 } } } } })
        });
    }

    // Procesadas vs pendientes
    var pp = data.procesadas_pendientes || {};
    var elPend = document.getElementById('chartPendientes');
    if (elPend && ((pp.procesadas || 0) + (pp.pendientes || 0)) > 0) {
        new Chart(elPend, {
            type: 'doughnut',
            data: {
                labels: ['Procesadas', 'Pendientes'],
                datasets: [{ data: [pp.procesadas || 0, pp.pendientes || 0], backgroundColor: [palette[1], palette[3]] }]
            },
            options: defaults
        });
    }

    // Top 10
    var top10 = data.top10 || [];
    var elTop = document.getElementById('chartTop10');
    if (elTop && top10.length) {
        new Chart(elTop, {
            type: 'bar',
            data: {
                labels: top10.map(function (r) { return truncate(r.prueba, 28); }),
                datasets: [{ label: 'Órdenes', data: top10.map(function (r) { return r.ordenes_con_prueba || 0; }), backgroundColor: palette[0] }]
            },
            options: Object.assign({}, defaults, { indexAxis: 'y', plugins: { legend: { display: false } } })
        });
    }

    // Por categoría
    var cats = data.por_categoria || [];
    var elCat = document.getElementById('chartCategoria');
    if (elCat && cats.length) {
        new Chart(elCat, {
            type: 'bar',
            data: {
                labels: cats.map(function (c) { return truncate(c.categoria, 22); }),
                datasets: [
                    { label: 'Solicitadas', data: cats.map(function (c) { return c.solicitadas || 0; }), backgroundColor: palette[0] },
                    { label: 'Procesadas', data: cats.map(function (c) { return c.procesadas || 0; }), backgroundColor: palette[1] }
                ]
            },
            options: Object.assign({}, defaults, { scales: { x: { ticks: { maxRotation: 45, font: { size: 10 } } } } })
        });
    }

    // Sexo (pacientes)
    var sexo = data.sexo_pacientes || {};
    var elSexo = document.getElementById('chartSexo');
    if (elSexo) {
        new Chart(elSexo, {
            type: 'pie',
            data: {
                labels: ['Masculino', 'Femenino', 'No indicado'],
                datasets: [{ data: [sexo['1'] || 0, sexo['2'] || 0, sexo['_'] || 0], backgroundColor: [palette[0], palette[3], palette[9]] }]
            },
            options: defaults
        });
    }

    // Grupos etarios
    var edad = data.grupos_etarios || [];
    var elEdad = document.getElementById('chartEdad');
    if (elEdad && edad.length) {
        var edadFil = edad.filter(function (g) { return (g.ordenes || 0) > 0; });
        new Chart(elEdad, {
            type: 'bar',
            data: {
                labels: edadFil.map(function (g) { return truncate(g.nombre, 18); }),
                datasets: [{ label: 'Órdenes', data: edadFil.map(function (g) { return g.ordenes || 0; }), backgroundColor: palette[5] }]
            },
            options: Object.assign({}, defaults, { plugins: { legend: { display: false } } })
        });
    }

    // Comparación período anterior
    var cmp = data.comparacion || {};
    var elCmp = document.getElementById('chartComparacion');
    if (elCmp && cmp.disponible) {
        new Chart(elCmp, {
            type: 'bar',
            data: {
                labels: ['Órdenes', 'Pruebas'],
                datasets: [
                    { label: 'Período anterior', data: [cmp.ordenes_anterior || 0, cmp.pruebas_anterior || 0], backgroundColor: palette[9] },
                    { label: 'Período actual', data: [cmp.ordenes_actual || 0, cmp.pruebas_actual || 0], backgroundColor: palette[0] }
                ]
            },
            options: defaults
        });
    }
})();
