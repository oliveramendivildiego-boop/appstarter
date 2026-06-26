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

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout);
$out = WRITEPATH . 'cache/footer_margin_probe_' . $id . '.pdf';
file_put_contents($out, $pdf);

$mm = is_array($layout['margins_mm'] ?? null) ? $layout['margins_mm'] : [];
$bottomGap = (float) ($mm['bottom'] ?? 0);
$reserve = \App\Services\ReportPdfLayoutService::estimatePdfFooterReserveMm($layout);
echo 'layout bottom gap mm: ' . $bottomGap . PHP_EOL;
echo 'footer reserve mm: ' . $reserve . PHP_EOL;
echo 'expected margin_bottom: ' . ($bottomGap + $reserve) . PHP_EOL;
echo 'expected margin_footer (gap): ' . $bottomGap . PHP_EOL;
echo 'wrote: ' . $out . PHP_EOL;
