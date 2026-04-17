<?php
declare(strict_types=1);

/** @var string $section_key header|patient_doctor|footer|lab_firmas */
/** @var int $col_count */
/** @var array<string, mixed> $sec_layout */
$resolved = \App\Services\ReportPdfLayoutService::resolveSectionLayoutStyle($sec_layout, max(1, min(6, (int) $col_count)));
$lh       = $resolved['line_height'];
$rows     = max(1, min(50, (int) ($sec_layout['rows'] ?? 3)));
$hArr     = $resolved['column_align_h'];
$vArr     = $resolved['column_align_v'];
$idSafe   = preg_replace('/[^a-z0-9_]/', '_', $section_key);
?>
<div class="pdf-section-style-controls border-top pt-3 mt-3" data-pdf-section="<?= esc($section_key, 'attr') ?>">
    <h6 class="small text-uppercase text-muted mb-2">Estilo de la cuadrícula (PDF / impresión)</h6>
    <div class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label small mb-0" for="sec_lh_<?= esc($idSafe, 'attr') ?>">Interlineado</label>
            <input type="number" class="form-control form-control-sm pdf-sec-line-height" id="sec_lh_<?= esc($idSafe, 'attr') ?>" data-pdf-section="<?= esc($section_key, 'attr') ?>" min="1" max="2.5" step="0.05" value="<?= esc((string) $lh, 'attr') ?>" style="width:5.5rem;" title="Altura de línea relativa (1 = apretado, 2 = amplio)">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-0" for="sec_rows_<?= esc($idSafe, 'attr') ?>">Filas</label>
            <input type="number" class="form-control form-control-sm pdf-sec-rows" id="sec_rows_<?= esc($idSafe, 'attr') ?>" data-pdf-section="<?= esc($section_key, 'attr') ?>" min="1" max="50" step="1" value="<?= esc((string) $rows, 'attr') ?>" style="width:5.5rem;" title="Cantidad de filas para la matriz editable">
        </div>
        <div class="col small text-muted">Aplica al texto dentro de cada celda. Valores entre 1 y 2,5.</div>
    </div>
    <p class="small text-muted mb-2">Alineación por columna (horizontal y vertical en la fila).</p>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0 bg-white" style="font-size: 0.8rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:6rem;">Columna</th>
                    <th>Horizontal</th>
                    <th>Vertical</th>
                </tr>
            </thead>
            <tbody id="sec_style_cols_<?= esc($idSafe, 'attr') ?>" class="pdf-sec-col-aligns" data-pdf-section="<?= esc($section_key, 'attr') ?>">
                <?php for ($ci = 0; $ci < max(1, min(6, (int) $col_count)); $ci++):
                    $hVal = $hArr[$ci] ?? 'left';
                    $vVal = $vArr[$ci] ?? 'top';
                    ?>
                <tr class="pdf-sec-col-row" data-col="<?= (int) $ci ?>">
                    <td class="text-muted"><?= (int) ($ci + 1) ?></td>
                    <td>
                        <select class="form-select form-select-sm pdf-sec-col-h" data-col="<?= (int) $ci ?>" aria-label="Alineación horizontal columna <?= (int) ($ci + 1) ?>">
                            <option value="left" <?= $hVal === 'left' ? 'selected' : '' ?>>Izquierda</option>
                            <option value="center" <?= $hVal === 'center' ? 'selected' : '' ?>>Centro</option>
                            <option value="right" <?= $hVal === 'right' ? 'selected' : '' ?>>Derecha</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select form-select-sm pdf-sec-col-v" data-col="<?= (int) $ci ?>" aria-label="Alineación vertical columna <?= (int) ($ci + 1) ?>">
                            <option value="top" <?= $vVal === 'top' ? 'selected' : '' ?>>Arriba</option>
                            <option value="middle" <?= $vVal === 'middle' ? 'selected' : '' ?>>Centro</option>
                            <option value="bottom" <?= $vVal === 'bottom' ? 'selected' : '' ?>>Abajo</option>
                        </select>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>
