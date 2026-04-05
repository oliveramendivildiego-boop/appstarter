<?php if (! empty($grupos)): ?>
    <div class="container mt-4">
        <?php foreach ($grupos as $padre => $items): ?>
            <?= view('registers/analisis/partials/compleja_tabla_reporte_grupo', ['padre' => $padre, 'items' => $items]) ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>No hay análisis disponibles.</p>
<?php endif; ?>
