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
$block = substr($html, $start, $end - $start);

$metodoEnd = strpos($block, '</div>', strpos($block, 'report-pdf-grupo-cabecera-line--metodo'));
$throughMetodo = substr($block, 0, $metodoEnd + 6) . '</div></div></div></div>';

$firstTableEnd = strpos($block, '</table>', strpos($block, 'Serie Roja'));
$throughFirstTable = substr($block, 0, $firstTableEnd + 8) . '</div></div></div></div>';

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
foreach (['through_metodo' => $throughMetodo, 'through_first_table' => $throughFirstTable, 'full' => $block] as $label => $body) {
    $doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="pdf-layout-engine pdf-pagination-area-soft-fit-signature pdf-dompdf-download pdf-engine-mpdf">' . $body . '</body></html>';
    $pdf = $renderer->renderHtml($doc, new \App\Libraries\Pdf\PdfOptions());
    $path = WRITEPATH . 'cache/prefix_' . $label . '.pdf';
    file_put_contents($path, $pdf);
    echo $label . ' -> ' . $path . PHP_EOL;
}
