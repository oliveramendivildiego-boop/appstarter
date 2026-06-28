<?php
$rows   = $rows ?? [];
$prueba = $prueba ?? null;
?>
<?php if ($prueba !== null): ?>
<div class="mb-1">
    <strong>Prueba:</strong> <?= esc($prueba['name'] ?? '') ?>
    <?php if (($prueba['grupo'] ?? '') !== ''): ?> · <strong>Grupo:</strong> <?= esc($prueba['grupo']) ?><?php endif; ?>
    · <strong>Órdenes:</strong> <?= (int) ($total_ordenes ?? 0) ?>
    · <strong>Resultados:</strong> <?= (int) ($total_resultados ?? 0) ?>
</div>
<?php endif; ?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Orden</th>
            <th>Paciente</th>
            <th>CI</th>
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
            <td><?= esc($row['paciente']) ?></td>
            <td><?= esc($row['paciente_ci'] ?: '—') ?></td>
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
            <tr><td colspan="11" class="small">Sin resultados en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($rows !== []): ?>
<div class="alert-box">Resultados listados: <?= count($rows) ?>. Ordenados por fecha descendente.</div>
<?php endif; ?>
