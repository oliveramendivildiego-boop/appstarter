<?php
declare(strict_types=1);

$notesPreviewTitle = trim((string) ($ns['section_title'] ?? ''));
if ($notesPreviewTitle === '') {
    $notesPreviewTitle = 'NOTAS';
}
$notesShowTitle = ! empty($ns['show_section_title']);
?>
<div class="pdf-tpl-live-preview-sticky mb-4 mb-lg-0" id="pdf_notes_live_preview_wrap">
    <div class="pdf-preview-sheet border rounded shadow-sm bg-white">
        <div class="pdf-preview-sheet-bar small text-dark bg-warning px-2 py-1 d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-eye me-1"></i> Vista previa en tiempo real</span>
            <span class="badge bg-dark">Notas</span>
        </div>
        <div id="pdf_notes_live_preview_scope" class="pdf-tpl-live-preview-scope p-2">
            <div class="pdf-notes-block">
                <table class="results pdf-notes-table mb-0" width="100%" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr id="pdf_notes_preview_title_row"<?= $notesShowTitle ? '' : ' style="display:none;"' ?>>
                            <td class="pdf-notes-title" id="pdf_notes_preview_title"><?= esc($notesPreviewTitle) ?></td>
                        </tr>
                        <tr>
                            <td class="pdf-notes-cell">
                                El paciente debe repetir la muestra en ayunas. Resultado validado por el laboratorio de referencia.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <p class="small text-muted mt-2 mb-0">
        <i class="fa-solid fa-palette me-1"></i>
        Cambie <strong>Fondo título</strong>, <strong>Texto título</strong>, <strong>Fondo contenido</strong> y <strong>Texto contenido</strong> para ver el efecto aquí al instante.
    </p>
</div>
<style id="pdf_notes_live_preview_rules"></style>
