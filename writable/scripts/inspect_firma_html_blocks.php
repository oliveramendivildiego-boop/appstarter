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
helper('qr');

$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData(308);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

if (preg_match_all('/<div class="report-lab-firma-grupo-inline[^"]*"[\s\S]*?<\/div>\s*<\/div>/', $html, $m)) {
    echo 'firma blocks: ' . count($m[0]) . PHP_EOL;
    foreach (array_slice($m[0], 0, 3) as $i => $block) {
        echo "--- block $i len=" . strlen($block) . " ---" . PHP_EOL;
        echo substr(strip_tags($block), 0, 200) . PHP_EOL;
        echo (str_contains($block, 'lab-firmas-body-grid') ? 'has body grid' : 'NO body grid') . PHP_EOL;
        echo (str_contains($block, '<img') ? 'has img' : 'no img') . PHP_EOL;
    }
}

$layout = $data['pdf_layout'] ?? [];
$n = 0;
foreach ($layout['instances'] ?? [] as $inst) {
    if (($inst['section'] ?? '') === 'lab_firmas' && ! empty($inst['enabled'])) {
        echo 'lf inst: ' . ($inst['element_type'] ?? '') . PHP_EOL;
        $n++;
    }
}
echo "lab_firmas instances enabled: $n" . PHP_EOL;
