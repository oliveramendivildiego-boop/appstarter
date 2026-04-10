<?php
$secciones          = $secciones ?? [];
$ordenes_en_periodo = (int) ($ordenes_en_periodo ?? 0);
?>
<p class="small mb-1">Órdenes en el período (completas, no anuladas): <?= $ordenes_en_periodo ?></p>
<?php if ($secciones === []): ?>
    <p class="small">Sin datos para el filtro seleccionado.</p>
<?php endif; ?>
<?php foreach ($secciones as $sec): ?>
    <h2><?= esc($sec['nombre'] ?? '') ?> — Total: <?= (int) ($sec['total_pruebas'] ?? 0) ?></h2>
    <table class="pdf-t">
        <thead>
            <tr>
                <th>Análisis</th>
                <th class="text-end">Veces solicitado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sec['detalle'] ?? [] as $fila): ?>
                <tr>
                    <td><?= esc($fila['prueba'] ?? '') ?></td>
                    <td class="text-end"><?= (int) ($fila['cantidad'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>
