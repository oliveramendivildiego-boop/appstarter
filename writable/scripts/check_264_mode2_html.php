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

echo 'Creatinina tail bundle=' . (str_contains($html, 'report-signature-tail-bundle"><div class="report-pdf-grupo-cabecera">') || str_contains($html, 'report-signature-tail-bundle"><div class="report-pdf-grupo-cabecera"') || preg_match('/report-signature-tail-bundle[\s\S]{0,80}report-pdf-grupo-cabecera[\s\S]{0,200}Creatinina/s', $html) ? 'yes' : 'no') . PHP_EOL;
if (preg_match('/report-signature-tail-bundle[\s\S]{0,120}report-pdf-grupo-cabecera[\s\S]{0,400}Creatinina[\s\S]{0,1200}report-lab-firma-grupo-inline/s', $html)) {
    echo "cabecera Creatinina inside bundle with firma ok\n";
}
