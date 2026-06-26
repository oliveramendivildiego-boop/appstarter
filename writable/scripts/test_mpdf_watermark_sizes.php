<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$wmPath = WRITEPATH . 'uploads/report_pdf_templates/4/wm_26fd3922abef8649.png';
$info = getimagesize($wmPath);
$pageW = 215.9; // letter mm
$sizePct = 75;
$wMm = $pageW * ($sizePct / 100);
$hMm = $wMm * ($info[1] / $info[0]);

$tests = [
    'broken_current' => [[75, 0], [50, 50]],
    'fixed_mm_center' => [[$wMm, $hMm], 'P'],
    'fixed_pos_F' => [[$wMm, $hMm], 'F'],
];

foreach ($tests as $label => [$size, $pos]) {
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf']);
    $mpdf->SetWatermarkImage($wmPath, 0.35, $size, $pos);
    $mpdf->showWatermarkImage = true;
    $mpdf->WriteHTML('<h1 style="text-align:center;margin-top:80mm">TEST WATERMARK</h1><p>Content block with text.</p>');
    $out = WRITEPATH . 'cache/wm_test_' . $label . '.pdf';
    $mpdf->Output($out, \Mpdf\Output\Destination::FILE);
    echo $label . ' -> ' . $out . ' (' . filesize($out) . ' bytes)' . PHP_EOL;
}
