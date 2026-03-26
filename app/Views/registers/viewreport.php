<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Reporte<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_registers'), 'url' => site_url('registers')],
    ['label' => ($register_info->first_name ?? '') . ' ' . ($register_info->last_name_fa ?? ''), 'url' => site_url('registers/view/' . ($register_info->registro_id ?? ''))],
]]) ?>

<fieldset id="customer_basic_info">
<div class="row mb-3">
    <div class="col-md-6">
        <?php
        helper('layout');
        $layoutCfg = layout_config();
        $logoUrl = base_url($layoutCfg['logo'] ?? 'images/logo-john.png');
        ?>
        <img src="<?= esc($logoUrl) ?>" alt="Logo" class="report-logo-preview">
    </div>
    <div class="col-md-6 text-center">
        <img src="<?= site_url('qr/generate') ?>?data=<?= urlencode(current_url()) ?>&size=120" alt="QR" /><br/>
        <?= esc($layoutCfg['website'] ?? '') ?>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-6">
        <span class="fw-bold">Paciente:</span> <?= esc(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? '')) ?><br/>
        <span class="fw-bold">Edad:</span> <?= esc($paciente->edad ?? '-') ?><br/>
        <span class="fw-bold">Teléfono:</span> <?= esc($paciente->phone_number ?? '') ?>
    </div>
    <div class="col-md-6">
        <?php $tituloMedico = ((int)($doctor->gender ?? 0) === 1) ? 'Dr.' : 'Dra.'; ?>
        <span class="fw-bold">Médico:</span> <?= $tituloMedico ?> <?= esc($doctor->name ?? '') ?><br/>
        <span class="fw-bold">Fecha:</span> <?= esc($register_info->ingreso ?? '') ?><br/>
        <span class="fw-bold">No. Orden:</span> <?= esc(registro_orden_display($register_info)) ?>
    </div>
</div>
<input type="hidden" name="registro_id" id="registro_id" value="<?= (int)($labotests_namecate ?? 0) ?>">

<?php
$grupos = $grupos ?? [];
if (empty($grupos)): ?>
<div class="alert alert-info mt-3">
    <i class="fa-solid fa-info-circle me-2"></i>No hay resultados cargados para esta orden. Complete los resultados en <a href="<?= site_url('registers/view/' . (int)($labotests_namecate ?? 0)) ?>">Editar registro</a>.
</div>
<?php else:
foreach ($grupos as $padre => $items):
    $nombreVista = strtolower($padre);
    $viewName = 'registers/analisis/default';
    if ($nombreVista === 'hematologia') $viewName = 'registers/analisis/hemograma';
    elseif ($nombreVista === 'orina') $viewName = 'registers/analisis/orina';
    elseif ($nombreVista === 'heces') $viewName = 'registers/analisis/heces';
    echo view($viewName, ['grupos' => [$padre => $items]]);
endforeach;
endif;
?>

<?php $notaResultado = trim((string)($register_info->comentario_resultado ?? '')); ?>
<?php if ($notaResultado !== ''): ?>
<div class="card mt-3">
    <div class="card-header"><strong>NOTAS</strong></div>
    <div class="card-body">
        <div style="white-space: pre-wrap;"><?= esc($notaResultado) ?></div>
    </div>
</div>
<?php endif; ?>

<div class="text-center mt-3">
    <button id="guardaranalisis" name="guardaranalisis" class="btn btn-primary">Guardar</button>
    <a href="<?= site_url('registers/pdf/' . ($labotests_namecate ?? 0)) ?>" class="btn btn-success" target="_blank">
        <i class="fa-solid fa-file-pdf me-1"></i> Descargar PDF
    </a>
</div>
</fieldset>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('guardaranalisis');
    if (btn) {
        btn.addEventListener('click', function() {
            var datos = [];
            document.querySelectorAll('.analisis').forEach(function(el) {
                datos.push({
                    padre: el.getAttribute('padre'),
                    hijo: el.getAttribute('hijo'),
                    analisis: el.getAttribute('analisis'),
                    valor: el.getAttribute('value') || el.value,
                    unidad: el.getAttribute('unidad'),
                    minimo: el.getAttribute('min'),
                    maximo: el.getAttribute('max'),
                    registro_id: document.getElementById('registro_id').value
                });
            });
            fetch('<?= site_url('registers/saveanalisiss') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'data=' + encodeURIComponent(JSON.stringify(datos))
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                window.location.href = '<?= site_url('registers') ?>';
            })
            .catch(function() { uiAlert('Error al guardar', 'Error'); });
        });
    }
});
</script>
<?= $this->endSection() ?>
