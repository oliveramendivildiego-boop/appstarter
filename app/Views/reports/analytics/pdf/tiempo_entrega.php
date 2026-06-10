<?php
$rows     = $rows ?? [];
$totales  = $totales ?? [];
$slaHoras = (int) ($slaHoras ?? 24);
$fmtHoras = static fn ($h) => $h === null ? '—' : number_format((float) $h, 1) . ' h';
?>
<div class="alert-box">
    Órdenes: <?= (int) ($totales['ordenes'] ?? 0) ?> |
    TAT a resultado — promedio: <?= esc($fmtHoras($totales['promedio_resultado'] ?? null)) ?>,
    mín: <?= esc($fmtHoras($totales['min_resultado'] ?? null)) ?>,
    máx: <?= esc($fmtHoras($totales['max_resultado'] ?? null)) ?> |
    Cumplimiento SLA (<?= $slaHoras ?> h): <?= ($totales['cumplimiento_sla'] ?? null) !== null ? number_format((float) $totales['cumplimiento_sla'], 1) . '%' : '—' ?> |
    Retrasos: <?= (int) ($totales['fuera_sla'] ?? 0) ?>
</div>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Orden</th>
            <th>Paciente</th>
            <th>Recepción</th>
            <th>Primer resultado</th>
            <th>Primera validación</th>
            <th class="text-end">Horas a resultado</th>
            <th class="text-end">Horas a validación</th>
            <th>SLA</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row):
            $hr = $row['horas_resultado'];
            $slaLabel = $hr === null ? 'Sin resultado' : ((float) $hr <= $slaHoras ? 'Dentro de SLA' : 'Retraso');
        ?>
        <tr>
            <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
            <td><?= esc($row['paciente']) ?></td>
            <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
            <td><?= $row['fecha_resultado'] ? esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_resultado'])) : '—' ?></td>
            <td><?= $row['fecha_validacion'] ? esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_validacion'])) : '—' ?></td>
            <td class="text-end"><?= esc($fmtHoras($row['horas_resultado'])) ?></td>
            <td class="text-end"><?= esc($fmtHoras($row['horas_validacion'])) ?></td>
            <td><?= esc($slaLabel) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="8" class="small">Sin órdenes en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<div class="small">Recepción = ingreso de la orden. Resultado/validación tomados de la bitácora de auditoría. Órdenes anuladas excluidas.</div>
