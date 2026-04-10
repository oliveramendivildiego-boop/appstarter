<?php
$data               = $data ?? [];
$anulacionDisponible = ! empty($anulacionDisponible);
?>
<?php if (! $anulacionDisponible): ?>
    <p class="small">Este reporte no aplica: falta campo de anulación en registro.</p>
<?php else: ?>
    <table class="pdf-t">
        <thead>
            <tr>
                <th>No.</th>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th>Pruebas</th>
                <th>Motivo</th>
                <th class="text-end">Total ref.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <?php $mot = trim((string) ($row['motivo_anulacion'] ?? '')); ?>
                <tr>
                    <td><?= esc($row['registro_id'] ?? '') ?></td>
                    <td><?= esc(date('d/m/Y H:i', strtotime($row['ingreso'] ?? ''))) ?></td>
                    <td><?= esc($row['paciente'] ?? '') ?></td>
                    <td><?= esc($row['doctor'] ?? '') ?></td>
                    <td class="small"><?= esc($row['pruebas_nombres'] ?? '-') ?></td>
                    <td class="small"><?= $mot !== '' ? esc(preg_replace('/\s+/', ' ', $mot)) : '—' ?></td>
                    <td class="text-end"><?= number_format((float) ($row['total'] ?? 0), 2) ?> Bs</td>
                </tr>
            <?php endforeach; ?>
            <?php if ($data === []): ?>
                <tr><td colspan="7" class="small">Sin datos.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>
