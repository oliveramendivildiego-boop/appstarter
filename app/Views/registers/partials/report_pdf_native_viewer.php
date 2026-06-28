<?php
/**
 * Visor PDF nativo del navegador (iframe). Mismo binario que registers/pdf.
 *
 * @var int    $ridPdf
 * @var string $pdf_url URL del PDF inline (registers/pdf/{id}?inline=1)
 */
$ridPdf = (int) ($ridPdf ?? 0);
$pdfUrl = (string) ($pdf_url ?? '');
if ($pdfUrl === '' && $ridPdf > 0) {
    $pdfUrl = site_url('registers/pdf/' . $ridPdf . '?inline=1&v=' . rawurlencode(\App\Libraries\Pdf\HtmlMpdfAdapter::CACHE_REVISION) . '&rid=' . $ridPdf);
}
?>
<div class="report-pdf-native-viewer is-loading" data-report-pdf-native-viewer data-pdf-url="<?= esc($pdfUrl, 'attr') ?>">
    <div class="report-pdf-native-frame-host">
        <div class="report-pdf-native-loading" data-pdf-native-loading aria-live="polite" aria-busy="true">
            <div class="report-pdf-native-loading-spinner" aria-hidden="true"></div>
            <p class="report-pdf-native-loading-text">Generando vista previa del análisis clínico…</p>
            <p class="report-pdf-native-loading-hint">Este análisis puede tardar unos segundos la primera vez.</p>
        </div>
        <div class="report-pdf-native-error" data-pdf-native-error role="alert">
            <p class="mb-0 fw-semibold">No se pudo cargar el PDF del reporte.</p>
            <p class="mb-0 small">El servidor no generó el archivo (error interno). Revise permisos de <code>writable/cache</code> en el servidor o contacte soporte.</p>
            <a href="<?= esc($pdfUrl, 'attr') ?>" class="btn btn-sm btn-warning mt-1" target="_blank" rel="noopener">Abrir PDF en nueva pestaña</a>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-pdf-native-retry>Reintentar</button>
        </div>
        <iframe
            class="report-pdf-native-frame"
            data-pdf-native-frame
            src="about:blank"
            title="Vista previa del reporte PDF"
            loading="eager"
        ></iframe>
    </div>
</div>
