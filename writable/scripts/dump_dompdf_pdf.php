<?php
declare(strict_types=1);

putenv('PDF_RENDERER=dompdf');
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$registroId = (int) ($argv[1] ?? 305);
$rs = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);
$d = $rs->prepareReportData($registroId);
helper('qr');
$html = $rs->renderReportPdfHtml($d, 'http://x', qr_base64('http://x', 120));
$pageSize = \App\Services\ReportPdfLayoutService::resolveGlobalPageSizeMm($rs->getLabConfig());
$pdf = (new \App\Libraries\PdfService())->generate($html, 'test.pdf', $pageSize);
$out = WRITEPATH . 'cache/dompdf_test_' . $registroId . '.pdf';
file_put_contents($out, $pdf);
echo "saved: $out\n";
echo 'renderer: ' . config('Pdf')->renderer . "\n";
echo 'size: ' . round(strlen($pdf) / 1024, 1) . " KB\n";
