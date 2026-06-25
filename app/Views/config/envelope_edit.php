<?= $this->extend('layouts/main') ?>
<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/config_ux.css') ?>?v=1">
<link rel="stylesheet" href="<?= base_url('css/envelope-editor.css') ?>?v=19">
<script src="<?= base_url('js/config-ux.js') ?>?v=1" defer></script>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$tid = (int) ($template->id ?? 0);
$layout = $layout ?? [];
$cols = (int) ($layout['columns'] ?? 4);
$rows = (int) ($layout['rows'] ?? 3);
$sizeKey = (string) ($layout['size_key'] ?? 'dl');
$widthMm = (float) ($layout['width_mm'] ?? 220);
$heightMm = (float) ($layout['height_mm'] ?? 110);
$previewMaxW = 720;
$previewMaxH = 460;
$previewCap = 2.0;
$previewScale = min($previewMaxW / max(1, $widthMm), $previewMaxH / max(1, $heightMm), $previewCap);
$previewPxW = (int) round($widthMm * $previewScale);
$previewPxH = (int) round($heightMm * $previewScale);
$items = is_array($layout['items'] ?? null) ? $layout['items'] : [];
$merges = is_array($layout['merges'] ?? null) ? $layout['merges'] : [];
$elLabels = $element_type_labels ?? \App\Services\EnvelopeLayoutService::elementTypeLabels();
$elSamples = $element_preview_samples ?? \App\Services\EnvelopeLayoutService::elementPreviewSamples();
if ($elLabels === []) {
    $elLabels = \App\Services\EnvelopeLayoutService::defaultFieldLabels();
}
$sizes = $envelope_sizes ?? [];
$imageBaseUrl = (string) ($image_base_url ?? '');
$backUrl = (string) ($back_url ?? site_url('config?tab=sobres'));
$itemsByCell = [];
foreach ($items as $it) {
    if (! is_array($it)) {
        continue;
    }
    $r = (int) ($it['row'] ?? 0);
    $c = (int) ($it['col'] ?? 0);
    $itemsByCell[$r . ',' . $c][] = $it;
}
?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_config'), 'url' => site_url('config')],
    ['label' => 'Sobres', 'url' => $backUrl],
    ['label' => esc($template->name ?? 'Editor')],
]]) ?>

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
<?php
$printTplId = (int) ($print_envelope_template_id ?? 0);
if ($printTplId > 0 && $printTplId !== $tid):
    ?>
<div class="alert alert-warning mb-0">
    <strong>Esta plantilla no es la que se imprime desde los reportes.</strong>
    <p class="mb-2 small">Los cambios que guarde aquí se verán en la vista previa del editor, pero al pulsar <em>Imprimir sobre</em> en un reporte se usa otra plantilla (la configurada en Configuración → Sobres → Plantilla para imprimir).</p>
    <a href="<?= esc(site_url('config?tab=sobres'), 'attr') ?>" class="btn btn-sm btn-warning">Ir a configuración de impresión</a>
</div>
<?php endif; ?>

<?= view('config/partials/config_section_guide', [
    'guide_key' => 'envelope_edit',
    'title' => 'Editor visual del sobre',
    'body' => 'Coloque en la cuadrícula los datos del paciente, logo, código QR y otros campos. El tamaño del sobre se define en milímetros.',
    'steps' => [
        'Arrastre elementos desde la paleta a una celda de la matriz.',
        'Use la vista previa para comprobar el resultado antes de guardar.',
        'Recuerde elegir la plantilla de impresión en Configuración → Sobres.',
    ],
]) ?>

<?= form_open(site_url('config/sobres/save'), [
    'id'       => 'envelope_tpl_form',
    'enctype'  => 'multipart/form-data',
    'onsubmit' => 'return window.envelopePrepareSave && window.envelopePrepareSave();',
]) ?>
<input type="hidden" name="id" value="<?= $tid ?>">
<?php
$layoutJsonInitial = json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
$layoutJsonInitial = is_string($layoutJsonInitial)
    ? str_replace('</textarea>', '<\\/textarea>', $layoutJsonInitial)
    : '{}';
?>
<textarea name="layout_json" id="layout_json" class="d-none" aria-hidden="true"><?= htmlspecialchars($layoutJsonInitial, ENT_SUBSTITUTE | ENT_NOQUOTES, 'UTF-8') ?></textarea>
<div id="envelope-image-uploads" class="d-none" aria-hidden="true"></div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-secondary text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0"><i class="fa-solid fa-envelope-open-text me-2"></i>Editor de sobre</h5>
        <a href="<?= esc($backUrl) ?>" class="btn btn-sm btn-light">Volver a configuración</a>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label" for="tpl_name">Nombre de la plantilla</label>
                <input type="text" class="form-control" name="name" id="tpl_name" required maxlength="120" value="<?= esc($template->name ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="env_size_key">Tamaño del sobre</label>
                <select class="form-select" id="env_size_key">
                    <?php foreach ($sizes as $key => $sz): ?>
                    <option value="<?= esc($key) ?>" <?= $sizeKey === $key ? 'selected' : '' ?>><?= esc($sz['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="env_cols">Columnas</label>
                <input type="number" class="form-control" id="env_cols" min="1" max="8" value="<?= $cols ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="env_rows">Filas</label>
                <input type="number" class="form-control" id="env_rows" min="1" max="12" value="<?= $rows ?>">
            </div>
        </div>
        <div class="row g-3 mb-4" id="env_custom_size_wrap" style="<?= $sizeKey === 'custom' ? '' : 'display:none;' ?>">
            <div class="col-md-3">
                <label class="form-label" for="env_width_mm">Ancho (mm)</label>
                <input type="number" class="form-control" id="env_width_mm" min="50" max="500" step="0.1" value="<?= esc((string) $widthMm) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="env_height_mm">Alto (mm)</label>
                <input type="number" class="form-control" id="env_height_mm" min="50" max="500" step="0.1" value="<?= esc((string) $heightMm) ?>">
            </div>
        </div>

        <h6 class="text-uppercase text-muted small mb-2">1. Campos disponibles</h6>
        <p class="small text-muted mb-2">Arrastre un campo a una celda de la matriz (abajo). También puede hacer clic en una celda vacía para elegir el campo.</p>
        <div id="envelope-field-palette" class="envelope-field-palette mb-4">
            <?php foreach ($elLabels as $fieldType => $fieldLabel): ?>
            <div class="envelope-palette-item"
                 draggable="true"
                 role="button"
                 tabindex="0"
                 data-element-type="<?= esc($fieldType, 'attr') ?>"
                 title="<?= esc($fieldType, 'attr') ?>"><?= esc($fieldLabel) ?></div>
            <?php endforeach; ?>
        </div>
        <?php if ($elLabels === []): ?>
        <p class="small text-danger mb-2">No se pudieron cargar los tipos de campo. Recargue la página o contacte al administrador.</p>
        <?php endif; ?>

        <div class="card border-primary mb-4">
            <div class="card-header bg-primary text-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold small text-uppercase">2. Matriz del sobre</span>
                <span class="badge bg-light text-primary" id="envelope-matrix-dims-badge"><?= (int) $cols ?> columnas × <?= (int) $rows ?> filas</span>
            </div>
            <div class="card-body envelope-matrix-card-body">
                <p class="small text-muted mb-3">
                    Cada celda corresponde a una posición del sobre. Puede colocar <strong>varios campos en la misma celda</strong> (arrastrar o «+ Agregar otro campo…»).
                    También puede <strong>combinar celdas</strong> en horizontal o vertical y ajustar la combinación en el modal de cada campo.
                </p>
                <div id="envelope-grid-editor" class="envelope-matrix-table-wrap" data-cols="<?= (int) $cols ?>" data-rows="<?= (int) $rows ?>">
                    <table class="table table-bordered envelope-matrix-table mb-0">
                        <thead>
                            <tr>
                                <th class="envelope-matrix-corner-th"></th>
                                <?php for ($c = 0; $c < $cols; $c++): ?>
                                <th class="envelope-matrix-col-th text-center">Col <?= $c + 1 ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($r = 0; $r < $rows; $r++): ?>
                            <tr>
                                <th class="envelope-matrix-row-th text-center">F<?= $r + 1 ?></th>
                                <?php for ($c = 0; $c < $cols; $c++):
                                    if (\App\Services\EnvelopeLayoutService::isMatrixCellCovered($r, $c, $items, $merges)) {
                                        continue;
                                    }
                                    $cellKey = $r . ',' . $c;
                                    $cellItems = $itemsByCell[$cellKey] ?? [];
                                    $cellSpan = \App\Services\EnvelopeLayoutService::matrixCellSpan($r, $c, $items, $merges);
                                    $csp = (int) $cellSpan['col_span'];
                                    $rsp = (int) $cellSpan['row_span'];
                                    $mergedCls = ($csp > 1 || $rsp > 1) ? ' envelope-grid-cell-merged' : '';
                                    ?>
                                <td class="envelope-grid-cell<?= $mergedCls ?>"
                                    data-row="<?= $r ?>"
                                    data-col="<?= $c ?>"
                                    <?= $csp > 1 ? ' colspan="' . $csp . '"' : '' ?>
                                    <?= $rsp > 1 ? ' rowspan="' . $rsp . '"' : '' ?>>
                                    <span class="envelope-grid-cell-coord">F<?= $r + 1 ?>·C<?= $c + 1 ?><?= ($csp > 1 || $rsp > 1) ? ' (' . $csp . '×' . $rsp . ')' : '' ?></span>
                                    <?php if ($cellItems === []): ?>
                                    <div class="envelope-grid-cell-empty">
                                        <span>Soltar aquí</span>
                                        <select class="form-select form-select-sm envelope-grid-cell-add" aria-label="Agregar campo">
                                            <option value="">+ Agregar campo…</option>
                                            <?php foreach ($elLabels as $fieldType => $fieldLabel): ?>
                                            <option value="<?= esc($fieldType, 'attr') ?>"><?= esc($fieldLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php else: ?>
                                    <div class="envelope-grid-cell-items">
                                        <?php foreach ($cellItems as $it):
                                            $type = (string) ($it['element_type'] ?? '');
                                            $uid = (string) ($it['uid'] ?? '');
                                            ?>
                                    <div class="envelope-grid-chip" data-uid="<?= esc($uid, 'attr') ?>" draggable="true">
                                        <span class="envelope-grid-chip-title"><?= esc($elLabels[$type] ?? $type) ?></span>
                                    </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="envelope-grid-cell-add-row">
                                        <select class="form-select form-select-sm envelope-grid-cell-add" aria-label="Agregar otro campo">
                                            <option value="">+ Agregar otro campo…</option>
                                            <?php foreach ($elLabels as $fieldType => $fieldLabel): ?>
                                            <option value="<?= esc($fieldType, 'attr') ?>"><?= esc($fieldLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <?php endfor; ?>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-secondary mb-0">
            <div class="card-header bg-light py-2">
                <span class="fw-semibold small text-uppercase text-secondary">Vista previa del sobre</span>
            </div>
            <div class="card-body text-center envelope-preview-card-body">
                <div class="envelope-editor-wrap d-inline-block">
                    <div id="envelope-preview-scaler" class="envelope-preview-scaler mx-auto">
                        <div id="envelope-preview-outer" class="envelope-size-preview mx-auto" style="width: <?= esc((string) $widthMm) ?>mm; height: <?= esc((string) $heightMm) ?>mm;">
                            <span class="envelope-size-preview-label" id="envelope-preview-dims"><?= esc($widthMm) ?> × <?= esc($heightMm) ?> mm</span>
                            <div id="envelope-preview-inner" class="envelope-preview-inner"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-check mb-3">
    <input type="checkbox" class="form-check-input" name="set_as_print_template" id="set_as_print_template" value="1" <?= ($printTplId < 1 || $printTplId === $tid) ? 'checked' : '' ?>>
    <label class="form-check-label" for="set_as_print_template">
        Usar esta plantilla al imprimir sobres desde los reportes
    </label>
</div>
<button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar plantilla</button>
<a href="<?= esc($backUrl) ?>" class="btn btn-outline-secondary">Cancelar</a>
<?= form_close() ?>

<!-- Modal propiedades de celda -->
<div class="modal fade" id="envItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Propiedades del campo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="env_modal_uid">
                <p class="small text-muted mb-3" id="env_modal_type_label"></p>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="env_modal_enabled" checked>
                        <label class="form-check-label" for="env_modal_enabled">Visible</label>
                    </div>
                </div>
                <div class="mb-3" id="env_modal_label_wrap">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="env_modal_show_label" checked>
                        <label class="form-check-label" for="env_modal_show_label" id="env_modal_show_label_lbl">Mostrar etiqueta</label>
                    </div>
                    <div id="env_modal_custom_label_row">
                        <label class="form-label" for="env_modal_custom_label">Etiqueta personalizada</label>
                        <input type="text" class="form-control form-control-sm" id="env_modal_custom_label" placeholder="Vacío = etiqueta por defecto">
                    </div>
                </div>
                <div class="border rounded p-2 mb-3 bg-light" id="env_modal_position_wrap">
                    <div class="small fw-semibold mb-2"><i class="fa-solid fa-arrows-up-down-left-right me-1"></i> Posición en la celda</div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label" for="env_modal_align">Horizontal</label>
                            <select class="form-select form-select-sm" id="env_modal_align">
                                <option value="left">Izquierda</option>
                                <option value="center">Centro</option>
                                <option value="right">Derecha</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="env_modal_valign">Vertical</label>
                            <select class="form-select form-select-sm" id="env_modal_valign">
                                <option value="top">Arriba</option>
                                <option value="middle">Centro</option>
                                <option value="bottom">Abajo</option>
                            </select>
                        </div>
                    </div>
                    <div class="small text-muted mb-1">Márgenes internos (mm)</div>
                    <div class="row g-2">
                        <div class="col-3">
                            <label class="form-label small" for="env_modal_margin_top">Arriba</label>
                            <input type="number" class="form-control form-control-sm" id="env_modal_margin_top" min="0" max="20" step="0.5" value="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label small" for="env_modal_margin_right">Derecha</label>
                            <input type="number" class="form-control form-control-sm" id="env_modal_margin_right" min="0" max="20" step="0.5" value="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label small" for="env_modal_margin_bottom">Abajo</label>
                            <input type="number" class="form-control form-control-sm" id="env_modal_margin_bottom" min="0" max="20" step="0.5" value="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label small" for="env_modal_margin_left">Izquierda</label>
                            <input type="number" class="form-control form-control-sm" id="env_modal_margin_left" min="0" max="20" step="0.5" value="0">
                        </div>
                    </div>
                </div>
                <div id="env_modal_text_wrap">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label" for="env_modal_font_size">Tamaño fuente (pt)</label>
                            <input type="number" class="form-control form-control-sm" id="env_modal_font_size" min="6" max="24" value="10">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="env_modal_text_flow">Dirección del texto</label>
                            <select class="form-select form-select-sm" id="env_modal_text_flow">
                                <option value="horizontal">Horizontal (izquierda → derecha)</option>
                                <option value="vertical_down">Vertical (arriba → abajo)</option>
                                <option value="vertical_up">Vertical (abajo → arriba)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="env_modal_bold">
                            <label class="form-check-label" for="env_modal_bold">Negrita</label>
                        </div>
                    </div>
                </div>
                <div id="env_modal_custom_text_wrap" style="display:none;">
                    <label class="form-label" for="env_modal_custom_value">Texto fijo</label>
                    <input type="text" class="form-control form-control-sm" id="env_modal_custom_value">
                </div>
                <div id="env_modal_qr_wrap" style="display:none;">
                    <label class="form-label" for="env_modal_qr_size">Tamaño QR (mm)</label>
                    <input type="number" class="form-control form-control-sm" id="env_modal_qr_size" min="15" max="80" value="35">
                </div>
                <div id="env_modal_barcode_wrap" style="display:none;">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label" for="env_modal_barcode_width">Ancho del código (mm)</label>
                            <input type="number" class="form-control form-control-sm" id="env_modal_barcode_width" min="25" max="120" value="60">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="env_modal_barcode_height">Altura del código (mm)</label>
                            <input type="number" class="form-control form-control-sm" id="env_modal_barcode_height" min="8" max="40" value="14">
                        </div>
                    </div>
                    <small class="text-muted d-block">Usa el número de orden (CODE128) y el nombre del paciente como en «Imprimir solo código de barras». El tamaño global (%) se toma de Configuración → Orden registrada.</small>
                </div>
                <div id="env_modal_image_wrap" style="display:none;">
                    <label class="form-label" for="env_modal_image_file">Imagen</label>
                    <input type="file" class="form-control form-control-sm" id="env_modal_image_file" accept="image/*" name="">
                    <div id="env_modal_image_preview" class="mt-2"></div>
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <label class="form-label" for="env_modal_img_width_pct">Ancho (%)</label>
                            <input type="range" class="form-range" id="env_modal_img_width_pct" min="5" max="100" value="80">
                            <span class="small text-muted" id="env_modal_img_width_val">80%</span>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="env_modal_img_height_pct">Alto (%)</label>
                            <input type="range" class="form-range" id="env_modal_img_height_pct" min="5" max="100" value="60">
                            <span class="small text-muted" id="env_modal_img_height_val">60%</span>
                        </div>
                    </div>
                </div>
                <div class="border rounded p-2 mb-2 bg-light" id="env_modal_merge_wrap">
                    <div class="small fw-semibold mb-2"><i class="fa-solid fa-table-columns me-1"></i> Combinar celdas</div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label" for="env_modal_col_span">Horizontal (columnas)</label>
                            <input type="number" class="form-control form-control-sm" id="env_modal_col_span" min="1" max="8" value="1">
                            <span class="small text-muted">Cuántas columnas ocupa hacia la derecha</span>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="env_modal_row_span">Vertical (filas)</label>
                            <input type="number" class="form-control form-control-sm" id="env_modal_row_span" min="1" max="12" value="1">
                            <span class="small text-muted">Cuántas filas ocupa hacia abajo</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="env_modal_delete">Quitar campo</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="env_modal_apply">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php
$envelopeBootJson = json_encode([
    'templateId'        => $tid,
    'layout'            => $layout,
    'elementLabels'     => $elLabels,
    'elementSamples'    => $elSamples,
    'envelopeSizes'     => $sizes,
    'imageBaseUrl'      => $imageBaseUrl,
    'previewMarkupUrl'  => site_url('config/sobres/preview-markup'),
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($envelopeBootJson === false) {
    $envelopeBootJson = '{}';
}
?>
<script type="application/json" id="envelope-editor-boot-json"><?= $envelopeBootJson ?></script>
<script>
(function () {
    var el = document.getElementById('envelope-editor-boot-json');
    try {
        window.ENVELOPE_EDITOR_BOOT = el ? JSON.parse(el.textContent || '{}') : {};
    } catch (e) {
        console.error('Envelope editor boot JSON:', e);
        window.ENVELOPE_EDITOR_BOOT = {};
    }
})();
</script>
<script src="<?= base_url('js/vendor/sortable.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="<?= base_url('js/envelope-barcode.js') ?>?v=4"></script>
<script src="<?= base_url('js/envelope-editor.js') ?>?v=25"></script>
<?= $this->endSection() ?>
