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
preg_match('/<head>(.*?)<\/head>/is', $html, $headM);
$head = $headM[1] ?? '';

$styles = [
    'empty' => '',
    'font_family_only' => "font-family:'DejaVu Sans';",
    'font_family_imp' => "font-family:'DejaVu Sans' !important;",
    'font_size' => 'font-size:12pt !important;',
    'color' => 'color:#333 !important;',
    'font_family_size' => "font-family:'DejaVu Sans';font-size:12pt !important;",
    'bold_only' => 'font-weight:bold !important;',
    'family_bold' => "font-family:'DejaVu Sans' !important;font-weight:bold !important;",
];

$sep = '<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="padding:2px;font-weight:bold;background:#E9ECEF;">HEMATOLOGIA</div>';
$metodo = '<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo">Método: Test</div>';

foreach ($styles as $label => $style) {
    $attr = $style === '' ? '' : ' style="' . $style . '"';
    $body = $sep . '<div class="report-pdf-grupo-cabecera"><div class="report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title"' . $attr . '>HEMOGRAMA + PLAQUETAS</div>' . $metodo . '</div>';
    $doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="pdf-layout-engine pdf-dompdf-download pdf-engine-mpdf">' . $body . '</body></html>';
    $adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($doc, new \App\Libraries\Pdf\PdfOptions());
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf']);
    $mpdf->WriteHTML($adapted);
    file_put_contents(WRITEPATH . 'cache/sattr_' . $label . '.pdf', $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN));
    echo $label . PHP_EOL;
}
