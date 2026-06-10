<?php
$rows    = $rows ?? [];
$totales = $totales ?? [];
?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Ranking</th>
            <th>Usuario</th>
            <th class="text-end">Recepciones</th>
            <th class="text-end">Resultados cargados</th>
            <th class="text-end">Modificaciones</th>
            <th class="text-end">Validaciones</th>
            <th class="text-end">Impresiones</th>
            <th class="text-end">Envíos WhatsApp</th>
            <th class="text-end">Total actividad</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= (int) $row['ranking'] ?></td>
            <td><?= esc($row['usuario']) ?></td>
            <td class="text-end"><?= (int) $row['recepciones'] ?></td>
            <td class="text-end"><?= (int) $row['resultados_cargados'] ?></td>
            <td class="text-end"><?= (int) $row['modificaciones'] ?></td>
            <td class="text-end"><?= (int) $row['validaciones'] ?></td>
            <td class="text-end"><?= (int) $row['impresiones'] ?></td>
            <td class="text-end"><?= (int) $row['envios_whatsapp'] ?></td>
            <td class="text-end"><strong><?= (int) $row['total_actividad'] ?></strong></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="9" class="small">Sin actividad en el período.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php if ($rows !== []): ?>
<div class="alert-box">
    Usuarios: <?= (int) ($totales['usuarios'] ?? 0) ?> |
    Recepciones: <?= (int) ($totales['recepciones'] ?? 0) ?> |
    Resultados cargados: <?= (int) ($totales['resultados_cargados'] ?? 0) ?> |
    Modificaciones: <?= (int) ($totales['modificaciones'] ?? 0) ?> |
    Validaciones: <?= (int) ($totales['validaciones'] ?? 0) ?>
</div>
<?php endif; ?>
