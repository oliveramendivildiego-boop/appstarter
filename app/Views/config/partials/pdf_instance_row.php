<?php
/** @var array $inst */
/** @var int $col_count */
/** @var array<string, string> $elLabels */
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
?>
<li class="list-group-item pdf-instance-item" data-uid="<?= esc($uid) ?>" data-element-type="<?= esc($type, 'attr') ?>">
    <div class="pdf-instance-head mb-2 pb-2 border-bottom">
        <strong class="d-block"><?= esc($label) ?></strong>
        <span class="small text-muted font-monospace"><?= esc($type) ?></span>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="text-muted instance-drag-handle" style="cursor: grab;" title="Arrastrar"><i class="fa-solid fa-grip-vertical"></i></span>
        <select class="form-select form-select-sm instance-column" style="max-width: 11rem;">
            <option value="-1" <?= $selVal === '-1' ? 'selected' : '' ?>>No mostrar</option>
            <?php for ($ci = 0; $ci < $cc; $ci++): ?>
            <option value="<?= $ci ?>" <?= $selVal === (string) $ci ? 'selected' : '' ?>>Columna <?= $ci + 1 ?></option>
            <?php endfor; ?>
        </select>
        <select class="form-select form-select-sm instance-span" style="max-width: 9rem;" title="Cuántas columnas ocupa el elemento" <?= $selVal === '-1' ? 'disabled' : '' ?>>
            <?php for ($s = 1; $s <= $maxSpan; $s++): ?>
            <option value="<?= $s ?>" <?= $spanIn === $s ? 'selected' : '' ?>><?= $s === 1 ? 'Ancho: 1 col.' : 'Ancho: ' . $s . ' cols.' ?></option>
            <?php endfor; ?>
        </select>
        <div class="flex-grow-1"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-dup-instance" title="Duplicar en esta sección"><i class="fa-regular fa-copy"></i></button>
        <button type="button" class="btn btn-sm btn-outline-danger btn-del-instance" title="Quitar"><i class="fa-solid fa-trash"></i></button>
    </div>
    <div class="row g-3 mt-2 pdf-text-style-controls">
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label small mb-1">Fuente</label>
            <select class="form-select form-select-sm instance-font-family">
                <?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $opt): ?>
                <option value="<?= esc($opt, 'attr') ?>" <?= $ff === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small mb-1">Tamaño</label>
            <input type="number" class="form-control form-control-sm instance-font-size" min="6" max="24" step="0.5" value="<?= esc((string) $fs) ?>">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small mb-1">Grosor</label>
            <select class="form-select form-select-sm instance-font-weight">
                <?php foreach (['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'] as $opt): ?>
                <option value="<?= esc($opt, 'attr') ?>" <?= $fw === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small mb-1">Color</label>
            <input type="color" class="form-control form-control-color form-control-sm instance-font-color" value="<?= esc($fc, 'attr') ?>">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small mb-1">Estilo</label>
            <select class="form-select form-select-sm instance-font-style">
                <?php foreach (['normal', 'italic', 'oblique'] as $opt): ?>
                <option value="<?= esc($opt, 'attr') ?>" <?= $fst === $opt ? 'selected' : '' ?>><?= esc(ucfirst($opt)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <label class="form-label small mb-1">Transformación</label>
            <select class="form-select form-select-sm instance-text-transform">
                <?php
                $transformOpts = ['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'];
                foreach ($transformOpts as $opt => $labelOpt): ?>
                <option value="<?= esc($opt, 'attr') ?>" <?= $tt === $opt ? 'selected' : '' ?>><?= esc($labelOpt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <label class="form-label small mb-1">Esp. letras</label>
            <input type="number" class="form-control form-control-sm instance-letter-spacing" min="-0.2" max="1" step="0.01" value="<?= esc((string) $ls) ?>">
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <label class="form-label small mb-1">Interlineado</label>
            <input type="number" class="form-control form-control-sm instance-line-height" min="1" max="3" step="0.05" value="<?= esc((string) $lh) ?>">
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label small mb-1">Sombra</label>
            <select class="form-select form-select-sm instance-text-shadow">
                <?php foreach (['none' => 'Sin sombra', 'soft' => 'Suave', 'medium' => 'Media', 'strong' => 'Fuerte'] as $opt => $labOpt): ?>
                <option value="<?= esc($opt, 'attr') ?>" <?= $sh === $opt ? 'selected' : '' ?>><?= esc($labOpt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</li>
