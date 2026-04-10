<?php
$pendientes   = $pendientes ?? [];
$total_saldo  = (float) ($total_saldo ?? 0);
?>
<div class="alert-box">
    <?= count($pendientes) ?> orden(es) con saldo pendiente · Total pendiente: <?= number_format($total_saldo, 2) ?> Bs
</div>
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
