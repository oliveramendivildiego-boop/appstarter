<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<script src="<?= base_url('js/vendor/jquery.validate.min.js') ?>"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_labotests'), 'url' => site_url('labotests')],
    ['label' => 'Perfiles de exámenes', 'url' => null],
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
            <div class="card-header"><strong>Agregar perfil</strong></div>
            <div class="card-body">
                <?= form_open('labotests/saveperfil', ['id' => 'form_perfil']) ?>
                <input type="hidden" name="perfil_id" value="0">
                <div class="mb-3">
                    <label class="form-label">Nombre del perfil <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="nombre_perfil" class="form-control" placeholder="ej. Hemograma básico">
                </div>
                <div class="mb-3">
                    <label class="form-label">Pruebas incluidas</label>
                    <div class="border rounded p-2 perfiles-pruebas-list">
                        <?php foreach ($categories ?? [] as $cat): ?>
                            <?php foreach ($cat['items'] ?? [] as $item): ?>
                            <div class="form-check">
                                <input type="checkbox" name="pruebas[]" value="<?= (int)($item['id'] ?? 0) ?>" id="p_<?= (int)($item['id'] ?? 0) ?>" class="form-check-input">
                                <label class="form-check-label" for="p_<?= (int)($item['id'] ?? 0) ?>"><?= esc($item['name'] ?? '') ?></label>
                            </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Guardar perfil</button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Perfiles existentes</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Nombre</th><th>Pruebas</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($perfiles ?? [] as $p): ?>
                        <tr>
                            <td><?= esc($p['nombre'] ?? '') ?></td>
                            <td><small><?= esc($p['pruebas'] ?? '') ?></small></td>
                            <td>
                                <a href="<?= site_url('labotests/deleteperfil/' . (int)($p['perfil_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" onclick="return uiConfirmLink(this, '¿Eliminar este perfil?');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php if (empty($perfiles)): ?>
                <p class="text-muted mb-0">No hay perfiles. Cree uno a la izquierda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<p class="mt-3">
    <a href="<?= site_url('labotests') ?>" class="btn btn-secondary">Volver a Pruebas</a>
    <a href="<?= site_url('registers') ?>" class="btn btn-outline-primary">Nueva orden</a>
</p>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $("#form_perfil").validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
        rules: { nombre: { required: true, minlength: 2 } },
        messages: { nombre: { required: "El nombre es obligatorio", minlength: "El nombre debe tener al menos 2 caracteres" } }
    }));
});
</script>
<?= $this->endSection() ?>
