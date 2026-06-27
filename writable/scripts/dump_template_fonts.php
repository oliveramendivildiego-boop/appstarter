<?php
declare(strict_types=1);
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
echo 'Layout keys: ' . implode(', ', array_keys($layout)) . PHP_EOL;
foreach ($layout['instances'] ?? [] as $it) {
    if (! is_array($it)) {
        continue;
    }
    $sec = (string) ($it['section'] ?? '');
    if ($sec !== 'header') {
        continue;
    }
    $ts = is_array($it['text_style'] ?? null) ? $it['text_style'] : [];
    echo '  ' . ($it['element_type'] ?? '?') . ' font_size_pt=' . ($ts['font_size_pt'] ?? 'default') . PHP_EOL;
}
$styles = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
$hg = is_array($styles['header_grid'] ?? null) ? $styles['header_grid'] : [];
$pd = is_array($styles['patient_doctor_grid'] ?? null) ? $styles['patient_doctor_grid'] : [];
echo 'header_grid.font_size_pt=' . ($hg['font_size_pt'] ?? 'n/a') . PHP_EOL;
echo 'patient_doctor_grid.font_size_pt=' . ($pd['font_size_pt'] ?? 'n/a') . PHP_EOL;
foreach ($layout['instances'] ?? [] as $it) {
    if (! is_array($it)) {
        continue;
    }
    $sec = (string) ($it['section'] ?? '');
    if ($sec !== 'patient_doctor') {
        continue;
    }
    $ts = is_array($it['text_style'] ?? null) ? $it['text_style'] : [];
    echo '  pd ' . ($it['element_type'] ?? '?') . ' font_size_pt=' . ($ts['font_size_pt'] ?? 'default') . PHP_EOL;
}

$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData(308);
helper('qr');
$url = $rs->publicReportViewerUrlForQr(308);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf(308);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);
if (preg_match('/header-piece-company[\s\S]*?<h1 style="([^"]+)"/', $html, $m)) {
    echo PHP_EOL . 'HTML h1 style: ' . html_entity_decode($m[1]) . PHP_EOL;
}
if (preg_match('/class="pdf-cell[^"]*"[^>]*font-size:([^;]+)/', $html, $m2)) {
    echo 'HTML td font-size: ' . html_entity_decode($m2[1]) . PHP_EOL;
}
