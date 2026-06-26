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

final class LoggingDompdfRenderer extends App\Libraries\Pdf\DompdfPdfRenderer
{
    /** @var list<string> */
    public array $log = [];

    protected function paintPaginationOnPage($canvas, $fontMetrics, array $slot, int $pageNumber, int $pageCount): void
    {
        $this->log[] = sprintf(
            'paint pag p%d/%d zone=%s inline=%s',
            $pageNumber,
            $pageCount,
            (string) ($slot['zone'] ?? ''),
            ! empty($slot['inlineAfterLabel']) ? 'yes' : 'no',
        );
        parent::paintPaginationOnPage($canvas, $fontMetrics, $slot, $pageNumber, $pageCount);
    }
}

$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData((int) ($argv[1] ?? 308));
helper('qr');
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));
$layout = (new App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$pageSize = App\Services\ReportPdfLayoutService::resolveGlobalPageSizeMm($rs->getLabConfig());

$renderer = new LoggingDompdfRenderer();
$bin = $renderer->renderHtml($html, App\Libraries\Pdf\PdfOptions::fromLegacyPageSize($pageSize));
$out = WRITEPATH . 'cache/dompdf_pag_log_test.pdf';
file_put_contents($out, $bin);

echo 'bytes: ' . strlen($bin) . PHP_EOL;
echo 'log lines: ' . count($renderer->log) . PHP_EOL;
foreach ($renderer->log as $line) {
    echo $line . PHP_EOL;
}
echo 'saved: ' . $out . PHP_EOL;
