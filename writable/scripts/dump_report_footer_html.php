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

$registroId = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);
$data = $rs->prepareReportData($registroId);
if ($data === null) {
    fwrite(STDERR, "Registro no encontrado\n");
    exit(1);
}
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($registroId);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($registroId);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));

$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);
$out = WRITEPATH . 'debug/report_html_' . $registroId . '.html';
@mkdir(dirname($out), 0755, true);
file_put_contents($out, $html);

$start = strpos($html, '<!-- report-pdf-footer:start -->');
$end = strpos($html, '<!-- report-pdf-footer:end -->');
if ($start !== false && $end !== false) {
    $footer = substr($html, $start, $end - $start + strlen('<!-- report-pdf-footer:end -->'));
    file_put_contents(WRITEPATH . 'debug/report_footer_' . $registroId . '.html', $footer);
    echo "Footer length: " . strlen($footer) . PHP_EOL;
    echo substr($footer, 0, 3000) . PHP_EOL;
}
echo "Full HTML: $out\n";

// bindings
$bindings = (new \App\Services\ReportPdfLayoutService())->getResultTemplateBindingsForReport();
echo "pdf template id: " . ($bindings['pdf']['resolved_template_id'] ?? '?') . PHP_EOL;
