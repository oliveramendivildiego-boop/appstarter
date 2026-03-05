<?php
$person_info = $person_info ?? new stdClass();
?>
<input type="hidden" name="person_id" value="<?= esc($person_info->person_id ?? '') ?>" id="person_id">
<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Common.common_first_name') . ':', 'first_name', ['class' => 'form-label required']) ?>
        <?= form_input(['name' => 'first_name', 'id' => 'first_name', 'class' => 'form-control', 'value' => $person_info->first_name ?? '']) ?>
    </div>
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Common.common_last_name_fa') . ':', 'last_name_fa', ['class' => 'form-label required']) ?>
        <?= form_input(['name' => 'last_name_fa', 'id' => 'last_name_fa', 'class' => 'form-control', 'value' => $person_info->last_name_fa ?? $person_info->last_name ?? '']) ?>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Common.common_last_name_mom') . ':', 'last_name_mom', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'last_name_mom', 'id' => 'last_name_mom', 'class' => 'form-control', 'value' => $person_info->last_name_mom ?? '']) ?>
    </div>
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Common.common_person_id') . ':', 'ci', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'ci', 'id' => 'ci', 'class' => 'form-control', 'value' => $person_info->ci ?? '']) ?>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Common.common_phone_number') . ':', 'phone_number', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'phone_number', 'id' => 'phone_number', 'class' => 'form-control', 'value' => $person_info->phone_number ?? '']) ?>
    </div>
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Common.common_email') . ':', 'email', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'email', 'id' => 'email', 'class' => 'form-control', 'value' => $person_info->email ?? '']) ?>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Customers.customers_birthday') . ':', 'birthday', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'birthday', 'id' => 'birthday', 'class' => 'form-control', 'value' => $person_info->birthday ?? '']) ?>
    </div>
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Customers.customers_gender') . ':', 'gender', ['class' => 'form-label']) ?>
        <?= form_dropdown('gender', ['' => '-- Seleccione --', '1' => 'Masculino', '2' => 'Femenino'], $person_info->gender ?? '', 'id="gender" class="form-select"') ?>
    </div>
</div>
<div class="row">
    <div class="col-md-12 mb-3">
        <?= form_label(lang('Common.common_comments') . ':', 'comments', ['class' => 'form-label']) ?>
        <?= form_textarea(['name' => 'comments', 'id' => 'comments', 'value' => $person_info->comments ?? '', 'rows' => '5', 'class' => 'form-control']) ?>
    </div>
</div>
