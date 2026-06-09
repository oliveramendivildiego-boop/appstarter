<?php
helper('registro');
$data = $data ?? [];
?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Cód. recepción</th>
            <th>Fecha</th>
            <th>Paciente</th>
            <th>Usuario recepción</th>
            <th>Primera carga</th>
            <th>Última edición</th>
            <th>Pruebas</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
            <tr>
                <td><?= esc(registro_codigo_recepcion_display($row, false)) ?></td>
                <td><?= esc(lab_dt_short($row['ingreso'] ?? null)) ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td class="small"><?= esc(trim((string) ($row['usuario_recepcion'] ?? '')) ?: '—') ?></td>
                <td class="small"><?= esc(trim((string) ($row['usuario_primera_carga'] ?? '')) ?: '—') ?></td>
                <td class="small"><?= esc(trim((string) ($row['usuario_ultima_edicion'] ?? '')) ?: '—') ?></td>
                <td class="small"><?= esc($row['pruebas_nombres'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($data === []): ?>
            <tr><td colspan="7" class="small">Sin órdenes completas en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
