<?php
/**
 * Partial: Formulario de configuración básica de la prueba (detalle).
 * Variables: $labotests_info, $tipos_muestra, $metodos_prueba (catálogos en Config)
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
                <label for="cost" class="form-label"><?= lang('Labotests.labotests_cost_sub') ?> (<?= esc(currency_symbol()) ?>)</label>
                <input type="number" name="cost" id="cost" class="form-control" value="<?= esc($labotests_info->cost ?? 0) ?>" min="0">
            </div>
            <div class="col-md-6 mb-3">
                <label for="cost_deriv" class="form-label"><?= lang('Labotests.labotests_costderiv_sub') ?> (<?= esc(currency_symbol()) ?>)</label>
                <input type="number" name="cost_deriv" id="cost_deriv" class="form-control" value="<?= esc($labotests_info->cost_deriv ?? 0) ?>" min="0">
            </div>
            <div class="col-md-6 mb-3">
                <label for="compleja" class="form-label"><?= lang('Labotests.labotests_tipo_analisis') ?></label>
                <select name="compleja" id="compleja" class="form-control">
                    <option value="0" <?= ((int) ($labotests_info->compleja ?? 0) === 0 ? 'selected' : '') ?>><?= lang('Labotests.labotests_tipo_analisis_simple') ?></option>
                    <option value="1" <?= ((int) ($labotests_info->compleja ?? 0) === 1 ? 'selected' : '') ?>><?= lang('Labotests.labotests_tipo_analisis_tabla') ?></option>
                    <option value="2" <?= ((int) ($labotests_info->compleja ?? 0) === 2 ? 'selected' : '') ?>><?= lang('Labotests.labotests_tipo_analisis_cultivo') ?></option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="mostrar_valores" class="form-label">Mostrar valores</label>
                <select name="mostrar_valores" id="mostrar_valores" class="form-control">
                    <option value="0" <?= ((int)($labotests_info->mostrar_valores ?? 0) === 0 ? 'selected' : '') ?>>NO</option>
                    <option value="1" <?= ((int)($labotests_info->mostrar_valores ?? 0) === 1 ? 'selected' : '') ?>>SI</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="tipo_muestra_id" class="form-label">Tipo de muestra</label>
                <select name="tipo_muestra_id" id="tipo_muestra_id" class="form-select">
                    <option value="">— Sin especificar —</option>
                    <?php
                    $selTipo = (int) ($labotests_info->tipo_muestra_id ?? 0);
                    foreach ($tipos_muestra ?? [] as $tm):
                        $tid = (int) ($tm['tipo_muestra_id'] ?? 0);
                    ?>
                    <option value="<?= $tid ?>" <?= $selTipo === $tid ? 'selected' : '' ?>><?= esc($tm['nombre'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Catálogo en <a href="<?= site_url('config?tab=tipos_muestra') ?>">Configuración → Tipos de muestra</a>.</small>
            </div>
            <div class="col-md-6 mb-3">
                <label for="metodo_id" class="form-label">Método</label>
                <select name="metodo_id" id="metodo_id" class="form-select">
                    <option value="">— Sin especificar —</option>
                    <?php
                    $selMet = (int) ($labotests_info->metodo_id ?? 0);
                    foreach ($metodos_prueba ?? [] as $mp):
                        $mid = (int) ($mp['metodo_id'] ?? 0);
                    ?>
                    <option value="<?= $mid ?>" <?= $selMet === $mid ? 'selected' : '' ?>><?= esc($mp['nombre'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Catálogo en <a href="<?= site_url('config?tab=metodos_prueba') ?>">Configuración → Métodos de prueba</a>.</small>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= lang('Common.common_submit') ?></button>
        <a href="<?= site_url('labotests') ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</div>
