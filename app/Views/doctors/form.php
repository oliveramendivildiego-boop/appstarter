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
    ? '<button type="button" id="doctor_delete_btn" class="btn btn-danger" title="' . esc(lang('Common.common_delete')) . '" data-doctor-id="' . esc((string) $doctor_info->doctor_id, 'attr') . '">' . esc(lang('Common.common_delete')) . '</button>'
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
    $('#doctor_delete_btn').on('click', function() {
        var doctorId = $(this).data('doctor-id');
        var confirmMsg = <?= json_encode(lang('Doctors.doctors_confirm_delete_one')) ?>;
        var deleteUrl = '<?= site_url(($controller_name ?? 'doctors') . '/delete') ?>';
        var listUrl = '<?= site_url($controller_name ?? 'doctors') ?>';
        function runDelete() {
            var payload = { 'ids[]': doctorId };
            if (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' && typeof window.CI_CSRF_TOKEN !== 'undefined') {
                payload[window.CI_CSRF_TOKEN_NAME] = window.CI_CSRF_TOKEN;
            }
            $.post(deleteUrl, payload, function(response) {
                if (response && response.success) {
                    if (typeof showToast === 'function') {
                        showToast(response.message || '', 'success');
                    }
                    window.location.href = listUrl;
                } else if (typeof showToast === 'function') {
                    showToast((response && response.message) ? response.message : 'Error', 'error');
                }
            }, 'json').fail(function() {
                if (typeof showToast === 'function') {
                    showToast('Error al eliminar', 'error');
                }
            });
        }
        if (typeof uiConfirm === 'function') {
            uiConfirm(confirmMsg, 'Confirmar').then(function(ok) { if (ok) runDelete(); });
        } else if (window.confirm(confirmMsg)) {
            runDelete();
        }
    });

    var isNewDoctor = <?= $saveId === -1 ? 'true' : 'false' ?>;
    if (isNewDoctor) {
        setTimeout(function() { $('#username, #password, #email').val(''); }, 100);
        setTimeout(function() { $('#username, #password, #email').val(''); }, 500);
    }

    $("#doctor_form").validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
        rules: {
            name: { required: true, minlength: 2 },
            phone_number: { maxlength: 50 },
            gender: { required: true },
            speciality: { maxlength: 255 },
            address: { maxlength: 255 },
            commission_percent: { number: true, min: 0, max: 100 }
        },
        messages: {
            name: { required: "Por favor ingrese nombre(s) y apellido(s)", minlength: "El nombre debe tener al menos 2 caracteres" },
            gender: { required: "Seleccione su género" },
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