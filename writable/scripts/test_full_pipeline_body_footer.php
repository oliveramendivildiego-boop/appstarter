<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 143);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));

// Simulate exact renderer steps on fresh HTML
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);
$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$h = $html;
$h = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($h);
$h = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($h);
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$body, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
[$body, ] = \App\Libraries\Pdf\MpdfPaginationHtmlInjector::extractHeaderHtml($body);
$body = \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($body, $layoutSnap);
$body = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
$body = \App\Libraries\Pdf\MpdfFooterExtractor::purgeFooterBlocksFromBody($body);

$countRootDivs = preg_match_all('/<div\b[^>]*\bclass="[^"]*\bmpdf-ft-root\b[^"]*"[^>]*>/i', $body, $m);
echo "Body mpdf-ft-root DIVs after full pipeline: {$countRootDivs}\n";
echo "Body has Direccion in td/cell: " . (preg_match('/mpdf-ft-cell[^>]*>[\s\S]*Direccion/', $body) ? 'YES DUPLICATE RISK' : 'no') . "\n";
echo "Raw HTML has payload: " . (str_contains($html, 'report-pdf-footer-payload') ? 'yes' : 'NO') . "\n";
echo "Raw HTML footer divs: " . preg_match_all('/<div\b[^>]*\bmpdf-ft-root\b/', $html, $x) . "\n";

$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout);
file_put_contents(WRITEPATH . 'debug/full_report_' . $id . '.pdf', $pdf);
echo 'PDF bytes: ' . strlen($pdf) . "\n";

// Simulate OLD html path: footer inline in body (no payload)
if (! str_contains($html, 'report-pdf-footer-payload')) {
    echo "Not using payload mode\n";
} else {
    // Decode payload and inject footer back into body like old behavior
    preg_match('/<!--\s*report-pdf-footer-payload:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $pm);
    $footerBlock = base64_decode($pm[1], true);
    $legacyHtml = preg_replace('/<!--\s*report-pdf-footer-payload:[A-Za-z0-9+\/=_-]+\s*-->/', (string) $footerBlock, $html, 1);
    $opts = \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null);
    $renderer = new \App\Libraries\Pdf\MpdfPdfRenderer();
    $legacyPdf = $renderer->renderHtml($legacyHtml, $opts);
    file_put_contents(WRITEPATH . 'debug/legacy_inline_footer_' . $id . '.pdf', $legacyPdf);
    echo 'Legacy inline footer PDF bytes: ' . strlen($legacyPdf) . "\n";
}
