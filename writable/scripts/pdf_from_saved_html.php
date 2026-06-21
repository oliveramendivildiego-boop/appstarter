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

$html = file_get_contents(dirname(__DIR__) . '/debug/report_262.html');
$t0 = microtime(true);
$pdf = (new \App\Libraries\PdfService())->generate($html, 't.pdf');
echo 'ms: ' . round((microtime(true) - $t0) * 1000) . "\n";
echo 'bytes: ' . strlen($pdf) . "\n";
echo 'images: ' . substr_count($pdf, '/Subtype /Image') . "\n";
file_put_contents(dirname(__DIR__) . '/debug/report_262_from_html.pdf', $pdf);
