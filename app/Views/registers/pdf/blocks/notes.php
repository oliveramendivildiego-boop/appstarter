<?php
declare(strict_types=1);

$notaResultado = trim((string) ($register_info->comentario_resultado ?? ''));
if ($notaResultado === '') {
    return;
}
$pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$ps = is_array($pl['page_style'] ?? null) ? $pl['page_style'] : \App\Services\ReportPdfLayoutService::defaultPageStyleStatic();
$ns = \App\Services\ReportPdfLayoutService::normalizeNotesStyle($ps['notes'] ?? []);
$showTitle = \App\Services\ReportPdfLayoutService::labFirmasBool($ns, 'show_section_title', true);
$secTitle  = (string) ($ns['section_title'] ?? 'NOTAS');
?>
<div class="pdf-notes-block" style="margin-top:10px;">
<?php if ($showTitle): ?>
    <div class="group-title pdf-notes-title"><?= esc($secTitle) ?></div>
<?php endif; ?>
    <table class="results pdf-notes-table" style="width:100%;border-collapse:collapse;margin-top:0;">
        <tbody>
            <tr>
                <td class="pdf-notes-cell" style="white-space:pre-wrap;"><?= esc($notaResultado) ?></td>
            </tr>
        </tbody>
    </table>
</div>
