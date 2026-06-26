<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$p = new Config\Paths();
require $p->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($p);

$html = file_get_contents(dirname(__DIR__) . '/debug/adapted_body_308.html');
preg_match('/<head>(.*?)<\/head>/is', $html, $h);

foreach (['12pt !important', '12pt', '10pt !important', '12pt !important;color:#333333 !important;font-weight:bold !important;'] as $fs) {
    $style = 'font-size:' . $fs . ';';
    if (! str_contains($fs, 'color')) {
        $style .= "font-family:'DejaVu Sans';";
    }
    $body = '<div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $style . '">HEMOGRAMA + PLAQUETAS</div>';
    $doc = '<!DOCTYPE html><html><head>' . $h[1] . '</head><body class="pdf-engine-mpdf pdf-dompdf-download pdf-layout-engine">' . $body . '</body></html>';
    $a = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($doc, new \App\Libraries\Pdf\PdfOptions());
    $m = new \Mpdf\Mpdf(['tempDir' => dirname(__DIR__) . '/cache/mpdf']);
    $m->WriteHTML($a);
    $path = dirname(__DIR__) . '/cache/fs_test_' . md5($fs) . '.pdf';
    $m->Output($path, \Mpdf\Output\Destination::FILE);
    echo $fs . ' -> ' . $path . PHP_EOL;
}
