<?php
$rows    = $rows ?? [];
$totales = $totales ?? [];
?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Ranking</th>
            <th>Código</th>
            <th>Prueba</th>
            <th>Grupo</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">% del total</th>
            <th class="text-end">Ingreso estimado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= (int) $row['ranking'] ?></td>
            <td><?= (int) $row['codigo'] ?></td>
            <td><?= esc($row['prueba']) ?></td>
            <td><?= esc($row['grupo']) ?></td>
            <td class="text-end"><?= (int) $row['cantidad'] ?></td>
            <td class="text-end"><?= number_format((float) $row['porcentaje'], 2) ?>%</td>
            <td class="text-end"><?= format_currency((float) $row['ingreso']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="7" class="small">Sin pruebas solicitadas en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($rows !== []): ?>
<div class="alert-box">
    Órdenes del período: <?= (int) ($totales['total_ordenes'] ?? 0) ?> |
    Total pruebas solicitadas: <?= (int) ($totales['total_pruebas'] ?? 0) ?> |
    Ingreso estimado total: <?= format_currency((float) ($totales['total_ingresos'] ?? 0)) ?>
    <div class="small">Ingreso estimado = cantidad × precio de catálogo vigente. Órdenes anuladas excluidas.</div>
</div>
<?php endif; ?>
