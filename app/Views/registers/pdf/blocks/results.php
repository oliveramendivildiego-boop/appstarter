<?php
declare(strict_types=1);

foreach ($grupos ?? [] as $padre => $items) {
    echo view('registers/analisis/partials/compleja_tabla_reporte_grupo', [
        'padre'   => $padre,
        'items'   => $items,
        'variant' => 'pdf',
    ]);
}
