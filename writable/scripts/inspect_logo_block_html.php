<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['PDF_RENDERER'] = 'mpdf';
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
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);

if (preg_match('/<div class="header-piece header-piece-logo">[\s\S]*?<\/div>\s*<\/div>/', $html, $m)) {
    echo "logo block (truncated):\n" . substr($m[0], 0, 500) . "\n";
} elseif (preg_match('/header-piece-logo[\s\S]{0,800}/', $html, $m)) {
    echo "partial:\n" . substr($m[0], 0, 800) . "\n";
} else {
    echo "NO logo block in HTML\n";
}

// instances in layout
foreach ($layout['instances'] ?? [] as $inst) {
    if (($inst['section'] ?? '') === 'header' && ($inst['element_type'] ?? '') === 'logo') {
        echo 'logo instance enabled=' . json_encode($inst['enabled'] ?? null) . ' col=' . ($inst['column'] ?? '') . "\n";
    }
}
