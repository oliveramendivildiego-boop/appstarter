<?php
/**
 * Bloque «Cliente y atención» en 2 columnas (recibo / factura PDF).
 *
 * @var \App\Models\ReciboComprobanteModel $doc
 */
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
    <tr>
        <td>
            <span class="pair-k">Teléfono: </span><span class="pair-v"><?= esc($telTxt) ?></span>
        </td>
        <td>
            <span class="pair-k">Médico referente: </span><span class="pair-v"><?= esc($doctorTxt) ?></span>
        </td>
    </tr>
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
