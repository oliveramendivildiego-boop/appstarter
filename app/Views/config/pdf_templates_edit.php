<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Editar plantilla PDF<?= $this->endSection() ?>
<?= $this->section('head_extra') ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<?= $this->endSection() ?>
<?php
$elLabels = $element_type_labels ?? \App\Services\ReportPdfLayoutService::elementTypeLabels();
$elSamples = $element_preview_samples ?? \App\Services\ReportPdfLayoutService::elementPreviewSamples();
$pdfStyleAllowlists = $pdf_style_allowlists ?? \App\Services\ReportPdfLayoutService::styleAllowlistsForClient();
$secLayouts = $layout['section_layouts'] ?? \App\Services\ReportPdfLayoutService::defaultSectionLayoutsStatic();
$hCols = max(1, min(6, (int) ($secLayouts['header']['columns'] ?? 3)));
$pCols = max(1, min(6, (int) ($secLayouts['patient_doctor']['columns'] ?? 2)));
$fCols = max(1, min(6, (int) ($secLayouts['footer']['columns'] ?? 3)));
$lCols = max(1, min(6, (int) ($secLayouts['lab_firmas']['columns'] ?? 3)));
$instHeader = [];
$instPatient = [];
$instFooter = [];
$instLabFirmas = [];
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
    } elseif ($s === 'lab_firmas') {
        $instLabFirmas[] = $inst;
    }
}
$mm = $layout['margins_mm'] ?? \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
$wm = $layout['watermark'] ?? \App\Services\ReportPdfLayoutService::defaultWatermarkStatic();
$ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : \App\Services\ReportPdfLayoutService::defaultPageStyleStatic();
$ch = \App\Services\ReportPdfLayoutService::normalizeCardHeaderStyle($ps['card_header'] ?? []);
$ns = \App\Services\ReportPdfLayoutService::normalizeNotesStyle($ps['notes'] ?? []);
$lf = \App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle($ps['lab_firmas'] ?? []);
$rs = \App\Services\ReportPdfLayoutService::normalizeResultsTableStyle($ps['results_table'] ?? []);
$hs = \App\Services\ReportPdfLayoutService::normalizeHeaderSectionStyle($ps['header_section'] ?? []);
$wmPreview = null;
if (! empty($wm['file'])) {
    $wmPreview = \App\Services\ReportPdfLayoutService::getWatermarkDataUriForLayout([
        'watermark' => array_merge($wm, ['enabled' => true]),
    ]);
}
$labelsShort = [
    'paciente_nombre'   => 'Paciente:',
    'paciente_genero'   => 'Género:',
    'paciente_edad'     => 'Edad:',
    'paciente_telefono' => 'Teléfono:',
    'medico'            => 'Médico:',
    'fecha_recepcion'   => 'Fecha de recepción:',
    'fecha_reporte'     => 'Fecha de reporte:',
    'numero_orden'      => 'No. Orden:',
    'lab_firmas_title'     => '',
    'lab_firmas_validator' => '',
    'lab_firmas_seal'      => '',
    'lab_firmas_approver'  => '',
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
<p class="text-muted">Configure el <strong>número de columnas</strong> por zona y, en cada tarjeta (encabezado, paciente/médico, validación/firmas, pie), la sección <strong>Estilo de la cuadrícula</strong>: <strong>interlineado</strong> (1–2,5) y <strong>alineación por columna</strong> (izquierda/centro/derecha y arriba/centro/abajo). Los ítems en la misma fila del PDF comparten una sola fila de tabla aunque ocupen varias columnas; varios bloques con la misma columna y ancho se apilan en una celda. Puede arrastrar elementos entre encabezado, paciente/médico y pie; la sección de firmas tiene su propia lista. <strong>Guarde la plantilla</strong> para persistir el diseño en el JSON.</p>

<?= form_open(site_url('config/pdf-templates/save'), ['id' => 'pdf_tpl_form', 'enctype' => 'multipart/form-data']) ?>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($template->id ?? 0) ?>">
    <input type="hidden" name="layout_json" id="layout_json" value="">

<div class="card shadow-sm mb-4 pdf-margins-card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Estilo global de card-header (PDF / impresión)</h5>
    </div>
    <div class="card-body">
        <p class="small text-muted">Aplica a los encabezados de sección del reporte (títulos tipo tarjeta como separadores de análisis).</p>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <label class="form-label small" for="hs_separator_color">Separador de encabezado (línea azul)</label>
                <input type="color" class="form-control form-control-color" id="hs_separator_color" value="<?= esc($hs['separator_color'], 'attr') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="ch_bg_color">Fondo</label>
                <input type="color" class="form-control form-control-color" id="ch_bg_color" value="<?= esc($ch['bg_color'], 'attr') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="ch_text_color">Texto</label>
                <input type="color" class="form-control form-control-color" id="ch_text_color" value="<?= esc($ch['text_color'], 'attr') ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small" for="ch_font_family">Fuente</label>
                <select class="form-select" id="ch_font_family">
                    <?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?>
                    <option value="<?= esc($ff, 'attr') ?>" <?= $ch['font_family'] === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="ch_font_size">Tamaño (pt)</label>
                <input type="number" class="form-control" id="ch_font_size" min="7" max="20" step="0.5" value="<?= esc((string) $ch['font_size_pt'], 'attr') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="ch_font_weight">Grosor</label>
                <select class="form-select" id="ch_font_weight">
                    <?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?>
                    <option value="<?= esc($w, 'attr') ?>" <?= $ch['font_weight'] === $w ? 'selected' : '' ?>><?= esc($w) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="ch_font_style">Estilo</label>
                <select class="form-select" id="ch_font_style">
                    <?php foreach (['normal', 'italic', 'oblique'] as $st): ?>
                    <option value="<?= esc($st, 'attr') ?>" <?= $ch['font_style'] === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="ch_text_transform">Transformación</label>
                <select class="form-select" id="ch_text_transform">
                    <?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $k => $v): ?>
                    <option value="<?= esc($k, 'attr') ?>" <?= $ch['text_transform'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-info-subtle border">
        <h5 class="mb-0">Estilo global de Tablas de resultados por prueba (PDF / impresión)</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_header_bg">Fondo encabezado</label><input type="color" class="form-control form-control-color" id="rs_header_bg" value="<?= esc($rs['header_bg_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_header_text">Texto encabezado</label><input type="color" class="form-control form-control-color" id="rs_header_text" value="<?= esc($rs['header_text_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_body_bg">Fondo filas</label><input type="color" class="form-control form-control-color" id="rs_body_bg" value="<?= esc($rs['body_bg_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_body_text">Texto filas</label><input type="color" class="form-control form-control-color" id="rs_body_text" value="<?= esc($rs['body_text_color'], 'attr') ?>"></div>
            <div class="col-12 col-md-3 d-flex align-items-end">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="rs_body_transparent" <?= ! empty($rs['body_transparent']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="rs_body_transparent">Body transparente (sin fondo)</label>
                </div>
            </div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_border_color">Color bordes</label><input type="color" class="form-control form-control-color" id="rs_border_color" value="<?= esc($rs['border_color'], 'attr') ?>"></div>
            <div class="col-12"><hr class="my-1"></div>
            <div class="col-12"><div class="small text-muted fw-semibold">Título de sección (fila separadora)</div></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_segment_bg">Fondo</label><input type="color" class="form-control form-control-color" id="rs_segment_bg" value="<?= esc($rs['segment_bg_color'], 'attr') ?>"></div>
            <div class="col-12 col-md-3 d-flex align-items-end">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="rs_segment_transparent" <?= ! empty($rs['segment_transparent']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="rs_segment_transparent">Fondo transparente</label>
                </div>
            </div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_segment_border_color">Color borde</label><input type="color" class="form-control form-control-color" id="rs_segment_border_color" value="<?= esc($rs['segment_border_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_segment_border_width">Ancho borde (px)</label><input type="number" class="form-control" id="rs_segment_border_width" min="0" max="4" step="1" value="<?= esc((string) $rs['segment_border_width_px'], 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_segment_shadow">Sombra</label><select class="form-select" id="rs_segment_shadow"><?php foreach (['none' => 'Sin sombra', 'soft' => 'Suave', 'medium' => 'Media', 'strong' => 'Fuerte'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $rs['segment_shadow'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-12 col-md-3"><label class="form-label small" for="rs_font_family">Fuente</label><select class="form-select" id="rs_font_family"><?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?><option value="<?= esc($ff, 'attr') ?>" <?= $rs['font_family'] === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_font_size">Tamaño</label><input type="number" class="form-control" id="rs_font_size" min="7" max="20" step="0.5" value="<?= esc((string) $rs['font_size_pt'], 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_font_weight">Grosor</label><select class="form-select" id="rs_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= $rs['font_weight'] === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_font_style">Estilo</label><select class="form-select" id="rs_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= $rs['font_style'] === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_text_transform">Transformación</label><select class="form-select" id="rs_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $rs['text_transform'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_line_height">Interlineado</label><input type="number" class="form-control" id="rs_line_height" min="1" max="3" step="0.05" value="<?= esc((string) $rs['line_height'], 'attr') ?>"></div>
        </div>
    </div>
</div>

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
        <p class="small text-muted mt-3 mb-0">Los estilos del bloque <strong>Notas del resultado</strong> y de <strong>Validación y aprobación (firmas)</strong> se configuran justo debajo, en la misma página.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning-subtle border">
        <h5 class="mb-0">Bloque «Notas del resultado» — estilo en PDF / impresión</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_title_bg">Fondo título</label><input type="color" class="form-control form-control-color" id="ns_title_bg" value="<?= esc($ns['title_bg_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_title_text">Texto título</label><input type="color" class="form-control form-control-color" id="ns_title_text" value="<?= esc($ns['title_text_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_body_bg">Fondo contenido</label><input type="color" class="form-control form-control-color" id="ns_body_bg" value="<?= esc($ns['body_bg_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_body_text">Texto contenido</label><input type="color" class="form-control form-control-color" id="ns_body_text" value="<?= esc($ns['body_text_color'], 'attr') ?>"></div>
            <div class="col-12 col-md-3"><label class="form-label small" for="ns_font_family">Fuente</label><select class="form-select" id="ns_font_family"><?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?><option value="<?= esc($ff, 'attr') ?>" <?= $ns['font_family'] === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ns_font_size">Tamaño</label><input type="number" class="form-control" id="ns_font_size" min="7" max="20" step="0.5" value="<?= esc((string) $ns['font_size_pt'], 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ns_font_weight">Grosor</label><select class="form-select" id="ns_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= $ns['font_weight'] === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ns_font_style">Estilo</label><select class="form-select" id="ns_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= $ns['font_style'] === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_text_transform">Transformación</label><select class="form-select" id="ns_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $ns['text_transform'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ns_line_height">Interlineado</label><input type="number" class="form-control" id="ns_line_height" min="1" max="3" step="0.05" value="<?= esc((string) $ns['line_height'], 'attr') ?>"></div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 border border-warning border-opacity-50">
    <div class="card-header bg-warning-subtle border-bottom">
        <h5 class="mb-1">Bloque «Validación y aprobación (firmas)» — estilo y textos en PDF / impresión</h5>
        <p class="small text-muted mb-0">Colores, tipografía y etiquetas del cuadro de firmas. Active o desactive el bloque en la lista de arriba.</p>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12"><span class="small fw-semibold text-secondary">Apariencia</span></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_title_bg">Fondo título</label><input type="color" class="form-control form-control-color" id="lf_title_bg" value="<?= esc($lf['title_bg_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_title_text">Texto título</label><input type="color" class="form-control form-control-color" id="lf_title_text" value="<?= esc($lf['title_text_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_body_bg">Fondo contenido</label><input type="color" class="form-control form-control-color" id="lf_body_bg" value="<?= esc($lf['body_bg_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_body_text">Texto contenido</label><input type="color" class="form-control form-control-color" id="lf_body_text" value="<?= esc($lf['body_text_color'], 'attr') ?>"></div>
            <div class="col-12 col-md-3"><label class="form-label small" for="lf_font_family">Fuente</label><select class="form-select" id="lf_font_family"><?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?><option value="<?= esc($ff, 'attr') ?>" <?= $lf['font_family'] === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="lf_font_size">Tamaño</label><input type="number" class="form-control" id="lf_font_size" min="7" max="20" step="0.5" value="<?= esc((string) $lf['font_size_pt'], 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="lf_font_weight">Grosor</label><select class="form-select" id="lf_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= $lf['font_weight'] === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="lf_font_style">Estilo</label><select class="form-select" id="lf_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= $lf['font_style'] === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_text_transform">Transformación</label><select class="form-select" id="lf_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $lf['text_transform'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="lf_line_height">Interlineado</label><input type="number" class="form-control" id="lf_line_height" min="1" max="3" step="0.05" value="<?= esc((string) $lf['line_height'], 'attr') ?>"></div>
            <div class="col-12"><hr class="my-2"></div>
            <div class="col-12"><span class="small fw-semibold text-secondary">Textos en el PDF</span> <span class="small text-muted">(máx. 120 caracteres c/u)</span></div>
            <div class="col-12 col-md-6"><label class="form-label small" for="lf_section_title">Título de la sección</label><input type="text" class="form-control form-control-sm" id="lf_section_title" maxlength="120" value="<?= esc((string) ($lf['section_title'] ?? ''), 'attr') ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label small" for="lf_label_validator">Etiqueta columna validador</label><input type="text" class="form-control form-control-sm" id="lf_label_validator" maxlength="120" value="<?= esc((string) ($lf['label_validator'] ?? ''), 'attr') ?>"></div>
            <div class="col-12 col-md-4"><label class="form-label small" for="lf_label_seal">Etiqueta columna sello</label><input type="text" class="form-control form-control-sm" id="lf_label_seal" maxlength="120" value="<?= esc((string) ($lf['label_seal'] ?? ''), 'attr') ?>"></div>
            <div class="col-12 col-md-4"><label class="form-label small" for="lf_label_approver">Etiqueta columna aprobador</label><input type="text" class="form-control form-control-sm" id="lf_label_approver" maxlength="120" value="<?= esc((string) ($lf['label_approver'] ?? ''), 'attr') ?>"></div>
            <div class="col-12 col-md-4"><label class="form-label small" for="lf_label_cargo">Etiqueta «cargo»</label><input type="text" class="form-control form-control-sm" id="lf_label_cargo" maxlength="120" value="<?= esc((string) ($lf['label_cargo'] ?? ''), 'attr') ?>"></div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white" style="color:#fff !important;">
        <h5 class="mb-0" style="color:#fff !important;">Márgenes de la hoja (mm)</h5>
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
                        <option value="lab_firmas">Validación / firmas</option>
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
                <?= view('config/partials/pdf_section_style_controls', [
                    'section_key' => 'header',
                    'col_count'   => $hCols,
                    'sec_layout'  => $secLayouts['header'] ?? [],
                ]) ?>
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
                <?= view('config/partials/pdf_section_style_controls', [
                    'section_key' => 'patient_doctor',
                    'col_count'   => $pCols,
                    'sec_layout'  => $secLayouts['patient_doctor'] ?? [],
                ]) ?>
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

        <div class="card border-warning mb-4">
            <div class="card-header bg-warning text-dark d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="fw-semibold">Validación y aprobación (firmas)</span>
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 small text-dark" for="sec_cols_lab_firmas">Columnas</label>
                    <input type="number" class="form-control form-control-sm" id="sec_cols_lab_firmas" min="1" max="6" value="<?= (int) $lCols ?>" style="width: 4.5rem;">
                </div>
            </div>
            <div class="card-body">
                <p class="small text-muted">Solo se imprime si el bloque «Validación y aprobación» está activo. El <strong>título</strong> se muestra una vez; validador, sello y aprobador se repiten por cada firma en el reporte.</p>
                <?= view('config/partials/pdf_section_style_controls', [
                    'section_key' => 'lab_firmas',
                    'col_count'   => $lCols,
                    'sec_layout'  => $secLayouts['lab_firmas'] ?? [],
                ]) ?>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <ul id="instance-list-lab-firmas" class="list-group pdf-instance-sortable" data-section="lab_firmas">
                            <?php foreach ($instLabFirmas as $inst): ?>
                                <?= view('config/partials/pdf_instance_row', ['inst' => $inst, 'col_count' => $lCols, 'elLabels' => $elLabels]) ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="text-uppercase text-muted small">Vista previa</h6>
                        <div class="pdf-preview-sheet border rounded shadow-sm bg-white mx-auto">
                            <div class="pdf-preview-sheet-bar small text-dark bg-warning px-2 py-1">Validación / firmas</div>
                            <div class="p-2" id="pdf-preview-lab-firmas-block"></div>
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
                <?= view('config/partials/pdf_section_style_controls', [
                    'section_key' => 'footer',
                    'col_count'   => $fCols,
                    'sec_layout'  => $secLayouts['footer'] ?? [],
                ]) ?>
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
.pdf-preview-line { line-height: inherit; }
.pdf-preview-lbl { font-weight: 700; color: #333; }
.pdf-preview-grid-row { display: flex; gap: 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 8px; }
.pdf-preview-grid-cell { flex: 1; min-width: 0; font-size: 0.75rem; }
.pdf-instance-sortable { min-height: 2.5rem; }
.pdf-margins-card .card-body {
    background: #212529;
    color: #fff;
}
.pdf-margins-card .card-body .form-label,
.pdf-margins-card .card-body .small,
.pdf-margins-card .card-body .text-muted {
    color: #fff !important;
}
.pdf-margins-card .card-header,
.pdf-margins-card .card-header h5 {
    color: #fff !important;
}
.pdf-instance-sortable .pdf-instance-item {
    border: 1px solid #b9c4d0 !important;
    border-left: 4px solid #0d6efd !important;
    border-radius: 8px !important;
    margin-bottom: 10px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.pdf-instance-sortable .pdf-instance-item:last-child { margin-bottom: 0; }
.pdf-instance-head strong { font-size: 0.9rem; color: #1f2d3d; }
.pdf-instance-head .font-monospace { font-size: 0.72rem; }
.pdf-text-style-controls {
    border-top: 1px dashed #d8dee5;
    background: #fafbfc;
    border-radius: 8px;
    padding: 0.7rem 0.45rem 0.45rem;
}
.pdf-text-style-controls .form-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.2rem !important;
    line-height: 1.2;
}
.pdf-text-style-controls .form-select,
.pdf-text-style-controls .form-control {
    min-height: 2rem;
}
.pdf-text-style-controls .form-control-color {
    min-height: 2rem;
    padding: 0.15rem;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var blockList = document.getElementById('pdf-block-list');
    var headerList = document.getElementById('instance-list-header');
    var patientList = document.getElementById('instance-list-patient');
    var footerList = document.getElementById('instance-list-footer');
    var labFirmasList = document.getElementById('instance-list-lab-firmas');
    var previewPatient = document.getElementById('pdf-preview-patient-block');
    var previewHeader = document.getElementById('pdf-preview-header-block');
    var previewFooter = document.getElementById('pdf-preview-footer-block');
    var previewLabFirmas = document.getElementById('pdf-preview-lab-firmas-block');
    var secColsH = document.getElementById('sec_cols_header');
    var secColsP = document.getElementById('sec_cols_patient');
    var secColsF = document.getElementById('sec_cols_footer');
    var secColsL = document.getElementById('sec_cols_lab_firmas');
    var LAB_ELEMENT_TYPES = ['lab_firmas_title', 'lab_firmas_validator', 'lab_firmas_seal', 'lab_firmas_approver'];

    window._elementLabels = <?= json_encode($elLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window._elementSamples = <?= json_encode($elSamples, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window._labelsShort = <?= json_encode($labelsShort, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window._pdfStyleAllowlists = <?= json_encode($pdfStyleAllowlists, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    function pdfAllow(key) {
        var a = window._pdfStyleAllowlists || {};
        return Array.isArray(a[key]) ? a[key] : [];
    }

    function pickAllowedDomId(id, listKey, fallback) {
        var allowed = pdfAllow(listKey);
        var el = document.getElementById(id);
        var v = el ? String(el.value || '').trim() : '';
        return allowed.indexOf(v) >= 0 ? v : fallback;
    }

    function isValidPdfHexJs(v) {
        return typeof v === 'string' && /^#[0-9a-fA-F]{6}$/.test(v.trim());
    }

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
        if (ul === labFirmasList) return clampCols(secColsL ? secColsL.value : '3');
        return clampCols(secColsF.value);
    }

    function syncAddElementTypeOptions() {
        var secEl = document.getElementById('add_element_section');
        var typeEl = document.getElementById('add_element_type');
        if (!secEl || !typeEl) return;
        var sec = secEl.value;
        var firstVisible = null;
        for (var i = 0; i < typeEl.options.length; i++) {
            var o = typeEl.options[i];
            var t = o.value;
            var isLab = LAB_ELEMENT_TYPES.indexOf(t) >= 0;
            var show = (sec === 'lab_firmas') ? isLab : !isLab;
            o.hidden = !show;
            o.disabled = !show;
            if (show && firstVisible === null) firstVisible = o;
        }
        if (firstVisible) typeEl.value = firstVisible.value;
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

    function fillSpanSelectElement(sel, maxS, selectedVal) {
        var prev = parseInt(selectedVal, 10);
        if (isNaN(prev) || prev < 1) prev = 1;
        prev = Math.max(1, Math.min(maxS, prev));
        sel.innerHTML = '';
        for (var s = 1; s <= maxS; s++) {
            var o = document.createElement('option');
            o.value = String(s);
            o.textContent = s === 1 ? 'Ancho: 1 col.' : 'Ancho: ' + s + ' cols.';
            sel.appendChild(o);
        }
        sel.value = String(prev);
    }

    function updateSpanOptionsForRow(li) {
        var ul = li.parentElement;
        if (!ul) return;
        var n = colsForList(ul);
        var colSel = li.querySelector('.instance-column');
        var spanSel = li.querySelector('.instance-span');
        if (!colSel || !spanSel) return;
        var cv = parseInt(colSel.value, 10);
        if (cv < 0) {
            spanSel.disabled = true;
            return;
        }
        spanSel.disabled = false;
        var maxS = Math.max(1, n - cv);
        fillSpanSelectElement(spanSel, maxS, spanSel.value);
    }

    function rebuildColumnSelects() {
        [headerList, patientList, footerList, labFirmasList].forEach(function(ul) {
            if (!ul) return;
            var n = colsForList(ul);
            ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
                var sel = li.querySelector('.instance-column');
                if (!sel) return;
                fillColumnSelect(sel, n, sel.value);
                updateSpanOptionsForRow(li);
            });
        });
    }

    function columnAlign(idx, total) {
        if (total <= 1) return 'center';
        if (idx === 0) return 'left';
        if (idx === total - 1) return 'right';
        return 'center';
    }

    function columnAlignForSpan(start, span, total) {
        var end = start + span - 1;
        if (total <= 1) return 'center';
        if (start === 0 && end >= total - 1) return 'center';
        if (start === 0) return 'left';
        if (end >= total - 1) return 'right';
        return 'center';
    }

    function sectionIdSafe(sectionKey) {
        return String(sectionKey).replace(/[^a-z0-9_]/g, '_');
    }

    /** Tbody de alineación por columna (id o data-pdf-section por si el id no coincide). */
    function sectionStyleColsTbody(sectionKey) {
        var idS = sectionIdSafe(sectionKey);
        var tbody = document.getElementById('sec_style_cols_' + idS);
        if (!tbody) {
            tbody = document.querySelector('.pdf-sec-col-aligns[data-pdf-section="' + sectionKey + '"]');
        }
        return tbody;
    }

    function sectionStyleLineHeightEl(sectionKey) {
        var idS = sectionIdSafe(sectionKey);
        var el = document.getElementById('sec_lh_' + idS);
        if (!el) {
            var box = document.querySelector('.pdf-section-style-controls[data-pdf-section="' + sectionKey + '"]');
            if (box) el = box.querySelector('.pdf-sec-line-height');
        }
        return el;
    }

    function readInstanceTextStyle(li) {
        function readStr(sel, listKey, fallback) {
            var allowed = pdfAllow(listKey);
            var el = li.querySelector(sel);
            var v = el ? String(el.value || '').trim() : '';
            return allowed.indexOf(v) >= 0 ? v : fallback;
        }
        function readNum(sel, minV, maxV, step, fallback) {
            var el = li.querySelector(sel);
            var n = el ? parseFloat(el.value) : NaN;
            if (isNaN(n)) n = fallback;
            n = Math.max(minV, Math.min(maxV, n));
            return Math.round(n / step) * step;
        }
        var clrEl = li.querySelector('.instance-font-color');
        var clr = clrEl ? String(clrEl.value || '').trim() : '#333333';
        if (!isValidPdfHexJs(clr)) clr = '#333333';
        return {
            font_family: readStr('.instance-font-family', 'font_families', 'DejaVu Sans'),
            font_size_pt: readNum('.instance-font-size', 6, 24, 0.5, 10),
            font_weight: readStr('.instance-font-weight', 'font_weights', 'normal'),
            font_color: clr,
            font_style: readStr('.instance-font-style', 'font_styles', 'normal'),
            text_transform: readStr('.instance-text-transform', 'text_transforms', 'none'),
            letter_spacing_em: readNum('.instance-letter-spacing', -0.2, 1, 0.01, 0),
            line_height: readNum('.instance-line-height', 1, 3, 0.05, 1.35),
            text_shadow: readStr('.instance-text-shadow', 'text_shadows', 'none')
        };
    }

    function textStyleToInlineCss(ts) {
        var mapShadow = {
            none: 'none',
            soft: '0.4px 0.4px 1px rgba(0,0,0,0.28)',
            medium: '0.7px 0.7px 1.4px rgba(0,0,0,0.35)',
            strong: '1px 1px 2px rgba(0,0,0,0.45)'
        };
        return 'font-family:' + ts.font_family + ';' +
            'font-size:' + ts.font_size_pt + 'pt;' +
            'font-weight:' + ts.font_weight + ';' +
            'color:' + ts.font_color + ';' +
            'font-style:' + ts.font_style + ';' +
            'text-transform:' + ts.text_transform + ';' +
            'letter-spacing:' + ts.letter_spacing_em + 'em;' +
            'line-height:' + ts.line_height + ';' +
            'text-shadow:' + (mapShadow[ts.text_shadow] || 'none') + ';';
    }

    function readSectionStyleFromDom(sectionKey, n) {
        var lhEl = sectionStyleLineHeightEl(sectionKey);
        var lh = parseFloat(lhEl && lhEl.value);
        if (isNaN(lh)) lh = 1.35;
        lh = Math.max(1, Math.min(2.5, Math.round(lh * 100) / 100));
        var tbody = sectionStyleColsTbody(sectionKey);
        var h = [];
        var v = [];
        for (var i = 0; i < n; i++) {
            var row = tbody ? tbody.querySelector('tr.pdf-sec-col-row[data-col="' + i + '"]') : null;
            var selH = row ? row.querySelector('.pdf-sec-col-h') : null;
            var selV = row ? row.querySelector('.pdf-sec-col-v') : null;
            var hv = selH && ['left', 'center', 'right'].indexOf(selH.value) >= 0 ? selH.value : columnAlign(i, n);
            var vv = selV && ['top', 'middle', 'bottom'].indexOf(selV.value) >= 0 ? selV.value : 'top';
            h.push(hv);
            v.push(vv);
        }
        return { line_height: lh, column_align_h: h, column_align_v: v };
    }

    function rebuildSectionStyleTable(sectionKey) {
        var tbody = sectionStyleColsTbody(sectionKey);
        if (!tbody) return;
        var colsInp = sectionKey === 'header' ? secColsH : (sectionKey === 'patient_doctor' ? secColsP : (sectionKey === 'lab_firmas' ? secColsL : secColsF));
        if (!colsInp) return;
        var n = clampCols(colsInp.value);
        var prevH = [];
        var prevV = [];
        tbody.querySelectorAll('tr.pdf-sec-col-row').forEach(function(tr) {
            var ci = parseInt(tr.getAttribute('data-col'), 10);
            var sh = tr.querySelector('.pdf-sec-col-h');
            var sv = tr.querySelector('.pdf-sec-col-v');
            if (!isNaN(ci) && sh && sv) {
                prevH[ci] = sh.value;
                prevV[ci] = sv.value;
            }
        });
        function optsH(sel) {
            var pairs = [['left', 'Izquierda'], ['center', 'Centro'], ['right', 'Derecha']];
            return pairs.map(function(p) {
                return '<option value="' + p[0] + '"' + (sel === p[0] ? ' selected' : '') + '>' + p[1] + '</option>';
            }).join('');
        }
        function optsV(sel) {
            var pairs = [['top', 'Arriba'], ['middle', 'Centro'], ['bottom', 'Abajo']];
            return pairs.map(function(p) {
                return '<option value="' + p[0] + '"' + (sel === p[0] ? ' selected' : '') + '>' + p[1] + '</option>';
            }).join('');
        }
        tbody.innerHTML = '';
        for (var i = 0; i < n; i++) {
            var hVal = prevH[i];
            var vVal = prevV[i];
            if (!hVal || ['left', 'center', 'right'].indexOf(hVal) < 0) hVal = columnAlign(i, n);
            if (!vVal || ['top', 'middle', 'bottom'].indexOf(vVal) < 0) vVal = 'top';
            var tr = document.createElement('tr');
            tr.className = 'pdf-sec-col-row';
            tr.setAttribute('data-col', String(i));
            tr.innerHTML = '<td class="text-muted">' + (i + 1) + '</td>' +
                '<td><select class="form-select form-select-sm pdf-sec-col-h" data-col="' + i + '">' + optsH(hVal) + '</select></td>' +
                '<td><select class="form-select form-select-sm pdf-sec-col-v" data-col="' + i + '">' + optsV(vVal) + '</select></td>';
            tbody.appendChild(tr);
        }
    }

    function buildSectionLayoutsForJson() {
        function pack(secKey, colsInput) {
            var n = clampCols(colsInput.value);
            var st = readSectionStyleFromDom(secKey, n);
            return {
                columns: n,
                line_height: st.line_height,
                column_align_h: st.column_align_h,
                column_align_v: st.column_align_v
            };
        }
        return {
            header: pack('header', secColsH),
            patient_doctor: pack('patient_doctor', secColsP),
            lab_firmas: pack('lab_firmas', secColsL),
            footer: pack('footer', secColsF)
        };
    }

    function rebuildGridPreview(ul, previewEl) {
        if (!previewEl || !ul) return;
        var n = colsForList(ul);
        var sectionKey = ul.getAttribute('data-section') || 'header';
        var secSt = readSectionStyleFromDom(sectionKey, n);
        var items = [];
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var sel = li.querySelector('.instance-column');
            var v = sel ? parseInt(sel.value, 10) : -1;
            if (v < 0) return;
            var col = Math.max(0, Math.min(n - 1, v));
            var spanSel = li.querySelector('.instance-span');
            var span = spanSel && !spanSel.disabled ? parseInt(spanSel.value, 10) : 1;
            if (isNaN(span) || span < 1) span = 1;
            var maxS = Math.max(1, n - col);
            span = Math.max(1, Math.min(maxS, span));
            var type = li.getAttribute('data-element-type') || '';
            var sample = (window._elementSamples && window._elementSamples[type]) ? window._elementSamples[type] : type;
            var shortL = (window._labelsShort && window._labelsShort[type]) ? window._labelsShort[type] : '';
            var styleWrap = textStyleToInlineCss(readInstanceTextStyle(li));
            var rawLine = shortL ? ('<span class="pdf-preview-lbl">' + escapeHtml(shortL) + '</span> ' + escapeHtml(sample)) : escapeHtml(sample);
            var line = '<span style="' + escapeHtml(styleWrap) + '">' + rawLine + '</span>';
            items.push({ col: col, span: span, html: line });
        });
        previewEl.innerHTML = '';
        var wrap = document.createElement('div');
        wrap.style.borderBottom = '1px solid #dee2e6';
        wrap.style.paddingBottom = '8px';
        var pctNum = 100 / n;
        var pct = pctNum.toFixed(2) + '%';

        function rangesOverlapPdf(a0, a1, b0, b1) {
            return a0 < b1 && b0 < a1;
        }

        var rows = [];
        var ri = 0;
        while (ri < items.length) {
            var colspans = [];
            var stacks = [];
            for (var sx = 0; sx < n; sx++) {
                stacks[sx] = null;
            }

            function canPlacePdf(itj) {
                var cc = itj.col;
                var sp = itj.span;
                if (sp > 1) {
                    for (var ci = 0; ci < colspans.length; ci++) {
                        var C = colspans[ci];
                        if (C.col === cc && C.span === sp) {
                            return true;
                        }
                    }
                    for (var ciO = 0; ciO < colspans.length; ciO++) {
                        var Co = colspans[ciO];
                        if (rangesOverlapPdf(cc, cc + sp, Co.col, Co.col + Co.span)) {
                            return false;
                        }
                    }
                    for (var k = cc; k < cc + sp; k++) {
                        if (stacks[k] !== null && stacks[k].length > 0) {
                            return false;
                        }
                    }
                    return true;
                }
                for (var ci2 = 0; ci2 < colspans.length; ci2++) {
                    var C2 = colspans[ci2];
                    if (cc >= C2.col && cc < C2.col + C2.span) {
                        return false;
                    }
                }
                return true;
            }

            function placePdf(itj) {
                var cc = itj.col;
                var sp = itj.span;
                if (sp > 1) {
                    var merged = false;
                    for (var cm = 0; cm < colspans.length; cm++) {
                        if (colspans[cm].col === cc && colspans[cm].span === sp) {
                            colspans[cm].items.push(itj);
                            merged = true;
                            break;
                        }
                    }
                    if (!merged) {
                        colspans.push({ col: cc, span: sp, items: [itj] });
                    }
                } else {
                    if (stacks[cc] === null) {
                        stacks[cc] = [];
                    }
                    stacks[cc].push(itj);
                }
            }

            var rj = ri;
            while (rj < items.length) {
                if (!canPlacePdf(items[rj])) {
                    break;
                }
                placePdf(items[rj]);
                rj++;
            }
            if (rj === ri) {
                ri++;
                continue;
            }
            rows.push({ colspans: colspans, stacks: stacks });
            ri = rj;
        }

        rows.forEach(function(row) {
            var csp = row.colspans;
            var stk = row.stacks;
            var tbl = document.createElement('table');
            tbl.setAttribute('data-pdf-lh', '1');
            tbl.style.width = '100%';
            tbl.style.tableLayout = 'fixed';
            tbl.style.borderCollapse = 'collapse';
            tbl.style.marginBottom = '6px';
            tbl.style.lineHeight = String(secSt.line_height);
            var tr = document.createElement('tr');
            var c = 0;
            while (c < n) {
                var block = null;
                for (var cix = 0; cix < csp.length; cix++) {
                    if (csp[cix].col === c) {
                        block = csp[cix];
                        break;
                    }
                }
                if (block) {
                    var sp = block.span;
                    var first = block.items[0];
                    var sc = first.col;
                    var tdM = document.createElement('td');
                    tdM.colSpan = sp;
                    tdM.style.width = ((sp * pctNum) / n).toFixed(2) + '%';
                    tdM.style.lineHeight = String(secSt.line_height);
                    tdM.style.verticalAlign = secSt.column_align_v[sc] || 'top';
                    tdM.style.textAlign = secSt.column_align_h[sc] || 'left';
                    tdM.style.padding = '0 6px';
                    block.items.forEach(function(sit, idx, arr) {
                        var divM = document.createElement('div');
                        divM.className = 'pdf-preview-line';
                        divM.style.lineHeight = 'inherit';
                        if (idx < arr.length - 1) divM.style.marginBottom = '0.4em';
                        divM.innerHTML = sit.html;
                        tdM.appendChild(divM);
                    });
                    tr.appendChild(tdM);
                    c += sp;
                } else if (stk[c] !== null && stk[c].length > 0) {
                    var tdS = document.createElement('td');
                    tdS.style.width = pct;
                    tdS.style.lineHeight = String(secSt.line_height);
                    tdS.style.verticalAlign = secSt.column_align_v[c] || 'top';
                    tdS.style.textAlign = secSt.column_align_h[c] || 'left';
                    tdS.style.padding = '0 6px';
                    stk[c].forEach(function(sit, idx, arr) {
                        var d = document.createElement('div');
                        d.className = 'pdf-preview-line';
                        d.style.lineHeight = 'inherit';
                        if (idx < arr.length - 1) d.style.marginBottom = '0.4em';
                        d.innerHTML = sit.html;
                        tdS.appendChild(d);
                    });
                    tr.appendChild(tdS);
                    c++;
                } else {
                    var tdE = document.createElement('td');
                    tdE.style.width = pct;
                    tdE.style.lineHeight = String(secSt.line_height);
                    tdE.style.verticalAlign = secSt.column_align_v[c] || 'top';
                    tdE.style.padding = '0 4px';
                    tr.appendChild(tdE);
                    c++;
                }
            }
            tbl.appendChild(tr);
            wrap.appendChild(tbl);
        });
        previewEl.appendChild(wrap);
    }

    function rebuildAllPreviews() {
        rebuildGridPreview(headerList, previewHeader);
        rebuildGridPreview(patientList, previewPatient);
        rebuildGridPreview(labFirmasList, previewLabFirmas);
        rebuildGridPreview(footerList, previewFooter);
    }

    function onInstanceColumnChange() {
        var li = this.closest('.pdf-instance-item');
        if (li) updateSpanOptionsForRow(li);
        rebuildAllPreviews();
    }

    function wireInstanceSelects() {
        document.querySelectorAll('.instance-column').forEach(function(el) {
            el.removeEventListener('change', onInstanceColumnChange);
            el.addEventListener('change', onInstanceColumnChange);
        });
        document.querySelectorAll('.instance-span').forEach(function(el) {
            el.removeEventListener('change', rebuildAllPreviews);
            el.addEventListener('change', rebuildAllPreviews);
        });
    }

    function createInstanceRow(uid, elementType, colCount, enabled, column, columnSpan) {
        if (columnSpan == null) columnSpan = 1;
        var label = (window._elementLabels && window._elementLabels[elementType]) ? window._elementLabels[elementType] : elementType;
        var li = document.createElement('li');
        li.className = 'list-group-item pdf-instance-item';
        li.setAttribute('data-uid', uid);
        li.setAttribute('data-element-type', elementType);
        var sel = document.createElement('select');
        sel.className = 'form-select form-select-sm instance-column';
        sel.style.maxWidth = '11rem';
        fillColumnSelect(sel, colCount, enabled ? String(column) : '-1');
        var spanSel = document.createElement('select');
        spanSel.className = 'form-select form-select-sm instance-span';
        spanSel.style.maxWidth = '9rem';
        spanSel.title = 'Cuántas columnas ocupa el elemento';
        var col = enabled ? Math.max(0, Math.min(colCount - 1, column)) : 0;
        var maxS = enabled ? Math.max(1, colCount - col) : 1;
        var sp = enabled ? Math.max(1, Math.min(maxS, columnSpan)) : 1;
        fillSpanSelectElement(spanSel, maxS, sp);
        if (!enabled) spanSel.disabled = true;
        var wrap = document.createElement('div');
        wrap.className = 'd-flex flex-wrap align-items-center gap-2';
        var h = document.createElement('span');
        h.className = 'text-muted instance-drag-handle';
        h.style.cursor = 'grab';
        h.title = 'Arrastrar';
        h.innerHTML = '<i class="fa-solid fa-grip-vertical"></i>';
        var lab = document.createElement('div');
        lab.className = 'flex-grow-1';
        lab.innerHTML = '';
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
        wrap.appendChild(spanSel);
        wrap.appendChild(lab);
        wrap.appendChild(bDup);
        wrap.appendChild(bDel);
        var head = document.createElement('div');
        head.className = 'pdf-instance-head mb-2 pb-2 border-bottom';
        head.innerHTML = '<strong class="d-block">' + escapeHtml(label) + '</strong><span class="small text-muted font-monospace">' + escapeHtml(elementType) + '</span>';
        li.appendChild(head);
        li.appendChild(wrap);
        li.insertAdjacentHTML('beforeend',
            '<div class="row g-3 mt-2 pdf-text-style-controls">' +
            '  <div class="col-12 col-md-6 col-lg-4"><label class="form-label small mb-1">Fuente</label><select class="form-select form-select-sm instance-font-family"><option value="DejaVu Sans">DejaVu Sans</option><option value="Helvetica">Helvetica</option><option value="Arial">Arial</option><option value="Times New Roman">Times New Roman</option><option value="Courier New">Courier New</option></select></div>' +
            '  <div class="col-6 col-md-3 col-lg-2"><label class="form-label small mb-1">Tamaño</label><input type="number" class="form-control form-control-sm instance-font-size" min="6" max="24" step="0.5" value="10"></div>' +
            '  <div class="col-6 col-md-3 col-lg-2"><label class="form-label small mb-1">Grosor</label><select class="form-select form-select-sm instance-font-weight"><option value="normal">normal</option><option value="bold">bold</option><option value="100">100</option><option value="200">200</option><option value="300">300</option><option value="400">400</option><option value="500">500</option><option value="600">600</option><option value="700">700</option><option value="800">800</option><option value="900">900</option></select></div>' +
            '  <div class="col-6 col-md-3 col-lg-2"><label class="form-label small mb-1">Color</label><input type="color" class="form-control form-control-color form-control-sm instance-font-color" value="#333333"></div>' +
            '  <div class="col-6 col-md-3 col-lg-2"><label class="form-label small mb-1">Estilo</label><select class="form-select form-select-sm instance-font-style"><option value="normal">Normal</option><option value="italic">Italic</option><option value="oblique">Oblique</option></select></div>' +
            '  <div class="col-6 col-md-4 col-lg-3"><label class="form-label small mb-1">Transformación</label><select class="form-select form-select-sm instance-text-transform"><option value="none">Normal</option><option value="uppercase">MAYÚSCULAS</option><option value="lowercase">minúsculas</option><option value="capitalize">Tipo Título</option></select></div>' +
            '  <div class="col-6 col-md-4 col-lg-2"><label class="form-label small mb-1">Esp. letras</label><input type="number" class="form-control form-control-sm instance-letter-spacing" min="-0.2" max="1" step="0.01" value="0"></div>' +
            '  <div class="col-6 col-md-4 col-lg-2"><label class="form-label small mb-1">Interlineado</label><input type="number" class="form-control form-control-sm instance-line-height" min="1" max="3" step="0.05" value="1.35"></div>' +
            '  <div class="col-12 col-md-6 col-lg-3"><label class="form-label small mb-1">Sombra</label><select class="form-select form-select-sm instance-text-shadow"><option value="none">Sin sombra</option><option value="soft">Suave</option><option value="medium">Media</option><option value="strong">Fuerte</option></select></div>' +
            '</div>'
        );
        return li;
    }

    function listBySectionKey(key) {
        if (key === 'header') return headerList;
        if (key === 'patient_doctor') return patientList;
        if (key === 'lab_firmas') return labFirmasList;
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
        ul.appendChild(createInstanceRow(newUid(), type, n, true, 0, 1));
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
            var spanOrig = li.querySelector('.instance-span');
            var sp = en && spanOrig && !spanOrig.disabled ? parseInt(spanOrig.value, 10) : 1;
            if (isNaN(sp) || sp < 1) sp = 1;
            var clone = createInstanceRow(newUid(), type, n, en, en ? col : 0, en ? sp : 1);
            if (!en && sel) clone.querySelector('.instance-column').value = '-1';
            var ts = readInstanceTextStyle(li);
            var setIf = function(q, v) {
                var el = clone.querySelector(q);
                if (el) el.value = String(v);
            };
            setIf('.instance-font-family', ts.font_family);
            setIf('.instance-font-size', ts.font_size_pt);
            setIf('.instance-font-weight', ts.font_weight);
            setIf('.instance-font-color', ts.font_color);
            setIf('.instance-font-style', ts.font_style);
            setIf('.instance-text-transform', ts.text_transform);
            setIf('.instance-letter-spacing', ts.letter_spacing_em);
            setIf('.instance-line-height', ts.line_height);
            setIf('.instance-text-shadow', ts.text_shadow);
            ul.insertBefore(clone, li.nextSibling);
            wireInstanceSelects();
            rebuildAllPreviews();
        }
        if (del && ul) {
            li.remove();
            rebuildAllPreviews();
        }
    });

    [secColsH, secColsP, secColsL, secColsF].forEach(function(inp) {
        if (!inp) return;
        inp.addEventListener('change', function() {
            rebuildColumnSelects();
            if (inp === secColsH) rebuildSectionStyleTable('header');
            else if (inp === secColsP) rebuildSectionStyleTable('patient_doctor');
            else if (inp === secColsL) rebuildSectionStyleTable('lab_firmas');
            else if (inp === secColsF) rebuildSectionStyleTable('footer');
            rebuildAllPreviews();
        });
    });

    var pdfEditorInst = document.getElementById('pdf-editor-instances');
    if (pdfEditorInst) {
        pdfEditorInst.addEventListener('change', function(e) {
            var t = e.target;
            if (t && (t.classList.contains('pdf-sec-col-h') || t.classList.contains('pdf-sec-col-v'))) {
                rebuildAllPreviews();
            } else if (t && t.closest('.pdf-text-style-controls')) {
                rebuildAllPreviews();
            }
        });
        pdfEditorInst.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('pdf-sec-line-height')) {
                rebuildAllPreviews();
            } else if (e.target && e.target.closest('.pdf-text-style-controls')) {
                rebuildAllPreviews();
            }
        });
    }

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
    var sortGroupLab = { name: 'pdf-lab-firmas', pull: false, put: false };
    if (labFirmasList && typeof Sortable !== 'undefined') {
        new Sortable(labFirmasList, { animation: 150, handle: '.instance-drag-handle', group: sortGroupLab, onEnd: rebuildAllPreviews });
    }

    var addSecEl = document.getElementById('add_element_section');
    if (addSecEl) {
        addSecEl.addEventListener('change', syncAddElementTypeOptions);
    }
    syncAddElementTypeOptions();

    wireInstanceSelects();
    rebuildAllPreviews();

    var pdfEditorRoot = document.getElementById('pdf-editor-instances');
    if (pdfEditorRoot) {
        pdfEditorRoot.addEventListener('blur', function(ev) {
            var t = ev.target;
            if (t && t.classList && (t.classList.contains('instance-font-size') || t.classList.contains('instance-letter-spacing') || t.classList.contains('instance-line-height'))) {
                clampInstanceStyleField(t);
                rebuildAllPreviews();
            }
        }, true);
    }

    function clampMargin(v) {
        var n = parseFloat(v);
        if (isNaN(n)) return 15;
        return Math.max(0, Math.min(50, n));
    }

    function readCardHeaderStyleForJson() {
        function pick(id, fallback) {
            var el = document.getElementById(id);
            return el ? String(el.value || '').trim() : fallback;
        }
        function pickNum(id, min, max, fallback) {
            var el = document.getElementById(id);
            var n = el ? parseFloat(el.value) : NaN;
            if (isNaN(n)) n = fallback;
            return Math.max(min, Math.min(max, n));
        }
        function pickHex(id, fallback) {
            var v = pick(id, fallback);
            return isValidPdfHexJs(v) ? v : fallback;
        }
        function pickLfText(id, fallback) {
            var el = document.getElementById(id);
            var v = el ? String(el.value || '').trim() : '';
            if (v === '') return fallback;
            return v.length > 120 ? v.slice(0, 120) : v;
        }
        var bg = pickHex('ch_bg_color', '#E9ECEF');
        var tx = pickHex('ch_text_color', '#212529');
        return {
            card_header: {
                bg_color: bg,
                text_color: tx,
                font_family: pickAllowedDomId('ch_font_family', 'font_families', 'DejaVu Sans'),
                font_size_pt: pickNum('ch_font_size', 7, 20, 10),
                font_weight: pickAllowedDomId('ch_font_weight', 'font_weights', '700'),
                font_style: pickAllowedDomId('ch_font_style', 'font_styles', 'normal'),
                text_transform: pickAllowedDomId('ch_text_transform', 'text_transforms', 'uppercase')
            },
            header_section: {
                separator_color: pickHex('hs_separator_color', '#0066CC')
            },
            notes: {
                title_bg_color: pickHex('ns_title_bg', '#FFF3CD'),
                title_text_color: pickHex('ns_title_text', '#664D03'),
                body_bg_color: pickHex('ns_body_bg', '#FFFFFF'),
                body_text_color: pickHex('ns_body_text', '#333333'),
                font_family: pickAllowedDomId('ns_font_family', 'font_families', 'DejaVu Sans'),
                font_size_pt: pickNum('ns_font_size', 7, 20, 9.5),
                font_weight: pickAllowedDomId('ns_font_weight', 'font_weights', 'normal'),
                font_style: pickAllowedDomId('ns_font_style', 'font_styles', 'normal'),
                text_transform: pickAllowedDomId('ns_text_transform', 'text_transforms', 'none'),
                line_height: pickNum('ns_line_height', 1, 3, 1.4)
            },
            lab_firmas: {
                title_bg_color: pickHex('lf_title_bg', '#FFF3CD'),
                title_text_color: pickHex('lf_title_text', '#664D03'),
                body_bg_color: pickHex('lf_body_bg', '#FFFFFF'),
                body_text_color: pickHex('lf_body_text', '#333333'),
                font_family: pickAllowedDomId('lf_font_family', 'font_families', 'DejaVu Sans'),
                font_size_pt: pickNum('lf_font_size', 7, 20, 9.5),
                font_weight: pickAllowedDomId('lf_font_weight', 'font_weights', 'normal'),
                font_style: pickAllowedDomId('lf_font_style', 'font_styles', 'normal'),
                text_transform: pickAllowedDomId('lf_text_transform', 'text_transforms', 'none'),
                line_height: pickNum('lf_line_height', 1, 3, 1.4),
                section_title: pickLfText('lf_section_title', 'VALIDACIÓN Y APROBACIÓN'),
                label_validator: pickLfText('lf_label_validator', 'Validado por:'),
                label_seal: pickLfText('lf_label_seal', 'Sello'),
                label_approver: pickLfText('lf_label_approver', 'Aprobado por:'),
                label_cargo: pickLfText('lf_label_cargo', 'Cargo:')
            },
            results_table: {
                header_bg_color: pickHex('rs_header_bg', '#0066CC'),
                header_text_color: pickHex('rs_header_text', '#FFFFFF'),
                body_bg_color: pickHex('rs_body_bg', '#FFFFFF'),
                body_transparent: !!(document.getElementById('rs_body_transparent') && document.getElementById('rs_body_transparent').checked),
                body_text_color: pickHex('rs_body_text', '#333333'),
                border_color: pickHex('rs_border_color', '#DDDDDD'),
                segment_bg_color: pickHex('rs_segment_bg', '#E9ECEF'),
                segment_transparent: !!(document.getElementById('rs_segment_transparent') && document.getElementById('rs_segment_transparent').checked),
                segment_border_color: pickHex('rs_segment_border_color', '#DDDDDD'),
                segment_border_width_px: Math.round(pickNum('rs_segment_border_width', 0, 4, 1)),
                segment_shadow: pickAllowedDomId('rs_segment_shadow', 'segment_shadows', 'none'),
                font_family: pickAllowedDomId('rs_font_family', 'font_families', 'DejaVu Sans'),
                font_size_pt: pickNum('rs_font_size', 7, 20, 9),
                font_weight: pickAllowedDomId('rs_font_weight', 'font_weights', 'normal'),
                font_style: pickAllowedDomId('rs_font_style', 'font_styles', 'normal'),
                text_transform: pickAllowedDomId('rs_text_transform', 'text_transforms', 'none'),
                line_height: pickNum('rs_line_height', 1, 3, 1.35)
            }
        };
    }

    function validatePdfEditorStylesBeforeSave() {
        var errs = [];
        var ff = pdfAllow('font_families');
        var fw = pdfAllow('font_weights');
        var fst = pdfAllow('font_styles');
        var tt = pdfAllow('text_transforms');
        var sh = pdfAllow('text_shadows');
        function pushIfBadHex(id, msg) {
            var el = document.getElementById(id);
            if (!el) return;
            var v = String(el.value || '').trim();
            if (!isValidPdfHexJs(v)) errs.push(msg);
        }
        function pushIfBadSelect(id, allowed, msg) {
            var el = document.getElementById(id);
            if (!el) return;
            if (allowed.indexOf(String(el.value || '').trim()) < 0) errs.push(msg);
        }
        function pushIfBadNum(id, min, max, msg) {
            var el = document.getElementById(id);
            if (!el) return;
            var n = parseFloat(el.value);
            if (isNaN(n) || n < min || n > max) errs.push(msg);
        }
        pushIfBadHex('ch_bg_color', 'Color de fondo (card header): use #RRGGBB.');
        pushIfBadHex('ch_text_color', 'Color de texto (card header): use #RRGGBB.');
        pushIfBadSelect('ch_font_family', ff, 'Fuente no permitida en card header.');
        pushIfBadNum('ch_font_size', 7, 20, 'Tamaño de fuente del card header: entre 7 y 20 pt.');
        pushIfBadSelect('ch_font_weight', fw, 'Grosor no permitido en card header.');
        pushIfBadSelect('ch_font_style', fst, 'Estilo no permitido en card header.');
        pushIfBadSelect('ch_text_transform', tt, 'Transformación no permitida en card header.');
        pushIfBadHex('hs_separator_color', 'Color del separador de sección: use #RRGGBB.');
        ['ns_title_bg', 'ns_title_text', 'ns_body_bg', 'ns_body_text'].forEach(function(id) {
            pushIfBadHex(id, 'Color inválido en notas del resultado.');
        });
        pushIfBadSelect('ns_font_family', ff, 'Fuente no permitida en notas.');
        pushIfBadNum('ns_font_size', 7, 20, 'Tamaño en notas: entre 7 y 20 pt.');
        pushIfBadSelect('ns_font_weight', fw, 'Grosor no permitido en notas.');
        pushIfBadSelect('ns_font_style', fst, 'Estilo no permitido en notas.');
        pushIfBadSelect('ns_text_transform', tt, 'Transformación no permitida en notas.');
        pushIfBadNum('ns_line_height', 1, 3, 'Interlineado en notas: entre 1 y 3.');
        ['lf_title_bg', 'lf_title_text', 'lf_body_bg', 'lf_body_text'].forEach(function(id) {
            pushIfBadHex(id, 'Color inválido en firmas.');
        });
        pushIfBadSelect('lf_font_family', ff, 'Fuente no permitida en firmas.');
        pushIfBadNum('lf_font_size', 7, 20, 'Tamaño en firmas: entre 7 y 20 pt.');
        pushIfBadSelect('lf_font_weight', fw, 'Grosor no permitido en firmas.');
        pushIfBadSelect('lf_font_style', fst, 'Estilo no permitido en firmas.');
        pushIfBadSelect('lf_text_transform', tt, 'Transformación no permitida en firmas.');
        pushIfBadNum('lf_line_height', 1, 3, 'Interlineado en firmas: entre 1 y 3.');
        ['lf_section_title', 'lf_label_validator', 'lf_label_seal', 'lf_label_approver', 'lf_label_cargo'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (String(el.value || '').length > 120) errs.push('Texto demasiado largo en firmas (máx. 120 caracteres).');
        });
        ['rs_header_bg', 'rs_header_text', 'rs_body_bg', 'rs_body_text', 'rs_border_color', 'rs_segment_bg', 'rs_segment_border_color'].forEach(function(id) {
            pushIfBadHex(id, 'Color inválido en tabla de resultados.');
        });
        pushIfBadSelect('rs_font_family', ff, 'Fuente no permitida en tabla de resultados.');
        pushIfBadNum('rs_font_size', 7, 20, 'Tamaño en tabla de resultados: entre 7 y 20 pt.');
        pushIfBadSelect('rs_font_weight', fw, 'Grosor no permitido en tabla de resultados.');
        pushIfBadSelect('rs_font_style', fst, 'Estilo no permitido en tabla de resultados.');
        pushIfBadSelect('rs_text_transform', tt, 'Transformación no permitida en tabla de resultados.');
        pushIfBadNum('rs_line_height', 1, 3, 'Interlineado en tabla de resultados: entre 1 y 3.');
        pushIfBadNum('rs_segment_border_width', 0, 4, 'Grosor de borde de segmento: entre 0 y 4 px.');
        pushIfBadSelect('rs_segment_shadow', pdfAllow('segment_shadows'), 'Sombra de segmento no permitida.');

        document.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var label = (li.querySelector('.pdf-instance-head strong') && li.querySelector('.pdf-instance-head strong').textContent.trim()) || 'Elemento';
            var fam = li.querySelector('.instance-font-family');
            if (fam && ff.indexOf(String(fam.value || '').trim()) < 0) {
                errs.push('«' + label + '»: fuente no permitida.');
            }
            var fs = li.querySelector('.instance-font-size');
            if (fs) {
                var v = parseFloat(fs.value);
                if (isNaN(v) || v < 6 || v > 24) errs.push('«' + label + '»: tamaño entre 6 y 24 pt.');
            }
            var fwEl = li.querySelector('.instance-font-weight');
            if (fwEl && fw.indexOf(String(fwEl.value || '').trim()) < 0) errs.push('«' + label + '»: grosor no permitido.');
            var fstEl = li.querySelector('.instance-font-style');
            if (fstEl && fst.indexOf(String(fstEl.value || '').trim()) < 0) errs.push('«' + label + '»: estilo no permitido.');
            var ttEl = li.querySelector('.instance-text-transform');
            if (ttEl && tt.indexOf(String(ttEl.value || '').trim()) < 0) errs.push('«' + label + '»: transformación no permitida.');
            var col = li.querySelector('.instance-font-color');
            if (col && !isValidPdfHexJs(String(col.value || '').trim())) errs.push('«' + label + '»: color inválido (#RRGGBB).');
            var ls = li.querySelector('.instance-letter-spacing');
            if (ls) {
                var lsV = parseFloat(ls.value);
                if (isNaN(lsV) || lsV < -0.2 || lsV > 1) errs.push('«' + label + '»: espaciado entre letras entre -0,2 y 1 em.');
            }
            var lh = li.querySelector('.instance-line-height');
            if (lh) {
                var lhV = parseFloat(lh.value);
                if (isNaN(lhV) || lhV < 1 || lhV > 3) errs.push('«' + label + '»: interlineado entre 1 y 3.');
            }
            var shEl = li.querySelector('.instance-text-shadow');
            if (shEl && sh.indexOf(String(shEl.value || '').trim()) < 0) errs.push('«' + label + '»: sombra no permitida.');
        });
        return errs;
    }

    function clampInstanceStyleField(el) {
        if (!el || !el.classList) return;
        if (el.classList.contains('instance-font-size')) {
            var v = parseFloat(el.value);
            if (isNaN(v)) v = 10;
            el.value = String(Math.max(6, Math.min(24, Math.round(v * 2) / 2)));
        } else if (el.classList.contains('instance-letter-spacing')) {
            var a = parseFloat(el.value);
            if (isNaN(a)) a = 0;
            el.value = String(Math.round(Math.max(-0.2, Math.min(1, a)) * 100) / 100);
        } else if (el.classList.contains('instance-line-height')) {
            var b = parseFloat(el.value);
            if (isNaN(b)) b = 1.35;
            el.value = String(Math.round(Math.max(1, Math.min(3, b)) * 100) / 100);
        }
    }

    function parseInstanceLi(li, section) {
        var sel = li.querySelector('.instance-column');
        var v = sel ? parseInt(sel.value, 10) : -1;
        var cols = section === 'header' ? clampCols(secColsH.value) : (section === 'patient_doctor' ? clampCols(secColsP.value) : (section === 'lab_firmas' ? clampCols(secColsL ? secColsL.value : '3') : clampCols(secColsF.value)));
        var enabled = v >= 0;
        var col = enabled ? Math.max(0, Math.min(cols - 1, v)) : 0;
        var spanSel = li.querySelector('.instance-span');
        var span = 1;
        if (enabled && spanSel && !spanSel.disabled) {
            span = parseInt(spanSel.value, 10);
            if (isNaN(span) || span < 1) span = 1;
            var maxS = Math.max(1, cols - col);
            span = Math.max(1, Math.min(maxS, span));
        }
        return {
            uid: li.getAttribute('data-uid') || '',
            element_type: li.getAttribute('data-element-type') || '',
            section: section,
            enabled: enabled,
            column: col,
            column_span: span,
            text_style: readInstanceTextStyle(li)
        };
    }

    document.getElementById('pdf_tpl_form').addEventListener('submit', function(ev) {
        var styleErrs = validatePdfEditorStylesBeforeSave();
        if (styleErrs.length > 0) {
            ev.preventDefault();
            var maxShow = 10;
            var msg = styleErrs.slice(0, maxShow).join('\n');
            if (styleErrs.length > maxShow) {
                msg += '\n… (' + styleErrs.length + ' problemas)';
            }
            alert('Revise los estilos antes de guardar:\n\n' + msg);
            return;
        }
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
        if (labFirmasList) {
            labFirmasList.querySelectorAll('.pdf-instance-item').forEach(function(li) {
                instances.push(parseInstanceLi(li, 'lab_firmas'));
            });
        }

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
            version: 7,
            blocks: blocks,
            section_layouts: buildSectionLayoutsForJson(),
            instances: instances,
            margins_mm: {
                top: clampMargin(document.getElementById('margin_top').value),
                right: clampMargin(document.getElementById('margin_right').value),
                bottom: clampMargin(document.getElementById('margin_bottom').value),
                left: clampMargin(document.getElementById('margin_left').value)
            },
            watermark: buildWatermarkForJson(),
            page_style: readCardHeaderStyleForJson()
        });
    });
});
</script>
<?= $this->endSection() ?>
