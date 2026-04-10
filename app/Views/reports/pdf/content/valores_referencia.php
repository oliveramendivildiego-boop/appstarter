<?php
$data = $data ?? [];
$pobL = [1 => 'Recién nacido', 2 => 'Niño', 3 => 'Adulto', 4 => 'Adulto mayor', 5 => 'Embarazada'];
?>
<p class="small mb-1">Filas con valor mín/máx definido (mismo criterio que exportación CSV).</p>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Categoría</th>
            <th>Prueba</th>
            <th>Análisis</th>
            <th>Población</th>
            <th>Sexo</th>
            <th class="text-end">Mín</th>
            <th class="text-end">Máx</th>
            <th>Unidad</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $item): ?>
            <?php if (empty($item['valor_min']) && empty($item['valor_max'])) {
                continue;
            } ?>
            <?php
            $pid = (int) ($item['poblacion'] ?? 3);
            $pob = $pobL[$pid] ?? 'Adulto';
            $sx  = (string) ($item['sexo'] ?? 'ambos');
            $sxL = $sx === 'masculino' ? 'M' : ($sx === 'femenino' ? 'F' : 'Ambos');
            ?>
            <tr>
                <td class="small"><?= esc($item['categoria'] ?? '') ?></td>
                <td class="small"><?= esc($item['prueba'] ?? '') ?></td>
                <td class="small"><?= esc($item['analisis'] ?? '') ?></td>
                <td><?= esc($pob) ?></td>
                <td><?= esc($sxL) ?></td>
                <td class="text-end"><?= esc((string) ($item['valor_min'] ?? '')) ?></td>
                <td class="text-end"><?= esc((string) ($item['valor_max'] ?? '')) ?></td>
                <td><?= esc((string) ($item['umedida'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
