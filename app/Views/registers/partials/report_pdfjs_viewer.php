<?php
/**
 * Visor PDF.js embebido (mismo PDF que genera Dompdf en registers/pdf).
 *
 * @var int    $ridPdf
 * @var string $pdf_url URL del PDF inline (registers/pdf/{id}?inline=1)
 */
$ridPdf = (int) ($ridPdf ?? 0);
$pdfUrl = (string) ($pdf_url ?? '');
if ($pdfUrl === '' && $ridPdf > 0) {
    $pdfUrl = site_url('registers/pdf/' . $ridPdf . '?inline=1');
}
?>
<div class="report-pdfjs-viewer is-loading"
     data-report-pdfjs-viewer
     data-pdf-url="<?= esc($pdfUrl, 'attr') ?>"
     data-worker-src="<?= esc(asset_url('js/vendor/pdfjs/pdf.worker.min.js'), 'attr') ?>"
     data-initial-scale="1.35">
    <div class="report-pdfjs-toolbar" role="toolbar" aria-label="Controles del visor PDF">
        <div class="report-pdfjs-toolbar-group">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-pdfjs-prev title="Página anterior" aria-label="Página anterior">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <span class="report-pdfjs-page-indicator small text-muted" data-pdfjs-page-label>Pág. 1 de —</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-pdfjs-next title="Página siguiente" aria-label="Página siguiente">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
        <div class="report-pdfjs-toolbar-group">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-pdfjs-zoom-out title="Alejar" aria-label="Alejar">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
            </button>
            <span class="report-pdfjs-zoom-label small text-muted" data-pdfjs-zoom-label>135%</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-pdfjs-zoom-in title="Acercar" aria-label="Acercar">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-pdfjs-fit-width title="Ajustar al ancho" aria-label="Ajustar al ancho">
                <i class="fa-solid fa-arrows-left-right"></i>
            </button>
        </div>
        <div class="report-pdfjs-toolbar-note small text-muted">
            Vista previa del PDF generado (misma salida que descarga e impresión)
        </div>
    </div>
    <div class="report-pdfjs-status small text-muted" data-pdfjs-status hidden></div>
    <div class="report-pdfjs-canvas-host" data-pdfjs-canvas-host tabindex="0" aria-label="Vista previa del reporte PDF">
        <div class="report-pdfjs-loading" data-pdfjs-loading aria-live="polite" aria-busy="true">
            <div class="report-pdfjs-loading-spinner" aria-hidden="true"></div>
            <p class="report-pdfjs-loading-text" data-pdfjs-loading-text>Generando vista previa del PDF…</p>
            <p class="report-pdfjs-loading-hint">Dompdf puede tardar unos segundos la primera vez.</p>
        </div>
    </div>
</div>
