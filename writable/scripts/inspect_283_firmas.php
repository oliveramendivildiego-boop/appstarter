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

$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData(283);
echo "lab_firmas:\n";
foreach ($data['report_lab_firmas'] ?? [] as $f) {
    echo '  prueba_nombre=[' . ($f['prueba_nombre'] ?? '') . "]\n";
}
echo "grupos keys:\n";
foreach (array_keys($data['grupos']) as $k) {
    echo "  [{$k}]\n";
}

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = \App\Services\ReportLayout\ReportPaginationMode::AREA_SOFT_FIT_SIGNATURE;
$ctx = $rs->buildReportLayoutContext($data, $layout, $rs->getLabConfig());
$applier = $ctx['applier'];
echo "\narea 3 signature tail bundle: " . ($applier->areaUsesSignatureTailBundle(3) ? 'YES' : 'no') . "\n";
echo "should close after firma area 3: " . ($applier->shouldCloseSignatureTailGroupAfterAreaFirma(3) ? 'YES' : 'no') . "\n";
echo "should open bundle before subgrupo area3 block0: " . ($applier->shouldOpenSignatureTailBundleBeforeSubgrupo(3, 0) ? 'YES' : 'no') . "\n";

$placement = $ctx['plan']->findPlacement('area-3-block-0');
if ($placement) {
    echo "area-3-block-0 signatureTailGroup=" . ($placement->markers->signatureTailGroup ? 'YES' : 'no') . "\n";
    echo "area-3-block-0 tableSplits=" . count($placement->tableSplits) . "\n";
}
