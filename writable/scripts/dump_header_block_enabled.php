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
echo 'template_id=' . ($layout['template_id'] ?? '?') . ' name=' . ($layout['template_name'] ?? '?') . PHP_EOL;
foreach ($layout['blocks'] ?? [] as $b) {
    if (! is_array($b)) {
        continue;
    }
    echo sprintf(
        "  block id=%s enabled=%s\n",
        (string) ($b['id'] ?? '?'),
        ! empty($b['enabled']) ? 'yes' : 'NO',
    );
}
$hasHeaderHtml = false;
$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);
$hasHeaderHtml = str_contains($html, 'pdf-hg-block');
echo PHP_EOL . "renderReportPdfHtml has pdf-hg-block: " . ($hasHeaderHtml ? 'yes' : 'NO') . PHP_EOL;
if (preg_match('/pdf-hg-block[\s\S]{0,800}/', $html, $m)) {
    echo substr(strip_tags($m[0]), 0, 200) . PHP_EOL;
}
