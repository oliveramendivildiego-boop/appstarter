<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?php if (!empty($extra_head_links)): foreach ($extra_head_links as $link): ?><?= $link . "\n" ?><?php endforeach; endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => 'Equipos', 'url' => site_url('equipos')],
    ['label' => esc($equipo['nombre'] ?? ''), 'url' => null],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header"><strong><?= esc($equipo['nombre'] ?? '') ?></strong> - <?= esc($equipo['codigo'] ?? '') ?> / <?= esc($equipo['ubicacion'] ?? '-') ?></div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <strong>Historial de mantenimientos</strong>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('equipos/historialPrint/' . (int) ($equipo['equipo_id'] ?? 0)) ?>" class="btn btn-sm btn-primary" target="_blank" rel="noopener" title="Imprimir historial">
                <i class="fas fa-print me-1"></i> Imprimir
            </a>
            <a href="<?= site_url('equipos/historialPdf/' . (int) ($equipo['equipo_id'] ?? 0)) ?>" class="btn btn-sm btn-danger" title="Exportar historial a PDF">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Descripción</th></tr></thead>
            <tbody>
                <?php foreach ($mantenimientos ?? [] as $m): ?>
                <tr>
                    <td><?= esc($m['fecha'] ?? '') ?></td>
                    <td><?= (int)($m['tipo'] ?? 1) === 1 ? 'Preventivo' : 'Correctivo' ?></td>
                    <td><?= esc($m['descripcion'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <hr>
        <?= form_open('equipos/savemantenimiento') ?>
        <input type="hidden" name="equipo_id" value="<?= (int)($equipo['equipo_id'] ?? 0) ?>">
        <div class="row g-2 mb-2">
            <div class="col-md-2"><input type="text" id="fecha_mantenimiento" name="fecha" class="form-control form-control-sm flatpickr-input" value="<?= esc(lab_today_ymd()) ?>" required></div>
            <div class="col-md-2"><select name="tipo" class="form-select form-select-sm"><option value="1">Preventivo</option><option value="2">Correctivo</option></select></div>
            <div class="col-md-4"><input type="text" name="descripcion" class="form-control form-control-sm" placeholder="Descripción"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm">Registrar</button></div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<p class="mt-3"><a href="<?= site_url('equipos') ?>" class="btn btn-secondary">Volver</a></p>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#fecha_mantenimiento", {
        dateFormat: "Y-m-d",
        locale: "es",
        maxDate: "today",
        onOpen: function(s, d, i) { flatpickrPositionArrowTopLeft(i); }
    });
});
</script>
<?= $this->endSection() ?>
