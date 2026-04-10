<?php
$data    = $data ?? [];
$totales = $totales ?? (object) [];
?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>No.</th>
            <th>Estado</th>
            <th>Fecha</th>
            <th>Paciente</th>
            <th>Doctor</th>
            <th class="text-end">Total</th>
            <th class="text-end">Cobrado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row):
            $del  = (int) ($row['estado_eliminado'] ?? 0) === 1;
            $anul = (int) ($row['estado_anulado'] ?? 0) === 1;
            $rvn  = (int) ($row['regvalues_cnt'] ?? 0);
            if ($del) {
                $estLabel = 'Eliminada';
            } elseif ($anul) {
                $estLabel = 'Anulada';
            } elseif ($rvn > 0) {
                $estLabel = 'Completa';
            } else {
                $estLabel = 'Incompleta';
            }
            $facturable   = ! $del && ! $anul;
            $montoTotal   = $facturable ? (float) ($row['total'] ?? 0) : 0.0;
            $montoCobrado = $facturable ? (float) ($row['monto_pagar'] ?? 0) : 0.0;
            ?>
            <tr>
                <td><?= esc($row['registro_id'] ?? '') ?></td>
                <td><?= esc($estLabel) ?></td>
                <td><?= esc(date('d/m/Y H:i', strtotime($row['ingreso'] ?? ''))) ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($row['doctor'] ?? '') ?></td>
                <td class="text-end"><?= number_format($montoTotal, 2) ?> Bs</td>
                <td class="text-end"><?= number_format($montoCobrado, 2) ?> Bs</td>
            </tr>
        <?php endforeach; ?>
        <?php if ($data === []): ?>
            <tr><td colspan="7" class="small">Sin registros en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($data !== []): ?>
    <div class="alert-box">
        Registros facturables: <?= (int) ($totales->total_registros ?? 0) ?> |
        Total facturado: <?= number_format((float) ($totales->total_facturado ?? 0), 2) ?> Bs |
        Total cobrado: <?= number_format((float) ($totales->total_cobrado ?? 0), 2) ?> Bs
    </div>
<?php endif; ?>
