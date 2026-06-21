<?php
declare(strict_types=1);
$id = (int) ($argv[1] ?? 253);
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);

foreach (['pdf' => 'renderReportPdfHtml', 'print' => 'renderReportPrintHtml'] as $label => $method) {
    $html = $method === 'renderReportPdfHtml'
        ? $rs->renderReportPdfHtml($data, 'http://test', '')
        : $rs->renderReportPrintHtml($data, 'http://test', '', $id);
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'debug';
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $path = $dir . DIRECTORY_SEPARATOR . "check_{$id}_{$label}.html";
    file_put_contents($path, $html);
    preg_match('/<body class="([^"]+)"/', $html, $m);
    echo strtoupper($label) . " body=" . ($m[1] ?? '?') . PHP_EOL;
    echo "  force-break=" . substr_count($html, 'report-subgrupo-force-break-before') . PHP_EOL;
    echo "  keep-intact=" . substr_count($html, 'report-subgrupo-keep-intact') . PHP_EOL;
    echo "  cabecera-force=" . substr_count($html, 'report-cabecera-force-break-before') . PHP_EOL;
    echo "  layout-block-id=" . substr_count($html, 'data-layout-block-id') . PHP_EOL;
    echo "  saved={$path}\n\n";
}
