<?php
$rows       = $rows ?? [];
$porUsuario = $porUsuario ?? [];
$porArea    = $porArea ?? [];
$total      = (int) ($total ?? 0);
?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Orden</th>
            <th>Paciente</th>
            <th>Fecha recepción</th>
            <th>Primer resultado</th>
            <th>Usuario que cargó</th>
            <th class="text-end">Tiempo pendiente</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row):
            $horas = (float) ($row['horas_pendiente'] ?? 0);
            $dias  = floor($horas / 24);
            $tiempoLabel = $dias >= 1 ? $dias . ' d ' . number_format(fmod($horas, 24), 0) . ' h' : number_format($horas, 1) . ' h';
        ?>
        <tr>
            <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
            <td><?= esc($row['paciente']) ?></td>
            <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
            <td><?= $row['fecha_resultado'] ? esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_resultado'])) : '—' ?></td>
            <td><?= esc($row['usuario_cargo']) ?></td>
            <td class="text-end"><?= esc($tiempoLabel) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="6" class="small">Sin resultados pendientes de validar en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($rows !== []): ?>
<div class="alert-box">Total de órdenes pendientes de validar: <?= $total ?></div>
<h2>Pendientes por usuario</h2>
<table class="pdf-t">
    <thead><tr><th>Usuario</th><th class="text-end">Pendientes</th></tr></thead>
    <tbody>
        <?php foreach ($porUsuario as $usuario => $cant): ?>
        <tr><td><?= esc($usuario) ?></td><td class="text-end"><?= (int) $cant ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<h2>Pendientes por área</h2>
<table class="pdf-t">
    <thead><tr><th>Área</th><th class="text-end">Pendientes</th></tr></thead>
    <tbody>
        <?php foreach ($porArea as $area => $cant): ?>
        <tr><td><?= esc($area) ?></td><td class="text-end"><?= (int) $cant ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
