<?php
$resumen            = $resumen ?? [];
$porGeneroOrdenes   = $porGeneroOrdenes ?? ['1' => 0, '2' => 0, '_' => 0];
$porGeneroPacientes = $porGeneroPacientes ?? ['1' => 0, '2' => 0, '_' => 0];
$poblacionGrupoRows = $poblacionGrupoRows ?? [];
$porPruebaRows      = $porPruebaRows ?? [];
$snis               = $snis ?? [];
$kpis               = $snis['kpis'] ?? [];
$ejecutivo          = $snis['resumen_ejecutivo'] ?? [];
$indicadores        = $snis['indicadores'] ?? [];
$pruebasSolicitadas = (int) ($resumen['pruebas_solicitadas'] ?? ($resumen['pruebas_realizadas'] ?? 0));
$pruebasProcesadas  = (int) ($resumen['pruebas_procesadas'] ?? 0);
?>
<div class="alert-box">
    Tablero SNIS — Pruebas solicitadas: <?= $pruebasSolicitadas ?> |
    Procesadas: <?= $pruebasProcesadas ?> |
    Pacientes: <?= (int) ($resumen['pacientes_distintos'] ?? 0) ?> |
    Órdenes sol./proc.: <?= (int) ($resumen['ordenes_solicitadas'] ?? 0) ?> / <?= (int) ($resumen['ordenes_procesadas'] ?? 0) ?>
    <?php if (isset($kpis['porcentaje_procesamiento'])): ?> | % proc.: <?= esc((string) $kpis['porcentaje_procesamiento']) ?>%<?php endif; ?>
</div>

<?php if (! empty($ejecutivo['bullets'])): ?>
<h2>Resumen ejecutivo</h2>
<ul class="small">
    <?php foreach ($ejecutivo['bullets'] as $b): ?>
    <li><?= esc($b) ?></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<h2>Indicadores operativos</h2>
<table class="pdf-t">
    <tbody>
        <tr><td>Órdenes pendientes de validación</td><td class="text-end"><?= (int) ($indicadores['ordenes_pendientes'] ?? 0) ?></td></tr>
        <tr><td>Órdenes anuladas</td><td class="text-end"><?= (int) ($indicadores['ordenes_anuladas'] ?? 0) ?></td></tr>
        <tr><td>Resultados corregidos</td><td class="text-end"><?= (int) ($indicadores['resultados_corregidos'] ?? 0) ?></td></tr>
        <tr><td>Resultados validados</td><td class="text-end"><?= (int) ($indicadores['resultados_validados'] ?? 0) ?></td></tr>
        <?php $tat = $indicadores['tat'] ?? []; if (! empty($tat['promedio_resultado'])): ?>
        <tr><td>TAT promedio resultado (h)</td><td class="text-end"><?= esc((string) $tat['promedio_resultado']) ?></td></tr>
        <?php endif; ?>
        <?php if (! empty($tat['promedio_validacion'])): ?>
        <tr><td>TAT promedio validación (h)</td><td class="text-end"><?= esc((string) $tat['promedio_validacion']) ?></td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Desglose por prueba</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Prueba</th>
            <th>Categoría</th>
            <th class="text-end">Órdenes con la prueba</th>
            <th class="text-end">Órdenes procesadas</th>
            <th class="text-end">Pacientes</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($porPruebaRows as $p): ?>
            <tr>
                <td><?= esc($p['prueba'] ?? '') ?></td>
                <td class="small"><?= esc(($p['categoria'] ?? '') !== '' ? (string) $p['categoria'] : '—') ?></td>
                <td class="text-end"><?= (int) ($p['ordenes_con_prueba'] ?? 0) ?></td>
                <td class="text-end"><?= (int) ($p['ordenes_procesadas'] ?? 0) ?></td>
                <td class="text-end"><?= (int) ($p['pacientes_distintos'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($porPruebaRows === []): ?>
            <tr><td colspan="5" class="small">Sin pruebas en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if (! empty($indicadores['por_categoria'])): ?>
<h2>Producción por categoría</h2>
<table class="pdf-t">
    <thead><tr><th>Categoría</th><th class="text-end">Solicitadas</th><th class="text-end">Procesadas</th></tr></thead>
    <tbody>
    <?php foreach ($indicadores['por_categoria'] as $c): ?>
        <tr>
            <td><?= esc($c['categoria'] ?? '') ?></td>
            <td class="text-end"><?= (int) ($c['solicitadas'] ?? 0) ?></td>
            <td class="text-end"><?= (int) ($c['procesadas'] ?? 0) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h2>Por grupo poblacional</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Grupo</th>
            <th>Edad</th>
            <th class="text-end">Órdenes</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($poblacionGrupoRows as $fila): ?>
            <tr>
                <td><?= esc($fila['nombre'] ?? '') ?></td>
                <td class="small"><?= esc($fila['rango_edad'] ?? '') ?></td>
                <td class="text-end"><?= (int) ($fila['ordenes'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($poblacionGrupoRows === []): ?>
            <tr><td colspan="3" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Por género</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Género</th>
            <th class="text-end">Pacientes únicos</th>
            <th class="text-end">Órdenes procesadas</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Masculino</td>
            <td class="text-end"><?= (int) ($porGeneroPacientes['1'] ?? 0) ?></td>
            <td class="text-end"><?= (int) ($porGeneroOrdenes['1'] ?? 0) ?></td>
        </tr>
        <tr>
            <td>Femenino</td>
            <td class="text-end"><?= (int) ($porGeneroPacientes['2'] ?? 0) ?></td>
            <td class="text-end"><?= (int) ($porGeneroOrdenes['2'] ?? 0) ?></td>
        </tr>
        <tr>
            <td>No indicado</td>
            <td class="text-end"><?= (int) ($porGeneroPacientes['_'] ?? 0) ?></td>
            <td class="text-end"><?= (int) ($porGeneroOrdenes['_'] ?? 0) ?></td>
        </tr>
    </tbody>
</table>

<?php if (! empty($snis['limitaciones'])): ?>
<p class="small text-muted">Notas: <?php foreach ($snis['limitaciones'] as $lim) { echo esc($lim['mensaje'] ?? '') . ' '; } ?></p>
<?php endif; ?>
