<?php $cierres = $cierres ?? []; ?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>#</th>
            <th>Período</th>
            <th>Registrado</th>
            <th>Elaborado por</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cierres as $c):
            $id     = (int) ($c['cierre_id'] ?? 0);
            $fd     = $c['fecha_desde'] ?? '';
            $fh     = $c['fecha_hasta'] ?? '';
            $periodo = ($fd && $fh)
                ? (date('d/m/Y', strtotime($fd)) . ' — ' . date('d/m/Y', strtotime($fh)))
                : '-';
            $creado = ! empty($c['created_at']) ? date('d/m/Y H:i', strtotime($c['created_at'])) : '-';
            $por    = trim((string) ($c['elaborado_nombre'] ?? ''));
            ?>
            <tr>
                <td><?= $id ?></td>
                <td><?= esc($periodo) ?></td>
                <td><?= esc($creado) ?></td>
                <td><?= esc($por !== '' ? $por : '—') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($cierres === []): ?>
            <tr><td colspan="4" class="small">Sin cierres registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
