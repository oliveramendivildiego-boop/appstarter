<?php
$secciones             = $secciones ?? [];
$ordenesSolicitadas    = (int) ($ordenes_solicitadas ?? $ordenes_en_periodo ?? 0);
$ordenesConResultado   = (int) ($ordenes_con_resultado ?? 0);
?>
<p class="small mb-1">
    Órdenes solicitadas en el período: <?= $ordenesSolicitadas ?>
    · Con al menos un resultado del grupo: <?= $ordenesConResultado ?>
</p>
<?php if ($secciones === []): ?>
    <p class="small">Sin datos para el filtro seleccionado.</p>
<?php endif; ?>
<?php foreach ($secciones as $sec): ?>
    <h2><?= esc($sec['nombre'] ?? '') ?> — Solicitadas: <?= (int) ($sec['total_solicitadas'] ?? $sec['total_pruebas'] ?? 0) ?> · Con resultado: <?= (int) ($sec['total_con_resultado'] ?? 0) ?></h2>
    <table class="pdf-t">
        <thead>
            <tr>
                <th>Análisis</th>
                <th class="text-end">Veces solicitado</th>
                <th class="text-end">Órdenes con resultado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sec['detalle'] ?? [] as $fila): ?>
                <tr>
                    <td><?= esc($fila['prueba'] ?? '') ?></td>
                    <td class="text-end"><?= (int) ($fila['veces_solicitado'] ?? $fila['cantidad'] ?? 0) ?></td>
                    <td class="text-end"><?= (int) ($fila['veces_con_resultado'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>
