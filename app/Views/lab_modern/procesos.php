<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Procesos de laboratorio<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_registers'), 'url' => site_url('registers/lista')],
        ['label' => 'Procesos de laboratorio', 'url' => null],
    ],
    'right' => '<a href="' . site_url('lab-modern/trazabilidad') . '" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-diagram-project me-1"></i> Trazabilidad</a>',
]) ?>

<div class="alert alert-light border small mb-3">
    <i class="fa-solid fa-circle-info me-1"></i>
    Mapa de referencia del flujo estándar. Haga clic en un paso para ir al módulo correspondiente.
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="lab-modern-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="lab-proc-fit"><i class="fa-solid fa-compress me-1"></i> Ajustar vista</button>
        </div>
        <div id="lab-proc-cy" class="lab-cy-wrap"></div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/vendor/cytoscape.min.js') ?>"></script>
<script src="<?= base_url('js/vendor/dagre.min.js') ?>"></script>
<script src="<?= base_url('js/vendor/cytoscape-dagre.js') ?>"></script>
<script src="<?= base_url('js/modern/procesos-lab.js') ?>"></script>
<?= $this->endSection() ?>
