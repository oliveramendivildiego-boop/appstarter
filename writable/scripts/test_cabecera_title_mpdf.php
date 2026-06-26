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

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$titleStyle = \App\Services\ReportPdfLayoutService::groupTitleStyleAttr($layout, false, true, true);

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
body { font-family: dejavusans; font-size: 10pt; }
</style></head><body class="pdf-engine-mpdf">
<div class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="padding:2px;font-weight:bold;background:#E9ECEF;">HEMATOLOGIA</div>
<div class="report-pdf-grupo-cabecera">
<div class="group-title report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" style="' . $titleStyle . '">HEMOGRAMA + PLAQUETAS</div>
<div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo">Método: Test</div>
</div>
<p>MARKER_AFTER</p>
</body></html>';

$renderer = \App\Libraries\Pdf\PdfRendererFactory::create();
$pdf = $renderer->renderHtml($html, new \App\Libraries\Pdf\PdfOptions());
$out = WRITEPATH . 'cache/test_cabecera_title.pdf';
file_put_contents($out, $pdf);
echo 'saved: ' . $out . ' (' . strlen($pdf) . ' bytes)' . PHP_EOL;
