<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$p = new Config\Paths();
require $p->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($p);
$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$d = $rs->prepareReportData(253);
$l = (new App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$l['page_style']['pagination_mode'] = App\Services\ReportLayout\ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE;
$h = $rs->renderReportPdfHtml($d, 'http://x', '', '', $l);
preg_match('/data-layout-block-id="area-1-block-2"[^>]*>/', $h, $m);
echo ($m[0] ?? 'not found') . PHP_EOL;
