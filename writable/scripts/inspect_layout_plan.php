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

$ls = new App\Services\ReportPdfLayoutService();
$layout = $ls->getActiveLayoutForRender();
$mode = App\Services\ReportPdfLayoutService::resolvePaginationModeFromLayout($layout);
$lf = App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle($layout['page_style']['lab_firmas'] ?? []);
echo 'pagination_mode: ' . $mode . PHP_EOL;
echo 'lab_firmas placement: ' . ($lf['placement'] ?? '?') . PHP_EOL;
echo 'lab_firmas block enabled: ' . (App\Services\ReportPdfLayoutService::isLabFirmasBlockEnabled($layout) ? 'yes' : 'no') . PHP_EOL;

$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData((int) ($argv[1] ?? 308));
$ctx = $rs->buildReportLayoutContext($data, $layout);
echo 'layout plan mode: ' . $ctx['plan']->mode . PHP_EOL;
echo 'total pages planned: ' . $ctx['plan']->totalPages . PHP_EOL;
foreach ($ctx['tree']->areas as $i => $area) {
  $sig = $area->signature;
  if ($sig === null) continue;
  $pl = $ctx['plan']->findPlacement($sig->id);
  echo "area {$i} {$area->name} sig page=" . ($pl?->pageIndex ?? '?') . ' tail=' . ($pl && $ctx['applier']->shouldCloseSignatureTailGroupAfterAreaFirma($i) ? 'yes' : 'no') . PHP_EOL;
}
