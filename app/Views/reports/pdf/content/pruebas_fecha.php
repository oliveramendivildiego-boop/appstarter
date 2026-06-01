<?php $data = $data ?? []; ?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>No.</th>
            <th>Fecha</th>
            <th>Paciente</th>
            <th>Doctor</th>
            <th>Pruebas</th>
            <th class="text-end">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
            <tr>
                <td><?= esc($row['registro_id'] ?? '') ?></td>
                <td><?= esc(lab_dt_short($row['ingreso'] ?? null)) ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="small"><?= esc($row['pruebas_nombres'] ?? '-') ?></td>
                <td class="text-end"><?= format_currency((float) ($row['total'] ?? 0)) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($data === []): ?>
            <tr><td colspan="6" class="small">Sin órdenes completas en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
