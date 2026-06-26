<?php
declare(strict_types=1);

/**
 * Genera un PDF mínimo y reporta qué motor se usó realmente.
 * Uso: php writable/scripts/probe_pdf_engine_live.php [registro_id]
 */
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$registroId = (int) ($argv[1] ?? 302);
$pdfConfig    = config('Pdf');
$registerSvc  = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);

echo "=== Config ===\n";
echo 'PDF_RENDERER: ' . ($pdfConfig->renderer ?? '?') . "\n";
echo 'CHROME_EXECUTABLE_PATH (config): ' . (($pdfConfig->executablePath ?? '') !== '' ? $pdfConfig->executablePath : '(vacío)') . "\n";
echo 'env CHROME_EXECUTABLE_PATH: ' . var_export(env('CHROME_EXECUTABLE_PATH'), true) . "\n";
echo 'fallbackToDompdf: ' . (($pdfConfig->fallbackToDompdf ?? false) ? 'sí' : 'no') . "\n";

$chromeCandidates = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    trim((string) ($pdfConfig->executablePath ?? '')),
    trim((string) env('CHROME_EXECUTABLE_PATH'), " \t\"'"),
];
foreach ($chromeCandidates as $c) {
    if ($c === '') {
        continue;
    }
    echo 'exists ' . $c . ': ' . (is_file($c) ? 'sí' : 'NO') . "\n";
}

$data = $registerSvc->prepareReportData($registroId);
if ($data === null) {
    fwrite(STDERR, "Registro {$registroId} no encontrado.\n");
    exit(1);
}

helper('qr');
$reportUrl = 'http://localhost/resultados/' . $registroId;
$emitidoEn = $registerSvc->lockReportEmitidoEnForPrintOrPdf($registroId);
$qrLayout  = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$qrPx      = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($qrLayout);
$qrDataUri = qr_base64($reportUrl, $qrPx);
$html      = $registerSvc->renderReportPdfHtml($data, $reportUrl, $qrDataUri, $emitidoEn);
$pageSize  = \App\Services\ReportPdfLayoutService::resolveGlobalPageSizeMm($registerSvc->getLabConfig());

echo "\n=== Generación (registro {$registroId}) ===\n";
$t0 = microtime(true);
$binary = (new \App\Libraries\PdfService())->generate($html, 'probe.pdf', $pageSize);
$ms = round((microtime(true) - $t0) * 1000, 1);
$engine = \App\Libraries\Pdf\PdfRendererFactory::lastRenderEngine();

$out = WRITEPATH . 'cache/probe_pdf_engine_' . $registroId . '.pdf';
file_put_contents($out, $binary);

echo "Motor usado: {$engine}\n";
echo "Tiempo: {$ms} ms\n";
echo 'Tamaño: ' . round(strlen($binary) / 1024, 1) . " KB\n";
echo "Guardado: {$out}\n";

if ($engine === 'dompdf-fallback') {
    echo "\n*** Chromium falló y se usó Dompdf como respaldo. Revise writable/logs.\n";
    exit(2);
}

if ($engine === 'chromium') {
    echo "\nChromium funcionó correctamente.\n";
    exit(0);
}

echo "\nMotor: {$engine}\n";
exit(0);
