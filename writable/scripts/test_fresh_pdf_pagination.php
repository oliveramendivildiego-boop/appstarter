<?php
declare(strict_types=1);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
defined('CI_DEBUG') || define('CI_DEBUG', true);
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 262);
$svc = new \App\Services\RegisterService();
$data = $svc->prepareReportData($id);
if (! $data) {
    fwrite(STDERR, "No data for {$id}\n");
    exit(1);
}
helper('qr');
$reportUrl = site_url('registers/viewreport/' . $id);
$qrLayout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$qrPx = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($qrLayout);
$qrDataUri = qr_base64($reportUrl, $qrPx);
$html = $svc->renderReportPdfHtml($data, $reportUrl, $qrDataUri, $svc->formatNowForReport());
file_put_contents(dirname(__DIR__) . '/debug/report_' . $id . '_fresh.html', $html);

$hasToken = str_contains($html, '__PDF_TOTAL_PAGES__');
$hasPagComment = (bool) preg_match('/pdf-pagination:/', $html);
$hasHidden = str_contains($html, 'header-piece-pagination" style="visibility:hidden');
$hasFooterStatic = str_contains($html, 'pdf-ft-block');
echo "token in html: " . ($hasToken ? 'yes' : 'no') . PHP_EOL;
echo "canvas comment: " . ($hasPagComment ? 'yes' : 'no') . PHP_EOL;
echo "hidden pagination: " . ($hasHidden ? 'yes' : 'no') . PHP_EOL;

$pdf = (new \App\Libraries\PdfService())->generate($html, 'test.pdf');
file_put_contents(dirname(__DIR__) . '/debug/report_' . $id . '_fresh.pdf', $pdf);
echo 'pdf bytes: ' . strlen($pdf) . PHP_EOL;

// Decompress streams lightly - search for pagination label fragments
$flat = @gzuncompress(substr($pdf, strpos($pdf, 'stream') ?: 0));
echo 'done' . PHP_EOL;
