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
$rs->clearReportPdfPreviewCache($id);
$data = $rs->prepareReportData($id);
if ($data === null) {
    echo "no data\n";
    exit(1);
}
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);
$adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html);

foreach (['pdf-hg-block', 'header-piece-logo', 'header-piece-qr', 'header-piece-company', 'pdf-cell--has-explicit-height'] as $needle) {
    echo $needle . ': ' . (str_contains($adapted, $needle) ? 'yes' : 'no') . PHP_EOL;
}

if (preg_match('/<div class="header header-grid pdf-hg-block">[\s\S]*?<\/div>\s*<\/div>/', $adapted, $m)) {
    $chunk = $m[0];
    $chunk = preg_replace('/src="data:image[^"]+"/', 'src="[DATA]"', $chunk) ?? $chunk;
    echo "\n--- header chunk ---\n";
    echo substr($chunk, 0, 6000) . "\n";
} else {
    echo "no header block found\n";
}

[$cols, $items] = \App\Services\ReportPdfLayoutService::gridItemsForSection($layout, 'header');
echo "\nheader instances: " . count($items) . " cols=$cols\n";
foreach ($items as $it) {
    echo '  ' . ($it['element_type'] ?? '?') . ' col=' . ($it['column'] ?? '') . ' span=' . ($it['column_span'] ?? 1)
        . ' row=' . ($it['grid_row'] ?? '') . ' ah=' . ($it['align_h'] ?? '-') . ' av=' . ($it['align_v'] ?? '-') . PHP_EOL;
}
