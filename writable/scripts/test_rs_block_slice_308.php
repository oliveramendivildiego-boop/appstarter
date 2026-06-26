<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['PDF_RENDERER'] = 'mpdf';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$html = file_get_contents(WRITEPATH . 'debug/adapted_body_308.html');
if ($html === false) {
    fwrite(STDERR, "no adapted body\n");
    exit(1);
}

// Extract head styles + pdf-rs-block first area only
if (! preg_match('/<head>(.*?)<\/head>/is', $html, $head)) {
    fwrite(STDERR, "no head\n");
    exit(1);
}
if (! preg_match('/<div class="pdf-rs-block">(.*?)<div class="report-grupo-inter-page-break/s', $html, $block)) {
    if (! preg_match('/<div class="pdf-rs-block">(.*?)<\/div>\s*<\/div>\s*<\/body>/s', $html, $block)) {
        fwrite(STDERR, "no rs block\n");
        exit(1);
    }
}

$slice = '<!DOCTYPE html><html><head>' . $head[1] . '</head><body class="pdf-layout-engine pdf-pagination-area-soft-fit-signature pdf-dompdf-download pdf-engine-mpdf">'
    . '<div class="pdf-rs-block">' . $block[1] . '</div></body></html>';

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
$pdf = $renderer->renderHtml($slice, new \App\Libraries\Pdf\PdfOptions());
$out = WRITEPATH . 'cache/test_rs_block_slice_308.pdf';
file_put_contents($out, $pdf);
echo 'saved ' . $out . ' (' . strlen($pdf) . " bytes)\n";
