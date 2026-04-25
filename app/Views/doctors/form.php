<?= $this->extend('layouts/main') ?>
<?= $this->section('head_extra') ?>
<?php if (!empty($extra_head_links) && is_array($extra_head_links)): foreach ($extra_head_links as $link): ?><?= $link ?><?php endforeach; endif; ?>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$dmItems = $doctor_info->doctor_id
    ? [['label' => lang('Module.module_doctors'), 'url' => site_url($controller_name ?? 'doctors')], ['label' => $doctor_info->name ?? '', 'url' => site_url(($controller_name ?? 'doctors') . '/view/' . $doctor_info->doctor_id)]]
    : [['label' => lang('Module.module_doctors'), 'url' => site_url($controller_name ?? 'doctors')], ['label' => lang('Doctors.doctors_new'), 'url' => null]];
$dmRight = ($doctor_info->doctor_id ?? 0)
    ? anchor(($controller_name ?? 'doctors') . '/delete/' . ($doctor_info->doctor_id ?? 0), lang('Common.common_delete'), ['class' => 'btn btn-danger', 'title' => lang('Common.common_delete')])
    : '';
?>
<?= view('partial/breadcrumb_nav', ['items' => $dmItems, 'right' => $dmRight]) ?>

<div id="title_bar">
    <div id="title" class="float-start"><?= lang('Doctors.doctors_basic_information') ?></div>
</div>
<div id="required_fields_message"><?= lang('Common.common_fields_required_message') ?></div>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?php $validationErrors = session()->getFlashdata('errors'); ?>
<?php
$saveId = isset($doctor_info->doctor_id) && $doctor_info->doctor_id !== '' && $doctor_info->doctor_id !== null ? $doctor_info->doctor_id : -1;
echo form_open(site_url('doctors/save/' . $saveId), ['id' => 'doctor_form', 'data-async' => '1', 'method' => 'post', 'autocomplete' => 'off']);
echo csrf_field();
?>
<fieldset id="customer_basic_info">

<?= view('doctors/form_basic_info') ?>

</fieldset>
<div class="submit_content">
<?php
echo form_submit(['name' => 'submit', 'id' => 'submit', 'value' => lang('Common.common_submit'), 'class' => 'btn btn-primary submit_button']);
?>
</div>
<?php echo form_close(); ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    var isNewDoctor = <?= $saveId === -1 ? 'true' : 'false' ?>;
    if (isNewDoctor) {
        setTimeout(function() { $('#username, #password, #email').val(''); }, 100);
        setTimeout(function() { $('#username, #password, #email').val(''); }, 500);
    }

    $("#doctor_form").validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
        rules: {
            name: { required: true, minlength: 2 },
            phone_number: { required: true, maxlength: 50 },
            gender: { required: true },
            speciality: { required: true, maxlength: 255 },
            address: { required: true, maxlength: 255 },
            commission_percent: { number: true, min: 0, max: 100 }
        },
        messages: {
            name: { required: "Por favor ingrese nombre(s) y apellido(s)", minlength: "El nombre debe tener al menos 2 caracteres" },
            phone_number: { required: "El teléfono es obligatorio" },
            gender: { required: "Seleccione su género" },
            speciality: { required: "La especialidad es obligatoria" },
            address: { required: "La dirección es obligatoria" },
            commission_percent: { number: "Ingrese un número válido", min: "La comisión no puede ser negativa", max: "La comisión no puede ser mayor a 100%" }
        }
    }));
    
    // Mostrar/ocultar campo de comisión según checkbox
    $('#has_commission').change(function() {
        if ($(this).is(':checked')) {
            $('#commission_field').show();
        } else {
            $('#commission_field').hide();
            $('#commission_percent').val('0.00');
            $('#hide_commission_details').prop('checked', false);
        }
    });
    
    <?php if (!empty($validationErrors) && is_array($validationErrors)): ?>
    window.CI_VALIDATION_ERRORS = <?= json_encode($validationErrors) ?>;
    if (typeof showServerValidationErrors === 'function') showServerValidationErrors('#doctor_form');
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>