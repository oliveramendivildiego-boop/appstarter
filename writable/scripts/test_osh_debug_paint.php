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

use setasign\Fpdi\Tcpdf\Fpdi;

$in = WRITEPATH . 'cache/chromium_test_305.pdf';
$out = WRITEPATH . 'cache/osh_debug.pdf';
$pdf = new Fpdi('P', 'pt');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->setSourceFile($in);
$n = 2;
for ($p = 1; $p <= $n; $p++) {
    $tpl = $pdf->importPage($p);
    $size = $pdf->getTemplateSize($tpl);
    $w = (float) $size['width'];
    $h = (float) $size['height'];
    $pdf->AddPage('P', [$w, $h]);
    $pdf->useTemplate($tpl);
    if ($p >= 2) {
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Text(72, 746, 'STAMP_TEST_PAGE_' . $p);
    }
}
$pdf->Output($out, 'F');
echo "wrote $out\n";
