<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'customers']) ?>
<script type="text/javascript">
$(document).ready(function() {
    flatpickr("#birthday", { dateFormat: "Y-m-d", maxDate: "today", locale: "es" });
    $("#customer_form").validate({
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
        },
        errorClass: "is-invalid text-danger small",
        validClass: "is-valid",
        errorElement: "div",
        highlight: function(el) { $(el).addClass("is-invalid").removeClass("is-valid"); },
        unhighlight: function(el) { $(el).removeClass("is-invalid").addClass("is-valid"); }
    });
});
</script>
<div id="title_bar">
    <div id="title" class="float-start"><?= lang('Customers.customers_basic_information') ?></div>
</div>
<div id="required_fields_message"><?= lang('Common.common_fields_required_message') ?></div>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?php $pid = $person_info->person_id ?? ''; $saveId = ($pid === '' || $pid === null) ? '-1' : (int) $pid; ?>
<?= form_open(site_url('customers/save/' . $saveId), ['id' => 'customer_form', 'data-async' => '1']) ?>
<ul id="error_message_box"></ul>
<fieldset id="customer_basic_info">
    <?= view('people/form_basic_info', ['person_info' => $person_info]) ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <?= form_label('Seguro / aseguradora', 'seguro', ['class' => 'form-label']) ?>
            <?= form_input(['name' => 'seguro', 'id' => 'seguro', 'class' => 'form-control', 'value' => $person_info->seguro ?? '']) ?>
        </div>
        <div class="col-md-6 mb-3">
            <?= form_label('Institución / procedencia', 'institucion', ['class' => 'form-label']) ?>
            <?= form_input(['name' => 'institucion', 'id' => 'institucion', 'class' => 'form-control', 'value' => $person_info->institucion ?? '']) ?>
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
<?= view('partial/footer') ?>
