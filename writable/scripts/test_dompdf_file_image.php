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

helper('registro');
$logo = report_image_dompdf_src('images/logo-lab-7410202fa8ffc708.png');
if ($logo === '') {
    // pick any existing image from html
    $logo = 'C:/wamp64/www/laboratorio/public/images/logo-lab-7410202fa8ffc708.png';
}
echo "Logo path: {$logo}\n";
echo 'exists: ' . (is_file($logo) ? 'yes' : 'no') . "\n";

$html = '<!DOCTYPE html><html><body><p>Test</p><img src="' . report_pdf_img_src_attr($logo) . '" style="width:100px"></body></html>';
file_put_contents(dirname(__DIR__) . '/debug/img_test.html', $html);

$pdf = (new \App\Libraries\PdfService())->generate($html, 't.pdf');
file_put_contents(dirname(__DIR__) . '/debug/img_test.pdf', $pdf);
echo 'PDF bytes: ' . strlen($pdf) . "\n";
echo 'images: ' . substr_count($pdf, '/Subtype /Image') . "\n";
