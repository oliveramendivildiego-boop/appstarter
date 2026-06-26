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

$ids = array_slice($argv, 1) ?: ['288', '298', '302'];
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();

foreach ($ids as $rawId) {
    $id = (int) $rawId;
    $data = $rs->prepareReportData($id, false, false);
    if (! $data) {
        echo "ID $id: NO DATA\n\n";
        continue;
    }
    $pac = trim(($data['register_info']->first_name ?? '') . ' ' . ($data['register_info']->last_name_fa ?? ''));
    $url = $rs->publicReportViewerUrlForQr($id);
    $emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
    $qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
    $html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);

    preg_match_all('/report-pdf-grupo-cabecera-line--title[^>]*>([^<]{2,120})</', $html, $titles);
    preg_match_all('/report-pdf-grupo-prueba[^>]*>([^<]{2,80})</', $html, $pruebas);

    echo "=== ID $id | $pac ===\n";
    echo "grupos titles: " . implode(' | ', array_unique($titles[1] ?? [])) . "\n";
    echo "pruebas padre: " . implode(' | ', array_unique(array_slice($pruebas[1] ?? [], 0, 8))) . "\n";
    echo "HEMOGRAMA: " . substr_count($html, 'HEMOGRAMA') . " ORINA: " . substr_count($html, 'ORINA') . "\n";
    echo "html len: " . strlen($html) . "\n";
    echo "contains paciente name: " . (stripos($html, $data['register_info']->first_name ?? '') !== false ? 'yes' : 'no') . "\n\n";
}
