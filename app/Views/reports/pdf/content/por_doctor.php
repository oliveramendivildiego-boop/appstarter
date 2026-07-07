<?php
$data             = $data ?? [];
$totales          = $totales ?? (object) [];
$sinDoctorDetalle = $sinDoctorDetalle ?? [];
$labelSinDoctor   = $labelSinDoctor ?? 'Sin doctor';
$totalSinDoctor   = 0.0;
foreach ($sinDoctorDetalle as $rowSd) {
    $totalSinDoctor += (float) ($rowSd['total'] ?? 0);
}
?>
<p class="small mb-1">Por fecha de ingreso. Incluye «<?= esc($labelSinDoctor) ?>». Excluye anuladas.</p>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Doctor</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">Total facturado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
            <tr>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= format_currency((float) ($row['total'] ?? 0)) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($data === []): ?>
            <tr><td colspan="3" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($sinDoctorDetalle !== []): ?>
    <h2 class="mt-2">Detalle — <?= esc($labelSinDoctor) ?></h2>
    <table class="pdf-t">
        <thead>
            <tr>
                <th>Orden</th>
                <th>Fecha</th>
                <th>Paciente</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sinDoctorDetalle as $rowSd): ?>
                <tr>
                    <td><?= esc(registro_orden_display($rowSd)) ?></td>
                    <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($rowSd['ingreso'] ?? '')) ?></td>
                    <td><?= esc(trim((string) ($rowSd['paciente'] ?? ''))) ?></td>
                    <td class="text-end"><?= format_currency((float) ($rowSd['total'] ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3" class="text-end"><strong>Total</strong></td>
                <td class="text-end"><strong><?= format_currency($totalSinDoctor) ?></strong></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>
<?php if ($data !== []): ?>
    <div class="alert-box">
        Registros facturables: <?= (int) ($totales->total_registros ?? 0) ?> |
        Total facturado: <?= format_currency((float) ($totales->total_facturado ?? 0)) ?>
    </div>
<?php endif; ?>
