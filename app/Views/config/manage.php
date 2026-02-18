<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'config']) ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_config'), 'url' => site_url('config')],
]]) ?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-gear me-2"></i><?= lang('Config.config_info') ?></h5>
    </div>
    <div class="card-body">
        <p class="mb-3">
    <a href="<?= site_url('config/backup') ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
        <i class="fa-solid fa-database me-1"></i> Exportar respaldo SQL
    </a>
</p>
<?= form_open_multipart(site_url('config/save'), ['id' => 'config_form', 'data-async' => '1']) ?>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_company'), 'company', ['class' => 'form-label']) ?>
                <?= form_input(['name' => 'company', 'id' => 'company', 'class' => 'form-control', 'autocomplete' => 'organization', 'value' => $config['company'] ?? '']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_phone'), 'phone', ['class' => 'form-label']) ?>
                <?= form_input(['name' => 'phone', 'id' => 'phone', 'class' => 'form-control', 'autocomplete' => 'tel', 'value' => $config['phone'] ?? '']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_header_brand'), 'header_brand', ['class' => 'form-label']) ?>
                <?= form_dropdown('header_brand', [
                    'logo'  => lang('Config.config_header_brand_logo'),
                    'name'  => lang('Config.config_header_brand_name'),
                ], $config['header_brand'] ?? 'logo', 'id="header_brand" class="form-select" autocomplete="off"') ?>
                <small class="text-muted"><?= lang('Config.config_header_brand_help') ?></small>
            </div>
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_logo'), 'logo_upload', ['class' => 'form-label']) ?>
                <?php $logoPath = $config['logo'] ?? 'images/logo-john.png'; ?>
                <?php if (!empty($logoPath) && file_exists(FCPATH . $logoPath)): ?>
                    <div class="mb-2">
                        <img src="<?= base_url($logoPath) ?>?v=<?= time() ?>" alt="Logo actual" style="max-height:60px" class="border rounded p-1">
                    </div>
                <?php endif; ?>
                <?= form_upload(['name' => 'logo_upload', 'id' => 'logo_upload', 'class' => 'form-control', 'accept' => 'image/*', 'autocomplete' => 'off']) ?>
                <small class="text-muted">Formatos: JPG, PNG, GIF. Se reemplazará el logo actual.</small>
            </div>
        </div>
        <div class="mb-3">
            <?= form_label(lang('Config.config_address'), 'address', ['class' => 'form-label']) ?>
            <?= form_input(['name' => 'address', 'id' => 'address', 'class' => 'form-control', 'autocomplete' => 'street-address', 'value' => $config['address'] ?? '']) ?>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_email'), 'email', ['class' => 'form-label']) ?>
                <?= form_input(['name' => 'email', 'id' => 'email', 'type' => 'email', 'class' => 'form-control', 'autocomplete' => 'email', 'value' => $config['email'] ?? '']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_website'), 'website', ['class' => 'form-label']) ?>
                <?= form_input(['name' => 'website', 'id' => 'website', 'class' => 'form-control', 'autocomplete' => 'url', 'value' => $config['website'] ?? '']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_language'), 'language', ['class' => 'form-label']) ?>
                <?= form_dropdown('language', ['es' => 'Español', 'en' => 'English'], $config['language'] ?? 'es', 'id="language" class="form-select" autocomplete="off"') ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_timezone'), 'timezone', ['class' => 'form-label']) ?>
                <?= form_dropdown('timezone', $timezone_options ?? [], $config['timezone'] ?? 'America/Bogota', 'id="timezone" class="form-select" autocomplete="off"') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_currency_symbol'), 'currency_symbol', ['class' => 'form-label']) ?>
                <?= form_input(['name' => 'currency_symbol', 'id' => 'currency_symbol', 'class' => 'form-control', 'autocomplete' => 'off', 'value' => $config['currency_symbol'] ?? '$']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_theme_color'), 'theme_color', ['class' => 'form-label']) ?>
                <?php
                $palette = $theme_palette ?? [];
                $currentColor = strtolower($config['theme_color'] ?? '#FF7218');
                $selectOptions = ['' => 'Personalizado'] + $palette;
                $selectedHex = '';
                foreach (array_keys($palette) as $h) {
                    if (strtolower(ltrim($h, '#')) === strtolower(ltrim($currentColor, '#'))) {
                        $selectedHex = $h;
                        break;
                    }
                }
                ?>
                <?= form_dropdown('theme_palette_select', $selectOptions, $selectedHex, 'id="theme_palette_select" class="form-select mb-2" autocomplete="off"') ?>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="color" name="theme_color" id="theme_color" value="<?= esc($config['theme_color'] ?? '#FF7218') ?>" class="form-control form-control-color" style="width:60px;height:40px;cursor:pointer" autocomplete="off">
                    <input type="text" id="theme_color_hex" name="theme_color_hex" value="<?= esc($config['theme_color'] ?? '#FF7218') ?>" class="form-control" style="max-width:100px" readonly autocomplete="off">
                </div>
                <small class="text-muted">O elige un color personalizado con el selector.</small>
            </div>
        </div>
        <div class="mb-3">
            <?= form_label(lang('Config.config_return_policy'), 'return_policy', ['class' => 'form-label']) ?>
            <?= form_textarea(['name' => 'return_policy', 'id' => 'return_policy', 'class' => 'form-control', 'rows' => 4, 'autocomplete' => 'off', 'value' => $config['return_policy'] ?? '']) ?>
        </div>
        <div class="mb-3">
            <div class="form-check">
                <?= form_checkbox('print_after_sale', '1', ($config['print_after_sale'] ?? '') ? true : false, 'id="print_after_sale" class="form-check-input" autocomplete="off"') ?>
                <?= form_label(lang('Config.config_print_after_sale'), 'print_after_sale', ['class' => 'form-check-label']) ?>
            </div>
        </div>
        <button type="submit" id="config_save_btn" name="config_save_btn" class="btn btn-primary"><?= lang('Config.config_save_btn') ?></button>
        <?= form_close() ?>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-people-group me-2"></i>Grupos de población (por edad)</h5>
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <table class="table table-sm table-bordered mb-4">
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Edad</th>
                    <th>Orden</th>
                    <th class="text-center" style="width:120px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($poblaciones ?? [] as $p): ?>
                <tr>
                    <td><?= esc($p['name'] ?? '') ?></td>
                    <td><?= \App\Models\PoblacionModel::formatRangoEdad($p) ?></td>
                    <td><?= (int)($p['orden'] ?? 0) ?></td>
                    <td class="text-center">
                        <form method="get" action="<?= esc(site_url('config')) ?>#form_poblacion" style="display:inline">
                        <input type="hidden" name="editar" value="<?= (int)($p['id_poblacion'] ?? 0) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        </form>
                        <a href="<?= site_url('config/deletepoblacion/' . (int)($p['id_poblacion'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Eliminar este grupo de población?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($poblaciones)): ?>
                <tr><td colspan="4" class="text-muted">No hay grupos definidos. Agregue uno abajo.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h6 class="mb-3" id="form_poblacion"><?= ($editar_poblacion ?? -1) >= 0 ? 'Editar grupo' : 'Agregar grupo' ?></h6>
        <?= form_open('config/savepoblacion', ['class' => 'border p-3 rounded']) ?>
        <input type="hidden" name="id_poblacion" value="<?= ($editar_poblacion ?? -1) >= 0 ? (int)$editar_poblacion : '' ?>">
        <div class="row">
            <div class="col-md-4 mb-2">
                <label class="form-label">Nombre del grupo <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control form-control-sm" value="<?= esc($editar_poblacion_data['name'] ?? '') ?>" required placeholder="Ej: Recién nacido">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Edad mín</label>
                <input type="text" name="edad_min" class="form-control form-control-sm" value="<?= esc($editar_poblacion_data['edad_min'] ?? '') ?>" placeholder="0">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Edad máx</label>
                <input type="text" name="edad_max" class="form-control form-control-sm" value="<?= esc($editar_poblacion_data['edad_max'] ?? '') ?>" placeholder="28">
                <small class="text-muted">Dejar vacío para "≥"</small>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Unidad</label>
                <select name="unidad" class="form-control form-control-sm">
                    <option value="">-</option>
                    <option value="dias" <?= (($editar_poblacion_data['unidad'] ?? '') === 'dias') ? 'selected' : '' ?>>días</option>
                    <option value="meses" <?= (($editar_poblacion_data['unidad'] ?? '') === 'meses') ? 'selected' : '' ?>>meses</option>
                    <option value="años" <?= (($editar_poblacion_data['unidad'] ?? '') === 'años') ? 'selected' : '' ?>>años</option>
                </select>
            </div>
            <div class="col-md-1 mb-2">
                <label class="form-label">Orden</label>
                <input type="number" name="orden" class="form-control form-control-sm" value="<?= (int)($editar_poblacion_data['orden'] ?? 0) ?>" min="0">
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm me-2"><?= ($editar_poblacion ?? -1) >= 0 ? 'Actualizar' : 'Agregar' ?></button>
                <?php if (($editar_poblacion ?? -1) >= 0): ?>
                <a href="<?= site_url('config') ?>" class="btn btn-secondary btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>
        </div>
        <small class="text-muted">Ejemplos: Recién nacido (0-28 días), Lactante (29-330 días ≈ 11 meses), Niño pequeño (1-5 años), Adulto mayor (mín 60, máx vacío, años)</small>
        <?= form_close() ?>
    </div>
</div>

<script src="<?= base_url('js/config.js') ?>" defer></script>
<?= view('partial/footer') ?>
