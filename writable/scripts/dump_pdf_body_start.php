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

$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData((int) ($argv[1] ?? 308));
helper('qr');
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));
$bodyStart = stripos($html, '<body');
$chunk = $bodyStart !== false ? substr($html, $bodyStart, 4000) : $html;
file_put_contents(dirname(__DIR__) . '/cache/pdf_body_start_snippet.html', $chunk);
echo 'saved snippet, len=' . strlen($chunk) . PHP_EOL;
$posHg = strpos($html, 'pdf-hg-block');
$posFt = strpos($html, 'pdf-dompdf-footer-anchor');
$posMain = strpos($html, 'pdf-main-stack');
echo 'hg: ' . $posHg . ' anchor: ' . $posFt . ' main: ' . $posMain . PHP_EOL;
echo 'order: ';
if ($posFt !== false && $posMain !== false && $posFt < $posMain) {
    echo "footer BEFORE main-stack\n";
} else {
    echo "main-stack before footer or N/A\n";
}
if ($posHg !== false && $posMain !== false) {
    echo ($posHg > $posMain ? 'header inside/after main' : 'header before main') . PHP_EOL;
}
