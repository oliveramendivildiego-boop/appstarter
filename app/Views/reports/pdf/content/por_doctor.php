<?php
$data    = $data ?? [];
$totales = $totales ?? (object) [];
?>
<p class="small mb-1">Totales excluyen órdenes anuladas.</p>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Doctor</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">Total facturado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
            <tr>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total'] ?? 0), 2) ?> Bs</td>
            </tr>
        <?php endforeach; ?>
        <?php if ($data === []): ?>
            <tr><td colspan="3" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($data !== []): ?>
    <div class="alert-box">
        Registros facturables: <?= (int) ($totales->total_registros ?? 0) ?> |
        Total facturado: <?= number_format((float) ($totales->total_facturado ?? 0), 2) ?> Bs
    </div>
<?php endif; ?>
