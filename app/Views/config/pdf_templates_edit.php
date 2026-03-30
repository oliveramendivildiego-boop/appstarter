<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Editar plantilla PDF<?= $this->endSection() ?>
<?= $this->section('head_extra') ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<?= $this->endSection() ?>
<?php
$elLabels = $element_type_labels ?? \App\Services\ReportPdfLayoutService::elementTypeLabels();
$elSamples = $element_preview_samples ?? \App\Services\ReportPdfLayoutService::elementPreviewSamples();
$secLayouts = $layout['section_layouts'] ?? \App\Services\ReportPdfLayoutService::defaultSectionLayoutsStatic();
$hCols = max(1, min(6, (int) ($secLayouts['header']['columns'] ?? 3)));
$pCols = max(1, min(6, (int) ($secLayouts['patient_doctor']['columns'] ?? 2)));
$fCols = max(1, min(6, (int) ($secLayouts['footer']['columns'] ?? 3)));
$instHeader = [];
$instPatient = [];
$instFooter = [];
foreach ($layout['instances'] ?? [] as $inst) {
    if (! is_array($inst)) {
        continue;
    }
    $s = (string) ($inst['section'] ?? '');
    if ($s === 'header') {
        $instHeader[] = $inst;
    } elseif ($s === 'patient_doctor') {
        $instPatient[] = $inst;
    } elseif ($s === 'footer') {
        $instFooter[] = $inst;
    }
}
$mm = $layout['margins_mm'] ?? \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
$wm = $layout['watermark'] ?? \App\Services\ReportPdfLayoutService::defaultWatermarkStatic();
$wmPreview = null;
if (! empty($wm['file'])) {
    $wmPreview = \App\Services\ReportPdfLayoutService::getWatermarkDataUriForLayout([
        'watermark' => array_merge($wm, ['enabled' => true]),
    ]);
}
$labelsShort = [
    'paciente_nombre'   => 'Paciente:',
    'paciente_edad'     => 'Edad:',
    'paciente_telefono' => 'Teléfono:',
    'medico'            => 'Médico:',
    'fecha_ingreso'     => 'Fecha:',
    'numero_orden'      => 'No. Orden:',
];
?>
<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_config'), 'url' => site_url('config')],
    ['label' => 'Plantillas PDF', 'url' => site_url('config/pdf-templates')],
    ['label' => esc($template->name ?? ''), 'url' => ''],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show"><?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show"><?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<h3 class="mb-2">Diseño: <?= esc($template->name ?? '') ?></h3>
<p class="text-muted">Configure el <strong>número de columnas</strong> por zona. Arrastre elementos entre encabezado, paciente/médico y pie; puede <strong>duplicar</strong> cualquier dato (por ejemplo el logo en el pie). El orden en cada lista define el apilado dentro de la columna.</p>

<?= form_open(site_url('config/pdf-templates/save'), ['id' => 'pdf_tpl_form', 'enctype' => 'multipart/form-data']) ?>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($template->id ?? 0) ?>">
    <input type="hidden" name="layout_json" id="layout_json" value="">

<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0">Bloques del documento (orden vertical)</h5>
    </div>
    <div class="card-body">
        <ul id="pdf-block-list" class="list-group pdf-block-sortable">
            <?php foreach (($layout['blocks'] ?? []) as $b):
                $bid = $b['id'] ?? '';
                $label = $block_labels[$bid] ?? $bid;
                ?>
            <li class="list-group-item d-flex align-items-center gap-3 pdf-block-item" data-block-id="<?= esc($bid) ?>">
                <span class="text-muted pdf-drag-handle" title="Arrastrar" style="cursor: grab;"><i class="fa-solid fa-grip-vertical fa-lg"></i></span>
                <div class="form-check mb-0">
                    <input class="form-check-input pdf-block-enabled" type="checkbox" id="en_<?= esc($bid) ?>" <?= ! empty($b['enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="en_<?= esc($bid) ?>">Incluir bloque</label>
                </div>
                <div class="flex-grow-1">
                    <strong><?= esc($label) ?></strong>
                    <div class="small text-muted font-monospace"><?= esc($bid) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Márgenes de la hoja (mm)</h5>
    </div>
    <div class="card-body">
        <p class="small text-muted">Espacio en blanco respecto al borde de la página al generar el PDF (0–50 mm).</p>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <label class="form-label small" for="margin_top">Arriba</label>
                <input type="number" class="form-control" id="margin_top" min="0" max="50" step="0.5" value="<?= esc((string) ($mm['top'] ?? 15)) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="margin_right">Derecha</label>
                <input type="number" class="form-control" id="margin_right" min="0" max="50" step="0.5" value="<?= esc((string) ($mm['right'] ?? 15)) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="margin_bottom">Abajo</label>
                <input type="number" class="form-control" id="margin_bottom" min="0" max="50" step="0.5" value="<?= esc((string) ($mm['bottom'] ?? 15)) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="margin_left">Izquierda</label>
                <input type="number" class="form-control" id="margin_left" min="0" max="50" step="0.5" value="<?= esc((string) ($mm['left'] ?? 15)) ?>">
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border">
        <h5 class="mb-0">Marca de agua (centro de la hoja)</h5>
    </div>
    <div class="card-body">
        <p class="small text-muted">Imagen semitransparente detrás del contenido al generar <strong>PDF</strong> o <strong>imprimir</strong> con esta plantilla.</p>
        <input type="hidden" name="watermark_file_rel" id="watermark_file_rel" value="<?= esc($wm['file'] ?? '') ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="wm_enabled" <?= ! empty($wm['enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="wm_enabled">Usar marca de agua</label>
                </div>
                <label class="form-label small" for="wm_opacity">Opacidad (0,05 – 0,9)</label>
                <input type="number" class="form-control" id="wm_opacity" min="0.05" max="0.9" step="0.01" value="<?= esc((string) ($wm['opacity'] ?? 0.12)) ?>">
                <label class="form-label small mt-2" for="wm_size">Tamaño (% del ancho de la hoja)</label>
                <input type="number" class="form-control" id="wm_size" min="10" max="95" value="<?= (int) ($wm['size_percent'] ?? 45) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small" for="watermark_upload">Subir imagen (PNG, JPG, GIF, WebP; máx. 2&nbsp;MB)</label>
                <input type="file" class="form-control" name="watermark_upload" id="watermark_upload" accept="image/jpeg,image/png,image/gif,image/webp">
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="watermark_remove" id="watermark_remove" value="1">
                    <label class="form-check-label text-danger" for="watermark_remove">Quitar imagen de marca de agua</label>
                </div>
                <?php if ($wmPreview): ?>
                <p class="small mb-1 mt-2 text-muted">Archivo actual:</p>
                <img src="<?= esc($wmPreview, 'attr') ?>" alt="" class="img-thumbnail" style="max-height: 120px;">
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4" id="pdf-editor-instances">
    <div class="card-header bg-light border">
        <h5 class="mb-0">Elementos del PDF</h5>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-8">
                <label class="form-label small mb-1" for="add_element_type">Añadir elemento al diseño</label>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <select class="form-select form-select-sm" id="add_element_type" style="max-width: 22rem;">
                        <?php foreach (\App\Services\ReportPdfLayoutService::ELEMENT_TYPES as $tid):
                            if (! isset($elLabels[$tid])) {
                                continue;
                            } ?>
                        <option value="<?= esc($tid, 'attr') ?>"><?= esc($elLabels[$tid]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-select form-select-sm" id="add_element_section" style="max-width: 14rem;">
                        <option value="header">Encabezado</option>
                        <option value="patient_doctor">Paciente / médico</option>
                        <option value="footer">Pie de página</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" id="btn_add_element"><i class="fa-solid fa-plus me-1"></i> Añadir</button>
                </div>
            </div>
        </div>

        <div class="card border-info mb-4">
            <div class="card-header bg-info text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="fw-semibold">Encabezado</span>
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 small text-white-50" for="sec_cols_header">Columnas</label>
                    <input type="number" class="form-control form-control-sm text-dark" id="sec_cols_header" min="1" max="6" value="<?= (int) $hCols ?>" style="width: 4.5rem;">
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <ul id="instance-list-header" class="list-group pdf-instance-sortable" data-section="header">
                            <?php foreach ($instHeader as $inst): ?>
                                <?= view('config/partials/pdf_instance_row', ['inst' => $inst, 'col_count' => $hCols, 'elLabels' => $elLabels]) ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="text-uppercase text-muted small">Vista previa</h6>
                        <div class="pdf-preview-sheet border rounded shadow-sm bg-white mx-auto">
                            <div class="pdf-preview-sheet-bar small text-white px-2 py-1" style="background:#0dcaf0;">Encabezado</div>
                            <div class="p-2" id="pdf-preview-header-block"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-primary mb-4">
            <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="fw-semibold">Paciente y médico</span>
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 small text-white-50" for="sec_cols_patient">Columnas</label>
                    <input type="number" class="form-control form-control-sm text-dark" id="sec_cols_patient" min="1" max="6" value="<?= (int) $pCols ?>" style="width: 4.5rem;">
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <ul id="instance-list-patient" class="list-group pdf-instance-sortable" data-section="patient_doctor">
                            <?php foreach ($instPatient as $inst): ?>
                                <?= view('config/partials/pdf_instance_row', ['inst' => $inst, 'col_count' => $pCols, 'elLabels' => $elLabels]) ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="text-uppercase text-muted small">Vista previa</h6>
                        <div class="pdf-preview-sheet border rounded shadow-sm bg-white mx-auto">
                            <div class="pdf-preview-sheet-bar small text-white bg-dark px-2 py-1">Paciente / médico</div>
                            <div class="pdf-preview-sheet-body p-3" id="pdf-preview-patient-block"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-secondary mb-0" style="border-color: #6f42c1 !important;">
            <div class="card-header text-white d-flex flex-wrap align-items-center justify-content-between gap-2" style="background-color: #6f42c1;">
                <span class="fw-semibold">Pie de página</span>
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 small" for="sec_cols_footer" style="opacity:0.9">Columnas</label>
                    <input type="number" class="form-control form-control-sm text-dark" id="sec_cols_footer" min="1" max="6" value="<?= (int) $fCols ?>" style="width: 4.5rem;">
                </div>
            </div>
            <div class="card-body">
                <p class="small text-muted">Solo se imprime si el bloque «Pie de página» está activo.</p>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <ul id="instance-list-footer" class="list-group pdf-instance-sortable" data-section="footer">
                            <?php foreach ($instFooter as $inst): ?>
                                <?= view('config/partials/pdf_instance_row', ['inst' => $inst, 'col_count' => $fCols, 'elLabels' => $elLabels]) ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="text-uppercase text-muted small">Vista previa</h6>
                        <div class="pdf-preview-sheet border rounded shadow-sm bg-white mx-auto">
                            <div class="pdf-preview-sheet-bar small text-white px-2 py-1" style="background-color:#6f42c1;">Pie de página</div>
                            <div class="p-2" id="pdf-preview-footer-block"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="mb-3">
        <label class="form-label" for="tpl_name">Nombre de la plantilla</label>
        <input type="text" class="form-control" name="name" id="tpl_name" required maxlength="120" value="<?= esc($template->name ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar plantilla</button>
    <a href="<?= site_url('config/pdf-templates') ?>" class="btn btn-outline-secondary">Volver al listado</a>
    <a href="<?= site_url('config') ?>?tab=sistema" class="btn btn-outline-secondary">Configuración</a>
<?= form_close() ?>

<style>
.pdf-block-sortable .pdf-block-item.sortable-ghost,
.pdf-instance-sortable .pdf-instance-item.sortable-ghost { opacity: 0.45; background: #e7f1ff; }
.pdf-drag-handle:active, .instance-drag-handle:active { cursor: grabbing; }
.pdf-preview-sheet { max-width: 480px; min-height: 160px; }
.pdf-preview-sheet-body { background: #f8f9fa; font-size: 0.85rem; }
.pdf-preview-line { margin-bottom: 0.35rem; line-height: 1.35; }
.pdf-preview-lbl { font-weight: 700; color: #333; }
.pdf-preview-grid-row { display: flex; gap: 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 8px; }
.pdf-preview-grid-cell { flex: 1; min-width: 0; font-size: 0.75rem; }
.pdf-instance-sortable { min-height: 2.5rem; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var blockList = document.getElementById('pdf-block-list');
    var headerList = document.getElementById('instance-list-header');
    var patientList = document.getElementById('instance-list-patient');
    var footerList = document.getElementById('instance-list-footer');
    var previewPatient = document.getElementById('pdf-preview-patient-block');
    var previewHeader = document.getElementById('pdf-preview-header-block');
    var previewFooter = document.getElementById('pdf-preview-footer-block');
    var secColsH = document.getElementById('sec_cols_header');
    var secColsP = document.getElementById('sec_cols_patient');
    var secColsF = document.getElementById('sec_cols_footer');

    window._elementLabels = <?= json_encode($elLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window._elementSamples = <?= json_encode($elSamples, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window._labelsShort = <?= json_encode($labelsShort, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    function escapeHtml(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function clampCols(v) {
        var n = parseInt(v, 10);
        if (isNaN(n)) return 3;
        return Math.max(1, Math.min(6, n));
    }

    function newUid() {
        if (window.crypto && crypto.getRandomValues) {
            var a = new Uint8Array(8);
            crypto.getRandomValues(a);
            return Array.from(a, function(b) { return ('0' + b.toString(16)).slice(-2); }).join('');
        }
        return 'id' + String(Date.now()) + Math.random().toString(16).slice(2);
    }

    function colsForList(ul) {
        if (ul === headerList) return clampCols(secColsH.value);
        if (ul === patientList) return clampCols(secColsP.value);
        return clampCols(secColsF.value);
    }

    function fillColumnSelect(sel, colCount, selectedValue) {
        var cur = selectedValue != null ? String(selectedValue) : (sel.value || '0');
        sel.innerHTML = '';
        var oHide = document.createElement('option');
        oHide.value = '-1';
        oHide.textContent = 'No mostrar';
        sel.appendChild(oHide);
        for (var i = 0; i < colCount; i++) {
            var o = document.createElement('option');
            o.value = String(i);
            o.textContent = 'Columna ' + (i + 1);
            sel.appendChild(o);
        }
        if (cur === '-1') {
            sel.value = '-1';
        } else {
            var ci = parseInt(cur, 10);
            if (isNaN(ci) || ci < 0 || ci >= colCount) ci = 0;
            sel.value = String(ci);
        }
    }

    function rebuildColumnSelects() {
        [headerList, patientList, footerList].forEach(function(ul) {
            if (!ul) return;
            var n = colsForList(ul);
            ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
                var sel = li.querySelector('.instance-column');
                if (!sel) return;
                fillColumnSelect(sel, n, sel.value);
            });
        });
    }

    function columnAlign(idx, total) {
        if (total <= 1) return 'center';
        if (idx === 0) return 'left';
        if (idx === total - 1) return 'right';
        return 'center';
    }

    function rebuildGridPreview(ul, previewEl) {
        if (!previewEl || !ul) return;
        var n = colsForList(ul);
        var buckets = [];
        for (var c = 0; c < n; c++) buckets.push([]);
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var sel = li.querySelector('.instance-column');
            var v = sel ? parseInt(sel.value, 10) : -1;
            if (v < 0) return;
            var col = Math.max(0, Math.min(n - 1, v));
            var type = li.getAttribute('data-element-type') || '';
            var sample = (window._elementSamples && window._elementSamples[type]) ? window._elementSamples[type] : type;
            var shortL = (window._labelsShort && window._labelsShort[type]) ? window._labelsShort[type] : '';
            var line = shortL ? ('<span class="pdf-preview-lbl">' + escapeHtml(shortL) + '</span> ' + escapeHtml(sample)) : escapeHtml(sample);
            buckets[col].push(line);
        });
        previewEl.innerHTML = '';
        var row = document.createElement('div');
        row.className = 'pdf-preview-grid-row';
        for (var c = 0; c < n; c++) {
            var cell = document.createElement('div');
            cell.className = 'pdf-preview-grid-cell';
            cell.style.textAlign = columnAlign(c, n);
            buckets[c].forEach(function(html) {
                var div = document.createElement('div');
                div.className = 'pdf-preview-line mb-1';
                div.innerHTML = html;
                cell.appendChild(div);
            });
            row.appendChild(cell);
        }
        previewEl.appendChild(row);
    }

    function rebuildAllPreviews() {
        rebuildGridPreview(headerList, previewHeader);
        rebuildGridPreview(patientList, previewPatient);
        rebuildGridPreview(footerList, previewFooter);
    }

    function wireInstanceSelects() {
        document.querySelectorAll('.instance-column').forEach(function(el) {
            el.removeEventListener('change', rebuildAllPreviews);
            el.addEventListener('change', rebuildAllPreviews);
        });
    }

    function createInstanceRow(uid, elementType, colCount, enabled, column) {
        var label = (window._elementLabels && window._elementLabels[elementType]) ? window._elementLabels[elementType] : elementType;
        var li = document.createElement('li');
        li.className = 'list-group-item pdf-instance-item';
        li.setAttribute('data-uid', uid);
        li.setAttribute('data-element-type', elementType);
        var sel = document.createElement('select');
        sel.className = 'form-select form-select-sm instance-column';
        sel.style.maxWidth = '12rem';
        fillColumnSelect(sel, colCount, enabled ? String(column) : '-1');
        var wrap = document.createElement('div');
        wrap.className = 'd-flex flex-wrap align-items-center gap-2';
        var h = document.createElement('span');
        h.className = 'text-muted instance-drag-handle';
        h.style.cursor = 'grab';
        h.title = 'Arrastrar';
        h.innerHTML = '<i class="fa-solid fa-grip-vertical"></i>';
        var lab = document.createElement('div');
        lab.className = 'flex-grow-1';
        lab.innerHTML = '<strong class="d-block small">' + escapeHtml(label) + '</strong><span class="small text-muted font-monospace">' + escapeHtml(elementType) + '</span>';
        var bDup = document.createElement('button');
        bDup.type = 'button';
        bDup.className = 'btn btn-sm btn-outline-secondary btn-dup-instance';
        bDup.title = 'Duplicar en esta sección';
        bDup.innerHTML = '<i class="fa-regular fa-copy"></i>';
        var bDel = document.createElement('button');
        bDel.type = 'button';
        bDel.className = 'btn btn-sm btn-outline-danger btn-del-instance';
        bDel.title = 'Quitar';
        bDel.innerHTML = '<i class="fa-solid fa-trash"></i>';
        wrap.appendChild(h);
        wrap.appendChild(sel);
        wrap.appendChild(lab);
        wrap.appendChild(bDup);
        wrap.appendChild(bDel);
        li.appendChild(wrap);
        return li;
    }

    function listBySectionKey(key) {
        if (key === 'header') return headerList;
        if (key === 'patient_doctor') return patientList;
        return footerList;
    }

    document.getElementById('btn_add_element').addEventListener('click', function() {
        var typeEl = document.getElementById('add_element_type');
        var secEl = document.getElementById('add_element_section');
        var type = typeEl ? typeEl.value : '';
        var sec = secEl ? secEl.value : 'header';
        var ul = listBySectionKey(sec);
        if (!ul || !type) return;
        var n = colsForList(ul);
        ul.appendChild(createInstanceRow(newUid(), type, n, true, 0));
        wireInstanceSelects();
        rebuildAllPreviews();
    });

    document.getElementById('pdf-editor-instances').addEventListener('click', function(e) {
        var dup = e.target.closest('.btn-dup-instance');
        var del = e.target.closest('.btn-del-instance');
        var li = e.target.closest('.pdf-instance-item');
        if (!li) return;
        var ul = li.parentElement;
        if (dup && ul) {
            var type = li.getAttribute('data-element-type') || '';
            var n = colsForList(ul);
            var sel = li.querySelector('.instance-column');
            var v = sel ? parseInt(sel.value, 10) : 0;
            var en = v >= 0;
            var col = en ? Math.max(0, Math.min(n - 1, v)) : 0;
            var clone = createInstanceRow(newUid(), type, n, en, en ? col : 0);
            if (!en && sel) clone.querySelector('.instance-column').value = '-1';
            ul.insertBefore(clone, li.nextSibling);
            wireInstanceSelects();
            rebuildAllPreviews();
        }
        if (del && ul) {
            li.remove();
            rebuildAllPreviews();
        }
    });

    [secColsH, secColsP, secColsF].forEach(function(inp) {
        if (!inp) return;
        inp.addEventListener('change', function() {
            rebuildColumnSelects();
            rebuildAllPreviews();
        });
    });

    if (blockList && typeof Sortable !== 'undefined') {
        new Sortable(blockList, { animation: 150, handle: '.pdf-drag-handle' });
    }
    var sortGroup = { name: 'pdf-instances', pull: true, put: true };
    if (headerList && typeof Sortable !== 'undefined') {
        new Sortable(headerList, { animation: 150, handle: '.instance-drag-handle', group: sortGroup, onEnd: rebuildAllPreviews });
    }
    if (patientList && typeof Sortable !== 'undefined') {
        new Sortable(patientList, { animation: 150, handle: '.instance-drag-handle', group: sortGroup, onEnd: rebuildAllPreviews });
    }
    if (footerList && typeof Sortable !== 'undefined') {
        new Sortable(footerList, { animation: 150, handle: '.instance-drag-handle', group: sortGroup, onEnd: rebuildAllPreviews });
    }

    wireInstanceSelects();
    rebuildAllPreviews();

    function clampMargin(v) {
        var n = parseFloat(v);
        if (isNaN(n)) return 15;
        return Math.max(0, Math.min(50, n));
    }

    function parseInstanceLi(li, section) {
        var sel = li.querySelector('.instance-column');
        var v = sel ? parseInt(sel.value, 10) : -1;
        var cols = section === 'header' ? clampCols(secColsH.value) : (section === 'patient_doctor' ? clampCols(secColsP.value) : clampCols(secColsF.value));
        var enabled = v >= 0;
        var col = enabled ? Math.max(0, Math.min(cols - 1, v)) : 0;
        return {
            uid: li.getAttribute('data-uid') || '',
            element_type: li.getAttribute('data-element-type') || '',
            section: section,
            enabled: enabled,
            column: col
        };
    }

    document.getElementById('pdf_tpl_form').addEventListener('submit', function() {
        var blocks = [];
        blockList.querySelectorAll('.pdf-block-item').forEach(function(li) {
            var id = li.getAttribute('data-block-id');
            var cb = li.querySelector('.pdf-block-enabled');
            blocks.push({ id: id, enabled: cb ? cb.checked : true });
        });

        var instances = [];
        headerList.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            instances.push(parseInstanceLi(li, 'header'));
        });
        patientList.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            instances.push(parseInstanceLi(li, 'patient_doctor'));
        });
        footerList.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            instances.push(parseInstanceLi(li, 'footer'));
        });

        function buildWatermarkForJson() {
            var relIn = document.getElementById('watermark_file_rel');
            var rel = relIn && relIn.value ? String(relIn.value).trim() : '';
            var removeCb = document.getElementById('watermark_remove');
            if (removeCb && removeCb.checked) {
                rel = '';
            }
            var op = parseFloat(document.getElementById('wm_opacity').value);
            if (isNaN(op)) op = 0.12;
            op = Math.max(0.05, Math.min(0.9, op));
            var sz = parseInt(document.getElementById('wm_size').value, 10);
            if (isNaN(sz)) sz = 45;
            sz = Math.max(10, Math.min(95, sz));
            var en = document.getElementById('wm_enabled') && document.getElementById('wm_enabled').checked;
            return {
                enabled: en,
                opacity: op,
                size_percent: sz,
                file: rel === '' ? null : rel
            };
        }

        document.getElementById('layout_json').value = JSON.stringify({
            version: 5,
            blocks: blocks,
            section_layouts: {
                header: { columns: clampCols(secColsH.value) },
                patient_doctor: { columns: clampCols(secColsP.value) },
                footer: { columns: clampCols(secColsF.value) }
            },
            instances: instances,
            margins_mm: {
                top: clampMargin(document.getElementById('margin_top').value),
                right: clampMargin(document.getElementById('margin_right').value),
                bottom: clampMargin(document.getElementById('margin_bottom').value),
                left: clampMargin(document.getElementById('margin_left').value)
            },
            watermark: buildWatermarkForJson()
        });
    });
});
</script>
<?= $this->endSection() ?>
