<?php
/**
 * Separador con nombre del área (mismo estilo que .report-segment-title.pdf-card-header).
 *
 * @var string $padre Nombre del área (grupo)
 * @var array<string,mixed>|null $pdf_layout
 * @var bool   $grupo_es_primero Si es el primer área del reporte
 * @var string $variant 'web' | 'pdf' | 'screen_pdf'
 */
$variant = $variant ?? 'pdf';
$usePdfChrome = ($variant === 'pdf' || $variant === 'screen_pdf');
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

$isFirstGrupoInReport = ! empty($grupo_es_primero);
$wrapStyle = \App\Services\ReportPdfLayoutService::grupoAreaSeparatorMarginStyleAttr(
    $pdfLayout,
    $isFirstGrupoInReport
);
?>
<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator"<?= $wrapStyle !== '' ? ' style="' . esc($wrapStyle, 'attr') . '"' : '' ?>><?= esc($titulo) ?></div>
