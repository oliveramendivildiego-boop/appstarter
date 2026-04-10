<?php
$data           = $data ?? [];
$totales        = $totales ?? (object) [];
$totalesAnulados = $totalesAnulados ?? null;
?>
<p class="small mb-1">Facturables excluyen anuladas. Columnas anuladas son referencia histórica.</p>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Fecha</th>
            <th class="text-end">Cant.</th>
            <th class="text-end">Total fact.</th>
            <th class="text-end">Cobrado</th>
            <th class="text-end">Anul. cant.</th>
            <th class="text-end">Anul. total</th>
            <th class="text-end">Anul. cobr.</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row):
            $nAnul = (int) ($row['cantidad_anuladas'] ?? 0);
            ?>
            <tr>
                <td><?= esc(date('d/m/Y', strtotime($row['fecha'] ?? ''))) ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['cobrado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= $nAnul > 0 ? $nAnul : '—' ?></td>
                <td class="text-end"><?= $nAnul > 0 ? number_format((float) ($row['total_anulado_ref'] ?? 0), 2) : '—' ?></td>
                <td class="text-end"><?= $nAnul > 0 ? number_format((float) ($row['cobrado_anulado_ref'] ?? 0), 2) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($data === []): ?>
            <tr><td colspan="7" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($data !== []): ?>
    <div class="alert-box">
        Registros facturables: <?= (int) ($totales->total_registros ?? 0) ?> |
        Total facturado: <?= number_format((float) ($totales->total_facturado ?? 0), 2) ?> Bs |
        Total cobrado: <?= number_format((float) ($totales->total_cobrado ?? 0), 2) ?> Bs
    </div>
<?php endif; ?>
<?php
$ta       = $totalesAnulados;
$nAnulTot = $ta ? (int) ($ta->total_registros ?? 0) : 0;
?>
<?php if ($nAnulTot > 0): ?>
    <div class="small">Órdenes anuladas en el período: <?= $nAnulTot ?> · Total hist. fact.: <?= number_format((float) ($ta->total_facturado ?? 0), 2) ?> Bs · Cobrado hist.: <?= number_format((float) ($ta->total_cobrado ?? 0), 2) ?> Bs</div>
<?php endif; ?>
