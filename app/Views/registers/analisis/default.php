<?php if (! empty($grupos)): ?>
    <?php
    $reportForzarColRefDefault = \App\Services\ReportPdfLayoutService::reportGruposTienenRangoReferencial(
        is_array($grupos) ? $grupos : []
    );
    ?>
    <div class="container mt-4">
        <?php foreach ($grupos as $padre => $items): ?>
            <?= view('registers/analisis/partials/compleja_tabla_reporte_grupo', [
                'padre' => $padre,
                'items' => $items,
                'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
                'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
                'report_pria_refs_consolidada'    => $report_pria_refs_consolidada ?? [],
                'report_forzar_col_ref'           => $reportForzarColRefDefault,
            ]) ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>No hay análisis disponibles.</p>
<?php endif; ?>
