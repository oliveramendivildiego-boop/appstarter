<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => 'Control de calidad', 'url' => site_url('controlcalidad')],
    ['label' => esc($control['nombre'] ?? 'Gráfica'), 'url' => null],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?= form_open('controlcalidad/grafica/' . (int)($control['control_id'] ?? 0), ['method' => 'get', 'class' => 'mb-3']) ?>
<div class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label mb-0">Desde</label><input type="text" id="fecha_ini" name="fecha_ini" class="form-control form-control-sm flatpickr-input" value="<?= esc($fecha_ini ?? '') ?>"></div>
    <div class="col-auto"><label class="form-label mb-0">Hasta</label><input type="text" id="fecha_fin" name="fecha_fin" class="form-control form-control-sm flatpickr-input" value="<?= esc($fecha_fin ?? '') ?>"></div>
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button></div>
</div>
<?= form_close() ?>

<div class="card mb-3">
    <div class="card-body">
        <canvas id="chartLevey" height="100"></canvas>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Registrar valor</strong></div>
    <div class="card-body">
        <?= form_open('controlcalidad/savevalor') ?>
        <input type="hidden" name="control_id" value="<?= (int)($control['control_id'] ?? 0) ?>">
        <div class="row g-2">
            <div class="col-md-2"><label class="form-label mb-0">Fecha</label><input type="text" id="fecha_control" name="fecha" class="form-control form-control-sm flatpickr-input" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-2"><label class="form-label mb-0">Valor</label><input type="number" step="any" name="valor" class="form-control form-control-sm" required></div>
            <div class="col-md-2"><label class="form-label mb-0">Esperado</label><input type="number" step="any" name="esperado" class="form-control form-control-sm" placeholder="Opcional"></div>
            <div class="col-md-3"><label class="form-label mb-0">Observaciones</label><input type="text" name="observaciones" class="form-control form-control-sm"></div>
            <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary btn-sm">Registrar</button></div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<p class="mt-3"><a href="<?= site_url('controlcalidad') ?>" class="btn btn-secondary">Volver</a></p>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#fecha_ini", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
    flatpickr("#fecha_fin", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
    flatpickr("#fecha_control", { dateFormat: "Y-m-d", locale: "es", maxDate: "today", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });

    var valores = <?= json_encode(array_map(fn($v) => ['fecha' => $v['fecha'], 'valor' => (float)($v['valor'] ?? 0), 'esperado' => isset($v['esperado']) ? (float)$v['esperado'] : null], $valores ?? [])) ?>;
    new Chart(document.getElementById('chartLevey'), {
        type: 'line',
        data: {
            labels: valores.map(function(v) { return v.fecha; }),
            datasets: [
                { label: 'Valor', data: valores.map(function(v) { return v.valor; }), borderColor: 'rgb(75, 192, 192)', tension: 0.1 },
                { label: 'Esperado', data: valores.map(function(v) { return v.esperado; }), borderColor: 'rgb(255, 99, 132)', borderDash: [5, 5], tension: 0.1 }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: false } } }
    });
});
</script>
<?= $this->endSection() ?>
