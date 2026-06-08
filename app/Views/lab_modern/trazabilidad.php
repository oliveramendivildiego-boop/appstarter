<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Mapa de trazabilidad<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_auditoria'), 'url' => site_url('auditoria')],
        ['label' => 'Mapa de trazabilidad', 'url' => null],
    ],
    'right' => '<a href="' . site_url('lab-modern/procesos') . '" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-sitemap me-1"></i> Procesos</a>',
]) ?>

<div class="alert alert-light border small mb-3">
    <i class="fa-solid fa-circle-info me-1"></i>
    Visualización de solo lectura sobre datos de auditoría existentes. No modifica registros ni reemplaza el listado de auditoría.
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="lab-modern-toolbar">
            <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text">Orden / ID</span>
                <input type="text" class="form-control" id="lab-traz-registro" placeholder="Opcional">
                <button type="button" class="btn btn-primary" id="lab-traz-load">Cargar</button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="lab-traz-fit"><i class="fa-solid fa-compress me-1"></i> Ajustar vista</button>
            <a href="<?= site_url('auditoria') ?>" class="btn btn-sm btn-outline-secondary ms-auto">Volver a auditoría</a>
        </div>
        <div id="lab-traz-cy" class="lab-cy-wrap"></div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php
$bootJson = json_encode([
    'dataUrl'         => site_url('lab-modern/trazabilidad-data'),
    'initialFilters'  => [],
], JSON_UNESCAPED_UNICODE);
?>
<script type="application/json" id="lab-traz-boot-json"><?= $bootJson ?></script>
<script>
(function () {
    var el = document.getElementById('lab-traz-boot-json');
    try { window.LAB_TRAZABILIDAD_BOOT = el ? JSON.parse(el.textContent || '{}') : {}; }
    catch (e) { window.LAB_TRAZABILIDAD_BOOT = {}; }
})();
</script>
<script src="<?= base_url('js/vendor/cytoscape.min.js') ?>"></script>
<script src="<?= base_url('js/modern/trazabilidad-canvas.js') ?>"></script>
<?= $this->endSection() ?>
