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
$html = $rs->renderReportPdfHtml(
    $rs->prepareReportData((int) ($argv[1] ?? 308)),
    'http://x',
    qr_base64('http://x', 120)
);
$body = stripos($html, '<body');
$chunk = $body !== false ? substr($html, $body, 8000) : $html;
file_put_contents(dirname(__DIR__) . '/cache/pdf_body_top_snippet.html', $chunk);

$markers = [
    'flow-top' => 'pdf-dompdf-flow-top',
    'hg' => 'pdf-hg-block',
    'pd' => 'pdf-pd-block',
    'footer-anchor' => 'pdf-dompdf-footer-anchor',
    'main-stack' => 'pdf-main-stack',
];
$searchFrom = $body !== false ? $body : 0;
foreach ($markers as $k => $needle) {
    $p = strpos($html, $needle, $searchFrom);
    echo $k . ': ' . ($p === false ? 'missing' : (string) $p) . PHP_EOL;
}
