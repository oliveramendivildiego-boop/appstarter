<?php
$data           = $data ?? [];
$totales        = $totales ?? (object) [];
$totalesAnulados = $totalesAnulados ?? null;
$totalesCobrosCaja = $totalesCobrosCaja ?? (object) [];
$totalesPagos = $totalesPagos ?? (object) [];
?>
<p class="small mb-1">Por fecha de ingreso. Cobrado = abonos del período en órdenes ingresadas en el período. Facturables excluyen anuladas.</p>
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
                <td><?= esc(\App\Services\RegisterService::formatReportDate($row['fecha'] ?? '')) ?></td>
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
        Registros ingresados en el período: <?= (int) ($totales->total_registros ?? 0) ?> |
        Total facturado (ingreso): <?= format_currency((float) ($totales->total_facturado ?? 0)) ?> |
        Total cobrado (órdenes del período): <?= format_currency((float) ($totales->total_cobrado ?? 0)) ?> |
        Órdenes con cobro en el período: <?= (int) ($totalesPagos->cantidad_ordenes ?? 0) ?> |
        Cobros totales del período (caja): <?= format_currency((float) ($totalesCobrosCaja->total_cobrado ?? 0)) ?>
    </div>
<?php endif; ?>
<?php
$ta       = $totalesAnulados;
$nAnulTot = $ta ? (int) ($ta->total_registros ?? 0) : 0;
?>
<?php if ($nAnulTot > 0): ?>
    <div class="small">Órdenes anuladas en el período: <?= $nAnulTot ?> · Total hist. fact.: <?= format_currency((float) ($ta->total_facturado ?? 0)) ?> · Cobrado hist.: <?= format_currency((float) ($ta->total_cobrado ?? 0)) ?></div>
<?php endif; ?>
