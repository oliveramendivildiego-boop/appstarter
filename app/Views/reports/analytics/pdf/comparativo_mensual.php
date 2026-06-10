<?php
$rows    = $rows ?? [];
$totales = $totales ?? [];

$fmtVar = static function ($v): string {
    if ($v === null) {
        return '—';
    }
    $v = (float) $v;

    return ($v > 0 ? '+' : '') . number_format($v, 2) . '%';
};
?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Mes</th>
            <th class="text-end">Pacientes</th>
            <th class="text-end">Órdenes</th>
            <th class="text-end">Pruebas</th>
            <th class="text-end">Facturado</th>
            <th class="text-end">Cobrado</th>
            <th class="text-end">Var. vs mes anterior</th>
            <th class="text-end">Var. vs año anterior</th>
            <th class="text-end">Facturado acumulado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= esc($row['mes']) ?></td>
            <td class="text-end"><?= (int) $row['pacientes'] ?></td>
            <td class="text-end"><?= (int) $row['ordenes'] ?></td>
            <td class="text-end"><?= (int) $row['pruebas'] ?></td>
            <td class="text-end"><?= format_currency((float) $row['facturado']) ?></td>
            <td class="text-end"><?= format_currency((float) $row['cobrado']) ?></td>
            <td class="text-end"><?= esc($fmtVar($row['var_mes_facturado'])) ?></td>
            <td class="text-end"><?= esc($fmtVar($row['var_anio_facturado'])) ?></td>
            <td class="text-end"><?= format_currency((float) $row['acumulado_facturado']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="9" class="small">Sin datos en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($rows !== []): ?>
<div class="alert-box">
    Meses: <?= (int) ($totales['meses'] ?? 0) ?> |
    Pacientes (suma mensual): <?= (int) ($totales['pacientes'] ?? 0) ?> |
    Órdenes: <?= (int) ($totales['ordenes'] ?? 0) ?> |
    Pruebas: <?= (int) ($totales['pruebas'] ?? 0) ?> |
    Facturado: <?= format_currency((float) ($totales['facturado'] ?? 0)) ?> |
    Cobrado: <?= format_currency((float) ($totales['cobrado'] ?? 0)) ?>
    <div class="small">Pacientes únicos por mes. Órdenes anuladas excluidas. Variaciones sobre facturación.</div>
</div>
<?php endif; ?>
