<?php
$isDoctorPortal = !empty($doctor_portal ?? false);
$reportUrlBase = $report_url_base ?? site_url('registers/viewreport/');
$pdfUrlBase = $pdf_url_base ?? site_url('registers/pdf/');
$editUrlBase = $edit_url_base ?? site_url('registers/view/');
$backUrl = $back_url ?? site_url('expediente');
$backLabel = $back_label ?? 'Buscar otro paciente';
$chartDataUrl = $chart_data_url ?? '';
$pruebasPaciente = $pruebas_paciente ?? [];
?>
<?php if ($isDoctorPortal): ?>
<?= $this->extend('layouts/doctor') ?>
<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/expediente.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?= $this->endSection() ?>
<?php else: ?>
<?= $this->extend('layouts/main') ?>
<?php endif; ?>

<?= $this->section('content') ?>
<?php if ($isDoctorPortal): ?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= site_url('doctor/home') ?>">Mi panel</a></li>
        <li class="breadcrumb-item active"><?= esc($pacienteNombre ?? '') ?></li>
    </ol>
</nav>
<?php else: ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_home'), 'url' => site_url('home')],
    ['label' => 'Expediente', 'url' => site_url('expediente')],
    ['label' => $pacienteNombre ?? '', 'url' => null],
]]) ?>
<?php endif; ?>

<?php if ($isDoctorPortal): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fa-solid fa-chart-line me-2"></i>Evolución por prueba</h5>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-8">
                <label for="prueba_select" class="form-label">Seleccione una prueba</label>
                <select id="prueba_select" class="form-select">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($pruebasPaciente as $prueba): ?>
                        <?php
                        $pruebaKey = is_array($prueba) ? (string) ($prueba['key'] ?? '') : (string) $prueba;
                        $pruebaLabel = is_array($prueba) ? (string) ($prueba['label'] ?? $pruebaKey) : (string) $prueba;
                        ?>
                        <option value="<?= esc($pruebaKey) ?>"><?= esc($pruebaLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <button type="button" id="btn_ver_grafico" class="btn btn-info text-white w-100">
                    <i class="fa-solid fa-chart-column me-1"></i> Ver gráfico
                </button>
            </div>
        </div>
        <div class="mt-3">
            <small id="chart_help" class="text-muted">Seleccione una prueba para visualizar su evolución en el tiempo.</small>
        </div>
        <div class="mt-3 d-flex justify-content-center">
            <div style="width: 100%; max-width: 900px; height: 500px;">
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
                <p class="mb-1"><strong>Edad:</strong> <?= esc($paciente->edad_texto ?? '-') ?></p>
                <p class="mb-1"><strong>Teléfono:</strong> <?= esc($paciente->phone_number ?? '-') ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Correo:</strong> <?= esc($paciente->email ?? '-') ?></p>
                <p class="mb-1"><strong>Dirección:</strong> <?= esc($paciente->address_1 ?? '-') ?></p>
                <p class="mb-0"><strong>Total de estudios:</strong> <?= count($registros) ?></p>
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
                                <td><?= esc($r->registro_id ?? '') ?></td>
                                <td><?= esc($r->ingreso ? date('d/m/Y H:i', strtotime($r->ingreso)) : '-') ?></td>
                                <td><?= esc($r->doctor ?? '-') ?></td>
                                <td><?= esc($r->total ?? '-') ?></td>
                                <td class="text-center">
                                    <a href="<?= $reportUrlBase . ($r->registro_id ?? '') ?>" class="btn btn-sm btn-primary" target="_blank" title="Ver reporte">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </a>
                                    <a href="<?= $pdfUrlBase . ($r->registro_id ?? '') ?>" class="btn btn-sm btn-success" target="_blank" title="Descargar PDF">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                    <?php if (!$isDoctorPortal): ?>
                                        <a href="<?= $editUrlBase . ($r->registro_id ?? '') ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($antecedentes)): ?>
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
                        Orden #<?= esc($ant->registro_id) ?> - <?= esc(date('d/m/Y', strtotime($ant->ingreso ?? 'now'))) ?>
                    </span>
                    <span>
                        <a href="<?= $reportUrlBase . ($ant->registro_id ?? '') ?>" class="btn btn-sm btn-outline-primary" target="_blank">Ver</a>
                        <a href="<?= $pdfUrlBase . ($ant->registro_id ?? '') ?>" class="btn btn-sm btn-outline-success" target="_blank">PDF</a>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($isDoctorPortal): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartCanvas = document.getElementById('pruebaChart');
    var chartHelp = document.getElementById('chart_help');
    var btn = document.getElementById('btn_ver_grafico');
    var pruebaSelect = document.getElementById('prueba_select');
    var chart = null;

    if (!chartCanvas || !btn || !pruebaSelect || typeof Chart === 'undefined') {
        return;
    }

    function drawChart(payload, pruebaNombre) {
        if (chart) {
            chart.destroy();
        }
        chart = new Chart(chartCanvas, {
            type: 'line',
            data: {
                labels: payload.labels || [],
                datasets: [
                    {
                        label: 'Resultado',
                        data: payload.valor || [],
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.15)',
                        tension: 0.2
                    },
                    {
                        label: 'Referencia mínima',
                        data: payload.minimo || [],
                        borderColor: 'rgb(255, 159, 64)',
                        borderDash: [6, 4],
                        tension: 0.2
                    },
                    {
                        label: 'Referencia máxima',
                        data: payload.maximo || [],
                        borderColor: 'rgb(255, 99, 132)',
                        borderDash: [6, 4],
                        tension: 0.2
                    }
                ]
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
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            font: { size: 11 }
                        }
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

    btn.addEventListener('click', function() {
        var pruebaKey = (pruebaSelect.value || '').trim();
        var pruebaLabel = pruebaSelect.options[pruebaSelect.selectedIndex] ? (pruebaSelect.options[pruebaSelect.selectedIndex].text || '') : '';
        if (!pruebaKey) {
            uiAlert('Seleccione una prueba', 'Validación');
            return;
        }

        fetch('<?= esc($chartDataUrl) ?>?prueba=' + encodeURIComponent(pruebaKey), {
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
                if (chart) chart.destroy();
                return;
            }
            chartHelp.textContent = 'Mostrando evolución histórica de la prueba seleccionada.';
            drawChart(data, pruebaLabel || pruebaKey);
        })
        .catch(function() {
            chartHelp.textContent = 'Error de conexión al cargar la gráfica.';
        });
    });
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
