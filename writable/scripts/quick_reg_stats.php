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

$id = (int) ($argv[1] ?? 268);
$rs = new App\Services\RegisterService();
$t = microtime(true);
$d = $rs->prepareReportData($id);
echo 'prep_ms: ' . round((microtime(true) - $t) * 1000, 2) . PHP_EOL;
if (! $d) {
    exit(1);
}
$items = 0;
foreach ($d['grupos'] ?? [] as $r) {
    $items += count($r);
}
echo 'grupos: ' . count($d['grupos'] ?? []) . ' items: ' . $items . ' analisis: ' . count($d['analisis'] ?? []) . PHP_EOL;
helper('qr');
$t = microtime(true);
$html = $rs->renderReportPdfHtml($d, 'http://x', qr_base64('http://x', 120));
echo 'render_html_ms: ' . round((microtime(true) - $t) * 1000, 2) . PHP_EOL;
echo 'html_kb: ' . round(strlen($html) / 1024, 1) . ' tr: ' . preg_match_all('/<tr/i', $html) . PHP_EOL;
$b64 = preg_match_all('/data:image[^;]+;base64,/', $html);
echo 'base64_images: ' . $b64 . PHP_EOL;
$t = microtime(true);
try {
    $pdf = (new App\Libraries\PdfService())->generate($html);
    echo 'pdf_kb: ' . round(strlen($pdf) / 1024, 1) . ' dompdf_ms: ' . round((microtime(true) - $t) * 1000, 2) . PHP_EOL;
} catch (Throwable $e) {
    echo 'dompdf_error: ' . $e->getMessage() . PHP_EOL;
}
