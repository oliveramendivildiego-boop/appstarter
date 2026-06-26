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

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

$tempDir = WRITEPATH . 'cache/mpdf';
$metrics = ['left' => 25, 'right' => 10, 'top' => 5, 'bottom' => 2, 'footer_reserve_mm' => 22.0];
$footerReserve = 22.0;
$marginBottom = 24.0;

function mk(array $metrics, float $marginBottom, float $footerReserve): Mpdf
{
    return new Mpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf',
        'margin_left' => $metrics['left'], 'margin_right' => $metrics['right'],
        'margin_top' => $metrics['top'], 'margin_bottom' => $marginBottom,
        'margin_footer' => $footerReserve, 'default_font' => 'dejavusans',
    ]);
}

$body = '<html><head></head><body><p style="font-size:14pt">BODY TEST PAGE 1</p></body></html>';

// 1 minimal
$m = mk($metrics, $marginBottom, $footerReserve);
$m->SetHTMLFooter('<div style="font-size:10pt;color:#c00;font-family:dejavusans;border-top:2px solid #236149;padding-top:3mm;">PIE MINIMO Direccion test</div>');
$m->WriteHTML($body);
file_put_contents(WRITEPATH . 'debug/ft_test_1_minimal.pdf', $m->Output('', Destination::STRING_RETURN));

// 2 table inline
$m = mk($metrics, $marginBottom, $footerReserve);
$m->SetHTMLFooter('<table width="100%" style="border-top:2px solid #236149;font-size:8pt;font-family:dejavusans;color:#333;"><tr><td width="50%">Direccion: Calle 1</td><td width="50%" style="text-align:right">Página {PAGENO} de {nbpg}</td></tr></table>');
$m->WriteHTML($body);
file_put_contents(WRITEPATH . 'debug/ft_test_2_table.pdf', $m->Output('', Destination::STRING_RETURN));

// 3 real wrapped footer
$wrapped = @file_get_contents(WRITEPATH . 'debug/mpdf_footer_wrapped_308.html');
if (is_string($wrapped) && $wrapped !== '') {
    $m = mk($metrics, $marginBottom, $footerReserve);
    $m->SetHTMLFooter($wrapped);
    $m->WriteHTML($body);
    file_put_contents(WRITEPATH . 'debug/ft_test_3_wrapped.pdf', $m->Output('', Destination::STRING_RETURN));
}

// 4 real footer + real body head css
$id = 308;
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);
$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout);
file_put_contents(WRITEPATH . 'debug/ft_test_4_full.pdf', $pdf);

// 5 footer in body at end (no SetHTMLFooter) - fixed bottom
$simpleFt = '<div style="position:fixed;bottom:0;left:0;right:0;font-size:8pt;border-top:2px solid green;padding:2mm;font-family:dejavusans;">FOOTER FIXED IN BODY</div>';
$m = mk($metrics, 10, 0);
$m->WriteHTML('<html><body><p>Content</p>' . $simpleFt . '</body></html>');
file_put_contents(WRITEPATH . 'debug/ft_test_5_fixed_body.pdf', $m->Output('', Destination::STRING_RETURN));

echo "Generated tests in writable/debug/ft_test_*.pdf\n";
echo "full pdf size: " . strlen($pdf) . "\n";
echo "engine: " . \App\Libraries\Pdf\PdfRendererFactory::lastRenderEngine() . "\n";

// dump footer from pipeline
$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$h = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$h = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($h);
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$h, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
$ft = $fi ? \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter($fi, $layoutSnap) : '';
$ft = $ft ? \App\Libraries\Pdf\HtmlMpdfAdapter::adaptFooterForMpdf($ft) : '';
echo 'footer len: ' . strlen($ft) . "\n";
file_put_contents(WRITEPATH . 'debug/ft_test_footer_final.html', $ft);
