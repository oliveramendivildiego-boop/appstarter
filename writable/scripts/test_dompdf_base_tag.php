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
$html = '<!DOCTYPE html><html><head><base href="http://localhost/laboratorio/public/" /></head><body><img src="' . report_pdf_img_src_attr($logo) . '"></body></html>';
$pdf = (new \App\Libraries\PdfService())->generate($html, 't.pdf');
echo 'bytes: ' . strlen($pdf) . ' images: ' . substr_count($pdf, '/Subtype /Image') . "\n";
