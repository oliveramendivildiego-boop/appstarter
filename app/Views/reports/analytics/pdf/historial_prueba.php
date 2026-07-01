<?php
$rows                    = $rows ?? [];
$solicitadasSinResultado = $solicitadas_sin_resultado ?? [];
$prueba                  = $prueba ?? null;
?>
<?php if ($prueba !== null): ?>
<div class="mb-1">
    <strong>Prueba:</strong> <?= esc($prueba['name'] ?? '') ?>
    <?php if (($prueba['grupo'] ?? '') !== ''): ?> · <strong>Grupo:</strong> <?= esc($prueba['grupo']) ?><?php endif; ?>
    · <strong>Solicitadas:</strong> <?= (int) ($total_solicitadas ?? 0) ?>
    · <strong>Con resultado:</strong> <?= (int) ($total_ordenes ?? 0) ?>
    · <strong>Solo solicitadas:</strong> <?= (int) ($total_pendientes ?? 0) ?>
    · <strong>Resultados listados:</strong> <?= (int) ($total_resultados ?? 0) ?>
</div>
<?php endif; ?>

<?php if ($rows !== []): ?>
<div class="mb-1 fw-bold">Órdenes con resultado</div>
<table class="pdf-t mb-2">
    <thead>
        <tr>
            <th>Tipo</th>
            <th>Fecha</th>
            <th>Orden</th>
            <th>Paciente</th>
            <th>CI</th>
            <th>Parámetro</th>
            <th class="text-end">Resultado</th>
            <th>Unidad</th>
            <th class="text-end">Ref. mín</th>
            <th class="text-end">Ref. máx</th>
            <th>Estado</th>
            <th>Médico solicitante</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td>Con resultado</td>
            <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
            <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
            <td><?= esc($row['paciente']) ?></td>
            <td><?= esc($row['paciente_ci'] ?: '—') ?></td>
            <td><?= esc($row['parametro']) ?></td>
            <td class="text-end"><?= esc($row['valor']) ?></td>
            <td><?= esc($row['unidad']) ?></td>
            <td class="text-end"><?= esc($row['valor_min']) ?></td>
            <td class="text-end"><?= esc($row['valor_max']) ?></td>
            <td><?= esc(\App\Controllers\ReportsAnalytics::estadoValorLabel((string) $row['estado'])) ?></td>
            <td><?= esc($row['doctor'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ($solicitadasSinResultado !== []): ?>
<div class="mb-1 fw-bold">Órdenes solo solicitadas (sin resultado)</div>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Tipo</th>
            <th>Fecha</th>
            <th>Orden</th>
            <th>Paciente</th>
            <th>CI</th>
            <th>Médico solicitante</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($solicitadasSinResultado as $row): ?>
        <tr>
            <td>Solo solicitada</td>
            <td><?= esc(\App\Services\RegisterService::formatStoredReporteFechaCorta($row['ingreso'] ?? '')) ?></td>
            <td><?= esc($row['numero_orden'] ?: $row['registro_id']) ?></td>
            <td><?= esc($row['paciente']) ?></td>
            <td><?= esc($row['paciente_ci'] ?: '—') ?></td>
            <td><?= esc($row['doctor'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ($rows === [] && $solicitadasSinResultado === []): ?>
<table class="pdf-t">
    <tbody>
        <tr><td class="small">Sin órdenes en el período.</td></tr>
    </tbody>
</table>
<?php endif; ?>
