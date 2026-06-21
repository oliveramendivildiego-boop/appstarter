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

use App\Services\RegisterService;
use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportPdfLayoutService;

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData(264);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = ReportPaginationMode::FLOW_NO_LONE_SIGNATURE;
$html = $rs->renderReportPdfHtml($data, 'http://x', 'x', '01/01/2025', $layout);
file_put_contents(dirname(__DIR__) . '/debug/check_264_mode2.html', $html);

$pos = strpos($html, 'Creatinina');
if ($pos === false) {
    echo "Creatinina not found\n";
    exit(1);
}
$chunk = substr($html, max(0, $pos - 800), 2000);
echo $chunk . "\n";
$bundleBefore = strrpos(substr($html, 0, $pos), 'report-signature-tail-bundle');
$cabeceraBefore = strrpos(substr($html, 0, $pos), 'report-pdf-grupo-cabecera');
echo "bundle before creatinina: " . ($bundleBefore !== false ? 'yes at ' . $bundleBefore : 'no') . PHP_EOL;
echo "cabecera before creatinina title: " . ($cabeceraBefore !== false ? 'yes' : 'no') . PHP_EOL;
if ($bundleBefore !== false && $cabeceraBefore !== false) {
    echo ($bundleBefore < $cabeceraBefore ? "cabecera INSIDE bundle\n" : "cabecera OUTSIDE bundle\n");
}
