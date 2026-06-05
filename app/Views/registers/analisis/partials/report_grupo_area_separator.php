<?php
/**
 * Separador horizontal con nombre del área (.report-pdf-grupo-area-separator).
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
$wrapStyle = \App\Services\ReportPdfLayoutService::mergePdfInlineStyleAttrs(
    \App\Services\ReportPdfLayoutService::grupoAreaSeparatorMarginStyleAttr($pdfLayout, $isFirstGrupoInReport),
    \App\Services\ReportPdfLayoutService::grupoAreaSeparatorInlineStyleAttr($pdfLayout)
);
?>
<div class="report-pdf-grupo-area-separator"<?= $wrapStyle !== '' ? ' style="' . esc($wrapStyle, 'attr') . '"' : '' ?>>
    <table class="report-pdf-grupo-area-separator-table" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="report-pdf-grupo-area-separator-line-cell report-pdf-grupo-area-separator-line-cell-left">
                <div class="report-pdf-grupo-area-separator-line"></div>
            </td>
            <td class="report-pdf-grupo-area-separator-label"><?= esc($titulo) ?></td>
            <td class="report-pdf-grupo-area-separator-line-cell report-pdf-grupo-area-separator-line-cell-right">
                <div class="report-pdf-grupo-area-separator-line"></div>
            </td>
        </tr>
    </table>
</div>
