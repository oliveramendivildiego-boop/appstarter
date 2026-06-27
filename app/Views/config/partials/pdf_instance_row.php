<?php
/** @var array $inst */
/** @var int $col_count */
/** @var array<string, string> $elLabels */
/** @var array<string, mixed> $pd_grid */
$uid    = (string) ($inst['uid'] ?? '');
$type   = (string) ($inst['element_type'] ?? '');
$label  = $elLabels[$type] ?? $type;
$en     = ! empty($inst['enabled']);
$col    = isset($inst['column']) ? (int) $inst['column'] : 0;
$cc     = max(1, min(6, $col_count));
$col    = max(0, min($cc - 1, $col));
$selVal = $en ? (string) $col : '-1';
$maxSpan = max(1, $cc - $col);
$spanIn  = isset($inst['column_span']) ? (int) $inst['column_span'] : 1;
$spanIn  = max(1, min($maxSpan, $spanIn));
$ts      = is_array($inst['text_style'] ?? null) ? $inst['text_style'] : [];
$ff      = (string) ($ts['font_family'] ?? 'DejaVu Sans');
$fs      = isset($ts['font_size_pt']) ? (float) $ts['font_size_pt'] : 10.0;
$fw      = (string) ($ts['font_weight'] ?? 'normal');
$fc      = (string) ($ts['font_color'] ?? '#333333');
$fst     = (string) ($ts['font_style'] ?? 'normal');
$tt      = (string) ($ts['text_transform'] ?? 'none');
$ls      = isset($ts['letter_spacing_em']) ? (float) $ts['letter_spacing_em'] : 0.0;
$lh      = isset($ts['line_height']) ? (float) $ts['line_height'] : 1.35;
$sh      = (string) ($ts['text_shadow'] ?? 'none');
$isCustomText = ($type === 'custom_text');
$ctCustom     = $isCustomText
    ? \App\Services\ReportPdfLayoutService::normalizeCustomTextPayload($inst['custom_text'] ?? [])
    : [];
$sectionKey = (string) ($inst['section'] ?? '');
$gridRow = array_key_exists('grid_row', $inst) ? max(0, (int) ($inst['grid_row'] ?? 0)) : '';
$gridStack = array_key_exists('grid_stack', $inst) ? max(0, (int) ($inst['grid_stack'] ?? 0)) : '';
$pdTypes = ['paciente_nombre', 'paciente_genero', 'paciente_edad', 'paciente_telefono', 'medico', 'fecha_recepcion', 'fecha_reporte', 'numero_orden'];
$pdGrid = is_array($pd_grid ?? null) ? $pd_grid : [];
$canPdSpacing = ($sectionKey === 'patient_doctor' && in_array($type, $pdTypes, true) && !$isCustomText);
$gapDef = $canPdSpacing ? ((int) ($inst['label_value_gap_px'] ?? $pdGrid['label_' . $type . '_value_gap_px'] ?? 0)) : 0;
$mtDef = $canPdSpacing ? ((int) ($inst['label_space_above_px'] ?? $pdGrid['label_' . $type . '_space_above_px'] ?? 0)) : 0;
$mbDef = $canPdSpacing ? ((int) ($inst['label_space_below_px'] ?? $pdGrid['label_' . $type . '_space_below_px'] ?? 0)) : 0;
$alignHIn = \App\Services\ReportPdfLayoutService::normalizeInstanceAlignH($inst['align_h'] ?? null);
$alignVIn = \App\Services\ReportPdfLayoutService::normalizeInstanceAlignV($inst['align_v'] ?? null);
$placementDisabled = ($selVal === '-1');
$isQr = ($type === 'qr');
$qrSizePct = max(50, min(400, (int) ($qr_size_percent ?? 100)));
?>
<li class="list-group-item pdf-instance-item" data-uid="<?= esc($uid) ?>" data-element-type="<?= esc($type, 'attr') ?>" data-grid-row="<?= esc((string) $gridRow, 'attr') ?>" data-grid-stack="<?= esc((string) $gridStack, 'attr') ?>">
    <div class="pdf-instance-head d-flex flex-wrap align-items-start gap-2 mb-3 pb-2 border-bottom">
        <div class="pdf-instance-head-title flex-grow-1 min-w-0">
            <strong class="d-block"><?= esc($label) ?></strong>
            <span class="small text-muted font-monospace pdf-instance-tech-id"><?= esc($type) ?></span>
        </div>
        <div class="pdf-instance-head-actions d-flex flex-shrink-0 gap-1">
            <button type="button" class="btn btn-sm btn-outline-secondary btn-dup-instance" title="Duplicar en esta sección"><i class="fa-regular fa-copy"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-del-instance" title="Quitar"><i class="fa-solid fa-trash"></i></button>
        </div>
    </div>
    <div class="pdf-instance-placement row g-2 g-md-3 align-items-end mb-2">
        <div class="col-auto d-flex align-items-end pb-1">
            <span class="text-muted instance-drag-handle" title="Arrastrar"><i class="fa-solid fa-grip-vertical"></i></span>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <label class="form-label small mb-1">Ubicación</label>
            <select class="form-select form-select-sm instance-column">
                <option value="-1" <?= $selVal === '-1' ? 'selected' : '' ?>>No mostrar</option>
                <?php for ($ci = 0; $ci < $cc; $ci++): ?>
                <option value="<?= $ci ?>" <?= $selVal === (string) $ci ? 'selected' : '' ?>>Columna <?= $ci + 1 ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <label class="form-label small mb-1">Ancho</label>
            <select class="form-select form-select-sm instance-span" title="Cuántas columnas ocupa el elemento" <?= $placementDisabled ? 'disabled' : '' ?>>
                <?php for ($s = 1; $s <= $maxSpan; $s++): ?>
                <option value="<?= $s ?>" <?= $spanIn === $s ? 'selected' : '' ?>><?= $s === 1 ? '1 columna' : $s . ' columnas' ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <label class="form-label small mb-1">Alineación H</label>
            <select class="form-select form-select-sm instance-align-h" title="Alineación horizontal en la celda" <?= $placementDisabled ? 'disabled' : '' ?>>
                <?php foreach (['left' => 'Izquierda', 'center' => 'Centro', 'right' => 'Derecha'] as $ak => $al): ?>
                <option value="<?= esc($ak, 'attr') ?>" <?= ($alignHIn ?? 'left') === $ak ? 'selected' : '' ?>><?= esc($al) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <label class="form-label small mb-1">Alineación V</label>
            <select class="form-select form-select-sm instance-align-v" title="Alineación vertical en la celda" <?= $placementDisabled ? 'disabled' : '' ?>>
                <?php foreach (['top' => 'Arriba', 'middle' => 'Centro', 'bottom' => 'Abajo'] as $ak => $al): ?>
                <option value="<?= esc($ak, 'attr') ?>" <?= ($alignVIn ?? 'top') === $ak ? 'selected' : '' ?>><?= esc($al) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php if ($isCustomText): ?>
    <?= view('config/partials/pdf_instance_custom_text_editor', ['ct' => $ctCustom, 'rowUid' => $uid]) ?>
    <?php elseif ($isQr): ?>
    <div class="row g-2 g-md-3 pdf-qr-size-controls">
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <label class="form-label small mb-1">Tamaño del QR (%)</label>
            <input type="number" class="form-control form-control-sm instance-qr-size-percent" min="50" max="400" step="1" value="<?= esc((string) $qrSizePct, 'attr') ?>" title="100 % ≈ 75 px de alto en el PDF">
        </div>
        <div class="col-12">
            <p class="small text-muted mb-0">La leyenda del QR (texto debajo o al lado) se configura en la tarjeta «Estilos de encabezado, paciente y pie».</p>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-2 g-md-3 pdf-text-style-controls">
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
            <label class="form-label small mb-1">Fuente</label>
            <select class="form-select form-select-sm instance-font-family">
                <?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $opt): ?>
                <option value="<?= esc($opt, 'attr') ?>" <?= $ff === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-3 col-md-2 col-xl-1">
            <label class="form-label small mb-1">Tamaño</label>
            <input type="number" class="form-control form-control-sm instance-font-size" min="6" max="24" step="0.5" value="<?= esc((string) $fs) ?>">
        </div>
        <div class="col-6 col-sm-3 col-md-2 col-xl-1">
            <label class="form-label small mb-1">Grosor</label>
            <select class="form-select form-select-sm instance-font-weight">
                <?php foreach (['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'] as $opt): ?>
                <option value="<?= esc($opt, 'attr') ?>" <?= $fw === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-3 col-md-2 col-xl-1">
            <label class="form-label small mb-1">Color</label>
            <input type="color" class="form-control form-control-color form-control-sm instance-font-color" value="<?= esc($fc, 'attr') ?>">
        </div>
        <div class="col-6 col-sm-3 col-md-2 col-xl-1">
            <label class="form-label small mb-1">Estilo</label>
            <select class="form-select form-select-sm instance-font-style">
                <?php foreach (['normal', 'italic', 'oblique'] as $opt): ?>
                <option value="<?= esc($opt, 'attr') ?>" <?= $fst === $opt ? 'selected' : '' ?>><?= esc(ucfirst($opt)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-6 col-md-4 col-xl-2">
            <label class="form-label small mb-1">Transformación</label>
            <select class="form-select form-select-sm instance-text-transform">
                <?php
                $transformOpts = ['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'];
                foreach ($transformOpts as $opt => $labelOpt): ?>
                <option value="<?= esc($opt, 'attr') ?>" <?= $tt === $opt ? 'selected' : '' ?>><?= esc($labelOpt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-md-2 col-xl-1">
            <label class="form-label small mb-1">Esp. letras</label>
            <input type="number" class="form-control form-control-sm instance-letter-spacing" min="-0.2" max="1" step="0.01" value="<?= esc((string) $ls) ?>">
        </div>
        <div class="col-6 col-sm-4 col-md-2 col-xl-1">
            <label class="form-label small mb-1">Interlineado</label>
            <input type="number" class="form-control form-control-sm instance-line-height" min="1" max="3" step="0.05" value="<?= esc((string) $lh) ?>">
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <label class="form-label small mb-1">Sombra</label>
            <select class="form-select form-select-sm instance-text-shadow">
                <?php foreach (['none' => 'Sin sombra', 'soft' => 'Suave', 'medium' => 'Media', 'strong' => 'Fuerte'] as $opt => $labOpt): ?>
                <option value="<?= esc($opt, 'attr') ?>" <?= $sh === $opt ? 'selected' : '' ?>><?= esc($labOpt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php if ($canPdSpacing): ?>
    <div class="row g-2 g-md-3 mt-2 pdf-patient-spacing-controls">
        <div class="col-12 col-sm-4">
            <label class="form-label small mb-1">Separación label/valor (px)</label>
            <input type="number" class="form-control form-control-sm instance-pd-label-value-gap-px" min="0" max="40" step="1" value="<?= esc((string) $gapDef, 'attr') ?>">
        </div>
        <div class="col-12 col-sm-4">
            <label class="form-label small mb-1">Espacio arriba (px)</label>
            <input type="number" class="form-control form-control-sm instance-pd-space-above-px" min="0" max="40" step="1" value="<?= esc((string) $mtDef, 'attr') ?>">
        </div>
        <div class="col-12 col-sm-4">
            <label class="form-label small mb-1">Espacio abajo (px)</label>
            <input type="number" class="form-control form-control-sm instance-pd-space-below-px" min="0" max="40" step="1" value="<?= esc((string) $mbDef, 'attr') ?>">
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</li>
