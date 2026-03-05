<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<script src="<?= base_url('js/vendor/jquery.validate.min.js') ?>"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [['label' => 'Equipos', 'url' => site_url('equipos')]]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><strong>Equipos</strong></div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Nombre</th><th>Código</th><th>Ubicación</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($equipos ?? [] as $e): ?>
                <tr>
                    <td><?= esc($e['nombre'] ?? '') ?></td>
                    <td><?= esc($e['codigo'] ?? '-') ?></td>
                    <td><?= esc($e['ubicacion'] ?? '-') ?></td>
                    <td><a href="<?= site_url("equipos/detalle/" . (int)$e['equipo_id']) ?>" class="btn btn-sm btn-outline-primary">Ver / Mantenimientos</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <hr>
        <?= form_open('equipos/saveequipo', ['id' => 'form_equipo']) ?>
        <input type="hidden" name="equipo_id" value="0">
        <div class="row g-2 mb-2">
            <div class="col-md-3"><input type="text" name="nombre" id="equipo_nombre" class="form-control form-control-sm" placeholder="Nombre"></div>
            <div class="col-md-2"><input type="text" name="codigo" class="form-control form-control-sm" placeholder="Código"></div>
            <div class="col-md-3"><input type="text" name="ubicacion" class="form-control form-control-sm" placeholder="Ubicación"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm">Agregar equipo</button></div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $("#form_equipo").validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
        rules: { nombre: { required: true, minlength: 2 } },
        messages: { nombre: { required: "El nombre es obligatorio", minlength: "El nombre debe tener al menos 2 caracteres" } }
    }));
});
</script>
<?= $this->endSection() ?>
