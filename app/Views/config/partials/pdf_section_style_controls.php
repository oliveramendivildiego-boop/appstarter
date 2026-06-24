<?php
declare(strict_types=1);

/** @var string $section_key header|patient_doctor|footer|lab_firmas */
/** @var int $col_count */
/** @var array<string, mixed> $sec_layout */
$resolved = \App\Services\ReportPdfLayoutService::resolveSectionLayoutStyle($sec_layout, max(1, min(6, (int) $col_count)));
$lh       = $resolved['line_height'];
$defRowGap = ($section_key === 'patient_doctor') ? 2 : (($section_key === 'footer') ? 0 : 6);
$rowGap    = max(0, min(40, (int) ($sec_layout['row_gap_px'] ?? $defRowGap)));
$idSafe   = preg_replace('/[^a-z0-9_]/', '_', $section_key);
?>
<div class="pdf-section-style-controls border-top pt-3 mt-3" data-pdf-section="<?= esc($section_key, 'attr') ?>">
    <h6 class="small text-uppercase text-muted mb-2">Estilo de la cuadrícula (PDF / impresión)</h6>
    <div class="row g-2 align-items-end mb-0">
        <div class="col-auto">
            <label class="form-label small mb-0" for="sec_lh_<?= esc($idSafe, 'attr') ?>">Interlineado</label>
            <input type="number" class="form-control form-control-sm pdf-sec-line-height" id="sec_lh_<?= esc($idSafe, 'attr') ?>" data-pdf-section="<?= esc($section_key, 'attr') ?>" min="1" max="2.5" step="0.05" value="<?= esc((string) $lh, 'attr') ?>" style="width:5.5rem;" title="Altura de línea relativa (1 = apretado, 2 = amplio)">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-0" for="sec_row_gap_<?= esc($idSafe, 'attr') ?>">Espacio entre filas (px)</label>
            <input type="number" class="form-control form-control-sm pdf-sec-row-gap" id="sec_row_gap_<?= esc($idSafe, 'attr') ?>" data-pdf-section="<?= esc($section_key, 'attr') ?>" min="0" max="40" step="1" value="<?= esc((string) $rowGap, 'attr') ?>" style="width:5.5rem;" title="Separación vertical entre filas de la cuadrícula en el PDF">
        </div>
        <div class="col small text-muted">Interlineado: altura de línea en cada celda (1–2,5). Entre filas: separación de la matriz en el PDF. Columnas y filas de la matriz se configuran debajo. La alineación de cada elemento se configura en el ítem.</div>
    </div>
</div>
