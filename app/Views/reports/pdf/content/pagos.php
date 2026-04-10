<?php
$tipoPagoMap             = $tipoPagoMap ?? [];
$totales                 = $totales ?? (object) [];
$resumenPagosPorTipo     = $resumenPagosPorTipo ?? [];
$pagosPagados            = $pagosPagados ?? [];
$pendientes              = $pendientes ?? [];
$resumenPagosPorDia      = $resumenPagosPorDia ?? [];
$resumenPagosPorDoctor   = $resumenPagosPorDoctor ?? [];
$todos                   = $todos ?? [];
?>
<div class="alert-box">
    Total facturado: <?= number_format((float) ($totales->total_facturado ?? 0), 2) ?> Bs |
    Total cobrado: <?= number_format((float) ($totales->total_cobrado ?? 0), 2) ?> Bs |
    Pendiente: <?= number_format((float) ($totales->total_pendiente ?? 0), 2) ?> Bs |
    Órdenes: <?= (int) ($totales->total_registros ?? 0) ?>
</div>

<h2>Resumen por tipo de pago</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Tipo</th>
            <th class="text-end">Cant.</th>
            <th class="text-end">Fact.</th>
            <th class="text-end">Cobr.</th>
            <th class="text-end">Pend.</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resumenPagosPorTipo as $row): ?>
            <tr>
                <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_facturado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_cobrado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_pendiente'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($resumenPagosPorTipo === []): ?>
            <tr><td colspan="5" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Pagos pagados</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>No.</th>
            <th>Fecha</th>
            <th>Paciente</th>
            <th>Doctor</th>
            <th class="text-end">Total</th>
            <th class="text-end">Pagado</th>
            <th class="text-end">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pagosPagados as $row): ?>
            <tr>
                <td><?= esc($row['registro_id'] ?? '') ?></td>
                <td><?= esc(date('d/m/Y H:i', strtotime($row['ingreso'] ?? ''))) ?></td>
                <td class="small"><?= esc($row['paciente'] ?? '') ?></td>
                <td class="small"><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float) ($row['total'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['monto_pagado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['saldo'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($pagosPagados === []): ?>
            <tr><td colspan="7" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Pendientes de pago</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>No.</th>
            <th>Fecha</th>
            <th>Paciente</th>
            <th>Doctor</th>
            <th class="text-end">Total</th>
            <th class="text-end">Pagado</th>
            <th class="text-end">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pendientes as $row): ?>
            <tr>
                <td><?= esc($row['registro_id'] ?? '') ?></td>
                <td><?= esc(date('d/m/Y H:i', strtotime($row['ingreso'] ?? ''))) ?></td>
                <td class="small"><?= esc($row['paciente'] ?? '') ?></td>
                <td class="small"><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float) ($row['total'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['monto_pagado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['saldo'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($pendientes === []): ?>
            <tr><td colspan="7" class="small">Sin pendientes.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Resumen diario</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Fecha</th>
            <th class="text-end">Cant.</th>
            <th class="text-end">Fact.</th>
            <th class="text-end">Cobr.</th>
            <th class="text-end">Pend.</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resumenPagosPorDia as $row): ?>
            <tr>
                <td><?= esc(date('d/m/Y', strtotime($row['fecha'] ?? ''))) ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_facturado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_cobrado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_pendiente'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($resumenPagosPorDia === []): ?>
            <tr><td colspan="5" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Resumen por doctor</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Doctor</th>
            <th class="text-end">Cant.</th>
            <th class="text-end">Fact.</th>
            <th class="text-end">Cobr.</th>
            <th class="text-end">Pend.</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resumenPagosPorDoctor as $row): ?>
            <tr>
                <td class="small"><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_facturado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_cobrado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_pendiente'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($resumenPagosPorDoctor === []): ?>
            <tr><td colspan="5" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Todos los pagos</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>No.</th>
            <th>Fecha</th>
            <th>Paciente</th>
            <th class="text-end">Total</th>
            <th class="text-end">Saldo</th>
            <th>Tipo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($todos as $row): ?>
            <tr>
                <td><?= esc($row['registro_id'] ?? '') ?></td>
                <td><?= esc(date('d/m/Y H:i', strtotime($row['ingreso'] ?? ''))) ?></td>
                <td class="small"><?= esc($row['paciente'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float) ($row['total'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['saldo'] ?? 0), 2) ?></td>
                <td class="small"><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($todos === []): ?>
            <tr><td colspan="6" class="small">Sin registros.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
