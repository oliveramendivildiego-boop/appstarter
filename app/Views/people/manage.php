<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', [
    'items' => [['label' => lang('Module.module_' . $controller_name), 'url' => site_url($controller_name)]],
    'right' => form_open(site_url($controller_name . '/search'), ['id' => 'search_form', 'class' => 'd-inline']) .
        '<input type="text" name="search" id="search" class="form-control form-control-sm d-inline-block search-input-inline" />' .
        '</form>' .
        anchor($controller_name . '/view', lang(\ucfirst($controller_name) . '.' . $controller_name . '_new'), ['class' => 'btn btn-primary btn-sm']) .
        anchor($controller_name . '/delete', lang('Common.common_delete'), ['id' => 'delete', 'class' => 'btn btn-primary btn-sm']),
]) ?>
<div id="table_holder"><?= $manage_table ?? '' ?></div>
<div id="feedback_bar"></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    enable_select_all();
    enable_row_selection();
    enable_search('<?= site_url($controller_name . '/suggest') ?>', '<?= lang('Common.common_confirm_search') ?>');
    enable_delete('<?= lang(\ucfirst($controller_name) . '.' . $controller_name . '_confirm_delete') ?>', '<?= lang(\ucfirst($controller_name) . '.' . $controller_name . '_none_selected') ?>');
});
</script>
<?= $this->endSection() ?>
