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
?>
<li class="list-group-item pdf-instance-item" data-uid="<?= esc($uid) ?>" data-element-type="<?= esc($type, 'attr') ?>">
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
        <div class="flex-grow-1">
            <strong class="d-block small"><?= esc($label) ?></strong>
            <span class="small text-muted font-monospace"><?= esc($type) ?></span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-dup-instance" title="Duplicar en esta sección"><i class="fa-regular fa-copy"></i></button>
        <button type="button" class="btn btn-sm btn-outline-danger btn-del-instance" title="Quitar"><i class="fa-solid fa-trash"></i></button>
    </div>
</li>
