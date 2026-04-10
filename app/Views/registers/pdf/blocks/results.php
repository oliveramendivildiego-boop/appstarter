<?php
declare(strict_types=1);

$av = $analisis_variant ?? 'pdf';

foreach ($grupos ?? [] as $padre => $items) {
    echo view('registers/analisis/partials/compleja_tabla_reporte_grupo', [
        'padre'   => $padre,
        'items'   => $items,
        'variant' => $av,
        'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
        'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
    ]);
}
