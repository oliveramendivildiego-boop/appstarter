<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Cambiar contraseña<?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<script src="<?= base_url('js/vendor/jquery.validate.min.js') ?>"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $validationErrors = session()->getFlashdata('errors'); ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => 'Mi cuenta', 'url' => null],
    ['label' => 'Cambiar contraseña', 'url' => null],
]]) ?>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<?php if (!empty($validationErrors) && is_array($validationErrors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">
        <?php foreach ($validationErrors as $error): ?>
        <li><?= esc($error) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-6 col-xl-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fa-solid fa-key me-2"></i>Cambiar contraseña</h5>
            </div>
            <div class="card-body">
                <?= form_open(site_url('account/password'), ['id' => 'password_form', 'autocomplete' => 'off']) ?>
                <div class="mb-3">
                    <?= form_label('Contraseña actual <span class="text-danger">*</span>', 'current_password', ['class' => 'form-label']) ?>
                    <?= form_password(['name' => 'current_password', 'id' => 'current_password', 'class' => 'form-control', 'autocomplete' => 'current-password']) ?>
                </div>
                <div class="mb-3">
                    <?= form_label('Nueva contraseña <span class="text-danger">*</span>', 'new_password', ['class' => 'form-label']) ?>
                    <?= form_password(['name' => 'new_password', 'id' => 'new_password', 'class' => 'form-control', 'autocomplete' => 'new-password']) ?>
                    <small class="text-muted">Mínimo 4 caracteres.</small>
                </div>
                <div class="mb-4">
                    <?= form_label('Confirmar nueva contraseña <span class="text-danger">*</span>', 'confirm_password', ['class' => 'form-label']) ?>
                    <?= form_password(['name' => 'confirm_password', 'id' => 'confirm_password', 'class' => 'form-control', 'autocomplete' => 'new-password']) ?>
                </div>
                <div class="d-flex gap-2">
                    <?= form_submit(['name' => 'submit', 'value' => 'Actualizar contraseña', 'class' => 'btn btn-primary']) ?>
                    <a href="<?= site_url('home') ?>" class="btn btn-secondary">Cancelar</a>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $("#password_form").validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
        rules: {
            current_password: { required: true },
            new_password: { required: true, minlength: 4 },
            confirm_password: { required: true, equalTo: "#new_password" }
        },
        messages: {
            current_password: { required: "Ingrese su contraseña actual" },
            new_password: { required: "Ingrese la nueva contraseña", minlength: "Mínimo 4 caracteres" },
            confirm_password: { required: "Confirme la nueva contraseña", equalTo: "Las contraseñas no coinciden" }
        }
    }));
});
</script>
<?= $this->endSection() ?>
