<?php
declare(strict_types=1);

/** @var list<string> $footerMatrixTypeList */
/** @var array<string, mixed> $ft */
/** @var array<string, mixed> $hg */
/** @var array<string, mixed> $pd */
/** @var array<string, string> $hgFieldUi */
/** @var array<string, string> $pdFieldUi */
/** @var array<string, string> $ftFieldUi */
/** @var array<string, string> $elLabels */
/** @var callable $pdfGridChk */
/** @var callable $pdfGridLineMode */

$ftDup = static fn (string $id): string => 'ft_dup_' . $id;
$fwOpts = ['normal', 'bold', '400', '500', '600', '700', '800'];
$fstOpts = ['normal', 'italic', 'oblique'];
$ttOpts = ['none' => 'Normal', 'uppercase' => 'MAYÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Tipo título'];
$hgBtc = (string) ($hg['body_text_color'] ?? '#333333');
$pdBtc = (string) ($pd['body_text_color'] ?? '#333333');
$ftBtc = (string) ($ft['body_text_color'] ?? '#666666');
$ftBaseFs = (float) ($ft['font_size_pt'] ?? 8);
$hgBaseFs = (float) ($hg['font_size_pt'] ?? 9.5);
$pdBaseFs = (float) ($pd['font_size_pt'] ?? 9.5);

$renderTypoCells = static function (
    string $colorId,
    string $colorVal,
    string $fsId,
    $fsVal,
    string $fwId,
    string $fwVal,
    string $fstId,
    string $fstVal,
    string $ttId,
    string $ttVal
) use ($fwOpts, $fstOpts, $ttOpts): void {
    ?>
    <td><input type="color" class="form-control form-control-color" id="<?= esc($colorId, 'attr') ?>" value="<?= esc($colorVal, 'attr') ?>"></td>
    <td><input type="number" class="form-control form-control-sm" id="<?= esc($fsId, 'attr') ?>" min="7" max="20" step="0.5" value="<?= esc((string) $fsVal, 'attr') ?>"></td>
    <td><select class="form-select form-select-sm" id="<?= esc($fwId, 'attr') ?>"><?php foreach ($fwOpts as $w): ?><option value="<?= esc($w, 'attr') ?>" <?= $fwVal === $w ? 'selected' : '' ?>><?= esc($w) ?></option><?php endforeach; ?></select></td>
    <td><select class="form-select form-select-sm" id="<?= esc($fstId, 'attr') ?>"><?php foreach ($fstOpts as $st): ?><option value="<?= esc($st, 'attr') ?>" <?= $fstVal === $st ? 'selected' : '' ?>><?= esc(ucfirst($st)) ?></option><?php endforeach; ?></select></td>
    <td><select class="form-select form-select-sm" id="<?= esc($ttId, 'attr') ?>"><?php foreach ($ttOpts as $k => $v): ?><option value="<?= esc($k, 'attr') ?>" <?= $ttVal === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select></td>
    <?php
};

$renderEmptyLabelCells = static function (): void {
    ?>
    <td class="text-muted text-center">—</td>
    <td class="text-muted text-center">—</td>
    <td class="text-muted text-center">—</td>
    <?php
};

$hasRows = $footerMatrixTypeList !== [];
?>
<p class="small text-muted mb-2">Tamaño, grosor y estilo por texto según los elementos en la matriz del pie (si no coincide con la vista, guarde la plantilla y recargue el reporte). El PDF y viewreport usan estos valores en el HTML. Al agregar campos en la matriz, aparecen aquí automáticamente. El <strong>valor</strong> de cada ítem en la matriz usa el estilo de su instancia.</p>
<p class="small text-muted mb-3" id="ft_matrix_empty"<?= $hasRows ? ' style="display:none;"' : '' ?>>Agregue elementos en la matriz del pie para configurar etiquetas y tipografía aquí.</p>
<div class="table-responsive mb-3" id="ft_matrix_table_wrap"<?= $hasRows ? '' : ' style="display:none;"' ?>>
    <table class="table table-sm table-bordered align-middle mb-0" id="ft_matrix_table">
        <thead class="table-light"><tr class="small"><th>Elemento</th><th>Texto de la etiqueta</th><th class="text-center" style="width:6rem;">Mostrar</th><th style="min-width:9rem;">Etiqueta vs valor</th><th style="width:6rem;">Color</th><th style="width:6rem;">Tamaño (pt)</th><th style="width:7rem;">Grosor</th><th style="width:7rem;">Estilo</th><th style="width:9rem;">Transformación</th></tr></thead>
        <tbody class="small" id="ft_matrix_tbody">
        <?php foreach ($footerMatrixTypeList as $type):
            if ($type === 'footer_generated'):
                $lab = (string) ($ft['label_footer_generated'] ?? 'Resultados generados el');
                ?>
            <tr data-ft-matrix-type="footer_generated">
                <td>
                    <span class="fw-semibold"><?= esc($ftFieldUi['footer_generated'] ?? $type) ?></span>
                    <span class="text-muted d-block small">Etiqueta antes de la fecha</span>
                </td>
                <td><input type="text" class="form-control form-control-sm" id="ft_label_footer_generated" maxlength="120" value="<?= esc($lab, 'attr') ?>"></td>
                <td class="text-center"><input type="checkbox" class="form-check-input" id="ft_show_label_footer_generated" value="1"<?= $pdfGridChk($ft, 'show_label_footer_generated') ?>></td>
                <td><select class="form-select form-select-sm" id="ft_label_footer_generated_line_mode"><option value="stacked" <?= $pdfGridLineMode($ft, 'label_footer_generated_line_mode') === 'stacked' ? 'selected' : '' ?>>Debajo</option><option value="inline" <?= $pdfGridLineMode($ft, 'label_footer_generated_line_mode') === 'inline' ? 'selected' : '' ?>>Misma línea</option></select></td>
                <?php $renderTypoCells('ft_color_label_generated', (string) ($ft['label_footer_generated_color'] ?? $ftBtc), 'ft_fs_label_gen', $ft['label_footer_generated_font_size_pt'] ?? $ftBaseFs, 'ft_fw_label_gen', (string) ($ft['label_footer_generated_font_weight'] ?? $ft['font_weight'] ?? 'normal'), 'ft_fst_label_gen', (string) ($ft['label_footer_generated_font_style'] ?? $ft['font_style'] ?? 'normal'), 'ft_tt_label_gen', (string) ($ft['label_footer_generated_text_transform'] ?? $ft['text_transform'] ?? 'none')); ?>
            </tr>
            <tr data-ft-matrix-type="footer_generated_datetime">
                <td>
                    <span class="fw-semibold">Fecha y hora generadas</span>
                    <span class="text-muted d-block small">Valor en «generados el»</span>
                </td>
                <?php $renderEmptyLabelCells(); ?>
                <?php $renderTypoCells('ft_color_datetime', (string) ($ft['label_footer_datetime_color'] ?? $ftBtc), 'ft_fs_datetime', $ft['label_footer_datetime_font_size_pt'] ?? $ftBaseFs, 'ft_fw_datetime', (string) ($ft['label_footer_datetime_font_weight'] ?? $ft['font_weight'] ?? 'normal'), 'ft_fst_datetime', (string) ($ft['label_footer_datetime_font_style'] ?? $ft['font_style'] ?? 'normal'), 'ft_tt_datetime', (string) ($ft['label_footer_datetime_text_transform'] ?? $ft['text_transform'] ?? 'none')); ?>
            </tr>
                <?php
            elseif ($type === 'footer_company'): ?>
            <tr data-ft-matrix-type="<?= esc($type, 'attr') ?>">
                <td><span class="fw-semibold"><?= esc($ftFieldUi['footer_company'] ?? $type) ?></span><span class="text-muted d-block small">Valor en la matriz</span></td>
                <?php $renderEmptyLabelCells(); ?>
                <?php $renderTypoCells('ft_color_company', (string) ($ft['footer_company_text_color'] ?? $ftBtc), 'ft_fs_company', $ft['footer_company_font_size_pt'] ?? $ftBaseFs, 'ft_fw_company', (string) ($ft['footer_company_font_weight'] ?? $ft['font_weight'] ?? 'normal'), 'ft_fst_company', (string) ($ft['footer_company_font_style'] ?? $ft['font_style'] ?? 'normal'), 'ft_tt_company', (string) ($ft['footer_company_text_transform'] ?? $ft['text_transform'] ?? 'none')); ?>
            </tr>
            <?php elseif ($type === 'footer_policy'): ?>
            <tr data-ft-matrix-type="<?= esc($type, 'attr') ?>">
                <td><span class="fw-semibold"><?= esc($ftFieldUi['footer_policy'] ?? $type) ?></span><span class="text-muted d-block small">Valor en la matriz</span></td>
                <?php $renderEmptyLabelCells(); ?>
                <?php $renderTypoCells('ft_color_policy', (string) ($ft['footer_policy_text_color'] ?? $ftBtc), 'ft_fs_policy', $ft['footer_policy_font_size_pt'] ?? $ftBaseFs, 'ft_fw_policy', (string) ($ft['footer_policy_font_weight'] ?? $ft['font_weight'] ?? 'normal'), 'ft_fst_policy', (string) ($ft['footer_policy_font_style'] ?? $ft['font_style'] ?? 'normal'), 'ft_tt_policy', (string) ($ft['footer_policy_text_transform'] ?? $ft['text_transform'] ?? 'none')); ?>
            </tr>
            <?php elseif (array_key_exists($type, \App\Services\ReportPdfLayoutService::HEADER_GRID_LABEL_DEFAULTS)):
                $defHg = \App\Services\ReportPdfLayoutService::HEADER_GRID_LABEL_DEFAULTS[$type];
                $hgLab = (string) ($hg['label_' . $type] ?? $defHg);
                $cHg = (string) ($hg['label_' . $type . '_text_color'] ?? $hgBtc);
                ?>
            <tr data-ft-matrix-type="<?= esc($type, 'attr') ?>">
                <td><span class="fw-semibold"><?= esc($hgFieldUi[$type] ?? $elLabels[$type] ?? $type) ?></span><span class="text-muted d-block small">Valor: estilo del ítem en la matriz</span></td>
                <td><input type="text" class="form-control form-control-sm" id="<?= esc($ftDup('hg_label_' . $type), 'attr') ?>" maxlength="120" value="<?= esc($hgLab, 'attr') ?>"></td>
                <td class="text-center"><input type="checkbox" class="form-check-input" id="<?= esc($ftDup('hg_show_label_' . $type), 'attr') ?>" value="1"<?= $pdfGridChk($hg, 'show_label_' . $type) ?>></td>
                <td><select class="form-select form-select-sm" id="<?= esc($ftDup('hg_label_' . $type . '_line_mode'), 'attr') ?>"><option value="stacked" <?= $pdfGridLineMode($hg, 'label_' . $type . '_line_mode') === 'stacked' ? 'selected' : '' ?>>Debajo</option><option value="inline" <?= $pdfGridLineMode($hg, 'label_' . $type . '_line_mode') === 'inline' ? 'selected' : '' ?>>Misma línea</option></select></td>
                <?php $renderTypoCells($ftDup('hg_hdr_' . $type . '_color'), $cHg, $ftDup('hg_hdr_' . $type . '_fs'), $hg['label_' . $type . '_font_size_pt'] ?? $hgBaseFs, $ftDup('hg_hdr_' . $type . '_fw'), (string) ($hg['label_' . $type . '_font_weight'] ?? $hg['font_weight'] ?? 'normal'), $ftDup('hg_hdr_' . $type . '_fst'), (string) ($hg['label_' . $type . '_font_style'] ?? $hg['font_style'] ?? 'normal'), $ftDup('hg_hdr_' . $type . '_tt'), (string) ($hg['label_' . $type . '_text_transform'] ?? $hg['text_transform'] ?? 'none')); ?>
            </tr>
                <?php
            elseif ($type === 'qr'):
                $cQr = (string) ($hg['label_qr_hint_text_color'] ?? $hgBtc);
                ?>
            <tr data-ft-matrix-type="qr">
                <td><span class="fw-semibold"><?= esc($hgFieldUi['qr'] ?? 'Código QR') ?> — leyenda</span></td>
                <td><input type="text" class="form-control form-control-sm" id="<?= esc($ftDup('hg_label_qr_hint'), 'attr') ?>" maxlength="120" value="<?= esc((string) ($hg['label_qr_hint'] ?? ''), 'attr') ?>"></td>
                <td class="text-center"><input type="checkbox" class="form-check-input" id="<?= esc($ftDup('hg_show_label_qr_hint'), 'attr') ?>" value="1"<?= $pdfGridChk($hg, 'show_label_qr_hint') ?>></td>
                <td><select class="form-select form-select-sm" id="<?= esc($ftDup('hg_label_qr_hint_line_mode'), 'attr') ?>"><option value="stacked" <?= $pdfGridLineMode($hg, 'label_qr_hint_line_mode') === 'stacked' ? 'selected' : '' ?>>Debajo del QR</option><option value="inline" <?= $pdfGridLineMode($hg, 'label_qr_hint_line_mode') === 'inline' ? 'selected' : '' ?>>Misma línea</option></select></td>
                <?php $renderTypoCells($ftDup('hg_qr_hint_color'), $cQr, $ftDup('hg_qr_hint_fs'), $hg['label_qr_hint_font_size_pt'] ?? $hgBaseFs, $ftDup('hg_qr_hint_fw'), (string) ($hg['label_qr_hint_font_weight'] ?? $hg['font_weight'] ?? 'normal'), $ftDup('hg_qr_hint_fst'), (string) ($hg['label_qr_hint_font_style'] ?? $hg['font_style'] ?? 'normal'), $ftDup('hg_qr_hint_tt'), (string) ($hg['label_qr_hint_text_transform'] ?? $hg['text_transform'] ?? 'none')); ?>
            </tr>
                <?php
            elseif (array_key_exists($type, \App\Services\ReportPdfLayoutService::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS)):
                $defPd = \App\Services\ReportPdfLayoutService::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS[$type];
                $pdLab = (string) ($pd['label_' . $type] ?? $defPd);
                $cPd = (string) ($pd['label_' . $type . '_text_color'] ?? $pdBtc);
                ?>
            <tr data-ft-matrix-type="<?= esc($type, 'attr') ?>">
                <td><span class="fw-semibold"><?= esc($pdFieldUi[$type] ?? $elLabels[$type] ?? $type) ?></span><span class="text-muted d-block small">Valor: estilo del ítem en la matriz</span></td>
                <td><input type="text" class="form-control form-control-sm" id="<?= esc($ftDup('pd_label_' . $type), 'attr') ?>" maxlength="120" value="<?= esc($pdLab, 'attr') ?>"></td>
                <td class="text-center"><input type="checkbox" class="form-check-input" id="<?= esc($ftDup('pd_show_label_' . $type), 'attr') ?>" value="1"<?= $pdfGridChk($pd, 'show_label_' . $type) ?>></td>
                <td><select class="form-select form-select-sm" id="<?= esc($ftDup('pd_label_' . $type . '_line_mode'), 'attr') ?>"><option value="stacked" <?= $pdfGridLineMode($pd, 'label_' . $type . '_line_mode') === 'stacked' ? 'selected' : '' ?>>Debajo</option><option value="inline" <?= $pdfGridLineMode($pd, 'label_' . $type . '_line_mode') === 'inline' ? 'selected' : '' ?>>Misma línea</option></select></td>
                <?php $renderTypoCells($ftDup('pd_lbl_' . $type . '_color'), $cPd, $ftDup('pd_lbl_' . $type . '_fs'), $pd['label_' . $type . '_font_size_pt'] ?? $pdBaseFs, $ftDup('pd_lbl_' . $type . '_fw'), (string) ($pd['label_' . $type . '_font_weight'] ?? $pd['font_weight'] ?? 'normal'), $ftDup('pd_lbl_' . $type . '_fst'), (string) ($pd['label_' . $type . '_font_style'] ?? $pd['font_style'] ?? 'normal'), $ftDup('pd_lbl_' . $type . '_tt'), (string) ($pd['label_' . $type . '_text_transform'] ?? $pd['text_transform'] ?? 'none')); ?>
            </tr>
            <?php endif;
        endforeach; ?>
        </tbody>
    </table>
</div>
