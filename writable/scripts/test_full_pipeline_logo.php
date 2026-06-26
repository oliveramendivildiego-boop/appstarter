<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);

$html2 = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$html2 = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($html2);
$html2 = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($html2);
[$body, $footer] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($html2);
$adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));

$countImg = substr_count($adapted, '<img');
$countLogo = substr_count($adapted, 'header-piece-logo');
$hasLogoImg = (bool) preg_match('/header-piece-logo[\s\S]*?<img/', $adapted);
echo "imgs=$countImg logoBlocks=$countLogo hasLogoImg=" . ($hasLogoImg ? 'yes' : 'NO') . PHP_EOL;

if (preg_match('/<img[^>]+alt="Logo"[^>]*>/', $adapted, $m)) {
    $tag = preg_replace('/src="data:image[^"]+"/', 'src="[DATA]"', $m[0]);
    echo "logo tag: $tag\n";
} else {
    echo "NO logo img tag in adapted body\n";
}

$pdf = (new \App\Libraries\PdfService())->generate($html, 'test.pdf', null);
$out = WRITEPATH . 'cache/logo_full_pipeline_308.pdf';
file_put_contents($out, $pdf);
echo 'pdf saved ' . $out . PHP_EOL;
