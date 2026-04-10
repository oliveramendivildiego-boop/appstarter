<?php
$report_lab_firmas = $report_lab_firmas ?? [];
if ($report_lab_firmas === []) {
    return;
}
helper('registro');
$multi = count($report_lab_firmas) > 1;
?>
<div class="group-title pdf-lab-f-title" style="margin-top: 10px;">VALIDACIÓN Y APROBACIÓN</div>
<table class="results">
    <tbody>
        <?php foreach ($report_lab_firmas as $idx => $firma): ?>
            <?php if ($multi): ?>
                <tr>
                    <td colspan="3" class="pdf-lab-f-cell" style="border-bottom: 1px solid #dee2e6; font-weight: 600;">
                        <?= esc(strtoupper((string) ($firma['prueba_nombre'] ?? ''))) ?>
                    </td>
                </tr>
            <?php endif; ?>
            <?php
            $seal = trim((string) ($firma['approver_seal'] ?? ''));
            $sig  = trim((string) ($firma['approver_signature'] ?? ''));
            $sigUri  = ($sig !== '') ? report_image_data_uri($sig) : '';
            $sealUri = ($seal !== '') ? report_image_data_uri($seal) : '';
            ?>
            <tr>
                <td class="pdf-lab-f-cell" style="vertical-align: top; width: 32%;">
                    <div style="opacity: 0.75; font-size: 0.92em; margin-bottom: 4px;">Validado por:</div>
                    <div><?= esc(($firma['validator_name'] ?? '') !== '' ? $firma['validator_name'] : '—') ?></div>
                </td>
                <td class="pdf-lab-f-cell" style="vertical-align: top; width: 34%; text-align: center;">
                    <div style="opacity: 0.75; font-size: 0.92em; margin-bottom: 6px;">Sello</div>
                    <?php if ($sealUri !== ''): ?>
                        <img src="<?= esc($sealUri, 'attr') ?>" alt="" class="pdf-lab-f-img" style="max-height: 110px; max-width: 100%;">
                    <?php else: ?>
                        <div style="opacity: 0.6;">—</div>
                    <?php endif; ?>
                </td>
                <td class="pdf-lab-f-cell" style="vertical-align: top; width: 34%;">
                    <div style="opacity: 0.75; font-size: 0.92em; margin-bottom: 6px;">Aprobado por:</div>
                    <?php if ($sigUri !== ''): ?>
                        <div style="margin-bottom: 8px;">
                            <img src="<?= esc($sigUri, 'attr') ?>" alt="" class="pdf-lab-f-img" style="max-height: 72px; max-width: 220px;">
                        </div>
                    <?php endif; ?>
                    <div style="font-weight: 600; margin-top: 4px;"><?= esc(($firma['approver_name'] ?? '') !== '' ? $firma['approver_name'] : '—') ?></div>
                    <?php $cargo = trim((string) ($firma['approver_cargo'] ?? '')); ?>
                    <?php if ($cargo !== ''): ?>
                        <div style="margin-top: 6px; opacity: 0.85; font-size: 0.95em;"><span style="opacity: 0.75;">Cargo:</span> <?= esc($cargo) ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($multi && $idx < count($report_lab_firmas) - 1): ?>
                <tr>
                    <td colspan="3" style="height: 12px; border: none !important; background: transparent !important; padding: 0;"></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>
