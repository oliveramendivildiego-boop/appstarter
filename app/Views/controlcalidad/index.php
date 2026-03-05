<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<script src="<?= base_url('js/vendor/jquery.validate.min.js') ?>"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => 'Control de calidad', 'url' => site_url('controlcalidad')],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Controles de calidad</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Nombre</th><th>Tipo</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($controles ?? [] as $c): ?>
                        <tr>
                            <td><?= esc($c['nombre'] ?? '') ?></td>
                            <td><?= (int)($c['tipo'] ?? 1) === 1 ? 'Interno' : 'Externo' ?></td>
                            <td><a href="<?= site_url("controlcalidad/grafica/" . (int)($c['control_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-chart-line"></i> Gráfica</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <hr>
                <?= form_open('controlcalidad/savecontrol', ['id' => 'form_control']) ?>
                <input type="hidden" name="control_id" value="0">
                <div class="mb-2">
                    <input type="text" name="nombre" id="control_nombre" class="form-control form-control-sm" placeholder="Nombre del control">
                </div>
                <div class="mb-2">
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="1">Interno</option>
                        <option value="2">Externo</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Agregar control</button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $("#form_control").validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
        rules: { nombre: { required: true, minlength: 2 } },
        messages: { nombre: { required: "El nombre es obligatorio", minlength: "El nombre debe tener al menos 2 caracteres" } }
    }));
});
</script>
<?= $this->endSection() ?>
