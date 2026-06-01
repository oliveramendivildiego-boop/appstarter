<?php
$rows                     = $rows ?? [];
$resumen_lotes_por_insumo = $resumen_lotes_por_insumo ?? [];
$subtitle                 = $subtitle ?? '';
?>
<p class="small mb-1">Período: <?= esc($subtitle) ?><?php if (! empty($lista_truncada)): ?> · Lista truncada (límite <?= (int) ($limite_lista ?? 2500) ?> movimientos).<?php endif; ?></p>
<h2 style="font-size:10pt;margin:8px 0 4px 0;">Movimientos</h2>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Insumo</th>
            <th>Tipo</th>
            <th class="text-end">Cant.</th>
            <th>Lote</th>
            <th>Orden</th>
            <th>Usuario</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $m): ?>
            <?php
            $rid = (int) ($m['registro_id'] ?? 0);
            $ord = (string) (($m['numero_orden'] ?? '') ?: ($rid > 0 ? '#' . $rid : ''));
            ?>
            <tr>
                <td><?= esc($m['fecha'] ?? '') ?></td>
                <td class="small"><?= esc($m['reactivo_nombre'] ?? '') ?></td>
                <td><?= esc($m['tipo'] ?? '') ?></td>
                <td class="text-end"><?= esc((string) ($m['cantidad'] ?? '')) ?></td>
                <td><?= esc($m['codigo_lote'] ?? '') ?></td>
                <td><?= esc($ord) ?></td>
                <td class="small"><?= esc(trim((string) ($m['usuario_nombre'] ?? '')) ?: '-') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="7" class="small">Sin movimientos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($resumen_lotes_por_insumo !== []): ?>
    <h2 style="font-size:10pt;margin:12px 0 4px 0;">Resumen de lotes</h2>
    <?php foreach ($resumen_lotes_por_insumo as $bloque): ?>
        <p class="small mb-1"><strong><?= esc($bloque['nombre'] ?? '') ?></strong> (<?= esc($bloque['unidad'] ?? '') ?>)</p>
        <table class="pdf-t">
            <thead>
                <tr>
                    <th>Lote</th>
                    <th class="text-end">Ingreso</th>
                    <th class="text-end">Queda</th>
                    <th>Venc.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bloque['lotes'] ?? [] as $lt): ?>
                    <tr>
                        <td><?= esc($lt['codigo_lote'] ?? '') ?></td>
                        <td class="text-end"><?= (int) ($lt['ingreso'] ?? 0) ?></td>
                        <td class="text-end"><?= (int) ($lt['queda'] ?? 0) ?></td>
                        <td><?= ! empty($lt['fecha_vencimiento']) ? esc(lab_date((string) $lt['fecha_vencimiento'])) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($bloque['lotes'])): ?>
                    <tr><td colspan="4" class="small">Sin lotes.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
<?php endif; ?>
