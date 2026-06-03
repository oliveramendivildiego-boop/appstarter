<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_labotests'), 'url' => site_url('labotests')],
    ['label' => ($labotests_info->anacategoria_id ?? null) ? lang('Labotests.labotests_edit_group') : lang('Labotests.labotests_new_group'), 'url' => null],
]]) ?>

<?php $cid = $labotests_info->anacategoria_id ?? null; ?>
<?= form_open(site_url('labotests/save/' . ($cid ?: '0')), ['id' => 'group_form']) ?>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <label for="name" class="form-label"><?= lang('Labotests.labotests_name') ?> <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control" value="<?= esc($labotests_info->name ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label for="order" class="form-label"><?= lang('Labotests.labotests_order') ?> <span class="text-danger">*</span></label>
            <input type="number" name="order" id="order" class="form-control" value="<?= esc($labotests_info->order ?? 0) ?>" min="0">
        </div>
        <button type="submit" class="btn btn-primary"><?= lang('Common.common_submit') ?></button>
        <a href="<?= site_url('labotests') ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</div>

<?= form_close() ?>
<?= $this->endSection() ?>
