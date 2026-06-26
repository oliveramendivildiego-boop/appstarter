<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['PDF_RENDERER'] = 'mpdf';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = 308;
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);

$html = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$html = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($html);
$html = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($html);
[$html, ] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($html);
[$html, ] = \App\Libraries\Pdf\MpdfPaginationHtmlInjector::extractHeaderHtml($html);
$html = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
$html = \App\Libraries\Pdf\MpdfInlineImageResolver::materializeDataUriImages($html);

if (preg_match('/<img[^>]+alt="Logo"[^>]*>/', $html, $m)) {
    echo "LOGO AFTER MATERIALIZE:\n" . $m[0] . "\n";
} else {
    echo "NO LOGO IMG\n";
}

// header-only slice
if (preg_match('/<div class="header header-grid pdf-hg-block">[\s\S]*?<\/div>\s*<div class="pdf-pd-block"/', $html, $m)) {
    $headerHtml = '<!DOCTYPE html><html><head></head><body class="pdf-engine-mpdf">' . $m[0] . '</body></html>';
    $pdf = (new \App\Libraries\PdfService())->generate($headerHtml, 'h.pdf', null);
    file_put_contents(WRITEPATH . 'cache/header_only_308.pdf', $pdf);
    echo 'header-only pdf bytes=' . strlen($pdf) . PHP_EOL;
}
