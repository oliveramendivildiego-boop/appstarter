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

$id = 262;
$svc = new \App\Services\RegisterService();
$data = $svc->prepareReportData($id);
helper('qr');
$reportUrl = site_url('registers/viewreport/' . $id);
$qrLayout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$qrPx = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($qrLayout);
$qrDataUri = qr_base64($reportUrl, $qrPx);
$html = $svc->renderReportPdfHtml($data, $reportUrl, $qrDataUri, $svc->formatNowForReport());

$pdfSvc = new class extends \App\Libraries\PdfService {
    public function generateUncompressed(string $html, ?array $pageSize = null): string
    {
        $ref = new ReflectionClass(parent::class);
        $extract = $ref->getMethod('extractPaginationSlots');
        $extract->setAccessible(true);
        $slots = $extract->invoke($this, $html);
        echo 'slots=' . count($slots) . PHP_EOL;
        if ($slots) {
            echo json_encode($slots[0], JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
        $probe = $ref->getMethod('htmlNeedsPageCountProbe');
        $probe->setAccessible(true);
        if ($probe->invoke($this, $html)) {
            $makeOpts = $ref->getMethod('makeDompdfOptions');
            $makeOpts->setAccessible(true);
            $makeDom = $ref->getMethod('makeDompdf');
            $makeDom->setAccessible(true);
            $render = $ref->getMethod('renderHtmlToDompdf');
            $render->setAccessible(true);
            $p = $makeDom->invoke($this, $makeOpts->invoke($this, true), $pageSize);
            $render->invoke($this, $p, $html, null, []);
            $pageCount = (int) $p->getCanvas()->get_page_count();
            $html = str_replace(\App\Services\RegisterService::TOTAL_PAGES_TOKEN, (string) $pageCount, $html);
            echo 'pages=' . $pageCount . PHP_EOL;
        }
        $makeOpts = $ref->getMethod('makeDompdfOptions');
        $makeOpts->setAccessible(true);
        $makeDom = $ref->getMethod('makeDompdf');
        $makeDom->setAccessible(true);
        $render = $ref->getMethod('renderHtmlToDompdf');
        $render->setAccessible(true);
        $dompdf = $makeDom->invoke($this, $makeOpts->invoke($this, false), $pageSize);
        $wm = $ref->getMethod('extractWatermarkData');
        $wm->setAccessible(true);
        $render->invoke($this, $dompdf, $html, $wm->invoke($this, $html), $slots);
        return $dompdf->output(['compress' => 0]);
    }
};

$pageSize = \App\Services\ReportPdfLayoutService::resolveGlobalPageSizeMm((new \App\Services\RegisterService())->getLabConfig());
$pdf = $pdfSvc->generateUncompressed($html, $pageSize);
file_put_contents(dirname(__DIR__) . '/debug/report_262_uncompressed.pdf', $pdf);

if (preg_match_all('/\[\(([^\)]{1,60})\)\]/', $pdf, $m)) {
    foreach ($m[1] as $s) {
        if (str_contains($s, 'de')) {
            echo 'Tj: ' . $s . PHP_EOL;
        }
    }
}
