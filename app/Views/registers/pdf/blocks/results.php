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
foreach ($grupos ?? [] as $padre => $items) {
    $isFirstGrupo = ($grupoPruebaIdx === 0);
    $grupoClass = 'report-pdf-grupo-prueba';
    if ($isFirstGrupo) {
        $grupoClass .= ' report-pdf-grupo-prueba-first';
    }
    $useBrowserPrintAreaStart = ($av === 'browser_print')
        && \App\Services\ReportPdfLayoutService::shouldRenderGrupoAreaPageLeader($layoutForLf, $isFirstGrupo);
    if ($useBrowserPrintAreaStart) {
        $grupoClass .= ' report-pdf-grupo-browser-print-area';
    }
    $grupoStyle = \App\Services\ReportPdfLayoutService::mergePdfInlineStyleAttrs(
        \App\Services\ReportPdfLayoutService::grupoPruebaGrupoIntactStyleAttr($layoutForLf, $isFirstGrupo),
        \App\Services\ReportPdfLayoutService::grupoPruebaGapMarginStyleAttr($layoutForLf, $isFirstGrupo),
        \App\Services\ReportPdfLayoutService::grupoPruebaBrowserPrintAreaStyleAttr($layoutForLf, $isFirstGrupo, $av)
    );
    if (! $useBrowserPrintAreaStart && \App\Services\ReportPdfLayoutService::shouldRenderGrupoAreaPageLeader($layoutForLf, $isFirstGrupo)) {
        $leaderStyle = \App\Services\ReportPdfLayoutService::grupoAreaPageLeaderStyleAttr($layoutForLf, $isFirstGrupo);
        echo '<div class="report-pdf-grupo-area-page-leader" aria-hidden="true"'
            . ($leaderStyle !== '' ? ' style="' . esc($leaderStyle, 'attr') . '"' : '')
            . '></div>';
    }
    echo '<div class="' . esc($grupoClass, 'attr') . '"'
        . ($grupoStyle !== '' ? ' style="' . esc($grupoStyle, 'attr') . '"' : '')
        . '>';
    if ($useBrowserPrintAreaStart) {
        echo view('registers/analisis/partials/report_grupo_area_browser_print_start', [
            'padre'      => $padre,
            'pdf_layout' => $layoutForLf,
        ]);
    } else {
        echo view('registers/analisis/partials/report_grupo_area_separator', [
            'padre'            => $padre,
            'pdf_layout'       => $layoutForLf,
            'grupo_es_primero' => $isFirstGrupo,
            'variant'          => $av,
        ]);
    }
    echo view('registers/analisis/partials/compleja_tabla_reporte_grupo', [
        'padre'   => $padre,
        'items'   => $items,
        'variant' => $av,
        'pdf_layout' => $layoutForLf,
        'grupo_es_primero' => $isFirstGrupo,
        'lab_config' => $lab_config ?? [],
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
