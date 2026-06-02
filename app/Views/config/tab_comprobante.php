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
$matrixLabels = $layoutSvc->matrixElementLabels();
$matrixSamples = $layoutSvc->matrixElementSamples();
$matrixPaletteGroups = $layoutSvc->matrixPaletteGroups();
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
$sectionGrids = $layoutSvc->sectionGridsFromLayout($layout);
$matrixSections = ComprobanteLayoutService::MATRIX_SECTIONS;
$styles = is_array($layout['styles'] ?? null) ? $layout['styles'] : ComprobanteLayoutService::DEFAULT_STYLES;
$sectionStyles = is_array($layout['section_styles'] ?? null)
    ? $layout['section_styles']
    : $layoutSvc->buildDefaultSectionStyles($styles);
$dimensions = is_array($layout['dimensions'] ?? null) ? $layout['dimensions'] : ComprobanteLayoutService::DEFAULT_DIMENSIONS;
$sectionSpacing = $layoutSvc->normalizeSectionSpacing($layout['section_spacing'] ?? null);
$sectionStyleKeys = [];
foreach (array_keys($matrixSections) as $sid) {
    $keys = $layoutSvc->styleKeysForMatrixSection($sid);
    if ($sid === 'header') {
        $keys[] = 'body';
    }
    $sectionStyleKeys[$sid] = array_values(array_unique($keys));
}
?>
<div class="tab-pane fade <?= $activeTab === 'comprobante' ? 'show active' : '' ?>" id="tab-comprobante" role="tabpanel">
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fa-solid fa-file-invoice me-2"></i>Personalización de comprobante PDF</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-4">
                Configure colores globales, tipografía por sección y la matriz completa del comprobante.
                Arrastre elementos desde la paleta o entre celdas; haga clic en un elemento para cambiar el nombre, ocultarlo o quitarlo.
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
                    <div class="card border-warning bg-warning bg-opacity-10">
                        <div class="card-body py-3">
                            <div class="form-check mb-2">
                                <input type="hidden" name="comprobante_recibo_num_rango_activo" value="0">
                                <input class="form-check-input comp-preview-sync" type="checkbox" id="comprobante_recibo_num_rango_activo" name="comprobante_recibo_num_rango_activo" value="1" <?= (($config['comprobante_recibo_num_rango_activo'] ?? '0') === '1') ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="comprobante_recibo_num_rango_activo">Numeración propia del recibo (rango inicio–fin)</label>
                            </div>
                            <div class="row g-2 align-items-end" id="comp_recibo_rango_fields">
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label small mb-0" for="comprobante_recibo_num_inicio">Número inicial</label>
                                    <input type="number" class="form-control form-control-sm comp-preview-sync" id="comprobante_recibo_num_inicio" name="comprobante_recibo_num_inicio" min="1" max="99999999" step="1" value="<?= esc((string) ($config['comprobante_recibo_num_inicio'] ?? ''), 'attr') ?>" placeholder="Ej. 1">
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label small mb-0" for="comprobante_recibo_num_fin">Número final</label>
                                    <input type="number" class="form-control form-control-sm comp-preview-sync" id="comprobante_recibo_num_fin" name="comprobante_recibo_num_fin" min="1" max="99999999" step="1" value="<?= esc((string) ($config['comprobante_recibo_num_fin'] ?? ''), 'attr') ?>" placeholder="Ej. 9999">
                                </div>
                                <div class="col-md-6">
                                    <p class="small text-muted mb-0">
                                        Si no activa el rango o deja los campos vacíos, en el PDF se usará el <strong>número de orden del registro</strong> (folio habitual).
                                        Cada recibo nuevo recibe el siguiente número del rango; al agotarse el rango vuelve al número de orden.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label for="comprobante_footer_note" class="form-label">Texto final (pie del comprobante)</label>
                    <textarea class="form-control comp-preview-sync" id="comprobante_footer_note" name="comprobante_footer_note" rows="2" maxlength="600"><?= esc($config['comprobante_footer_note'] ?? 'Documento interno de constancia de pago emitido por el laboratorio. No reemplaza un comprobante fiscal electrónico ni factura validada ante el SIN.') ?></textarea>
                </div>
            </div>

            <p class="text-muted small mb-3">
                Cada sección incluye la <strong>matriz de diseño</strong> (arrastre de elementos) y, a su lado, los <strong>estilos tipográficos</strong> de los elementos de esa parte.
                Use <strong>Espacio entre filas</strong>, <strong>Margen inferior entre secciones</strong> y el <strong>separador</strong> opcional para el espacio entre bloques (encabezado, cliente, tabla, etc.).
                La vista previa se actualiza al mover elementos o cambiar estilos. Pulse <strong>Guardar estilo de comprobante</strong> para el PDF.
            </p>

            <div id="comp-sections-editor">
            <?php foreach ($matrixSections as $sectionId => $sectionDef):
                $secGrid = $sectionGrids[$sectionId] ?? ['columns' => $sectionDef['default_columns'], 'rows' => $sectionDef['default_rows'], 'items' => []];
                $secCols = max(1, (int) ($secGrid['columns'] ?? $sectionDef['default_columns']));
                $secRows = max(1, (int) ($secGrid['rows'] ?? $sectionDef['default_rows']));
                $secSpace = $sectionSpacing[$sectionId] ?? ComprobanteLayoutService::DEFAULT_SECTION_SPACING[$sectionId];
                $secRowGap = (float) ($secSpace['row_gap_pt'] ?? 4);
                $secMarginBottom = (float) ($secSpace['margin_bottom_pt'] ?? 12);
                $secSepEnabled = ! empty($secSpace['separator_enabled']);
                $secSepStyle = (string) ($secSpace['separator_style'] ?? 'line');
                $secSepColor = \App\Services\LayoutService::htmlColorPickerValue($secSpace['separator_color'] ?? '', '#cbd5e1');
                $secSepThick = (float) ($secSpace['separator_thickness_pt'] ?? 1);
                $secSepWidth = (float) ($secSpace['separator_width_pct'] ?? 100);
                $secSepGap = (float) ($secSpace['separator_gap_pt'] ?? 6);
                $sectionCardClass = match ($sectionId) {
                    'header' => 'border-primary',
                    'client' => 'border-info',
                    'table'  => 'border-success',
                    'totals' => 'border-warning',
                    default  => 'border-secondary',
                };
                $sectionStyleGroups = $layoutSvc->styleGroupsForMatrixSection($sectionId);
                ?>
            <div class="card <?= $sectionCardClass ?> mb-4 comp-section-card" data-section="<?= esc($sectionId, 'attr') ?>">
                <div class="card-header py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h6 class="mb-0"><i class="fa-solid fa-table-cells me-2"></i><?= esc($sectionDef['label']) ?></h6>
                    <span class="badge bg-light text-dark comp-section-dims-badge" data-section="<?= esc($sectionId, 'attr') ?>">
                        <?= $secCols ?> columnas × <?= $secRows ?> filas
                    </span>
                </div>
                <div class="card-body comp-section-body" data-section="<?= esc($sectionId, 'attr') ?>">
                    <div class="row g-2 mb-3">
                        <div class="col-md-2 col-6">
                            <label class="form-label small" for="comp_sec_<?= esc($sectionId, 'attr') ?>_cols">Columnas</label>
                            <input type="number"
                                   class="form-control form-control-sm comp-section-cols"
                                   id="comp_sec_<?= esc($sectionId, 'attr') ?>_cols"
                                   data-section="<?= esc($sectionId, 'attr') ?>"
                                   min="<?= (int) $sectionDef['col_min'] ?>"
                                   max="<?= (int) $sectionDef['col_max'] ?>"
                                   value="<?= $secCols ?>">
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label small" for="comp_sec_<?= esc($sectionId, 'attr') ?>_rows">Filas</label>
                            <input type="number"
                                   class="form-control form-control-sm comp-section-rows"
                                   id="comp_sec_<?= esc($sectionId, 'attr') ?>_rows"
                                   data-section="<?= esc($sectionId, 'attr') ?>"
                                   min="<?= (int) $sectionDef['row_min'] ?>"
                                   max="<?= (int) $sectionDef['row_max'] ?>"
                                   value="<?= $secRows ?>">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small" for="comp_sec_<?= esc($sectionId, 'attr') ?>_row_gap">Espacio entre filas (pt)</label>
                            <input type="number"
                                   class="form-control form-control-sm comp-section-spacing"
                                   id="comp_sec_<?= esc($sectionId, 'attr') ?>_row_gap"
                                   data-section="<?= esc($sectionId, 'attr') ?>"
                                   data-spacing-field="row_gap_pt"
                                   min="0"
                                   max="24"
                                   step="0.5"
                                   value="<?= esc((string) $secRowGap, 'attr') ?>">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small" for="comp_sec_<?= esc($sectionId, 'attr') ?>_margin"><?= $sectionId === 'header' ? 'Margen inferior tras encabezado y separador (pt)' : 'Margen inferior entre secciones (pt)' ?></label>
                            <input type="number"
                                   class="form-control form-control-sm comp-section-spacing"
                                   id="comp_sec_<?= esc($sectionId, 'attr') ?>_margin"
                                   data-section="<?= esc($sectionId, 'attr') ?>"
                                   data-spacing-field="margin_bottom_pt"
                                   min="0"
                                   max="48"
                                   step="0.5"
                                   value="<?= esc((string) $secMarginBottom, 'attr') ?>">
                        </div>
                    </div>

                    <div class="row g-2 mb-3 comp-section-separator-row align-items-end">
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input comp-section-spacing"
                                       id="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_on"
                                       data-section="<?= esc($sectionId, 'attr') ?>"
                                       data-spacing-field="separator_enabled"
                                       value="1"
                                       <?= $secSepEnabled ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_on">Separador después de esta sección</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small" for="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_style">Tipo de separador</label>
                            <select class="form-select form-select-sm comp-section-spacing"
                                    id="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_style"
                                    data-section="<?= esc($sectionId, 'attr') ?>"
                                    data-spacing-field="separator_style">
                                <?php foreach (ComprobanteLayoutService::SEPARATOR_STYLES as $sepKey => $sepLabel): ?>
                                <option value="<?= esc($sepKey, 'attr') ?>" <?= $secSepStyle === $sepKey ? 'selected' : '' ?>><?= esc($sepLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label small" for="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_color">Color</label>
                            <input type="color"
                                   class="form-control form-control-color comp-section-spacing w-100"
                                   id="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_color"
                                   data-section="<?= esc($sectionId, 'attr') ?>"
                                   data-spacing-field="separator_color"
                                   value="<?= esc($secSepColor, 'attr') ?>">
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label small" for="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_thick">Grosor (pt)</label>
                            <input type="number"
                                   class="form-control form-control-sm comp-section-spacing"
                                   id="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_thick"
                                   data-section="<?= esc($sectionId, 'attr') ?>"
                                   data-spacing-field="separator_thickness_pt"
                                   min="0.5"
                                   max="4"
                                   step="0.5"
                                   value="<?= esc((string) $secSepThick, 'attr') ?>">
                        </div>
                        <div class="col-md-2 col-6">
                            <label class="form-label small" for="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_width">Ancho (%)</label>
                            <input type="number"
                                   class="form-control form-control-sm comp-section-spacing"
                                   id="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_width"
                                   data-section="<?= esc($sectionId, 'attr') ?>"
                                   data-spacing-field="separator_width_pct"
                                   min="20"
                                   max="100"
                                   step="5"
                                   value="<?= esc((string) $secSepWidth, 'attr') ?>">
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small" for="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_gap">Espacio antes del separador (pt)</label>
                            <input type="number"
                                   class="form-control form-control-sm comp-section-spacing"
                                   id="comp_sec_<?= esc($sectionId, 'attr') ?>_sep_gap"
                                   data-section="<?= esc($sectionId, 'attr') ?>"
                                   data-spacing-field="separator_gap_pt"
                                   min="0"
                                   max="24"
                                   step="0.5"
                                   value="<?= esc((string) $secSepGap, 'attr') ?>">
                        </div>
                    </div>

                    <div class="row g-3 comp-section-split">
                        <div class="col-lg-6 comp-section-layout-col">
                            <h6 class="text-uppercase text-muted small mb-2">Matriz de diseño — arrastre cualquier elemento</h6>
                            <div class="comp-field-palette comp-section-palette mb-3" data-section-palette="<?= esc($sectionId, 'attr') ?>">
                                <?php foreach ($matrixLabels as $matrixType => $matrixLabel):
                                    $meta = ComprobanteLayoutService::MATRIX_ELEMENT_DEFINITIONS[$matrixType] ?? null;
                                    if ($meta === null) {
                                        continue;
                                    }
                                    $suggestedSection = $layoutSvc->sectionIdForElementType($matrixType);
                                    $suggestedLabel = $matrixSections[$suggestedSection]['label'] ?? $suggestedSection;
                                    $elStyleKey = (string) ($meta['style_key'] ?? '');
                                    $elStyleLabel = $styleLabels[$elStyleKey] ?? $elStyleKey;
                                    ?>
                                <div class="comp-palette-item"
                                     draggable="true"
                                     role="button"
                                     tabindex="0"
                                     data-element-type="<?= esc($matrixType, 'attr') ?>"
                                     data-section="<?= esc($sectionId, 'attr') ?>"
                                     title="<?= esc($matrixType . ' → estilo: ' . $elStyleKey . ' · sugerido: ' . $suggestedLabel, 'attr') ?>">
                                    <?= esc($matrixLabel) ?>
                                    <span class="comp-palette-style-hint"><?= esc($elStyleLabel) ?><?= $suggestedSection !== $sectionId ? ' · ' . esc($suggestedLabel) : '' ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="comp-matrix-table-wrap">
                                <table class="table table-bordered comp-matrix-table mb-0 comp-section-matrix"
                                       id="comp_matrix_<?= esc($sectionId, 'attr') ?>"
                                       data-section="<?= esc($sectionId, 'attr') ?>">
                                    <thead>
                                        <tr>
                                            <th class="comp-matrix-corner"></th>
                                            <?php for ($c = 0; $c < $secCols; $c++): ?>
                                            <th class="comp-matrix-col-th text-center small">Col <?= $c + 1 ?></th>
                                            <?php endfor; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php for ($r = 0; $r < $secRows; $r++): ?>
                                        <tr>
                                            <th class="comp-matrix-row-th text-center small">F<?= $r + 1 ?></th>
                                            <?php for ($c = 0; $c < $secCols; $c++): ?>
                                            <td class="comp-matrix-cell"
                                                data-section="<?= esc($sectionId, 'attr') ?>"
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
                        <div class="col-lg-6 comp-section-styles-col">
                            <?= view('config/_comprobante_section_styles', [
                                'sectionId'           => $sectionId,
                                'sectionStyleGroups'  => $sectionStyleGroups,
                                'layoutSvc'           => $layoutSvc,
                                'styles'              => $styles,
                                'sectionStyles'       => $sectionStyles,
                                'dimensions'          => $dimensions,
                                'styleLabels'         => $styleLabels,
                                'fontFamilies'        => $fontFamilies,
                                'fontWeights'         => $fontWeights,
                                'fontStyles'          => $fontStyles,
                                'textTransforms'      => $textTransforms,
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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
                <h6 class="modal-title">Elemento del comprobante</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Tipo: <strong id="comp_modal_type_label">—</strong><br><code id="comp_modal_type_code" class="small">—</code></p>
                <div class="mb-2">
                    <label class="form-label small" for="comp_modal_label">Nombre / texto visible</label>
                    <input type="text" class="form-control form-control-sm" id="comp_modal_label" maxlength="80">
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="comp_modal_enabled" checked>
                    <label class="form-check-label small" for="comp_modal_enabled">Mostrar en el comprobante</label>
                </div>
                <div class="form-check mb-2" id="comp_modal_show_label_wrap">
                    <input class="form-check-input" type="checkbox" id="comp_modal_show_label" checked>
                    <label class="form-check-label small" for="comp_modal_show_label">Mostrar etiqueta (datos del cliente)</label>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small" for="comp_modal_col_span">Ancho (columnas)</label>
                        <input type="number" class="form-control form-control-sm" id="comp_modal_col_span" min="1" max="4" value="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="comp_modal_row_span">Alto (filas)</label>
                        <input type="number" class="form-control form-control-sm" id="comp_modal_row_span" min="1" max="8" value="1">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small" for="comp_modal_align_h">Alineación horizontal</label>
                        <select class="form-select form-select-sm" id="comp_modal_align_h">
                            <?php foreach (ComprobanteLayoutService::ALIGN_H_LABELS as $val => $lbl) : ?>
                                <option value="<?= esc($val) ?>"><?= esc($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="comp_modal_align_v">Alineación vertical</label>
                        <select class="form-select form-select-sm" id="comp_modal_align_v">
                            <?php foreach (ComprobanteLayoutService::ALIGN_V_LABELS as $val => $lbl) : ?>
                                <option value="<?= esc($val) ?>"><?= esc($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-danger" id="comp_modal_remove" title="Quita el elemento de la matriz; puede volver a arrastrarlo desde la paleta">Quitar de la matriz</button>
                <button type="button" class="btn btn-sm btn-primary" id="comp_modal_save">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<?php
$compBootJson = json_encode([
    'layout'              => $layout,
    'matrixSections'      => $matrixSections,
    'fieldLabels'         => $fieldLabels,
    'fieldSamples'        => $fieldSamples,
    'matrixElements'      => $layoutSvc->matrixElementsForEditor(),
    'matrixPaletteGroups' => $matrixPaletteGroups,
    'matrixLabels'        => $matrixLabels,
    'matrixSamples'       => $matrixSamples,
    'styleLabels'         => $styleLabels,
    'companyName'         => trim((string) ($config['company'] ?? 'Laboratorio')),
    'defaultStyles'       => ComprobanteLayoutService::DEFAULT_STYLES,
    'defaultDimensions'      => ComprobanteLayoutService::DEFAULT_DIMENSIONS,
    'defaultSectionSpacing' => ComprobanteLayoutService::DEFAULT_SECTION_SPACING,
    'sectionStyleKeys'      => $sectionStyleKeys,
    'alignHLabels'          => ComprobanteLayoutService::ALIGN_H_LABELS,
    'alignVLabels'          => ComprobanteLayoutService::ALIGN_V_LABELS,
    'reciboNumeroPreview' => (new \App\Services\ComprobanteReciboNumeroService())->previewNumero($config),
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($compBootJson === false) {
    $compBootJson = '{}';
}
?>
<script type="application/json" id="comprobante-editor-boot-json"><?= $compBootJson ?></script>
