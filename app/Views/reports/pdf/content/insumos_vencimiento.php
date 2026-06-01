<?php
$data        = $data ?? [];
$dias_alerta = (int) ($dias_alerta ?? 40);
?>
<p class="small mb-1">Alerta vencimiento: <?= $dias_alerta ?> días. Leyenda: Vencido / Por vencer / Sin fecha / OK.</p>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Insumo</th>
            <th>Lote</th>
            <th>Ing.</th>
            <th>Venc.</th>
            <th class="text-end">Cant.</th>
            <th>Unidad</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row):
            $estado = $row['estado'] ?? 'ok';
            $lbl    = $row['estado_label'] ?? '';
            ?>
            <tr>
                <td class="small"><?= esc($row['reactivo_nombre'] ?? '-') ?></td>
                <td><?= esc($row['codigo_lote'] ?? '-') ?></td>
                <td><?= ! empty($row['fecha_ingreso']) ? esc(lab_date((string) $row['fecha_ingreso'])) : '-' ?></td>
                <td><?= ! empty($row['fecha_vencimiento']) ? esc(lab_date((string) $row['fecha_vencimiento'])) : '-' ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td><?= esc($row['unidad_base'] ?? $row['unidad'] ?? '-') ?></td>
                <td class="small"><?= esc($lbl !== '' ? $lbl : $estado) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($data === []): ?>
            <tr><td colspan="7" class="small">Sin lotes.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
