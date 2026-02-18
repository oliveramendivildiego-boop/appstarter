<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'labotests']) ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_labotests'), 'url' => site_url('labotests')],
    ['label' => $labotests_master->name ?? '', 'url' => site_url('labotests/view/' . $labotests_namecate)],
    ['label' => lang('Labotests.labotests_new_analysis'), 'url' => null],
]]) ?>

<?= form_open('labotests/savesubmain/' . $labotests_namecate, ['id' => 'subgroup_form']) ?>
<input type="hidden" id="prianacategoria_id" name="prianacategoria_id" value="<?= (int)($labotests_info->prianacategoria_id ?? 0) ?>">
<input type="hidden" id="anacategoria_id" name="anacategoria_id" value="<?= (int)$labotests_namecate ?>">

<div class="card">
    <div class="card-body">
        <p class="text-muted"><?= lang('Labotests.labotests_no_price_here') ?></p>
        <div class="mb-3">
            <label for="name" class="form-label"><?= lang('Labotests.labotests_name_sub') ?> <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control" value="<?= esc($labotests_info->name ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label for="order" class="form-label"><?= lang('Labotests.labotests_order') ?> <span class="text-danger">*</span></label>
            <input type="number" name="order" id="order" class="form-control" value="<?= esc($labotests_info->order ?? 0) ?>" min="0">
        </div>
        <div class="mb-3">
            <label for="compleja" class="form-label"><?= lang('Labotests.labotests_compuesta') ?></label>
            <select name="compleja" id="compleja" class="form-control">
                <option value="0" <?= (($labotests_info->compleja ?? 0) == 0 ? 'selected' : '') ?>><?= lang('Labotests.labotests_no') ?></option>
                <option value="1" <?= (($labotests_info->compleja ?? 0) == 1 ? 'selected' : '') ?>><?= lang('Labotests.labotests_yes') ?></option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?= lang('Common.common_submit') ?></button>
        <a href="<?= site_url('labotests') ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</div>

<?= form_close() ?>

<?= view('partial/footer') ?>
