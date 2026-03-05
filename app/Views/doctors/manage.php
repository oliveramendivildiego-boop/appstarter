<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$bcRight = form_open(site_url('doctors/search'), ['id' => 'search_form', 'class' => 'd-inline']) .
    '<input type="text" name="search" id="search" class="form-control form-control-sm d-inline-block search-input-inline" />' .
    '</form>' .
    anchor('doctors/view', lang('Doctors.doctors_new'), ['class' => 'btn btn-primary btn-sm']) .
    anchor('doctors/delete', lang('Common.common_delete'), ['id' => 'delete', 'class' => 'btn btn-primary btn-sm']);
?>
<?= view('partial/breadcrumb_nav', [
    'items' => [['label' => lang('Module.module_doctors'), 'url' => site_url('doctors')]],
    'right' => $bcRight,
]) ?>
<div id="table_holder"><?= $manage_table ?? '' ?></div>
<div id="feedback_bar"></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    enable_select_all();
    enable_row_selection();
    enable_search('<?= site_url('doctors/suggest') ?>', '<?= lang('Doctors.doctors_confirm_search') ?>');
    enable_delete('<?= lang('Doctors.doctors_confirm_delete') ?>', '<?= lang('Doctors.doctors_none_selected') ?>');
});
</script>
<?= $this->endSection() ?>
