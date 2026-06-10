<?php
$rows      = $rows ?? [];
$porEstado = $porEstado ?? [];
$porPrueba = $porPrueba ?? [];
$porGrupo  = $porGrupo ?? [];
$total     = (int) ($total ?? 0);
?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Orden</th>
            <th>Paciente</th>
            <th>Grupo</th>
            <th>Prueba</th>
            <th>Parámetro</th>
            <th class="text-end">Resultado</th>
            <th>Unidad</th>
            <th class="text-end">Ref. mín</th>
            <th class="text-end">Ref. máx</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
            <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
            <td><?= esc($row['paciente']) ?></td>
            <td><?= esc($row['grupo']) ?></td>
            <td><?= esc($row['prueba']) ?></td>
            <td><?= esc($row['parametro']) ?></td>
            <td class="text-end"><strong><?= esc($row['valor']) ?></strong></td>
            <td><?= esc($row['unidad']) ?></td>
            <td class="text-end"><?= esc($row['valor_min']) ?></td>
            <td class="text-end"><?= esc($row['valor_max']) ?></td>
            <td><?= esc(\App\Controllers\ReportsAnalytics::estadoValorLabel((string) $row['estado'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="11" class="small">Sin valores fuera de rango en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($rows !== []): ?>
<div class="alert-box">
    Total fuera de rango: <?= $total ?> |
    Bajos: <?= (int) ($porEstado['bajo'] ?? 0) ?> | Altos: <?= (int) ($porEstado['alto'] ?? 0) ?> |
    Críticos bajos: <?= (int) ($porEstado['critico_bajo'] ?? 0) ?> | Críticos altos: <?= (int) ($porEstado['critico_alto'] ?? 0) ?>
</div>
<h2>Cantidad por prueba</h2>
<table class="pdf-t">
    <thead><tr><th>Prueba</th><th class="text-end">Cantidad</th><th class="text-end">%</th></tr></thead>
    <tbody>
        <?php foreach ($porPrueba as $prueba => $cant): ?>
        <tr>
            <td><?= esc($prueba) ?></td>
            <td class="text-end"><?= (int) $cant ?></td>
            <td class="text-end"><?= $total > 0 ? number_format($cant * 100 / $total, 1) : 0 ?>%</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<h2>Cantidad por grupo</h2>
<table class="pdf-t">
    <thead><tr><th>Grupo</th><th class="text-end">Cantidad</th><th class="text-end">%</th></tr></thead>
    <tbody>
        <?php foreach ($porGrupo as $grupo => $cant): ?>
        <tr>
            <td><?= esc($grupo !== '' ? $grupo : 'Sin grupo') ?></td>
            <td class="text-end"><?= (int) $cant ?></td>
            <td class="text-end"><?= $total > 0 ? number_format($cant * 100 / $total, 1) : 0 ?>%</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
