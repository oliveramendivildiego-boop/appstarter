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
</table>
