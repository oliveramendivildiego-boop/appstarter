<?php
$doctor_info = $doctor_info ?? new stdClass();
?>
<input type="hidden" name="doctor_id" value="<?= $doctor_info->doctor_id ?? '' ?>" id="doctor_id">

<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Doctors.doctors_name') . ':', 'name', ['class' => 'form-label required']) ?>
        <?= form_input(['name' => 'name', 'id' => 'name', 'class' => 'form-control', 'value' => $doctor_info->name ?? '']) ?>
    </div>
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Doctors.doctors_phone') . ':', 'phone_number', ['class' => 'form-label required']) ?>
        <?= form_input(['name' => 'phone_number', 'id' => 'phone_number', 'class' => 'form-control', 'value' => $doctor_info->phone_number ?? '']) ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Doctors.doctors_speciality') . ':', 'speciality', ['class' => 'form-label required']) ?>
        <?= form_input(['name' => 'speciality', 'id' => 'speciality', 'class' => 'form-control', 'value' => $doctor_info->speciality ?? '']) ?>
    </div>
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Doctors.doctors_address') . ':', 'address', ['class' => 'form-label required']) ?>
        <?= form_input(['name' => 'address', 'id' => 'address', 'class' => 'form-control', 'value' => $doctor_info->address ?? '']) ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Doctors.doctors_gender') . ':', 'gender', ['class' => 'form-label required']) ?>
        <?= form_dropdown('gender', ['' => '-- Seleccione --', '1' => 'Masculino', '2' => 'Femenino'], $doctor_info->gender ?? '', 'id="gender" class="form-select"') ?>
    </div>
    <div class="col-md-6 mb-3">
        <div class="form-check mt-4">
            <?= form_checkbox(['name' => 'has_commission', 'id' => 'has_commission', 'class' => 'form-check-input', 'value' => '1', 'checked' => ($doctor_info->has_commission ?? 0) == 1]) ?>
            <?= form_label('¿Este doctor usa comisiones?', 'has_commission', ['class' => 'form-check-label']) ?>
        </div>
    </div>
</div>

<div class="row" id="commission_field" style="<?= ($doctor_info->has_commission ?? 0) == 1 ? '' : 'display:none;' ?>">
    <div class="col-md-6 mb-3">
        <?= form_label('Comisión (%)', 'commission_percent', ['class' => 'form-label']) ?>
        <div class="input-group">
            <?= form_input(['name' => 'commission_percent', 'id' => 'commission_percent', 'class' => 'form-control', 'value' => $doctor_info->commission_percent ?? 0.00, 'step' => '0.01', 'min' => '0', 'max' => '100', 'type' => 'number', 'placeholder' => '0.00']) ?>
            <span class="input-group-text">%</span>
        </div>
        <small class="form-text text-muted">Porcentaje de comisión que recibirá el doctor por cada prueba realizada.</small>
    </div>
</div>

<hr class="my-4">
<h6 class="mb-3"><?= lang('Doctors.doctors_login_access') ?? 'Acceso al portal' ?></h6>
<p class="text-muted small mb-3">Opcional. Si se configuran usuario y contraseña, el doctor podrá ingresar al portal y ver solo el historial de sus pacientes.</p>
<div class="row">
    <div class="col-md-4 mb-3">
        <?= form_label('Usuario', 'username', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'username', 'id' => 'username', 'class' => 'form-control', 'value' => $doctor_info->username ?? '', 'placeholder' => 'Ej: dr.garcia']) ?>
    </div>
    <div class="col-md-4 mb-3">
        <?= form_label('Contraseña', 'password', ['class' => 'form-label']) ?>
        <?= form_password(['name' => 'password', 'id' => 'password', 'class' => 'form-control', 'value' => '', 'placeholder' => (isset($doctor_info->username) && $doctor_info->username) ? 'Dejar vacío para no cambiar' : '']) ?>
    </div>
    <div class="col-md-4 mb-3">
        <?= form_label('Correo', 'email', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'email', 'id' => 'email', 'type' => 'email', 'class' => 'form-control', 'value' => $doctor_info->email ?? '', 'placeholder' => 'Para login con correo']) ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <?= form_label(lang('Common.common_comments') . ':', 'comments', ['class' => 'form-label']) ?>
        <?= form_textarea(['name' => 'comments', 'id' => 'comments', 'value' => $doctor_info->comments ?? '', 'rows' => '5', 'class' => 'form-control']) ?>
    </div>
</div>







