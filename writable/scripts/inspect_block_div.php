<?php
declare(strict_types=1);
$registroId = (int) ($argv[1] ?? 253);
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\RegisterService;
use App\Services\ReportPdfLayoutService;

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = ReportPaginationMode::AREA_HARD_PAGE_BREAK;
$ctx = $rs->buildReportLayoutContext($data, $layout, $rs->getLabConfig());
$html = $rs->renderReportPdfHtml($data, 'http://test', '', '', $layout);

foreach (['area-1-block-2', 'area-1-block-11'] as $id) {
    if (preg_match('/<div\b[^>]*data-layout-block-id="' . preg_quote($id, '/') . '"[^>]*>/', $html, $m)) {
        echo $id . ': ' . html_entity_decode($m[0]) . "\n\n";
    } else {
        echo $id . ": NOT FOUND\n\n";
    }
}
