<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php
$userName = trim(($user_info->first_name ?? '') . ' ' . ($user_info->last_name_fa ?? ''));
$metaMes = 100;
$regMes = (int)($dashboard_stats['registers_month'] ?? 0);
$porcentajeMeta = $metaMes > 0 ? min(100, (int) round(($regMes / $metaMes) * 100)) : 0;
$currencySym = $currency_symbol ?? '$';
$ingresosMes = (float)($ingresos_mes ?? 0);
$pendientes = $pendientes ?? ['total_pendiente' => 0, 'cantidad' => 0];
$alertas = $alertas_insumos ?? ['vencidos' => 0, 'por_vencer' => 0, 'total' => 0, 'items' => []];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1 fw-bold"><?= $userName ? 'Bienvenido, ' . esc($userName) . '!' : 'Dashboard' ?></h1>
        <p class="text-muted mb-0 small">Resumen de actividad, registros e ingresos del laboratorio.</p>
    </div>
</div>

<!-- Widget meta del mes -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-semibold">Registros del mes</h6>
            <span class="badge bg-primary"><?= $porcentajeMeta ?>%</span>
        </div>
        <p class="text-muted small mb-2"><?= $regMes ?> de <?= $metaMes ?> registros. <?= $porcentajeMeta >= 100 ? '¡Meta cumplida!' : 'Siga registrando para alcanzar la meta.' ?></p>
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $porcentajeMeta ?>%;" aria-valuenow="<?= $porcentajeMeta ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</div>

<div class="row mb-4 g-3">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 ynex-stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="ynex-stat-icon primary me-3">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="ynex-stat-label">Pacientes</div>
                    <div class="ynex-stat-value"><?= (int)($dashboard_stats['customers'] ?? 0) ?></div>
                </div>
                <a href="<?= site_url('customers') ?>" class="btn btn-sm btn-outline-primary">Ver <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 ynex-stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="ynex-stat-icon success me-3">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="ynex-stat-label">Doctores</div>
                    <div class="ynex-stat-value"><?= (int)($dashboard_stats['doctors'] ?? 0) ?></div>
                </div>
                <a href="<?= site_url('doctors') ?>" class="btn btn-sm btn-outline-success">Ver <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 ynex-stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="ynex-stat-icon info me-3">
                    <i class="fa-solid fa-book-medical"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="ynex-stat-label">Registros hoy</div>
                    <div class="ynex-stat-value"><?= (int)($dashboard_stats['registers_today'] ?? 0) ?></div>
                </div>
                <a href="<?= site_url('registers') ?>" class="btn btn-sm btn-outline-info">Nuevo <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 ynex-stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="ynex-stat-icon warning me-3">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="ynex-stat-label">Empleados</div>
                    <div class="ynex-stat-value"><?= (int)($dashboard_stats['employees'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 ynex-stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="ynex-stat-icon success me-3">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="ynex-stat-label">Ingresos del mes</div>
                    <div class="ynex-stat-value"><?= $currencySym ?> <?= number_format($ingresosMes, 0, ',', '.') ?></div>
                </div>
                <a href="<?= site_url('reports/ingresosFecha') ?>" class="btn btn-sm btn-outline-success">Ver <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 ynex-stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="ynex-stat-icon primary me-3">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="ynex-stat-label">Pendientes por cobrar</div>
                    <div class="ynex-stat-value"><?= $currencySym ?> <?= number_format($pendientes['total_pendiente'], 0, ',', '.') ?></div>
                    <small class="text-muted"><?= (int)$pendientes['cantidad'] ?> orden(es)</small>
                </div>
                <a href="<?= site_url('reports/pagos') ?>" class="btn btn-sm btn-outline-primary">Ver <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<?php if ($alertas['total'] > 0): ?>
<!-- Alertas de insumos -->
<div class="alert alert-warning d-flex align-items-start gap-3 mb-4 alert-insumos-dashboard" role="alert">
    <i class="fa-solid fa-triangle-exclamation fa-2x mt-1 flex-shrink-0"></i>
    <div class="flex-grow-1">
        <h6 class="alert-heading mb-2"><i class="fa-solid fa-vial me-1"></i> Insumos con alerta de vencimiento</h6>
        <p class="mb-2">
            <?php if ($alertas['vencidos'] > 0): ?>
                <span class="badge bg-danger me-2"><?= $alertas['vencidos'] ?> vencido(s)</span>
            <?php endif; ?>
            <?php if ($alertas['por_vencer'] > 0): ?>
                <span class="badge bg-warning text-dark me-2"><?= $alertas['por_vencer'] ?> por vencer</span>
            <?php endif; ?>
            (próximos <?= (int)($alertas['dias_alerta'] ?? 40) ?> días)
        </p>
        <div class="table-responsive dashboard-alert-table">
            <table class="table table-sm table-bordered mb-0 small">
                <thead class="table-light">
                    <tr><th>Insumo</th><th>Lote</th><th>Vencimiento</th><th>Cant.</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($alertas['items'] as $it): ?>
                    <tr class="<?= ($it['estado'] ?? '') === 'vencido' ? 'table-danger' : 'table-warning' ?>">
                        <td><?= esc($it['reactivo_nombre'] ?? '-') ?></td>
                        <td><?= esc($it['codigo_lote'] ?? '-') ?></td>
                        <td><?= !empty($it['fecha_vencimiento']) ? date('d/m/Y', strtotime($it['fecha_vencimiento'])) : '-' ?></td>
                        <td><?= (int)($it['cantidad'] ?? 0) ?> <?= esc($it['unidad_base'] ?? $it['unidad'] ?? '') ?></td>
                        <td>
                            <?php if (($it['estado'] ?? '') === 'vencido'): ?>
                                <span class="badge bg-danger">Vencido</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><?= (int)($it['dias_restantes'] ?? 0) ?> días</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="<?= site_url('reports/insumosVencimiento') ?>" class="btn btn-sm btn-warning mt-2">Ver reporte completo <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>
</div>
<?php endif; ?>

<!-- Gráficas -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0"><i class="fa-solid fa-chart-line me-2"></i>Registros e ingresos (últimos 7 días)</h5>
            </div>
            <div class="card-body">
                <canvas id="chartLine" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0"><i class="fa-solid fa-chart-pie me-2"></i>Distribución por período</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="max-width: 220px;">
                    <canvas id="chartDonut"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cierres de pagos (snapshots guardados) -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Cierres de pagos (últimos 30 días)</h5>
                    <p class="text-muted small mb-0">Totales del <strong>snapshot</strong> agrupados por la <strong>fecha hasta</strong> del período de cada cierre (día en que termina el rango facturado). Varios cierres con la misma fecha hasta se suman.</p>
                </div>
                <a href="<?= site_url('reports/pagosCierres') ?>" class="btn btn-sm btn-outline-primary">Ver cierres guardados</a>
            </div>
            <div class="card-body">
                <canvas id="chartCierresPagos" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Ingresos por mes y Top doctores -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0"><i class="fa-solid fa-chart-column me-2"></i>Ingresos cobrados por mes</h5>
            </div>
            <div class="card-body">
                <canvas id="chartBarras" height="140"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fa-solid fa-user-doctor me-2"></i>Top doctores (este mes)</h5>
                <a href="<?= site_url('reports/porDoctor') ?>" class="btn btn-sm btn-outline-primary">Ver reporte</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($top_doctores)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Doctor</th><th class="text-end">Registros</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_doctores as $doc): ?>
                            <tr>
                                <td><?= esc($doc['doctor'] ?? '-') ?></td>
                                <td class="text-end"><?= (int)($doc['cantidad'] ?? 0) ?></td>
                                <td class="text-end fw-semibold"><?= $currencySym ?> <?= number_format((float)($doc['total'] ?? 0), 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-4 mb-0 small">Sin datos este mes</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0"><i class="fa-solid fa-chart-simple me-2"></i>Registros por período</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td>Hoy</td>
                            <td class="text-end fw-semibold"><?= (int)($dashboard_stats['registers_today'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Esta semana</td>
                            <td class="text-end fw-semibold"><?= (int)($dashboard_stats['registers_week'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Este mes</td>
                            <td class="text-end fw-semibold"><?= (int)($dashboard_stats['registers_month'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Total</td>
                            <td class="text-end fw-semibold"><?= (int)($dashboard_stats['registers_total'] ?? 0) ?></td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Registros recientes</h5>
                <a href="<?= site_url('registers/lista') ?>" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recent_registers)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Orden</th>
                                <th>Paciente</th>
                                <th>Doctor</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_registers as $reg): ?>
                            <tr>
                                <td><?= esc(registro_orden_display($reg)) ?></td>
                                <td><?= esc($reg->paciente ?? '-') ?></td>
                                <td><?= esc($reg->doctor ?? '-') ?></td>
                                <td><?= !empty($reg->ingreso) ? date('d/m/Y H:i', strtotime($reg->ingreso)) : '-' ?></td>
                                <td>
                                    <a href="<?= site_url('registers/viewreport/' . ($reg->registro_id ?? 0)) ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-5 mb-0">No hay registros recientes</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?php $this->section('scripts'); ?>
<script>
(function() {
    var chartData = <?= json_encode($chart_data ?? []) ?>;
    var donutData = <?= json_encode($donut_data ?? []) ?>;

    // Rellenar últimos 7 días para la gráfica lineal (días sin datos = 0)
    var last7 = [];
    for (var i = 6; i >= 0; i--) {
        var d = new Date();
        d.setDate(d.getDate() - i);
        var key = d.toISOString().slice(0, 10);
        var found = chartData.find(function(r) { return r.fecha === key; });
        last7.push({
            fecha: key,
            cantidad: found ? parseInt(found.cantidad, 10) : 0,
            cobrado: found ? parseFloat(found.cobrado) || 0 : 0
        });
    }

    // Gráfica lineal
    var ctxLine = document.getElementById('chartLine');
    if (ctxLine && typeof Chart !== 'undefined') {
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: last7.map(function(r) {
                    var d = new Date(r.fecha + 'T12:00:00');
                    return d.toLocaleDateString('es', { weekday: 'short', day: 'numeric', month: 'short' });
                }),
                datasets: [
                    {
                        label: 'Registros',
                        data: last7.map(function(r) { return r.cantidad; }),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Ingresos ($)',
                        data: last7.map(function(r) { return r.cobrado; }),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { type: 'linear', display: true, position: 'left' },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    // Gráfica donut
    var ctxDonut = document.getElementById('chartDonut');
    if (ctxDonut && typeof Chart !== 'undefined' && donutData.length) {
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: donutData.map(function(d) { return d.label; }),
                datasets: [{
                    data: donutData.map(function(d) { return d.value; }),
                    backgroundColor: donutData.map(function(d) { return d.color; }),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '60%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Gráfica de barras - Ingresos por mes
    var cierresPagosSeries = <?= json_encode($cierres_pagos_series ?? []) ?>;
    var currencyLabel = <?= json_encode($currency_symbol ?? '$') ?>;

    function labelCierreYmd(ymd) {
        var p = (ymd || '').split('-');
        if (p.length !== 3) return ymd || '';
        var meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        var mi = parseInt(p[1], 10) - 1;
        return parseInt(p[2], 10) + ' ' + (meses[mi] != null ? meses[mi] : p[1]);
    }

    var ctxCierres = document.getElementById('chartCierresPagos');
    if (ctxCierres && typeof Chart !== 'undefined' && cierresPagosSeries.length) {
        new Chart(ctxCierres, {
            type: 'bar',
            data: {
                labels: cierresPagosSeries.map(function(r) { return labelCierreYmd(r.fecha); }),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Total cobrado (snapshot) ' + currencyLabel,
                        data: cierresPagosSeries.map(function(r) { return parseFloat(r.cobrado) || 0; }),
                        backgroundColor: 'rgba(59, 130, 246, 0.55)',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'Total facturado (snapshot) ' + currencyLabel,
                        data: cierresPagosSeries.map(function(r) { return parseFloat(r.facturado) || 0; }),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.15)',
                        fill: false,
                        tension: 0.25,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'Nº cierres',
                        data: cierresPagosSeries.map(function(r) { return parseInt(r.cierres, 10) || 0; }),
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.2)',
                        fill: false,
                        tension: 0.3,
                        yAxisID: 'y1',
                        borderDash: [4, 2]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            afterBody: function(items) {
                                if (!items.length) return [];
                                var i = items[0].dataIndex;
                                var r = cierresPagosSeries[i];
                                if (!r) return [];
                                return ['Fecha hasta (período): ' + r.fecha];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Monto (' + currencyLabel + ')' },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Cantidad de cierres' },
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    var ingresosPorMeses = <?= json_encode($ingresos_por_meses ?? []) ?>;
    var ctxBar = document.getElementById('chartBarras');
    if (ctxBar && typeof Chart !== 'undefined' && ingresosPorMeses.length) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ingresosPorMeses.map(function(m) { return m.label; }),
                datasets: [{
                    label: 'Ingresos cobrados',
                    data: ingresosPorMeses.map(function(m) { return m.cobrado; }),
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: '#10b981',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(v) { return v >= 1000 ? (v/1000) + 'k' : v; } }
                    }
                }
            }
        });
    }
})();
</script>
<?php $this->endSection(); ?>
