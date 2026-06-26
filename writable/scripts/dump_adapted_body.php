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
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);
$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$h = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$h = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($h);
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$bodyRaw, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
[$bodyRaw, ] = \App\Libraries\Pdf\MpdfPaginationHtmlInjector::extractHeaderHtml($bodyRaw);
$body = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt(
    \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($bodyRaw, $layoutSnap),
    null,
);
file_put_contents(WRITEPATH . 'debug/adapted_body_308.html', $body);

$patterns = ['htmlpagefooter', 'sethtmlpagefooter', 'pagefooter', 'setpagefooter', '<footer', 'HTMLFooter', 'tocpagebreak', 'pagebreak'];
foreach ($patterns as $p) {
    echo $p . ': ' . (stripos($body, $p) !== false ? 'YES' : 'no') . PHP_EOL;
}

// split by lab-firmas
$pos = stripos($body, 'lab-firmas-pdf-block');
echo 'lab-firmas at: ' . ($pos !== false ? $pos : 'none') . PHP_EOL;
