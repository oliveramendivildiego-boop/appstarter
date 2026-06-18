<?php
/**
 * Separador con nombre del área (mismo estilo que .report-segment-title.pdf-card-header).
 *
 * @var string $padre Nombre del área (grupo)
 * @var array<string,mixed>|null $pdf_layout
 * @var bool   $grupo_es_primero Si es el primer área del reporte
 * @var string $variant 'web' | 'pdf' | 'screen_pdf' | 'browser_print'
 */
$variant = $variant ?? 'pdf';
$usePdfChrome = in_array($variant, ['pdf', 'screen_pdf', 'browser_print'], true);
if (! $usePdfChrome) {
    return;
}

$pdfLayout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
if (! \App\Services\ReportPdfLayoutService::grupoAreaSeparatorEnabled($pdfLayout)) {
    return;
}

$titulo = \App\Services\ReportPdfLayoutService::buildGrupoAreaSeparatorTitle(
    (string) ($padre ?? ''),
    $pdfLayout
);
if ($titulo === '') {
    return;
}

$wrapStyle = \App\Services\ReportPdfLayoutService::grupoAreaSeparatorMarginStyleAttr($pdfLayout, (string) $variant);
$separatorClass = 'report-segment-title pdf-card-header report-pdf-grupo-area-separator';
if (! empty($grupo_inicia_nueva_pagina) && $variant === 'pdf') {
    $separatorClass .= ' report-pdf-grupo-area-new-page';
    $wrapStyle = \App\Services\ReportPdfLayoutService::mergePdfInlineStyleAttrs(
        $wrapStyle,
        'page-break-before:always;break-before:page;'
    );
}
?>
<div class="<?= esc($separatorClass, 'attr') ?>"<?= $wrapStyle !== '' ? ' style="' . esc($wrapStyle, 'attr') . '"' : '' ?>><?= esc($titulo) ?></div>
