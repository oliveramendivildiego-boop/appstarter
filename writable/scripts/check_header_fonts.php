<?php
declare(strict_types=1);

putenv('PDF_RENDERER=mpdf');
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 100), date('d/m/Y'), $layout);

if (preg_match('/header-piece-logo.*?<img[^>]+style="([^"]+)"/s', $html, $m)) {
    echo 'logo img style: ' . html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') . PHP_EOL;
}
if (preg_match('/header-piece-company.*?<h1 style="([^"]+)"/s', $html, $m2)) {
    echo 'company h1: ' . html_entity_decode($m2[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') . PHP_EOL;
}
$ts = null;
foreach ($layout['instances'] ?? [] as $inst) {
    if (is_array($inst) && ($inst['element_type'] ?? '') === 'logo') {
        $ts = $inst['text_style'] ?? [];
    }
}
echo 'logo maxH px: ' . \App\Services\ReportPdfLayoutService::logoMaxHeightPxFromTextStyle($ts) . PHP_EOL;
