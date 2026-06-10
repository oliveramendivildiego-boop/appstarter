<?php
$porPrueba   = $porPrueba ?? [];
$porReactivo = $porReactivo ?? [];
$porDia      = $porDia ?? [];
$porMes      = $porMes ?? [];
$totales     = $totales ?? [];
?>
<h2>Consumo por prueba y reactivo</h2>
<table class="pdf-t">
    <thead>
        <tr><th>Prueba</th><th>Reactivo</th><th>Unidad</th><th class="text-end">Eventos</th><th class="text-end">Cantidad consumida</th></tr>
    </thead>
    <tbody>
        <?php foreach ($porPrueba as $row): ?>
        <tr>
            <td><?= esc($row['prueba']) ?></td>
            <td><?= esc($row['reactivo']) ?></td>
            <td><?= esc($row['unidad']) ?></td>
            <td class="text-end"><?= (int) $row['eventos'] ?></td>
            <td class="text-end"><?= number_format((float) $row['cantidad_total'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($porPrueba === []): ?>
            <tr><td colspan="5" class="small">Sin consumo automático por prueba en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Consumo por reactivo (salidas de inventario)</h2>
<table class="pdf-t">
    <thead>
        <tr><th>Reactivo</th><th>Unidad</th><th class="text-end">Movimientos</th><th class="text-end">Cantidad consumida</th></tr>
    </thead>
    <tbody>
        <?php foreach ($porReactivo as $row): ?>
        <tr>
            <td><?= esc($row['reactivo']) ?></td>
            <td><?= esc($row['unidad']) ?></td>
            <td class="text-end"><?= (int) $row['movimientos'] ?></td>
            <td class="text-end"><?= number_format((float) $row['cantidad_total'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($porReactivo === []): ?>
            <tr><td colspan="4" class="small">Sin salidas de inventario en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Consumo mensual</h2>
<table class="pdf-t">
    <thead><tr><th>Mes</th><th>Reactivo</th><th class="text-end">Cantidad</th></tr></thead>
    <tbody>
        <?php foreach ($porMes as $row): ?>
        <tr>
            <td><?= esc($row['periodo']) ?></td>
            <td><?= esc($row['reactivo']) ?></td>
            <td class="text-end"><?= number_format((float) $row['cantidad_total'], 2) ?> <?= esc($row['unidad']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($porMes === []): ?>
            <tr><td colspan="3" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="alert-box">
    Reactivos con consumo: <?= (int) ($totales['reactivos'] ?? 0) ?> |
    Combinaciones prueba-reactivo: <?= (int) ($totales['combinaciones'] ?? 0) ?> |
    Total unidades consumidas: <?= number_format((float) ($totales['total_consumido'] ?? 0), 2) ?>
    <div class="small">Costo estimado no disponible: el módulo de insumos no registra costos de compra.</div>
</div>
