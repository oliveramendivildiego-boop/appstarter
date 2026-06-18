<?php
/**
 * Genera HTML/PDF de prueba. Uso: php writable/scripts/debug_pdf_html.php 253
 */
declare(strict_types=1);

$registroId = (int) ($argv[1] ?? 0);
if ($registroId < 1) {
    fwrite(STDERR, "Uso: php writable/scripts/debug_pdf_html.php <registro_id>\n");
    exit(1);
}

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
defined('CI_DEBUG') || define('CI_DEBUG', true);

define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$registerService = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);

$data = $registerService->prepareReportData($registroId);
if ($data === null) {
    fwrite(STDERR, "Registro {$registroId} no encontrado.\n");
    exit(1);
}

helper('qr');
$reportUrl = 'https://example.test/report/' . $registroId;
$layout    = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$qrPx      = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout);
$qrDataUri = qr_base64($reportUrl, $qrPx);
$html      = $registerService->renderReportPdfHtml($data, $reportUrl, $qrDataUri, date('d/m/Y H:i:s'));

$outDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'debug';
if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$htmlFile = $outDir . DIRECTORY_SEPARATOR . "report_{$registroId}.html";
file_put_contents($htmlFile, $html);

$interCount      = substr_count($html, 'report-grupo-inter-page-break');
$newAreaCount    = substr_count($html, 'report-pdf-grupo-pdf-new-area');
$afterBreakCount = substr_count($html, 'report-pdf-grupo-pdf-area-end-break');
$markerCount     = substr_count($html, 'report-pdf-area-break:v3');

$pdfFile = $outDir . DIRECTORY_SEPARATOR . "report_{$registroId}.pdf";
$pdfService = new \App\Libraries\PdfService();
$pdfBinary = $pdfService->generate($html, "report_{$registroId}.pdf");
file_put_contents($pdfFile, $pdfBinary);

$pageCount = 0;
if (preg_match_all('/\/Type\s*\/Page[^s]/', $pdfBinary, $m)) {
    $pageCount = count($m[0]);
}

echo "HTML: {$htmlFile}\n";
echo "PDF:  {$pdfFile}\n";
echo "Marcador v3: {$markerCount}\n";
echo "Separadores inter-page-break: {$interCount}\n";
echo "Clase pdf-new-area (break-before): {$newAreaCount}\n";
echo "Fin de area break-after: {$afterBreakCount}\n";
echo "Paginas PDF (aprox): {$pageCount}\n";
