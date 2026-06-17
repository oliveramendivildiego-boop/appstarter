<?php
$tipoPagoMap             = $tipoPagoMap ?? [];
$totales                 = $totales ?? (object) [];
$resumenPagosPorTipo     = $resumenPagosPorTipo ?? [];
$pagosPagados            = $pagosPagados ?? [];
$pendientes              = $pendientes ?? [];
$resumenPagosPorDia      = $resumenPagosPorDia ?? [];
$resumenPagosPorDoctor   = $resumenPagosPorDoctor ?? [];
$todos                   = $todos ?? [];
$totalesIngresosCaja     = $totalesIngresosCaja ?? (object) [];
$resumenIngresosCajaPorTipo = $resumenIngresosCajaPorTipo ?? [];
$ingresosCajaMov         = $ingresosCajaMov ?? [];
$totalesEgresos          = $totalesEgresos ?? (object) [];
$resumenEgresosPorTipo   = $resumenEgresosPorTipo ?? [];
$egresos                 = $egresos ?? [];
$cajaResumen             = $cajaResumen ?? ['ingresos' => 0, 'egresos' => 0, 'saldo_neto' => 0, 'estado' => 'positivo'];
$cajaPorTipo             = $cajaPorTipo ?? [];
$cajaPorTipoTotales      = $cajaPorTipoTotales ?? ['ingresos' => 0, 'egresos' => 0, 'saldo_neto' => 0];
?>
<div class="alert-box">
    Cobrado (período): <?= format_currency((float) ($totales->total_cobrado ?? 0)) ?> |
    Cobros: <?= (int) ($totales->total_registros ?? 0) ?> |
    Órdenes: <?= (int) ($totales->cantidad_ordenes ?? 0) ?> |
    Facturado órdenes: <?= format_currency((float) ($totales->total_facturado ?? 0)) ?> |
    Saldo pendiente: <?= format_currency((float) ($totales->total_pendiente ?? 0)) ?>
</div>
<div class="alert-box">
    Cuadre de caja | Ingresos ventas: <?= format_currency((float) ($cajaResumen['ingresos_ventas'] ?? 0)) ?> |
    Ingresos caja: <?= format_currency((float) ($cajaResumen['ingresos_movimientos'] ?? 0)) ?> |
    Total ingresos: <?= format_currency((float) ($cajaResumen['ingresos'] ?? 0)) ?> |
    Egresos: <?= format_currency((float) ($cajaResumen['egresos'] ?? 0)) ?> |
    Saldo neto: <?= format_currency((float) ($cajaResumen['saldo_neto'] ?? 0)) ?>
</div>

<h2>Cuadre de caja por tipo de pago</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Tipo</th>
            <th class="text-end">Ingresos</th>
            <th class="text-end">Egresos</th>
            <th class="text-end">Total disponible</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cajaPorTipo as $row): ?>
            <tr>
                <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                <td class="text-end"><?= number_format((float) ($row['ingresos'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['egresos'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['saldo_neto'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($cajaPorTipo === []): ?>
            <tr><td colspan="4" class="small">Sin datos por tipo.</td></tr>
        <?php endif; ?>
        <tr>
            <td class="text-end"><strong>Total general</strong></td>
            <td class="text-end"><strong><?= number_format((float) ($cajaPorTipoTotales['ingresos'] ?? 0), 2) ?></strong></td>
            <td class="text-end"><strong><?= number_format((float) ($cajaPorTipoTotales['egresos'] ?? 0), 2) ?></strong></td>
            <td class="text-end"><strong><?= number_format((float) ($cajaPorTipoTotales['saldo_neto'] ?? 0), 2) ?></strong></td>
        </tr>
    </tbody>
</table>

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

<h2>Resumen por Procesamiento</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Procesamiento</th>
            <th class="text-end">Cant.</th>
            <th class="text-end">Fact.</th>
            <th class="text-end">Cobr.</th>
            <th class="text-end">Pend.</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resumenPagosPorProcesamiento ?? [] as $row): ?>
            <?php
            $procKey = (string) ($row['procesamiento'] ?? '');
            $procLabel = $procesamientoMap[$procKey] ?? $procKey ?: '-';
            ?>
            <tr>
                <td><?= esc($procLabel) ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_facturado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_cobrado'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_pendiente'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($resumenPagosPorProcesamiento ?? [])): ?>
            <tr><td colspan="5" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Resumen de ingresos de caja por tipo</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Tipo</th>
            <th class="text-end">Cant.</th>
            <th class="text-end">Ingresos</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resumenIngresosCajaPorTipo as $row): ?>
            <tr>
                <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_ingresos'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($resumenIngresosCajaPorTipo === []): ?>
            <tr><td colspan="3" class="small">Sin ingresos de caja.</td></tr>
        <?php endif; ?>
        <tr>
            <td colspan="2" class="text-end"><strong>Total ingresos de caja</strong></td>
            <td class="text-end"><strong><?= number_format((float) ($totalesIngresosCaja->total_ingresos ?? 0), 2) ?></strong></td>
        </tr>
    </tbody>
</table>

<h2>Detalle de ingresos de caja</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>No.</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Desglose</th>
            <th class="text-end">Monto</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($ingresosCajaMov as $row): ?>
            <tr>
                <td><?= (int) ($row['egreso_id'] ?? 0) ?></td>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta((string) ($row['fecha'] ?? ''))) ?></td>
                <td class="small"><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                <td class="small"><?= esc((string) ($row['desglose'] ?? '')) ?></td>
                <td class="text-end"><?= number_format((float) ($row['monto'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($ingresosCajaMov === []): ?>
            <tr><td colspan="5" class="small">Sin ingresos de caja.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Resumen de egresos por tipo</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Tipo</th>
            <th class="text-end">Cant.</th>
            <th class="text-end">Egresos</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resumenEgresosPorTipo as $row): ?>
            <tr>
                <td><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float) ($row['total_egresos'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($resumenEgresosPorTipo === []): ?>
            <tr><td colspan="3" class="small">Sin egresos.</td></tr>
        <?php endif; ?>
        <tr>
            <td colspan="2" class="text-end"><strong>Total egresos</strong></td>
            <td class="text-end"><strong><?= number_format((float) ($totalesEgresos->total_egresos ?? 0), 2) ?></strong></td>
        </tr>
    </tbody>
</table>

<h2>Detalle de egresos de caja</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>No.</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Desglose</th>
            <th class="text-end">Monto</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($egresos as $row): ?>
            <tr>
                <td><?= (int) ($row['egreso_id'] ?? 0) ?></td>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta((string) ($row['fecha'] ?? ''))) ?></td>
                <td class="small"><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                <td class="small"><?= esc((string) ($row['desglose'] ?? '')) ?></td>
                <td class="text-end"><?= number_format((float) ($row['monto'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($egresos === []): ?>
            <tr><td colspan="5" class="small">Sin egresos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Cobros en órdenes saldadas</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Orden</th>
            <th>Fecha cobro</th>
            <th class="text-end">Cobrado</th>
            <th>Paciente</th>
            <th class="text-end">Total</th>
            <th class="text-end">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pagosPagados as $row): ?>
            <tr>
                <td><?= esc(registro_orden_display($row)) ?></td>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_cobro'] ?? $row['ingreso'] ?? '')) ?></td>
                <td class="text-end"><?= number_format((float) ($row['monto_cobro'] ?? $row['monto_pagado'] ?? 0), 2) ?></td>
                <td class="small"><?= esc($row['paciente'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float) ($row['total'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['saldo'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($pagosPagados === []): ?>
            <tr><td colspan="6" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Cobros parciales (con saldo)</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Orden</th>
            <th>Fecha cobro</th>
            <th class="text-end">Cobrado</th>
            <th>Paciente</th>
            <th class="text-end">Total</th>
            <th class="text-end">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pendientes as $row): ?>
            <tr>
                <td><?= esc(registro_orden_display($row)) ?></td>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_cobro'] ?? $row['ingreso'] ?? '')) ?></td>
                <td class="text-end"><?= number_format((float) ($row['monto_cobro'] ?? 0), 2) ?></td>
                <td class="small"><?= esc($row['paciente'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float) ($row['total'] ?? 0), 2) ?></td>
                <td class="text-end"><?= number_format((float) ($row['saldo'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($pendientes === []): ?>
            <tr><td colspan="6" class="small">Sin cobros parciales.</td></tr>
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
                <td><?= esc(\App\Services\RegisterService::formatReportDate($row['fecha'] ?? '')) ?></td>
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

<h2>Todos los cobros del período</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Orden</th>
            <th>Fecha cobro</th>
            <th class="text-end">Cobrado</th>
            <th>Tipo</th>
            <th>Paciente</th>
            <th class="text-end">Saldo orden</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($todos as $row): ?>
            <tr>
                <td><?= esc(registro_orden_display($row)) ?></td>
                <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['fecha_cobro'] ?? $row['ingreso'] ?? '')) ?></td>
                <td class="text-end"><?= number_format((float) ($row['monto_cobro'] ?? $row['monto_pagado'] ?? 0), 2) ?></td>
                <td class="small"><?= esc($tipoPagoMap[$row['tipopago'] ?? ''] ?? $row['tipopago'] ?? '-') ?></td>
                <td class="small"><?= esc($row['paciente'] ?? '') ?></td>
                <td class="text-end"><?= number_format((float) ($row['saldo'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($todos === []): ?>
            <tr><td colspan="6" class="small">Sin cobros en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
