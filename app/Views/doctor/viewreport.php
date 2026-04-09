<?= $this->extend('layouts/doctor') ?>
<?= $this->section('title') ?>Reporte<?= $this->endSection() ?>
<?= $this->section('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= site_url('doctor/home') ?>">Mi panel</a></li>
        <li class="breadcrumb-item active">Reporte #<?= esc((string) ($labotests_namecate ?? '')) ?></li>
    </ol>
</nav>

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
    if ($nombreVista === 'hematologia') $viewName = 'registers/analisis/hemograma';
    elseif ($nombreVista === 'orina') $viewName = 'registers/analisis/orina';
    elseif ($nombreVista === 'heces') $viewName = 'registers/analisis/heces';
    echo view($viewName, [
        'grupos' => [$padre => $items],
        'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
        'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
    ]);
endforeach;
endif;
?>

<div class="text-center mt-3">
    <a href="<?= site_url('doctor/home') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver
    </a>
    <a href="<?= site_url('doctor/pdf/' . ($labotests_namecate ?? 0)) ?>" class="btn btn-success" target="_blank">
        <i class="fa-solid fa-file-pdf me-1"></i> Descargar PDF
    </a>
</div>
</fieldset>
<?= $this->endSection() ?>
