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

<?php
$qc = $qc_stats ?? null;
$n = $qc['n'] ?? 0;
$fmt = static function ($x, int $dec = 4) {
    if ($x === null) {
        return '—';
    }
    return number_format((float) $x, $dec, ',', '');
};
?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<div class="alert alert-info mb-3">
    <strong>Recomendación sobre el tamaño muestral:</strong>
    con un mínimo de 20 datos se establecen los límites iniciales de control, pero idealmente se usan más (30–40) para mayor confiabilidad.
</div>

<?= form_open('controlcalidad/grafica/' . (int)($control['control_id'] ?? 0), ['method' => 'get', 'class' => 'mb-3']) ?>
<div class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label mb-0">Desde</label><input type="text" id="fecha_ini" name="fecha_ini" class="form-control form-control-sm flatpickr-input" value="<?= esc($fecha_ini ?? '') ?>"></div>
    <div class="col-auto"><label class="form-label mb-0">Hasta</label><input type="text" id="fecha_fin" name="fecha_fin" class="form-control form-control-sm flatpickr-input" value="<?= esc($fecha_fin ?? '') ?>"></div>
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button></div>
</div>
<?= form_close() ?>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Estadísticos del periodo filtrado</strong> <span class="text-muted small">(n = <?= (int) $n ?>)</span></div>
            <div class="card-body">
                <?php if ($qc === null): ?>
                <p class="text-muted mb-0">No hay valores en el rango de fechas seleccionado.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-1"><span class="text-success">✔</span> <strong>Media (x̄):</strong> <?= $fmt($qc['mean']) ?></li>
                    <li class="mb-1"><span class="text-success">✔</span> <strong>Desviación estándar (DE):</strong> <?= $fmt($qc['sd']) ?></li>
                    <li class="mb-1"><span class="text-success">✔</span> <strong>Coeficiente de variación (CV%):</strong> <?= $qc['cv_percent'] !== null ? $fmt($qc['cv_percent'], 2) . ' %' : '— <span class="text-muted">(media = 0)</span>' ?></li>
                    <li class="mb-1"><span class="text-success">✔</span> <strong>Límites de control (±1, ±2, ±3 DE):</strong> visibles en la gráfica de Levey-Jennings</li>
                    <?php if (isset($control['sesgo']) && $control['sesgo'] !== null && $control['sesgo'] !== ''): ?>
                    <li class="mb-1"><span class="text-success">✔</span> <strong>Sesgo (asignado):</strong> <?= $fmt((float) $control['sesgo']) ?></li>
                    <?php else: ?>
                    <li class="mb-1 text-muted"><span class="text-secondary">○</span> <strong>Sesgo (asignado):</strong> sin valor — puede indicarlo abajo si aplica.</li>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Evaluación con reglas de Westgard</strong></div>
            <div class="card-body">
                <?php if ($qc === null || ($qc['sd'] ?? 0) <= 0): ?>
                <p class="text-muted mb-0 small">Se requiere al menos 2 mediciones con variación para aplicar las reglas (límites basados en la DE muestral del periodo).</p>
                <?php elseif (empty($westgard ?? [])): ?>
                <p class="text-success mb-0 small">En el periodo analizado no se detectaron violaciones de las reglas 1-3s, 2-2s, R-4s, 4-1s ni 10x respecto a la media y DE calculadas en este rango.</p>
                <?php else: ?>
                <ul class="small mb-0 ps-3">
                    <?php foreach ($westgard as $msg): ?>
                    <li class="text-danger mb-1"><?= $msg ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Gráfica de Levey-Jennings</strong> <span class="text-muted small">(líneas: media y ±1, ±2, ±3 DE del periodo)</span></div>
    <div class="card-body">
        <?php if ($qc === null): ?>
        <p class="text-muted mb-0">Agregue valores en el rango o amplíe las fechas para ver la gráfica.</p>
        <?php else: ?>
        <canvas id="chartLevey" height="110"></canvas>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Sesgo asignado</strong> <span class="text-muted small">(opcional, p. ej. referencia del fabricante)</span></div>
    <div class="card-body">
        <?= form_open('controlcalidad/savecontrol') ?>
        <input type="hidden" name="control_id" value="<?= (int)($control['control_id'] ?? 0) ?>">
        <input type="hidden" name="nombre" value="<?= esc($control['nombre'] ?? '') ?>">
        <input type="hidden" name="tipo" value="<?= (int)($control['tipo'] ?? 1) ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-0">Valor de sesgo</label>
                <input type="number" step="any" name="sesgo" class="form-control form-control-sm" value="<?= isset($control['sesgo']) && $control['sesgo'] !== null && $control['sesgo'] !== '' ? esc((string) $control['sesgo']) : '' ?>" placeholder="Vacío = sin asignar">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Guardar sesgo</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Registrar valor</strong></div>
    <div class="card-body">
        <?= form_open('controlcalidad/savevalor') ?>
        <input type="hidden" name="control_id" value="<?= (int)($control['control_id'] ?? 0) ?>">
        <div class="row g-2">
            <div class="col-md-2"><label class="form-label mb-0">Fecha</label><input type="text" id="fecha_control" name="fecha" class="form-control form-control-sm flatpickr-input" value="<?= esc(lab_today_ymd()) ?>" required></div>
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

    var qc = <?= json_encode($qc_stats, JSON_THROW_ON_ERROR) ?>;
    if (!qc || typeof qc.mean !== 'number') return;

    var valores = <?= json_encode(array_map(fn($v) => ['fecha' => $v['fecha'], 'valor' => (float)($v['valor'] ?? 0), 'esperado' => isset($v['esperado']) && $v['esperado'] !== null && $v['esperado'] !== '' ? (float)$v['esperado'] : null], $valores ?? []), JSON_THROW_ON_ERROR) ?>;
    var labels = valores.map(function(v) { return v.fecha; });
    var mean = qc.mean;
    var sd = qc.sd;
    var len = labels.length;

    function line(val) {
        var a = [];
        for (var i = 0; i < len; i++) a.push(val);
        return a;
    }

    var datasets = [
        {
            label: 'Valor medido',
            data: valores.map(function(v) { return v.valor; }),
            borderColor: 'rgb(25, 135, 84)',
            backgroundColor: 'rgba(25, 135, 84, 0.15)',
            tension: 0.15,
            borderWidth: 2,
            order: 0
        },
        {
            label: 'Media (x̄)',
            data: line(mean),
            borderColor: 'rgb(13, 110, 253)',
            borderWidth: 2,
            pointRadius: 0,
            tension: 0,
            order: 10
        }
    ];

    if (sd > 0) {
        datasets.push(
            { label: '+1 DE', data: line(mean + sd), borderColor: 'rgba(108, 117, 125, 0.9)', borderDash: [4, 4], borderWidth: 1, pointRadius: 0, tension: 0, order: 20 },
            { label: '-1 DE', data: line(mean - sd), borderColor: 'rgba(108, 117, 125, 0.9)', borderDash: [4, 4], borderWidth: 1, pointRadius: 0, tension: 0, order: 20 },
            { label: '+2 DE', data: line(mean + 2*sd), borderColor: 'rgba(255, 193, 7, 0.95)', borderDash: [6, 3], borderWidth: 1, pointRadius: 0, tension: 0, order: 21 },
            { label: '-2 DE', data: line(mean - 2*sd), borderColor: 'rgba(255, 193, 7, 0.95)', borderDash: [6, 3], borderWidth: 1, pointRadius: 0, tension: 0, order: 21 },
            { label: '+3 DE', data: line(mean + 3*sd), borderColor: 'rgba(220, 53, 69, 0.85)', borderDash: [2, 3], borderWidth: 1, pointRadius: 0, tension: 0, order: 22 },
            { label: '-3 DE', data: line(mean - 3*sd), borderColor: 'rgba(220, 53, 69, 0.85)', borderDash: [2, 3], borderWidth: 1, pointRadius: 0, tension: 0, order: 22 }
        );
    }

    var hasEsperado = valores.some(function(v) { return v.esperado !== null && !isNaN(v.esperado); });
    if (hasEsperado) {
        datasets.push({
            label: 'Esperado (registro)',
            data: valores.map(function(v) { return v.esperado; }),
            borderColor: 'rgb(214, 51, 132)',
            borderDash: [5, 5],
            spanGaps: true,
            tension: 0.1,
            pointRadius: 0,
            order: 5
        });
    }

    new Chart(document.getElementById('chartLevey'), {
        type: 'line',
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                title: { display: true, text: 'Levey-Jennings — control interno/externo' }
            },
            scales: {
                y: { beginAtZero: false },
                x: { ticks: { maxRotation: 45, minRotation: 0 } }
            }
        }
    });
});
</script>
<?= $this->endSection() ?>
