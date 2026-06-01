<?php
/**
 * Bloque «Cliente y atención» en 2 columnas (recibo / factura PDF).
 * Si se pasa $comprobante_layout, usa la matriz configurada; si no, el diseño fijo legacy.
 *
 * @var \App\Models\ReciboComprobanteModel|\App\Models\FacturaComprobanteModel $doc
 */
$showDoctor = isset($show_doctor) ? (bool) $show_doctor : true;
$layout = isset($comprobante_layout) && is_array($comprobante_layout) ? $comprobante_layout : null;

if ($layout !== null) {
    echo (new \App\Services\ComprobanteLayoutService())->renderClientGridHtml($layout, $doc, $showDoctor);
    return;
}

$g = (int) ($doc->doctorGender ?? 0);
$tituloMedico = $g === 1 ? 'Dr. ' : ($g === 2 ? 'Dra. ' : '');
$doctorTxt    = trim($doc->doctorNombre ?? '') !== '' ? $tituloMedico . $doc->doctorNombre : '—';
$ciTxt        = trim($doc->pacienteCi ?? '') !== '' ? $doc->pacienteCi : '—';
$telTxt       = trim($doc->pacienteTelefono ?? '') !== '' ? $doc->pacienteTelefono : '—';
$instTxt      = trim((string) ($doc->institucionNombre ?? ''));
$instDctoPct  = (float) ($doc->institucionDescuentoPct ?? 0);
?>
<table class="pair-table">
    <tr>
        <td>
            <span class="pair-k">Paciente: </span><span class="pair-v"><?= esc($doc->pacienteNombre) ?></span>
        </td>
        <td>
            <span class="pair-k">Documento de identidad: </span><span class="pair-v"><?= esc($ciTxt) ?></span>
        </td>
    </tr>
    <?php if ($showDoctor): ?>
        <tr>
            <td>
                <span class="pair-k">Teléfono: </span><span class="pair-v"><?= esc($telTxt) ?></span>
            </td>
            <td>
                <span class="pair-k">Médico referente: </span><span class="pair-v"><?= esc($doctorTxt) ?></span>
            </td>
        </tr>
    <?php else: ?>
        <tr>
            <td colspan="2">
                <span class="pair-k">Teléfono: </span><span class="pair-v"><?= esc($telTxt) ?></span>
            </td>
        </tr>
    <?php endif; ?>
    <tr>
        <td colspan="2">
            <span class="pair-k">Institución / procedencia: </span>
            <span class="pair-v">
                <?php if ($instTxt !== ''): ?>
                    <?= esc($instTxt) ?>
                    <?php if ($instDctoPct > 0): ?>
                        — Descuento aplicado: <?= esc(number_format($instDctoPct, 2, '.', '')) ?>%
                    <?php endif; ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </span>
        </td>
    </tr>
</table>
