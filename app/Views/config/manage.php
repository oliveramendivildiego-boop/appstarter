<?= $this->extend('layouts/main') ?>
<?= $this->section('head_extra') ?>
<script src="<?= base_url('js/vendor/jquery.validate.min.js') ?>"></script>
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
<ul class="nav nav-tabs mb-3" id="configTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'sistema' ? 'active' : '' ?>" id="tab-sistema-btn" data-bs-toggle="tab" data-bs-target="#tab-sistema" type="button" role="tab">Configuración del sistema</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'estilo' ? 'active' : '' ?>" id="tab-estilo-btn" data-bs-toggle="tab" data-bs-target="#tab-estilo" type="button" role="tab"><?= lang('Config.config_style_tab_nav') ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'poblacion' ? 'active' : '' ?>" id="tab-poblacion-btn" data-bs-toggle="tab" data-bs-target="#tab-poblacion" type="button" role="tab">Grupos de población (por edad)</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'opciones' ? 'active' : '' ?>" id="tab-opciones-btn" data-bs-toggle="tab" data-bs-target="#tab-opciones" type="button" role="tab">Tipos de resultado</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'tipos_muestra' ? 'active' : '' ?>" id="tab-tipos_muestra-btn" data-bs-toggle="tab" data-bs-target="#tab-tipos_muestra" type="button" role="tab">Tipos de muestra</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'whatsapp' ? 'active' : '' ?>" id="tab-whatsapp-btn" data-bs-toggle="tab" data-bs-target="#tab-whatsapp" type="button" role="tab">WhatsApp</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'sin' ? 'active' : '' ?>" id="tab-sin-btn" data-bs-toggle="tab" data-bs-target="#tab-sin" type="button" role="tab">Facturación SIN</button>
    </li>
    <?php if (($can_manage_tenants ?? false)): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'tenants' ? 'active' : '' ?>" id="tab-tenants-btn" data-bs-toggle="tab" data-bs-target="#tab-tenants" type="button" role="tab">Tenants</button>
    </li>
    <?php endif; ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'sesiones' ? 'active' : '' ?>" id="tab-sesiones-btn" data-bs-toggle="tab" data-bs-target="#tab-sesiones" type="button" role="tab">Sesiones activas</button>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" href="<?= site_url('config/pdf-templates') ?>"><i class="fa-solid fa-file-pdf me-1"></i><?= lang('Config.config_pdf_templates_tab') ?></a>
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
                <?= form_dropdown('timezone', $timezone_options ?? [], $config['timezone'] ?? 'America/Bogota', 'id="timezone" class="form-select" autocomplete="off"') ?>
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
        </div>
        <div class="mb-3">
            <?= form_label(lang('Config.config_return_policy'), 'return_policy', ['class' => 'form-label']) ?>
            <?= form_textarea(['name' => 'return_policy', 'id' => 'return_policy', 'class' => 'form-control', 'rows' => 4, 'autocomplete' => 'off', 'value' => $config['return_policy'] ?? '']) ?>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Config.config_decimales_sugerencia'), 'decimales_sugerencia', ['class' => 'form-label']) ?>
                <?= form_input(['name' => 'decimales_sugerencia', 'id' => 'decimales_sugerencia', 'type' => 'number', 'min' => 0, 'max' => 10, 'class' => 'form-control', 'value' => $config['decimales_sugerencia'] ?? '2', 'autocomplete' => 'off']) ?>
                <small class="text-muted"><?= lang('Config.config_decimales_sugerencia_help') ?></small>
            </div>
            <div class="col-md-6 mb-3">
                <label for="dias_alerta_vencimiento" class="form-label">Días de alerta para vencimiento de insumos</label>
                <?= form_input(['name' => 'dias_alerta_vencimiento', 'id' => 'dias_alerta_vencimiento', 'type' => 'number', 'min' => 1, 'max' => 365, 'class' => 'form-control', 'value' => $config['dias_alerta_vencimiento'] ?? '40', 'autocomplete' => 'off']) ?>
                <small class="text-muted">Los lotes que venzan en los próximos X días se marcarán en amarillo en el reporte de insumos por vencimiento.</small>
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
                    <li><code>%i</code> — <strong>obligatorio</strong> si usa formato: contador que aumenta (1, 2, 3…). Se reinicia según lo que incluya la plantilla: si hay día (<code>%d</code> o <code>%dd</code>) cada día; si solo mes, cada mes; si solo año, cada año; si no hay fecha, un solo contador global.</li>
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

    <?= view('config/tab_estilo', ['config' => $config, 'activeTab' => $activeTab, 'theme_palette' => $theme_palette ?? []]) ?>

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
                    <strong>Superusuario sin auditoría:</strong> en cada fila puede abrir el laboratorio de ese tenant con su usuario actual;
                    las acciones <em>no</em> generan registros en la tabla de auditoría del tenant (sí puede haber huella en sesiones del servidor). Use solo para soporte.
                    Si el laboratorio usa otro dominio (ej. <code>http://quantum.local</code>), indique la <strong>URL pública del tenant</strong> en el formulario de abajo o configure en <code>.env</code> <code>tenancy.publicUrlTemplate=http://{tenant_key}.local</code>: se generará un enlace de un solo uso para iniciar sesión en ese host (mismo <code>person_id</code> y usuario en la BD del tenant).
                </p>

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
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Aprovisionar DB + migraciones"><i class="fa-solid fa-server"></i></button>
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

    <!-- Pestaña: WhatsApp -->
    <div class="tab-pane fade <?= $activeTab === 'whatsapp' ? 'show active' : '' ?>" id="tab-whatsapp" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fa-brands fa-whatsapp me-2"></i>Configuración de WhatsApp Business API</h5>
            </div>
            <div class="card-body">
                <?= form_open(site_url('config/saveWhatsapp'), ['id' => 'whatsapp_form']) ?>
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
                <?= view('config/partial_opciones', ['opciones' => $opciones ?? []]) ?>
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

    <!-- Pestaña: Facturación SIN -->
    <div class="tab-pane fade <?= $activeTab === 'sin' ? 'show active' : '' ?>" id="tab-sin" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fa-solid fa-receipt me-2"></i>Configuración de Facturación SIN</h5>
            </div>
            <div class="card-body">
                <?= form_open(site_url('config/saveSin'), ['id' => 'sin_form']) ?>
                
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
                        <label for="sin_api_endpoint" class="form-label fw-bold">Endpoint de API</label>
                        <input type="url" name="sin_api_endpoint" id="sin_api_endpoint" class="form-control" 
                            value="<?= esc($config['sin_api_endpoint'] ?? '') ?>" 
                            placeholder="https://api.impuestos.gob.bo/v1" autocomplete="off" required>
                        <small class="text-muted">URL base para la integración con SIN</small>
                    </div>

                    <!-- Certificado Digital SIN -->
                    <div class="mb-4">
                        <label for="sin_certificate_path" class="form-label fw-bold">Ruta del Certificado Digital (PEM)</label>
                        <input type="text" name="sin_certificate_path" id="sin_certificate_path" class="form-control" 
                            value="<?= esc($config['sin_certificate_path'] ?? '') ?>" 
                            placeholder="/path/to/certificado.pem" autocomplete="off" required>
                        <small class="text-muted">Ruta del certificado digital requerido para firmar documentos. Debe ser accesible por el servidor.</small>
                    </div>

                    <!-- Certificado Password -->
                    <div class="mb-4">
                        <label for="sin_certificate_password" class="form-label fw-bold">Contraseña del Certificado</label>
                        <input type="password" name="sin_certificate_password" id="sin_certificate_password" class="form-control" 
                            value="<?= esc($config['sin_certificate_password'] ?? '') ?>" 
                            placeholder="••••••••" autocomplete="off" required>
                        <small class="text-muted">Contraseña del certificado digital PEM</small>
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
                        <small class="text-muted ms-2">Verifica si la configuración es correcta</small>
                        <div id="sin_test_result" class="mt-2" style="display: none;"></div>
                    </div>

                    <button type="submit" class="btn btn-warning" style="color: #000;">
                        <i class="fa-solid fa-save me-1"></i> Guardar Configuración SIN
                    </button>
                </div>

                <?php if (!($config['sin_billing_enabled'] ?? '')): ?>
                <div class="alert alert-secondary py-2 small mt-3">
                    <strong>Estado actual:</strong> Facturación SIN deshabilitada. Se emitirán solo recibos.
                </div>
                <?php endif; ?>

                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
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
    
    // SIN Billing Toggle
    var sinToggle = document.getElementById('sin_billing_enabled');
    if (sinToggle) {
        sinToggle.addEventListener('change', function() {
            var configBlock = document.getElementById('sin-config-block');
            if (configBlock) {
                configBlock.style.display = this.checked ? 'block' : 'none';
            }
        });
    }

    // SIN Test Connection Button
    var testBtn = document.getElementById('sin_test_btn');
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            var testResult = document.getElementById('sin_test_result');
            testResult.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Probando...</span></div> Probando conexión...';
            testResult.style.display = 'block';
            
            $.ajax({
                url: '<?= site_url('config/testSin') ?>',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        testResult.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="fa-solid fa-circle-check me-2"></i> Conexión exitosa</div>';
                    } else {
                        testResult.innerHTML = '<div class="alert alert-warning py-2 mb-0"><i class="fa-solid fa-triangle-exclamation me-2"></i> ' + (response.message || 'Error en la prueba') + '</div>';
                    }
                },
                error: function() {
                    testResult.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="fa-solid fa-circle-xmark me-2"></i> Error al conectar con el servidor</div>';
                }
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
<?= $this->endSection() ?>
