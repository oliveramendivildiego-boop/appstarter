<?php
$rows       = $rows ?? [];
$porUsuario = $porUsuario ?? [];
$total      = (int) ($total ?? 0);
?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Orden</th>
            <th>Paciente</th>
            <th>Prueba</th>
            <th>Campo</th>
            <th>Resultado original</th>
            <th>Resultado nuevo</th>
            <th>Usuario</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha'] ?? '')) ?></td>
            <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
            <td><?= esc($row['paciente'] ?: '—') ?></td>
            <td><?= esc($row['prueba'] ?: '—') ?></td>
            <td><?= esc($row['campo']) ?></td>
            <td><?= esc($row['valor_anterior']) ?></td>
            <td><strong><?= esc($row['valor_nuevo']) ?></strong></td>
            <td><?= esc($row['usuario'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="8" class="small">Sin correcciones registradas en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($rows !== []): ?>
<div class="alert-box">
    Total de correcciones: <?= $total ?>
    <div class="small">Fuente: bitácora de auditoría (comparación antes/después por campo).</div>
</div>
<h2>Correcciones por usuario</h2>
<table class="pdf-t">
    <thead><tr><th>Usuario</th><th class="text-end">Correcciones</th></tr></thead>
    <tbody>
        <?php foreach ($porUsuario as $usuario => $cant): ?>
        <tr><td><?= esc($usuario !== '' ? $usuario : 'Desconocido') ?></td><td class="text-end"><?= (int) $cant ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
