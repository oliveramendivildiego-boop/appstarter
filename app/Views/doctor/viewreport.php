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
        helper(['layout', 'registro']);
        $layoutCfg = layout_config();
        $logoUrl = base_url($layoutCfg['logo'] ?? 'images/logo-john.png');
        ?>
        <img src="<?= esc($logoUrl) ?>" alt="Logo" class="report-logo-preview">
    </div>
    <div class="col-md-6 text-center">
        <?php
        $qrTarget = ! empty($public_resultados_token)
            ? site_url('resultados/' . $public_resultados_token)
            : current_url();
        ?>
        <img src="<?= site_url('qr/generate') ?>?data=<?= urlencode($qrTarget) ?>&size=120" alt="QR" /><br/>
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
        <?php if (!empty($doctor->report_sin_prefijo_medico ?? false)) : ?>
        <span class="fw-bold">Médico:</span> <?= esc($doctor->name ?? '') ?><br/>
        <?php else : ?>
        <?php $tituloMedico = ((int)($doctor->gender ?? 0) === 1) ? 'Dr.' : 'Dra.'; ?>
        <span class="fw-bold">Médico:</span> <?= $tituloMedico ?> <?= esc($doctor->name ?? '') ?><br/>
        <?php endif; ?>
        <span class="fw-bold">Fecha de recepción:</span> <?= esc($register_info->recepcion_fecha_hora ?? '') ?><br/>
        <span class="fw-bold">Fecha de reporte:</span> <?= esc($report_emitido_en ?? \App\Services\RegisterService::formatNowForReport()) ?><br/>
        <span class="fw-bold">No. Orden:</span> <?= esc(registro_orden_display($register_info)) ?>
    </div>
</div>

<?php if (!empty($clinical_summary)): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-stethoscope me-2"></i>Resumen médico automático</h5>
        <span class="badge bg-<?= esc($clinical_summary['status_class'] ?? 'secondary') ?>"><?= esc($clinical_summary['status'] ?? 'Normal') ?></span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="small text-muted">Valores alterados</div>
                <div class="h4 mb-0"><?= (int) ($clinical_summary['altered_count'] ?? 0) ?></div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Críticos</div>
                <div class="h4 mb-0 text-danger"><?= (int) ($clinical_summary['critical_count'] ?? 0) ?></div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">Interpretación global</div>
                <p class="mb-0"><?= esc($clinical_summary['global_interpretation'] ?? '') ?></p>
            </div>
        </div>
        <?php if (!empty($clinical_summary['top_alterations'])): ?>
        <div class="mt-3">
            <div class="fw-semibold mb-2">Top alteraciones</div>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($clinical_summary['top_alterations'] as $alt): ?>
                    <span class="badge bg-<?= !empty($alt['critico']) ? 'danger' : 'warning text-dark' ?>">
                        <?= esc($alt['nombre'] ?? '-') ?>: <?= esc($alt['valor'] ?? '-') ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($clinical_summary['recommendations'])): ?>
        <div class="mt-3">
            <div class="fw-semibold mb-2">Recomendaciones clínicas</div>
            <ul class="mb-0 small">
                <?php foreach ($clinical_summary['recommendations'] as $rec): ?>
                    <li><strong><?= esc($rec['priority'] ?? 'Sugerida') ?>:</strong> <?= esc($rec['text'] ?? '') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php if (!empty($clinical_summary['microbiology'])): ?>
        <div class="alert alert-info mt-3 mb-0">
            <div class="fw-semibold mb-1">Microbiología avanzada</div>
            <?php foreach ($clinical_summary['microbiology'] as $micro): ?>
                <div class="small">
                    <strong><?= esc($micro['nombre'] ?? 'Hallazgo') ?>:</strong>
                    <?= esc($micro['meaning'] ?? '') ?>
                    <?php if (!empty($micro['pattern'])): ?>
                        Patrón detectado: <?= esc($micro['pattern']) ?>.
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$grupos = $grupos ?? [];
if (empty($grupos)): ?>
<div class="alert alert-info mt-3">
    <i class="fa-solid fa-info-circle me-2"></i>No hay resultados cargados para esta orden.
</div>
<?php else:
$firmasPorPadre = [];
foreach ($report_lab_firmas ?? [] as $firmaRow) {
    if (! is_array($firmaRow)) {
        continue;
    }
    $areaNombre = trim((string) ($firmaRow['prueba_nombre'] ?? ''));
    if ($areaNombre !== '') {
        $firmasPorPadre[$areaNombre] = $firmaRow;
    }
}
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
    $padreKey = trim((string) $padre);
    if ($padreKey !== '' && isset($firmasPorPadre[$padreKey])) {
        echo view('registers/partials/report_lab_firma_grupo_inline', [
            'firma'            => $firmasPorPadre[$padreKey],
            'analisis_variant' => 'screen',
        ]);
    }
endforeach;
endif;
?>

<?= view('registers/partials/report_lab_firmas', ['report_lab_firmas' => $report_lab_firmas ?? []]) ?>

<div class="text-center mt-3">
    <a href="<?= site_url('doctor/home') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver
    </a>
    <?php $pubTok = trim((string) ($public_resultados_token ?? '')); ?>
    <?php if ($pubTok !== ''): ?>
    <a href="<?= site_url('resultados/' . $pubTok . '/pdf') ?>" class="btn btn-success" target="_blank" rel="noopener">
        <i class="fa-solid fa-file-pdf me-1"></i> Descargar PDF
    </a>
    <?php else: ?>
    <a href="<?= site_url('doctor/pdf/' . ($labotests_namecate ?? 0)) ?>" class="btn btn-success" target="_blank">
        <i class="fa-solid fa-file-pdf me-1"></i> Descargar PDF
    </a>
    <?php endif; ?>
</div>
</fieldset>
<?= $this->endSection() ?>
