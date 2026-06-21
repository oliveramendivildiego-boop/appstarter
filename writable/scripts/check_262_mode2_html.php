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
use App\Services\ReportPdfLayoutService;
use App\Services\ReportLayout\ReportPaginationMode;

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData(262);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = ReportPaginationMode::FLOW_NO_LONE_SIGNATURE;
$html = $rs->renderReportPdfHtml($data, 'http://x', 'x', '01/01/2025', $layout);
file_put_contents(dirname(__DIR__) . '/debug/check_262_mode2.html', $html);
echo 'tail bundles=' . substr_count($html, 'report-signature-tail-bundle') . PHP_EOL;
echo 'companion rows=' . substr_count($html, 'report-signature-companion-row') . PHP_EOL;
if (preg_match('/report-signature-tail-bundle[\s\S]*?group-title[\s\S]*?Creatinina[\s\S]*?report-lab-firma-grupo-inline/s', $html)) {
    echo "Creatinina cabecera+tabla+firma in bundle ok\n";
}
