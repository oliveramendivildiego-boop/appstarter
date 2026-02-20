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
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <?= form_label(lang('Common.common_comments') . ':', 'comments', ['class' => 'form-label']) ?>
        <?= form_textarea(['name' => 'comments', 'id' => 'comments', 'value' => $doctor_info->comments ?? '', 'rows' => '5', 'class' => 'form-control']) ?>
    </div>
</div>







