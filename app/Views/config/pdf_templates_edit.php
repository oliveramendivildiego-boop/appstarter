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
$hg = \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle($ps['header_grid'] ?? []);
$pd = \App\Services\ReportPdfLayoutService::normalizePatientDoctorGridStyle($ps['patient_doctor_grid'] ?? []);
$ft = \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle($ps['footer_grid'] ?? []);
$pp = \App\Services\ReportPdfLayoutService::normalizePrintPaginationStyle($ps['print_pagination'] ?? []);
$gpb = \App\Services\ReportPdfLayoutService::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
$pdfGridChk = static function (array $a, string $k): string {
    $v = $a[$k] ?? true;

    return ($v === false || $v === 0 || $v === '0' || $v === 'false') ? '' : ' checked';
};
$pdfGridLineMode = static function (array $a, string $k): string {
    return (isset($a[$k]) && trim((string) $a[$k]) === 'inline') ? 'inline' : 'stacked';
};
$pdFieldUi = [
    'paciente_nombre'   => 'Nombre del paciente',
    'paciente_genero'   => 'Género',
    'paciente_edad'     => 'Edad',
    'paciente_telefono' => 'Teléfono',
    'diagnostico_presuntivo' => 'Diagnóstico presuntivo',
    'medico'            => 'Médico',
    'fecha_recepcion'   => 'Fecha de recepción',
    'fecha_reporte'     => 'Fecha de reporte',
    'numero_orden'      => 'Nº de orden',
];
$hgFieldUi = \App\Services\ReportPdfLayoutService::headerFieldLabels();
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
    'diagnostico_presuntivo' => 'Diagnóstico presuntivo:',
    'medico'            => 'Médico:',
    'fecha_recepcion'   => 'Fecha de recepción:',
    'fecha_reporte'     => 'Fecha de reporte:',
    'numero_orden'      => 'No. Orden:',
    'lab_firmas_title'     => '',
    'lab_firmas_validator' => '',
    'lab_firmas_seal'                   => '',
    'lab_firmas_approver_signature'     => '',
    'lab_firmas_approver_name'          => '',
    'lab_firmas_approver_cargo'         => '',
    'lab_firmas_matricula'              => '',
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
    <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar plantilla</button>
        <a href="<?= site_url('config/pdf-templates') ?>" class="btn btn-outline-secondary">Volver al listado</a>
        <a href="<?= site_url('config') ?>?tab=sistema" class="btn btn-outline-secondary">Configuración</a>
    </div>

<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <ul class="nav nav-tabs flex-wrap gap-1" id="pdf_config_tabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" type="button" data-config-tab="general">General</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" type="button" data-config-tab="header">Encabezado</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" type="button" data-config-tab="patient_doctor">Paciente / médico</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" type="button" data-config-tab="results">Resultados</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" type="button" data-config-tab="notes">Notas</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" type="button" data-config-tab="lab_firmas">Validación / firmas</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" type="button" data-config-tab="footer">Pie</button></li>
        </ul>
        <p class="small text-muted mt-2 mb-0">Cada pestaña muestra solo la configuración del bloque correspondiente.</p>
    </div>
</div>

<div class="card shadow-sm mb-4 pdf-margins-card pdf-config-panel" data-config-panels="general">
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
            <div class="col-12 col-md-3 d-flex align-items-end">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="ch_bg_transparent" <?= ! empty($ch['bg_transparent']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="ch_bg_transparent">Fondo transparente (títulos de análisis)</label>
                </div>
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

<div class="card shadow-sm mb-4 border border-primary border-opacity-25 pdf-config-panel" data-config-panels="header,patient_doctor,footer">
    <div class="card-header bg-primary-subtle border-bottom">
        <h5 class="mb-0">Cuadrículas PDF: encabezado superior, paciente/médico y pie</h5>
        <p class="small text-muted mb-0 mt-1">Fondo de celdas (con opción transparente), tipografía, bordes verticales entre columnas y textos de etiquetas editables (incl. leyenda del QR y prefijo de «generado el»).</p>
    </div>
    <div class="card-body">
        <div class="pdf-subpanel" data-config-subpanel="header">
        <h6 class="text-secondary">Encabezado superior (logo, datos del laboratorio, QR)</h6>
        <div class="row g-2 mb-2">
            <div class="col-6 col-md-2"><label class="form-label small" for="hg_body_bg">Fondo celdas</label><input type="color" class="form-control form-control-color" id="hg_body_bg" value="<?= esc((string) ($hg['body_bg_color'] ?? '#FFFFFF'), 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="hg_body_text">Texto</label><input type="color" class="form-control form-control-color" id="hg_body_text" value="<?= esc((string) ($hg['body_text_color'] ?? '#333333'), 'attr') ?>"></div>
            <div class="col-12 col-md-3 d-flex align-items-end"><div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="hg_body_transparent" <?= ! empty($hg['body_transparent']) ? 'checked' : '' ?>><label class="form-check-label small" for="hg_body_transparent">Celdas sin fondo</label></div></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="hg_column_border_width">Borde columnas (px)</label><input type="number" class="form-control form-control-sm" id="hg_column_border_width" min="0" max="4" step="1" value="<?= esc((string) (int) ($hg['column_border_width_px'] ?? 0), 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="hg_column_border_color">Color borde</label><input type="color" class="form-control form-control-color" id="hg_column_border_color" value="<?= esc((string) ($hg['column_border_color'] ?? '#DDDDDD'), 'attr') ?>"></div>
            <div class="col-12 col-md-3"><label class="form-label small" for="hg_font_family">Fuente</label><select class="form-select form-select-sm" id="hg_font_family"><?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?><option value="<?= esc($ff, 'attr') ?>" <?= ($hg['font_family'] ?? '') === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option><?php endforeach; ?></select></div>
            <div class="col-4 col-md-2"><label class="form-label small" for="hg_font_size">Tamaño</label><input type="number" class="form-control form-control-sm" id="hg_font_size" min="7" max="20" step="0.5" value="<?= esc((string) ($hg['font_size_pt'] ?? 9.5), 'attr') ?>"></div>
            <div class="col-4 col-md-2"><label class="form-label small" for="hg_font_weight">Grosor</label><select class="form-select form-select-sm" id="hg_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= ($hg['font_weight'] ?? '') === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-4 col-md-2"><label class="form-label small" for="hg_font_style">Estilo</label><select class="form-select form-select-sm" id="hg_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= ($hg['font_style'] ?? '') === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="hg_text_transform">Transformación</label><select class="form-select form-select-sm" id="hg_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= ($hg['text_transform'] ?? '') === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="hg_line_height">Interlineado</label><input type="number" class="form-control form-control-sm" id="hg_line_height" min="1" max="3" step="0.05" value="<?= esc((string) ($hg['line_height'] ?? 1.35), 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="hg_qr_size_percent" title="100 % = tamaño base del PDF (~75 px de alto). Suba el porcentaje para ampliar el código QR (máx. 400 %).">Tamaño del código QR (%)</label><input type="number" class="form-control form-control-sm" id="hg_qr_size_percent" min="50" max="400" step="5" value="<?= esc((string) (int) ($hg['qr_size_percent'] ?? 100), 'attr') ?>"><span class="form-text small text-muted d-block">50–400 % respecto al tamaño base</span></div>
        </div>
        <p class="small text-muted mb-2">Etiquetas del encabezado: texto, visibilidad, disposición respecto al valor (o al QR) y tipografía del texto de etiqueta en el PDF.</p>
        <div class="table-responsive mb-2">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light"><tr class="small"><th>Elemento</th><th>Texto de la etiqueta</th><th class="text-center" style="width:6rem;">Mostrar</th><th style="min-width:9rem;">Etiqueta vs valor</th></tr></thead>
                <tbody class="small">
                <?php foreach (\App\Services\ReportPdfLayoutService::HEADER_GRID_LABEL_DEFAULTS as $hfid => $defHgLbl):
                    $hgLab = (string) ($hg['label_' . $hfid] ?? $defHgLbl);
                    ?>
                    <tr>
                        <td><?= esc($hgFieldUi[$hfid] ?? $hfid) ?></td>
                        <td><input type="text" class="form-control form-control-sm" id="hg_label_<?= esc($hfid, 'attr') ?>" maxlength="120" value="<?= esc($hgLab, 'attr') ?>"></td>
                        <td class="text-center"><input type="checkbox" class="form-check-input" id="hg_show_label_<?= esc($hfid, 'attr') ?>" value="1"<?= $pdfGridChk($hg, 'show_label_' . $hfid) ?>></td>
                        <td>
                            <?php $hm = $pdfGridLineMode($hg, 'label_' . $hfid . '_line_mode'); ?>
                            <select class="form-select form-select-sm" id="hg_label_<?= esc($hfid, 'attr') ?>_line_mode">
                                <option value="stacked" <?= $hm === 'stacked' ? 'selected' : '' ?>>Debajo</option>
                                <option value="inline" <?= $hm === 'inline' ? 'selected' : '' ?>>Misma línea</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
                    <tr>
                        <td><?= esc($hgFieldUi['qr'] ?? 'qr') ?> — leyenda</td>
                        <td><input type="text" class="form-control form-control-sm" id="hg_label_qr_hint" maxlength="120" value="<?= esc((string) ($hg['label_qr_hint'] ?? ''), 'attr') ?>"></td>
                        <td class="text-center"><input type="checkbox" class="form-check-input" id="hg_show_label_qr_hint" value="1"<?= $pdfGridChk($hg, 'show_label_qr_hint') ?>></td>
                        <td>
                            <?php $hqm = $pdfGridLineMode($hg, 'label_qr_hint_line_mode'); ?>
                            <select class="form-select form-select-sm" id="hg_label_qr_hint_line_mode">
                                <option value="stacked" <?= $hqm === 'stacked' ? 'selected' : '' ?>>Debajo del QR</option>
                                <option value="inline" <?= $hqm === 'inline' ? 'selected' : '' ?>>Misma línea</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light"><tr class="small"><th>Etiqueta (color / tipo)</th><th style="width:6rem;">Color</th><th style="width:6rem;">Tamaño (pt)</th><th style="width:7rem;">Grosor</th><th style="width:7rem;">Estilo</th><th style="width:9rem;">Transformación</th></tr></thead>
                <tbody class="small">
                <?php
                $hgBtc = (string) ($hg['body_text_color'] ?? '#333333');
                foreach (array_keys(\App\Services\ReportPdfLayoutService::HEADER_GRID_LABEL_DEFAULTS) as $hfid):
                    $cHg = (string) ($hg['label_' . $hfid . '_text_color'] ?? $hgBtc);
                    $fsHg = $hg['label_' . $hfid . '_font_size_pt'] ?? $hg['font_size_pt'] ?? 9.5;
                    $fwHg = (string) ($hg['label_' . $hfid . '_font_weight'] ?? $hg['font_weight'] ?? 'normal');
                    $fstHg = (string) ($hg['label_' . $hfid . '_font_style'] ?? $hg['font_style'] ?? 'normal');
                    $ttHg = (string) ($hg['label_' . $hfid . '_text_transform'] ?? $hg['text_transform'] ?? 'none');
                    ?>
                    <tr>
                        <td><?= esc($hgFieldUi[$hfid] ?? $hfid) ?></td>
                        <td><input type="color" class="form-control form-control-color" id="hg_hdr_<?= esc($hfid, 'attr') ?>_color" value="<?= esc($cHg, 'attr') ?>"></td>
                        <td><input type="number" class="form-control form-control-sm" id="hg_hdr_<?= esc($hfid, 'attr') ?>_fs" min="7" max="20" step="0.5" value="<?= esc((string) $fsHg, 'attr') ?>"></td>
                        <td><select class="form-select form-select-sm" id="hg_hdr_<?= esc($hfid, 'attr') ?>_fw"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= $fwHg === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="hg_hdr_<?= esc($hfid, 'attr') ?>_fst"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= $fstHg === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="hg_hdr_<?= esc($hfid, 'attr') ?>_tt"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $ttHg === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                    </tr>
                <?php endforeach; ?>
                    <tr>
                        <td>Leyenda del QR</td>
                        <?php
                        $cQr = (string) ($hg['label_qr_hint_text_color'] ?? $hgBtc);
                        $fsQr = $hg['label_qr_hint_font_size_pt'] ?? $hg['font_size_pt'] ?? 9.5;
                        $fwQr = (string) ($hg['label_qr_hint_font_weight'] ?? $hg['font_weight'] ?? 'normal');
                        $fstQr = (string) ($hg['label_qr_hint_font_style'] ?? $hg['font_style'] ?? 'normal');
                        $ttQr = (string) ($hg['label_qr_hint_text_transform'] ?? $hg['text_transform'] ?? 'none');
                        ?>
                        <td><input type="color" class="form-control form-control-color" id="hg_qr_hint_color" value="<?= esc($cQr, 'attr') ?>"></td>
                        <td><input type="number" class="form-control form-control-sm" id="hg_qr_hint_fs" min="7" max="20" step="0.5" value="<?= esc((string) $fsQr, 'attr') ?>"></td>
                        <td><select class="form-select form-select-sm" id="hg_qr_hint_fw"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= $fwQr === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="hg_qr_hint_fst"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= $fstQr === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="hg_qr_hint_tt"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $ttQr === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>
        <hr class="my-3 pdf-subpanel-divider" data-config-subpanel="patient_doctor">
        <div class="pdf-subpanel" data-config-subpanel="patient_doctor">
        <h6 class="text-secondary">Paciente y médico</h6>
        <div class="row g-2 mb-2">
            <div class="col-6 col-md-2"><label class="form-label small" for="pd_body_bg">Fondo celdas</label><input type="color" class="form-control form-control-color" id="pd_body_bg" value="<?= esc((string) ($pd['body_bg_color'] ?? '#F8F9FA'), 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="pd_body_text">Texto</label><input type="color" class="form-control form-control-color" id="pd_body_text" value="<?= esc((string) ($pd['body_text_color'] ?? '#333333'), 'attr') ?>"></div>
            <div class="col-12 col-md-3 d-flex align-items-end"><div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="pd_body_transparent" <?= ! empty($pd['body_transparent']) ? 'checked' : '' ?>><label class="form-check-label small" for="pd_body_transparent">Celdas sin fondo</label></div></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="pd_column_border_width">Borde columnas (px)</label><input type="number" class="form-control form-control-sm" id="pd_column_border_width" min="0" max="4" step="1" value="<?= esc((string) (int) ($pd['column_border_width_px'] ?? 0), 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="pd_column_border_color">Color borde</label><input type="color" class="form-control form-control-color" id="pd_column_border_color" value="<?= esc((string) ($pd['column_border_color'] ?? '#DDDDDD'), 'attr') ?>"></div>
            <div class="col-12 col-md-3"><label class="form-label small" for="pd_font_family">Fuente</label><select class="form-select form-select-sm" id="pd_font_family"><?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?><option value="<?= esc($ff, 'attr') ?>" <?= ($pd['font_family'] ?? '') === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option><?php endforeach; ?></select></div>
            <div class="col-4 col-md-2"><label class="form-label small" for="pd_font_size">Tamaño</label><input type="number" class="form-control form-control-sm" id="pd_font_size" min="7" max="20" step="0.5" value="<?= esc((string) ($pd['font_size_pt'] ?? 9.5), 'attr') ?>"></div>
            <div class="col-4 col-md-2"><label class="form-label small" for="pd_font_weight">Grosor</label><select class="form-select form-select-sm" id="pd_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= ($pd['font_weight'] ?? '') === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-4 col-md-2"><label class="form-label small" for="pd_font_style">Estilo</label><select class="form-select form-select-sm" id="pd_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= ($pd['font_style'] ?? '') === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="pd_text_transform">Transformación</label><select class="form-select form-select-sm" id="pd_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= ($pd['text_transform'] ?? '') === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="pd_line_height">Interlineado</label><input type="number" class="form-control form-control-sm" id="pd_line_height" min="1" max="3" step="0.05" value="<?= esc((string) ($pd['line_height'] ?? 1.35), 'attr') ?>"></div>
        </div>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light"><tr class="small"><th>Campo</th><th>Etiqueta en PDF</th><th class="text-center" style="width:6rem;">Mostrar</th><th style="min-width:9rem;">Etiqueta vs valor</th><th style="width:6rem;">Color</th><th style="width:6rem;">Tamaño (pt)</th><th style="width:7rem;">Grosor</th><th style="width:7rem;">Estilo</th><th style="width:9rem;">Transformación</th><th style="width:8.5rem;">Entre etiqueta y resultado (px)</th><th style="width:7.5rem;">Arriba (px)</th><th style="width:7.5rem;">Abajo (px)</th></tr></thead>
                <tbody class="small">
                <?php foreach (\App\Services\ReportPdfLayoutService::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS as $fid => $defLbl):
                    $lab = (string) ($pd['label_' . $fid] ?? $defLbl);
                    $pdBtc = (string) ($pd['body_text_color'] ?? '#333333');
                    $cPd = (string) ($pd['label_' . $fid . '_text_color'] ?? $pdBtc);
                    $fsPd = $pd['label_' . $fid . '_font_size_pt'] ?? $pd['font_size_pt'] ?? 9.5;
                    $fwPd = (string) ($pd['label_' . $fid . '_font_weight'] ?? $pd['font_weight'] ?? 'normal');
                    $fstPd = (string) ($pd['label_' . $fid . '_font_style'] ?? $pd['font_style'] ?? 'normal');
                    $ttPd = (string) ($pd['label_' . $fid . '_text_transform'] ?? $pd['text_transform'] ?? 'none');
                    ?>
                    <tr>
                        <td><?= esc($pdFieldUi[$fid] ?? $fid) ?></td>
                        <td><input type="text" class="form-control form-control-sm" id="pd_label_<?= esc($fid, 'attr') ?>" maxlength="120" value="<?= esc($lab, 'attr') ?>"></td>
                        <td class="text-center"><input type="checkbox" class="form-check-input" id="pd_show_label_<?= esc($fid, 'attr') ?>" value="1"<?= $pdfGridChk($pd, 'show_label_' . $fid) ?>></td>
                        <td>
                            <?php $m = $pdfGridLineMode($pd, 'label_' . $fid . '_line_mode'); ?>
                            <select class="form-select form-select-sm" id="pd_label_<?= esc($fid, 'attr') ?>_line_mode">
                                <option value="stacked" <?= $m === 'stacked' ? 'selected' : '' ?>>Debajo</option>
                                <option value="inline" <?= $m === 'inline' ? 'selected' : '' ?>>Misma línea</option>
                            </select>
                        </td>
                        <td><input type="color" class="form-control form-control-color" id="pd_lbl_<?= esc($fid, 'attr') ?>_color" value="<?= esc($cPd, 'attr') ?>"></td>
                        <td><input type="number" class="form-control form-control-sm" id="pd_lbl_<?= esc($fid, 'attr') ?>_fs" min="7" max="20" step="0.5" value="<?= esc((string) $fsPd, 'attr') ?>"></td>
                        <td><select class="form-select form-select-sm" id="pd_lbl_<?= esc($fid, 'attr') ?>_fw"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= $fwPd === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="pd_lbl_<?= esc($fid, 'attr') ?>_fst"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= $fstPd === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="pd_lbl_<?= esc($fid, 'attr') ?>_tt"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $ttPd === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                        <td><input type="number" class="form-control form-control-sm" id="pd_label_<?= esc($fid, 'attr') ?>_value_gap_px" min="0" max="40" step="1" value="<?= esc((string) (int) ($pd['label_' . $fid . '_value_gap_px'] ?? 0), 'attr') ?>"></td>
                        <td><input type="number" class="form-control form-control-sm" id="pd_label_<?= esc($fid, 'attr') ?>_space_above_px" min="0" max="40" step="1" value="<?= esc((string) (int) ($pd['label_' . $fid . '_space_above_px'] ?? 0), 'attr') ?>"></td>
                        <td><input type="number" class="form-control form-control-sm" id="pd_label_<?= esc($fid, 'attr') ?>_space_below_px" min="0" max="40" step="1" value="<?= esc((string) (int) ($pd['label_' . $fid . '_space_below_px'] ?? 0), 'attr') ?>"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
        <hr class="my-3 pdf-subpanel-divider" data-config-subpanel="footer">
        <div class="pdf-subpanel" data-config-subpanel="footer">
        <h6 class="text-secondary">Pie de página</h6>
        <div class="row g-2 mb-2 align-items-end">
            <div class="col-12 col-md-4">
                <span class="small fw-semibold text-secondary d-block mb-1">Borde superior del bloque (línea sobre el pie)</span>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="ft_section_top_border_enabled" <?= ! empty($ft['section_top_border_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="ft_section_top_border_enabled">Mostrar</label>
                    </div>
                    <div>
                        <label class="form-label small mb-0" for="ft_section_top_border_width">Grosor (px)</label>
                        <input type="number" class="form-control form-control-sm" id="ft_section_top_border_width" min="0" max="6" step="1" value="<?= esc((string) (int) ($ft['section_top_border_width_px'] ?? 1), 'attr') ?>" style="max-width:5rem;">
                    </div>
                    <div>
                        <label class="form-label small mb-0" for="ft_section_top_border_color">Color</label>
                        <input type="color" class="form-control form-control-color" id="ft_section_top_border_color" value="<?= esc((string) ($ft['section_top_border_color'] ?? '#DDDDDD'), 'attr') ?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6 col-md-2"><label class="form-label small" for="ft_body_bg">Fondo celdas</label><input type="color" class="form-control form-control-color" id="ft_body_bg" value="<?= esc((string) ($ft['body_bg_color'] ?? '#FFFFFF'), 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ft_body_text">Texto</label><input type="color" class="form-control form-control-color" id="ft_body_text" value="<?= esc((string) ($ft['body_text_color'] ?? '#666666'), 'attr') ?>"></div>
            <div class="col-12 col-md-3 d-flex align-items-end"><div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="ft_body_transparent" <?= ! empty($ft['body_transparent']) ? 'checked' : '' ?>><label class="form-check-label small" for="ft_body_transparent">Celdas sin fondo</label></div></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ft_column_border_width">Borde columnas (px)</label><input type="number" class="form-control form-control-sm" id="ft_column_border_width" min="0" max="4" step="1" value="<?= esc((string) (int) ($ft['column_border_width_px'] ?? 0), 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ft_column_border_color">Color borde</label><input type="color" class="form-control form-control-color" id="ft_column_border_color" value="<?= esc((string) ($ft['column_border_color'] ?? '#DDDDDD'), 'attr') ?>"></div>
            <div class="col-12 col-md-3"><label class="form-label small" for="ft_font_family">Fuente</label><select class="form-select form-select-sm" id="ft_font_family"><?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?><option value="<?= esc($ff, 'attr') ?>" <?= ($ft['font_family'] ?? '') === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option><?php endforeach; ?></select></div>
            <div class="col-4 col-md-2"><label class="form-label small" for="ft_font_size">Tamaño</label><input type="number" class="form-control form-control-sm" id="ft_font_size" min="7" max="20" step="0.5" value="<?= esc((string) ($ft['font_size_pt'] ?? 8), 'attr') ?>"></div>
            <div class="col-4 col-md-2"><label class="form-label small" for="ft_font_weight">Grosor</label><select class="form-select form-select-sm" id="ft_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= ($ft['font_weight'] ?? '') === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-4 col-md-2"><label class="form-label small" for="ft_font_style">Estilo</label><select class="form-select form-select-sm" id="ft_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= ($ft['font_style'] ?? '') === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ft_text_transform">Transformación</label><select class="form-select form-select-sm" id="ft_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= ($ft['text_transform'] ?? '') === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ft_line_height">Interlineado</label><input type="number" class="form-control form-control-sm" id="ft_line_height" min="1" max="3" step="0.05" value="<?= esc((string) ($ft['line_height'] ?? 1.35), 'attr') ?>"></div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6 col-md-3"><label class="form-label small" for="ft_color_company">Color nombre laboratorio</label><input type="color" class="form-control form-control-color" id="ft_color_company" value="<?= esc((string) ($ft['footer_company_text_color'] ?? $ft['body_text_color']), 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ft_color_label_generated">Color texto antes de la fecha</label><input type="color" class="form-control form-control-color" id="ft_color_label_generated" value="<?= esc((string) ($ft['label_footer_generated_color'] ?? $ft['body_text_color']), 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ft_color_datetime">Color fecha y hora</label><input type="color" class="form-control form-control-color" id="ft_color_datetime" value="<?= esc((string) ($ft['label_footer_datetime_color'] ?? $ft['body_text_color']), 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ft_color_policy">Color texto legal / política</label><input type="color" class="form-control form-control-color" id="ft_color_policy" value="<?= esc((string) ($ft['footer_policy_text_color'] ?? $ft['body_text_color']), 'attr') ?>"></div>
        </div>
        <p class="small text-muted mb-2">Tamaño, grosor y estilo por texto (si no coincide con la vista, guarde la plantilla y recargue el reporte). El PDF y viewreport usan estos valores en el HTML.</p>
        <div class="table-responsive mb-2">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light"><tr class="small"><th>Texto del pie</th><th style="width:6rem;">Tamaño (pt)</th><th style="width:7rem;">Grosor</th><th style="width:7rem;">Estilo</th><th style="width:9rem;">Transformación</th></tr></thead>
                <tbody class="small">
                    <tr>
                        <td>Nombre del laboratorio</td>
                        <td><input type="number" class="form-control form-control-sm" id="ft_fs_company" min="7" max="20" step="0.5" value="<?= esc((string) ($ft['footer_company_font_size_pt'] ?? $ft['font_size_pt']), 'attr') ?>"></td>
                        <td><select class="form-select form-select-sm" id="ft_fw_company"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= ($ft['footer_company_font_weight'] ?? $ft['font_weight']) === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="ft_fst_company"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= ($ft['footer_company_font_style'] ?? $ft['font_style']) === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="ft_tt_company"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= ($ft['footer_company_text_transform'] ?? $ft['text_transform']) === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                    </tr>
                    <tr>
                        <td>Texto antes de la fecha</td>
                        <td><input type="number" class="form-control form-control-sm" id="ft_fs_label_gen" min="7" max="20" step="0.5" value="<?= esc((string) ($ft['label_footer_generated_font_size_pt'] ?? $ft['font_size_pt']), 'attr') ?>"></td>
                        <td><select class="form-select form-select-sm" id="ft_fw_label_gen"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= ($ft['label_footer_generated_font_weight'] ?? $ft['font_weight']) === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="ft_fst_label_gen"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= ($ft['label_footer_generated_font_style'] ?? $ft['font_style']) === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="ft_tt_label_gen"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= ($ft['label_footer_generated_text_transform'] ?? $ft['text_transform']) === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                    </tr>
                    <tr>
                        <td>Fecha y hora generadas</td>
                        <td><input type="number" class="form-control form-control-sm" id="ft_fs_datetime" min="7" max="20" step="0.5" value="<?= esc((string) ($ft['label_footer_datetime_font_size_pt'] ?? $ft['font_size_pt']), 'attr') ?>"></td>
                        <td><select class="form-select form-select-sm" id="ft_fw_datetime"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= ($ft['label_footer_datetime_font_weight'] ?? $ft['font_weight']) === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="ft_fst_datetime"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= ($ft['label_footer_datetime_font_style'] ?? $ft['font_style']) === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="ft_tt_datetime"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= ($ft['label_footer_datetime_text_transform'] ?? $ft['text_transform']) === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                    </tr>
                    <tr>
                        <td>Política / texto legal</td>
                        <td><input type="number" class="form-control form-control-sm" id="ft_fs_policy" min="7" max="20" step="0.5" value="<?= esc((string) ($ft['footer_policy_font_size_pt'] ?? $ft['font_size_pt']), 'attr') ?>"></td>
                        <td><select class="form-select form-select-sm" id="ft_fw_policy"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= ($ft['footer_policy_font_weight'] ?? $ft['font_weight']) === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="ft_fst_policy"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= ($ft['footer_policy_font_style'] ?? $ft['font_style']) === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></td>
                        <td><select class="form-select form-select-sm" id="ft_tt_policy"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= ($ft['footer_policy_text_transform'] ?? $ft['text_transform']) === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6"><label class="form-label small" for="ft_label_footer_generated">Texto antes de la fecha («Resultados generados el»)</label><input type="text" class="form-control form-control-sm" id="ft_label_footer_generated" maxlength="120" value="<?= esc((string) ($ft['label_footer_generated'] ?? ''), 'attr') ?>"></div>
            <div class="col-6 col-md-2 text-center"><div class="form-check d-inline-block"><input class="form-check-input" type="checkbox" id="ft_show_label_footer_generated" value="1"<?= $pdfGridChk($ft, 'show_label_footer_generated') ?>><label class="form-check-label small" for="ft_show_label_footer_generated">Mostrar</label></div></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ft_label_footer_generated_line_mode">Texto vs fecha</label><select class="form-select form-select-sm" id="ft_label_footer_generated_line_mode"><option value="stacked" <?= $pdfGridLineMode($ft, 'label_footer_generated_line_mode') === 'stacked' ? 'selected' : '' ?>>Fecha debajo</option><option value="inline" <?= $pdfGridLineMode($ft, 'label_footer_generated_line_mode') === 'inline' ? 'selected' : '' ?>>Misma línea</option></select></div>
        </div>
        <hr class="my-3">
        <h6 class="text-secondary">Paginación en impresión directa (label / valor)</h6>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="pp_enabled" <?= ! empty($pp['enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="pp_enabled">Mostrar paginación fija</label>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small" for="pp_label_text">Texto de etiqueta</label>
                <input type="text" class="form-control form-control-sm" id="pp_label_text" maxlength="60" value="<?= esc((string) ($pp['label_text'] ?? 'Página'), 'attr') ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small" for="pp_label_position">Posición del label</label>
                <select class="form-select form-select-sm" id="pp_label_position">
                    <?php foreach (['top-left' => 'Arriba izquierda', 'top-center' => 'Arriba centro', 'top-right' => 'Arriba derecha', 'bottom-left' => 'Abajo izquierda', 'bottom-center' => 'Abajo centro', 'bottom-right' => 'Abajo derecha'] as $k => $v): ?>
                    <option value="<?= esc($k, 'attr') ?>" <?= ($pp['label_position'] ?? 'bottom-left') === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small" for="pp_value_position">Posición del valor</label>
                <select class="form-select form-select-sm" id="pp_value_position">
                    <?php foreach (['top-left' => 'Arriba izquierda', 'top-center' => 'Arriba centro', 'top-right' => 'Arriba derecha', 'bottom-left' => 'Abajo izquierda', 'bottom-center' => 'Abajo centro', 'bottom-right' => 'Abajo derecha'] as $k => $v): ?>
                    <option value="<?= esc($k, 'attr') ?>" <?= ($pp['value_position'] ?? 'bottom-right') === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 pdf-config-panel" data-config-panels="results">
    <div class="card-header bg-info-subtle border">
        <h5 class="mb-1">Resultados en el PDF</h5>
        <p class="small text-muted mb-0">Tabla por prueba, cabecera de grupo (área/análisis, tipo de muestra, método), espacio entre áreas/grupos, filas separadoras entre análisis y matriz de referencia. Use las secciones siguientes en orden: colores → tipografía → espacio entre filas y grupos → títulos de sección → matriz poblacional → cabecera de grupo.</p>
    </div>
    <div class="card-body">
        <div class="accordion accordion-flush pdf-results-accordion" id="accordion_pdf_results">
            <div class="accordion-item border rounded mb-2 overflow-hidden">
                <h2 class="accordion-header m-0">
                    <button class="accordion-button py-2" type="button" data-bs-toggle="collapse" data-bs-target="#pdf_rs_panel_colors" aria-expanded="true" aria-controls="pdf_rs_panel_colors">
                        <span class="fw-semibold">1. Colores de la tabla principal</span>
                        <span class="small text-muted ms-2 d-none d-md-inline">Encabezado, cuerpo y bordes</span>
                    </button>
                </h2>
                <div id="pdf_rs_panel_colors" class="accordion-collapse collapse show" data-bs-parent="#accordion_pdf_results">
                    <div class="accordion-body pt-0">
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
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded mb-2 overflow-hidden">
                <h2 class="accordion-header m-0">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#pdf_rs_panel_type" aria-expanded="false" aria-controls="pdf_rs_panel_type">
                        <span class="fw-semibold">2. Tipografía de la tabla principal</span>
                        <span class="small text-muted ms-2 d-none d-md-inline">Fuente, tamaño e interlineado del contenido</span>
                    </button>
                </h2>
                <div id="pdf_rs_panel_type" class="accordion-collapse collapse" data-bs-parent="#accordion_pdf_results">
                    <div class="accordion-body pt-0">
                        <div class="row g-3">
            <div class="col-12 col-md-3"><label class="form-label small" for="rs_font_family">Fuente</label><select class="form-select" id="rs_font_family"><?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?><option value="<?= esc($ff, 'attr') ?>" <?= $rs['font_family'] === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_font_size">Tamaño</label><input type="number" class="form-control" id="rs_font_size" min="7" max="20" step="0.5" value="<?= esc((string) $rs['font_size_pt'], 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_font_weight">Grosor</label><select class="form-select" id="rs_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= $rs['font_weight'] === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_font_style">Estilo</label><select class="form-select" id="rs_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= $rs['font_style'] === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_text_transform">Transformación</label><select class="form-select" id="rs_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $rs['text_transform'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_line_height">Interlineado</label><input type="number" class="form-control" id="rs_line_height" min="1" max="3" step="0.05" value="<?= esc((string) $rs['line_height'], 'attr') ?>"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded mb-2 overflow-hidden">
                <h2 class="accordion-header m-0">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#pdf_rs_panel_density" aria-expanded="false" aria-controls="pdf_rs_panel_density">
                        <span class="fw-semibold">3. Espacio entre filas y grupos en el PDF</span>
                        <span class="small text-muted ms-2 d-none d-md-inline">Altura de fila y separación entre áreas</span>
                    </button>
                </h2>
                <div id="pdf_rs_panel_density" class="accordion-collapse collapse" data-bs-parent="#accordion_pdf_results">
                    <div class="accordion-body pt-0">
                        <p class="small text-muted mb-2">Si las filas se ven muy altas o muy apretadas en el PDF, ajuste el <strong>relleno vertical</strong> (principal) y el interlineado en la sección anterior.</p>
                        <div class="row g-3">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label small" for="rs_cell_padding_v" title="Espacio arriba y abajo en cada celda; controla el alto de la fila">Relleno vertical por fila (px)</label>
                <input type="number" class="form-control" id="rs_cell_padding_v" min="0" max="20" step="1" value="<?= esc((string) (int) ($rs['cell_padding_v_px'] ?? 6), 'attr') ?>">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label small" for="rs_grupo_prueba_gap" title="Separación vertical entre cada área o grupo de pruebas en el PDF (clase report-pdf-grupo-prueba)">Espacio entre grupos de prueba (px)</label>
                <input type="number" class="form-control" id="rs_grupo_prueba_gap" min="0" max="80" step="1" value="<?= esc((string) (int) ($rs['grupo_prueba_gap_px'] ?? 10), 'attr') ?>">
                <div class="form-text">Entre áreas distintas (<code>report-pdf-grupo-prueba</code>).</div>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label small" for="rs_subgrupo_prueba_gap" title="Separación vertical entre pruebas distintas dentro del mismo área (clase report-pdf-subgrupo-prueba)">Espacio entre pruebas del mismo área (px)</label>
                <input type="number" class="form-control" id="rs_subgrupo_prueba_gap" min="0" max="80" step="1" value="<?= esc((string) (int) ($rs['subgrupo_prueba_gap_px'] ?? 18), 'attr') ?>">
                <div class="form-text">Varias pruebas bajo el mismo padre (<code>report-pdf-subgrupo-block</code>).</div>
            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded mb-2 overflow-hidden">
                <h2 class="accordion-header m-0">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#pdf_rs_panel_segment" aria-expanded="false" aria-controls="pdf_rs_panel_segment">
                        <span class="fw-semibold">4. Fila separadora entre pruebas / secciones</span>
                        <span class="small text-muted ms-2 d-none d-md-inline">Título de cada bloque de análisis</span>
                    </button>
                </h2>
                <div id="pdf_rs_panel_segment" class="accordion-collapse collapse" data-bs-parent="#accordion_pdf_results">
                    <div class="accordion-body pt-0">
                        <div class="row g-3">
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
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded mb-2 overflow-hidden">
                <h2 class="accordion-header m-0">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#pdf_rs_panel_matrix" aria-expanded="false" aria-controls="pdf_rs_panel_matrix">
                        <span class="fw-semibold">5. Matriz de valores referenciales</span>
                        <span class="small text-muted ms-2 d-none d-md-inline">Tabla poblacional</span>
                    </button>
                </h2>
                <div id="pdf_rs_panel_matrix" class="accordion-collapse collapse" data-bs-parent="#accordion_pdf_results">
                    <div class="accordion-body pt-0">
                        <div class="small fw-semibold text-secondary mb-2">Texto y encabezados de la matriz</div>
                        <div class="row g-3">
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_matrix_align">Alineación horizontal</label><select class="form-select" id="rs_matrix_align"><?php foreach (['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= ($rs['matrix_text_align'] ?? 'center') === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_matrix_valign">Alineación vertical</label><select class="form-select" id="rs_matrix_valign"><?php foreach (['top' => 'Arriba', 'middle' => 'Centro', 'bottom' => 'Abajo'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= ($rs['matrix_vertical_align'] ?? 'middle') === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_matrix_text_color">Color texto matriz</label><input type="color" class="form-control form-control-color" id="rs_matrix_text_color" value="<?= esc((string) ($rs['matrix_text_color'] ?? '#333333'), 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_matrix_font_size">Tamaño matriz</label><input type="number" class="form-control" id="rs_matrix_font_size" min="7" max="20" step="0.5" value="<?= esc((string) ($rs['matrix_font_size_pt'] ?? 8), 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_matrix_font_weight">Grosor matriz</label><select class="form-select" id="rs_matrix_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= (($rs['matrix_font_weight'] ?? 'normal') === $w) ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_matrix_font_style">Estilo matriz</label><select class="form-select" id="rs_matrix_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= (($rs['matrix_font_style'] ?? 'normal') === $st) ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_matrix_text_transform">Transformación matriz</label><select class="form-select" id="rs_matrix_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= (($rs['matrix_text_transform'] ?? 'none') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><div class="small text-muted fw-semibold mt-1">Encabezados de matriz</div></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_matrix_header_text_color">Color texto header</label><input type="color" class="form-control form-control-color" id="rs_matrix_header_text_color" value="<?= esc((string) ($rs['matrix_header_text_color'] ?? '#1F2937'), 'attr') ?>"></div>
            <div class="col-12 col-md-3"><label class="form-label small" for="rs_matrix_header_font_family">Fuente header</label><select class="form-select" id="rs_matrix_header_font_family"><?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?><option value="<?= esc($ff, 'attr') ?>" <?= (($rs['matrix_header_font_family'] ?? 'DejaVu Sans') === $ff) ? 'selected' : '' ?>><?= esc($ff) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_matrix_header_font_size">Tamaño header</label><input type="number" class="form-control" id="rs_matrix_header_font_size" min="7" max="20" step="0.5" value="<?= esc((string) ($rs['matrix_header_font_size_pt'] ?? 8), 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_matrix_header_font_weight">Grosor header</label><select class="form-select" id="rs_matrix_header_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= (($rs['matrix_header_font_weight'] ?? 'bold') === $w) ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="rs_matrix_header_font_style">Estilo header</label><select class="form-select" id="rs_matrix_header_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= (($rs['matrix_header_font_style'] ?? 'normal') === $st) ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="rs_matrix_header_text_transform">Transformación header</label><select class="form-select" id="rs_matrix_header_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= (($rs['matrix_header_text_transform'] ?? 'uppercase') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><div class="small text-muted fw-semibold mt-1">Alineación por columna (header / contenido)</div></div>
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light"><tr><th>Columna</th><th>Header</th><th>Contenido</th></tr></thead>
                        <tbody class="small">
                            <tr>
                                <td>Grupo poblacional</td>
                                <td><select class="form-select form-select-sm" id="rs_matrix_hdr_population_align"><?php foreach (['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= (($rs['matrix_hdr_population_align'] ?? 'left') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                                <td><select class="form-select form-select-sm" id="rs_matrix_col_population_align"><?php foreach (['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= (($rs['matrix_col_population_align'] ?? 'left') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                            </tr>
                            <tr>
                                <td>Parámetro</td>
                                <td><select class="form-select form-select-sm" id="rs_matrix_hdr_parameter_align"><?php foreach (['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= (($rs['matrix_hdr_parameter_align'] ?? 'left') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                                <td><select class="form-select form-select-sm" id="rs_matrix_col_parameter_align"><?php foreach (['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= (($rs['matrix_col_parameter_align'] ?? 'left') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                            </tr>
                            <tr>
                                <td>Sexo</td>
                                <td><select class="form-select form-select-sm" id="rs_matrix_hdr_sex_align"><?php foreach (['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= (($rs['matrix_hdr_sex_align'] ?? 'center') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                                <td><select class="form-select form-select-sm" id="rs_matrix_col_sex_align"><?php foreach (['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= (($rs['matrix_col_sex_align'] ?? 'center') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                            </tr>
                            <tr>
                                <td>Valor de referencia</td>
                                <td><select class="form-select form-select-sm" id="rs_matrix_hdr_reference_align"><?php foreach (['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= (($rs['matrix_hdr_reference_align'] ?? 'center') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                                <td><select class="form-select form-select-sm" id="rs_matrix_col_reference_align"><?php foreach (['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha', 'justify' => 'Justificado'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= (($rs['matrix_col_reference_align'] ?? 'center') === $k) ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded mb-2 overflow-hidden">
                <h2 class="accordion-header m-0">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#pdf_rs_panel_grupo_cabecera" aria-expanded="false" aria-controls="pdf_rs_panel_grupo_cabecera">
                        <span class="fw-semibold">6. Cabecera de grupo de prueba</span>
                        <span class="small text-muted ms-2 d-none d-md-inline">Título, tipo de muestra y método</span>
                    </button>
                </h2>
                <div id="pdf_rs_panel_grupo_cabecera" class="accordion-collapse collapse" data-bs-parent="#accordion_pdf_results">
                    <div class="accordion-body pt-0">
                        <p class="small text-muted mb-3">Aplica al bloque <code>.report-pdf-grupo-cabecera</code> en el reporte en pantalla, PDF e impresión. Si la opción está activa pero el valor no está configurado en el análisis clínico, no se muestra (comportamiento actual).</p>
                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <label class="form-label small" for="rs_grupo_cabecera_title_mode">Formato del título</label>
                                <select class="form-select" id="rs_grupo_cabecera_title_mode">
                                    <option value="grupo_analisis" <?= ($rs['grupo_cabecera_title_mode'] ?? 'grupo_analisis') === 'grupo_analisis' ? 'selected' : '' ?>>Área (grupo) — Análisis clínico</option>
                                    <option value="solo_analisis" <?= ($rs['grupo_cabecera_title_mode'] ?? '') === 'solo_analisis' ? 'selected' : '' ?>>Solo análisis clínico</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="rs_grupo_cabecera_show_tipo_muestra" <?= ! empty($rs['grupo_cabecera_show_tipo_muestra']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="rs_grupo_cabecera_show_tipo_muestra">Mostrar tipo de muestra</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="rs_grupo_cabecera_show_metodo" <?= ! empty($rs['grupo_cabecera_show_metodo']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="rs_grupo_cabecera_show_metodo">Mostrar método</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 pdf-config-panel" data-config-panels="general">
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

<div class="card shadow-sm mb-4 pdf-config-panel" data-config-panels="notes">
    <div class="card-header bg-warning-subtle border">
        <h5 class="mb-0">Bloque «Notas del resultado» — estilo en PDF / impresión</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_title_bg">Fondo título</label><input type="color" class="form-control form-control-color" id="ns_title_bg" value="<?= esc($ns['title_bg_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_title_text">Texto título</label><input type="color" class="form-control form-control-color" id="ns_title_text" value="<?= esc($ns['title_text_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_body_bg">Fondo contenido</label><input type="color" class="form-control form-control-color" id="ns_body_bg" value="<?= esc($ns['body_bg_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_body_text">Texto contenido</label><input type="color" class="form-control form-control-color" id="ns_body_text" value="<?= esc($ns['body_text_color'], 'attr') ?>"></div>
            <div class="col-12 col-md-3 d-flex flex-wrap align-items-end gap-3">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="ns_title_transparent" <?= ! empty($ns['title_transparent']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="ns_title_transparent">Título sin fondo</label>
                </div>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="ns_body_transparent" <?= ! empty($ns['body_transparent']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="ns_body_transparent">Contenido sin fondo</label>
                </div>
            </div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ns_column_border_width">Borde título/caja (px)</label><input type="number" class="form-control" id="ns_column_border_width" min="0" max="4" step="1" value="<?= esc((string) (int) ($ns['column_border_width_px'] ?? 1), 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_column_border_color">Color del borde</label><input type="color" class="form-control form-control-color" id="ns_column_border_color" value="<?= esc((string) ($ns['column_border_color'] ?? '#DDDDDD'), 'attr') ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label small" for="ns_section_title">Título del bloque (ej. NOTAS)</label><input type="text" class="form-control" id="ns_section_title" maxlength="120" value="<?= esc((string) ($ns['section_title'] ?? ''), 'attr') ?>"></div>
            <div class="col-12 col-md-3 d-flex align-items-end">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="ns_show_section_title" value="1" <?= ! empty($ns['show_section_title']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="ns_show_section_title">Mostrar título</label>
                </div>
            </div>
            <div class="col-12 col-md-3"><label class="form-label small" for="ns_font_family">Fuente</label><select class="form-select" id="ns_font_family"><?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?><option value="<?= esc($ff, 'attr') ?>" <?= $ns['font_family'] === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ns_font_size">Tamaño</label><input type="number" class="form-control" id="ns_font_size" min="7" max="20" step="0.5" value="<?= esc((string) $ns['font_size_pt'], 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ns_font_weight">Grosor</label><select class="form-select" id="ns_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= $ns['font_weight'] === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ns_font_style">Estilo</label><select class="form-select" id="ns_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= $ns['font_style'] === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="ns_text_transform">Transformación</label><select class="form-select" id="ns_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $ns['text_transform'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="ns_line_height">Interlineado</label><input type="number" class="form-control" id="ns_line_height" min="1" max="3" step="0.05" value="<?= esc((string) $ns['line_height'], 'attr') ?>"></div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 border border-warning border-opacity-50 pdf-config-panel" data-config-panels="lab_firmas">
    <div class="card-header bg-warning-subtle border-bottom">
        <h5 class="mb-1">Bloque «Validación y aprobación (firmas)» — estilo y textos en PDF / impresión</h5>
        <p class="small text-muted mb-0">Configure la validación <strong>por área</strong> (debajo de cada grupo de pruebas, como en el registro) o al final del documento. La cuadrícula y columnas de abajo aplican a cada bloque de firmas. Active el bloque en la lista superior.</p>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-2">
            <div class="col-12"><span class="small fw-semibold text-secondary">Ubicación en el reporte</span></div>
            <div class="col-12 col-md-6">
                <label class="form-label small" for="lf_placement">¿Dónde mostrar las firmas?</label>
                <?php $lfPlacement = (string) ($lf['placement'] ?? 'per_group'); ?>
                <select class="form-select" id="lf_placement">
                    <option value="per_group" <?= $lfPlacement === 'per_group' ? 'selected' : '' ?>>Debajo de cada área (grupo de pruebas)</option>
                    <option value="block_end" <?= $lfPlacement === 'block_end' ? 'selected' : '' ?>>Solo al final del documento</option>
                    <option value="both" <?= $lfPlacement === 'both' ? 'selected' : '' ?>>En cada área y también al final</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="lf_inline_margin_top">Margen superior (pt)</label>
                <input type="number" class="form-control" id="lf_inline_margin_top" min="0" max="24" step="0.5" value="<?= esc((string) ($lf['inline_margin_top_pt'] ?? 8), 'attr') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="lf_inline_margin_bottom">Margen inferior (pt)</label>
                <input type="number" class="form-control" id="lf_inline_margin_bottom" min="0" max="24" step="0.5" value="<?= esc((string) ($lf['inline_margin_bottom_pt'] ?? 6), 'attr') ?>">
            </div>
            <div class="col-12"><span class="small fw-semibold text-secondary">Título del área (opcional, solo si va por partes)</span></div>
            <div class="col-12 col-md-4 d-flex align-items-end">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="lf_show_area_heading" value="1" <?= ! empty($lf['show_area_heading']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="lf_show_area_heading">Mostrar nombre del área encima de cada firma</label>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small" for="lf_area_heading_color">Color título área</label>
                <input type="color" class="form-control form-control-color" id="lf_area_heading_color" value="<?= esc((string) ($lf['area_heading_color'] ?? '#664D03'), 'attr') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small" for="lf_area_heading_font_size">Tamaño (pt)</label>
                <input type="number" class="form-control" id="lf_area_heading_font_size" min="7" max="16" step="0.5" value="<?= esc((string) ($lf['area_heading_font_size_pt'] ?? 9), 'attr') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small" for="lf_area_heading_font_weight">Grosor</label>
                <select class="form-select" id="lf_area_heading_font_weight"><?php foreach (['normal', 'bold', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= ($lf['area_heading_font_weight'] ?? '600') === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small" for="lf_area_heading_text_transform">Transformación</label>
                <select class="form-select" id="lf_area_heading_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= ($lf['area_heading_text_transform'] ?? 'uppercase') === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select>
            </div>
            <div class="col-12"><span class="small fw-semibold text-secondary">Tamaño de imágenes (sello y firma)</span></div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="lf_seal_max_height">Alto máx. sello (px)</label>
                <input type="number" class="form-control" id="lf_seal_max_height" min="40" max="200" step="1" value="<?= esc((string) (int) ($lf['seal_max_height_px'] ?? 110), 'attr') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="lf_signature_max_height">Alto máx. firma (px)</label>
                <input type="number" class="form-control" id="lf_signature_max_height" min="30" max="160" step="1" value="<?= esc((string) (int) ($lf['signature_max_height_px'] ?? 72), 'attr') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="lf_signature_max_width">Ancho máx. firma (px)</label>
                <input type="number" class="form-control" id="lf_signature_max_width" min="80" max="400" step="1" value="<?= esc((string) (int) ($lf['signature_max_width_px'] ?? 220), 'attr') ?>">
            </div>
            <div class="col-12"><hr class="my-1"></div>
            <div class="col-12"><span class="small fw-semibold text-secondary">Apariencia del cuadro de firmas</span></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_title_bg">Fondo título</label><input type="color" class="form-control form-control-color" id="lf_title_bg" value="<?= esc($lf['title_bg_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_title_text">Texto título</label><input type="color" class="form-control form-control-color" id="lf_title_text" value="<?= esc($lf['title_text_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_body_bg">Fondo contenido</label><input type="color" class="form-control form-control-color" id="lf_body_bg" value="<?= esc($lf['body_bg_color'], 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_body_text">Texto contenido</label><input type="color" class="form-control form-control-color" id="lf_body_text" value="<?= esc($lf['body_text_color'], 'attr') ?>"></div>
            <div class="col-12 col-md-6 d-flex align-items-end">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="lf_body_transparent" value="1" <?= ! empty($lf['body_transparent']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="lf_body_transparent">Fondo del contenido transparente (sin color en las celdas del cuadro)</label>
                </div>
            </div>
            <div class="col-6 col-md-2"><label class="form-label small" for="lf_column_border_width">Borde entre columnas (px)</label><input type="number" class="form-control" id="lf_column_border_width" min="0" max="4" step="1" value="<?= esc((string) (int) ($lf['column_border_width_px'] ?? 1), 'attr') ?>"></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_column_border_color">Color del borde</label><input type="color" class="form-control form-control-color" id="lf_column_border_color" value="<?= esc((string) ($lf['column_border_color'] ?? '#DDDDDD'), 'attr') ?>"></div>
            <div class="col-12 col-md-3"><label class="form-label small" for="lf_font_family">Fuente</label><select class="form-select" id="lf_font_family"><?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $ff): ?><option value="<?= esc($ff, 'attr') ?>" <?= $lf['font_family'] === $ff ? 'selected' : '' ?>><?= esc($ff) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="lf_font_size">Tamaño</label><input type="number" class="form-control" id="lf_font_size" min="7" max="20" step="0.5" value="<?= esc((string) $lf['font_size_pt'], 'attr') ?>"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="lf_font_weight">Grosor</label><select class="form-select" id="lf_font_weight"><?php foreach (['normal', 'bold', '400', '500', '600', '700', '800'] as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= $lf['font_weight'] === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="lf_font_style">Estilo</label><select class="form-select" id="lf_font_style"><?php foreach (['normal', 'italic', 'oblique'] as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= $lf['font_style'] === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-3"><label class="form-label small" for="lf_text_transform">Transformación</label><select class="form-select" id="lf_text_transform"><?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $lf['text_transform'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="lf_line_height">Interlineado</label><input type="number" class="form-control" id="lf_line_height" min="1" max="3" step="0.05" value="<?= esc((string) $lf['line_height'], 'attr') ?>"></div>
            <div class="col-12"><hr class="my-2"></div>
            <div class="col-12"><span class="small fw-semibold text-secondary">Textos en el PDF</span> <span class="small text-muted">(máx. 120 caracteres c/u; puede ocultar cada etiqueta o ponerla en la misma línea que el valor)</span></div>
            <?php
            $lfChk = static function (array $a, string $k): string {
                $v = $a[$k] ?? true;

                return ($v === false || $v === 0 || $v === '0' || $v === 'false') ? '' : ' checked';
            };
            $lfSelMode = static function (array $a, string $k): string {
                return (isset($a[$k]) && trim((string) $a[$k]) === 'inline') ? 'inline' : 'stacked';
            };
            ?>
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small">
                                <th style="min-width:9rem;">Bloque</th>
                                <th>Texto</th>
                                <th style="width:6rem;" class="text-center">Mostrar</th>
                                <th style="min-width:9rem;">Etiqueta vs valor</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td>Título del bloque</td>
                                <td><input type="text" class="form-control form-control-sm" id="lf_section_title" maxlength="120" value="<?= esc((string) ($lf['section_title'] ?? ''), 'attr') ?>"></td>
                                <td class="text-center"><input type="checkbox" class="form-check-input" id="lf_show_section_title" value="1"<?= $lfChk($lf, 'show_section_title') ?>></td>
                                <td class="text-muted">—</td>
                            </tr>
                            <tr>
                                <td>Validador</td>
                                <td><input type="text" class="form-control form-control-sm" id="lf_label_validator" maxlength="120" value="<?= esc((string) ($lf['label_validator'] ?? ''), 'attr') ?>"></td>
                                <td class="text-center"><input type="checkbox" class="form-check-input" id="lf_show_label_validator" value="1"<?= $lfChk($lf, 'show_label_validator') ?>></td>
                                <td>
                                    <?php $vMode = $lfSelMode($lf, 'validator_line_mode'); ?>
                                    <select class="form-select form-select-sm" id="lf_validator_line_mode">
                                        <option value="stacked" <?= $vMode === 'stacked' ? 'selected' : '' ?>>Debajo</option>
                                        <option value="inline" <?= $vMode === 'inline' ? 'selected' : '' ?>>Misma línea</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Sello (imagen)</td>
                                <td><input type="text" class="form-control form-control-sm" id="lf_label_seal" maxlength="120" value="<?= esc((string) ($lf['label_seal'] ?? ''), 'attr') ?>"></td>
                                <td class="text-center"><input type="checkbox" class="form-check-input" id="lf_show_label_seal" value="1"<?= $lfChk($lf, 'show_label_seal') ?>></td>
                                <td>
                                    <?php $m = $lfSelMode($lf, 'label_seal_line_mode'); ?>
                                    <select class="form-select form-select-sm" id="lf_label_seal_line_mode">
                                        <option value="stacked" <?= $m === 'stacked' ? 'selected' : '' ?>>Debajo</option>
                                        <option value="inline" <?= $m === 'inline' ? 'selected' : '' ?>>Misma línea</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Firma (imagen)</td>
                                <td><input type="text" class="form-control form-control-sm" id="lf_label_firma" maxlength="120" value="<?= esc((string) ($lf['label_firma'] ?? ''), 'attr') ?>"></td>
                                <td class="text-center"><input type="checkbox" class="form-check-input" id="lf_show_label_firma" value="1"<?= $lfChk($lf, 'show_label_firma') ?>></td>
                                <td>
                                    <?php $m = $lfSelMode($lf, 'label_firma_line_mode'); ?>
                                    <select class="form-select form-select-sm" id="lf_label_firma_line_mode">
                                        <option value="stacked" <?= $m === 'stacked' ? 'selected' : '' ?>>Debajo</option>
                                        <option value="inline" <?= $m === 'inline' ? 'selected' : '' ?>>Misma línea</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Nombre aprobador</td>
                                <td><input type="text" class="form-control form-control-sm" id="lf_label_approver" maxlength="120" value="<?= esc((string) ($lf['label_approver'] ?? ''), 'attr') ?>"></td>
                                <td class="text-center"><input type="checkbox" class="form-check-input" id="lf_show_label_approver" value="1"<?= $lfChk($lf, 'show_label_approver') ?>></td>
                                <td>
                                    <?php $m = $lfSelMode($lf, 'label_approver_line_mode'); ?>
                                    <select class="form-select form-select-sm" id="lf_label_approver_line_mode">
                                        <option value="stacked" <?= $m === 'stacked' ? 'selected' : '' ?>>Debajo</option>
                                        <option value="inline" <?= $m === 'inline' ? 'selected' : '' ?>>Misma línea</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Cargo</td>
                                <td><input type="text" class="form-control form-control-sm" id="lf_label_cargo" maxlength="120" value="<?= esc((string) ($lf['label_cargo'] ?? ''), 'attr') ?>"></td>
                                <td class="text-center"><input type="checkbox" class="form-check-input" id="lf_show_label_cargo" value="1"<?= $lfChk($lf, 'show_label_cargo') ?>></td>
                                <td>
                                    <?php $m = $lfSelMode($lf, 'label_cargo_line_mode'); ?>
                                    <select class="form-select form-select-sm" id="lf_label_cargo_line_mode">
                                        <option value="stacked" <?= $m === 'stacked' ? 'selected' : '' ?>>Debajo</option>
                                        <option value="inline" <?= $m === 'inline' ? 'selected' : '' ?>>Misma línea</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Matrícula</td>
                                <td><input type="text" class="form-control form-control-sm" id="lf_label_matricula" maxlength="120" value="<?= esc((string) ($lf['label_matricula'] ?? ''), 'attr') ?>"></td>
                                <td class="text-center"><input type="checkbox" class="form-check-input" id="lf_show_label_matricula" value="1"<?= $lfChk($lf, 'show_label_matricula') ?>></td>
                                <td>
                                    <?php $m = $lfSelMode($lf, 'label_matricula_line_mode'); ?>
                                    <select class="form-select form-select-sm" id="lf_label_matricula_line_mode">
                                        <option value="stacked" <?= $m === 'stacked' ? 'selected' : '' ?>>Debajo</option>
                                        <option value="inline" <?= $m === 'inline' ? 'selected' : '' ?>>Misma línea</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 pdf-config-panel" data-config-panels="general">
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

<div class="card shadow-sm mb-4 pdf-config-panel" data-config-panels="general">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Saltos de página en grupos de prueba</h5>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">Controla los saltos de página al generar PDF o imprimir. Puede aplicarse al área completa (<code>.report-pdf-grupo-prueba</code>) o a cada segmento de resultados (<code>.report-segment-table-wrap</code>).</p>
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <label class="form-label small" for="gpb_mode">Comportamiento de salto de página</label>
                <select class="form-select" id="gpb_mode">
                    <option value="flow" <?= ($gpb['mode'] ?? 'flow') === 'flow' ? 'selected' : '' ?>>Flujo libre — sin reglas extra; el contenido puede partirse en cualquier punto</option>
                    <option value="keep_segment" <?= ($gpb['mode'] ?? '') === 'keep_segment' ? 'selected' : '' ?>>Flujo por segmentos — cada segmento de tabla va entero a la página siguiente si no cabe</option>
                    <option value="keep_together_if_fits" <?= ($gpb['mode'] ?? '') === 'keep_together_if_fits' ? 'selected' : '' ?>>Grupo íntegro solo si cabe — mantiene el área junta solo si entra en el espacio restante; si no, rellena la hoja actual</option>
                    <option value="keep_together" <?= ($gpb['mode'] ?? '') === 'keep_together' ? 'selected' : '' ?>>Grupo íntegro — todo el área se mueve junta a la página siguiente</option>
                    <option value="keep_together_compact" <?= ($gpb['mode'] ?? '') === 'keep_together_compact' ? 'selected' : '' ?>>Grupo íntegro con compactación — reduce fuentes/espaciado antes de mover el área completa</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="gpb_min_remaining_mm" title="Solo aplica a «Grupo íntegro» y «Grupo íntegro con compactación». Si el espacio libre en la hoja es menor que este valor (mm), no se mueve todo el bloque: se rellena la hoja actual por segmentos.">Umbral espacio restante (mm)</label>
                <input type="number" class="form-control" id="gpb_min_remaining_mm" min="0" max="120" step="1" value="<?= esc((string) (float) ($gpb['min_remaining_mm_to_force_break'] ?? 0), 'attr') ?>">
                <span class="form-text small text-muted">0 = mover siempre el bloque entero; 30–50 = rellenar hoja si queda poco espacio</span>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="gpb_compact_min_scale" title="Porcentaje mínimo al compactar el grupo para que quepa en el espacio restante">Escala mínima compactación (%)</label>
                <input type="number" class="form-control" id="gpb_compact_min_scale" min="75" max="100" step="1" value="<?= esc((string) (int) ($gpb['compact_min_scale_percent'] ?? 85), 'attr') ?>">
                <span class="form-text small text-muted">75–100 % (modo compactación)</span>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="gpb_compact_cell_padding" title="Relleno vertical de filas al compactar. 0 = automático según escala">Relleno filas al compactar (px)</label>
                <input type="number" class="form-control" id="gpb_compact_cell_padding" min="0" max="20" step="1" value="<?= esc((string) (int) ($gpb['compact_cell_padding_px'] ?? 0), 'attr') ?>">
                <span class="form-text small text-muted">0 = auto; 1–20 = fijo al compactar</span>
            </div>
            <div class="col-6 col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="gpb_compact_aggressive" <?= ! empty($gpb['compact_aggressive']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="gpb_compact_aggressive">Compactación agresiva (menos interlineado y márgenes)</label>
                </div>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="gpb_repeat_header" <?= ! empty($gpb['repeat_header_on_split']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="gpb_repeat_header">Repetir encabezado del examen al continuar en otra página</label>
                </div>
            </div>
        </div>
        <ul class="small text-muted mb-0 mt-2 ps-3">
            <li><strong>Grupo íntegro solo si cabe:</strong> ideal para hemogramas; si el área no cabe tras la cabecera, empieza en la hoja 1 y continúa en la 2 (por segmentos), sin dejar la primera hoja vacía.</li>
            <li><strong>Grupo íntegro:</strong> mueve todo el área a la siguiente hoja si no cabe entera. Deje el <strong>umbral en 0 mm</strong> para este comportamiento puro.</li>
            <li><strong>Umbral &gt; 0 mm:</strong> solo en «Grupo íntegro» / «con compactación»; si queda poco espacio libre, rellena la hoja actual por segmentos en lugar de mover el bloque.</li>
            <li><strong>Con compactación:</strong> reduce fuentes, relleno e interlineado (escala, relleno fijo y/o compactación agresiva) antes de mover el bloque.</li>
            <li><strong>Flujo por segmentos:</strong> cada <code>report-segment-table-wrap</code> no se parte; si no cabe, va entero a la página siguiente.</li>
        </ul>
    </div>
</div>

<div class="card shadow-sm mb-4 pdf-config-panel" data-config-panels="general">
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

<div class="card shadow-sm mb-4 pdf-config-panel" data-config-panels="general,header,patient_doctor,lab_firmas,footer" id="pdf-editor-instances">
    <div class="card-header bg-light border">
        <h5 class="mb-0">Elementos del PDF</h5>
    </div>
    <div class="card-body">
        <template id="tpl_pdf_custom_text_editor">
            <?= view('config/partials/pdf_instance_custom_text_editor', [
                'rowUid' => '__PDF_UID__',
                'ct'     => \App\Services\ReportPdfLayoutService::normalizeCustomTextPayload([
                    'label'       => 'Etiqueta:',
                    'value'       => 'Valor de ejemplo',
                    'show_label'  => true,
                    'line_mode'   => 'stacked',
                    'label_style' => \App\Services\ReportPdfLayoutService::DEFAULT_TEXT_STYLE,
                    'value_style' => \App\Services\ReportPdfLayoutService::DEFAULT_TEXT_STYLE,
                ]),
            ]) ?>
        </template>
        <div class="row g-3 mb-4 pdf-general-only-controls">
            <div class="col-12 col-lg-6">
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
            <div class="col-12 col-lg-6">
                <label class="form-label small mb-1" for="palette_span_select">Campos arrastrables (drag & drop)</label>
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                    <select class="form-select form-select-sm" id="palette_span_select" style="max-width: 12rem;">
                        <option value="1">Ancho nuevo: 1 col.</option>
                        <option value="2">Ancho nuevo: 2 cols.</option>
                        <option value="3">Ancho nuevo: 3 cols.</option>
                        <option value="4">Ancho nuevo: 4 cols.</option>
                        <option value="5">Ancho nuevo: 5 cols.</option>
                        <option value="6">Ancho nuevo: 6 cols.</option>
                    </select>
                    <div class="form-check ms-2">
                        <input class="form-check-input" type="checkbox" id="toggle_instance_lists" checked>
                        <label class="form-check-label small" for="toggle_instance_lists">Mostrar lista avanzada</label>
                    </div>
                    <span class="small text-muted">Arrastrá estos campos a cualquier matriz. Si soltás sobre un item, hace replace y conserva el ancho del destino.</span>
                </div>
                <div id="pdf-field-palette" class="pdf-field-palette"></div>
            </div>
            <div class="col-12 border-top pt-3 mt-1">
                <label class="form-label small mb-1" for="pdf_grid_editor_row_gap">Separación entre filas en la cuadrícula (solo vista del editor, no el PDF)</label>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <input type="range" class="form-range m-0" id="pdf_grid_editor_row_gap" min="2" max="18" step="1" value="6" style="max-width: 22rem;">
                    <span class="small text-muted" id="pdf_grid_editor_row_gap_hint">0,60 rem</span>
                </div>
                <p class="small text-muted mb-0 mt-1">Si cada fila del diseño se ve muy alta, baje este valor. La pestaña <strong>Resultados</strong> tiene el control equivalente para el PDF (<em>Relleno vertical por fila</em>).</p>
            </div>
        </div>

        <div class="card border-info mb-4 pdf-section-editor" data-config-section="header">
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
                <div class="pdf-grid-editor-wrap mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-uppercase text-muted small mb-0">Matriz editable</h6>
                        <span class="small text-muted">Drop en la celda: agrega al final del stack. Drop sobre un item: replace.</span>
                    </div>
                    <div id="grid-editor-header" class="pdf-grid-editor" data-section="header"></div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6 pdf-instance-list-col">
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

        <div class="card border-primary mb-4 pdf-section-editor" data-config-section="patient_doctor">
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
                <div class="pdf-grid-editor-wrap mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-uppercase text-muted small mb-0">Matriz editable</h6>
                        <span class="small text-muted">Los espacios, tipografías y alineaciones se conservan por instancia.</span>
                    </div>
                    <div id="grid-editor-patient" class="pdf-grid-editor" data-section="patient_doctor"></div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6 pdf-instance-list-col">
                        <ul id="instance-list-patient" class="list-group pdf-instance-sortable" data-section="patient_doctor">
                            <?php foreach ($instPatient as $inst): ?>
                                <?= view('config/partials/pdf_instance_row', ['inst' => $inst, 'col_count' => $pCols, 'elLabels' => $elLabels, 'pd_grid' => $pd]) ?>
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

        <div class="card border-warning mb-4 pdf-section-editor" data-config-section="lab_firmas">
            <div class="card-header bg-warning text-dark d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="fw-semibold">Validación y aprobación (firmas)</span>
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 small text-dark" for="sec_cols_lab_firmas">Columnas</label>
                    <input type="number" class="form-control form-control-sm" id="sec_cols_lab_firmas" min="1" max="6" value="<?= (int) $lCols ?>" style="width: 4.5rem;">
                </div>
            </div>
            <div class="card-body">
                <p class="small text-muted">Con ubicación <strong>por área</strong>, este diseño se repite debajo de cada grupo (Química, Hematología, etc.) según lo registrado. Ordene validador, sello, firma, nombre, cargo y matrícula en columnas. Si elige «al final», esta cuadrícula solo aparece al cierre del PDF.</p>
                <?= view('config/partials/pdf_section_style_controls', [
                    'section_key' => 'lab_firmas',
                    'col_count'   => $lCols,
                    'sec_layout'  => $secLayouts['lab_firmas'] ?? [],
                ]) ?>
                <div class="pdf-grid-editor-wrap mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-uppercase text-muted small mb-0">Matriz editable</h6>
                        <span class="small text-muted">Podés apilar varios elementos dentro del mismo espacio.</span>
                    </div>
                    <div id="grid-editor-lab-firmas" class="pdf-grid-editor" data-section="lab_firmas"></div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6 pdf-instance-list-col">
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

        <div class="card border-secondary mb-0 pdf-section-editor" data-config-section="footer" style="border-color: #6f42c1 !important;">
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
                <div class="pdf-grid-editor-wrap mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-uppercase text-muted small mb-0">Matriz editable</h6>
                        <span class="small text-muted">El ancho de destino se mantiene en los replace.</span>
                    </div>
                    <div id="grid-editor-footer" class="pdf-grid-editor" data-section="footer"></div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6 pdf-instance-list-col">
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
.pdf-config-panel-hidden { display: none !important; }
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
.pdf-field-palette {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.pdf-palette-item {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.65rem;
    border: 1px solid #c7d3df;
    border-radius: 999px;
    background: #fff;
    cursor: grab;
    font-size: 0.8rem;
    user-select: none;
}
.pdf-palette-item:active,
.pdf-grid-chip:active {
    cursor: grabbing;
}
.pdf-grid-editor-wrap {
    border-top: 1px dashed #d8dee5;
    padding-top: 1rem;
    overflow-x: auto;
}
.pdf-grid-editor {
    display: flex;
    flex-direction: column;
    gap: var(--pdf-grid-editor-gap, 0.45rem);
}
.pdf-grid-row-editor {
    display: grid;
    gap: var(--pdf-grid-row-inner-gap, 0.45rem);
    width: max-content;
    min-width: 100%;
}
.pdf-grid-cell-editor {
    min-width: 170px;
    min-height: var(--pdf-grid-cell-min-height, 72px);
    border: 1px dashed #b9c4d0;
    border-radius: 10px;
    background: #fbfcfe;
    padding: 0.45rem;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
.pdf-grid-cell-editor.is-drop-target,
.pdf-grid-chip.is-drop-target {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15);
    background: #eef5ff !important;
}
.pdf-grid-cell-stack {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    min-height: 100%;
}
.pdf-grid-cell-empty {
    color: #6c757d;
    font-size: 0.72rem;
    text-align: center;
    padding-top: 1.1rem;
}
.pdf-grid-cell-add {
    margin-top: 0.35rem;
    text-align: center;
}
.pdf-grid-cell-add .btn {
    --bs-btn-padding-y: 0.1rem;
    --bs-btn-padding-x: 0.45rem;
    --bs-btn-font-size: 0.68rem;
    line-height: 1.2;
    white-space: nowrap;
    min-width: 1.9rem;
}
.pdf-grid-chip {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid #d0d7de;
    border-radius: 8px;
    background: #fff;
    padding: 0.45rem 0.55rem;
    cursor: grab;
}
.pdf-grid-chip-main {
    min-width: 0;
}
.pdf-grid-chip-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    flex-wrap: nowrap;
    flex: 0 0 auto;
}
.pdf-grid-chip-actions .btn {
    --bs-btn-padding-y: 0.1rem;
    --bs-btn-padding-x: 0.35rem;
    --bs-btn-font-size: 0.66rem;
    line-height: 1.2;
    min-width: 1.7rem;
    white-space: nowrap;
}
.pdf-grid-chip-title {
    font-size: 0.78rem;
    font-weight: 600;
    color: #1f2d3d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pdf-grid-chip-meta {
    font-size: 0.68rem;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pdf-grid-row-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.2rem;
}
.pdf-results-accordion .accordion-button {
    font-size: 0.95rem;
}
.pdf-results-accordion .accordion-button:not(.collapsed) {
    box-shadow: none;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function csvHasToken(csv, token) {
        if (!csv) return false;
        return String(csv).split(',').map(function(s) { return s.trim(); }).indexOf(token) >= 0;
    }
    function applyConfigTab(tabKey) {
        document.querySelectorAll('#pdf_config_tabs .nav-link[data-config-tab]').forEach(function(btn) {
            btn.classList.toggle('active', String(btn.getAttribute('data-config-tab') || '') === tabKey);
        });
        document.querySelectorAll('.pdf-config-panel[data-config-panels]').forEach(function(panel) {
            var groups = String(panel.getAttribute('data-config-panels') || '');
            panel.classList.toggle('pdf-config-panel-hidden', !csvHasToken(groups, tabKey));
        });
        document.querySelectorAll('.pdf-subpanel[data-config-subpanel]').forEach(function(panel) {
            panel.classList.toggle('pdf-config-panel-hidden', String(panel.getAttribute('data-config-subpanel') || '') !== tabKey);
        });
        document.querySelectorAll('.pdf-subpanel-divider[data-config-subpanel]').forEach(function(hr) {
            hr.classList.toggle('pdf-config-panel-hidden', String(hr.getAttribute('data-config-subpanel') || '') !== tabKey);
        });
        document.querySelectorAll('.pdf-section-editor[data-config-section]').forEach(function(panel) {
            panel.classList.toggle('pdf-config-panel-hidden', String(panel.getAttribute('data-config-section') || '') !== tabKey);
        });
        document.querySelectorAll('.pdf-general-only-controls').forEach(function(panel) {
            panel.classList.toggle('pdf-config-panel-hidden', tabKey !== 'general');
        });
    }
    document.querySelectorAll('#pdf_config_tabs .nav-link[data-config-tab]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var key = String(btn.getAttribute('data-config-tab') || 'general');
            applyConfigTab(key);
        });
    });
    applyConfigTab('general');

    (function pdfGridEditorGapPrefs() {
        var rangeEl = document.getElementById('pdf_grid_editor_row_gap');
        var hintEl = document.getElementById('pdf_grid_editor_row_gap_hint');
        if (!rangeEl) return;
        function applyGap(val) {
            var v = Math.max(2, Math.min(18, parseInt(val, 10) || 6));
            var rem = (v / 10).toFixed(2).replace('.', ',');
            var inner = (Math.max(2, v - 1) / 10).toFixed(2).replace('.', ',');
            document.documentElement.style.setProperty('--pdf-grid-editor-gap', (v / 10) + 'rem');
            document.documentElement.style.setProperty('--pdf-grid-row-inner-gap', (Math.max(2, v - 1) / 10) + 'rem');
            document.documentElement.style.setProperty('--pdf-grid-cell-min-height', (56 + Math.round(v * 2.2)) + 'px');
            if (hintEl) {
                hintEl.textContent = rem + ' rem entre filas · ' + inner + ' rem entre columnas';
            }
            try {
                localStorage.setItem('pdfTplGridEditorRowGap', String(v));
            } catch (e) { /* ignore */ }
        }
        try {
            var saved = localStorage.getItem('pdfTplGridEditorRowGap');
            if (saved !== null && saved !== '') {
                rangeEl.value = String(Math.max(2, Math.min(18, parseInt(saved, 10) || 6)));
            }
        } catch (e) { /* ignore */ }
        applyGap(rangeEl.value);
        rangeEl.addEventListener('input', function() {
            applyGap(rangeEl.value);
        });
    })();

    var blockList = document.getElementById('pdf-block-list');
    var headerList = document.getElementById('instance-list-header');
    var patientList = document.getElementById('instance-list-patient');
    var footerList = document.getElementById('instance-list-footer');
    var labFirmasList = document.getElementById('instance-list-lab-firmas');
    var previewPatient = document.getElementById('pdf-preview-patient-block');
    var previewHeader = document.getElementById('pdf-preview-header-block');
    var previewFooter = document.getElementById('pdf-preview-footer-block');
    var previewLabFirmas = document.getElementById('pdf-preview-lab-firmas-block');
    var gridEditorHeader = document.getElementById('grid-editor-header');
    var gridEditorPatient = document.getElementById('grid-editor-patient');
    var gridEditorFooter = document.getElementById('grid-editor-footer');
    var gridEditorLabFirmas = document.getElementById('grid-editor-lab-firmas');
    var fieldPalette = document.getElementById('pdf-field-palette');
    var paletteSpanSelect = document.getElementById('palette_span_select');
    var toggleInstanceLists = document.getElementById('toggle_instance_lists');
    var activeDragPayload = null;
    var secColsH = document.getElementById('sec_cols_header');
    var secColsP = document.getElementById('sec_cols_patient');
    var secColsF = document.getElementById('sec_cols_footer');
    var secColsL = document.getElementById('sec_cols_lab_firmas');
    var secRowsH = document.getElementById('sec_rows_header');
    var secRowsP = document.getElementById('sec_rows_patient_doctor');
    var secRowsF = document.getElementById('sec_rows_footer');
    var secRowsL = document.getElementById('sec_rows_lab_firmas');
    var LAB_ELEMENT_TYPES = ['lab_firmas_title', 'lab_firmas_validator', 'lab_firmas_seal', 'lab_firmas_approver_signature', 'lab_firmas_approver_name', 'lab_firmas_approver_cargo', 'lab_firmas_matricula'];

    window._elementLabels = <?= json_encode($elLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window._elementSamples = <?= json_encode($elSamples, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window._labelsShort = <?= json_encode($labelsShort, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window._headerLabelFieldIds = <?= json_encode(array_keys(\App\Services\ReportPdfLayoutService::HEADER_GRID_LABEL_DEFAULTS), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window._headerLabelDefaults = <?= json_encode(\App\Services\ReportPdfLayoutService::HEADER_GRID_LABEL_DEFAULTS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
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

    function clampRows(v) {
        var n = parseInt(v, 10);
        if (isNaN(n)) return 3;
        return Math.max(1, Math.min(50, n));
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

    function rowsForSectionKey(sectionKey) {
        if (sectionKey === 'header') return clampRows(secRowsH ? secRowsH.value : '3');
        if (sectionKey === 'patient_doctor') return clampRows(secRowsP ? secRowsP.value : '4');
        if (sectionKey === 'lab_firmas') return clampRows(secRowsL ? secRowsL.value : '3');
        return clampRows(secRowsF ? secRowsF.value : '2');
    }

    function gridRootForSection(sectionKey) {
        if (sectionKey === 'header') return gridEditorHeader;
        if (sectionKey === 'patient_doctor') return gridEditorPatient;
        if (sectionKey === 'lab_firmas') return gridEditorLabFirmas;
        return gridEditorFooter;
    }

    function listForSection(sectionKey) {
        if (sectionKey === 'header') return headerList;
        if (sectionKey === 'patient_doctor') return patientList;
        if (sectionKey === 'lab_firmas') return labFirmasList;
        return footerList;
    }

    function findInstanceByUid(sectionKey, uid) {
        var ul = listForSection(sectionKey);
        if (!ul || !uid) return null;
        var found = null;
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            if (!found && li.getAttribute('data-uid') === uid) found = li;
        });
        return found;
    }

    function instanceDisplayLabel(type) {
        return (window._elementLabels && window._elementLabels[type]) ? window._elementLabels[type] : type;
    }

    function refreshInstanceHeader(li, elementType) {
        if (!li) return;
        var label = instanceDisplayLabel(elementType);
        li.setAttribute('data-element-type', elementType);
        var headStrong = li.querySelector('.pdf-instance-head strong');
        var headMono = li.querySelector('.pdf-instance-head .font-monospace');
        if (headStrong) headStrong.textContent = label;
        if (headMono) headMono.textContent = elementType;
    }

    function syncSpanOptionsFromPlacement(li) {
        if (!li) return;
        updateSpanOptionsForRow(li);
        var spanSel = li.querySelector('.instance-span');
        var colSel = li.querySelector('.instance-column');
        if (!spanSel || !colSel) return;
        if (String(colSel.value) === '-1') return;
        var maxS = spanSel.options.length > 0 ? spanSel.options.length : 1;
        var cur = parseInt(spanSel.value, 10);
        if (isNaN(cur) || cur < 1) cur = 1;
        if (cur > maxS) spanSel.value = String(maxS);
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
            var isLabOnly = LAB_ELEMENT_TYPES.indexOf(t) >= 0;
            var show = (sec === 'lab_firmas') ? isLabOnly : (!isLabOnly || t === 'custom_text');
            o.hidden = !show;
            o.disabled = !show;
            if (show && firstVisible === null) firstVisible = o;
        }
        if (firstVisible) typeEl.value = firstVisible.value;
    }

    function renderFieldPalette() {
        if (!fieldPalette) return;
        var html = '';
        Object.keys(window._elementLabels || {}).forEach(function(type) {
            html += '<div class="pdf-palette-item" draggable="true" data-type="' + escapeHtml(type) + '">' +
                '<i class="fa-solid fa-grip-lines text-muted"></i>' +
                '<span>' + escapeHtml(window._elementLabels[type]) + '</span>' +
                '</div>';
        });
        fieldPalette.innerHTML = html;
        fieldPalette.querySelectorAll('.pdf-palette-item').forEach(function(el) {
            el.addEventListener('dragstart', function(ev) {
                var span = paletteSpanSelect ? clampCols(paletteSpanSelect.value) : 1;
                var payload = {
                    kind: 'palette',
                    element_type: el.getAttribute('data-type') || '',
                    span: span
                };
                activeDragPayload = payload;
                ev.dataTransfer.setData('text/plain', JSON.stringify(payload));
                ev.dataTransfer.effectAllowed = 'copy';
            });
            el.addEventListener('dragend', function() {
                activeDragPayload = null;
                clearGridDropHighlights();
            });
        });
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

    function applyPlacementToLi(li, sectionKey, rowIdx, colIdx, spanVal, stackIdx) {
        if (!li) return;
        var ul = listForSection(sectionKey);
        if (!ul) return;
        if (li.parentElement !== ul) ul.appendChild(li);
        li.setAttribute('data-grid-row', String(Math.max(0, rowIdx)));
        li.setAttribute('data-grid-stack', String(Math.max(0, stackIdx)));
        var colSel = li.querySelector('.instance-column');
        if (colSel) colSel.value = String(Math.max(0, colIdx));
        syncSpanOptionsFromPlacement(li);
        var spanSel = li.querySelector('.instance-span');
        if (spanSel) {
            var maxS = spanSel.options.length > 0 ? spanSel.options.length : 1;
            var finalSpan = Math.max(1, Math.min(maxS, spanVal));
            spanSel.value = String(finalSpan);
        }
    }

    function stackCountForRegion(sectionKey, rowIdx, colIdx, spanVal) {
        var ul = listForSection(sectionKey);
        if (!ul) return 0;
        var count = 0;
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var colSel = li.querySelector('.instance-column');
            var enabledVal = colSel ? parseInt(colSel.value, 10) : -1;
            if (isNaN(enabledVal) || enabledVal < 0) return;
            var p = readGridPlacement(li, sectionKey);
            if (p.row == null) return;
            if (p.row === rowIdx && p.col === colIdx && p.span === spanVal) count++;
        });
        return count;
    }

    function addTypeToRegion(sectionKey, rowIdx, colIdx, spanVal, typeOpt) {
        var ul = listForSection(sectionKey);
        if (!ul) return;
        var type = typeOpt || selectedTypeForSection(sectionKey);
        if (!type || !isTypeAllowedInSection(type, sectionKey)) return;
        var span = Math.max(1, spanVal || 1);
        var stackIdx = stackCountForRegion(sectionKey, rowIdx, colIdx, span);
        var li = createInstanceRow(newUid(), type, colsForList(ul), true, colIdx, span, sectionKey);
        applyPlacementToLi(li, sectionKey, rowIdx, colIdx, span, stackIdx);
        ul.appendChild(li);
        wireInstanceSelects();
    }

    function readGridPlacement(li, sectionKey) {
        var ul = listForSection(sectionKey);
        var n = colsForList(ul);
        var rowRaw = li.getAttribute('data-grid-row');
        var stackRaw = li.getAttribute('data-grid-stack');
        var row = (rowRaw == null || String(rowRaw).trim() === '') ? null : Math.max(0, parseInt(rowRaw, 10) || 0);
        var stack = (stackRaw == null || String(stackRaw).trim() === '') ? null : Math.max(0, parseInt(stackRaw, 10) || 0);
        var colSel = li.querySelector('.instance-column');
        var col = colSel ? parseInt(colSel.value, 10) : 0;
        if (isNaN(col) || col < 0) col = 0;
        var spanSel = li.querySelector('.instance-span');
        var span = spanSel && !spanSel.disabled ? parseInt(spanSel.value, 10) : 1;
        if (isNaN(span) || span < 1) span = 1;
        span = Math.max(1, Math.min(Math.max(1, n - col), span));
        return { row: row, stack: stack, col: col, span: span };
    }

    function packImplicitGridForSection(sectionKey) {
        var ul = listForSection(sectionKey);
        if (!ul) return;
        var n = colsForList(ul);
        var items = [];
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var colSel = li.querySelector('.instance-column');
            var v = colSel ? parseInt(colSel.value, 10) : -1;
            if (isNaN(v) || v < 0) return;
            var spanSel = li.querySelector('.instance-span');
            var span = spanSel && !spanSel.disabled ? parseInt(spanSel.value, 10) : 1;
            if (isNaN(span) || span < 1) span = 1;
            span = Math.max(1, Math.min(Math.max(1, n - v), span));
            items.push({ li: li, col: v, span: span });
        });
        var i = 0;
        var rowIdx = 0;
        function rangesOverlap(a0, a1, b0, b1) {
            return a0 < b1 && b0 < a1;
        }
        while (i < items.length) {
            var colspans = [];
            var stacks = new Array(n).fill(null);
            function canPlace(norm) {
                var c = norm.col;
                var s = norm.span;
                if (s > 1) {
                    for (var k = 0; k < colspans.length; k++) {
                        if (colspans[k].col === c && colspans[k].span === s) return true;
                    }
                    for (var x = 0; x < colspans.length; x++) {
                        if (rangesOverlap(c, c + s, colspans[x].col, colspans[x].col + colspans[x].span)) return false;
                    }
                    for (var cc = c; cc < c + s; cc++) {
                        if (stacks[cc] !== null && stacks[cc].length > 0) return false;
                    }
                    return true;
                }
                for (var j = 0; j < colspans.length; j++) {
                    if (c >= colspans[j].col && c < colspans[j].col + colspans[j].span) return false;
                }
                return true;
            }
            function place(norm) {
                var c = norm.col;
                var s = norm.span;
                if (s > 1) {
                    for (var k = 0; k < colspans.length; k++) {
                        if (colspans[k].col === c && colspans[k].span === s) {
                            colspans[k].items.push(norm);
                            return;
                        }
                    }
                    colspans.push({ col: c, span: s, items: [norm] });
                } else {
                    if (!stacks[c]) stacks[c] = [];
                    stacks[c].push(norm);
                }
            }
            var j = i;
            while (j < items.length) {
                if (!canPlace(items[j])) break;
                place(items[j]);
                j++;
            }
            if (j === i) {
                i++;
                continue;
            }
            colspans.forEach(function(region) {
                region.items.forEach(function(norm, idx) {
                    norm.li.setAttribute('data-grid-row', String(rowIdx));
                    norm.li.setAttribute('data-grid-stack', String(idx));
                });
            });
            stacks.forEach(function(stack) {
                if (!stack) return;
                stack.forEach(function(norm, idx) {
                    norm.li.setAttribute('data-grid-row', String(rowIdx));
                    norm.li.setAttribute('data-grid-stack', String(idx));
                });
            });
            rowIdx++;
            i = j;
        }
    }

    function ensureGridPlacementForSection(sectionKey) {
        var ul = listForSection(sectionKey);
        if (!ul) return;
        var missing = false;
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var colSel = li.querySelector('.instance-column');
            var v = colSel ? parseInt(colSel.value, 10) : -1;
            if (isNaN(v) || v < 0) return;
            var rowRaw = li.getAttribute('data-grid-row');
            if (rowRaw == null || String(rowRaw).trim() === '') missing = true;
        });
        if (missing) packImplicitGridForSection(sectionKey);
    }

    function rangesOverlapGrid(a0, a1, b0, b1) {
        return a0 < b1 && b0 < a1;
    }

    function canResizeRegion(sectionKey, rowIdx, colIdx, oldSpan, nextSpan) {
        var ul = listForSection(sectionKey);
        if (!ul) return false;
        var row = Math.max(0, rowIdx);
        var col = Math.max(0, colIdx);
        var n = colsForList(ul);
        var maxByCols = Math.max(1, n - col);
        var span = Math.max(1, Math.min(maxByCols, nextSpan));
        var oldEnd = col + Math.max(1, oldSpan);
        var newEnd = col + span;
        var ok = true;
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            if (!ok) return;
            var p = readGridPlacement(li, sectionKey);
            if (p.row == null || p.row !== row) return;
            if (p.col === col && p.span === oldSpan) return;
            var end = p.col + Math.max(1, p.span);
            if (rangesOverlapGrid(col, newEnd, p.col, end)) ok = false;
        });
        // También debe ser al menos tan ancho como el rango previo ocupado por la región.
        if (span < 1 || newEnd <= col || oldEnd <= col) ok = false;
        return ok;
    }

    function applyRegionSpan(sectionKey, rowIdx, colIdx, oldSpan, nextSpan) {
        var ul = listForSection(sectionKey);
        if (!ul) return false;
        var n = colsForList(ul);
        var span = Math.max(1, Math.min(Math.max(1, n - colIdx), nextSpan));
        if (!canResizeRegion(sectionKey, rowIdx, colIdx, oldSpan, span)) return false;
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var p = readGridPlacement(li, sectionKey);
            if (p.row == null) return;
            if (p.row !== rowIdx || p.col !== colIdx || p.span !== oldSpan) return;
            var spanSel = li.querySelector('.instance-span');
            if (spanSel) {
                syncSpanOptionsFromPlacement(li);
                var maxS = spanSel.options.length > 0 ? spanSel.options.length : 1;
                spanSel.value = String(Math.max(1, Math.min(maxS, span)));
            }
        });
        return true;
    }

    function normalizeRegionStacks(sectionKey, rowIdx, colIdx, spanVal) {
        var ul = listForSection(sectionKey);
        if (!ul) return [];
        var list = [];
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var colSel = li.querySelector('.instance-column');
            var enabledVal = colSel ? parseInt(colSel.value, 10) : -1;
            if (isNaN(enabledVal) || enabledVal < 0) return;
            var p = readGridPlacement(li, sectionKey);
            if (p.row == null) return;
            if (p.row === rowIdx && p.col === colIdx && p.span === spanVal) {
                list.push({ li: li, stack: p.stack == null ? 0 : p.stack });
            }
        });
        list.sort(function(a, b) { return a.stack - b.stack; });
        list.forEach(function(rec, idx) {
            rec.li.setAttribute('data-grid-stack', String(idx));
        });
        return list.map(function(rec) { return rec.li; });
    }

    function mergeRegionWithNextRow(sectionKey, rowIdx, colIdx, spanVal) {
        var ul = listForSection(sectionKey);
        if (!ul) return false;
        var nextRow = rowIdx + 1;
        var secRows = rowsForSectionKey(sectionKey);
        if (nextRow >= secRows) return false;

        var curr = normalizeRegionStacks(sectionKey, rowIdx, colIdx, spanVal);
        var next = normalizeRegionStacks(sectionKey, nextRow, colIdx, spanVal);
        if (!next || next.length === 0) return false;

        var start = curr.length;
        next.forEach(function(li, idx) {
            li.setAttribute('data-grid-row', String(rowIdx));
            li.setAttribute('data-grid-stack', String(start + idx));
        });
        normalizeRegionStacks(sectionKey, rowIdx, colIdx, spanVal);
        return true;
    }

    function validateGridConflictsBeforeSave() {
        var errors = [];
        ['header', 'patient_doctor', 'lab_firmas', 'footer'].forEach(function(sectionKey) {
            var ul = listForSection(sectionKey);
            if (!ul) return;
            var sectionLabel = sectionKey === 'patient_doctor' ? 'Paciente / médico' : (sectionKey === 'lab_firmas' ? 'Validación / firmas' : (sectionKey === 'header' ? 'Encabezado' : 'Pie'));
            var rows = rowsForSectionKey(sectionKey);
            var cols = colsForList(ul);
            var byRow = {};
            ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
                var colSel = li.querySelector('.instance-column');
                var enabledVal = colSel ? parseInt(colSel.value, 10) : -1;
                if (isNaN(enabledVal) || enabledVal < 0) return;
                var p = readGridPlacement(li, sectionKey);
                var row = p.row == null ? 0 : Math.max(0, Math.min(rows - 1, p.row));
                if (!byRow[row]) byRow[row] = [];
                byRow[row].push({
                    uid: li.getAttribute('data-uid') || '',
                    type: li.getAttribute('data-element-type') || '',
                    col: Math.max(0, Math.min(cols - 1, p.col)),
                    span: Math.max(1, Math.min(Math.max(1, cols - p.col), p.span))
                });
            });
            Object.keys(byRow).forEach(function(rk) {
                var rowIdx = parseInt(rk, 10);
                var regions = {};
                byRow[rk].forEach(function(rec) {
                    var key = rec.col + ':' + rec.span;
                    if (!regions[key]) regions[key] = [];
                    regions[key].push(rec);
                });
                var keys = Object.keys(regions).map(function(k) {
                    var p = k.split(':');
                    return { col: parseInt(p[0], 10), span: parseInt(p[1], 10), key: k };
                }).sort(function(a, b) { return a.col - b.col; });
                for (var i = 0; i < keys.length; i++) {
                    for (var j = i + 1; j < keys.length; j++) {
                        var a = keys[i], b = keys[j];
                        if (rangesOverlapGrid(a.col, a.col + a.span, b.col, b.col + b.span)) {
                            errors.push('Conflicto de matriz en ' + sectionLabel + ' (fila ' + (rowIdx + 1) + '): celdas superpuestas.');
                            i = keys.length;
                            break;
                        }
                    }
                }
            });
        });
        return errors;
    }

    function buildSectionGridEditor(sectionKey) {
        var ul = listForSection(sectionKey);
        var root = gridRootForSection(sectionKey);
        if (!ul || !root) return;
        ensureGridPlacementForSection(sectionKey);
        var cols = colsForList(ul);
        var rows = rowsForSectionKey(sectionKey);
        root.innerHTML = '';
        var itemsByRegion = {};
        ul.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var colSel = li.querySelector('.instance-column');
            var enabledVal = colSel ? parseInt(colSel.value, 10) : -1;
            if (isNaN(enabledVal) || enabledVal < 0) return;
            var p = readGridPlacement(li, sectionKey);
            var row = p.row == null ? 0 : Math.min(rows - 1, p.row);
            li.setAttribute('data-grid-row', String(row));
            var key = row + ':' + p.col + ':' + p.span;
            if (!itemsByRegion[key]) itemsByRegion[key] = [];
            itemsByRegion[key].push({ li: li, row: row, col: p.col, span: p.span, stack: p.stack == null ? 0 : p.stack });
        });
        Object.keys(itemsByRegion).forEach(function(key) {
            itemsByRegion[key].sort(function(a, b) { return a.stack - b.stack; });
        });

        for (var row = 0; row < rows; row++) {
            var rowWrap = document.createElement('div');
            var lbl = document.createElement('div');
            lbl.className = 'pdf-grid-row-label';
            lbl.textContent = 'Fila ' + (row + 1);
            rowWrap.appendChild(lbl);
            var grid = document.createElement('div');
            grid.className = 'pdf-grid-row-editor';
            grid.style.gridTemplateColumns = 'repeat(' + cols + ', minmax(170px, 1fr))';

            var occupied = {};
            Object.keys(itemsByRegion).forEach(function(key) {
                var parts = key.split(':');
                if (parseInt(parts[0], 10) !== row) return;
                var c = parseInt(parts[1], 10);
                var s = parseInt(parts[2], 10);
                for (var x = c; x < c + s; x++) occupied[x] = true;
            });

            for (var col = 0; col < cols; col++) {
                if (occupied[col]) {
                    // solo renderiza la región al inicio.
                    var foundRegion = null;
                    Object.keys(itemsByRegion).some(function(key) {
                        var parts = key.split(':');
                        if (parseInt(parts[0], 10) === row && parseInt(parts[1], 10) === col) {
                            foundRegion = { key: key, span: parseInt(parts[2], 10), items: itemsByRegion[key] };
                            return true;
                        }
                        return false;
                    });
                    if (!foundRegion) continue;
                    var cell = document.createElement('div');
                    cell.className = 'pdf-grid-cell-editor';
                    cell.style.gridColumn = String(col + 1) + ' / span ' + foundRegion.span;
                    cell.setAttribute('data-drop-row', String(row));
                    cell.setAttribute('data-drop-col', String(col));
                    cell.setAttribute('data-drop-span', String(foundRegion.span));
                    cell.setAttribute('data-drop-section', sectionKey);
                    var stack = document.createElement('div');
                    stack.className = 'pdf-grid-cell-stack';
                    foundRegion.items.forEach(function(rec, idx) {
                        var chip = document.createElement('div');
                        chip.className = 'pdf-grid-chip';
                        chip.draggable = true;
                        chip.setAttribute('data-uid', rec.li.getAttribute('data-uid') || '');
                        chip.setAttribute('data-section', sectionKey);
                        chip.setAttribute('data-row', String(row));
                        chip.setAttribute('data-col', String(col));
                        chip.setAttribute('data-span', String(foundRegion.span));
                        chip.setAttribute('data-stack', String(idx));
                        chip.innerHTML = '<div class="pdf-grid-chip-main"><div class="pdf-grid-chip-title">' + escapeHtml(instanceDisplayLabel(rec.li.getAttribute('data-element-type') || '')) + '</div><div class="pdf-grid-chip-meta">' + escapeHtml(rec.li.getAttribute('data-element-type') || '') + ' · ' + escapeHtml(foundRegion.span === 1 ? '1 col.' : (foundRegion.span + ' cols.')) + '</div></div>' +
                            '<div class="pdf-grid-chip-actions">' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary pdf-grid-chip-span-dec" title="Reducir ancho"><i class="fa-solid fa-minus"></i></button>' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary pdf-grid-chip-merge-col" title="Unir columnas"><i class="fa-solid fa-arrows-left-right"></i></button>' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary pdf-grid-chip-merge-row" title="Unir filas"><i class="fa-solid fa-arrows-up-down"></i></button>' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary pdf-grid-chip-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>' +
                            '</div>';
                        stack.appendChild(chip);
                    });
                    var addWrap = document.createElement('div');
                    addWrap.className = 'pdf-grid-cell-add';
                    addWrap.innerHTML = '<button type="button" class="btn btn-sm btn-outline-primary pdf-grid-add-cell" title="Agregar aquí" data-add-section="' + sectionKey + '" data-add-row="' + row + '" data-add-col="' + col + '" data-add-span="' + foundRegion.span + '"><i class="fa-solid fa-circle-plus"></i></button>';
                    stack.appendChild(addWrap);
                    cell.appendChild(stack);
                    grid.appendChild(cell);
                    col += foundRegion.span - 1;
                } else {
                    var empty = document.createElement('div');
                    empty.className = 'pdf-grid-cell-editor';
                    empty.setAttribute('data-drop-row', String(row));
                    empty.setAttribute('data-drop-col', String(col));
                    empty.setAttribute('data-drop-span', '1');
                    empty.setAttribute('data-drop-section', sectionKey);
                    empty.innerHTML = '<div class="pdf-grid-cell-stack"><div class="pdf-grid-cell-empty">Soltá aquí</div><div class="pdf-grid-cell-add"><button type="button" class="btn btn-sm btn-outline-primary pdf-grid-add-cell" title="Agregar aquí" data-add-section="' + sectionKey + '" data-add-row="' + row + '" data-add-col="' + col + '" data-add-span="1"><i class="fa-solid fa-circle-plus"></i></button></div></div>';
                    grid.appendChild(empty);
                }
            }
            rowWrap.appendChild(grid);
            root.appendChild(rowWrap);
        }
    }

    function rebuildGridEditors() {
        ['header', 'patient_doctor', 'lab_firmas', 'footer'].forEach(buildSectionGridEditor);
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

    /** Estilo por defecto de instancia (coincide con DEFAULT_TEXT_STYLE del servidor). */
    function defaultInstanceTextStyleJson() {
        return {
            font_family: 'DejaVu Sans',
            font_size_pt: 10,
            font_weight: 'normal',
            font_color: '#333333',
            font_style: 'normal',
            text_transform: 'none',
            letter_spacing_em: 0,
            line_height: 1.35,
            text_shadow: 'none'
        };
    }

    /** Lee un bloque de estilo ct-lbl-* / ct-val-* dentro de un contenedor. */
    function readCtStyleInScope(scope, pfx) {
        function readStr(suf, listKey, fallback) {
            var allowed = pdfAllow(listKey);
            var el = scope ? scope.querySelector('.' + pfx + '-' + suf) : null;
            var v = el ? String(el.value || '').trim() : '';
            return allowed.indexOf(v) >= 0 ? v : fallback;
        }
        function readNum(suf, minV, maxV, step, fallback) {
            var el = scope ? scope.querySelector('.' + pfx + '-' + suf) : null;
            var n = el ? parseFloat(el.value) : NaN;
            if (isNaN(n)) n = fallback;
            n = Math.max(minV, Math.min(maxV, n));
            return Math.round(n / step) * step;
        }
        var clrEl = scope ? scope.querySelector('.' + pfx + '-font-color') : null;
        var clr = clrEl ? String(clrEl.value || '').trim() : '#333333';
        if (!isValidPdfHexJs(clr)) clr = '#333333';
        return {
            font_family: readStr('font-family', 'font_families', 'DejaVu Sans'),
            font_size_pt: readNum('font-size', 6, 24, 0.5, 10),
            font_weight: readStr('font-weight', 'font_weights', 'normal'),
            font_color: clr,
            font_style: readStr('font-style', 'font_styles', 'normal'),
            text_transform: readStr('text-transform', 'text_transforms', 'none'),
            letter_spacing_em: readNum('letter-spacing', -0.2, 1, 0.01, 0),
            line_height: readNum('line-height', 1, 3, 0.05, 1.35),
            text_shadow: readStr('text-shadow', 'text_shadows', 'none')
        };
    }

    function readInstanceCustomText(li) {
        var lblIn = li.querySelector('.custom-text-label-input');
        var valIn = li.querySelector('.custom-text-value-input');
        var showCb = li.querySelector('.custom-text-show-label');
        var modeSel = li.querySelector('.custom-text-line-mode');
        var lblScope = li.querySelector('.custom-text-style-label');
        var valScope = li.querySelector('.custom-text-style-value');
        return {
            label: lblIn ? String(lblIn.value || '').slice(0, 200) : '',
            value: valIn ? String(valIn.value || '').slice(0, 500) : '',
            show_label: !!(showCb && showCb.checked),
            line_mode: (modeSel && modeSel.value === 'inline') ? 'inline' : 'stacked',
            label_style: readCtStyleInScope(lblScope, 'ct-lbl'),
            value_style: readCtStyleInScope(valScope, 'ct-val')
        };
    }

    function applyInstanceCustomText(li, ct) {
        if (!li || !ct || typeof ct !== 'object') return;
        function setVal(sel, v) {
            var el = li.querySelector(sel);
            if (el && v != null) el.value = String(v);
        }
        function setChk(sel, on) {
            var el = li.querySelector(sel);
            if (el) el.checked = !!on;
        }
        setVal('.custom-text-label-input', ct.label);
        setVal('.custom-text-value-input', ct.value);
        setChk('.custom-text-show-label', ct.show_label);
        setVal('.custom-text-line-mode', (ct.line_mode === 'inline') ? 'inline' : 'stacked');
        function applyScope(scope, pfx, ts) {
            if (!scope || !ts || typeof ts !== 'object') return;
            function setS(suf, v) {
                var el = scope.querySelector('.' + pfx + '-' + suf);
                if (el && v != null) el.value = String(v);
            }
            setS('font-family', ts.font_family);
            setS('font-size', ts.font_size_pt);
            setS('font-weight', ts.font_weight);
            setS('font-color', ts.font_color);
            setS('font-style', ts.font_style);
            setS('text-transform', ts.text_transform);
            setS('letter-spacing', ts.letter_spacing_em);
            setS('line-height', ts.line_height);
            setS('text-shadow', ts.text_shadow);
        }
        applyScope(li.querySelector('.custom-text-style-label'), 'ct-lbl', ct.label_style);
        applyScope(li.querySelector('.custom-text-style-value'), 'ct-val', ct.value_style);
    }

    function buildCustomTextPreviewInnerHtml(ct) {
        var stL = textStyleToInlineCss(ct.label_style || defaultInstanceTextStyleJson());
        var stV = textStyleToInlineCss(ct.value_style || defaultInstanceTextStyleJson());
        var labT = String(ct.label || '');
        var valT = String(ct.value || '');
        var showLbl = !!ct.show_label && labT !== '';
        var inlineM = (ct.line_mode === 'inline');
        var valDisp = valT !== '' ? valT : '—';
        if (inlineM && showLbl) {
            return '<p style="margin:0;"><span style="' + escapeHtml(stL) + '">' + escapeHtml(labT) + '</span> <span style="' + escapeHtml(stV) + '">' + escapeHtml(valDisp) + '</span></p>';
        }
        if (inlineM) {
            return '<p style="margin:0;"><span style="' + escapeHtml(stV) + '">' + escapeHtml(valDisp) + '</span></p>';
        }
        if (showLbl) {
            return '<p style="margin:0;"><span style="' + escapeHtml(stL) + '">' + escapeHtml(labT) + '</span></p>' +
                '<p style="margin:0;"><span style="' + escapeHtml(stV) + '">' + escapeHtml(valDisp) + '</span></p>';
        }
        return '<p style="margin:0;"><span style="' + escapeHtml(stV) + '">' + escapeHtml(valDisp) + '</span></p>';
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
        function pack(secKey, colsInput, rowsInput) {
            var n = clampCols(colsInput.value);
            var st = readSectionStyleFromDom(secKey, n);
            return {
                columns: n,
                rows: clampRows(rowsInput && rowsInput.value),
                line_height: st.line_height,
                column_align_h: st.column_align_h,
                column_align_v: st.column_align_v
            };
        }
        return {
            header: pack('header', secColsH, secRowsH),
            patient_doctor: pack('patient_doctor', secColsP, secRowsP),
            lab_firmas: pack('lab_firmas', secColsL, secRowsL),
            footer: pack('footer', secColsF, secRowsF)
        };
    }

    /** Pie: tipografía por instancia (como element.php); ft solo para textos/disposición de «generado el». */
    function buildFooterPreviewPieceHtml(type, sample, ft, instanceTs) {
        var sti = textStyleToInlineCss(instanceTs || defaultInstanceTextStyleJson());
        if (type === 'footer_company') {
            return '<div class="footer-piece footer-piece-company" style="' + escapeHtml(sti) + '">' + escapeHtml(sample) + '</div>';
        }
        if (type === 'footer_policy') {
            return '<div class="footer-piece footer-piece-policy"><small class="footer-policy-text" style="' + escapeHtml(sti) + '">' + escapeHtml(sample) + '</small></div>';
        }
        if (type === 'footer_generated') {
            var pref = String(ft.label_footer_generated != null ? ft.label_footer_generated : '').trim();
            var showP = !!ft.show_label_footer_generated;
            var inlineF = (ft.label_footer_generated_line_mode === 'inline');
            var showPref = showP && pref !== '';
            var dateSample = '10/04/2026 14:35';
            var parts = '<div class="footer-piece footer-piece-generated">';
            if (inlineF) {
                if (showPref) {
                    parts += '<span class="footer-generated-label" style="' + escapeHtml(sti) + '">' + escapeHtml(pref) + '</span> ';
                }
                parts += '<span class="footer-generated-datetime" style="' + escapeHtml(sti) + '">' + escapeHtml(dateSample) + '</span>';
            } else {
                if (showPref) {
                    parts += '<div class="footer-generated-label" style="' + escapeHtml(sti) + '">' + escapeHtml(pref) + '</div>';
                }
                parts += '<div class="footer-generated-datetime" style="' + escapeHtml(sti) + '">' + escapeHtml(dateSample) + '</div>';
            }
            parts += '</div>';
            return parts;
        }
        return escapeHtml(sample);
    }

    function headerGridLabelPieceStyleCss(hg, piece) {
        if (!hg || typeof hg !== 'object') return '';
        var fn = String(hg.font_family || 'DejaVu Sans').trim();
        var ffCss = (fn.indexOf(' ') >= 0 || fn.indexOf('"') >= 0)
            ? '"' + fn.replace(/["\\]/g, '') + '", sans-serif'
            : fn.replace(/["\\]/g, '') + ', sans-serif';
        var lh = parseFloat(hg.line_height);
        if (isNaN(lh)) lh = 1.35;
        lh = Math.max(1, Math.min(3, lh));
        var tt = String(hg.text_transform || 'none');
        function decl(color, fs, fw, fst) {
            var fsn = parseFloat(fs);
            if (isNaN(fsn)) fsn = parseFloat(hg.font_size_pt) || 9.5;
            fsn = Math.max(7, Math.min(20, fsn));
            return 'color:' + String(color)
                + ';font-family:' + ffCss
                + ';font-size:' + fsn + 'pt'
                + ';font-weight:' + String(fw)
                + ';font-style:' + String(fst)
                + ';text-transform:' + tt
                + ';line-height:' + lh;
        }
        if (piece === 'qr_hint') {
            return decl(
                hg.label_qr_hint_text_color || hg.body_text_color,
                hg.label_qr_hint_font_size_pt,
                hg.label_qr_hint_font_weight || hg.font_weight,
                hg.label_qr_hint_font_style || hg.font_style
            );
        }
        var ids = window._headerLabelFieldIds || [];
        if (ids.indexOf(piece) < 0) return '';
        return decl(
            hg['label_' + piece + '_text_color'] || hg.body_text_color,
            hg['label_' + piece + '_font_size_pt'],
            hg['label_' + piece + '_font_weight'] || hg.font_weight,
            hg['label_' + piece + '_font_style'] || hg.font_style
        );
    }

    function buildHeaderPreviewPieceHtml(type, sample, hg, instanceTs) {
        var st = headerGridLabelPieceStyleCss;
        var stI = textStyleToInlineCss(instanceTs || defaultInstanceTextStyleJson());
        function labeledBlock(fid, valueInnerHtml) {
            var lbl = String(hg['label_' + fid] != null ? hg['label_' + fid] : '').trim();
            var showL = !!hg['show_label_' + fid];
            var inline = (hg['label_' + fid + '_line_mode'] === 'inline');
            var showTxt = showL && lbl !== '';
            var stL = st(hg, fid);
            var wrappedVal = '<span style="' + escapeHtml(stI) + '">' + valueInnerHtml + '</span>';
            if (!showTxt) return wrappedVal;
            if (inline) {
                return '<span style="' + escapeHtml(stL) + '">' + escapeHtml(lbl) + '</span> ' + wrappedVal;
            }
            return '<div><span style="' + escapeHtml(stL) + '">' + escapeHtml(lbl) + '</span></div><div>' + wrappedVal + '</div>';
        }
        if (type === 'logo') {
            return '<div class="header-preview-logo">' + labeledBlock('logo', escapeHtml(sample)) + '</div>';
        }
        if (type === 'lab_company') {
            return '<div class="header-preview-company">' + labeledBlock('lab_company', '<strong>' + escapeHtml(sample) + '</strong>') + '</div>';
        }
        if (type === 'lab_address') {
            return '<div class="header-preview-addr">' + labeledBlock('lab_address', escapeHtml(sample)) + '</div>';
        }
        if (type === 'lab_phone') {
            return '<div class="header-preview-phone">' + labeledBlock('lab_phone', escapeHtml(sample)) + '</div>';
        }
        if (type === 'lab_email') {
            return '<div class="header-preview-email">' + labeledBlock('lab_email', escapeHtml(sample)) + '</div>';
        }
        if (type === 'lab_website') {
            return '<div class="header-preview-web">' + labeledBlock('lab_website', escapeHtml(sample)) + '</div>';
        }
        if (type === 'paciente_institucion') {
            return '<div class="header-preview-institucion">' + labeledBlock('paciente_institucion', escapeHtml(sample)) + '</div>';
        }
        if (type === 'pdf_pages_total') {
            return '<div class="header-preview-pages-total">' + labeledBlock('pdf_pages_total', escapeHtml(sample)) + '</div>';
        }
        if (type === 'qr') {
            var hint = String(hg.label_qr_hint != null ? hg.label_qr_hint : '').trim();
            var showH = !!hg.show_label_qr_hint && hint !== '';
            var inlineQ = (hg.label_qr_hint_line_mode === 'inline');
            var stQ = st(hg, 'qr_hint');
            var qrPctPrev = parseInt(hg.qr_size_percent, 10);
            if (isNaN(qrPctPrev)) qrPctPrev = 100;
            qrPctPrev = Math.max(50, Math.min(400, qrPctPrev));
            var qrBoxPx = Math.round(36 * qrPctPrev / 100);
            var qrFs = Math.max(8, Math.round(10 * qrPctPrev / 100));
            var img = '<span style="display:inline-block;width:' + qrBoxPx + 'px;height:' + qrBoxPx + 'px;line-height:' + qrBoxPx + 'px;text-align:center;background:#6c757d;color:#fff;border-radius:4px;font-size:' + qrFs + 'px;font-weight:600;vertical-align:middle">QR</span>';
            if (inlineQ && showH) {
                return '<div class="text-center"><span style="display:inline-block;vertical-align:middle;margin-right:6px;' + escapeHtml(stQ) + '">' + escapeHtml(hint) + '</span>' + img + '</div>';
            }
            if (inlineQ) {
                return '<div class="text-center">' + img + '</div>';
            }
            if (showH) {
                return '<div class="text-center">' + img + '<div style="' + escapeHtml(stQ) + '">' + escapeHtml(hint) + '</div></div>';
            }
            return '<div class="text-center">' + img + '</div>';
        }
        return escapeHtml(sample);
    }

    function rebuildGridPreview(ul, previewEl) {
        if (!previewEl || !ul) return;
        var n = colsForList(ul);
        var sectionKey = ul.getAttribute('data-section') || 'header';
        var secSt = readSectionStyleFromDom(sectionKey, n);
        var ftFooter = null;
        var hgHeader = null;
        var pdHeader = null;
        if (sectionKey === 'footer') {
            ftFooter = readCardHeaderStyleForJson().footer_grid;
        }
        if (sectionKey === 'header') {
            hgHeader = readCardHeaderStyleForJson().header_grid;
        }
        if (sectionKey === 'patient_doctor') {
            pdHeader = readCardHeaderStyleForJson().patient_doctor_grid;
        }
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
            var line;
            if (type === 'custom_text') {
                line = '<div class="pdf-custom-text">' + buildCustomTextPreviewInnerHtml(readInstanceCustomText(li)) + '</div>';
            } else if (ftFooter && (type === 'footer_company' || type === 'footer_generated' || type === 'footer_policy')) {
                line = buildFooterPreviewPieceHtml(type, sample, ftFooter, readInstanceTextStyle(li));
            } else {
                var styleWrap = textStyleToInlineCss(readInstanceTextStyle(li));
                var rawLine;
                var headerLikeTypes = ['logo', 'lab_company', 'lab_address', 'paciente_institucion', 'lab_phone', 'lab_email', 'lab_website', 'pdf_pages_total', 'qr'];
                if (sectionKey === 'patient_doctor' && ['paciente_nombre','paciente_genero','paciente_edad','paciente_telefono','diagnostico_presuntivo','medico','fecha_recepcion','fecha_reporte','numero_orden'].indexOf(type) >= 0 && pdHeader) {
                    var showLpd = !!pdHeader['show_label_' + type];
                    var lblPd = String(pdHeader['label_' + type] != null ? pdHeader['label_' + type] : '').trim();
                    var inlinePd = (pdHeader['label_' + type + '_line_mode'] === 'inline');
                    var gapPd = parseInt(pdHeader['label_' + type + '_value_gap_px'], 10);
                    if (isNaN(gapPd)) gapPd = 0;
                    var mtPd = parseInt(pdHeader['label_' + type + '_space_above_px'], 10);
                    if (isNaN(mtPd)) mtPd = 0;
                    var mbPd = parseInt(pdHeader['label_' + type + '_space_below_px'], 10);
                    if (isNaN(mbPd)) mbPd = 0;
                    // Overrides por instancia (si existen en el editor).
                    var ovGapEl = li.querySelector('.instance-pd-label-value-gap-px');
                    var ovMtEl = li.querySelector('.instance-pd-space-above-px');
                    var ovMbEl = li.querySelector('.instance-pd-space-below-px');
                    if (ovGapEl) {
                        var ovG = parseInt(ovGapEl.value, 10);
                        if (!isNaN(ovG)) gapPd = Math.max(0, Math.min(40, ovG));
                    }
                    if (ovMtEl) {
                        var ovA = parseInt(ovMtEl.value, 10);
                        if (!isNaN(ovA)) mtPd = Math.max(0, Math.min(40, ovA));
                    }
                    if (ovMbEl) {
                        var ovB = parseInt(ovMbEl.value, 10);
                        if (!isNaN(ovB)) mbPd = Math.max(0, Math.min(40, ovB));
                    }
                    var valPd = escapeHtml(sample);
                    var lblEsc = escapeHtml(lblPd);
                    var showLblText = showLpd && lblPd !== '';
                    if (inlinePd) {
                        rawLine = '<div class="patient-line" style="margin-top:' + mtPd + 'px;margin-bottom:' + mbPd + 'px;">'
                            + (showLblText ? ('<span class="label" style="margin-right:' + gapPd + 'px;">' + lblEsc + '</span>') : '')
                            + (showLblText ? valPd : '<span style="margin-left:' + gapPd + 'px;display:inline-block;">' + valPd + '</span>')
                            + '</div>';
                    } else {
                        var valMtPd = showLblText ? 0 : (mtPd + gapPd);
                        rawLine = (showLblText
                            ? ('<div class="patient-line" style="margin-top:' + mtPd + 'px;margin-bottom:' + gapPd + 'px;"><span class="label">' + lblEsc + '</span></div>')
                            : '')
                            + '<div class="patient-line" style="margin-top:' + valMtPd + 'px;margin-bottom:' + mbPd + 'px;">' + valPd + '</div>';
                    }
                    line = '<div style="' + escapeHtml(styleWrap) + '">' + rawLine + '</div>';
                } else if (sectionKey === 'footer' && headerLikeTypes.indexOf(type) >= 0) {
                    line = buildHeaderPreviewPieceHtml(type, sample, readCardHeaderStyleForJson().header_grid, readInstanceTextStyle(li));
                } else if (hgHeader && headerLikeTypes.indexOf(type) >= 0) {
                    line = buildHeaderPreviewPieceHtml(type, sample, hgHeader, readInstanceTextStyle(li));
                } else {
                    rawLine = shortL ? ('<span class="pdf-preview-lbl">' + escapeHtml(shortL) + '</span> ' + escapeHtml(sample)) : escapeHtml(sample);
                    line = '<span style="' + escapeHtml(styleWrap) + '">' + rawLine + '</span>';
                }
            }
            items.push({ col: col, span: span, html: line });
        });
        previewEl.innerHTML = '';
        var wrap = document.createElement('div');
        if (ftFooter && ftFooter.section_top_border_enabled) {
            var bw = parseInt(ftFooter.section_top_border_width_px, 10);
            if (isNaN(bw)) bw = 1;
            bw = Math.max(0, Math.min(6, bw));
            var bc = String(ftFooter.section_top_border_color || '#DDDDDD');
            if (bw > 0) {
                wrap.style.borderTop = bw + 'px solid ' + bc;
            }
            wrap.style.paddingTop = '8px';
        }
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
        rebuildGridEditors();
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

    function createInstanceRow(uid, elementType, colCount, enabled, column, columnSpan, sectionKey) {
        if (columnSpan == null) columnSpan = 1;
        var label = (window._elementLabels && window._elementLabels[elementType]) ? window._elementLabels[elementType] : elementType;
        var pdTypes = ['paciente_nombre', 'paciente_genero', 'paciente_edad', 'paciente_telefono', 'diagnostico_presuntivo', 'medico', 'fecha_recepcion', 'fecha_reporte', 'numero_orden'];
        var pdSpacingDefaults = { gap: 0, above: 0, below: 0 };
        if (sectionKey === 'patient_doctor' && pdTypes.indexOf(elementType) >= 0) {
            var pdHeader;
            try {
                pdHeader = readCardHeaderStyleForJson().patient_doctor_grid;
            } catch (e) {
                pdHeader = null;
            }
            if (pdHeader) {
                var g = parseInt(pdHeader['label_' + elementType + '_value_gap_px'], 10);
                var a = parseInt(pdHeader['label_' + elementType + '_space_above_px'], 10);
                var b = parseInt(pdHeader['label_' + elementType + '_space_below_px'], 10);
                pdSpacingDefaults.gap = isNaN(g) ? 0 : Math.max(0, Math.min(40, g));
                pdSpacingDefaults.above = isNaN(a) ? 0 : Math.max(0, Math.min(40, a));
                pdSpacingDefaults.below = isNaN(b) ? 0 : Math.max(0, Math.min(40, b));
            }
        }
        var li = document.createElement('li');
        li.className = 'list-group-item pdf-instance-item';
        li.setAttribute('data-uid', uid);
        li.setAttribute('data-element-type', elementType);
        li.setAttribute('data-grid-row', '');
        li.setAttribute('data-grid-stack', '');
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
        if (elementType === 'custom_text') {
            var tplCt = document.getElementById('tpl_pdf_custom_text_editor');
            if (tplCt && tplCt.innerHTML) {
                li.insertAdjacentHTML('beforeend', tplCt.innerHTML.replace(/__PDF_UID__/g, uid));
            }
        } else {
            var addPdSpacing = (sectionKey === 'patient_doctor' && pdTypes.indexOf(elementType) >= 0);
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
                '</div>' +
                (addPdSpacing
                    ? (
                        '<div class="row g-3 mt-2 pdf-patient-spacing-controls">' +
                        '  <div class="col-12 col-md-4 col-lg-4"><label class="form-label small mb-1">Separación label/valor (px)</label>' +
                        '    <input type="number" class="form-control form-control-sm instance-pd-label-value-gap-px" min="0" max="40" step="1" value="' + pdSpacingDefaults.gap + '"></div>' +
                        '  <div class="col-12 col-md-4 col-lg-4"><label class="form-label small mb-1">Espacio arriba (px)</label>' +
                        '    <input type="number" class="form-control form-control-sm instance-pd-space-above-px" min="0" max="40" step="1" value="' + pdSpacingDefaults.above + '"></div>' +
                        '  <div class="col-12 col-md-4 col-lg-4"><label class="form-label small mb-1">Espacio abajo (px)</label>' +
                        '    <input type="number" class="form-control form-control-sm instance-pd-space-below-px" min="0" max="40" step="1" value="' + pdSpacingDefaults.below + '"></div>' +
                        '</div>'
                    )
                    : '')
            );
        }
        return li;
    }

    function isTypeAllowedInSection(type, sectionKey) {
        var isLabOnly = LAB_ELEMENT_TYPES.indexOf(type) >= 0;
        if (sectionKey === 'lab_firmas') return isLabOnly || type === 'custom_text';
        return !isLabOnly;
    }

    function firstAllowedTypeForSection(sectionKey) {
        var out = null;
        Object.keys(window._elementLabels || {}).some(function(type) {
            if (!isTypeAllowedInSection(type, sectionKey)) return false;
            out = type;
            return true;
        });
        return out;
    }

    function selectedTypeForSection(sectionKey) {
        var addTypeEl = document.getElementById('add_element_type');
        var t = addTypeEl ? String(addTypeEl.value || '') : '';
        if (t && isTypeAllowedInSection(t, sectionKey)) return t;
        return firstAllowedTypeForSection(sectionKey);
    }

    function readDragPayload(ev) {
        var raw = ev.dataTransfer ? ev.dataTransfer.getData('text/plain') : '';
        if (!raw) return null;
        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function dragPayloadFromEventOrState(ev) {
        var p = readDragPayload(ev);
        if (p) return p;
        return activeDragPayload;
    }

    function copyStandardStyleIfPossible(fromLi, toLi) {
        if (!fromLi || !toLi) return;
        if ((fromLi.getAttribute('data-element-type') || '') === 'custom_text') return;
        if ((toLi.getAttribute('data-element-type') || '') === 'custom_text') return;
        var ts = readInstanceTextStyle(fromLi);
        function setIf(q, v) {
            var el = toLi.querySelector(q);
            if (el) el.value = String(v);
        }
        setIf('.instance-font-family', ts.font_family);
        setIf('.instance-font-size', ts.font_size_pt);
        setIf('.instance-font-weight', ts.font_weight);
        setIf('.instance-font-color', ts.font_color);
        setIf('.instance-font-style', ts.font_style);
        setIf('.instance-text-transform', ts.text_transform);
        setIf('.instance-letter-spacing', ts.letter_spacing_em);
        setIf('.instance-line-height', ts.line_height);
        setIf('.instance-text-shadow', ts.text_shadow);
        ['.instance-pd-label-value-gap-px', '.instance-pd-space-above-px', '.instance-pd-space-below-px'].forEach(function(q) {
            var src = fromLi.querySelector(q);
            var dst = toLi.querySelector(q);
            if (src && dst) dst.value = String(src.value || '0');
        });
    }

    function copyInstanceValues(fromLi, toLi) {
        if (!fromLi || !toLi) return;
        var srcType = fromLi.getAttribute('data-element-type') || '';
        if (srcType === 'custom_text') {
            applyInstanceCustomText(toLi, readInstanceCustomText(fromLi));
            return;
        }
        copyStandardStyleIfPossible(fromLi, toLi);
    }

    function cloneInstanceRowWithValues(fromLi, sectionKey, colCount, enabled, col, span) {
        if (!fromLi) return null;
        var srcType = fromLi.getAttribute('data-element-type') || '';
        if (!srcType || !isTypeAllowedInSection(srcType, sectionKey)) return null;
        var clone = createInstanceRow(newUid(), srcType, colCount, enabled, enabled ? col : 0, enabled ? span : 1, sectionKey);
        if (!enabled) {
            var cloneCol = clone.querySelector('.instance-column');
            if (cloneCol) cloneCol.value = '-1';
        }
        copyInstanceValues(fromLi, clone);
        return clone;
    }

    function replaceTargetWithPalette(targetLi, sectionKey, newType) {
        if (!targetLi || !sectionKey || !newType) return;
        if (!isTypeAllowedInSection(newType, sectionKey)) return;
        var ul = listForSection(sectionKey);
        var n = colsForList(ul);
        var place = readGridPlacement(targetLi, sectionKey);
        var colSel = targetLi.querySelector('.instance-column');
        var enabled = colSel ? parseInt(colSel.value, 10) >= 0 : true;
        var replacement = createInstanceRow(newUid(), newType, n, enabled, place.col, place.span, sectionKey);
        replacement.setAttribute('data-grid-row', targetLi.getAttribute('data-grid-row') || '');
        replacement.setAttribute('data-grid-stack', targetLi.getAttribute('data-grid-stack') || '');
        copyStandardStyleIfPossible(targetLi, replacement);
        ul.insertBefore(replacement, targetLi.nextSibling);
        targetLi.remove();
        wireInstanceSelects();
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
        ul.appendChild(createInstanceRow(newUid(), type, n, true, 0, 1, sec));
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
            var sectionKey = ul ? (ul.getAttribute('data-section') || '') : '';
            var clone = createInstanceRow(newUid(), type, n, en, en ? col : 0, en ? sp : 1, sectionKey);
            if (!en && sel) clone.querySelector('.instance-column').value = '-1';
            if (type === 'custom_text') {
                applyInstanceCustomText(clone, readInstanceCustomText(li));
            } else {
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
                // Espacios opcionales label/valor (paciente/médico) por instancia.
                ['.instance-pd-label-value-gap-px', '.instance-pd-space-above-px', '.instance-pd-space-below-px'].forEach(function(q) {
                    var src = li.querySelector(q);
                    var dst = clone.querySelector(q);
                    if (src && dst) dst.value = String(src.value || '0');
                });
            }
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
    [secRowsH, secRowsP, secRowsL, secRowsF].forEach(function(inp) {
        if (!inp) return;
        inp.addEventListener('change', function() {
            inp.value = String(clampRows(inp.value));
            rebuildAllPreviews();
        });
    });

    var pdfEditorInst = document.getElementById('pdf-editor-instances');
    if (pdfEditorInst) {
        pdfEditorInst.addEventListener('change', function(e) {
            var t = e.target;
            if (t && (t.classList.contains('pdf-sec-col-h') || t.classList.contains('pdf-sec-col-v'))) {
                rebuildAllPreviews();
            } else if (t && (t.closest('.pdf-text-style-controls') || t.closest('.custom-text-editor-root'))) {
                rebuildAllPreviews();
            }
        });
        pdfEditorInst.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('pdf-sec-line-height')) {
                rebuildAllPreviews();
            } else if (e.target && (e.target.closest('.pdf-text-style-controls') || e.target.closest('.custom-text-editor-root'))) {
                rebuildAllPreviews();
            }
        });
    }

    function clearGridDropHighlights() {
        document.querySelectorAll('.pdf-grid-cell-editor.is-drop-target, .pdf-grid-chip.is-drop-target').forEach(function(el) {
            el.classList.remove('is-drop-target');
        });
    }

    document.addEventListener('dragstart', function(ev) {
        var listInst = ev.target.closest('.pdf-instance-item');
        if (listInst) {
            var listSection = (listInst.parentElement && listInst.parentElement.getAttribute('data-section')) || '';
            var listUid = listInst.getAttribute('data-uid') || '';
            if (listUid && listSection) {
                var listPayload = {
                    kind: 'instance_copy',
                    uid: listUid,
                    section: listSection
                };
                activeDragPayload = listPayload;
                if (ev.dataTransfer) {
                    ev.dataTransfer.setData('text/plain', JSON.stringify(listPayload));
                    ev.dataTransfer.effectAllowed = 'copy';
                }
            }
            return;
        }
        var chip = ev.target.closest('.pdf-grid-chip');
        if (!chip) return;
        var payload = {
            kind: 'instance',
            uid: chip.getAttribute('data-uid') || '',
            section: chip.getAttribute('data-section') || ''
        };
        activeDragPayload = payload;
        ev.dataTransfer.setData('text/plain', JSON.stringify(payload));
        ev.dataTransfer.effectAllowed = 'move';
    });
    document.addEventListener('dragend', function() {
        activeDragPayload = null;
        clearGridDropHighlights();
    });

    document.addEventListener('dragover', function(ev) {
        var chip = ev.target.closest('.pdf-grid-chip');
        var cell = ev.target.closest('.pdf-grid-cell-editor');
        if (!chip && !cell) return;
        var payload = dragPayloadFromEventOrState(ev);
        if (!payload) return;
        ev.preventDefault();
        clearGridDropHighlights();
        (chip || cell).classList.add('is-drop-target');
        if (ev.dataTransfer) {
            var isCopy = (payload.kind === 'palette' || payload.kind === 'instance_copy');
            ev.dataTransfer.dropEffect = isCopy ? 'copy' : 'move';
        }
    });

    document.addEventListener('dragleave', function(ev) {
        var t = ev.target.closest('.pdf-grid-cell-editor, .pdf-grid-chip');
        if (t) t.classList.remove('is-drop-target');
    });

    document.addEventListener('drop', function(ev) {
        var chip = ev.target.closest('.pdf-grid-chip');
        var cell = ev.target.closest('.pdf-grid-cell-editor');
        if (!chip && !cell) return;
        var payload = dragPayloadFromEventOrState(ev);
        clearGridDropHighlights();
        if (!payload) return;
        ev.preventDefault();

        if (chip) {
            var targetUid = chip.getAttribute('data-uid') || '';
            var targetSection = chip.getAttribute('data-section') || '';
            var targetLi = findInstanceByUid(targetSection, targetUid);
            if (!targetLi) return;
            var targetPlacement = readGridPlacement(targetLi, targetSection);

            if (payload.kind === 'palette') {
                replaceTargetWithPalette(targetLi, targetSection, payload.element_type || '');
            } else if (payload.kind === 'instance_copy') {
                var copySrcLi = findInstanceByUid(payload.section || '', payload.uid || '');
                if (!copySrcLi) return;
                var targetUl = listForSection(targetSection);
                if (!targetUl) return;
                var targetCols = colsForList(targetUl);
                var targetRow = targetPlacement.row == null ? 0 : targetPlacement.row;
                var copyClone = cloneInstanceRowWithValues(copySrcLi, targetSection, targetCols, true, targetPlacement.col, targetPlacement.span);
                if (!copyClone) return;
                applyPlacementToLi(copyClone, targetSection, targetRow, targetPlacement.col, targetPlacement.span, targetPlacement.stack == null ? 0 : targetPlacement.stack);
                targetUl.insertBefore(copyClone, targetLi.nextSibling);
                targetLi.remove();
                wireInstanceSelects();
            } else if (payload.kind === 'instance') {
                var srcLi = findInstanceByUid(payload.section || '', payload.uid || '');
                if (!srcLi || srcLi === targetLi) return;
                if (!isTypeAllowedInSection(srcLi.getAttribute('data-element-type') || '', targetSection)) return;
                applyPlacementToLi(srcLi, targetSection, targetPlacement.row == null ? 0 : targetPlacement.row, targetPlacement.col, targetPlacement.span, targetPlacement.stack == null ? 0 : targetPlacement.stack);
                targetLi.remove();
                wireInstanceSelects();
            }
            rebuildAllPreviews();
            activeDragPayload = null;
            return;
        }

        if (cell) {
            var sectionKey = cell.getAttribute('data-drop-section') || '';
            var ul = listForSection(sectionKey);
            if (!ul) return;
            var rowIdx = parseInt(cell.getAttribute('data-drop-row') || '0', 10);
            var colIdx = parseInt(cell.getAttribute('data-drop-col') || '0', 10);
            var cellSpan = parseInt(cell.getAttribute('data-drop-span') || '1', 10);
            if (isNaN(rowIdx)) rowIdx = 0;
            if (isNaN(colIdx)) colIdx = 0;
            if (isNaN(cellSpan) || cellSpan < 1) cellSpan = 1;
            var stackCount = cell.querySelectorAll('.pdf-grid-chip').length;

            if (payload.kind === 'palette') {
                if (!isTypeAllowedInSection(payload.element_type || '', sectionKey)) return;
                var span = Math.max(1, Math.min(Math.max(1, colsForList(ul) - colIdx), payload.span || 1));
                if (cell.querySelectorAll('.pdf-grid-chip').length > 0) {
                    // región existente: usa el ancho de la región.
                    span = cellSpan;
                }
                var li = createInstanceRow(newUid(), payload.element_type || '', colsForList(ul), true, colIdx, span, sectionKey);
                applyPlacementToLi(li, sectionKey, rowIdx, colIdx, span, stackCount);
                ul.appendChild(li);
                wireInstanceSelects();
            } else if (payload.kind === 'instance_copy') {
                var copyList = listForSection(payload.section || '');
                var copySrc = copyList ? findInstanceByUid(payload.section || '', payload.uid || '') : null;
                if (!copySrc) return;
                if (!isTypeAllowedInSection(copySrc.getAttribute('data-element-type') || '', sectionKey)) return;
                var srcPlace = readGridPlacement(copySrc, payload.section || sectionKey);
                var spanCopy = srcPlace.span;
                if (cell.querySelectorAll('.pdf-grid-chip').length > 0) spanCopy = cellSpan;
                spanCopy = Math.max(1, Math.min(Math.max(1, colsForList(ul) - colIdx), spanCopy || 1));
                var cloneLi = cloneInstanceRowWithValues(copySrc, sectionKey, colsForList(ul), true, colIdx, spanCopy);
                if (!cloneLi) return;
                applyPlacementToLi(cloneLi, sectionKey, rowIdx, colIdx, spanCopy, stackCount);
                ul.appendChild(cloneLi);
                wireInstanceSelects();
            } else if (payload.kind === 'instance') {
                var srcList = listForSection(payload.section || '');
                var srcLi = srcList ? findInstanceByUid(payload.section || '', payload.uid || '') : null;
                if (!srcLi) return;
                if (!isTypeAllowedInSection(srcLi.getAttribute('data-element-type') || '', sectionKey)) return;
                var srcPlacement = readGridPlacement(srcLi, payload.section || sectionKey);
                var spanMove = srcPlacement.span;
                if (cell.querySelectorAll('.pdf-grid-chip').length > 0) spanMove = cellSpan;
                applyPlacementToLi(srcLi, sectionKey, rowIdx, colIdx, spanMove, stackCount);
                wireInstanceSelects();
            }
            rebuildAllPreviews();
            activeDragPayload = null;
        }
    });

    document.addEventListener('click', function(ev) {
        var addCellBtn = ev.target.closest('.pdf-grid-add-cell');
        if (addCellBtn) {
            var secAdd = addCellBtn.getAttribute('data-add-section') || '';
            var rowAdd = parseInt(addCellBtn.getAttribute('data-add-row') || '0', 10);
            var colAdd = parseInt(addCellBtn.getAttribute('data-add-col') || '0', 10);
            var spanAdd = parseInt(addCellBtn.getAttribute('data-add-span') || '1', 10);
            if (isNaN(rowAdd) || isNaN(colAdd) || isNaN(spanAdd)) return;
            addTypeToRegion(secAdd, rowAdd, colAdd, spanAdd, selectedTypeForSection(secAdd));
            rebuildAllPreviews();
            return;
        }

        var spanDec = ev.target.closest('.pdf-grid-chip-span-dec');
        var mergeCol = ev.target.closest('.pdf-grid-chip-merge-col');
        var mergeRow = ev.target.closest('.pdf-grid-chip-merge-row');
        if (spanDec || mergeCol || mergeRow) {
            var chipSpan = (spanDec || mergeCol || mergeRow).closest('.pdf-grid-chip');
            if (!chipSpan) return;
            var section = chipSpan.getAttribute('data-section') || '';
            var row = parseInt(chipSpan.getAttribute('data-row') || '0', 10);
            var col = parseInt(chipSpan.getAttribute('data-col') || '0', 10);
            var spanCur = parseInt(chipSpan.getAttribute('data-span') || '1', 10);
            if (isNaN(row) || isNaN(col) || isNaN(spanCur)) return;
            var ok = false;
            if (spanDec) {
                ok = applyRegionSpan(section, row, col, spanCur, spanCur - 1);
            } else if (mergeCol) {
                ok = applyRegionSpan(section, row, col, spanCur, spanCur + 1);
            } else if (mergeRow) {
                ok = mergeRegionWithNextRow(section, row, col, spanCur);
            }
            if (ok) {
                rebuildGridEditors();
                rebuildAllPreviews();
            }
            return;
        }
        var btn = ev.target.closest('.pdf-grid-chip-edit');
        if (!btn) return;
        var chip = btn.closest('.pdf-grid-chip');
        if (!chip) return;
        var li = findInstanceByUid(chip.getAttribute('data-section') || '', chip.getAttribute('data-uid') || '');
        if (li) li.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    document.addEventListener('change', function(e) {
        var t = e.target;
        if (t && t.id && (t.id.indexOf('ft_') === 0 || t.id.indexOf('hg_') === 0)) {
            rebuildAllPreviews();
        }
    });
    document.addEventListener('input', function(e) {
        var t = e.target;
        if (t && t.id && (t.id.indexOf('ft_') === 0 || t.id.indexOf('hg_') === 0)) {
            rebuildAllPreviews();
        }
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
    var sortGroupLab = { name: 'pdf-lab-firmas', pull: false, put: false };
    if (labFirmasList && typeof Sortable !== 'undefined') {
        new Sortable(labFirmasList, { animation: 150, handle: '.instance-drag-handle', group: sortGroupLab, onEnd: rebuildAllPreviews });
    }

    var addSecEl = document.getElementById('add_element_section');
    if (addSecEl) {
        addSecEl.addEventListener('change', syncAddElementTypeOptions);
    }
    syncAddElementTypeOptions();
    renderFieldPalette();
    if (toggleInstanceLists) {
        var applyListVisibility = function() {
            var show = !!toggleInstanceLists.checked;
            document.querySelectorAll('.pdf-instance-list-col').forEach(function(el) {
                el.style.display = show ? '' : 'none';
            });
        };
        toggleInstanceLists.addEventListener('change', applyListVisibility);
        applyListVisibility();
    }

    wireInstanceSelects();
    rebuildAllPreviews();

    var pdfEditorRoot = document.getElementById('pdf-editor-instances');
    if (pdfEditorRoot) {
        pdfEditorRoot.addEventListener('blur', function(ev) {
            var t = ev.target;
            if (t && t.classList && (t.classList.contains('instance-font-size') || t.classList.contains('instance-letter-spacing') || t.classList.contains('instance-line-height') || t.classList.contains('instance-pd-label-value-gap-px') || t.classList.contains('instance-pd-space-above-px') || t.classList.contains('instance-pd-space-below-px') || t.classList.contains('ct-lbl-font-size') || t.classList.contains('ct-val-font-size') || t.classList.contains('ct-lbl-letter-spacing') || t.classList.contains('ct-val-letter-spacing') || t.classList.contains('ct-lbl-line-height') || t.classList.contains('ct-val-line-height'))) {
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
        function pickLfText(id, fallback, allowEmpty) {
            var el = document.getElementById(id);
            var v = el ? String(el.value || '').trim() : '';
            if (v === '') {
                if (allowEmpty === true && el) return '';
                return fallback;
            }
            return v.length > 120 ? v.slice(0, 120) : v;
        }
        function pickChk(id, fallback) {
            var el = document.getElementById(id);
            if (!el) return fallback;
            return !!el.checked;
        }
        function pickLineModeSelect(id) {
            var el = document.getElementById(id);
            var v = el ? String(el.value || '').trim() : 'stacked';
            return v === 'inline' ? 'inline' : 'stacked';
        }
        function pickPaginationPos(id, fallback) {
            var el = document.getElementById(id);
            var v = el ? String(el.value || '').trim() : fallback;
            var allowed = ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'];
            return allowed.indexOf(v) >= 0 ? v : fallback;
        }
        function readSectionGridWrap(prefix, defBodyBg, defBodyText, defFontSize) {
            var fs = defFontSize !== undefined ? defFontSize : 9.5;
            return {
                body_bg_color: pickHex(prefix + '_body_bg', defBodyBg),
                body_text_color: pickHex(prefix + '_body_text', defBodyText),
                body_transparent: !!(document.getElementById(prefix + '_body_transparent') && document.getElementById(prefix + '_body_transparent').checked),
                font_family: pickAllowedDomId(prefix + '_font_family', 'font_families', 'DejaVu Sans'),
                font_size_pt: pickNum(prefix + '_font_size', 7, 20, fs),
                font_weight: pickAllowedDomId(prefix + '_font_weight', 'font_weights', 'normal'),
                font_style: pickAllowedDomId(prefix + '_font_style', 'font_styles', 'normal'),
                text_transform: pickAllowedDomId(prefix + '_text_transform', 'text_transforms', 'none'),
                line_height: pickNum(prefix + '_line_height', 1, 3, 1.35),
                column_border_width_px: Math.round(pickNum(prefix + '_column_border_width', 0, 4, 0)),
                column_border_color: pickHex(prefix + '_column_border_color', '#DDDDDD')
            };
        }
        var bg = pickHex('ch_bg_color', '#E9ECEF');
        var tx = pickHex('ch_text_color', '#212529');
        var pdGrid = readSectionGridWrap('pd', '#F8F9FA', '#333333', 9.5);
        var pdDefs = { paciente_nombre: 'Paciente:', paciente_genero: 'Género:', paciente_edad: 'Edad:', paciente_telefono: 'Teléfono:', diagnostico_presuntivo: 'Diagnóstico presuntivo:', medico: 'Médico:', fecha_recepcion: 'Fecha de recepción:', fecha_reporte: 'Fecha de reporte:', numero_orden: 'No. Orden:' };
        Object.keys(pdDefs).forEach(function(fid) {
            pdGrid['label_' + fid] = pickLfText('pd_label_' + fid, pdDefs[fid]);
            pdGrid['show_label_' + fid] = pickChk('pd_show_label_' + fid, true);
            pdGrid['label_' + fid + '_line_mode'] = pickLineModeSelect('pd_label_' + fid + '_line_mode');
            pdGrid['label_' + fid + '_text_color'] = pickHex('pd_lbl_' + fid + '_color', pdGrid.body_text_color);
            pdGrid['label_' + fid + '_font_size_pt'] = pickNum('pd_lbl_' + fid + '_fs', 7, 20, pdGrid.font_size_pt);
            pdGrid['label_' + fid + '_font_weight'] = pickAllowedDomId('pd_lbl_' + fid + '_fw', 'font_weights', pdGrid.font_weight);
            pdGrid['label_' + fid + '_font_style'] = pickAllowedDomId('pd_lbl_' + fid + '_fst', 'font_styles', pdGrid.font_style);
            pdGrid['label_' + fid + '_text_transform'] = pickAllowedDomId('pd_lbl_' + fid + '_tt', 'text_transforms', pdGrid.text_transform);
            pdGrid['label_' + fid + '_value_gap_px'] = pickNum('pd_label_' + fid + '_value_gap_px', 0, 40, 0);
            pdGrid['label_' + fid + '_space_above_px'] = pickNum('pd_label_' + fid + '_space_above_px', 0, 40, 0);
            pdGrid['label_' + fid + '_space_below_px'] = pickNum('pd_label_' + fid + '_space_below_px', 0, 40, 0);
        });
        var ftGrid = readSectionGridWrap('ft', '#FFFFFF', '#666666', 8);
        var ftBaseFs = pickNum('ft_font_size', 7, 20, 8);
        Object.assign(ftGrid, {
            label_footer_generated: pickLfText('ft_label_footer_generated', 'Resultados generados el'),
            show_label_footer_generated: pickChk('ft_show_label_footer_generated', true),
            label_footer_generated_line_mode: pickLineModeSelect('ft_label_footer_generated_line_mode'),
            footer_company_text_color: pickHex('ft_color_company', ftGrid.body_text_color),
            label_footer_generated_color: pickHex('ft_color_label_generated', ftGrid.body_text_color),
            label_footer_datetime_color: pickHex('ft_color_datetime', ftGrid.body_text_color),
            footer_policy_text_color: pickHex('ft_color_policy', ftGrid.body_text_color),
            section_top_border_enabled: pickChk('ft_section_top_border_enabled', true),
            section_top_border_width_px: Math.round(pickNum('ft_section_top_border_width', 0, 6, 1)),
            section_top_border_color: pickHex('ft_section_top_border_color', '#DDDDDD'),
            footer_company_font_size_pt: pickNum('ft_fs_company', 7, 20, ftBaseFs),
            footer_company_font_weight: pickAllowedDomId('ft_fw_company', 'font_weights', ftGrid.font_weight),
            footer_company_font_style: pickAllowedDomId('ft_fst_company', 'font_styles', ftGrid.font_style),
            footer_company_text_transform: pickAllowedDomId('ft_tt_company', 'text_transforms', ftGrid.text_transform),
            label_footer_generated_font_size_pt: pickNum('ft_fs_label_gen', 7, 20, ftBaseFs),
            label_footer_generated_font_weight: pickAllowedDomId('ft_fw_label_gen', 'font_weights', ftGrid.font_weight),
            label_footer_generated_font_style: pickAllowedDomId('ft_fst_label_gen', 'font_styles', ftGrid.font_style),
            label_footer_generated_text_transform: pickAllowedDomId('ft_tt_label_gen', 'text_transforms', ftGrid.text_transform),
            label_footer_datetime_font_size_pt: pickNum('ft_fs_datetime', 7, 20, ftBaseFs),
            label_footer_datetime_font_weight: pickAllowedDomId('ft_fw_datetime', 'font_weights', ftGrid.font_weight),
            label_footer_datetime_font_style: pickAllowedDomId('ft_fst_datetime', 'font_styles', ftGrid.font_style),
            label_footer_datetime_text_transform: pickAllowedDomId('ft_tt_datetime', 'text_transforms', ftGrid.text_transform),
            footer_policy_font_size_pt: pickNum('ft_fs_policy', 7, 20, ftBaseFs),
            footer_policy_font_weight: pickAllowedDomId('ft_fw_policy', 'font_weights', ftGrid.font_weight),
            footer_policy_font_style: pickAllowedDomId('ft_fst_policy', 'font_styles', ftGrid.font_style),
            footer_policy_text_transform: pickAllowedDomId('ft_tt_policy', 'text_transforms', ftGrid.text_transform)
        });
        var hgGrid = readSectionGridWrap('hg', '#FFFFFF', '#333333', 9.5);
        var hgBaseFs = pickNum('hg_font_size', 7, 20, 9.5);
        Object.assign(hgGrid, {
            label_qr_hint: pickLfText('hg_label_qr_hint', 'Escanee para ver sus resultados online'),
            show_label_qr_hint: pickChk('hg_show_label_qr_hint', true),
            label_qr_hint_line_mode: pickLineModeSelect('hg_label_qr_hint_line_mode'),
            label_qr_hint_text_color: pickHex('hg_qr_hint_color', hgGrid.body_text_color),
            label_qr_hint_font_size_pt: pickNum('hg_qr_hint_fs', 7, 20, hgBaseFs),
            label_qr_hint_font_weight: pickAllowedDomId('hg_qr_hint_fw', 'font_weights', hgGrid.font_weight),
            label_qr_hint_font_style: pickAllowedDomId('hg_qr_hint_fst', 'font_styles', hgGrid.font_style),
            label_qr_hint_text_transform: pickAllowedDomId('hg_qr_hint_tt', 'text_transforms', hgGrid.text_transform),
            qr_size_percent: Math.round(pickNum('hg_qr_size_percent', 50, 400, 100))
        });
        (window._headerLabelFieldIds || []).forEach(function(fid) {
            var defT = (window._headerLabelDefaults && Object.prototype.hasOwnProperty.call(window._headerLabelDefaults, fid)) ? window._headerLabelDefaults[fid] : '';
            hgGrid['label_' + fid] = pickLfText('hg_label_' + fid, defT, true);
            hgGrid['show_label_' + fid] = pickChk('hg_show_label_' + fid, true);
            hgGrid['label_' + fid + '_line_mode'] = pickLineModeSelect('hg_label_' + fid + '_line_mode');
            hgGrid['label_' + fid + '_text_color'] = pickHex('hg_hdr_' + fid + '_color', hgGrid.body_text_color);
            hgGrid['label_' + fid + '_font_size_pt'] = pickNum('hg_hdr_' + fid + '_fs', 7, 20, hgBaseFs);
            hgGrid['label_' + fid + '_font_weight'] = pickAllowedDomId('hg_hdr_' + fid + '_fw', 'font_weights', hgGrid.font_weight);
            hgGrid['label_' + fid + '_font_style'] = pickAllowedDomId('hg_hdr_' + fid + '_fst', 'font_styles', hgGrid.font_style);
            hgGrid['label_' + fid + '_text_transform'] = pickAllowedDomId('hg_hdr_' + fid + '_tt', 'text_transforms', hgGrid.text_transform);
        });
        return {
            card_header: {
                bg_color: bg,
                text_color: tx,
                bg_transparent: !!(document.getElementById('ch_bg_transparent') && document.getElementById('ch_bg_transparent').checked),
                font_family: pickAllowedDomId('ch_font_family', 'font_families', 'DejaVu Sans'),
                font_size_pt: pickNum('ch_font_size', 7, 20, 10),
                font_weight: pickAllowedDomId('ch_font_weight', 'font_weights', '700'),
                font_style: pickAllowedDomId('ch_font_style', 'font_styles', 'normal'),
                text_transform: pickAllowedDomId('ch_text_transform', 'text_transforms', 'uppercase')
            },
            header_section: {
                separator_color: pickHex('hs_separator_color', '#0066CC')
            },
            header_grid: hgGrid,
            patient_doctor_grid: pdGrid,
            footer_grid: ftGrid,
            print_pagination: {
                enabled: pickChk('pp_enabled', false),
                label_text: pickLfText('pp_label_text', 'Página'),
                label_position: pickPaginationPos('pp_label_position', 'bottom-left'),
                value_position: pickPaginationPos('pp_value_position', 'bottom-right')
            },
            grupo_prueba_page_break: {
                mode: (function() {
                    var el = document.getElementById('gpb_mode');
                    var v = el ? el.value : 'keep_together_compact';
                    return (v === 'flow' || v === 'keep_segment' || v === 'keep_together_if_fits' || v === 'keep_together' || v === 'keep_together_compact') ? v : 'keep_together_if_fits';
                })(),
                repeat_header_on_split: pickChk('gpb_repeat_header', true),
                compact_min_scale_percent: Math.round(pickNum('gpb_compact_min_scale', 75, 100, 85)),
                compact_cell_padding_px: Math.round(pickNum('gpb_compact_cell_padding', 0, 20, 0)),
                compact_aggressive: pickChk('gpb_compact_aggressive', false),
                min_remaining_mm_to_force_break: Math.round(pickNum('gpb_min_remaining_mm', 0, 120, 0) * 10) / 10
            },
            notes: {
                title_bg_color: pickHex('ns_title_bg', '#FFF3CD'),
                title_text_color: pickHex('ns_title_text', '#664D03'),
                title_transparent: !!(document.getElementById('ns_title_transparent') && document.getElementById('ns_title_transparent').checked),
                body_bg_color: pickHex('ns_body_bg', '#FFFFFF'),
                body_text_color: pickHex('ns_body_text', '#333333'),
                body_transparent: !!(document.getElementById('ns_body_transparent') && document.getElementById('ns_body_transparent').checked),
                column_border_width_px: Math.round(pickNum('ns_column_border_width', 0, 4, 1)),
                column_border_color: pickHex('ns_column_border_color', '#DDDDDD'),
                section_title: pickLfText('ns_section_title', 'NOTAS'),
                show_section_title: pickChk('ns_show_section_title', true),
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
                body_transparent: !!(document.getElementById('lf_body_transparent') && document.getElementById('lf_body_transparent').checked),
                column_border_width_px: Math.round(pickNum('lf_column_border_width', 0, 4, 1)),
                column_border_color: pickHex('lf_column_border_color', '#DDDDDD'),
                font_family: pickAllowedDomId('lf_font_family', 'font_families', 'DejaVu Sans'),
                font_size_pt: pickNum('lf_font_size', 7, 20, 9.5),
                font_weight: pickAllowedDomId('lf_font_weight', 'font_weights', 'normal'),
                font_style: pickAllowedDomId('lf_font_style', 'font_styles', 'normal'),
                text_transform: pickAllowedDomId('lf_text_transform', 'text_transforms', 'none'),
                line_height: pickNum('lf_line_height', 1, 3, 1.4),
                section_title: pickLfText('lf_section_title', 'VALIDACIÓN Y APROBACIÓN'),
                show_section_title: pickChk('lf_show_section_title', true),
                label_validator: pickLfText('lf_label_validator', 'Verificado por:'),
                show_label_validator: pickChk('lf_show_label_validator', true),
                validator_line_mode: pickLineModeSelect('lf_validator_line_mode'),
                label_seal: pickLfText('lf_label_seal', ''),
                show_label_seal: pickChk('lf_show_label_seal', true),
                label_seal_line_mode: pickLineModeSelect('lf_label_seal_line_mode'),
                label_firma: pickLfText('lf_label_firma', 'ATENTAMENTE'),
                show_label_firma: pickChk('lf_show_label_firma', true),
                label_firma_line_mode: pickLineModeSelect('lf_label_firma_line_mode'),
                label_approver: pickLfText('lf_label_approver', ''),
                show_label_approver: pickChk('lf_show_label_approver', true),
                label_approver_line_mode: pickLineModeSelect('lf_label_approver_line_mode'),
                label_cargo: pickLfText('lf_label_cargo', ''),
                show_label_cargo: pickChk('lf_show_label_cargo', true),
                label_cargo_line_mode: pickLineModeSelect('lf_label_cargo_line_mode'),
                label_matricula: pickLfText('lf_label_matricula', 'Matrícula:', true),
                show_label_matricula: pickChk('lf_show_label_matricula', true),
                label_matricula_line_mode: pickLineModeSelect('lf_label_matricula_line_mode'),
                placement: (function() {
                    var el = document.getElementById('lf_placement');
                    var v = el ? el.value : 'per_group';
                    return (v === 'block_end' || v === 'both') ? v : 'per_group';
                })(),
                show_area_heading: pickChk('lf_show_area_heading', false),
                area_heading_color: pickHex('lf_area_heading_color', '#664D03'),
                area_heading_font_size_pt: pickNum('lf_area_heading_font_size', 7, 16, 9),
                area_heading_font_weight: pickAllowedDomId('lf_area_heading_font_weight', 'font_weights', '600'),
                area_heading_text_transform: pickAllowedDomId('lf_area_heading_text_transform', 'text_transforms', 'uppercase'),
                inline_margin_top_pt: pickNum('lf_inline_margin_top', 0, 24, 8),
                inline_margin_bottom_pt: pickNum('lf_inline_margin_bottom', 0, 24, 6),
                seal_max_height_px: Math.round(pickNum('lf_seal_max_height', 40, 200, 110)),
                signature_max_height_px: Math.round(pickNum('lf_signature_max_height', 30, 160, 72)),
                signature_max_width_px: Math.round(pickNum('lf_signature_max_width', 80, 400, 220))
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
                line_height: pickNum('rs_line_height', 1, 3, 1.35),
                cell_padding_v_px: Math.round(pickNum('rs_cell_padding_v', 0, 20, 6)),
                grupo_prueba_gap_px: Math.round(pickNum('rs_grupo_prueba_gap', 0, 80, 10)),
                subgrupo_prueba_gap_px: Math.round(pickNum('rs_subgrupo_prueba_gap', 0, 80, 18)),
                matrix_text_align: pickAllowedDomId('rs_matrix_align', 'text_aligns', 'center'),
                matrix_vertical_align: pickAllowedDomId('rs_matrix_valign', 'vertical_aligns', 'middle'),
                matrix_text_color: pickHex('rs_matrix_text_color', '#333333'),
                matrix_font_size_pt: pickNum('rs_matrix_font_size', 7, 20, 8),
                matrix_font_weight: pickAllowedDomId('rs_matrix_font_weight', 'font_weights', 'normal'),
                matrix_font_style: pickAllowedDomId('rs_matrix_font_style', 'font_styles', 'normal'),
                matrix_text_transform: pickAllowedDomId('rs_matrix_text_transform', 'text_transforms', 'none'),
                matrix_header_text_color: pickHex('rs_matrix_header_text_color', '#1F2937'),
                matrix_header_font_family: pickAllowedDomId('rs_matrix_header_font_family', 'font_families', 'DejaVu Sans'),
                matrix_header_font_size_pt: pickNum('rs_matrix_header_font_size', 7, 20, 8),
                matrix_header_font_weight: pickAllowedDomId('rs_matrix_header_font_weight', 'font_weights', 'bold'),
                matrix_header_font_style: pickAllowedDomId('rs_matrix_header_font_style', 'font_styles', 'normal'),
                matrix_header_text_transform: pickAllowedDomId('rs_matrix_header_text_transform', 'text_transforms', 'uppercase'),
                matrix_col_population_align: pickAllowedDomId('rs_matrix_col_population_align', 'text_aligns', 'left'),
                matrix_col_parameter_align: pickAllowedDomId('rs_matrix_col_parameter_align', 'text_aligns', 'left'),
                matrix_col_sex_align: pickAllowedDomId('rs_matrix_col_sex_align', 'text_aligns', 'center'),
                matrix_col_reference_align: pickAllowedDomId('rs_matrix_col_reference_align', 'text_aligns', 'center'),
                matrix_hdr_population_align: pickAllowedDomId('rs_matrix_hdr_population_align', 'text_aligns', 'left'),
                matrix_hdr_parameter_align: pickAllowedDomId('rs_matrix_hdr_parameter_align', 'text_aligns', 'left'),
                matrix_hdr_sex_align: pickAllowedDomId('rs_matrix_hdr_sex_align', 'text_aligns', 'center'),
                matrix_hdr_reference_align: pickAllowedDomId('rs_matrix_hdr_reference_align', 'text_aligns', 'center'),
                grupo_cabecera_title_mode: pickAllowedDomId('rs_grupo_cabecera_title_mode', 'grupo_cabecera_title_modes', 'grupo_analisis'),
                grupo_cabecera_show_tipo_muestra: !!(document.getElementById('rs_grupo_cabecera_show_tipo_muestra') && document.getElementById('rs_grupo_cabecera_show_tipo_muestra').checked),
                grupo_cabecera_show_metodo: !!(document.getElementById('rs_grupo_cabecera_show_metodo') && document.getElementById('rs_grupo_cabecera_show_metodo').checked)
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
        var ta = pdfAllow('text_aligns');
        var va = pdfAllow('vertical_aligns');
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
        ['ns_title_bg', 'ns_title_text', 'ns_body_bg', 'ns_body_text', 'ns_column_border_color'].forEach(function(id) {
            pushIfBadHex(id, 'Color inválido en notas del resultado.');
        });
        pushIfBadNum('ns_column_border_width', 0, 4, 'Borde en notas: entre 0 y 4 px.');
        pushIfBadSelect('ns_font_family', ff, 'Fuente no permitida en notas.');
        pushIfBadNum('ns_font_size', 7, 20, 'Tamaño en notas: entre 7 y 20 pt.');
        pushIfBadSelect('ns_font_weight', fw, 'Grosor no permitido en notas.');
        pushIfBadSelect('ns_font_style', fst, 'Estilo no permitido en notas.');
        pushIfBadSelect('ns_text_transform', tt, 'Transformación no permitida en notas.');
        pushIfBadNum('ns_line_height', 1, 3, 'Interlineado en notas: entre 1 y 3.');
        ['hg_body_bg', 'hg_body_text', 'hg_column_border_color', 'pd_body_bg', 'pd_body_text', 'pd_column_border_color', 'ft_body_bg', 'ft_body_text', 'ft_column_border_color', 'ft_color_company', 'ft_color_label_generated', 'ft_color_datetime', 'ft_color_policy', 'ft_section_top_border_color'].forEach(function(id) {
            pushIfBadHex(id, 'Color inválido en cuadrícula PDF (' + id + ').');
        });
        pushIfBadNum('hg_column_border_width', 0, 4, 'Borde columnas (encabezado): entre 0 y 4 px.');
        pushIfBadNum('pd_column_border_width', 0, 4, 'Borde columnas (paciente/médico): entre 0 y 4 px.');
        pushIfBadNum('ft_column_border_width', 0, 4, 'Borde columnas (pie): entre 0 y 4 px.');
        pushIfBadNum('ft_section_top_border_width', 0, 6, 'Borde superior del pie: entre 0 y 6 px.');
        ['ft_fs_company', 'ft_fs_label_gen', 'ft_fs_datetime', 'ft_fs_policy'].forEach(function(id) {
            pushIfBadNum(id, 7, 20, 'Tamaño de fuente en pie (tabla por texto): entre 7 y 20 pt.');
        });
        ['ft_fw_company', 'ft_fw_label_gen', 'ft_fw_datetime', 'ft_fw_policy'].forEach(function(id) {
            pushIfBadSelect(id, fw, 'Grosor no permitido en pie (tabla por texto).');
        });
        ['ft_fst_company', 'ft_fst_label_gen', 'ft_fst_datetime', 'ft_fst_policy'].forEach(function(id) {
            pushIfBadSelect(id, fst, 'Estilo no permitido en pie (tabla por texto).');
        });
        pushIfBadSelect('hg_font_family', ff, 'Fuente no permitida (cuadrícula encabezado).');
        pushIfBadNum('hg_font_size', 7, 20, 'Tamaño (cuadrícula encabezado): entre 7 y 20 pt.');
        pushIfBadSelect('hg_font_weight', fw, 'Grosor no permitido (cuadrícula encabezado).');
        pushIfBadSelect('hg_font_style', fst, 'Estilo no permitido (cuadrícula encabezado).');
        pushIfBadSelect('hg_text_transform', tt, 'Transformación no permitida (cuadrícula encabezado).');
        pushIfBadNum('hg_line_height', 1, 3, 'Interlineado (cuadrícula encabezado): entre 1 y 3.');
        pushIfBadNum('hg_qr_size_percent', 50, 400, 'Tamaño del QR (encabezado): entre 50 y 400 %.');
        (window._headerLabelFieldIds || []).forEach(function(fid) {
            pushIfBadHex('hg_hdr_' + fid + '_color', 'Color inválido en etiqueta del encabezado (' + fid + ').');
            pushIfBadNum('hg_hdr_' + fid + '_fs', 7, 20, 'Tamaño de etiqueta en encabezado: entre 7 y 20 pt.');
            pushIfBadSelect('hg_hdr_' + fid + '_fw', fw, 'Grosor no permitido en etiqueta del encabezado.');
            pushIfBadSelect('hg_hdr_' + fid + '_fst', fst, 'Estilo no permitido en etiqueta del encabezado.');
        });
        pushIfBadHex('hg_qr_hint_color', 'Color inválido en leyenda del QR.');
        pushIfBadNum('hg_qr_hint_fs', 7, 20, 'Tamaño leyenda QR: entre 7 y 20 pt.');
        pushIfBadSelect('hg_qr_hint_fw', fw, 'Grosor no permitido en leyenda del QR.');
        pushIfBadSelect('hg_qr_hint_fst', fst, 'Estilo no permitido en leyenda del QR.');
        pushIfBadSelect('pd_font_family', ff, 'Fuente no permitida (cuadrícula paciente/médico).');
        pushIfBadNum('pd_font_size', 7, 20, 'Tamaño (cuadrícula paciente/médico): entre 7 y 20 pt.');
        pushIfBadSelect('pd_font_weight', fw, 'Grosor no permitido (cuadrícula paciente/médico).');
        pushIfBadSelect('pd_font_style', fst, 'Estilo no permitido (cuadrícula paciente/médico).');
        pushIfBadSelect('pd_text_transform', tt, 'Transformación no permitida (cuadrícula paciente/médico).');
        pushIfBadNum('pd_line_height', 1, 3, 'Interlineado (cuadrícula paciente/médico): entre 1 y 3.');
        pushIfBadSelect('ft_font_family', ff, 'Fuente no permitida (cuadrícula pie).');
        pushIfBadNum('ft_font_size', 7, 20, 'Tamaño (cuadrícula pie): entre 7 y 20 pt.');
        pushIfBadSelect('ft_font_weight', fw, 'Grosor no permitido (cuadrícula pie).');
        pushIfBadSelect('ft_font_style', fst, 'Estilo no permitido (cuadrícula pie).');
        pushIfBadSelect('ft_text_transform', tt, 'Transformación no permitida (cuadrícula pie).');
        pushIfBadNum('ft_line_height', 1, 3, 'Interlineado (cuadrícula pie): entre 1 y 3.');
        ['hg_label_qr_hint', 'ft_label_footer_generated'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (String(el.value || '').length > 120) errs.push('Texto demasiado largo en «' + id + '» (máx. 120).');
        });
        var ppLbl = document.getElementById('pp_label_text');
        if (ppLbl && String(ppLbl.value || '').length > 60) errs.push('Texto de etiqueta de paginación: máx. 60 caracteres.');
        ['pp_label_position', 'pp_value_position'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            var v = String(el.value || '').trim();
            if (['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'].indexOf(v) < 0) {
                errs.push('Posición inválida en paginación (' + id + ').');
            }
        });
        var gpbModeEl = document.getElementById('gpb_mode');
        if (gpbModeEl) {
            var gpbMode = String(gpbModeEl.value || '').trim();
            if (['flow', 'keep_segment', 'keep_together_if_fits', 'keep_together', 'keep_together_compact'].indexOf(gpbMode) < 0) {
                errs.push('Modo de salto de página en grupos de prueba no válido.');
            }
        }
        pushIfBadNum('gpb_min_remaining_mm', 0, 120, 'Umbral de espacio restante: entre 0 y 120 mm.');
        pushIfBadNum('gpb_compact_min_scale', 75, 100, 'Escala mínima de compactación: entre 75 y 100 %.');
        pushIfBadNum('gpb_compact_cell_padding', 0, 20, 'Relleno de filas al compactar: entre 0 y 20 px.');
        (window._headerLabelFieldIds || []).forEach(function(fid) {
            var el = document.getElementById('hg_label_' + fid);
            if (el && String(el.value || '').length > 120) errs.push('Etiqueta demasiado larga en encabezado: ' + fid + '.');
        });
        ['paciente_nombre', 'paciente_genero', 'paciente_edad', 'paciente_telefono', 'diagnostico_presuntivo', 'medico', 'fecha_recepcion', 'fecha_reporte', 'numero_orden'].forEach(function(fid) {
            var el = document.getElementById('pd_label_' + fid);
            if (el && String(el.value || '').length > 120) errs.push('Etiqueta demasiado larga en paciente/médico: ' + fid + '.');
        });
        ['paciente_nombre', 'paciente_genero', 'paciente_edad', 'paciente_telefono', 'diagnostico_presuntivo', 'medico', 'fecha_recepcion', 'fecha_reporte', 'numero_orden'].forEach(function(fid) {
            pushIfBadNum('pd_label_' + fid + '_value_gap_px', 0, 40, 'Separación entre etiqueta y valor (px): entre 0 y 40.');
            pushIfBadNum('pd_label_' + fid + '_space_above_px', 0, 40, 'Espacio arriba (px): entre 0 y 40.');
            pushIfBadNum('pd_label_' + fid + '_space_below_px', 0, 40, 'Espacio abajo (px): entre 0 y 40.');
        });
        var nsSec = document.getElementById('ns_section_title');
        if (nsSec && String(nsSec.value || '').length > 120) errs.push('Título de notas: máx. 120 caracteres.');
        ['lf_title_bg', 'lf_title_text', 'lf_body_bg', 'lf_body_text', 'lf_column_border_color'].forEach(function(id) {
            pushIfBadHex(id, 'Color inválido en firmas.');
        });
        pushIfBadSelect('lf_font_family', ff, 'Fuente no permitida en firmas.');
        pushIfBadNum('lf_font_size', 7, 20, 'Tamaño en firmas: entre 7 y 20 pt.');
        pushIfBadSelect('lf_font_weight', fw, 'Grosor no permitido en firmas.');
        pushIfBadSelect('lf_font_style', fst, 'Estilo no permitido en firmas.');
        pushIfBadSelect('lf_text_transform', tt, 'Transformación no permitida en firmas.');
        pushIfBadNum('lf_line_height', 1, 3, 'Interlineado en firmas: entre 1 y 3.');
        pushIfBadNum('lf_column_border_width', 0, 4, 'Borde entre columnas en firmas: entre 0 y 4 px.');
        pushIfBadHex('lf_area_heading_color', 'Color inválido en título de área.');
        pushIfBadNum('lf_area_heading_font_size', 7, 16, 'Tamaño del título de área: entre 7 y 16 pt.');
        pushIfBadSelect('lf_area_heading_font_weight', fw, 'Grosor no permitido en título de área.');
        pushIfBadSelect('lf_area_heading_text_transform', tt, 'Transformación no permitida en título de área.');
        pushIfBadNum('lf_inline_margin_top', 0, 24, 'Margen superior de firmas por área: entre 0 y 24 pt.');
        pushIfBadNum('lf_inline_margin_bottom', 0, 24, 'Margen inferior de firmas por área: entre 0 y 24 pt.');
        pushIfBadNum('lf_seal_max_height', 40, 200, 'Alto del sello: entre 40 y 200 px.');
        pushIfBadNum('lf_signature_max_height', 30, 160, 'Alto de la firma: entre 30 y 160 px.');
        pushIfBadNum('lf_signature_max_width', 80, 400, 'Ancho de la firma: entre 80 y 400 px.');
        ['lf_section_title', 'lf_label_validator', 'lf_label_seal', 'lf_label_firma', 'lf_label_approver', 'lf_label_cargo', 'lf_label_matricula'].forEach(function(id) {
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
        pushIfBadNum('rs_cell_padding_v', 0, 20, 'Relleno vertical de filas (tabla de resultados): entre 0 y 20 px.');
        pushIfBadNum('rs_grupo_prueba_gap', 0, 80, 'Espacio entre grupos de prueba: entre 0 y 80 px.');
        pushIfBadNum('rs_subgrupo_prueba_gap', 0, 80, 'Espacio entre pruebas del mismo área: entre 0 y 80 px.');
        pushIfBadNum('rs_segment_border_width', 0, 4, 'Grosor de borde de segmento: entre 0 y 4 px.');
        pushIfBadSelect('rs_segment_shadow', pdfAllow('segment_shadows'), 'Sombra de segmento no permitida.');
        pushIfBadSelect('rs_matrix_align', ta, 'Alineación horizontal no permitida en matriz de referencia.');
        pushIfBadSelect('rs_matrix_valign', va, 'Alineación vertical no permitida en matriz de referencia.');
        pushIfBadHex('rs_matrix_text_color', 'Color inválido en matriz de referencia.');
        pushIfBadNum('rs_matrix_font_size', 7, 20, 'Tamaño en matriz de referencia: entre 7 y 20 pt.');
        pushIfBadSelect('rs_matrix_font_weight', fw, 'Grosor no permitido en matriz de referencia.');
        pushIfBadSelect('rs_matrix_font_style', fst, 'Estilo no permitido en matriz de referencia.');
        pushIfBadSelect('rs_matrix_text_transform', tt, 'Transformación no permitida en matriz de referencia.');
        pushIfBadHex('rs_matrix_header_text_color', 'Color inválido en headers de matriz de referencia.');
        pushIfBadSelect('rs_matrix_header_font_family', ff, 'Fuente no permitida en headers de matriz de referencia.');
        pushIfBadNum('rs_matrix_header_font_size', 7, 20, 'Tamaño en headers de matriz de referencia: entre 7 y 20 pt.');
        pushIfBadSelect('rs_matrix_header_font_weight', fw, 'Grosor no permitido en headers de matriz de referencia.');
        pushIfBadSelect('rs_matrix_header_font_style', fst, 'Estilo no permitido en headers de matriz de referencia.');
        pushIfBadSelect('rs_matrix_header_text_transform', tt, 'Transformación no permitida en headers de matriz de referencia.');
        [
            'rs_matrix_col_population_align',
            'rs_matrix_col_parameter_align',
            'rs_matrix_col_sex_align',
            'rs_matrix_col_reference_align',
            'rs_matrix_hdr_population_align',
            'rs_matrix_hdr_parameter_align',
            'rs_matrix_hdr_sex_align',
            'rs_matrix_hdr_reference_align'
        ].forEach(function(id) {
            pushIfBadSelect(id, ta, 'Alineación no permitida en columnas de matriz de referencia (' + id + ').');
        });
        pushIfBadSelect('rs_grupo_cabecera_title_mode', pdfAllow('grupo_cabecera_title_modes'), 'Formato de título de cabecera de grupo no permitido.');

        function pushCtScopeErrors(scope, pfx, partLabel, itemLabel, errs) {
            if (!scope) return;
            var fam = scope.querySelector('.' + pfx + '-font-family');
            if (fam && ff.indexOf(String(fam.value || '').trim()) < 0) {
                errs.push('«' + itemLabel + '» (' + partLabel + '): fuente no permitida.');
            }
            var fs = scope.querySelector('.' + pfx + '-font-size');
            if (fs) {
                var v = parseFloat(fs.value);
                if (isNaN(v) || v < 6 || v > 24) errs.push('«' + itemLabel + '» (' + partLabel + '): tamaño entre 6 y 24 pt.');
            }
            var fwEl = scope.querySelector('.' + pfx + '-font-weight');
            if (fwEl && fw.indexOf(String(fwEl.value || '').trim()) < 0) errs.push('«' + itemLabel + '» (' + partLabel + '): grosor no permitido.');
            var fstEl = scope.querySelector('.' + pfx + '-font-style');
            if (fstEl && fst.indexOf(String(fstEl.value || '').trim()) < 0) errs.push('«' + itemLabel + '» (' + partLabel + '): estilo no permitido.');
            var ttEl = scope.querySelector('.' + pfx + '-text-transform');
            if (ttEl && tt.indexOf(String(ttEl.value || '').trim()) < 0) errs.push('«' + itemLabel + '» (' + partLabel + '): transformación no permitida.');
            var col = scope.querySelector('.' + pfx + '-font-color');
            if (col && !isValidPdfHexJs(String(col.value || '').trim())) errs.push('«' + itemLabel + '» (' + partLabel + '): color inválido (#RRGGBB).');
            var ls = scope.querySelector('.' + pfx + '-letter-spacing');
            if (ls) {
                var lsV = parseFloat(ls.value);
                if (isNaN(lsV) || lsV < -0.2 || lsV > 1) errs.push('«' + itemLabel + '» (' + partLabel + '): espaciado entre letras entre -0,2 y 1 em.');
            }
            var lh = scope.querySelector('.' + pfx + '-line-height');
            if (lh) {
                var lhV = parseFloat(lh.value);
                if (isNaN(lhV) || lhV < 1 || lhV > 3) errs.push('«' + itemLabel + '» (' + partLabel + '): interlineado entre 1 y 3.');
            }
            var shEl = scope.querySelector('.' + pfx + '-text-shadow');
            if (shEl && sh.indexOf(String(shEl.value || '').trim()) < 0) errs.push('«' + itemLabel + '» (' + partLabel + '): sombra no permitida.');
        }
        document.querySelectorAll('.pdf-instance-item').forEach(function(li) {
            var label = (li.querySelector('.pdf-instance-head strong') && li.querySelector('.pdf-instance-head strong').textContent.trim()) || 'Elemento';
            var et = li.getAttribute('data-element-type') || '';
            if (et === 'custom_text') {
                var lblIn = li.querySelector('.custom-text-label-input');
                var valIn = li.querySelector('.custom-text-value-input');
                if (lblIn && String(lblIn.value || '').length > 200) errs.push('«' + label + '»: etiqueta demasiado larga (máx. 200).');
                if (valIn && String(valIn.value || '').length > 500) errs.push('«' + label + '»: valor demasiado largo (máx. 500).');
                pushCtScopeErrors(li.querySelector('.custom-text-style-label'), 'ct-lbl', 'etiqueta', label, errs);
                pushCtScopeErrors(li.querySelector('.custom-text-style-value'), 'ct-val', 'valor', label, errs);
                return;
            }
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
            // Espaciado label/valor por instancia (paciente/médico).
            var gEl = li.querySelector('.instance-pd-label-value-gap-px');
            if (gEl) {
                var gV = parseInt(gEl.value, 10);
                if (isNaN(gV) || gV < 0 || gV > 40) errs.push('«' + label + '»: Separación label/valor (px): entre 0 y 40.');
            }
            var aEl = li.querySelector('.instance-pd-space-above-px');
            if (aEl) {
                var aV = parseInt(aEl.value, 10);
                if (isNaN(aV) || aV < 0 || aV > 40) errs.push('«' + label + '»: Espacio arriba (px): entre 0 y 40.');
            }
            var bEl = li.querySelector('.instance-pd-space-below-px');
            if (bEl) {
                var bV = parseInt(bEl.value, 10);
                if (isNaN(bV) || bV < 0 || bV > 40) errs.push('«' + label + '»: Espacio abajo (px): entre 0 y 40.');
            }
        });
        return errs;
    }

    function clampInstanceStyleField(el) {
        if (!el || !el.classList) return;
        if (el.classList.contains('instance-font-size') || el.classList.contains('ct-lbl-font-size') || el.classList.contains('ct-val-font-size')) {
            var v = parseFloat(el.value);
            if (isNaN(v)) v = 10;
            el.value = String(Math.max(6, Math.min(24, Math.round(v * 2) / 2)));
        } else if (el.classList.contains('instance-letter-spacing') || el.classList.contains('ct-lbl-letter-spacing') || el.classList.contains('ct-val-letter-spacing')) {
            var a = parseFloat(el.value);
            if (isNaN(a)) a = 0;
            el.value = String(Math.round(Math.max(-0.2, Math.min(1, a)) * 100) / 100);
        } else if (el.classList.contains('instance-line-height') || el.classList.contains('ct-lbl-line-height') || el.classList.contains('ct-val-line-height')) {
            var b = parseFloat(el.value);
            if (isNaN(b)) b = 1.35;
            el.value = String(Math.round(Math.max(1, Math.min(3, b)) * 100) / 100);
        } else if (
            el.classList.contains('instance-pd-label-value-gap-px') ||
            el.classList.contains('instance-pd-space-above-px') ||
            el.classList.contains('instance-pd-space-below-px')
        ) {
            var n = parseInt(el.value, 10);
            if (isNaN(n)) n = 0;
            el.value = String(Math.max(0, Math.min(40, n)));
        }
    }

    function parseInstanceLi(li, section) {
        var pdTypes = ['paciente_nombre', 'paciente_genero', 'paciente_edad', 'paciente_telefono', 'diagnostico_presuntivo', 'medico', 'fecha_recepcion', 'fecha_reporte', 'numero_orden'];
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
        var elType = li.getAttribute('data-element-type') || '';
        var base = {
            uid: li.getAttribute('data-uid') || '',
            element_type: elType,
            section: section,
            enabled: enabled,
            column: col,
            column_span: span
        };
        var gridRowRaw = li.getAttribute('data-grid-row');
        var gridStackRaw = li.getAttribute('data-grid-stack');
        if (gridRowRaw !== null && String(gridRowRaw).trim() !== '') {
            base.grid_row = Math.max(0, parseInt(gridRowRaw, 10) || 0);
        }
        if (gridStackRaw !== null && String(gridStackRaw).trim() !== '') {
            base.grid_stack = Math.max(0, parseInt(gridStackRaw, 10) || 0);
        }
        if (elType === 'custom_text') {
            base.text_style = defaultInstanceTextStyleJson();
            base.custom_text = readInstanceCustomText(li);
        } else {
            base.text_style = readInstanceTextStyle(li);
        }
        if (section === 'patient_doctor' && pdTypes.indexOf(elType) >= 0) {
            function readInt(sel, fallback) {
                var el = li.querySelector(sel);
                var n = el ? parseInt(el.value, 10) : NaN;
                if (isNaN(n)) n = fallback;
                return Math.max(0, Math.min(40, n));
            }
            base.label_value_gap_px = readInt('.instance-pd-label-value-gap-px', 0);
            base.label_space_above_px = readInt('.instance-pd-space-above-px', 0);
            base.label_space_below_px = readInt('.instance-pd-space-below-px', 0);
        }
        return base;
    }

    document.getElementById('pdf_tpl_form').addEventListener('submit', function(ev) {
        var styleErrs = validatePdfEditorStylesBeforeSave();
        var gridErrs = validateGridConflictsBeforeSave();
        if (gridErrs.length > 0) {
            styleErrs = styleErrs.concat(gridErrs);
        }
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
