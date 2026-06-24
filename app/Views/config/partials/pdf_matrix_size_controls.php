<?php
declare(strict_types=1);

/** @var string $section_key header|patient_doctor|footer|lab_firmas */
/** @var int $col_count */
/** @var array<string, mixed> $sec_layout */
$colsIdBySection = [
    'header'         => 'sec_cols_header',
    'patient_doctor' => 'sec_cols_patient',
    'lab_firmas'     => 'sec_cols_lab_firmas',
    'footer'         => 'sec_cols_footer',
];
$cols   = max(1, min(6, (int) $col_count));
$rows   = max(1, min(50, (int) ($sec_layout['rows'] ?? 3)));
$idSafe = preg_replace('/[^a-z0-9_]/', '_', $section_key);
$colsId = $colsIdBySection[$section_key] ?? ('sec_cols_' . $idSafe);
$rowsId = 'sec_rows_' . $idSafe;
?>
<div class="d-flex align-items-center gap-3 flex-wrap pdf-matrix-size-controls" data-pdf-section="<?= esc($section_key, 'attr') ?>">
    <div class="d-flex align-items-center gap-1">
        <label class="form-label small mb-0 fw-semibold" for="<?= esc($colsId, 'attr') ?>">Columnas</label>
        <input type="number" class="form-control form-control-sm pdf-sec-cols" id="<?= esc($colsId, 'attr') ?>" data-pdf-section="<?= esc($section_key, 'attr') ?>" min="1" max="6" step="1" value="<?= (int) $cols ?>" style="width:4.5rem;" title="Cantidad de columnas de la matriz">
    </div>
    <div class="d-flex align-items-center gap-1">
        <label class="form-label small mb-0 fw-semibold" for="<?= esc($rowsId, 'attr') ?>">Filas</label>
        <input type="number" class="form-control form-control-sm pdf-sec-rows" id="<?= esc($rowsId, 'attr') ?>" data-pdf-section="<?= esc($section_key, 'attr') ?>" min="1" max="50" step="1" value="<?= (int) $rows ?>" style="width:4.5rem;" title="Cantidad de filas de la matriz">
    </div>
</div>
