<?php
helper('registro');
$resumen    = $resumen ?? [];
$subtotales = $subtotales ?? [];
$detalle    = $detalle ?? [];
$top_derivadas = $top_derivadas ?? [];
$grupos = ['interno' => 'Interno', 'derivado' => 'Derivado'];
$lineasPorGrupo = ['interno' => [], 'derivado' => []];
foreach ($detalle as $line) {
    $tipo = (string) ($line['tipo_procesamiento'] ?? 'interno');
    if (! isset($lineasPorGrupo[$tipo])) {
        $lineasPorGrupo[$tipo] = [];
    }
    $lineasPorGrupo[$tipo][] = $line;
}
?>
<div class="alert-box">
    Total pruebas: <?= (int) ($resumen['total_pruebas'] ?? 0) ?> |
    Facturado: <?= format_currency((float) ($resumen['total_facturado'] ?? 0)) ?> |
    Cobrado: <?= format_currency((float) ($resumen['total_cobrado'] ?? 0)) ?> |
    Pendiente: <?= format_currency((float) ($resumen['total_pendiente'] ?? 0)) ?><br>
    Internas: <?= (int) ($resumen['total_pruebas_internas'] ?? 0) ?> |
    Derivadas: <?= (int) ($resumen['total_pruebas_derivadas'] ?? 0) ?> |
    % derivadas: <?= number_format((float) ($resumen['pct_pruebas_derivadas'] ?? 0), 2) ?>% |
    % ingresos derivados: <?= number_format((float) ($resumen['pct_ingresos_derivados'] ?? 0), 2) ?>%
</div>

<?php if ($top_derivadas !== []): ?>
<h2>Top 10 pruebas más derivadas</h2>
<table class="pdf-t">
    <thead>
        <tr><th>#</th><th>Prueba</th><th class="text-end">Cant.</th><th class="text-end">%</th></tr>
    </thead>
    <tbody>
        <?php foreach ($top_derivadas as $row): ?>
        <tr>
            <td><?= (int) ($row['ranking'] ?? 0) ?></td>
            <td><?= esc($row['prueba'] ?? '') ?></td>
            <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
            <td class="text-end"><?= number_format((float) ($row['porcentaje'] ?? 0), 2) ?>%</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>Detalle por tipo de procesamiento</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Orden</th>
            <th>Paciente</th>
            <th>Prueba</th>
            <th>Tipo</th>
            <th>Lab. derivado</th>
            <th class="text-end">Importe</th>
            <th class="text-end">Cobrado</th>
            <th class="text-end">Saldo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($detalle === []): ?>
        <tr><td colspan="10" class="small">Sin datos.</td></tr>
        <?php else: ?>
            <?php foreach ($grupos as $tipoKey => $tipoLabel): ?>
                <?php $lineas = $lineasPorGrupo[$tipoKey] ?? []; ?>
                <?php if ($lineas === []) { continue; } ?>
                <tr><td colspan="10" class="small"><strong><?= esc($tipoLabel) ?></strong></td></tr>
                <?php foreach ($lineas as $line): ?>
                <?php
                $codigo = trim((string) ($line['codigo_orden'] ?? ''));
                if ($codigo === '' && ! empty($line['registro_id'])) {
                    $codigo = registro_codigo_recepcion_display($line, false);
                }
                ?>
                <tr>
                    <td><?= esc(\App\Services\RegisterService::formatReportDate($line['fecha'] ?? '')) ?></td>
                    <td><?= esc($codigo !== '' ? $codigo : '—') ?></td>
                    <td><?= esc($line['paciente'] ?? '') ?></td>
                    <td><?= esc($line['prueba'] ?? '') ?></td>
                    <td><?= esc($line['tipo_label'] ?? '') ?></td>
                    <td><?= esc($line['laboratorio_derivado'] ?? '—') ?></td>
                    <td class="text-end"><?= number_format((float) ($line['importe'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($line['monto_cobrado'] ?? 0), 2) ?></td>
                    <td class="text-end"><?= number_format((float) ($line['saldo_pendiente'] ?? 0), 2) ?></td>
                    <td><?= esc($line['estado_label'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php $st = $subtotales[$tipoKey] ?? []; ?>
                <tr>
                    <td colspan="6" class="text-end"><strong>Subtotal <?= esc($tipoLabel) ?></strong></td>
                    <td class="text-end"><strong><?= number_format((float) ($st['importe'] ?? 0), 2) ?></strong></td>
                    <td class="text-end"><strong><?= number_format((float) ($st['cobrado'] ?? 0), 2) ?></strong></td>
                    <td class="text-end"><strong><?= number_format((float) ($st['pendiente'] ?? 0), 2) ?></strong></td>
                    <td><?= (int) ($st['cantidad'] ?? 0) ?> pr.</td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="6" class="text-end"><strong>TOTAL GENERAL</strong></td>
                <td class="text-end"><strong><?= number_format((float) ($resumen['total_facturado'] ?? 0), 2) ?></strong></td>
                <td class="text-end"><strong><?= number_format((float) ($resumen['total_cobrado'] ?? 0), 2) ?></strong></td>
                <td class="text-end"><strong><?= number_format((float) ($resumen['total_pendiente'] ?? 0), 2) ?></strong></td>
                <td><?= (int) ($resumen['total_pruebas'] ?? 0) ?> pr.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
