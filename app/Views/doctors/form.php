<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'doctors', 'extra_head_links' => $extra_head_links ?? []]) ?>
<script type="text/javascript">
$(document).ready(function() {
    $("#customer_form").validate({
        rules: {
            name: { required: true, minlength: 2 },
            phone_number: { required: true, maxlength: 50 },
            gender: { required: true },
            speciality: { required: true, maxlength: 255 },
            address: { required: true, maxlength: 255 }
        },
        messages: {
            name: { required: "Por favor ingrese nombre(s) y apellido(s)", minlength: "El nombre debe tener al menos 2 caracteres" },
            phone_number: { required: "El teléfono es obligatorio" },
            gender: { required: "Seleccione su género" },
            speciality: { required: "La especialidad es obligatoria" },
            address: { required: "La dirección es obligatoria" }
        },
        errorClass: "invalid-feedback",
        errorElement: "div",
        highlight: function(el) { $(el).addClass("is-invalid"); },
        unhighlight: function(el) { $(el).removeClass("is-invalid"); },
        errorPlacement: function(error, element) {
            error.addClass("invalid-feedback d-block");
            element.after(error);
        }
    });
});
</script>
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
<?php
$saveId = isset($doctor_info->doctor_id) && $doctor_info->doctor_id !== '' && $doctor_info->doctor_id !== null ? $doctor_info->doctor_id : -1;
echo form_open(site_url('doctors/save/' . $saveId), ['id' => 'customer_form', 'data-async' => '1', 'method' => 'post']);
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
<?php 
echo form_close();
?>
<?= view('partial/footer') ?>