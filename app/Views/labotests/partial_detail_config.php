<?php
/**
 * Partial: Formulario de configuración básica de la prueba (detalle).
 * Variables requeridas: $labotests_info
 */
?>
<div class="card">
    <div class="card-header"><strong><?= lang('Labotests.labotests_config') ?></strong></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label"><?= lang('Labotests.labotests_name_sub') ?> <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="<?= esc($labotests_info->name ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="order" class="form-label"><?= lang('Labotests.labotests_order') ?></label>
                <input type="number" name="order" id="order" class="form-control" value="<?= esc($labotests_info->order ?? 0) ?>" min="0">
            </div>
            <div class="col-md-6 mb-3">
                <label for="cost" class="form-label"><?= lang('Labotests.labotests_cost_sub') ?> (Bs)</label>
                <input type="number" name="cost" id="cost" class="form-control" value="<?= esc($labotests_info->cost ?? 0) ?>" min="0">
            </div>
            <div class="col-md-6 mb-3">
                <label for="cost_deriv" class="form-label"><?= lang('Labotests.labotests_costderiv_sub') ?> (Bs)</label>
                <input type="number" name="cost_deriv" id="cost_deriv" class="form-control" value="<?= esc($labotests_info->cost_deriv ?? 0) ?>" min="0">
            </div>
            <div class="col-md-6 mb-3">
                <label for="compleja" class="form-label"><?= lang('Labotests.labotests_compuesta') ?></label>
                <select name="compleja" id="compleja" class="form-control">
                    <option value="0" <?= (($labotests_info->compleja ?? 0) == 0 ? 'selected' : '') ?>><?= lang('Labotests.labotests_no') ?></option>
                    <option value="1" <?= (($labotests_info->compleja ?? 0) == 1 ? 'selected' : '') ?>><?= lang('Labotests.labotests_yes') ?></option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="mostrar_valores" class="form-label">Mostrar valores</label>
                <select name="mostrar_valores" id="mostrar_valores" class="form-control">
                    <option value="0" <?= ((int)($labotests_info->mostrar_valores ?? 0) === 0 ? 'selected' : '') ?>>NO</option>
                    <option value="1" <?= ((int)($labotests_info->mostrar_valores ?? 0) === 1 ? 'selected' : '') ?>>SI</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= lang('Common.common_submit') ?></button>
        <a href="<?= site_url('labotests') ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</div>
