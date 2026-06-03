<?php
declare(strict_types=1);

$av = $analisis_variant ?? 'pdf';

$layoutForLf = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$lfStyle = \App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle(
    is_array($layoutForLf['page_style']['lab_firmas'] ?? null) ? $layoutForLf['page_style']['lab_firmas'] : []
);
$labFirmasEnabled = \App\Services\ReportPdfLayoutService::isLabFirmasBlockEnabled($layoutForLf);
$showFirmaPerGroup = $labFirmasEnabled && \App\Services\ReportPdfLayoutService::labFirmasPlacementShowsPerGroup($lfStyle);

$firmasPorPadre = [];
if ($showFirmaPerGroup) {
    foreach ($report_lab_firmas ?? [] as $firmaRow) {
        if (! is_array($firmaRow)) {
            continue;
        }
        $areaNombre = trim((string) ($firmaRow['prueba_nombre'] ?? ''));
        if ($areaNombre !== '') {
            $firmasPorPadre[$areaNombre] = $firmaRow;
        }
    }
}

$grupoPruebaIdx = 0;
$grupoIntactStyle = \App\Services\ReportPdfLayoutService::grupoPruebaGrupoIntactStyleAttr($layoutForLf);
foreach ($grupos ?? [] as $padre => $items) {
    $grupoClass = 'report-pdf-grupo-prueba';
    if ($grupoPruebaIdx === 0) {
        $grupoClass .= ' report-pdf-grupo-prueba-first';
    }
    echo '<div class="' . esc($grupoClass, 'attr') . '"'
        . ($grupoIntactStyle !== '' ? ' style="' . esc($grupoIntactStyle, 'attr') . '"' : '')
        . '>';
    echo view('registers/analisis/partials/compleja_tabla_reporte_grupo', [
        'padre'   => $padre,
        'items'   => $items,
        'variant' => $av,
        'pdf_layout' => $layoutForLf,
        'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
        'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
        'report_pria_refs_consolidada'    => $report_pria_refs_consolidada ?? [],
    ]);
    $padreKey = trim((string) $padre);
    if ($showFirmaPerGroup && $padreKey !== '' && isset($firmasPorPadre[$padreKey])) {
        echo view('registers/partials/report_lab_firma_grupo_inline', [
            'firma'             => $firmasPorPadre[$padreKey],
            'area_label'        => $padreKey,
            'analisis_variant'  => $av,
            'pdf_layout'        => $pdf_layout ?? [],
            'lab_config'        => $lab_config ?? [],
            'lab_firmas_style'  => $lfStyle,
        ]);
    }
    echo '</div>';
    $grupoPruebaIdx++;
}
