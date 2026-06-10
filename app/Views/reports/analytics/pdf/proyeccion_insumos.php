<?php
$rows      = $rows ?? [];
$porEstado = $porEstado ?? [];
$ventana   = (int) ($ventana ?? 30);

$estadoLabel = static fn (string $estado): string => match ($estado) {
    'critico'     => 'CRÍTICO',
    'advertencia' => 'Advertencia',
    'normal'      => 'Normal',
    'sin_stock'   => 'Sin stock',
    default       => 'Sin consumo',
};
?>
<div class="alert-box">
    Críticos: <?= (int) ($porEstado['critico'] ?? 0) ?> |
    Advertencia: <?= (int) ($porEstado['advertencia'] ?? 0) ?> |
    Normales: <?= (int) ($porEstado['normal'] ?? 0) ?> |
    Sin stock: <?= (int) ($porEstado['sin_stock'] ?? 0) ?> |
    Sin consumo: <?= (int) ($porEstado['sin_consumo'] ?? 0) ?>
</div>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Reactivo</th>
            <th>Unidad</th>
            <th class="text-end">Stock actual</th>
            <th class="text-end">Stock mínimo</th>
            <th class="text-end">Consumo promedio/día</th>
            <th class="text-end">Días restantes</th>
            <th>Fecha estimada de agotamiento</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= esc($row['nombre']) ?></td>
            <td><?= esc($row['unidad']) ?></td>
            <td class="text-end"><?= number_format((float) $row['stock_actual'], 0) ?></td>
            <td class="text-end"><?= number_format((float) $row['stock_minimo'], 0) ?></td>
            <td class="text-end"><?= number_format((float) $row['consumo_promedio_dia'], 2) ?></td>
            <td class="text-end"><?= $row['dias_restantes'] !== null ? (int) $row['dias_restantes'] : '—' ?></td>
            <td><?= esc($row['fecha_agotamiento'] ?? '—') ?></td>
            <td><?= esc($estadoLabel((string) $row['estado'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="8" class="small">Sin reactivos registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<div class="small">Stock actual = suma de lotes activos. Consumo promedio = salidas de los últimos <?= $ventana ?> días ÷ <?= $ventana ?>. Crítico: ≤ 7 días o stock bajo mínimo. Advertencia: ≤ 30 días.</div>
