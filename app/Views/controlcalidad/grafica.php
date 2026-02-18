<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'controlcalidad']) ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => 'Control de calidad', 'url' => site_url('controlcalidad')],
    ['label' => esc($control['nombre'] ?? 'Gráfica'), 'url' => null],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?= form_open('controlcalidad/grafica/' . (int)($control['control_id'] ?? 0), ['method' => 'get', 'class' => 'mb-3']) ?>
<div class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label mb-0">Desde</label><input type="date" name="fecha_ini" class="form-control form-control-sm" value="<?= esc($fecha_ini ?? '') ?>"></div>
    <div class="col-auto"><label class="form-label mb-0">Hasta</label><input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= esc($fecha_fin ?? '') ?>"></div>
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
            <div class="col-md-2"><label class="form-label mb-0">Fecha</label><input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-2"><label class="form-label mb-0">Valor</label><input type="number" step="any" name="valor" class="form-control form-control-sm" required></div>
            <div class="col-md-2"><label class="form-label mb-0">Esperado</label><input type="number" step="any" name="esperado" class="form-control form-control-sm" placeholder="Opcional"></div>
            <div class="col-md-3"><label class="form-label mb-0">Observaciones</label><input type="text" name="observaciones" class="form-control form-control-sm"></div>
            <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary btn-sm">Registrar</button></div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

<p class="mt-3"><a href="<?= site_url('controlcalidad') ?>" class="btn btn-secondary">Volver</a></p>

<?= view('partial/footer') ?>
