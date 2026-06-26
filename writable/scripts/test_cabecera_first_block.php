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

$html = file_get_contents(WRITEPATH . 'debug/adapted_body_308.html');
preg_match('/<head>(.*?)<\/head>/is', $html, $headMatch);
$head = $headMatch[1] ?? '';
$start = strpos($html, '<div class="pdf-rs-block">');
$end = strpos($html, '<div class="report-grupo-inter-page-break', $start);
$block = substr($html, $start, min(12000, $end - $start));

$doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="pdf-layout-engine pdf-pagination-area-soft-fit-signature pdf-dompdf-download pdf-engine-mpdf">' . $block . '</body></html>';
$adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($doc, new \App\Libraries\Pdf\PdfOptions());
$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf']);
$mpdf->WriteHTML($adapted);
$out = WRITEPATH . 'cache/cabecera_first_block.pdf';
$mpdf->Output($out, \Mpdf\Output\Destination::FILE);
echo 'saved ' . $out . PHP_EOL;
