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

use App\Services\ReportLayout\ReportLayoutMetrics;
use App\Services\ReportPdfLayoutService;
use App\Services\RegisterService;

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$lab = $rs->getLabConfig();
$start = ReportPdfLayoutService::estimatePdfHeaderBeforeResultsMm($layout);
$m = ReportLayoutMetrics::fromLayoutAndConfig($layout, $lab, $start);

echo "contentStartMm={$m->contentStartMm}\n";
echo "maxContentHeightMm={$m->maxContentHeightMm}\n";
echo "pageBottom=" . ($m->contentStartMm + $m->maxContentHeightMm) . "\n";
echo "rowHeightMm={$m->rowHeightMm}\n";
echo "theadHeightMm={$m->theadHeightMm}\n";
echo "tableMarginTopMm={$m->tableMarginTopMm}\n";
echo "tableMarginBottomMm={$m->tableMarginBottomMm}\n";
echo "segmentWrapMarginBottomMm={$m->segmentWrapMarginBottomMm}\n";
echo "subgrupoGapMm={$m->subgrupoGapMm}\n";
echo "signatureHeightMm={$m->signatureHeightMm}\n";
echo "footerReserveMm={$m->footerReserveMm}\n";

$oneRow = $m->theadHeightMm + $m->rowHeightMm + $m->tableRowBorderMm
    + $m->tableMarginTopMm + $m->tableMarginBottomMm + $m->segmentWrapMarginBottomMm;
echo "1-row section (no title)={$oneRow} mm\n";
