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

$registroId = (int) ($argv[1] ?? 308);
$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

if (preg_match('/class="footer footer-grid pdf-ft-block" style="([^"]+)"/', $html, $m)) {
    echo "footer inner style:\n" . html_entity_decode($m[1]) . "\n";
}
if (preg_match('/class="pdf-dompdf-footer-anchor" style="([^"]+)"/', $html, $m2)) {
    echo "anchor style:\n" . html_entity_decode($m2[1]) . "\n";
}
if (preg_match('/@page\s*\{([^}]+)\}/', $html, $m3)) {
    echo "@page:\n" . $m3[1] . "\n";
}

$mm = is_array($data['pdf_layout']['margins_mm'] ?? null) ? $data['pdf_layout']['margins_mm'] : [];
echo "margins: " . json_encode($mm) . "\n";
