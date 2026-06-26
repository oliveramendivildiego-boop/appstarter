<?php
/**
 * Visor PDF nativo del navegador (iframe). Mismo binario que Dompdf en registers/pdf.
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
<div class="report-pdf-native-viewer is-loading" data-report-pdf-native-viewer>
    <div class="report-pdf-native-frame-host">
        <div class="report-pdf-native-loading" data-pdf-native-loading aria-live="polite" aria-busy="true">
            <div class="report-pdf-native-loading-spinner" aria-hidden="true"></div>
            <p class="report-pdf-native-loading-text">Generando vista previa del análisis clínico…</p>
            <p class="report-pdf-native-loading-hint">Este análisis puede tardar unos segundos la primera vez.</p>
        </div>
        <iframe
            class="report-pdf-native-frame"
            data-pdf-native-frame
            src="<?= esc($pdfUrl, 'attr') ?>"
            title="Vista previa del reporte PDF"
            loading="eager"
        ></iframe>
    </div>
</div>
