<?php
/**
 * Pestaña Estilo comprobante (fragmento incluido desde config/manage).
 *
 * @var array<string, mixed> $config
 * @var string               $activeTab
 */

use App\Services\ComprobanteLayoutService;
use App\Services\LayoutService;

$activeTab = $activeTab ?? 'comprobante';
$layoutSvc = new ComprobanteLayoutService();
$layout = $layoutSvc->layoutFromConfig($config);
$fieldLabels = $layoutSvc->fieldTypeLabels();
$styleLabels = ComprobanteLayoutService::STYLE_ELEMENT_LABELS;
$fieldSamples = ComprobanteLayoutService::FIELD_PREVIEW_SAMPLES;
$fontFamilies = ComprobanteLayoutService::ALLOWED_FONT_FAMILIES;
$fontWeights = ComprobanteLayoutService::ALLOWED_FONT_WEIGHTS;
$fontStyles = ComprobanteLayoutService::ALLOWED_FONT_STYLES;
$textTransforms = [
    'none'       => 'Normal',
    'uppercase'  => 'MAYÚSCULAS',
    'lowercase'  => 'minúsculas',
    'capitalize' => 'Tipo título',
];
$primaryColor = LayoutService::htmlColorPickerValue($config['comprobante_primary_color'] ?? '', '#0f766e');
$secondaryColor = LayoutService::htmlColorPickerValue($config['comprobante_secondary_color'] ?? '', '#134e4a');
$textColor = LayoutService::htmlColorPickerValue($config['comprobante_text_color'] ?? '', '#1e293b');
$clientGrid = is_array($layout['client_grid'] ?? null) ? $layout['client_grid'] : [];
$gridCols = max(1, (int) ($clientGrid['columns'] ?? 2));
$gridRows = max(1, (int) ($clientGrid['rows'] ?? 3));
$styles = is_array($layout['styles'] ?? null) ? $layout['styles'] : ComprobanteLayoutService::DEFAULT_STYLES;
?>
<div class="tab-pane fade <?= $activeTab === 'comprobante' ? 'show active' : '' ?>" id="tab-comprobante" role="tabpanel">
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fa-solid fa-file-invoice me-2"></i>Personalización de comprobante PDF</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-4">
                Configure colores globales, tipografía por elemento y la disposición de datos del bloque «Cliente y atención».
                Arrastre campos desde la paleta hacia la matriz; haga clic en una celda ocupada para editar etiqueta y opciones.
            </p>

            <?= form_open(site_url('config/saveComprobanteStyle'), ['id' => 'form_comprobante_style']) ?>
            <?= csrf_field() ?>
            <input type="hidden" name="comprobante_layout_json" id="comprobante_layout_json" value="">

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="comprobante_primary_color" class="form-label">Color principal</label>
                    <input type="color" class="form-control form-control-color comp-color-sync" id="comprobante_primary_color" name="comprobante_primary_color" value="<?= esc($primaryColor, 'attr') ?>" data-comp-preview="primary">
                </div>
                <div class="col-md-4">
                    <label for="comprobante_secondary_color" class="form-label">Color secundario</label>
                    <input type="color" class="form-control form-control-color comp-color-sync" id="comprobante_secondary_color" name="comprobante_secondary_color" value="<?= esc($secondaryColor, 'attr') ?>" data-comp-preview="secondary">
                </div>
                <div class="col-md-4">
                    <label for="comprobante_text_color" class="form-label">Color de texto base</label>
                    <input type="color" class="form-control form-control-color comp-color-sync" id="comprobante_text_color" name="comprobante_text_color" value="<?= esc($textColor, 'attr') ?>" data-comp-preview="text">
                </div>
                <div class="col-md-8">
                    <label for="comprobante_tagline" class="form-label">Subtítulo</label>
                    <input type="text" class="form-control comp-preview-sync" id="comprobante_tagline" name="comprobante_tagline" maxlength="120" value="<?= esc($config['comprobante_tagline'] ?? 'Constancia de pago') ?>" placeholder="Ej: Recibo oficial del laboratorio">
                    <small class="text-muted">Se muestra bajo el nombre del laboratorio en el PDF.</small>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="comprobante_show_doctor" value="0">
                        <input class="form-check-input comp-preview-sync" type="checkbox" id="comprobante_show_doctor" name="comprobante_show_doctor" value="1" <?= (($config['comprobante_show_doctor'] ?? '1') === '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="comprobante_show_doctor">Mostrar médico en comprobante</label>
                    </div>
                </div>
                <div class="col-12">
                    <label for="comprobante_footer_note" class="form-label">Texto final (pie del comprobante)</label>
                    <textarea class="form-control comp-preview-sync" id="comprobante_footer_note" name="comprobante_footer_note" rows="2" maxlength="600"><?= esc($config['comprobante_footer_note'] ?? 'Documento interno de constancia de pago emitido por el laboratorio. No reemplaza un comprobante fiscal electrónico ni factura validada ante el SIN.') ?></textarea>
                </div>
            </div>

            <div class="card border-primary mb-4">
                <div class="card-header bg-primary bg-opacity-10 py-2">
                    <h6 class="mb-0 text-primary"><i class="fa-solid fa-table me-2"></i>Matriz de estilos tipográficos</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0 comp-style-matrix" id="comp_style_matrix">
                            <thead class="table-light">
                                <tr class="small">
                                    <th style="min-width:11rem;">Elemento</th>
                                    <th style="min-width:8rem;">Fuente</th>
                                    <th style="width:5.5rem;">Tamaño (pt)</th>
                                    <th style="width:6rem;">Grosor</th>
                                    <th style="width:6rem;">Estilo</th>
                                    <th style="width:7rem;">Transformación</th>
                                    <th style="width:4.5rem;">Color</th>
                                    <th style="min-width:9rem;">Vista previa</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($styleLabels as $styleKey => $styleLabel):
                                $st = $styles[$styleKey] ?? ComprobanteLayoutService::DEFAULT_STYLES[$styleKey] ?? ComprobanteLayoutService::DEFAULT_STYLES['body'];
                                $stColor = LayoutService::htmlColorPickerValue($st['color'] ?? '#1e293b', '#1e293b');
                                ?>
                                <tr data-style-key="<?= esc($styleKey, 'attr') ?>">
                                    <td class="small fw-semibold"><?= esc($styleLabel) ?><br><code class="text-muted" style="font-size:.68rem;"><?= esc($styleKey) ?></code></td>
                                    <td>
                                        <select class="form-select form-select-sm comp-style-input" data-style-field="font_family">
                                            <?php foreach ($fontFamilies as $ff): ?>
                                            <option value="<?= esc($ff, 'attr') ?>" <?= ($st['font_family'] ?? '') === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm comp-style-input" data-style-field="font_size_pt" min="6" max="24" step="0.5" value="<?= esc((string) ($st['font_size_pt'] ?? 10), 'attr') ?>">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm comp-style-input" data-style-field="font_weight">
                                            <?php foreach ($fontWeights as $fw): ?>
                                            <option value="<?= esc($fw, 'attr') ?>" <?= ($st['font_weight'] ?? '') === $fw ? 'selected' : '' ?>><?= esc($fw) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm comp-style-input" data-style-field="font_style">
                                            <?php foreach ($fontStyles as $fs): ?>
                                            <option value="<?= esc($fs, 'attr') ?>" <?= ($st['font_style'] ?? '') === $fs ? 'selected' : '' ?>><?= esc(ucfirst($fs)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm comp-style-input" data-style-field="text_transform">
                                            <?php foreach ($textTransforms as $tk => $tv): ?>
                                            <option value="<?= esc($tk, 'attr') ?>" <?= ($st['text_transform'] ?? 'none') === $tk ? 'selected' : '' ?>><?= esc($tv) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="color" class="form-control form-control-color comp-style-input comp-style-color" data-style-field="color" value="<?= esc($stColor, 'attr') ?>">
                                    </td>
                                    <td>
                                        <span class="comp-style-preview-sample small px-1 py-0 d-inline-block" data-style-key="<?= esc($styleKey, 'attr') ?>">Aa Bb 123</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-info mb-4">
                <div class="card-header bg-info bg-opacity-25 py-2">
                    <h6 class="mb-0"><i class="fa-solid fa-grip-vertical me-2"></i>Matriz «Cliente y atención» — arrastrar campos</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <label class="form-label" for="comp_grid_cols">Columnas</label>
                            <input type="number" class="form-control" id="comp_grid_cols" min="1" max="4" value="<?= $gridCols ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="comp_grid_rows">Filas</label>
                            <input type="number" class="form-control" id="comp_grid_rows" min="1" max="8" value="<?= $gridRows ?>">
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted small mb-2">Campos disponibles</h6>
                    <p class="small text-muted mb-2">Arrastre un campo a una celda de la matriz. El nombre técnico (<code>element_type</code>) se muestra al pasar el cursor.</p>
                    <div id="comp-field-palette" class="comp-field-palette mb-3">
                        <?php foreach ($fieldLabels as $fieldType => $fieldLabel): ?>
                        <div class="comp-palette-item"
                             draggable="true"
                             role="button"
                             tabindex="0"
                             data-element-type="<?= esc($fieldType, 'attr') ?>"
                             title="<?= esc($fieldType, 'attr') ?>"><?= esc($fieldLabel) ?></div>
                        <?php endforeach; ?>
                    </div>

                    <div class="comp-matrix-table-wrap">
                        <table class="table table-bordered comp-matrix-table mb-0" id="comp_client_matrix">
                            <thead>
                                <tr>
                                    <th class="comp-matrix-corner"></th>
                                    <?php for ($c = 0; $c < $gridCols; $c++): ?>
                                    <th class="comp-matrix-col-th text-center small">Col <?= $c + 1 ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php for ($r = 0; $r < $gridRows; $r++): ?>
                                <tr>
                                    <th class="comp-matrix-row-th text-center small">F<?= $r + 1 ?></th>
                                    <?php for ($c = 0; $c < $gridCols; $c++): ?>
                                    <td class="comp-matrix-cell"
                                        data-row="<?= $r ?>"
                                        data-col="<?= $c ?>"
                                        tabindex="0"></td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-secondary mb-4">
                <div class="card-header bg-body-secondary py-2">
                    <h6 class="mb-0"><i class="fa-solid fa-eye me-2"></i>Vista previa del comprobante</h6>
                </div>
                <div class="card-body comp-preview-wrap">
                    <div id="comp_receipt_preview" class="comp-receipt-preview"></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Guardar estilo de comprobante</button>
            <?= form_close() ?>
        </div>
    </div>
</div>

<div class="modal fade" id="comp_field_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Campo en celda</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Tipo: <strong id="comp_modal_type_label">—</strong><br><code id="comp_modal_type_code" class="small">—</code></p>
                <div class="mb-2">
                    <label class="form-label small" for="comp_modal_label">Etiqueta visible</label>
                    <input type="text" class="form-control form-control-sm" id="comp_modal_label" maxlength="80">
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="comp_modal_show_label" checked>
                    <label class="form-check-label small" for="comp_modal_show_label">Mostrar etiqueta</label>
                </div>
                <div class="mb-2">
                    <label class="form-label small" for="comp_modal_col_span">Ancho (columnas)</label>
                    <input type="number" class="form-control form-control-sm" id="comp_modal_col_span" min="1" max="4" value="1">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-danger" id="comp_modal_remove">Quitar</button>
                <button type="button" class="btn btn-sm btn-primary" id="comp_modal_save" data-bs-dismiss="modal">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<?php
$compBootJson = json_encode([
    'layout'         => $layout,
    'fieldLabels'    => $fieldLabels,
    'fieldSamples'   => $fieldSamples,
    'styleLabels'    => $styleLabels,
    'companyName'    => trim((string) ($config['company'] ?? 'Laboratorio')),
    'defaultStyles'  => ComprobanteLayoutService::DEFAULT_STYLES,
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($compBootJson === false) {
    $compBootJson = '{}';
}
?>
<script type="application/json" id="comprobante-editor-boot-json"><?= $compBootJson ?></script>
