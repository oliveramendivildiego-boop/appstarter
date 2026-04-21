<?php
declare(strict_types=1);

$av = $analisis_variant ?? 'pdf';

$grupoPruebaIdx = 0;
foreach ($grupos ?? [] as $padre => $items) {
    $grupoClass = 'report-pdf-grupo-prueba';
    if ($grupoPruebaIdx === 0) {
        $grupoClass .= ' report-pdf-grupo-prueba-first';
    }
    echo '<div class="' . esc($grupoClass, 'attr') . '">';
    echo view('registers/analisis/partials/compleja_tabla_reporte_grupo', [
        'padre'   => $padre,
        'items'   => $items,
        'variant' => $av,
        'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
        'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
        'report_pria_refs_consolidada'    => $report_pria_refs_consolidada ?? [],
    ]);
    echo '</div>';
    $grupoPruebaIdx++;
}
