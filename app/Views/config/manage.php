<?= $this->extend('layouts/main') ?>
<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="<?= base_url('css/comprobante-editor.css') ?>?v=20">
<script src="<?= base_url('js/vendor/jquery.validate.min.js') ?>"></script>
<?php if (($can_manage_tenants ?? false)): ?>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<?php endif; ?>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [['label' => lang('Module.module_config'), 'url' => site_url('config')]]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php $activeTab = isset($active_tab) ? $active_tab : 'sistema'; ?>
<style>
#configTabs.config-tabs-nav {
    border-bottom: 0;
    gap: .45rem;
}
#configTabs.config-tabs-nav .nav-link {
    border: 1px solid #cfd8e3;
    border-radius: .65rem;
    background: #f4f7fb;
    color: #2d4059;
    font-weight: 600;
    padding: .45rem .85rem;
    transition: all .15s ease-in-out;
}
#configTabs.config-tabs-nav .nav-link:hover,
#configTabs.config-tabs-nav .nav-link:focus {
    background: #eaf1fb;
    border-color: #9db6d8;
    color: #1d3557;
}
#configTabs.config-tabs-nav .nav-link.active {
    background: #1f6fd7;
    border-color: #1f6fd7;
    color: #fff;
    box-shadow: 0 .2rem .6rem rgba(31, 111, 215, .25);
}
</style>
<ul class="nav nav-tabs mb-3 config-tabs-nav" id="configTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'sistema' ? 'active' : '' ?>" id="tab-sistema-btn" data-bs-toggle="tab" data-bs-target="#tab-sistema" type="button" role="tab">Configuración del sistema</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'institucion_descuentos' ? 'active' : '' ?>" id="tab-institucion-descuentos-btn" data-bs-toggle="tab" data-bs-target="#tab-institucion-descuentos" type="button" role="tab">Descuentos por institución</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'comprobante' ? 'active' : '' ?>" id="tab-comprobante-btn" data-bs-toggle="tab" data-bs-target="#tab-comprobante" type="button" role="tab">Estilo comprobante</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'estilo' ? 'active' : '' ?>" id="tab-estilo-btn" data-bs-toggle="tab" data-bs-target="#tab-estilo" type="button" role="tab"><?= lang('Config.config_style_tab_nav') ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'sin' ? 'active' : '' ?>" id="tab-sin-btn" data-bs-toggle="tab" data-bs-target="#tab-sin" type="button" role="tab">Facturación SIN</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'poblacion' ? 'active' : '' ?>" id="tab-poblacion-btn" data-bs-toggle="tab" data-bs-target="#tab-poblacion" type="button" role="tab">Grupos de población (por edad)</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'lab_validacion' ? 'active' : '' ?>" id="tab-lab-validacion-btn" data-bs-toggle="tab" data-bs-target="#tab-lab-validacion" type="button" role="tab"><?= lang('Config.config_lab_validation_tab_nav') ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'metodos_prueba' ? 'active' : '' ?>" id="tab-metodos_prueba-btn" data-bs-toggle="tab" data-bs-target="#tab-metodos_prueba" type="button" role="tab">Métodos de prueba</button>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" href="<?= site_url('config/pdf-templates') ?>"><i class="fa-solid fa-file-pdf me-1"></i><?= lang('Config.config_pdf_templates_tab') ?></a>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'sobres' ? 'active' : '' ?>" id="tab-sobres-btn" data-bs-toggle="tab" data-bs-target="#tab-sobres" type="button" role="tab"><i class="fa-solid fa-envelope me-1"></i>Sobres</button>
    </li>
    <?php if (($can_manage_tenants ?? false)): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'tenant_home_broadcast' ? 'active' : '' ?>" id="tab-tenant-home-broadcast-btn" data-bs-toggle="tab" data-bs-target="#tab-tenant-home-broadcast" type="button" role="tab">Aviso en dashboard</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'tenant_subscriptions' ? 'active' : '' ?>" id="tab-tenant-subscriptions-btn" data-bs-toggle="tab" data-bs-target="#tab-tenant-subscriptions" type="button" role="tab">Pagos / suscripciones</button>
    </li>
    <?php endif; ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'sesiones' ? 'active' : '' ?>" id="tab-sesiones-btn" data-bs-toggle="tab" data-bs-target="#tab-sesiones" type="button" role="tab">Sesiones activas</button>
    </li>
    <?php if (($can_manage_tenants ?? false)): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'tenants' ? 'active' : '' ?>" id="tab-tenants-btn" data-bs-toggle="tab" data-bs-target="#tab-tenants" type="button" role="tab">Tenants</button>
    </li>
    <?php endif; ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'tipos_muestra' ? 'active' : '' ?>" id="tab-tipos_muestra-btn" data-bs-toggle="tab" data-bs-target="#tab-tipos_muestra" type="button" role="tab">Tipos de muestra</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'opciones' ? 'active' : '' ?>" id="tab-opciones-btn" data-bs-toggle="tab" data-bs-target="#tab-opciones" type="button" role="tab">Tipos de resultado</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'leyendas_cultivo' ? 'active' : '' ?>" id="tab-leyendas_cultivo-btn" data-bs-toggle="tab" data-bs-target="#tab-leyendas_cultivo" type="button" role="tab">Leyendas cultivo</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'whatsapp' ? 'active' : '' ?>" id="tab-whatsapp-btn" data-bs-toggle="tab" data-bs-target="#tab-whatsapp" type="button" role="tab">WhatsApp</button>
    </li>
</ul>

<div class="tab-content" id="configTabsContent">
    <!-- Pestaña: Configuración del sistema -->
    <div class="tab-pane fade <?= $activeTab === 'sistema' ? 'show active' : '' ?>" id="tab-sistema" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fa-solid fa-gear me-2"></i><?= lang('Config.config_info') ?></h5>
            </div>
            <div class="card-body">
<?= form_open_multipart(site_url('config/save'), ['id' => 'config_form', 'data-async' => '1', 'data-reload-on-success' => '1']) ?>
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
                        <img src="<?= base_url($logoPath) ?>?v=<?= time() ?>" alt="Logo actual" class="border rounded p-1 config-logo-preview">
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
                <?php
                $labTzOffsetNow = \App\Services\RegisterService::reportNow()->format('P');
                $tzMisconfiguredGmt6 = ($labTzOffsetNow === '-05:00');
                ?>
                <?php if ($tzMisconfiguredGmt6): ?>
                <div class="alert alert-warning py-2 small mb-2">
                    <?= lang('Config.config_timezone_wrong_offset_warning') ?>
                </div>
                <?php endif; ?>
                <?= form_dropdown('timezone', $timezone_options ?? [], $config['timezone'] ?? 'America/Mexico_City', 'id="timezone" class="form-select" autocomplete="off"') ?>
                <p class="form-text small mb-1"><?= lang('Config.config_timezone_gmt6_hint') ?></p>
                <p class="form-text small mb-2">
                    <strong><?= lang('Config.config_timezone_lab_clock') ?>:</strong>
                    <?= esc(\App\Services\RegisterService::formatNowForReport()) ?>
                    <span class="text-muted">(<?= esc(\App\Services\RegisterService::labTimezoneSummary()) ?>)</span>
                </p>
                <div class="form-check">
                    <input type="hidden" name="lab_datetime_storage" value="0">
                    <input class="form-check-input" type="checkbox" name="lab_datetime_storage" id="lab_datetime_storage" value="1"
                        <?= ($config['lab_datetime_storage'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="lab_datetime_storage"><?= lang('Config.config_lab_datetime_storage') ?></label>
                </div>
                <p class="form-text small mb-0"><?= lang('Config.config_lab_datetime_storage_help') ?></p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_currency_symbol'), 'currency_symbol', ['class' => 'form-label']) ?>
                <?= form_input(['name' => 'currency_symbol', 'id' => 'currency_symbol', 'class' => 'form-control', 'autocomplete' => 'off', 'value' => $config['currency_symbol'] ?? '$']) ?>
                    <?= form_label(lang('Config.config_currency_side'), 'currency_side', ['class' => 'form-label mt-3']) ?>
                    <?= form_dropdown(
                        'currency_side',
                        [
                            'left'  => lang('Config.config_currency_side_left'),
                            'right' => lang('Config.config_currency_side_right'),
                        ],
                        $config['currency_side'] ?? 'left',
                        'id="currency_side" class="form-select" autocomplete="off"'
                    ) ?>
            </div>
            <div class="col-md-6 mb-3">
                <label for="label_sin_doctor" class="form-label"><?= lang('Config.config_label_sin_doctor') ?></label>
                <?= form_input([
                    'name'        => 'label_sin_doctor',
                    'id'          => 'label_sin_doctor',
                    'class'       => 'form-control',
                    'maxlength'   => 160,
                    'value'       => $config['label_sin_doctor'] ?? 'Sin doctor',
                    'autocomplete'=> 'off',
                ]) ?>
                <small class="text-muted"><?= lang('Config.config_label_sin_doctor_help') ?></small>
            </div>
        </div>
        <div class="mb-3">
            <?= form_label(lang('Config.config_return_policy'), 'return_policy', ['class' => 'form-label']) ?>
            <?= form_textarea(['name' => 'return_policy', 'id' => 'return_policy', 'class' => 'form-control', 'rows' => 4, 'autocomplete' => 'off', 'value' => $config['return_policy'] ?? '']) ?>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <?= form_label(lang('Config.config_decimales_sugerencia'), 'decimales_sugerencia', ['class' => 'form-label']) ?>
                <?= form_input(['name' => 'decimales_sugerencia', 'id' => 'decimales_sugerencia', 'type' => 'number', 'min' => 0, 'max' => 10, 'class' => 'form-control', 'value' => $config['decimales_sugerencia'] ?? '2', 'autocomplete' => 'off']) ?>
                <small class="text-muted"><?= lang('Config.config_decimales_sugerencia_help') ?></small>
            </div>
            <div class="col-md-4 mb-3">
                <label for="dias_alerta_vencimiento" class="form-label">Días de alerta para vencimiento de insumos</label>
                <?= form_input(['name' => 'dias_alerta_vencimiento', 'id' => 'dias_alerta_vencimiento', 'type' => 'number', 'min' => 1, 'max' => 365, 'class' => 'form-control', 'value' => $config['dias_alerta_vencimiento'] ?? '40', 'autocomplete' => 'off']) ?>
                <small class="text-muted">Los lotes que venzan en los próximos X días se marcarán en amarillo en el reporte de insumos por vencimiento.</small>
            </div>
            <div class="col-md-4 mb-3">
                <label for="stock_alerta_factor" class="form-label">Factor alerta de stock bajo</label>
                <?= form_input(['name' => 'stock_alerta_factor', 'id' => 'stock_alerta_factor', 'type' => 'number', 'min' => '0.5', 'max' => '3', 'step' => '0.1', 'class' => 'form-control', 'value' => $config['stock_alerta_factor'] ?? '1', 'autocomplete' => 'off']) ?>
                <small class="text-muted">1.0 = alerta al llegar al stock mínimo. Ejemplo: 1.5 alerta antes del mínimo.</small>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <?= form_label(lang('Config.config_pdf_template'), 'pdf_result_template_id', ['class' => 'form-label']) ?>
                <div class="mb-2">
                    <a href="<?= site_url('config/pdf-templates') ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file-pdf me-1"></i><?= lang('Config.config_pdf_templates_link') ?></a>
                </div>
                <?php if (!empty($pdf_templates)): ?>
                <?php
                $tplOpts = [];
                foreach ($pdf_templates as $pt) {
                    $tplOpts[(string) $pt->id] = $pt->name ?? ('Plantilla #' . $pt->id);
                }
                $selTpl = (int) ($config['pdf_result_template_id'] ?? 1);
                if ($selTpl < 1 || ! array_key_exists((string) $selTpl, $tplOpts)) {
                    $selTpl = (int) (array_key_first($tplOpts) ?: 1);
                }
                ?>
                <?= form_dropdown('pdf_result_template_id', $tplOpts, $selTpl, 'id="pdf_result_template_id" class="form-select" autocomplete="off"') ?>
                <small class="text-muted d-block mt-1"><?= lang('Config.config_pdf_template_help') ?></small>
                <label class="form-label mt-3" for="print_result_template_id"><?= lang('Config.config_print_template') ?></label>
                <?php
                $selPrint = (int) ($config['print_result_template_id'] ?? $selTpl);
                if ($selPrint < 1 || ! array_key_exists((string) $selPrint, $tplOpts)) {
                    $selPrint = $selTpl;
                }
                ?>
                <?= form_dropdown('print_result_template_id', $tplOpts, $selPrint, 'id="print_result_template_id" class="form-select" autocomplete="off"') ?>
                <small class="text-muted d-block mt-1"><?= lang('Config.config_print_template_help') ?></small>
                <?php else: ?>
                <div class="alert alert-warning mb-0">
                    <?= lang('Config.config_pdf_template_missing') ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="mb-3">
            <div class="form-check">
                <?= form_checkbox('print_after_sale', '1', ($config['print_after_sale'] ?? '') ? true : false, 'id="print_after_sale" class="form-check-input" autocomplete="off"') ?>
                <?= form_label(lang('Config.config_print_after_sale'), 'print_after_sale', ['class' => 'form-check-label']) ?>
            </div>
        </div>
        <div class="mb-3">
            <input type="hidden" name="show_order_barcode" value="0">
            <div class="form-check">
                <?= form_checkbox('show_order_barcode', '1', (($config['show_order_barcode'] ?? '1') === '1'), 'id="show_order_barcode" class="form-check-input" autocomplete="off"') ?>
                <?= form_label('Mostrar código de barras en orden registrada', 'show_order_barcode', ['class' => 'form-check-label']) ?>
            </div>
            <small class="text-muted">Si se desactiva, la orden se imprimirá sin código de barras.</small>
        </div>
        <div class="mb-3">
            <input type="hidden" name="show_order_costs" value="0">
            <div class="form-check">
                <?= form_checkbox('show_order_costs', '1', (($config['show_order_costs'] ?? '0') === '1'), 'id="show_order_costs" class="form-check-input" autocomplete="off"') ?>
                <?= form_label(lang('Config.config_show_order_costs'), 'show_order_costs', ['class' => 'form-check-label']) ?>
            </div>
            <small class="text-muted d-block mt-1"><?= lang('Config.config_show_order_costs_help') ?></small>
        </div>
        <div class="mb-3">
            <label for="registers_lista_fecha_default" class="form-label"><?= lang('Config.config_registers_lista_fecha_default') ?></label>
            <?php
            $listaFechaDefault = \App\Services\ConfigService::normalizeRegistersListaFechaDefault(
                (string) ($config['registers_lista_fecha_default'] ?? 'hoy')
            );
            $listaFechaOpts = [
                'hoy'    => lang('Config.config_registers_lista_fecha_hoy'),
                'semana' => lang('Config.config_registers_lista_fecha_semana'),
                'mes'    => lang('Config.config_registers_lista_fecha_mes'),
                'todos'  => lang('Config.config_registers_lista_fecha_todos'),
            ];
            ?>
            <?= form_dropdown(
                'registers_lista_fecha_default',
                $listaFechaOpts,
                $listaFechaDefault,
                'id="registers_lista_fecha_default" class="form-select" style="max-width: 28rem;" autocomplete="off"'
            ) ?>
            <small class="text-muted d-block mt-1"><?= lang('Config.config_registers_lista_fecha_default_help') ?></small>
        </div>
        <div class="mb-3">
            <label for="order_barcode_print_layout" class="form-label"><?= lang('Config.config_order_barcode_print_layout') ?></label>
            <?php
            $barcodeLayout = strtolower((string) ($config['order_barcode_print_layout'] ?? 'vertical'));
            if ($barcodeLayout !== 'horizontal') {
                $barcodeLayout = 'vertical';
            }
            $layoutOpts = [
                'vertical'   => lang('Config.config_order_barcode_layout_vertical'),
                'horizontal' => lang('Config.config_order_barcode_layout_horizontal'),
            ];
            ?>
            <?= form_dropdown('order_barcode_print_layout', $layoutOpts, $barcodeLayout, 'id="order_barcode_print_layout" class="form-select" style="max-width: 22rem;" autocomplete="off"') ?>
            <small class="text-muted d-block mt-1"><?= lang('Config.config_order_barcode_print_layout_help') ?></small>
        </div>
        <div class="mb-3">
            <label for="order_barcode_print_size_percent" class="form-label"><?= lang('Config.config_order_barcode_size_percent') ?></label>
            <?php
            $bcSize = (int) ($config['order_barcode_print_size_percent'] ?? 100);
            if ($bcSize < 1) {
                $bcSize = 100;
            }
            $bcSize = max(30, min(250, $bcSize));
            ?>
            <input type="number" name="order_barcode_print_size_percent" id="order_barcode_print_size_percent" class="form-control" style="max-width: 10rem;" min="30" max="250" step="1" value="<?= $bcSize ?>" autocomplete="off">
            <small class="text-muted d-block mt-1"><?= lang('Config.config_order_barcode_size_percent_help') ?></small>
        </div>
        <div class="mb-3">
            <label for="print_paper_size" class="form-label"><?= lang('Config.config_print_paper_size') ?></label>
            <?php
            $printPaper = strtolower((string) ($config['print_paper_size'] ?? 'letter'));
            if (! in_array($printPaper, ['letter', 'a4', 'legal', 'custom'], true)) {
                $printPaper = 'letter';
            }
            $printPaperOpts = [
                'letter' => lang('Config.config_print_paper_letter'),
                'a4'     => lang('Config.config_print_paper_a4'),
                'legal'  => lang('Config.config_print_paper_legal'),
                'custom' => lang('Config.config_print_paper_custom'),
            ];
            $ppW = (float) ($config['print_paper_width_mm'] ?? 210);
            $ppH = (float) ($config['print_paper_height_mm'] ?? 297);
            $ppW = max(50.0, min(999.0, $ppW > 0 ? $ppW : 210.0));
            $ppH = max(50.0, min(999.0, $ppH > 0 ? $ppH : 297.0));
            ?>
            <?= form_dropdown('print_paper_size', $printPaperOpts, $printPaper, 'id="print_paper_size" class="form-select" style="max-width: 16rem;" autocomplete="off"') ?>
            <div id="print_paper_custom_wrap" class="mt-2" style="display: none;">
                <div class="row g-2 align-items-end" style="max-width: 22rem;">
                    <div class="col-6">
                        <label for="print_paper_width_mm" class="form-label small mb-0"><?= lang('Config.config_print_paper_width_mm') ?></label>
                        <input type="number" name="print_paper_width_mm" id="print_paper_width_mm" class="form-control" min="50" max="999" step="0.1" value="<?= esc((string) $ppW) ?>" autocomplete="off">
                    </div>
                    <div class="col-6">
                        <label for="print_paper_height_mm" class="form-label small mb-0"><?= lang('Config.config_print_paper_height_mm') ?></label>
                        <input type="number" name="print_paper_height_mm" id="print_paper_height_mm" class="form-control" min="50" max="999" step="0.1" value="<?= esc((string) $ppH) ?>" autocomplete="off">
                    </div>
                </div>
                <small class="text-muted d-block mt-1"><?= lang('Config.config_print_paper_custom_dims_help') ?></small>
            </div>
            <small class="text-muted d-block mt-1"><?= lang('Config.config_print_paper_size_help') ?></small>
        </div>
        <div class="mb-3">
            <input type="hidden" name="pdf_order_sheet_header_enabled" value="0">
            <div class="form-check">
                <?= form_checkbox('pdf_order_sheet_header_enabled', '1', (($config['pdf_order_sheet_header_enabled'] ?? '0') === '1'), 'id="pdf_order_sheet_header_enabled" class="form-check-input" autocomplete="off"') ?>
                <?= form_label(lang('Config.config_pdf_order_sheet_header_enabled'), 'pdf_order_sheet_header_enabled', ['class' => 'form-check-label']) ?>
            </div>
            <small class="text-muted d-block mt-1"><?= lang('Config.config_pdf_order_sheet_header_enabled_help') ?></small>
        </div>
        <div class="mb-3">
            <input type="hidden" name="print_pagination_enabled" value="0">
            <div class="form-check">
                <?= form_checkbox('print_pagination_enabled', '1', (($config['print_pagination_enabled'] ?? '0') === '1'), 'id="print_pagination_enabled" class="form-check-input" autocomplete="off"') ?>
                <?= form_label(lang('Config.config_print_pagination_enabled'), 'print_pagination_enabled', ['class' => 'form-check-label']) ?>
            </div>
        </div>
        <div class="mb-3">
            <label for="print_pagination_position" class="form-label"><?= lang('Config.config_print_pagination_position') ?></label>
            <?php
            $printPos = strtolower((string) ($config['print_pagination_position'] ?? 'bottom-right'));
            $allowedPrintPos = ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'];
            if (!in_array($printPos, $allowedPrintPos, true)) {
                $printPos = 'bottom-right';
            }
            $printPosOpts = [
                'top-left'      => lang('Config.config_print_pagination_top_left'),
                'top-center'    => lang('Config.config_print_pagination_top_center'),
                'top-right'     => lang('Config.config_print_pagination_top_right'),
                'bottom-left'   => lang('Config.config_print_pagination_bottom_left'),
                'bottom-center' => lang('Config.config_print_pagination_bottom_center'),
                'bottom-right'  => lang('Config.config_print_pagination_bottom_right'),
            ];
            ?>
            <?= form_dropdown('print_pagination_position', $printPosOpts, $printPos, 'id="print_pagination_position" class="form-select" style="max-width: 16rem;" autocomplete="off"') ?>
            <small class="text-muted d-block mt-1"><?= lang('Config.config_print_pagination_position_help') ?></small>
        </div>
        <div class="mb-3">
            <input type="hidden" name="leyendas_enabled" value="0">
            <div class="form-check">
                <?= form_checkbox('leyendas_enabled', '1', (($config['leyendas_enabled'] ?? '0') === '1'), 'id="leyendas_enabled" class="form-check-input" autocomplete="off"') ?>
                <?= form_label('Habilitar leyendas en pruebas', 'leyendas_enabled', ['class' => 'form-check-label']) ?>
            </div>
            <small class="text-muted">Si se activa, se mostrará un campo de comentarios/leyendas al registrar resultados de pruebas.</small>
        </div>

        <hr class="my-3">
        <div class="mb-3">
            <label for="registro_folio_format" class="form-label fw-bold">Formato del número de orden (recepción)</label>
            <textarea name="registro_folio_format" id="registro_folio_format" class="form-control font-monospace" rows="2" maxlength="128" placeholder="Ej: LAB-%yyyy-%mm-%dd-%i"><?= esc($config['registro_folio_format'] ?? '') ?></textarea>
            <?php
            $folioPad = (int) ($config['registro_folio_counter_pad'] ?? 0);
            if ($folioPad < 0) {
                $folioPad = 0;
            }
            if ($folioPad > 6) {
                $folioPad = 6;
            }
            $folioReset = strtolower((string) ($config['registro_folio_counter_reset'] ?? 'auto'));
            if (! in_array($folioReset, ['auto', 'day', 'month', 'year', 'global'], true)) {
                $folioReset = 'auto';
            }
            ?>
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label for="registro_folio_counter_pad" class="form-label"><?= lang('Config.config_registro_folio_counter_pad') ?></label>
                    <?= form_dropdown('registro_folio_counter_pad', [
                        '0' => lang('Config.config_registro_folio_counter_pad_none'),
                        '2' => lang('Config.config_registro_folio_counter_pad_2'),
                    ], (string) ($folioPad === 2 ? 2 : 0), 'id="registro_folio_counter_pad" class="form-select" autocomplete="off"') ?>
                    <small class="text-muted d-block mt-1"><?= lang('Config.config_registro_folio_counter_pad_help') ?></small>
                </div>
                <div class="col-md-6">
                    <label for="registro_folio_counter_reset" class="form-label"><?= lang('Config.config_registro_folio_counter_reset') ?></label>
                    <?= form_dropdown('registro_folio_counter_reset', [
                        'auto'   => lang('Config.config_registro_folio_counter_reset_auto'),
                        'day'    => lang('Config.config_registro_folio_counter_reset_day'),
                        'month'  => lang('Config.config_registro_folio_counter_reset_month'),
                        'year'   => lang('Config.config_registro_folio_counter_reset_year'),
                        'global' => lang('Config.config_registro_folio_counter_reset_global'),
                    ], $folioReset, 'id="registro_folio_counter_reset" class="form-select" autocomplete="off"') ?>
                    <small class="text-muted d-block mt-1"><?= lang('Config.config_registro_folio_counter_reset_help') ?></small>
                </div>
            </div>
            <div class="small text-muted mt-2">
                <p class="mb-1">Deje vacío para usar solo el ID numérico interno del sistema (comportamiento anterior).</p>
                <p class="mb-1"><strong>Variables</strong> (respete mayúsculas):</p>
                <ul class="mb-1 ps-3">
                    <li><code>%yyyy</code> — año cuatro dígitos (2026)</li>
                    <li><code>%yy</code> — año dos dígitos (26)</li>
                    <li><code>%mm</code> — mes con cero (01–12)</li>
                    <li><code>%m</code> — mes sin cero (1–12)</li>
                    <li><code>%dd</code> — día con cero (01–31)</li>
                    <li><code>%d</code> — día sin cero (1–31)</li>
                    <li><code>%i</code> — <strong>obligatorio</strong> si usa formato: contador incremental. El formato (1, 2, 3 o 01, 02, 03) y cuándo se reinicia se configuran en las opciones de arriba.</li>
                    <li><code>%%</code> — un símbolo <code>%</code> literal</li>
                </ul>
                <p class="mb-0">Puede mezclar <strong>texto fijo</strong> (letras, guiones, etc.) con las variables. Ejemplo: <code>ORD-%yyyy-%mm-%dd-%i</code> o <code>%m%dd%yyyy-%i</code>. Máximo 128 caracteres en la plantilla; el número generado no puede superar 64 caracteres.</p>
            </div>
        </div>
        
        <button type="submit" id="config_save_btn" name="config_save_btn" class="btn btn-primary"><?= lang('Config.config_save_btn') ?></button>
        <?= form_close() ?>
            </div>
        </div>
    </div>

    <?= view('config/tab_lab_validacion', [
        'activeTab'      => $activeTab,
        'lab_validators' => $lab_validators ?? [],
        'lab_approvers'  => $lab_approvers ?? [],
    ]) ?>

    <?= view('config/tab_estilo', ['config' => $config, 'activeTab' => $activeTab, 'theme_palette' => $theme_palette ?? []]) ?>

    <?= view('config/tab_comprobante', ['config' => $config, 'activeTab' => $activeTab]) ?>

    <!-- Pestaña: Tenants -->
    <?php if (($can_manage_tenants ?? false)): ?>
    <div class="tab-pane fade <?= $activeTab === 'tenants' ? 'show active' : '' ?>" id="tab-tenants" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fa-solid fa-building me-2"></i>Multi-tenant (base de datos por cliente)</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Cada tenant representa un cliente con su propia base de datos. Al guardar, se publica el mapa de conexiones para uso inmediato del runtime.
                </p>
                <p class="small text-secondary mb-3 border-start border-3 border-secondary ps-2">
                    <strong>Superusuario sin auditoría:</strong> en cada fila puede abrir el laboratorio de ese tenant;
                    las acciones <em>no</em> generan registros en la tabla de auditoría del tenant. No necesita la contraseña del administrador del cliente: si su usuario no está en esa base de datos, entrará con el usuario <code>admin</code> u otro empleado activo de soporte.
                    Si el laboratorio usa otro dominio (ej. <code>http://quantum.local</code>), indique la <strong>URL pública del tenant</strong> en el formulario de abajo o configure en <code>.env</code> <code>tenancy.publicUrlTemplate=http://{tenant_key}.local</code>: se generará un enlace de un solo uso (válido 2 minutos).
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                    <a href="<?= site_url('config/backupTenants') ?>"
                       class="btn btn-outline-primary"
                       onclick="return uiConfirmLink(this, 'Se descargará un ZIP con respaldos SQL de todos los tenants activos (incluido el default). ¿Continuar?');">
                        <i class="fa-solid fa-download me-1"></i>Respaldar todos los tenants
                    </a>
                    <?= form_open(site_url('config/migrateCurrentDatabase'), ['class' => 'd-inline']) ?>
                    <button type="submit" class="btn btn-outline-success" title="Migraciones pendientes en la BD del laboratorio en uso (sin recrear la base)">
                        <i class="fa-solid fa-database me-1"></i>Migrar BD actual
                    </button>
                    <?= form_close() ?>
                    <?= form_open(site_url('config/migrateAllTenants'), ['class' => 'd-inline']) ?>
                    <button type="submit" class="btn btn-outline-success"
                            onclick="return confirm('¿Aplicar migraciones pendientes en todos los tenants activos?');"
                            title="Migraciones pendientes en cada tenant activo">
                        <i class="fa-solid fa-layer-group me-1"></i>Migrar todos los tenants
                    </button>
                    <?= form_close() ?>
                </div>
                <p class="text-muted small mb-3">
                    El botón <i class="fa-solid fa-server"></i> de cada fila <strong>aprovisiona</strong> (crea la BD si falta) y ejecuta <strong>todas las migraciones pendientes</strong>
                    (incluida <code>AddHomeModule</code>: permiso de dashboard <code>/home</code>).
                    Use <strong>Migrar BD actual</strong> para el laboratorio que está usando ahora, o <strong>Migrar todos los tenants</strong> tras desplegar cambios en el código.
                </p>

                <?php
                $tbs = $tenant_backup_schedule ?? [];
                $tbsTzId = (string) ($tenant_backup_timezone_id ?? 'UTC');
                ?>
                <div class="border rounded p-3 mb-4 bg-light">
                    <h6 class="mb-2 d-flex flex-wrap align-items-center gap-2">
                        <span class="me-1"><i class="fa-solid fa-clock me-1"></i>Respaldos automáticos (programados)</span>
                        <span class="d-inline-flex align-items-center gap-2 ms-md-auto">
                            <span class="small text-muted text-nowrap">Hora del laboratorio</span>
                            <span class="badge bg-dark font-monospace px-2 py-2 fs-6"
                                id="tbs-lab-clock"
                                data-timezone="<?= esc($tbsTzId, 'attr') ?>"
                                title="Zona horaria (configuración del sistema): <?= esc($tbsTzId, 'attr') ?>. La hora programada del respaldo se interpreta en esta zona.">
                                --:--:--
                            </span>
                        </span>
                    </h6>
                    <div class="alert alert-warning small py-2 mb-3" role="alert">
                        <strong>Importante:</strong> guardar aquí solo define la hora; <strong>no ejecuta nada por sí solo</strong>.
                        Tiene que existir algo que llame al sistema cada pocos minutos (idealmente cada 5).
                    </div>
                    <div class="alert alert-info small py-2 mb-3" role="alert">
                        <strong>cPanel / hosting (Namecheap, etc.):</strong> en <em>cPanel → Cron Jobs</em> programe cada 5 minutos un comando como:
                        <br><code class="user-select-all d-inline-block mt-1">wget -q -O - "<?= esc(rtrim((string) ($tenant_backup_cron_url ?? ''), '/')) ?>?token=SU_CLAVE_SECRETA"</code>
                        <br>Sustituya <code>SU_CLAVE_SECRETA</code> por el valor de <code>tenantBackup.cronKey</code> en el archivo <code>.env</code> del servidor (cadena larga y aleatoria; si está vacío, la URL no hace nada y responde 404).
                        <?php if (! empty($tenant_backup_cron_ready)): ?>
                            <br><span class="text-success">Token cron configurado en el servidor.</span>
                        <?php else: ?>
                            <br><span class="text-danger">Aún no hay <code>tenantBackup.cronKey</code> en <code>.env</code>; añádalo y suba el archivo.</span>
                        <?php endif; ?>
                        <br>También puede usar un servicio externo gratuito (p. ej. cron-job.org) que haga una petición GET a esa misma URL cada 5 minutos.
                        <br><strong>Sin cron en cPanel:</strong> en el <code>.env</code> ponga <code>tenantBackup.tickOnWeb = true</code> y mantenga <code>tenantBackup.cronKey</code>. Cuando un usuario con permiso de <strong>Configuración</strong> haga GET, al final del request (y con <code>fastcgi_finish_request</code> si existe) el servidor hará una petición HTTP interna <strong>equivalente a su curl</strong> hacia <code>/cron/tenant-backup-schedule?token=…</code>; solo si <strong>toca según la frecuencia y hora</strong> guardadas en Configuración. Intervalo mínimo entre comprobaciones: <code>tenantBackup.tickIntervalSeconds</code> (p. ej. 120–300). Si el host no resuelve bien la URL pública, defina <code>tenantBackup.selfTriggerBaseUrl = https://su-dominio.com</code> (sin barra final). Para ejecutar el respaldo en PHP sin HTTP: <code>tenantBackup.tickDirectRunner = true</code>.
                        <br><em>Límite:</em> si a la hora programada nadie usa la web, no habrá disparo; para eso sigue siendo mejor cron externo o cPanel.
                        <br><strong>En su PC local</strong> no hace falta PHP: el respaldo se genera en el <strong>servidor</strong>. Para copiar los ZIP use el <em>Administrador de archivos</em> de cPanel, <strong>FTP/SFTP</strong> o <strong>WinSCP</strong> en <code>writable/tenant_backups_scheduled/</code>.
                        <br><strong>Servidor con SSH:</strong> alternativa <code>php spark lab:tenant-backup-schedule</code> desde la raíz del proyecto.
                        <?php $tbsTickOn = filter_var((string) env('tenantBackup.tickOnWeb', 'false'), FILTER_VALIDATE_BOOLEAN); ?>
                        <br><span class="<?= $tbsTickOn ? 'text-success' : 'text-secondary' ?>">Comprobación vía navegación (tickOnWeb): <strong><?= $tbsTickOn ? 'activada' : 'desactivada' ?></strong>.</span>
                    </div>
                    <p class="small text-muted mb-3">
                        Al activar por primera vez se guarda la hora actual como referencia para la <strong>próxima</strong> franja.
                        El campo <strong>Hora (24 h)</strong> usa la misma zona que el reloj (pestaña «Configuración del sistema» → «Zona horaria»).
                    </p>
                    <?= form_open(site_url('config/saveTenantBackupSchedule'), ['class' => 'row g-3 align-items-end']) ?>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="tenant_backup_schedule_enabled" value="1" class="form-check-input" id="tbs_enabled"
                                <?= (($tbs[\App\Services\TenantBackupScheduleService::$keyEnabled] ?? '0') === '1') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tbs_enabled">Activar respaldos automáticos</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="tbs_freq">Frecuencia</label>
                        <select name="tenant_backup_schedule_frequency" id="tbs_freq" class="form-select">
                            <?php
                            $f = (string) ($tbs[\App\Services\TenantBackupScheduleService::$keyFrequency] ?? 'daily');
                            ?>
                            <option value="<?= esc(\App\Services\TenantBackupScheduleService::$freqDaily) ?>" <?= $f === \App\Services\TenantBackupScheduleService::$freqDaily ? 'selected' : '' ?>>Cada día</option>
                            <option value="<?= esc(\App\Services\TenantBackupScheduleService::$freqWeekly) ?>" <?= $f === \App\Services\TenantBackupScheduleService::$freqWeekly ? 'selected' : '' ?>>Cada semana</option>
                            <option value="<?= esc(\App\Services\TenantBackupScheduleService::$freqMonthly) ?>" <?= $f === \App\Services\TenantBackupScheduleService::$freqMonthly ? 'selected' : '' ?>>Cada mes</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="tbs_time">Hora (24 h)</label>
                        <input type="time" name="tenant_backup_schedule_time" id="tbs_time" class="form-control" required
                            value="<?= esc((string) ($tbs[\App\Services\TenantBackupScheduleService::$keyTime] ?? '02:30')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="tbs_wd">Día (solo semanal)</label>
                        <?php $wd = (int) ($tbs[\App\Services\TenantBackupScheduleService::$keyWeekday] ?? 1); ?>
                        <select name="tenant_backup_schedule_weekday" id="tbs_wd" class="form-select">
                            <?php
                            $days = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
                            foreach ($days as $k => $label): ?>
                            <option value="<?= (int) $k ?>" <?= $wd === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Mismo criterio que PHP: 0 = domingo.</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="tbs_md">Día del mes</label>
                        <?php $md = (int) ($tbs[\App\Services\TenantBackupScheduleService::$keyMonthday] ?? 1); ?>
                        <input type="number" name="tenant_backup_schedule_monthday" id="tbs_md" class="form-control" min="1" max="28" value="<?= max(1, min(28, $md)) ?>">
                        <small class="text-muted">Solo mensual (1–28).</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="tbs_keep">Conservar últimos</label>
                        <input type="number" name="tenant_backup_schedule_keep" id="tbs_keep" class="form-control" min="1" max="100" value="<?= (int) ($tbs[\App\Services\TenantBackupScheduleService::$keyKeep] ?? 14) ?>">
                        <small class="text-muted">Archivos ZIP en disco.</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Guardar programación</button>
                    </div>
                    <div class="col-12">
                        <p class="small mb-1"><strong>Última ejecución automática:</strong>
                            <?php $lr = trim((string) ($tbs[\App\Services\TenantBackupScheduleService::$keyLastRun] ?? '')); ?>
                            <?= $lr !== '' ? esc($lr) : '<span class="text-muted">—</span>' ?>
                        </p>
                        <p class="small mb-0"><strong>Comando (Programador de tareas de Windows o cron):</strong><br>
                            <code class="user-select-all">cd /d <?= esc(rtrim(ROOTPATH, '/\\')) ?> &amp;&amp; php spark lab:tenant-backup-schedule</code><br>
                            <span class="text-muted">Prueba manual forzada: <code>php spark lab:tenant-backup-schedule --force</code>. Los archivos quedan en <code>writable/tenant_backups_scheduled/</code>.</span>
                        </p>
                    </div>
                    <?= form_close() ?>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tenant Key</th>
                                <th>Nombre</th>
                                <th>Host:Puerto</th>
                                <th>DB</th>
                                <th>Usuario</th>
                                <th>Estado</th>
                                <th>Default</th>
                                <th class="text-center" style="width: 220px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($tenants ?? []) as $t): ?>
                            <tr>
                                <td><code><?= esc($t['tenant_key'] ?? '') ?></code></td>
                                <td><?= esc($t['tenant_name'] ?? '') ?></td>
                                <td><?= esc(($t['db_host'] ?? 'localhost') . ':' . ((int) ($t['db_port'] ?? 3306))) ?></td>
                                <td><?= esc($t['db_name'] ?? '') ?></td>
                                <td><?= esc($t['db_user'] ?? '') ?></td>
                                <td>
                                    <?php if ((int) ($t['is_active'] ?? 0) === 1): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int) ($t['is_default'] ?? 0) === 1): ?>
                                        <span class="badge bg-primary">Sí</span>
                                    <?php else: ?>
                                        <span class="text-muted">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= form_open(site_url('config/provisiontenant/' . (int) ($t['id'] ?? 0)), ['class' => 'd-inline']) ?>
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Crear BD (si no existe) + migraciones pendientes (módulos, dashboard home, etc.)"><i class="fa-solid fa-server"></i></button>
                                    <?= form_close() ?>
                                    <?php if ((int) ($t['is_active'] ?? 0) === 1): ?>
                                    <a href="<?= site_url('config/ghostEnterTenant/' . (int) ($t['id'] ?? 0)) ?>"
                                       class="btn btn-sm btn-outline-dark"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       title="Abre el laboratorio de este tenant en una pestaña nueva (sin registro en auditoría del tenant)"><i class="fa-solid fa-user-secret"></i></a>
                                    <?php endif; ?>
                                    <a href="<?= site_url('config?tab=tenants&tenant_edit=' . (int) ($t['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                    <a href="<?= site_url('config/deletetenant/' . (int) ($t['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return uiConfirmLink(this, '¿Eliminar este tenant?');"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($tenants)): ?>
                            <tr>
                                <td colspan="8" class="text-muted text-center">No hay tenants configurados todavía.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php $tenantEdit = $tenant_edit_data ?? []; ?>
                <h6 class="mb-3"><?= empty($tenantEdit) ? 'Agregar tenant' : 'Editar tenant' ?></h6>
                <?= form_open(site_url('config/savetenant'), ['id' => 'tenant_form', 'class' => 'border rounded p-3']) ?>
                <input type="hidden" name="tenant_id" value="<?= (int) ($tenantEdit['id'] ?? 0) ?>">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tenant key *</label>
                        <input type="text" name="tenant_key" class="form-control" required maxlength="64" placeholder="acme" value="<?= esc($tenantEdit['tenant_key'] ?? '') ?>">
                        <small class="text-muted">Identificador técnico (subdominio/header).</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Nombre cliente *</label>
                        <input type="text" name="tenant_name" class="form-control" required maxlength="120" placeholder="Laboratorio ACME" value="<?= esc($tenantEdit['tenant_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">URL pública del tenant (opcional)</label>
                        <input type="url" name="public_base_url" class="form-control" maxlength="255" placeholder="http://quantum.local" value="<?= esc($tenantEdit['public_base_url'] ?? '') ?>">
                        <small class="text-muted">Para acceder desde otro vhost con el botón de superusuario. Sin barra final. Requiere migración <code>AddPublicBaseUrlToTenantConfigs</code> y carpeta <code>writable/tenant_handoff</code> escribible.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">DB host *</label>
                        <input type="text" name="db_host" class="form-control" required value="<?= esc($tenantEdit['db_host'] ?? 'localhost') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">DB puerto *</label>
                        <input type="number" name="db_port" class="form-control" min="1" max="65535" value="<?= esc((string) ($tenantEdit['db_port'] ?? '3306')) ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nombre de base *</label>
                        <input type="text" name="db_name" class="form-control" required value="<?= esc($tenantEdit['db_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Usuario DB *</label>
                        <input type="text" name="db_user" class="form-control" required value="<?= esc($tenantEdit['db_user'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Contraseña DB</label>
                        <input type="text" name="db_pass" class="form-control" value="<?= esc($tenantEdit['db_pass'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Prefijo tablas</label>
                        <input type="text" name="db_prefix" class="form-control" value="<?= esc($tenantEdit['db_prefix'] ?? 'dom_') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active_tenant" <?= ((int) ($tenantEdit['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active_tenant">Tenant activo</label>
                    </div>
                    <input type="hidden" name="is_default" value="0">
                    <div class="form-check mt-1">
                        <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default_tenant" <?= ((int) ($tenantEdit['is_default'] ?? 0) === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_default_tenant">Usar como tenant por defecto</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-info text-white"><?= empty($tenantEdit) ? 'Guardar tenant' : 'Actualizar tenant' ?></button>
                <?php if (!empty($tenantEdit)): ?>
                <a href="<?= site_url('config?tab=tenants') ?>" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (($can_manage_tenants ?? false)): ?>
    <?php $thb = $tenant_home_broadcast_form ?? []; ?>
    <div class="tab-pane fade <?= $activeTab === 'tenant_home_broadcast' ? 'show active' : '' ?>" id="tab-tenant-home-broadcast" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fa-solid fa-bullhorn me-2"></i>Aviso en el dashboard de laboratorios cliente</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Solo el tenant principal puede configurar este aviso. Los laboratorios cliente lo verán en la parte superior de <strong>/home</strong> (dashboard).
                    Puede incluir imagen, título, mensaje o los tres. Use <strong>Desactivar aviso</strong> para ocultarlo sin borrar el contenido.
                </p>
                <?= form_open_multipart(site_url('config/saveTenantHomeBroadcast'), ['class' => 'row g-3', 'id' => 'form-tenant-home-broadcast']) ?>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="tenant_home_broadcast_enabled"
                            name="<?= esc(\App\Services\TenantHomeBroadcastService::KEY_ENABLED) ?>" value="1"
                            <?= (($thb['enabled'] ?? '0') === '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="tenant_home_broadcast_enabled">Mostrar aviso en el dashboard de los laboratorios cliente</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="tenant_home_broadcast_disable"
                            name="tenant_home_broadcast_disable" value="1"
                            <?= ! empty($thb['is_inactive']) ? 'checked' : '' ?>>
                        <label class="form-check-label text-muted" for="tenant_home_broadcast_disable">Desactivar aviso (ocultar en todos los laboratorios cliente)</label>
                    </div>
                </div>
                <script>
                (function () {
                    var show = document.getElementById('tenant_home_broadcast_enabled');
                    var hide = document.getElementById('tenant_home_broadcast_disable');
                    if (!show || !hide) return;
                    show.addEventListener('change', function () { if (show.checked) hide.checked = false; });
                    hide.addEventListener('change', function () { if (hide.checked) show.checked = false; });
                })();
                </script>
                <div class="col-md-6">
                    <label class="form-label" for="tenant_home_broadcast_title">Título (opcional)</label>
                    <input type="text" class="form-control" id="tenant_home_broadcast_title"
                        name="<?= esc(\App\Services\TenantHomeBroadcastService::KEY_TITLE) ?>"
                        maxlength="200" value="<?= esc($thb['title'] ?? '') ?>" placeholder="Ej. Mantenimiento programado">
                </div>
                <div class="col-12">
                    <label class="form-label" for="tenant_home_broadcast_message">Mensaje (opcional)</label>
                    <textarea class="form-control" id="tenant_home_broadcast_message" rows="4" maxlength="4000"
                        name="<?= esc(\App\Services\TenantHomeBroadcastService::KEY_MESSAGE) ?>"
                        placeholder="Texto visible para todos los usuarios del laboratorio en su inicio."><?= esc($thb['message'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tenant_home_broadcast_image_file">Imagen (opcional)</label>
                    <input type="file" class="form-control" id="tenant_home_broadcast_image_file"
                        name="tenant_home_broadcast_image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">JPG, PNG, GIF o WebP. Máximo 2 MB.</div>
                </div>
                <?php if (! empty($thb['image_url'])): ?>
                <div class="col-12">
                    <p class="small text-muted mb-1">Imagen actual:</p>
                    <img src="<?= esc($thb['image_url'], 'attr') ?>" alt="" class="img-thumbnail mb-2" style="max-height: 160px;">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="tenant_home_broadcast_remove_image"
                            name="tenant_home_broadcast_remove_image" value="1">
                        <label class="form-check-label" for="tenant_home_broadcast_remove_image">Quitar imagen actual</label>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Guardar aviso</button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $activeTab === 'tenant_subscriptions' ? 'show active' : '' ?>" id="tab-tenant-subscriptions" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Pagos y vigencia por laboratorio cliente</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Registre cada pago con el período de vigencia (desde / hasta). Puede <strong>generar un PDF automático</strong> o <strong>adjuntar su propio PDF</strong> (recibo/factura que ya tenga guardado).
                    Los laboratorios cliente solo pueden <strong>ver y descargar</strong> el comprobante desde el menú «Suscripción y comprobantes».
                    Si la fecha fin del último pago está dentro del plazo de aviso configurado abajo (por defecto 4 días), verán un aviso en la parte superior; <strong>si la vigencia ya venció, el sistema bloquea el acceso</strong> hasta registrar un nuevo período pagado.
                </p>

                <div class="border rounded p-3 mb-4 bg-light">
                    <h6 class="mb-2"><i class="fa-solid fa-bell me-1"></i> Aviso previo al vencimiento</h6>
                    <p class="small text-muted mb-2">Cantidad de días antes del fin de vigencia del último pago en que el laboratorio cliente verá la alerta (y el tenant principal verá el listado en el dashboard). Valores entre 1 y 90.</p>
                    <?= form_open(site_url('config/saveTenantSubscriptionAlertDays'), ['class' => 'row g-2 align-items-end']) ?>
                    <div class="col-auto">
                        <label class="form-label mb-0" for="dias_alerta_suscripcion_tenant">Días de aviso</label>
                        <input type="number" name="dias_alerta_suscripcion_tenant" id="dias_alerta_suscripcion_tenant" class="form-control" min="1" max="90" required value="<?= (int) ($tenant_subscription_alert_days_form ?? 4) ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-primary">Guardar</button>
                    </div>
                    <?= form_close() ?>
                </div>

                <?php $subRes = $tenant_subscription_resumen_admin ?? []; ?>
                <?php if (! empty($subRes)): ?>
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
                    <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                    <div class="small">
                        <strong>Atención:</strong> hay laboratorios cliente con el último pago por vencer o ya vencido (según los <?= (int) ($tenant_subscription_alert_days_form ?? 4) ?> día(s) de aviso). Revise el historial y registre la renovación.
                        <div class="table-responsive mt-2 mb-0">
                            <table class="table table-sm table-bordered bg-white mb-0">
                                <thead class="table-light"><tr><th>Laboratorio</th><th>Fin vigencia</th><th>Estado</th></tr></thead>
                                <tbody>
                                    <?php foreach ($subRes as $sr): ?>
                                    <tr class="<?= (($sr['estado'] ?? '') === 'vencido') ? 'table-danger' : '' ?>">
                                        <td><?= esc($sr['tenant_name'] ?? '') ?></td>
                                        <td><?= ! empty($sr['period_end']) ? lab_date((string) $sr['period_end']) : '-' ?></td>
                                        <td><?= ($sr['estado'] ?? '') === 'vencido' ? '<span class="badge bg-danger">Vencido</span>' : '<span class="badge bg-warning text-dark">' . (int) ($sr['days_left'] ?? 0) . ' día(s)</span>' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <h6 class="mb-2">Registrar pago</h6>
                <?= form_open_multipart(site_url('config/saveTenantSubscriptionPayment'), ['class' => 'border rounded p-3 mb-4']) ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Laboratorio (tenant) *</label>
                        <select name="tenant_config_id" class="form-select" required>
                            <option value="">— Seleccione —</option>
                            <?php foreach (($billable_tenants ?? []) as $bt): ?>
                            <option value="<?= (int) ($bt['id'] ?? 0) ?>"><?= esc(($bt['tenant_name'] ?? '') . ' (' . ($bt['tenant_key'] ?? '') . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="tenant_sub_period_start">Vigencia desde *</label>
                        <input type="text" name="period_start" id="tenant_sub_period_start" class="form-control flatpickr-input" required value="<?= esc(date('Y-m-d')) ?>" autocomplete="off" placeholder="Desde">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="tenant_sub_period_end">Vigencia hasta *</label>
                        <input type="text" name="period_end" id="tenant_sub_period_end" class="form-control flatpickr-input" required autocomplete="off" placeholder="Hasta">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Monto *</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0" required value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Moneda</label>
                        <input type="text" name="currency" class="form-control" maxlength="8" value="Bs" placeholder="Bs">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notas (opcional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Referencia de transferencia, factura, etc."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="tenant_sub_voucher_pdf">PDF del comprobante (opcional)</label>
                        <input type="file" name="voucher_pdf" id="tenant_sub_voucher_pdf" class="form-control" accept="application/pdf,.pdf">
                        <small class="text-muted">Si elige un archivo, se guardará ese PDF y <strong>no</strong> se generará el comprobante automático. Máx. 15 MB.</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-3"><i class="fa-solid fa-plus me-1"></i> Guardar pago</button>
                <?= form_close() ?>

                <h6 class="mb-2">Historial</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Laboratorio</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Monto</th>
                                <th style="min-width:200px">Comprobante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($subscription_payments ?? []) as $sp): ?>
                            <tr>
                                <td><?= (int) ($sp['id'] ?? 0) ?></td>
                                <td><?= esc($sp['_tenant_name'] ?? '') ?></td>
                                <td><?= esc($sp['period_start'] ?? '') ?></td>
                                <td><?= esc($sp['period_end'] ?? '') ?></td>
                                <td><?= esc(number_format((float) ($sp['amount'] ?? 0), 2, ',', '.')) ?> <?= esc($sp['currency'] ?? '') ?></td>
                                <td class="small">
                                    <?php $spId = (int) ($sp['id'] ?? 0); ?>
                                    <?php if (! empty($sp['voucher_filename'])): ?>
                                    <a class="btn btn-sm btn-outline-primary mb-2 d-inline-block" href="<?= site_url('config/tenantSubscriptionVoucher/' . $spId) ?>" title="Descargar"><i class="fa-solid fa-download"></i></a>
                                    <?php endif; ?>
                                    <?= form_open_multipart(site_url('config/uploadTenantSubscriptionVoucher/' . $spId), ['class' => 'tenant-sub-voucher-upload']) ?>
                                    <input type="file" name="voucher_pdf" class="form-control form-control-sm mb-1" accept="application/pdf,.pdf" required>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"><?= ! empty($sp['voucher_filename']) ? 'Reemplazar PDF' : 'Subir PDF' ?></button>
                                    <?= form_close() ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($subscription_payments)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No hay pagos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pestaña: Sesiones activas -->
    <div class="tab-pane fade <?= $activeTab === 'sesiones' ? 'show active' : '' ?>" id="tab-sesiones" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fa-solid fa-user-shield me-2"></i>Sesiones activas</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-1">Administre sesiones abiertas en otros dispositivos. Su sesión actual no se puede cerrar desde aquí.</p>
                <p class="text-muted small"><strong>Nota:</strong> cada fila es un ID de sesión en el servidor; el texto bajo la fecha es <em>cuándo fue la última petición</em> para ese ID (no indica si el usuario está deshabilitado). Duplicados con horas distintas solían ser filas viejas al rotar el ID de sesión; ahora se elimina la fila anterior automáticamente. Puede borrar restos antiguos con «Cerrar» o «Cerrar todas».</p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" id="btn_close_all_sessions" class="btn btn-danger">
                        <i class="fa-solid fa-door-open me-1"></i> Cerrar todas las sesiones
                    </button>
                    <button type="button" id="btn_refresh_sessions" class="btn btn-outline-primary">
                        <i class="fa-solid fa-rotate me-1"></i> Actualizar sesiones activas
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle" id="active_sessions_table">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Dispositivo/Navegador</th>
                                <th>IP</th>
                                <th>Última actividad</th>
                                <th>Estado</th>
                                <th class="text-center" style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-muted text-center">Cargando sesiones...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestaña: Grupos de población (por edad) -->
    <div class="tab-pane fade <?= $activeTab === 'poblacion' ? 'show active' : '' ?>" id="tab-poblacion" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fa-solid fa-people-group me-2"></i>Grupos de población (por edad)</h5>
            </div>
            <div class="card-body">
        <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered">
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
                        <form method="get" action="<?= esc(site_url('config')) ?>" class="d-inline">
                        <input type="hidden" name="editar" value="<?= (int)($p['id_poblacion'] ?? 0) ?>">
                        <input type="hidden" name="tab" value="poblacion">
                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        </form>
                        <a href="<?= site_url('config/deletepoblacion/' . (int)($p['id_poblacion'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return uiConfirmLink(this, '¿Eliminar este grupo de población?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($poblaciones)): ?>
                <tr><td colspan="4" class="text-muted">No hay grupos definidos. Agregue uno abajo.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <h6 class="mb-3" id="form_poblacion"><?= ($editar_poblacion ?? -1) >= 0 ? 'Editar grupo' : 'Agregar grupo' ?></h6>
        <?= form_open('config/savepoblacion', ['class' => 'border p-3 rounded', 'id' => 'form_poblacion']) ?>
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
                <a href="<?= site_url('config?tab=poblacion') ?>" class="btn btn-secondary btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>
        </div>
        <small class="text-muted">Ejemplos: Recién nacido (0-28 días), Lactante (29-330 días ≈ 11 meses), Niño pequeño (1-5 años), Adulto mayor (mín 60, máx vacío, años)</small>
        <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Pestaña: Sobres -->
    <div class="tab-pane fade <?= $activeTab === 'sobres' ? 'show active' : '' ?>" id="tab-sobres" role="tabpanel">
        <?= view('config/tab_sobres', [
            'envelope_templates'          => $envelope_templates ?? [],
            'active_envelope_template_id' => $active_envelope_template_id ?? 0,
            'print_envelope_template_id'  => $print_envelope_template_id ?? 0,
            'envelope_db_error'           => $envelope_db_error ?? null,
        ]) ?>
    </div>

    <!-- Pestaña: WhatsApp -->
    <div class="tab-pane fade <?= $activeTab === 'whatsapp' ? 'show active' : '' ?>" id="tab-whatsapp" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fa-brands fa-whatsapp me-2"></i>Configuración de WhatsApp Business API</h5>
            </div>
            <div class="card-body">
                <?= form_open(site_url('config/saveWhatsapp'), ['id' => 'whatsapp_form']) ?>
                <div class="mb-4">
                    <label for="whatsapp_country_code" class="form-label fw-bold">Prefijo telefónico del país</label>
                    <div class="input-group" style="max-width: 14rem;">
                        <span class="input-group-text">+</span>
                        <input type="text" name="whatsapp_country_code" id="whatsapp_country_code" class="form-control" value="<?= esc($config['whatsapp_country_code'] ?? '591') ?>" placeholder="591" inputmode="numeric" pattern="[0-9]{1,4}" maxlength="4" autocomplete="off" required>
                    </div>
                    <small class="text-muted d-block mt-1">Solo dígitos, sin el signo +. Se aplica al botón de WhatsApp en <strong>Clientes</strong> y <strong>Médicos</strong> cuando el teléfono es local (≤9 dígitos), y al enviar resultados por API. Ejemplos: Bolivia <code>591</code>, Honduras <code>504</code>, México <code>52</code>.</small>
                </div>
                <div class="mb-4">
                    <label for="whatsapp_provider" class="form-label fw-bold">Proveedor</label>
                    <select name="whatsapp_provider" id="whatsapp_provider" class="form-select">
                        <option value="meta" <?= ($config['whatsapp_provider'] ?? 'meta') === 'meta' ? 'selected' : '' ?>>Meta Cloud API (recomendado)</option>
                        <option value="twilio" <?= ($config['whatsapp_provider'] ?? '') === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                    </select>
                    <small class="text-muted">Meta Cloud API es la API oficial de WhatsApp Business.</small>
                </div>

                <!-- Bloque Meta Cloud API -->
                <div id="wa-meta-block" class="mb-4" style="display:<?= ($config['whatsapp_provider'] ?? 'meta') === 'meta' ? 'block' : 'none' ?>;">
                    <p class="text-muted small mb-3"><a href="https://developers.facebook.com/docs/whatsapp/cloud-api" target="_blank" rel="noopener">Documentación Meta Cloud API</a></p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp_meta_phone_id" class="form-label">Phone Number ID</label>
                            <input type="text" name="whatsapp_meta_phone_id" id="whatsapp_meta_phone_id" class="form-control" value="<?= esc($config['whatsapp_meta_phone_id'] ?? '') ?>" placeholder="123456789012345" autocomplete="off">
                            <small class="text-muted">En Meta for Developers → WhatsApp → API Setup</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp_meta_token" class="form-label">Access Token (permanente)</label>
                            <input type="password" name="whatsapp_meta_token" id="whatsapp_meta_token" class="form-control" value="<?= esc($config['whatsapp_meta_token'] ?? '') ?>" placeholder="EAAxxxx..." autocomplete="off">
                            <small class="text-muted">Token del System User o de la App</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp_meta_template" class="form-label">Plantilla (primer contacto)</label>
                            <input type="text" name="whatsapp_meta_template" id="whatsapp_meta_template" class="form-control" value="<?= esc($config['whatsapp_meta_template'] ?? '') ?>" placeholder="resultados_laboratorio" autocomplete="off">
                            <small class="text-muted">Plantilla aprobada con header=document y body={{1}}. Opcional si hay ventana 24h.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp_meta_lang" class="form-label">Idioma plantilla</label>
                            <input type="text" name="whatsapp_meta_lang" id="whatsapp_meta_lang" class="form-control" value="<?= esc($config['whatsapp_meta_lang'] ?? 'es') ?>" placeholder="es" autocomplete="off">
                        </div>
                    </div>
                    <div class="alert alert-info py-2 small">
                        <strong>Ventana 24h:</strong> Si el destinatario escribió recientemente, se envía directo. Si no, use una plantilla aprobada en Meta Business Manager (header=documento dinámico, body con {{1}}).
                    </div>
                </div>

                <!-- Bloque Twilio -->
                <div id="wa-twilio-block" class="mb-4" style="display:<?= ($config['whatsapp_provider'] ?? '') === 'twilio' ? 'block' : 'none' ?>;">
                    <p class="text-muted small mb-3"><a href="https://www.twilio.com/docs/whatsapp" target="_blank" rel="noopener">Documentación Twilio</a></p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp_twilio_account_sid" class="form-label">Account SID</label>
                            <input type="text" name="whatsapp_twilio_account_sid" id="whatsapp_twilio_account_sid" class="form-control" value="<?= esc($config['whatsapp_twilio_account_sid'] ?? '') ?>" placeholder="ACxxx..." autocomplete="off">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp_twilio_auth_token" class="form-label">Auth Token</label>
                            <input type="password" name="whatsapp_twilio_auth_token" id="whatsapp_twilio_auth_token" class="form-control" value="<?= esc($config['whatsapp_twilio_auth_token'] ?? '') ?>" placeholder="••••••••" autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="whatsapp_twilio_from" class="form-label">Número WhatsApp (From)</label>
                        <input type="text" name="whatsapp_twilio_from" id="whatsapp_twilio_from" class="form-control" value="<?= esc($config['whatsapp_twilio_from'] ?? '') ?>" placeholder="+14155238886" autocomplete="off">
                        <small class="text-muted">Sandbox: cada destinatario debe enviar el código de unión primero.</small>
                    </div>
                </div>

                <!-- URL base (común) -->
                <div class="mb-3">
                    <label for="whatsapp_base_url" class="form-label">URL base (para descargar PDF)</label>
                    <input type="url" name="whatsapp_base_url" id="whatsapp_base_url" class="form-control" value="<?= esc($config['whatsapp_base_url'] ?? '') ?>" placeholder="https://laboratorio.tudominio.com" autocomplete="off">
                    <small class="text-muted">URL pública del laboratorio. Debe ser accesible desde internet.</small>
                </div>

                <hr class="my-4">
                <div class="mb-3">
                    <label for="whatsapp_message_paciente" class="form-label">Mensaje para el paciente</label>
                    <textarea name="whatsapp_message_paciente" id="whatsapp_message_paciente" class="form-control" rows="3" placeholder="Estimado/a {paciente}..."><?= esc($config['whatsapp_message_paciente'] ?? '') ?></textarea>
                    <small class="text-muted">Placeholders: {paciente}, {orden}, {fecha}, {laboratorio}</small>
                </div>
                <div class="mb-3">
                    <label for="whatsapp_message_doctor" class="form-label">Mensaje para el doctor</label>
                    <textarea name="whatsapp_message_doctor" id="whatsapp_message_doctor" class="form-control" rows="3" placeholder="Dr/a {doctor}..."><?= esc($config['whatsapp_message_doctor'] ?? '') ?></textarea>
                    <small class="text-muted">Placeholders: {doctor}, {paciente}, {orden}, {fecha}, {laboratorio}</small>
                </div>
                <button type="submit" class="btn btn-success">Guardar configuración WhatsApp</button>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Pestaña: Tipos de resultado -->
    <div class="tab-pane fade <?= $activeTab === 'opciones' ? 'show active' : '' ?>" id="tab-opciones" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa-solid fa-list-check me-2"></i>Tipos de resultado</h5>
                <a href="<?= site_url('labotests') ?>" class="btn btn-light btn-sm"><i class="fa-solid fa-flask-vial me-1"></i> Ir a Análisis clínicos</a>
            </div>
            <div class="card-body">
                <div id="opciones-content">
                    <?= view('config/partial_opciones', [
                        'opciones' => $opciones ?? [],
                        'opciones_pagination' => $opciones_pagination ?? [],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestaña: Descuentos por institución -->
    <div class="tab-pane fade <?= $activeTab === 'institucion_descuentos' ? 'show active' : '' ?>" id="tab-institucion-descuentos" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fa-solid fa-percent me-2"></i>Descuentos por institución / procedencia</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Defina el porcentaje de descuento por institución. Estas instituciones salen del campo
                    <strong>Institución / procedencia</strong> registrado en pacientes.
                </p>

                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Institución / procedencia</th>
                                <th style="width: 140px;" class="text-end">Descuento (%)</th>
                                <th style="width: 120px;" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($institucion_descuentos ?? []) as $idc): ?>
                            <tr>
                                <td><?= esc($idc['institucion'] ?? '') ?></td>
                                <td class="text-end"><?= esc(number_format((float) ($idc['descuento'] ?? 0), 2, '.', '')) ?>%</td>
                                <td class="text-center">
                                    <a href="<?= site_url('config/deleteInstitucionDescuento?institucion=' . rawurlencode((string) ($idc['institucion'] ?? ''))) ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return uiConfirmLink(this, '¿Eliminar este descuento?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($institucion_descuentos)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No hay descuentos configurados todavía.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?= form_open(site_url('config/saveInstitucionDescuento'), ['class' => 'border rounded p-3']) ?>
                <div class="row align-items-end">
                    <div class="col-md-8 mb-2 mb-md-0">
                        <label for="institucion_descuento_select" class="form-label">Institución / procedencia</label>
                        <select name="institucion" id="institucion_descuento_select" class="form-select" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach (($instituciones_disponibles ?? []) as $inst): ?>
                            <option value="<?= esc($inst) ?>"><?= esc($inst) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label for="institucion_descuento_pct" class="form-label">Descuento %</label>
                        <input type="number"
                               name="descuento"
                               id="institucion_descuento_pct"
                               class="form-control"
                               min="0"
                               max="100"
                               step="0.01"
                               value="0"
                               required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info text-white w-100">
                            <i class="fa-solid fa-save me-1"></i> Guardar
                        </button>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">Si la institución ya existe en la tabla, al guardar se actualizará su porcentaje.</small>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Pestaña: Tipos de muestra (recepción / etiquetas) -->
    <div class="tab-pane fade <?= $activeTab === 'tipos_muestra' ? 'show active' : '' ?>" id="tab-tipos_muestra" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fa-solid fa-vial me-2"></i>Tipos de muestra</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Defina los tipos que aparecerán al <strong>crear una muestra</strong> en una orden (sangre, suero, orina, etc.). La eliminación es lógica y solo está permitida si ninguna muestra usa ese tipo.
                </p>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th class="text-center" style="width: 140px; white-space: nowrap;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($tipos_muestra ?? []) as $tm): ?>
                            <tr>
                                <td><?= esc($tm['nombre'] ?? '') ?></td>
                                <td class="text-center p-2">
                                    <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-nowrap">
                                        <form method="get" action="<?= esc(site_url('config')) ?>" class="d-inline m-0">
                                            <input type="hidden" name="tab" value="tipos_muestra">
                                            <input type="hidden" name="editar_tipo" value="<?= (int) ($tm['tipo_muestra_id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                        </form>
                                        <a href="<?= site_url('config/deletetipomuestra/' . (int) ($tm['tipo_muestra_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger flex-shrink-0" title="Eliminar" onclick="return uiConfirmLink(this, '¿Eliminar este tipo de muestra?');"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($tipos_muestra)): ?>
                            <tr>
                                <td colspan="2" class="text-muted text-center">No hay tipos definidos. Ejecute la migración de base de datos (<code>php spark migrate</code>) o agregue uno abajo.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h6 class="mb-3"><?= (($editar_tipo_muestra ?? 0) > 0) ? 'Editar tipo' : 'Agregar tipo' ?></h6>
                <?= form_open(site_url('config/savetipomuestra'), ['class' => 'border rounded p-3']) ?>
                <input type="hidden" name="tipo_muestra_id" value="<?= (($editar_tipo_muestra ?? 0) > 0) ? (int) $editar_tipo_muestra : '' ?>">
                <div class="row align-items-end">
                    <div class="col-md-8 mb-2 mb-md-0">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control form-control-sm" maxlength="128" required
                               value="<?= esc($editar_tipo_muestra_data['nombre'] ?? '') ?>"
                               placeholder="Ej: Suero, Plasma, LCR…">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-sm me-2"><?= (($editar_tipo_muestra ?? 0) > 0) ? 'Actualizar' : 'Agregar' ?></button>
                        <?php if (($editar_tipo_muestra ?? 0) > 0): ?>
                        <a href="<?= site_url('config?tab=tipos_muestra') ?>" class="btn btn-secondary btn-sm">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Pestaña: Métodos de prueba (técnica del análisis, reporte) -->
    <div class="tab-pane fade <?= $activeTab === 'metodos_prueba' ? 'show active' : '' ?>" id="tab-metodos_prueba" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fa-solid fa-microscope me-2"></i>Métodos de prueba</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Defina los <strong>métodos o técnicas</strong> empleadas en cada análisis (por ejemplo inmunoenzimático, quimioluminiscencia). Se asignan en <strong>Análisis clínicos → detalle del análisis</strong>, debajo del tipo de muestra, y aparecen en el reporte bajo «Tipo de Muestra». Solo se puede eliminar un método si ningún análisis lo usa.
                </p>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th class="text-center" style="width: 140px; white-space: nowrap;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($metodos_prueba ?? []) as $mp): ?>
                            <tr>
                                <td><?= esc($mp['nombre'] ?? '') ?></td>
                                <td class="text-center p-2">
                                    <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-nowrap">
                                        <form method="get" action="<?= esc(site_url('config')) ?>" class="d-inline m-0">
                                            <input type="hidden" name="tab" value="metodos_prueba">
                                            <input type="hidden" name="editar_metodo" value="<?= (int) ($mp['metodo_id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                        </form>
                                        <a href="<?= site_url('config/deletemetodo/' . (int) ($mp['metodo_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger flex-shrink-0" title="Eliminar" onclick="return uiConfirmLink(this, '¿Eliminar este método?');"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($metodos_prueba)): ?>
                            <tr>
                                <td colspan="2" class="text-muted text-center">No hay métodos definidos. Ejecute la migración (<code>php spark migrate</code>) o agregue uno abajo.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h6 class="mb-3"><?= (($editar_metodo ?? 0) > 0) ? 'Editar método' : 'Agregar método' ?></h6>
                <?= form_open(site_url('config/savemetodo'), ['class' => 'border rounded p-3']) ?>
                <input type="hidden" name="metodo_id" value="<?= (($editar_metodo ?? 0) > 0) ? (int) $editar_metodo : '' ?>">
                <div class="row align-items-end">
                    <div class="col-md-8 mb-2 mb-md-0">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control form-control-sm" maxlength="128" required
                               value="<?= esc($editar_metodo_data['nombre'] ?? '') ?>"
                               placeholder="Ej: Quimioluminiscencia, ELISA…">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-sm me-2"><?= (($editar_metodo ?? 0) > 0) ? 'Actualizar' : 'Agregar' ?></button>
                        <?php if (($editar_metodo ?? 0) > 0): ?>
                        <a href="<?= site_url('config?tab=metodos_prueba') ?>" class="btn btn-secondary btn-sm">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Pestaña: Facturación SIN -->
    <div class="tab-pane fade <?= $activeTab === 'sin' ? 'show active' : '' ?>" id="tab-sin" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fa-solid fa-receipt me-2"></i>Configuración de Facturación SIN</h5>
            </div>
            <div class="card-body">
                <?= form_open_multipart(site_url('config/saveSin'), ['id' => 'sin_form']) ?>

                <!-- Enable/Disable Checkbox -->
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sin_billing_enabled" id="sin_billing_enabled" value="1" 
                            <?= ($config['sin_billing_enabled'] ?? '') ? 'checked' : '' ?> 
                            autocomplete="off" style="width: 2.5em; height: 1.5em;">
                        <label class="form-check-label fw-bold" for="sin_billing_enabled">
                            Habilitar facturación con SIN
                        </label>
                        <small class="d-block text-muted mt-1">Activa la emisión de facturas electrónicas. Si está deshabilitado, se emitirán solo recibos.</small>
                    </div>
                </div>

                <div id="sin-config-block" style="display: <?= ($config['sin_billing_enabled'] ?? '') ? 'block' : 'none' ?>;">
                    <hr class="my-4">
                    
                    <!-- Documentación de SIN -->
                    <div class="alert alert-info py-2 small">
                        <strong>Información:</strong> Integración con Servicio de Impuestos Nacionales (SIN) de Bolivia. 
                        <a href="https://www.impuestos.gob.bo/" target="_blank" rel="noopener">Documentación oficial SIN</a>
                    </div>

                    <!-- API Endpoint -->
                    <div class="mb-4">
                        <label for="sin_api_endpoint" class="form-label fw-bold">Endpoint de API (SIAT)</label>
                        <input type="url" name="sin_api_endpoint" id="sin_api_endpoint" class="form-control" 
                            value="<?= esc($config['sin_api_endpoint'] ?? '') ?>" 
                            placeholder="https://pilotosiatservicios.impuestos.gob.bo/v2" autocomplete="off" required>
                        <small class="text-muted d-block">
                            URL base del servicio REST de facturación electrónica (SIAT), <strong>no</strong> <code>api.impuestos.gob.bo/v1</code>.
                        </small>
                        <small class="text-muted d-block mt-1">
                            <strong>Piloto/pruebas:</strong> <code>https://pilotosiatservicios.impuestos.gob.bo/v2</code><br>
                            <strong>Producción:</strong> <code>https://siatrest.impuestos.gob.bo/v2</code>
                            — Token delegado en <a href="https://siatinfo.impuestos.gob.bo/index.php/facturacion-en-linea/emision-y-envio-de-facturas/solicitud-token" target="_blank" rel="noopener">siatinfo.impuestos.gob.bo</a>.
                        </small>
                    </div>

                    <?php
                    $sinTokenConfigured = trim((string) ($config['sin_delegated_token'] ?? '')) !== '';
                    $sinCodigoSistema = trim((string) ($config['sin_codigo_sistema'] ?? ''));
                    $sinAmbiente = trim((string) ($config['sin_codigo_ambiente'] ?? '2'));
                    if (! in_array($sinAmbiente, ['1', '2'], true)) {
                        $sinAmbiente = '2';
                    }
                    ?>
                    <div class="mb-4">
                        <label for="sin_delegated_token" class="form-label fw-bold">Token delegado SIAT (piloto / producción)</label>
                        <textarea name="sin_delegated_token" id="sin_delegated_token" class="form-control font-monospace small" rows="3"
                            placeholder="Pegue el JWT completo (TokenApi …) generado en el portal SIAT"
                            autocomplete="off"><?= esc($config['sin_delegated_token'] ?? '') ?></textarea>
                        <small class="text-muted d-block mt-1">
                            Se envía al SIAT en el encabezado <code>apikey: TokenApi &lt;su_token&gt;</code>. Si lo deja vacío al guardar, se mantiene el token actual.
                        </small>
                        <?php if ($sinTokenConfigured): ?>
                        <div class="alert alert-success py-2 small mt-2 mb-0">
                            <i class="fa-solid fa-key me-1"></i> Token delegado guardado.
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="sin_codigo_sistema" class="form-label fw-bold">Código de sistema SIAT</label>
                            <input type="text" name="sin_codigo_sistema" id="sin_codigo_sistema" class="form-control font-monospace"
                                value="<?= esc($sinCodigoSistema) ?>" placeholder="Ej: 227AC476DC07E8E9D5E11F" autocomplete="off">
                            <small class="text-muted">Se obtiene del token o del registro de su sistema en el SIN.</small>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="sin_codigo_ambiente" class="form-label fw-bold">Ambiente SIAT</label>
                            <select name="sin_codigo_ambiente" id="sin_codigo_ambiente" class="form-select">
                                <option value="2" <?= $sinAmbiente === '2' ? 'selected' : '' ?>>2 — Piloto / pruebas</option>
                                <option value="1" <?= $sinAmbiente === '1' ? 'selected' : '' ?>>1 — Producción</option>
                            </select>
                            <small class="text-muted">Use <strong>2</strong> con <code>pilotosiatservicios</code>.</small>
                        </div>
                    </div>

                    <!-- Certificado Digital SIN (DigiCert .p12) -->
                    <?php
                    $sinP12Rel = trim((string) ($config['sin_certificate_p12_path'] ?? ''));
                    $sinPemRel = trim((string) ($config['sin_certificate_path'] ?? ''));
                    $sinCertConfigured = $sinP12Rel !== '' || $sinPemRel !== '';
                    ?>
                    <div class="mb-4">
                        <label for="sin_certificate_p12" class="form-label fw-bold">Certificado digital (.p12)</label>
                        <input type="file" name="sin_certificate_p12" id="sin_certificate_p12" class="form-control"
                            accept=".p12,application/x-pkcs12" autocomplete="off">
                        <small class="text-muted d-block mt-1">
                            Archivo <strong>.p12</strong> entregado por DigiCert. El nombre del archivo debe terminar en <code>.p12</code> (por ejemplo <code>certificado.p12</code>).
                        </small>
                        <?php if ($sinCertConfigured): ?>
                        <div class="alert alert-success py-2 small mt-2 mb-0">
                            <i class="fa-solid fa-certificate me-1"></i>
                            <strong>Certificado cargado.</strong>
                            <?php if ($sinP12Rel !== ''): ?>
                            Archivo: <code><?= esc(basename($sinP12Rel)) ?></code>
                            <?php endif; ?>
                            <?php if ($sinPemRel !== ''): ?>
                            <span class="text-muted">— PEM: <code><?= esc(basename($sinPemRel)) ?></code></span>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning py-2 small mt-2 mb-0">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                            Aún no hay certificado cargado. Suba el archivo <strong>.p12</strong> y la contraseña para habilitar la firma digital.
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="sin_certificate_password" class="form-label fw-bold">Contraseña del certificado</label>
                        <input type="password" name="sin_certificate_password" id="sin_certificate_password" class="form-control"
                            value="" placeholder="<?= $sinCertConfigured ? 'Dejar vacío para mantener la actual' : 'Contraseña del archivo .p12' ?>"
                            autocomplete="new-password"<?= $sinCertConfigured ? '' : ' required' ?>>
                        <small class="text-muted">Contraseña asignada al exportar o descargar el certificado desde DigiCert.</small>
                    </div>

                    <!-- Business ID / NIT -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="sin_nit" class="form-label fw-bold">NIT de la Empresa</label>
                            <input type="text" name="sin_nit" id="sin_nit" class="form-control" 
                                value="<?= esc($config['sin_nit'] ?? '') ?>" 
                                placeholder="Ej: 123456789" autocomplete="off" required>
                            <small class="text-muted">Número de Identificación Tributario de la empresa</small>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label for="sin_business_name" class="form-label fw-bold">Razón Social</label>
                            <input type="text" name="sin_business_name" id="sin_business_name" class="form-control" 
                                value="<?= esc($config['sin_business_name'] ?? '') ?>" 
                                placeholder="Nombre legal de la empresa" autocomplete="off" required>
                            <small class="text-muted">Razón social como aparece en el SIN</small>
                        </div>
                    </div>

                    <!-- Sucursal -->
                    <div class="mb-4">
                        <label for="sin_branch_code" class="form-label fw-bold">Código de Sucursal</label>
                        <input type="text" name="sin_branch_code" id="sin_branch_code" class="form-control" 
                            value="<?= esc($config['sin_branch_code'] ?? '') ?>" 
                            placeholder="1" autocomplete="off">
                        <small class="text-muted">Código de la sucursal emisora de facturas (por defecto: 1)</small>
                    </div>

                    <!-- System Type -->
                    <div class="mb-4">
                        <label for="sin_system_type" class="form-label fw-bold">Tipo de Sistema</label>
                        <select name="sin_system_type" id="sin_system_type" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="nativo" <?= ($config['sin_system_type'] ?? '') === 'nativo' ? 'selected' : '' ?>>Nativo (Conectado en línea)</option>
                            <option value="descargable" <?= ($config['sin_system_type'] ?? '') === 'descargable' ? 'selected' : '' ?>>Sistema Descargable</option>
                        </select>
                        <small class="text-muted">Sistema de facturación a usar según SIN</small>
                    </div>

                    <!-- Emission Mode -->
                    <div class="mb-4">
                        <label for="sin_emission_mode" class="form-label fw-bold">Modalidad de Emisión</label>
                        <select name="sin_emission_mode" id="sin_emission_mode" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="online" <?= ($config['sin_emission_mode'] ?? '') === 'online' ? 'selected' : '' ?>>En Línea</option>
                            <option value="offline" <?= ($config['sin_emission_mode'] ?? '') === 'offline' ? 'selected' : '' ?>>Fuera de Línea</option>
                        </select>
                        <small class="text-muted">Modalidad de emisión de facturas con SIN</small>
                    </div>

                    <!-- Activity Code -->
                    <div class="mb-4">
                        <label for="sin_activity_code" class="form-label fw-bold">Código de Actividad</label>
                        <input type="text" name="sin_activity_code" id="sin_activity_code" class="form-control" 
                            value="<?= esc($config['sin_activity_code'] ?? '') ?>" 
                            placeholder="Ej: 6230" autocomplete="off" required>
                        <small class="text-muted">Código CAEN de la actividad económica (para laboratorios normalmente 6230)</small>
                    </div>

                    <hr class="my-4">
                    
                    <!-- Test Button -->
                    <div class="mb-4">
                        <button type="button" id="sin_test_btn" class="btn btn-outline-primary me-2">
                            <i class="fa-solid fa-flask-vial me-1"></i> Probar Conexión
                        </button>
                        <small class="text-muted ms-2">Valida certificado, token delegado y API SIAT (guarde antes de probar)</small>
                        <div id="sin_test_result" class="mt-2" style="display: none;"></div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-warning" style="color: #000;">
                        <i class="fa-solid fa-save me-1"></i> Guardar Configuración SIN
                    </button>
                    <small class="text-muted d-block mt-2" id="sin_save_hint">
                        <?= ($config['sin_billing_enabled'] ?? '') ? 'Pulse guardar para aplicar cambios en la emisión de comprobantes.' : 'Pulse guardar para confirmar que solo se emitirán recibos.' ?>
                    </small>
                </div>

                <div class="alert alert-secondary py-2 small mt-3" id="sin_status_alert" style="display: <?= ($config['sin_billing_enabled'] ?? '') ? 'none' : 'block' ?>;">
                    <strong>Estado actual:</strong> Facturación SIN deshabilitada. Se emitirán solo recibos.
                </div>

                <?= form_close() ?>
            </div>
        </div>
    </div>

    <?= view('config/tab_leyendas_cultivo', [
        'leyendas_cultivo'            => $leyendas_cultivo ?? [],
        'leyendas_cultivo_categorias' => $leyendas_cultivo_categorias ?? [],
        'editar_leyenda_cultivo'      => $editar_leyenda_cultivo ?? 0,
        'editar_leyenda_cultivo_data' => $editar_leyenda_cultivo_data ?? [],
        'editar_leyenda_cultivo_categoria' => $editar_leyenda_cultivo_categoria ?? 0,
        'editar_leyenda_cultivo_categoria_data' => $editar_leyenda_cultivo_categoria_data ?? [],
        'activeTab'                   => $activeTab,
    ]) ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="<?= base_url('js/comprobante-editor.js') ?>?v=20" defer></script>
<script src="<?= base_url('js/config.js') ?>" defer></script>
<script>
$(document).ready(function() {
    document.querySelectorAll('#configTabs button[data-bs-toggle="tab"]').forEach(function(btn) {
        btn.addEventListener('shown.bs.tab', function(e) {
            var target = e.target.getAttribute('data-bs-target');
            if (target) {
                var tab = target.replace('#tab-', '');
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                history.replaceState(null, '', url);
            }
        });
    });

    var elSubStart = document.getElementById('tenant_sub_period_start');
    var elSubEnd = document.getElementById('tenant_sub_period_end');
    if (elSubStart && elSubEnd && typeof flatpickr !== 'undefined') {
        var fpOpts = { dateFormat: 'Y-m-d', locale: 'es', onOpen: function (s, d, i) { if (typeof flatpickrPositionArrowTopLeft === 'function') { flatpickrPositionArrowTopLeft(i); } } };
        flatpickr(elSubStart, fpOpts);
        flatpickr(elSubEnd, fpOpts);
    }

    (function initTenantBackupLabClock() {
        var el = document.getElementById('tbs-lab-clock');
        if (!el || typeof Intl === 'undefined' || !Intl.DateTimeFormat) {
            return;
        }
        var tz = el.getAttribute('data-timezone') || 'UTC';
        function tick() {
            try {
                var fmt = new Intl.DateTimeFormat('es', {
                    timeZone: tz,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
                el.textContent = fmt.format(new Date());
            } catch (err) {
                el.textContent = '—';
                el.setAttribute('title', 'Zona no válida en el navegador: ' + tz);
            }
        }
        tick();
        setInterval(tick, 1000);
    })();
    
    // Tamaño de hoja personalizado (impresión directa)
    (function initPrintPaperCustom() {
        var sel = document.getElementById('print_paper_size');
        var wrap = document.getElementById('print_paper_custom_wrap');
        if (!sel || !wrap) {
            return;
        }
        var w = document.getElementById('print_paper_width_mm');
        var h = document.getElementById('print_paper_height_mm');
        function sync() {
            var on = sel.value === 'custom';
            wrap.style.display = on ? 'block' : 'none';
            if (w) {
                w.disabled = !on;
            }
            if (h) {
                h.disabled = !on;
            }
        }
        sel.addEventListener('change', sync);
        sync();
    })();

    // SIN Billing Toggle
    var sinToggle = document.getElementById('sin_billing_enabled');
    if (sinToggle) {
        function syncSinRequiredFields() {
            var on = sinToggle.checked;
            var configBlock = document.getElementById('sin-config-block');
            if (configBlock) {
                configBlock.style.display = on ? 'block' : 'none';
                configBlock.querySelectorAll('input, select, textarea').forEach(function(el) {
                    if (el.id === 'sin_certificate_password' && el.getAttribute('data-sin-cert-configured') === '1') {
                        el.required = false;
                        return;
                    }
                    if (el.type === 'file') {
                        el.required = false;
                        return;
                    }
                    if (el.hasAttribute('required')) {
                        el.required = on;
                    }
                });
            }
            var statusAlert = document.getElementById('sin_status_alert');
            if (statusAlert) {
                statusAlert.style.display = on ? 'none' : 'block';
            }
            var saveHint = document.getElementById('sin_save_hint');
            if (saveHint) {
                saveHint.textContent = on
                    ? 'Pulse guardar para aplicar cambios en la emisión de comprobantes.'
                    : 'Pulse guardar para confirmar que solo se emitirán recibos.';
            }
        }
        var sinPwd = document.getElementById('sin_certificate_password');
        if (sinPwd) {
            sinPwd.setAttribute('data-sin-cert-configured', <?= $sinCertConfigured ?? false ? '1' : '0' ?>);
        }
        sinToggle.addEventListener('change', syncSinRequiredFields);
        syncSinRequiredFields();
    }

    function sinTestResolveUrl(configuredUrl) {
        try {
            var parsed = new URL(configuredUrl, window.location.href);
            return window.location.origin + parsed.pathname + parsed.search;
        } catch (e) {
            var base = (typeof BASE_URL !== 'undefined' ? String(BASE_URL) : '/');
            return base.replace(/\/?$/, '/') + 'config/testSin';
        }
    }

    function sinEscapeHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function sinTestShowResult(testResult, ok, message, details) {
        var cls = ok ? 'alert-success' : 'alert-danger';
        var icon = ok ? 'fa-circle-check' : 'fa-circle-xmark';
        var html = '<div class="alert ' + cls + ' py-2 mb-0"><i class="fa-solid ' + icon + ' me-2"></i> ' + sinEscapeHtml(message || '') + '</div>';
        if (details && details.length) {
            html += '<ul class="small text-muted mb-0 mt-1 ps-3">';
            details.forEach(function(line) {
                if (line) html += '<li>' + sinEscapeHtml(line) + '</li>';
            });
            html += '</ul>';
        }
        testResult.innerHTML = html;
        testResult.style.display = 'block';
    }

    (function sinTokenAutofillCodigoSistema() {
        var ta = document.getElementById('sin_delegated_token');
        var cod = document.getElementById('sin_codigo_sistema');
        if (!ta || !cod) return;
        function tryFill() {
            if (cod.value.trim() !== '') return;
            var raw = ta.value.trim();
            if (!raw) return;
            if (raw.toLowerCase().indexOf('tokenapi ') === 0) raw = raw.slice(9).trim();
            var parts = raw.split('.');
            if (parts.length < 2) return;
            try {
                var b64 = parts[1].replace(/-/g, '+').replace(/_/g, '/');
                while (b64.length % 4) b64 += '=';
                var payload = JSON.parse(atob(b64));
                if (payload && payload.codigoSistema) {
                    cod.value = String(payload.codigoSistema);
                }
            } catch (e) { /* ignore */ }
        }
        ta.addEventListener('blur', tryFill);
    })();

    // SIN Test Connection Button
    var testBtn = document.getElementById('sin_test_btn');
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            var testResult = document.getElementById('sin_test_result');
            var sinForm = document.getElementById('sin_form');
            testResult.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Probando...</span></div> Probando certificado y servidor SIAT...';
            testResult.style.display = 'block';

            var csrfName = (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
            var csrfVal = (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
            var csrfInput = document.querySelector('#sin_form input[name*="csrf"]');
            if (csrfInput && csrfInput.value) {
                csrfVal = csrfInput.value;
                csrfName = csrfInput.name;
            }
            if (!csrfVal) {
                sinTestShowResult(testResult, false, 'No se encontró el token de seguridad. Recargue la página (F5).', []);
                return;
            }

            var fd = new FormData();
            if (sinForm) {
                var enabledEl = document.getElementById('sin_billing_enabled');
                if (enabledEl && enabledEl.checked) {
                    fd.append('sin_billing_enabled', '1');
                }
                ['sin_api_endpoint', 'sin_delegated_token', 'sin_codigo_sistema', 'sin_codigo_ambiente', 'sin_nit', 'sin_business_name', 'sin_branch_code', 'sin_system_type', 'sin_emission_mode', 'sin_activity_code'].forEach(function(name) {
                    var el = sinForm.querySelector('[name="' + name + '"]');
                    if (el && el.value !== undefined) {
                        fd.append(name, el.value);
                    }
                });
                var pwdEl = document.getElementById('sin_certificate_password');
                if (pwdEl && pwdEl.value) {
                    fd.append('sin_certificate_password', pwdEl.value);
                }
            }
            fd.append(csrfName, csrfVal);

            var headers = { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfVal };
            var testSinUrl = sinTestResolveUrl('<?= site_url('config/testSin') ?>');
            fetch(testSinUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: headers
            })
            .then(function(res) {
                return res.text().then(function(text) {
                    return { ok: res.ok, status: res.status, text: text };
                });
            })
            .then(function(bundle) {
                var data = null;
                try {
                    data = bundle.text ? JSON.parse(bundle.text) : null;
                } catch (parseErr) {
                    var snippet = (bundle.text || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 220);
                    sinTestShowResult(testResult, false, 'El servidor no devolvió JSON (HTTP ' + bundle.status + '). ' + (snippet || 'Revise sesión o permisos.'), []);
                    return;
                }
                if (data && data.csrf_token && data.csrf_name && typeof syncCsrfToken === 'function') {
                    syncCsrfToken(data.csrf_name, data.csrf_token);
                }
                if (!data) {
                    sinTestShowResult(testResult, false, 'Respuesta vacía del servidor.', []);
                    return;
                }
                if (data.success === false) {
                    sinTestShowResult(testResult, false, data.message || 'Error en la prueba', data.details || []);
                } else {
                    sinTestShowResult(testResult, true, data.message || 'Prueba correcta', data.details || []);
                }
            })
            .catch(function(err) {
                sinTestShowResult(testResult, false, 'No se pudo contactar al servidor local (' + testSinUrl + '): ' + (err && err.message ? err.message : 'error de red'), []);
            });
        });
    }

    function toggleWaProvider() {
        var p = document.getElementById('whatsapp_provider');
        var prov = p ? p.value : 'meta';
        var meta = document.getElementById('wa-meta-block');
        var twilio = document.getElementById('wa-twilio-block');
        if (meta) meta.style.display = prov === 'meta' ? 'block' : 'none';
        if (twilio) twilio.style.display = prov === 'twilio' ? 'block' : 'none';
    }
    (function() {
        var prov = document.getElementById('whatsapp_provider');
        if (prov) prov.addEventListener('change', toggleWaProvider);
        toggleWaProvider();
    })();
    if ($.fn.validate && $('#config_form').length) {
        $('#config_form').validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
            rules: { company: { required: true, minlength: 2, maxlength: 255 } },
            messages: { company: { required: "El nombre de la empresa es obligatorio", minlength: "La empresa debe tener al menos 2 caracteres" } }
        }));
        $('#form_poblacion').validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
            rules: { name: { required: true } },
            messages: { name: { required: "El nombre del grupo es obligatorio" } }
        }));
        $('#form_nueva_opcion').validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
            rules: { opciones: { required: true } },
            messages: { opciones: { required: "El nombre es obligatorio" } }
        }));
        $('#tenant_form').validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
            rules: {
                tenant_key: { required: true, maxlength: 64 },
                tenant_name: { required: true, maxlength: 120 },
                db_host: { required: true },
                db_port: { required: true, number: true, min: 1, max: 65535 },
                db_name: { required: true },
                db_user: { required: true }
            }
        }));
    }

    function initNuevaOpcionValidation() {
        if (!$.fn.validate || !$('#form_nueva_opcion').length) return;
        $('#form_nueva_opcion').validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
            rules: { opciones: { required: true } },
            messages: { opciones: { required: "El nombre es obligatorio" } }
        }));
    }

    function syncCsrfToken(csrfName, csrfToken) {
        if (!csrfName || !csrfToken) return;
        document.querySelectorAll('input[name="' + csrfName + '"]').forEach(function(input) {
            input.value = csrfToken;
        });
        if (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined') {
            window.CI_CSRF_TOKEN_NAME = csrfName;
        }
        if (typeof window.CI_CSRF_TOKEN !== 'undefined') {
            window.CI_CSRF_TOKEN = csrfToken;
        }
    }

    function refreshOpcionesContent(response) {
        var cont = document.getElementById('opciones-content');
        if (!cont || !response || typeof response.html !== 'string') return;
        cont.innerHTML = response.html;
        initNuevaOpcionValidation();
    }

    function showOpcionesToast(message, ok) {
        if (typeof showToast === 'function' && message) {
            showToast(message, ok ? 'success' : 'error');
        }
    }

    var opcionesTab = document.getElementById('tab-opciones');
    if (opcionesTab) {
        opcionesTab.addEventListener('submit', function(event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) return;

            var action = String(form.getAttribute('action') || '');
            var isOpcionesAction = /config\/(saveopcion|saveopcionvalor|savevalortabla)/i.test(action);
            if (!isOpcionesAction) return;

            event.preventDefault();
            var formData = new FormData(form);
            fetch(action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(r) {
                return r.json().then(function(data) {
                    return { ok: r.ok, data: data };
                });
            }).then(function(result) {
                var payload = result.data || {};
                syncCsrfToken(payload.csrf_name, payload.csrf_token);
                refreshOpcionesContent(payload);
                showOpcionesToast(payload.message || (result.ok ? 'Guardado correctamente.' : 'No se pudo guardar.'), result.ok);
            }).catch(function() {
                showOpcionesToast('Error al guardar. Intente nuevamente.', false);
            });
        });

        opcionesTab.addEventListener('click', function(event) {
            if (event.defaultPrevented) return;
            var link = event.target.closest('a');
            if (!link) return;
            var href = String(link.getAttribute('href') || '');
            var isDeleteAction = /config\/(deleteopcion\/|deleteopcionvalor\/|deletevalortabla\/)/i.test(href);
            if (!isDeleteAction) return;

            event.preventDefault();
            var proceed = function() {
                fetch(href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function(r) {
                    return r.json().then(function(data) {
                        return { ok: r.ok, data: data };
                    });
                }).then(function(result) {
                    var payload = result.data || {};
                    syncCsrfToken(payload.csrf_name, payload.csrf_token);
                    refreshOpcionesContent(payload);
                    showOpcionesToast(payload.message || (result.ok ? 'Eliminado correctamente.' : 'No se pudo eliminar.'), result.ok);
                }).catch(function() {
                    showOpcionesToast('Error al eliminar. Intente nuevamente.', false);
                });
            };

            if (typeof uiConfirm === 'function') {
                uiConfirm('¿Confirmar eliminación?', 'Confirmar').then(function(ok) {
                    if (ok) proceed();
                });
                return;
            }
            proceed();
        });
    }

    // Cerrar todas las sesiones
    var btnCloseAllSessions = document.getElementById('btn_close_all_sessions');
    var btnRefreshSessions = document.getElementById('btn_refresh_sessions');
    var sessionsTableBody = document.querySelector('#active_sessions_table tbody');
    var sessionsTabBtn = document.getElementById('tab-sesiones-btn');
    var sessionsVersionCheckTimer = null;
    var sessionsVersionCheckMs = 2000;
    var lastSessionsVersion = null;

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function formatInactive(seconds) {
        var s = Number(seconds || 0);
        if (s < 60) return 'Última petición: hace menos de 1 min';
        if (s < 3600) return 'Última petición hace ' + Math.floor(s / 60) + ' min';
        return 'Última petición hace ' + Math.floor(s / 3600) + ' h';
    }

    function loadActiveSessions() {
        if (!sessionsTableBody) return;
        sessionsTableBody.innerHTML = '<tr><td colspan="8" class="text-muted text-center">Cargando sesiones...</td></tr>';

        var fd = new FormData();
        var csrfInput = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
        var csrfName = (csrfInput && csrfInput.name) ? csrfInput.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
        var csrfVal = (csrfInput && csrfInput.value) ? csrfInput.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
        if (csrfVal) fd.append(csrfName, csrfVal);
        fetch('<?= site_url('config/getActiveSessions') ?>', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfVal }
        }).then(function(r) { return r.json(); }).then(function(response) {
            if (!response.success || !Array.isArray(response.sessions)) {
                sessionsTableBody.innerHTML = '<tr><td colspan="8" class="text-danger text-center">No se pudo cargar sesiones activas</td></tr>';
                return;
            }
            if (response.version) {
                lastSessionsVersion = String(response.version);
            }

            if (response.sessions.length === 0) {
                sessionsTableBody.innerHTML = '<tr><td colspan="8" class="text-muted text-center">No hay sesiones activas</td></tr>';
                return;
            }

            var rowsHtml = response.sessions.map(function(s) {
                var typeLabel = s.user_type === 'doctor' ? '<span class="badge bg-info text-dark">Doctor</span>' : '<span class="badge bg-primary">Empleado</span>';
                var currentBadge = s.is_current ? '<span class="badge bg-success">Esta sesión</span>' : '<span class="badge bg-secondary">Activa</span>';
                var btnKill = s.is_current
                    ? '<button type="button" class="btn btn-sm btn-outline-secondary" disabled>Actual</button>'
                    : '<button type="button" class="btn btn-sm btn-outline-danger btn-kill-session" data-session-id="' + escapeHtml(s.session_id) + '"><i class="fa-solid fa-power-off me-1"></i> Cerrar</button>';
                return '<tr>'
                    + '<td>' + typeLabel + '</td>'
                    + '<td>' + escapeHtml(s.username || '') + '</td>'
                    + '<td>' + escapeHtml(s.full_name || '') + '</td>'
                    + '<td><small>' + escapeHtml(s.user_agent || 'No disponible') + '</small></td>'
                    + '<td>' + escapeHtml(s.ip_address || '') + '</td>'
                    + '<td>' + escapeHtml(s.last_activity || '-') + '<br><small class="text-muted">' + escapeHtml(formatInactive(s.seconds_inactive)) + '</small></td>'
                    + '<td>' + currentBadge + '</td>'
                    + '<td class="text-center">' + btnKill + '</td>'
                    + '</tr>';
            }).join('');

            sessionsTableBody.innerHTML = rowsHtml;
        }).catch(function() {
            sessionsTableBody.innerHTML = '<tr><td colspan="8" class="text-danger text-center">Error al conectar con el servidor</td></tr>';
        });
    }

    function isSessionsTabActive() {
        return !!(sessionsTabBtn && sessionsTabBtn.classList.contains('active'));
    }

    function checkSessionsVersion() {
        if (!isSessionsTabActive()) return;
        if (document.visibilityState && document.visibilityState !== 'visible') return;

        var fd = new FormData();
        var csrfInput = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
        var csrfName = (csrfInput && csrfInput.name) ? csrfInput.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
        var csrfVal = (csrfInput && csrfInput.value) ? csrfInput.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
        if (csrfVal) fd.append(csrfName, csrfVal);

        fetch('<?= site_url('config/getSessionsVersion') ?>', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfVal }
        }).then(function(r) { return r.json(); }).then(function(response) {
            if (!response || !response.success || !response.version) return;
            var currentVersion = String(response.version);
            if (lastSessionsVersion === null) {
                lastSessionsVersion = currentVersion;
                return;
            }
            if (currentVersion !== lastSessionsVersion) {
                loadActiveSessions();
            }
        }).catch(function() {
            // Silencioso: en el próximo ciclo vuelve a intentar.
        });
    }

    function startSessionsVersionWatcher() {
        if (sessionsVersionCheckTimer) return;
        sessionsVersionCheckTimer = setInterval(function() {
            if (!isSessionsTabActive()) return;
            if (document.visibilityState && document.visibilityState !== 'visible') return;
            checkSessionsVersion();
        }, sessionsVersionCheckMs);
    }

    function stopSessionsVersionWatcher() {
        if (!sessionsVersionCheckTimer) return;
        clearInterval(sessionsVersionCheckTimer);
        sessionsVersionCheckTimer = null;
    }

    function killSessionById(sessionId) {
        var fd = new FormData();
        fd.append('session_id', sessionId);
        var csrfInput = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
        var csrfName = (csrfInput && csrfInput.name) ? csrfInput.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
        var csrfVal = (csrfInput && csrfInput.value) ? csrfInput.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
        if (csrfVal) fd.append(csrfName, csrfVal);
        fetch('<?= site_url('config/killSession') ?>', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfVal }
        }).then(function(r) { return r.json(); }).then(function(response) {
            if (typeof showToast === 'function') {
                showToast(response.message || (response.success ? 'Sesión cerrada' : 'No se pudo cerrar la sesión'), response.success ? 'success' : 'error');
            }
            loadActiveSessions();
        }).catch(function() {
            if (typeof showToast === 'function') {
                showToast('Error al cerrar sesión', 'error');
            }
        });
    }

    if (btnRefreshSessions) {
        btnRefreshSessions.addEventListener('click', loadActiveSessions);
    }

    if (sessionsTableBody) {
        sessionsTableBody.addEventListener('click', function(ev) {
            var target = ev.target.closest('.btn-kill-session');
            if (!target) return;
            var sid = target.getAttribute('data-session-id') || '';
            if (!sid) return;
            if (typeof uiConfirm === 'function') {
                uiConfirm('¿Cerrar esta sesión?', 'Confirmar').then(function(ok) {
                    if (ok) killSessionById(sid);
                });
                return;
            }
            // Fallback: si no existe uiConfirm, no continuar (evita confirm nativo)
        });
    }

    if (sessionsTabBtn) {
        sessionsTabBtn.addEventListener('shown.bs.tab', function() {
            loadActiveSessions();
            startSessionsVersionWatcher();
        });
    }

    document.querySelectorAll('#configTabs button[data-bs-toggle="tab"]').forEach(function(btn) {
        btn.addEventListener('shown.bs.tab', function(e) {
            var target = e.target.getAttribute('data-bs-target') || '';
            if (target === '#tab-sesiones') {
                startSessionsVersionWatcher();
            } else {
                stopSessionsVersionWatcher();
            }
        });
    });

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible' && isSessionsTabActive()) {
            loadActiveSessions();
            startSessionsVersionWatcher();
        }
    });

    if (isSessionsTabActive()) {
        loadActiveSessions();
        startSessionsVersionWatcher();
    }

    if (btnCloseAllSessions) {
        btnCloseAllSessions.addEventListener('click', function() {
            btnCloseAllSessions.disabled = true;
            var originalText = btnCloseAllSessions.innerHTML;
            btnCloseAllSessions.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Cerrando...';
            
            var csrfInput = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
            var csrfName = (csrfInput && csrfInput.name) ? csrfInput.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
            var csrfVal = (csrfInput && csrfInput.value) ? csrfInput.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
            
            var fd = new FormData();
            if (csrfVal) fd.append(csrfName, csrfVal);
            
            var fetchHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
            if (csrfVal) fetchHeaders['X-CSRF-TOKEN'] = csrfVal;
            
            fetch('<?= site_url('config/closeAllSessions') ?>', {
                method: 'POST',
                body: fd,
                headers: fetchHeaders
            }).then(function(r) { return r.json(); }).then(function(response) {
                btnCloseAllSessions.disabled = false;
                btnCloseAllSessions.innerHTML = originalText;
                if (response.success) {
                    if (typeof showToast === 'function') {
                        showToast(response.message || 'Todas las sesiones han sido cerradas', 'success');
                    }
                    loadActiveSessions();
                } else {
                    if (typeof showToast === 'function') {
                        showToast(response.message || 'Error al cerrar las sesiones', 'error');
                    }
                }
            }).catch(function() {
                btnCloseAllSessions.disabled = false;
                btnCloseAllSessions.innerHTML = originalText;
                if (typeof showToast === 'function') {
                    showToast('Error al conectar con el servidor', 'error');
                }
            });
        });
    }
});
</script>
<script>
(function() {
    function initLeyendaCultivoEditor() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.summernote) return;
        var el = document.getElementById('leyenda_cultivo_mensaje');
        if (!el || jQuery(el).data('summernote')) return;
        jQuery(el).summernote({
            height: 220,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['codeview', 'undo', 'redo']]
            ]
        });
    }

    var lcForm = document.getElementById('form_leyenda_cultivo');
    if (lcForm) {
        lcForm.addEventListener('submit', function() {
            var el = document.getElementById('leyenda_cultivo_mensaje');
            if (el && typeof jQuery !== 'undefined' && jQuery(el).data('summernote')) {
                el.value = jQuery(el).summernote('code');
            }
        });
    }

    var lcTabBtn = document.getElementById('tab-leyendas_cultivo-btn');
    if (lcTabBtn) {
        lcTabBtn.addEventListener('shown.bs.tab', initLeyendaCultivoEditor);
    }

    var lcPane = document.getElementById('tab-leyendas_cultivo');
    if (lcPane && lcPane.classList.contains('active')) {
        initLeyendaCultivoEditor();
    }
})();
</script>
<?= $this->endSection() ?>
