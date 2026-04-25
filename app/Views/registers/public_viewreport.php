<?= $this->extend('layouts/public_resultados') ?>
<?= $this->section('title') ?>Resultados<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$token = trim((string) ($public_resultados_token ?? ''));
$publicBase = $token !== '' ? site_url('resultados/' . $token) : site_url();
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active">Resultados de laboratorio</li>
    </ol>
</nav>

<fieldset id="customer_basic_info">
<div class="row mb-3">
    <div class="col-md-6">
        <?php
        helper(['layout', 'registro']);
        $layoutCfg = layout_config();
        $logoUrl = base_url($layoutCfg['logo'] ?? 'images/logo-john.png');
        ?>
        <img src="<?= esc($logoUrl) ?>" alt="Logo" class="report-logo-preview">
    </div>
    <div class="col-md-6 text-center">
        <?php if ($token !== ''): ?>
        <img src="<?= site_url('qr/generate') ?>?data=<?= urlencode($publicBase) ?>&size=120" alt="QR" /><br/>
        <?php endif; ?>
        <?= esc($layoutCfg['website'] ?? '') ?>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-6">
        <span class="fw-bold">Paciente:</span> <?= esc(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? '')) ?><br/>
        <span class="fw-bold">Género:</span> <?= esc(paciente_genero_texto($paciente)) ?><br/>
        <span class="fw-bold">Edad:</span> <?= esc($paciente->edad ?? '-') ?><br/>
        <span class="fw-bold">Teléfono:</span> <?= esc($paciente->phone_number ?? '') ?>
    </div>
    <div class="col-md-6">
        <?php $tituloMedico = ((int)($doctor->gender ?? 0) === 1) ? 'Dr.' : 'Dra.'; ?>
        <span class="fw-bold">Médico:</span> <?= $tituloMedico ?> <?= esc($doctor->name ?? '') ?><br/>
        <span class="fw-bold">Fecha de recepción:</span> <?= esc($register_info->recepcion_fecha_hora ?? '') ?><br/>
        <span class="fw-bold">Fecha de reporte:</span> <?= esc($report_emitido_en ?? \App\Services\RegisterService::formatNowForReport()) ?><br/>
        <span class="fw-bold">No. Orden:</span> <?= esc(registro_orden_display($register_info)) ?>
    </div>
</div>

<?php
$grupos = $grupos ?? [];
if (empty($grupos)): ?>
<div class="alert alert-info mt-3">
    <i class="fa-solid fa-info-circle me-2"></i>No hay resultados cargados para esta orden.
</div>
<?php else:
foreach ($grupos as $padre => $items):
    $nombreVista = strtolower($padre);
    $viewName = 'registers/analisis/default';
    if ($nombreVista === 'hematologia') {
        $viewName = 'registers/analisis/hemograma';
    } elseif ($nombreVista === 'orina') {
        $viewName = 'registers/analisis/orina';
    } elseif ($nombreVista === 'heces') {
        $viewName = 'registers/analisis/heces';
    }
    echo view($viewName, [
        'grupos' => [$padre => $items],
        'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
        'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
        'report_pria_refs_consolidada'    => $report_pria_refs_consolidada ?? [],
    ]);
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

<?= view('registers/partials/report_lab_firmas', ['report_lab_firmas' => $report_lab_firmas ?? []]) ?>

<div class="text-center mt-3">
    <?php if ($token !== '' && !empty($doctor_assigned ?? false)): ?>
    <a href="<?= site_url('resultados/' . $token . '/pdf') ?>" class="btn btn-success" target="_blank" rel="noopener">
        <i class="fa-solid fa-file-pdf me-1"></i> Descargar PDF
    </a>
    <?php elseif ($token !== ''): ?>
    <div class="small text-muted">
        El PDF estará disponible cuando la orden tenga un doctor asignado.
    </div>
    <?php endif; ?>
</div>
</fieldset>
<?= $this->endSection() ?>
