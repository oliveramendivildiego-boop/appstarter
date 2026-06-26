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

$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$d = $rs->prepareReportData(305);
helper('qr');
$html = $rs->renderReportPdfHtml($d, 'http://x', qr_base64('http://x', 120));
$slot = \App\Libraries\Pdf\ChromiumPdfOrderSheetStamper::extractSlot($html);
$slots = \App\Libraries\Pdf\ChromiumPdfPaginationStamper::extractFooterPaginationSlots($html);
$pdf = (string) file_get_contents(WRITEPATH . 'cache/chromium_test_305.pdf');
$temp = WRITEPATH . 'cache/chromium_pdf';

try {
    $inputPath = $temp . '/dbg_in.pdf';
    $outputPath = $temp . '/dbg_out.pdf';
    file_put_contents($inputPath, $pdf);
    $fpdi = new \setasign\Fpdi\Tcpdf\Fpdi('P', 'pt');
    $fpdi->setPrintHeader(false);
    $fpdi->setPrintFooter(false);
    $fpdi->setAutoPageBreak(false);
    $fpdi->setMargins(0, 0, 0);
    $pageCount = $fpdi->setSourceFile($inputPath);
    echo "pages: $pageCount\n";
    for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
        $tplId = $fpdi->importPage($pageNumber);
        $size = $fpdi->getTemplateSize($tplId);
        $pageW = (float) ($size['width'] ?? 612);
        $pageH = (float) ($size['height'] ?? 792);
        $fpdi->AddPage('P', [$pageW, $pageH]);
        $fpdi->useTemplate($tplId);
        if ($pageNumber >= 2 && $slot !== null) {
            echo "painting page $pageNumber\n";
            \App\Libraries\Pdf\ChromiumPdfOrderSheetStamper::paintOrderSheetRow($fpdi, $slot, $pageW, $pageH);
        }
    }
    $fpdi->Output($outputPath, 'F');
    echo "ok out size: " . filesize($outputPath) . "\n";
} catch (\Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
