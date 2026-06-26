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
$metrics = \App\Libraries\Pdf\MpdfFooterStyles::resolveLayoutMetrics($layoutSnap);
echo 'layout snap keys: ' . implode(',', array_keys($layoutSnap)) . PHP_EOL;
echo 'footer_reserve_mm: ' . $metrics['footer_reserve_mm'] . PHP_EOL;
echo 'margins bottom/top: ' . $metrics['bottom'] . '/' . $metrics['top'] . PHP_EOL;
echo 'margin_bottom computed: ' . ($metrics['footer_reserve_mm'] > 0 ? ($metrics['bottom'] + $metrics['footer_reserve_mm']) : $metrics['bottom']) . PHP_EOL;

$html2 = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$html2 = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($html2);
$html2 = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($html2);
[$body, $footerInner] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($html2);
$footerHtml = $footerInner ? \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter($footerInner, $layoutSnap) : '';
echo 'footer inner len: ' . strlen($footerInner ?? '') . PHP_EOL;
echo 'footer wrapped len: ' . strlen($footerHtml) . PHP_EOL;
echo 'has style block in footer: ' . (str_contains($footerHtml, '<style>') ? 'YES' : 'no') . PHP_EOL;
echo 'has Direccion in footer: ' . (str_contains($footerHtml, 'Direccion') ? 'yes' : 'NO') . PHP_EOL;

$body = \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($body, $layoutSnap);
$body = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, null);
echo 'body has mpdf footer css: ' . (str_contains($body, '/* mPDF footer (plantilla) */') ? 'yes' : 'NO') . PHP_EOL;

file_put_contents(WRITEPATH . 'debug/mpdf_footer_wrapped_308.html', $footerHtml);

// Minimal render with only footer
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
$tempDir = WRITEPATH . 'cache/mpdf';
$footerReserve = $metrics['footer_reserve_mm'];
$marginBottom = $footerReserve > 0 ? ($metrics['bottom'] + $footerReserve) : $metrics['bottom'];
$mpdf = new Mpdf([
    'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => $tempDir,
    'margin_left' => $metrics['left'], 'margin_right' => $metrics['right'],
    'margin_top' => $metrics['top'], 'margin_bottom' => $marginBottom,
    'margin_footer' => $footerReserve > 0 ? $footerReserve : 8,
    'default_font' => 'dejavusans',
]);
$mpdf->SetHTMLFooter($footerHtml);
$mpdf->WriteHTML($body);
$out = WRITEPATH . 'debug/mpdf_footer_only_test_308.pdf';
file_put_contents($out, $mpdf->Output('', Destination::STRING_RETURN));
echo 'saved: ' . $out . ' (' . filesize($out) . ' bytes)' . PHP_EOL;
