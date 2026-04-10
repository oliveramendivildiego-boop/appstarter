<?php
$resumen            = $resumen ?? [];
$porGeneroOrdenes   = $porGeneroOrdenes ?? ['1' => 0, '2' => 0, '_' => 0];
$porGeneroPacientes = $porGeneroPacientes ?? ['1' => 0, '2' => 0, '_' => 0];
$poblacionGrupoRows = $poblacionGrupoRows ?? [];
$porPruebaRows      = $porPruebaRows ?? [];
$prTot              = (int) ($resumen['pruebas_realizadas'] ?? 0);
?>
<div class="alert-box">
    Pruebas realizadas: <?= $prTot ?> |
    Pacientes distintos: <?= (int) ($resumen['pacientes_distintos'] ?? 0) ?> |
    Órdenes: <?= (int) ($resumen['ordenes'] ?? 0) ?>
</div>

<h2>Desglose por prueba</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Prueba</th>
            <th>Categoría</th>
            <th class="text-end">Órdenes</th>
            <th class="text-end">Pacientes</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($porPruebaRows as $p): ?>
            <tr>
                <td><?= esc($p['prueba'] ?? '') ?></td>
                <td class="small"><?= esc(($p['categoria'] ?? '') !== '' ? (string) $p['categoria'] : '—') ?></td>
                <td class="text-end"><?= (int) ($p['ordenes_con_prueba'] ?? 0) ?></td>
                <td class="text-end"><?= (int) ($p['pacientes_distintos'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($porPruebaRows === []): ?>
            <tr><td colspan="4" class="small">Sin pruebas en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

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
            <th class="text-end">Órdenes</th>
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
