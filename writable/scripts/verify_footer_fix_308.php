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
[$bodyRaw, ] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
$adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt(
    \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($bodyRaw, $layoutSnap),
    null,
);
echo '@page in adapted: ' . (preg_match('/@page/i', $adapted) ? 'YES BAD' : 'no ok') . PHP_EOL;

$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout);
file_put_contents(WRITEPATH . 'cache/mpdf_pipeline_308.pdf', $pdf);
$tmp = WRITEPATH . 'cache/mpdf_pipeline_308.pdf';
$out = shell_exec('python -c "from pypdf import PdfReader; r=PdfReader(r\'' . str_replace('\\', '/', $tmp) . '\'); t=chr(10).join((p.extract_text() or \'\') for p in r.pages); print(\'footer_ok\', \'Direccion\' in t and \'68724938\' in t)"');
echo trim((string) $out) . PHP_EOL;
