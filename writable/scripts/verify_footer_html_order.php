<?php
declare(strict_types=1);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$registroId = (int) ($argv[1] ?? 308);
$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
helper('qr');
$layout = (new App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($registroId);
$html = $rs->renderReportPdfHtml($data, $url, qr_base64($url, 120));

$bodyStart = stripos($html, '<body');
$bodyHtml  = $bodyStart !== false ? substr($html, $bodyStart) : $html;
$posFt   = strpos($bodyHtml, 'footer footer-grid pdf-ft-block');
$posMain = strpos($bodyHtml, 'pdf-main-stack');
echo 'footer before main: ' . (($posFt !== false && $posMain !== false && $posFt < $posMain) ? 'yes' : 'no') . PHP_EOL;
echo 'order sheet marker: ' . (preg_match('/<!--\s*pdf-order-sheet-header:/', $bodyHtml) ? 'yes' : 'no') . PHP_EOL;
echo 'order sheet table row in body: ' . (preg_match('/<tr[^>]*pdf-order-sheet-table-row/', $bodyHtml) ? 'yes' : 'no') . PHP_EOL;

$renderer = new App\Libraries\Pdf\DompdfPdfRenderer();
$ref = new ReflectionClass($renderer);
$m = $ref->getMethod('extractOrderSheetHeaderSlot');
$m->setAccessible(true);
$slot = $m->invoke($renderer, $html);
echo 'slot: ' . json_encode($slot, JSON_UNESCAPED_UNICODE) . PHP_EOL;
