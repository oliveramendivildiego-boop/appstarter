<?php
declare(strict_types=1);

$registroId = (int) ($argv[1] ?? 262);
if ($registroId < 1) {
    fwrite(STDERR, "Uso: php writable/scripts/test_order_sheet_page2.php <registro_id>\n");
    exit(1);
}

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
defined('CI_DEBUG') || define('CI_DEBUG', true);

define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$rs = new App\Services\RegisterService();
$data = $rs->prepareReportData($registroId);
if (empty($data['grupos'])) {
    fwrite(STDERR, "No data for registro {$registroId}\n");
    exit(1);
}

helper(['qr', 'registro']);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

echo 'marker: ' . (str_contains($html, 'pdf-order-sheet-header:') ? 'yes' : 'no') . PHP_EOL;
echo 'band table in footer html: ' . (preg_match('/pdf-order-sheet-footer-band-dompdf[^>]*>[\s\S]*?<table class="pdf-order-sheet-header"/', $html) ? 'yes' : 'no') . PHP_EOL;

$pdf = (new App\Libraries\PdfService())->generate($html);
$out = dirname(__DIR__) . '/debug/order_sheet_test_' . $registroId . '.pdf';
file_put_contents($out, $pdf);
echo 'written: ' . $out . PHP_EOL;

if (preg_match_all('/\/Type\s*\/Page[^s]/', $pdf, $pages)) {
    echo 'page count approx: ' . count($pages[0]) . PHP_EOL;
}
