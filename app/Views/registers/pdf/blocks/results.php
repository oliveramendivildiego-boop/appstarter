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

$pdfPbService = ($av === 'pdf')
    ? \App\Services\ReportPdfDompdfGrupoPageBreakService::create(
        $layoutForLf,
        \App\Services\ReportPdfLayoutService::estimatePdfHeaderBeforeResultsMm($layoutForLf)
    )
    : null;
$usePdfPbService = $pdfPbService !== null && $pdfPbService->isActive();
if ($usePdfPbService) {
    $pdfPbService->setRefsConsolidada(
        is_array($report_pria_refs_consolidada ?? null) ? $report_pria_refs_consolidada : []
    );
}

$grupoPruebaIdx = 0;
$gruposList = is_array($grupos ?? null) ? $grupos : [];
$totalGrupos = count($gruposList);
$reportForzarColRef = in_array($av, ['pdf', 'screen_pdf', 'browser_print'], true)
    && \App\Services\ReportPdfLayoutService::reportGruposTienenRangoReferencial($gruposList);
foreach ($gruposList as $padre => $items) {
    $isFirstGrupo = ($grupoPruebaIdx === 0);
    $isLastGrupo  = ($grupoPruebaIdx >= $totalGrupos - 1);
    $padreKey = trim((string) $padre);
    $itemsList = is_array($items) ? $items : [];
    $hasFirmaEnGrupo = $showFirmaPerGroup && $padreKey !== '' && isset($firmasPorPadre[$padreKey]);

    $pbMeta = [
        'classes'            => '',
        'grupo_style'        => '',
        'inter_break'        => false,
        'area_page_leader'   => false,
        'separator_new_page' => false,
        'apply_compact'      => false,
    ];
    if ($usePdfPbService) {
        $pbMeta = array_merge($pbMeta, $pdfPbService->beginGrupo($isFirstGrupo, $itemsList, $hasFirmaEnGrupo));
    }

    $useInterPageBreak = ! empty($pbMeta['inter_break']) && empty($pb_diag_no_separators);
    $useAreaPageLeader = ! empty($pbMeta['area_page_leader']) && empty($pb_diag_no_separators);

    $grupoClass = 'report-pdf-grupo-prueba';
    if ($isFirstGrupo) {
        $grupoClass .= ' report-pdf-grupo-prueba-first';
    }
    if ($pbMeta['classes'] !== '') {
        $grupoClass .= ' ' . $pbMeta['classes'];
    }

    $applyCompactPdf = ! empty($pbMeta['apply_compact']);
    $compactAttrs = \App\Services\ReportPdfLayoutService::grupoPruebaCompactPdfAttrs(
        $layoutForLf,
        $applyCompactPdf
    );
    if ($compactAttrs['class'] !== '') {
        $grupoClass .= ' ' . $compactAttrs['class'];
    }

    $grupoExtraStyle = '';
    if ($useInterPageBreak && $av !== 'pdf') {
        if (! str_contains($grupoClass, 'report-pdf-grupo-prueba-new-page-start')) {
            $grupoClass .= ' report-pdf-grupo-prueba-new-page-start';
        }
        $grupoExtraStyle = 'page-break-before:avoid;break-before:avoid;margin-top:0;padding-top:0;';
    }
    $useBrowserPrintAreaStart = false;
    if ($useBrowserPrintAreaStart) {
        $grupoClass .= ' report-pdf-grupo-browser-print-area';
    }
    $grupoStyle = \App\Services\ReportPdfLayoutService::mergePdfInlineStyleAttrs(
        ($av === 'pdf' && ! $isFirstGrupo && $useInterPageBreak)
            ? ''
            : \App\Services\ReportPdfLayoutService::grupoPruebaGapMarginStyleAttr($layoutForLf, $isFirstGrupo),
        \App\Services\ReportPdfLayoutService::grupoPruebaBrowserPrintAreaStyleAttr($layoutForLf, $isFirstGrupo, $av),
        $pbMeta['grupo_style'] ?? '',
        $compactAttrs['style'] ?? '',
        $grupoExtraStyle
    );
    if (! $useBrowserPrintAreaStart && $av !== 'pdf' && $useAreaPageLeader) {
        $leaderStyle = \App\Services\ReportPdfLayoutService::grupoAreaPageLeaderStyleAttr($layoutForLf, $isFirstGrupo);
        echo '<div class="report-pdf-grupo-area-page-leader" aria-hidden="true"'
            . ($leaderStyle !== '' ? ' style="' . esc($leaderStyle, 'attr') . '"' : '')
            . '></div>';
    }
    if ($av === 'browser_print' && $useInterPageBreak) {
        echo '<div class="report-grupo-inter-page-break report-grupo-inter-page-break-server" aria-hidden="true"></div>';
    }
    if ($av === 'pdf' && $useInterPageBreak) {
        echo '<div class="report-grupo-inter-page-break report-grupo-inter-page-break-server report-grupo-inter-page-break-pdf" aria-hidden="true" style="'
            . esc(\App\Services\ReportPdfLayoutService::grupoInterPageBreakStyleAttr(), 'attr')
            . '"></div>';
    }
    echo '<div class="' . esc(trim($grupoClass), 'attr') . '"'
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
            'grupo_inicia_nueva_pagina' => false,
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
        'pdf_grupo_pb_service'            => $usePdfPbService ? $pdfPbService : null,
        'report_forzar_col_ref'           => $reportForzarColRef,
    ]);
    if ($hasFirmaEnGrupo) {
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
