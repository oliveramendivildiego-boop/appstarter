<?php
$rows    = $rows ?? [];
$totales = $totales ?? [];
$calidad = $totales['calidad'] ?? [];
$fmtHoras = static fn ($h) => $h === null ? '—' : number_format((float) $h, 1) . ' h';
?>
<div class="alert-box">
    Notificaciones: <?= (int) ($totales['total_notificaciones'] ?? 0) ?> |
    Análisis entregados: <?= (int) ($totales['total_analisis'] ?? 0) ?> |
    Promedio validación → entrega: <?= esc($fmtHoras($totales['promedio_horas'] ?? null)) ?> |
    ≤12h: <?= (int) ($calidad['dentro_12'] ?? 0) ?><?= ($calidad['pct_dentro_12'] ?? null) !== null ? ' (' . number_format((float) $calidad['pct_dentro_12'], 1) . '%)' : '' ?> |
    12–24h: <?= (int) ($calidad['entre_12_24'] ?? 0) ?> |
    &gt;24h: <?= (int) ($calidad['mayor_24'] ?? 0) ?>
</div>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Validación</th>
            <th>Entrega</th>
            <th>Horas</th>
            <th>Análisis</th>
            <th>Paciente</th>
            <th>Registro</th>
            <th>Usuario</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= ! empty($row['validated_at']) ? esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['validated_at'])) : '—' ?></td>
            <td><?= ! empty($row['fecha_entrega']) ? esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_entrega'])) : '—' ?></td>
            <td><?= esc($fmtHoras($row['horas_transcurridas'] ?? null)) ?></td>
            <td><?= esc($row['analisis_codigo'] ?? '') ?></td>
            <td><?= esc($row['paciente'] ?? '') ?></td>
            <td><?= esc($row['registro_codigo'] ?? '') ?></td>
            <td><?= esc($row['usuario'] ?? '') ?></td>
            <td><?= esc($row['estado'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="8" class="small">Sin entregas confirmadas en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
