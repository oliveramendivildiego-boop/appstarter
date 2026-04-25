<?= $this->extend('layouts/main') ?>
<?= $this->section('head_extra') ?>
<?php if (!empty($extra_head_links) && is_array($extra_head_links)): foreach ($extra_head_links as $link): ?><?= $link ?><?php endforeach; endif; ?>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_customers'), 'url' => site_url('customers')],
    ['label' => (!empty($person_info->person_id) && (int)$person_info->person_id > 0) ? lang('Customers.customers_update') : lang('Customers.customers_new'), 'url' => null],
]]) ?>
<div id="title_bar">
    <div id="title" class="float-start"><?= lang('Customers.customers_basic_information') ?></div>
</div>
<div id="required_fields_message"><?= lang('Common.common_fields_required_message') ?></div>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?php $validationErrors = session()->getFlashdata('errors'); ?>
<?php $pid = $person_info->person_id ?? ''; $saveId = ($pid === '' || $pid === null) ? '-1' : (int) $pid; ?>
<?= form_open(site_url('customers/save/' . $saveId), ['id' => 'customer_form', 'data-async' => '1', 'autocomplete' => 'off']) ?>
<fieldset id="customer_basic_info">
    <?= view('people/form_basic_info', ['person_info' => $person_info]) ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <?= form_label('Seguro / aseguradora', 'seguro', ['class' => 'form-label']) ?>
            <?= form_input(['name' => 'seguro', 'id' => 'seguro', 'class' => 'form-control', 'value' => esc($person_info->seguro ?? '')]) ?>
        </div>
        <div class="col-md-6 mb-3">
            <?= form_label('Institución / procedencia', 'institucion', ['class' => 'form-label']) ?>
            <?= form_input([
                'name' => 'institucion',
                'id' => 'institucion',
                'class' => 'form-control',
                'value' => esc($person_info->institucion ?? ''),
                'list' => 'institucion_suggestions',
                'autocomplete' => 'off',
                'placeholder' => 'Ej: Hospital X, Caja Y, Empresa Z'
            ]) ?>
            <datalist id="institucion_suggestions">
                <?php foreach (($institucion_suggestions ?? []) as $inst): ?>
                    <option value="<?= esc($inst) ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <?= form_label(lang('Customers.customers_taxable') . ':', 'taxable', ['class' => 'form-label']) ?>
            <?= form_checkbox('taxable', '1', ($person_info->taxable ?? '') == '' ? true : (bool) $person_info->taxable, 'id="taxable" class="form-check-input"') ?>
        </div>
        <?php if (!empty($person_info->person_id) && (int)$person_info->person_id > 0): ?>
        <div class="col-md-6">
            <label class="form-label">Código QR paciente</label>
            <p class="mb-0">
                <a href="<?= base_url('qr/generate?data=' . urlencode('P' . (int)$person_info->person_id) . '&size=120') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-qrcode me-1"></i> Ver QR
                </a>
                <span class="text-muted small ms-2">ID: <?= (int)$person_info->person_id ?></span>
            </p>
        </div>
        <?php endif; ?>
    </div>
</fieldset>
<div class="submit_content">
    <?= form_submit(['name' => 'submit', 'id' => 'submit', 'value' => lang('Common.common_submit'), 'class' => 'btn btn-primary submit_button']) ?>
    <?php if (!empty($person_info->person_id) && (int) $person_info->person_id > 0): ?>
    <a href="<?= site_url('expediente/view/' . (int) $person_info->person_id) ?>" class="btn btn-info ms-2">
        <i class="fa-solid fa-folder-open me-1"></i> Ver historial de estudios
    </a>
    <?php endif; ?>
</div>
<?= form_close() ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    flatpickr("#birthday", { dateFormat: "Y-m-d", maxDate: "today", locale: "es", onOpen: function(s,d,i){ if (typeof flatpickrPositionArrowTopLeft === 'function') flatpickrPositionArrowTopLeft(i); } });
    $("#customer_form").validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
        rules: {
            first_name: { required: true, minlength: 2 },
            last_name_fa: { required: true, minlength: 2 },
            email: { email: true },
            birthday: { required: true, date: true },
            gender: { required: true }
        },
        messages: {
            first_name: { required: "Por favor ingrese su nombre(s)", minlength: "El nombre debe tener al menos 2 caracteres" },
            last_name_fa: { required: "Por favor ingrese su apellido(s)", minlength: "Debe tener al menos 2 caracteres" },
            email: { email: "Ingrese un correo válido" },
            birthday: { required: "Seleccione su fecha de nacimiento", date: "Ingrese una fecha válida" },
            gender: { required: "Seleccione su género" }
        }
    }));
    <?php if (!empty($validationErrors) && is_array($validationErrors)): ?>
    window.CI_VALIDATION_ERRORS = <?= json_encode($validationErrors) ?>;
    if (typeof showServerValidationErrors === 'function') showServerValidationErrors('#customer_form');
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>
