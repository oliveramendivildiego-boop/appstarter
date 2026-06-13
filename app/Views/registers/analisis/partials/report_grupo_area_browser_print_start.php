<?php
/**
 * Inicio de área para impresión directa (navegador): título dentro del grupo (sin salto propio).
 * El salto de hoja va en .report-pdf-grupo-browser-print-area; la tabla evita recorte del título en Chrome.
 *
 * @var string               $padre
 * @var array<string,mixed>  $pdf_layout
 */
$pdfLayout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$ps = is_array($pdfLayout['page_style'] ?? null) ? $pdfLayout['page_style'] : [];
$rs = \App\Services\ReportPdfLayoutService::normalizeResultsTableStyle($ps['results_table'] ?? []);
$gapPx = max(0, min(80, (int) ($rs['grupo_prueba_gap_px'] ?? 10)));
$sepTopPx = max(0, min(80, (int) ($rs['grupo_area_separator_margin_top_px'] ?? 10)));
$sepBottomPx = max(0, min(80, (int) ($rs['grupo_area_separator_margin_bottom_px'] ?? 10)));
$spacerPx = $gapPx + $sepTopPx;
$hasSeparator = \App\Services\ReportPdfLayoutService::grupoAreaSeparatorEnabled($pdfLayout);
$titulo = $hasSeparator
    ? \App\Services\ReportPdfLayoutService::buildGrupoAreaSeparatorTitle((string) ($padre ?? ''), $pdfLayout)
    : '';
?>
<table class="report-pdf-grupo-area-start-table" role="presentation" width="100%" cellpadding="0" cellspacing="0"
    style="width:100%;border-collapse:collapse;margin:0;padding:0;border:0;">
<tbody>
<?php if ($spacerPx > 0): ?>
<tr class="report-pdf-grupo-area-start-spacer" aria-hidden="true">
    <td style="height:<?= (int) $spacerPx ?>px;padding:0;margin:0;border:0;font-size:0;line-height:0;">&#160;</td>
</tr>
<?php endif; ?>
<?php if ($hasSeparator && $titulo !== ''): ?>
<tr class="report-pdf-grupo-area-start-title-row">
    <td style="padding:0;margin:0;border:0;vertical-align:top;">
        <div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator"
            style="margin:0 0 <?= (int) $sepBottomPx ?>px 0;padding:6px 8px;"><?= esc($titulo) ?></div>
    </td>
</tr>
<?php endif; ?>
</tbody>
</table>
