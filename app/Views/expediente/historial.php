<?php
$isDoctorPortal = !empty(isset($doctor_portal) ? $doctor_portal : false);
$reportUrlBase = isset($report_url_base) ? $report_url_base : site_url('registers/viewreport/');
$pdfUrlBase = isset($pdf_url_base) ? $pdf_url_base : site_url('registers/pdf/');
$editUrlBase = isset($edit_url_base) ? $edit_url_base : site_url('registers/view/');
$backUrl = isset($back_url) ? $back_url : site_url('expediente');
$backLabel = isset($back_label) ? $back_label : 'Buscar otro paciente';
$chartDataUrl = isset($chart_data_url) ? $chart_data_url : '';
$pruebasPaciente = isset($pruebas_paciente) ? $pruebas_paciente : [];
$totalRegistrosCount = isset($total_registros_count) ? (int) $total_registros_count : count($registros);
$totalAntecedentesCount = isset($total_antecedentes_count) ? (int) $total_antecedentes_count : (isset($antecedentes) && is_array($antecedentes) ? count($antecedentes) : 0);
$expedientePerPage = isset($expediente_per_page) ? (int) $expediente_per_page : 15;
$hPage = isset($h_page) ? (int) $h_page : 1;
$aPage = isset($a_page) ? (int) $a_page : 1;
$hTotalPages = isset($h_total_pages) ? (int) $h_total_pages : 0;
$aTotalPages = isset($a_total_pages) ? (int) $a_total_pages : 0;
$expedientePagerBase = isset($expediente_pager_base) ? $expediente_pager_base : site_url('expediente/view/' . (int) (isset($paciente->person_id) ? $paciente->person_id : 0));
$showAntecedentes = $totalAntecedentesCount > 0;
?>
<?php if ($isDoctorPortal): ?>
<?= $this->extend('layouts/doctor') ?>
<?php else: ?>
<?= $this->extend('layouts/main') ?>
<?php endif; ?>
<?= $this->section('head_extra') ?>
<?php if ($isDoctorPortal): ?>
<link rel="stylesheet" href="<?= base_url('assets/css/expediente.css') ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if ($isDoctorPortal): ?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= site_url('doctor/home') ?>">Mi panel</a></li>
        <li class="breadcrumb-item active"><?= esc(isset($pacienteNombre) ? $pacienteNombre : '') ?></li>
    </ol>
</nav>
<?php else: ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_home'), 'url' => site_url('home')],
    ['label' => 'Expediente', 'url' => site_url('expediente')],
    ['label' => isset($pacienteNombre) ? $pacienteNombre : '', 'url' => null],
]]) ?>
<?php endif; ?>

<?php if ($chartDataUrl !== ''): ?>
<div class="card shadow-sm mb-4 clinical-chart-card">
    <div class="card-header <?= $isDoctorPortal ? 'bg-info' : 'bg-primary' ?> text-white">
        <h5 class="mb-0"><i class="fa-solid fa-chart-line me-2"></i>Evolución clínica por analito</h5>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="prueba_select" class="form-label">Analito principal</label>
                <select id="prueba_select" class="form-select">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($pruebasPaciente as $prueba): ?>
                        <?php
                        $pruebaKey = is_array($prueba) ? (string) (isset($prueba['key']) ? $prueba['key'] : '') : (string) $prueba;
                        $pruebaLabel = is_array($prueba) ? (string) (isset($prueba['label']) ? $prueba['label'] : $pruebaKey) : (string) $prueba;
                        ?>
                        <option value="<?= esc($pruebaKey) ?>"><?= esc($pruebaLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <label for="prueba_compare_select" class="form-label">Comparar con</label>
                <select id="prueba_compare_select" class="form-select">
                    <option value="">Sin comparación</option>
                    <?php foreach ($pruebasPaciente as $prueba): ?>
                        <?php
                        $pruebaKey = is_array($prueba) ? (string) (isset($prueba['key']) ? $prueba['key'] : '') : (string) $prueba;
                        $pruebaLabel = is_array($prueba) ? (string) (isset($prueba['label']) ? $prueba['label'] : $pruebaKey) : (string) $prueba;
                        ?>
                        <option value="<?= esc($pruebaKey) ?>"><?= esc($pruebaLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label for="chart_range" class="form-label">Rango</label>
                <select id="chart_range" class="form-select">
                    <option value="90d">90 días</option>
                    <option value="30d">30 días</option>
                    <option value="180d">6 meses</option>
                    <option value="1y">1 año</option>
                    <option value="all" selected>Todo</option>
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <button type="button" id="btn_ver_grafico" class="btn <?= $isDoctorPortal ? 'btn-info text-white' : 'btn-primary' ?> w-100">
                    <i class="fa-solid fa-chart-column me-1"></i> Ver gráfico
                </button>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label for="chart_date_from" class="form-label">Desde</label>
                <input type="text" id="chart_date_from" class="form-control flatpickr-input clinical-date-input" autocomplete="off" placeholder="Seleccionar fecha">
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label for="chart_date_to" class="form-label">Hasta</label>
                <input type="text" id="chart_date_to" class="form-control flatpickr-input clinical-date-input" autocomplete="off" placeholder="Seleccionar fecha">
            </div>
        </div>
        <div class="mt-3">
            <small id="chart_help" class="text-muted">Seleccione un analito para visualizar evolución, rango normal, tendencia y valores críticos.</small>
        </div>
        <div id="trend_summary" class="clinical-chart-summary d-none mt-3"></div>
        <div class="mt-3 d-flex justify-content-center">
            <div class="clinical-chart-canvas">
                <canvas id="pruebaChart"></canvas>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-user-doctor me-2"></i>Expediente del paciente</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Paciente:</strong> <?= esc($pacienteNombre) ?></p>
                <p class="mb-1"><strong>Edad:</strong> <?= esc(isset($paciente->edad_texto) ? $paciente->edad_texto : '-') ?></p>
                <p class="mb-1"><strong>Teléfono:</strong> <?= esc(isset($paciente->phone_number) ? $paciente->phone_number : '-') ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Correo:</strong> <?= esc(isset($paciente->email) ? $paciente->email : '-') ?></p>
                <p class="mb-1"><strong>Dirección:</strong> <?= esc(isset($paciente->address_1) ? $paciente->address_1 : '-') ?></p>
                <p class="mb-0"><strong>Total de estudios:</strong> <?= (int) $totalRegistrosCount ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="<?= $backUrl ?>" class="btn btn-secondary"><i class="fa-solid fa-search me-1"></i> <?= esc($backLabel) ?></a>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-book-medical me-2"></i>Historial de estudios</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($registros)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fa-solid fa-clipboard-list fa-3x mb-3"></i>
                <p>No hay estudios con resultados completados para este paciente.</p>
                <a href="<?= site_url('registers') ?>" class="btn btn-primary">Crear nuevo registro</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Orden</th>
                            <th>Fecha</th>
                            <th>Médico</th>
                            <th>Total</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                            <tr>
                                <td><?= esc(isset($r->registro_id) ? $r->registro_id : '') ?></td>
                                <td><?= esc($r->ingreso ? date('d/m/Y H:i', strtotime($r->ingreso)) : '-') ?></td>
                                <td><?= esc(isset($r->doctor) ? $r->doctor : '-') ?></td>
                                <td><?= esc(isset($r->total) ? $r->total : '-') ?></td>
                                <td class="text-center">
                                    <a href="<?= $reportUrlBase . (isset($r->registro_id) ? $r->registro_id : '') ?>" class="btn btn-sm btn-primary" target="_blank" title="Ver reporte">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </a>
                                    <a href="<?= $pdfUrlBase . (isset($r->registro_id) ? $r->registro_id : '') ?>" class="btn btn-sm btn-success" target="_blank" title="Descargar PDF">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                    <?php if (!$isDoctorPortal): ?>
                                        <a href="<?= $editUrlBase . (isset($r->registro_id) ? $r->registro_id : '') ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalRegistrosCount > 0) : ?>
                <?= view('expediente/pager', [
                    'pager_base'  => $expedientePagerBase,
                    'this_param'  => 'h_page',
                    'other_param' => 'a_page',
                    'this_page'   => $hPage,
                    'other_page'  => $aPage,
                    'total_pages' => $hTotalPages,
                    'total_items' => $totalRegistrosCount,
                    'per_page'    => $expedientePerPage,
                ]) ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($showAntecedentes): ?>
<div class="card shadow-sm mt-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-history me-2"></i>Antecedentes (estudios previos)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small">Registros anteriores del mismo paciente para comparar resultados.</p>
        <ul class="list-group list-group-flush">
            <?php foreach ($antecedentes as $ant): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        Orden #<?= esc($ant->registro_id) ?> - <?= esc(date('d/m/Y', strtotime(isset($ant->ingreso) ? $ant->ingreso : 'now'))) ?>
                    </span>
                    <span>
                        <a href="<?= $reportUrlBase . (isset($ant->registro_id) ? $ant->registro_id : '') ?>" class="btn btn-sm btn-outline-primary" target="_blank">Ver</a>
                        <a href="<?= $pdfUrlBase . (isset($ant->registro_id) ? $ant->registro_id : '') ?>" class="btn btn-sm btn-outline-success" target="_blank">PDF</a>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?= view('expediente/pager', [
            'pager_base'  => $expedientePagerBase,
            'this_param'  => 'a_page',
            'other_param' => 'h_page',
            'this_page'   => $aPage,
            'other_page'  => $hPage,
            'total_pages' => $aTotalPages,
            'total_items' => $totalAntecedentesCount,
            'per_page'    => $expedientePerPage,
        ]) ?>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($chartDataUrl !== ''): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartCanvas = document.getElementById('pruebaChart');
    var chartHelp = document.getElementById('chart_help');
    var btn = document.getElementById('btn_ver_grafico');
    var pruebaSelect = document.getElementById('prueba_select');
    var compareSelect = document.getElementById('prueba_compare_select');
    var rangeSelect = document.getElementById('chart_range');
    var dateFromInput = document.getElementById('chart_date_from');
    var dateToInput = document.getElementById('chart_date_to');
    var trendSummary = document.getElementById('trend_summary');
    var chart = null;

    if (!chartCanvas || !btn || !pruebaSelect || typeof Chart === 'undefined') {
        return;
    }

    if (typeof flatpickr !== 'undefined') {
        var datePickerOptions = {
            dateFormat: 'Y-m-d',
            locale: 'es',
            allowInput: true,
            onOpen: function(selectedDates, dateStr, instance) {
                if (typeof flatpickrPositionArrowTopLeft === 'function') {
                    flatpickrPositionArrowTopLeft(instance);
                }
            }
        };
        if (dateFromInput) flatpickr(dateFromInput, datePickerOptions);
        if (dateToInput) flatpickr(dateToInput, datePickerOptions);
    }

    function escHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function valueText(value) {
        return value === null || typeof value === 'undefined' ? '-' : String(value);
    }

    function pointForLabel(serie, label) {
        var points = Array.isArray(serie.points) ? serie.points : [];
        for (var i = 0; i < points.length; i++) {
            if (points[i].date_label === label) return points[i];
        }
        return null;
    }

    function hasNumericValue(values) {
        return Array.isArray(values) && values.some(function(value) {
            return value !== null && typeof value !== 'undefined' && !isNaN(Number(value));
        });
    }

    function chartBounds(values) {
        var nums = [];
        (values || []).forEach(function(value) {
            if (value !== null && typeof value !== 'undefined' && !isNaN(Number(value))) nums.push(Number(value));
        });
        if (nums.length === 0) return {};
        var min = Math.min.apply(null, nums);
        var max = Math.max.apply(null, nums);
        var padding = Math.max((max - min) * 0.18, Math.abs(max || min || 1) * 0.08, 1);
        return {
            suggestedMin: min - padding,
            suggestedMax: max + padding
        };
    }

    function buildSummary(payload) {
        if (!trendSummary) return;
        var series = Array.isArray(payload.series) ? payload.series : [];
        if (series.length === 0 || !series[0].summary) {
            trendSummary.classList.add('d-none');
            trendSummary.innerHTML = '';
            return;
        }

        trendSummary.innerHTML = series.map(function(serie, idx) {
            var summary = serie.summary || {};
            var change = summary.change || null;
            var badge = summary.status_class || 'secondary';
            var changeLabel = change ? change.label : 'Sin previo';
            var clinicallyRelevant = summary.clinically_relevant_change ? '<span class="clinical-summary-flag">Cambio relevante</span>' : '';
            return '' +
                '<div class="clinical-summary-card ' + (idx > 0 ? 'compare' : '') + '">' +
                    '<div class="clinical-summary-label">' + escHtml(idx === 0 ? 'Último valor' : 'Comparativo') + '</div>' +
                    '<div class="clinical-summary-title">' + escHtml(serie.label || 'Analito') + '</div>' +
                    '<div class="clinical-summary-value">' + escHtml(valueText(summary.last_raw_value || summary.last_value)) + '</div>' +
                    '<div class="clinical-summary-meta">' +
                        '<span class="badge bg-' + escHtml(badge) + '">' + escHtml(summary.status || '-') + '</span>' +
                        '<span>' + escHtml(summary.last_date || '-') + '</span>' +
                        '<span>' + escHtml(changeLabel) + '</span>' +
                    '</div>' +
                    '<div class="clinical-summary-trend"><strong>Tendencia:</strong> ' + escHtml(summary.trend_direction || '-') + '. ' + escHtml(summary.trend_interpretation || '') + '</div>' +
                    clinicallyRelevant +
                '</div>';
        }).join('');
        trendSummary.classList.remove('d-none');
    }

    function normalBandDatasets(payload) {
        if (!hasNumericValue(payload.minimo) || !hasNumericValue(payload.maximo)) {
            return [];
        }

        return [
            {
                label: 'Rango normal mínimo',
                data: payload.minimo || [],
                borderColor: 'rgba(16, 185, 129, 0)',
                backgroundColor: 'rgba(16, 185, 129, 0)',
                pointRadius: 0,
                yAxisID: 'y',
                tension: 0.2,
                clinicalHelper: true,
                order: 5
            },
            {
                label: 'Banda rango normal',
                data: payload.maximo || [],
                borderColor: 'rgba(16, 185, 129, 0.25)',
                backgroundColor: 'rgba(16, 185, 129, 0.14)',
                pointRadius: 0,
                yAxisID: 'y',
                tension: 0.2,
                fill: '-1',
                clinicalHelper: true,
                order: 4
            }
        ];
    }

    function criticalDatasets(payload) {
        var series = Array.isArray(payload.series) ? payload.series : [];
        var primary = series[0] || {};
        var datasets = [];
        if (hasNumericValue(primary.critico_min)) {
            datasets.push({
                label: 'Crítico bajo',
                data: primary.critico_min || [],
                borderColor: 'rgba(185, 28, 28, 0.65)',
                borderDash: [4, 4],
                pointRadius: 0,
                yAxisID: 'y',
                tension: 0.1,
                clinicalHelper: true,
                order: 3
            });
        }
        if (hasNumericValue(primary.critico_max)) {
            datasets.push({
                label: 'Crítico alto',
                data: primary.critico_max || [],
                borderColor: 'rgba(185, 28, 28, 0.65)',
                borderDash: [4, 4],
                pointRadius: 0,
                yAxisID: 'y',
                tension: 0.1,
                clinicalHelper: true,
                order: 3
            });
        }

        return datasets;
    }

    function resultDatasets(payload) {
        var palette = [
            { border: '#2563eb', fill: 'rgba(37, 99, 235, 0.14)' },
            { border: '#7c3aed', fill: 'rgba(124, 58, 237, 0.10)' }
        ];
        var labels = payload.labels || [];
        var series = Array.isArray(payload.series) ? payload.series : [];

        return series.map(function(serie, idx) {
            var color = palette[idx] || palette[0];
            return {
                label: serie.label || (idx === 0 ? 'Resultado' : 'Comparativo'),
                data: serie.valor || [],
                borderColor: color.border,
                backgroundColor: color.fill,
                tension: 0.25,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBorderWidth: 2,
                pointBorderColor: '#ffffff',
                pointBackgroundColor: labels.map(function(label) {
                    var point = pointForLabel(serie, label);
                    if (point && point.status === 'Crítico') return '#b91c1c';
                    if (point && (point.status === 'Alto' || point.status === 'Bajo')) return '#d97706';
                    return color.border;
                }),
                pointStyle: labels.map(function(label) {
                    var point = pointForLabel(serie, label);
                    return point && point.status === 'Crítico' ? 'triangle' : 'circle';
                }),
                spanGaps: true,
                yAxisID: idx === 0 ? 'y' : 'y1',
                clinicalSerieIndex: idx,
                order: idx === 0 ? 1 : 2
            };
        });
    }

    function yScaleOptions(payload) {
        var series = Array.isArray(payload.series) ? payload.series : [];
        var primary = series[0] || {};
        var compare = series[1] || {};
        var primaryBounds = chartBounds([]
            .concat(primary.valor || [])
            .concat(payload.minimo || [])
            .concat(payload.maximo || []));
        var compareBounds = chartBounds(compare.valor || []);

        return [
            {
                beginAtZero: false,
                suggestedMin: primaryBounds.suggestedMin,
                suggestedMax: primaryBounds.suggestedMax,
                grid: { color: 'rgba(15, 23, 42, 0.06)' },
                ticks: {
                    color: '#334155',
                    font: { size: 11 }
                },
                title: {
                    display: true,
                    text: primary.label || 'Analito principal',
                    color: '#2563eb',
                    font: { size: 11, weight: 'bold' }
                }
            },
            {
                type: 'linear',
                display: !!compare.key,
                position: 'right',
                beginAtZero: false,
                suggestedMin: compareBounds.suggestedMin,
                suggestedMax: compareBounds.suggestedMax,
                grid: { drawOnChartArea: false },
                ticks: {
                    color: '#6d28d9',
                    font: { size: 11 }
                },
                title: {
                    display: !!compare.key,
                    text: compare.label || 'Comparativo',
                    color: '#7c3aed',
                    font: { size: 11, weight: 'bold' }
                }
            }
        ];
    }

    function drawChart(payload, pruebaNombre) {
        if (chart) {
            chart.destroy();
        }
        var datasets = normalBandDatasets(payload)
            .concat(criticalDatasets(payload))
            .concat(resultDatasets(payload));
        var yOptions = yScaleOptions(payload);

        chart = new Chart(chartCanvas, {
            type: 'line',
            data: {
                labels: payload.labels || [],
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolución de ' + pruebaNombre,
                        font: { size: 18, weight: 'bold' },
                        padding: 20
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 12 },
                            padding: 15,
                            usePointStyle: true,
                            filter: function(item, data) {
                                var dataset = data.datasets[item.datasetIndex] || {};
                                return dataset.clinicalHelper !== true;
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.94)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        filter: function(item) {
                            return typeof item.dataset.clinicalSerieIndex !== 'undefined' && item.raw !== null;
                        },
                        callbacks: {
                            label: function(context) {
                                var serie = (payload.series || [])[context.dataset.clinicalSerieIndex] || null;
                                if (!serie) return context.dataset.label + ': ' + context.formattedValue;
                                var point = pointForLabel(serie, context.label);
                                if (!point) return context.dataset.label + ': ' + context.formattedValue;
                                var range = (point.min !== null || point.max !== null)
                                    ? 'Rango: ' + valueText(point.min) + ' - ' + valueText(point.max)
                                    : 'Rango: no configurado';
                                var change = point.change ? 'Cambio: ' + point.change.label + ' vs anterior' : 'Cambio: sin resultado previo';
                                return [
                                    (serie.label || 'Analito') + ': ' + valueText(point.raw_value || point.value),
                                    'Fecha: ' + valueText(point.date_label),
                                    'Estado: ' + valueText(point.status),
                                    range,
                                    change,
                                    'Interpretación: ' + valueText(point.interpretation)
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: yOptions[0].beginAtZero,
                        suggestedMin: yOptions[0].suggestedMin,
                        suggestedMax: yOptions[0].suggestedMax,
                        grid: yOptions[0].grid,
                        ticks: yOptions[0].ticks,
                        title: yOptions[0].title
                    },
                    y1: {
                        type: yOptions[1].type,
                        display: yOptions[1].display,
                        position: yOptions[1].position,
                        beginAtZero: yOptions[1].beginAtZero,
                        suggestedMin: yOptions[1].suggestedMin,
                        suggestedMax: yOptions[1].suggestedMax,
                        grid: yOptions[1].grid,
                        ticks: yOptions[1].ticks,
                        title: yOptions[1].title
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            font: { size: 11 },
                            maxRotation: 45,
                            minRotation: 0,
                            maxTicksLimit: 10
                        }
                    }
                }
            }
        });
    }

    function loadChart() {
        var pruebaKey = (pruebaSelect.value || '').trim();
        var pruebaLabel = pruebaSelect.options[pruebaSelect.selectedIndex] ? (pruebaSelect.options[pruebaSelect.selectedIndex].text || '') : '';
        if (!pruebaKey) {
            uiAlert('Seleccione una prueba', 'Validación');
            return;
        }

        var params = new URLSearchParams();
        params.set('prueba', pruebaKey);
        if (compareSelect && compareSelect.value && compareSelect.value !== pruebaKey) params.set('compare', compareSelect.value);
        if (rangeSelect && rangeSelect.value) params.set('range', rangeSelect.value);
        if (dateFromInput && dateFromInput.value) params.set('date_from', dateFromInput.value);
        if (dateToInput && dateToInput.value) params.set('date_to', dateToInput.value);

        fetch('<?= esc($chartDataUrl) ?>?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data || data.success !== true) {
                chartHelp.textContent = (data && data.message) ? data.message : 'No se pudo cargar la gráfica.';
                return;
            }
            if (!Array.isArray(data.labels) || data.labels.length === 0) {
                chartHelp.textContent = 'No hay datos históricos para esta prueba.';
                if (trendSummary) {
                    trendSummary.classList.add('d-none');
                    trendSummary.textContent = '';
                }
                if (chart) chart.destroy();
                return;
            }
            chartHelp.textContent = 'Mostrando evolución histórica con rango normal, tendencia y marcadores críticos.';
            buildSummary(data);
            drawChart(data, pruebaLabel || pruebaKey);
        })
        .catch(function() {
            chartHelp.textContent = 'Error de conexión al cargar la gráfica.';
        });
    }

    btn.addEventListener('click', loadChart);
    [rangeSelect, dateFromInput, dateToInput, compareSelect].forEach(function(el) {
        if (!el) return;
        el.addEventListener('change', function() {
            if ((pruebaSelect.value || '').trim() !== '') loadChart();
        });
    });
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
