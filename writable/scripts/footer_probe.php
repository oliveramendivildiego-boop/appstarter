<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['PDF_RENDERER'] = 'mpdf';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 143);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
if ($data === null) {
    fwrite(STDERR, "No data for register {$id}\n");
    exit(1);
}
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);
$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout);

$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$h = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$h = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($h);
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$h, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
$wrapped = ($fi !== null && $fi !== '') ? \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter($fi, $layoutSnap) : '';

$out = WRITEPATH . 'debug/footer_probe_' . $id;
file_put_contents($out . '.pdf', $pdf);
file_put_contents($out . '_inner.html', (string) $fi);
file_put_contents($out . '_wrapped.html', $wrapped);

echo 'register=' . $id . ' pdf=' . strlen($pdf) . ' inner=' . strlen((string) $fi) . ' wrapped=' . strlen($wrapped) . "\n";
echo 'footer enabled: ' . (\App\Services\ReportPdfLayoutService::isPdfFooterBlockEnabledForLayout($layoutSnap) ? 'yes' : 'NO') . "\n";
echo 'reserve mm: ' . \App\Services\ReportPdfLayoutService::estimatePdfFooterReserveMm($layoutSnap) . "\n";
echo 'has border-top on table: ' . (preg_match('/mpdf-ft-table[^>]*border-top/i', $wrapped) ? 'yes' : 'no') . "\n";
echo 'has mpdf-ft-top-border: ' . (str_contains($wrapped, 'mpdf-ft-top-border') ? 'yes' : 'no') . "\n";

// crude text sniff in PDF stream
$sample = substr($pdf, 0, 500000);
foreach (['Direccion', 'Dirección', 'Pagina', 'Página', 'mpdf-ft', 'footer'] as $needle) {
    echo "pdf contains '{$needle}': " . (stripos($sample, $needle) !== false ? 'yes' : 'no') . "\n";
}
