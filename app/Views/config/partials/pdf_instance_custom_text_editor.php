<?php
/** @var array<string, mixed> $ct Resultado de normalizeCustomTextPayload */
/** @var string $rowUid uid de la fila (id único checkbox) */
$rowUid = (string) ($rowUid ?? 'x');
$idShow = 'ct_show_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $rowUid);
$lab   = (string) ($ct['label'] ?? '');
$val   = (string) ($ct['value'] ?? '');
$showL = ! empty($ct['show_label']);
$mode  = (($ct['line_mode'] ?? 'stacked') === 'inline') ? 'inline' : 'stacked';
$ls    = is_array($ct['label_style'] ?? null) ? $ct['label_style'] : [];
$vs    = is_array($ct['value_style'] ?? null) ? $ct['value_style'] : [];

$field = static function (string $pfx, array $ts): void {
    $ff = (string) ($ts['font_family'] ?? 'DejaVu Sans');
    $fs = isset($ts['font_size_pt']) ? (float) $ts['font_size_pt'] : 10.0;
    $fw = (string) ($ts['font_weight'] ?? 'normal');
    $fc = (string) ($ts['font_color'] ?? '#333333');
    $fst = (string) ($ts['font_style'] ?? 'normal');
    $tt = (string) ($ts['text_transform'] ?? 'none');
    $lse = isset($ts['letter_spacing_em']) ? (float) $ts['letter_spacing_em'] : 0.0;
    $lh = isset($ts['line_height']) ? (float) $ts['line_height'] : 1.35;
    $sh = (string) ($ts['text_shadow'] ?? 'none');
    ?>
<div class="col-12 col-md-6 col-lg-4">
    <label class="form-label small mb-1">Fuente</label>
    <select class="form-select form-select-sm <?= esc($pfx, 'attr') ?>-font-family">
        <?php foreach (['DejaVu Sans', 'Helvetica', 'Arial', 'Times New Roman', 'Courier New'] as $opt): ?>
        <option value="<?= esc($opt, 'attr') ?>" <?= $ff === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-6 col-md-3 col-lg-2">
    <label class="form-label small mb-1">Tamaño</label>
    <input type="number" class="form-control form-control-sm <?= esc($pfx, 'attr') ?>-font-size" min="6" max="24" step="0.5" value="<?= esc((string) $fs) ?>">
</div>
<div class="col-6 col-md-3 col-lg-2">
    <label class="form-label small mb-1">Grosor</label>
    <select class="form-select form-select-sm <?= esc($pfx, 'attr') ?>-font-weight">
        <?php foreach (['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'] as $opt): ?>
        <option value="<?= esc($opt, 'attr') ?>" <?= $fw === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-6 col-md-3 col-lg-2">
    <label class="form-label small mb-1">Color</label>
    <input type="color" class="form-control form-control-color form-control-sm <?= esc($pfx, 'attr') ?>-font-color" value="<?= esc($fc, 'attr') ?>">
</div>
<div class="col-6 col-md-3 col-lg-2">
    <label class="form-label small mb-1">Estilo</label>
    <select class="form-select form-select-sm <?= esc($pfx, 'attr') ?>-font-style">
        <?php foreach (['normal', 'italic', 'oblique'] as $opt): ?>
        <option value="<?= esc($opt, 'attr') ?>" <?= $fst === $opt ? 'selected' : '' ?>><?= esc(ucfirst($opt)) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-6 col-md-4 col-lg-3">
    <label class="form-label small mb-1">Transformación</label>
    <select class="form-select form-select-sm <?= esc($pfx, 'attr') ?>-text-transform">
        <?php foreach (['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo Título'] as $opt => $labOpt): ?>
        <option value="<?= esc($opt, 'attr') ?>" <?= $tt === $opt ? 'selected' : '' ?>><?= esc($labOpt) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-6 col-md-4 col-lg-2">
    <label class="form-label small mb-1">Esp. letras</label>
    <input type="number" class="form-control form-control-sm <?= esc($pfx, 'attr') ?>-letter-spacing" min="-0.2" max="1" step="0.01" value="<?= esc((string) $lse) ?>">
</div>
<div class="col-6 col-md-4 col-lg-2">
    <label class="form-label small mb-1">Interlineado</label>
    <input type="number" class="form-control form-control-sm <?= esc($pfx, 'attr') ?>-line-height" min="1" max="3" step="0.05" value="<?= esc((string) $lh) ?>">
</div>
<div class="col-12 col-md-6 col-lg-3">
    <label class="form-label small mb-1">Sombra</label>
    <select class="form-select form-select-sm <?= esc($pfx, 'attr') ?>-text-shadow">
        <?php foreach (['none' => 'Sin sombra', 'soft' => 'Suave', 'medium' => 'Media', 'strong' => 'Fuerte'] as $opt => $labOpt): ?>
        <option value="<?= esc($opt, 'attr') ?>" <?= $sh === $opt ? 'selected' : '' ?>><?= esc($labOpt) ?></option>
        <?php endforeach; ?>
    </select>
</div>
    <?php
};
?>
<div class="custom-text-editor-root mt-2 border rounded p-2 bg-light bg-opacity-50">
    <p class="small text-muted mb-2">Este elemento no usa el bloque «Fuente / tamaño» de arriba: solo los estilos de <strong>etiqueta</strong> y <strong>valor</strong> siguientes (evita solaparse con la cuadrícula).</p>
    <div class="row g-2 mb-3">
        <div class="col-12 col-md-6">
            <label class="form-label small mb-1">Texto de la etiqueta</label>
            <input type="text" class="form-control form-control-sm custom-text-label-input" maxlength="200" value="<?= esc($lab, 'attr') ?>">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label small mb-1">Texto del valor</label>
            <input type="text" class="form-control form-control-sm custom-text-value-input" maxlength="500" value="<?= esc($val, 'attr') ?>">
        </div>
        <div class="col-6 col-md-3 d-flex align-items-end">
            <div class="form-check mb-0">
                <input class="form-check-input custom-text-show-label" type="checkbox" id="<?= esc($idShow, 'attr') ?>" <?= $showL ? 'checked' : '' ?>>
                <label class="form-check-label small" for="<?= esc($idShow, 'attr') ?>">Mostrar etiqueta</label>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label small mb-1">Disposición</label>
            <select class="form-select form-select-sm custom-text-line-mode">
                <option value="stacked" <?= $mode === 'stacked' ? 'selected' : '' ?>>Etiqueta arriba, valor abajo</option>
                <option value="inline" <?= $mode === 'inline' ? 'selected' : '' ?>>Misma línea</option>
            </select>
        </div>
    </div>
    <p class="small fw-semibold mb-1">Estilo de la etiqueta</p>
    <div class="row g-3 mb-3 custom-text-style-label pdf-text-style-controls">
        <?php $field('ct-lbl', $ls); ?>
    </div>
    <p class="small fw-semibold mb-1">Estilo del valor</p>
    <div class="row g-3 custom-text-style-value pdf-text-style-controls">
        <?php $field('ct-val', $vs); ?>
    </div>
</div>
