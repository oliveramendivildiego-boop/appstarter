<?php
declare(strict_types=1);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$svc = new \App\Services\ReportPdfLayoutService();
$layout = $svc->getActiveLayoutForRender();
$sp = \App\Services\ReportPdfLayoutService::grupoCabeceraSpacingFromLayout($layout);
$rs = is_array($layout['page_style']['results_table'] ?? null) ? $layout['page_style']['results_table'] : [];

echo "active template spacing:\n";
echo json_encode($sp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
echo "tipo_style (not last): " . \App\Services\ReportPdfLayoutService::grupoCabeceraTipoMuestraStyleAttr($layout, false, true, false) . "\n";
echo "metodo_style (last): " . \App\Services\ReportPdfLayoutService::grupoCabeceraMetodoStyleAttr($layout, true, true, false) . "\n";

$dataCache = new \App\Services\Report\ReportDataCacheService();
$registroId = (int) ($argv[1] ?? 308);
$fp = $dataCache->computeFingerprint($registroId);
$cached = $dataCache->read($registroId, $fp);
if ($cached !== null) {
    $cachedLayout = is_array($cached['pdf_layout'] ?? null) ? $cached['pdf_layout'] : [];
    $cachedSp = \App\Services\ReportPdfLayoutService::grupoCabeceraSpacingFromLayout($cachedLayout);
    echo "\ncached pdf_layout spacing:\n";
    echo json_encode($cachedSp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "\nno cached data for registro {$registroId} (fp match)\n";
}
