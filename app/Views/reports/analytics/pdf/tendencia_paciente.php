<?php
$rows     = $rows ?? [];
$paciente = $paciente ?? null;
?>
<?php if ($paciente !== null): ?>
<div class="mb-1"><strong>Paciente:</strong> <?= esc($paciente['paciente'] ?? '') ?> · <strong>CI:</strong> <?= esc($paciente['ci'] ?? '—') ?> · <strong>Nacimiento:</strong> <?= esc($paciente['birthday'] ?? '—') ?></div>
<?php endif; ?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Orden</th>
            <th>Prueba</th>
            <th>Parámetro</th>
            <th class="text-end">Resultado</th>
            <th>Unidad</th>
            <th class="text-end">Ref. mín</th>
            <th class="text-end">Ref. máx</th>
            <th>Estado</th>
            <th>Médico solicitante</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
            <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
            <td><?= esc($row['prueba']) ?></td>
            <td><?= esc($row['parametro']) ?></td>
            <td class="text-end"><?= esc($row['valor']) ?></td>
            <td><?= esc($row['unidad']) ?></td>
            <td class="text-end"><?= esc($row['valor_min']) ?></td>
            <td class="text-end"><?= esc($row['valor_max']) ?></td>
            <td><?= esc(\App\Controllers\ReportsAnalytics::estadoValorLabel((string) $row['estado'])) ?></td>
            <td><?= esc($row['doctor'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="10" class="small">Sin resultados en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($rows !== []): ?>
<div class="alert-box">Resultados listados: <?= count($rows) ?>. Orden cronológico por prueba y parámetro para comparación histórica.</div>
<?php endif; ?>
