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

$id = (int) ($argv[1] ?? 307);
$rm = new \App\Models\RegisterModel();
$rs = new \App\Services\RegisterService($rm, new \App\Models\AppConfigModel());

echo "=== REGISTRO $id ===\n\n";

$refill = $rm->getInfoRefill($id);
echo "pruebas CSV: " . trim((string) ($refill->pruebas ?? '')) . "\n";
echo "paciente: " . trim(($refill->first_name ?? '') . ' ' . ($refill->last_name_fa ?? '')) . "\n\n";

echo "--- regvalues (analisis) ---\n";
$analisis = $rm->getInfoAnalisis($id);
foreach ($analisis as $row) {
    echo '  ' . ($row['name'] ?? '') . ' = ' . substr((string) ($row['regvalues'] ?? ''), 0, 60) . "\n";
}
echo 'total regvalues: ' . count($analisis) . "\n\n";

$data = $rs->prepareReportData($id, false, false);
if (! $data) {
    echo "prepareReportData: NULL\n";
    exit(1);
}

echo "--- grupos en prepareReportData ---\n";
$totalItems = 0;
foreach ($data['grupos'] ?? [] as $padre => $items) {
    echo "AREA: $padre (" . count($items) . " items)\n";
    foreach ($items as $it) {
        $o = is_array($it) ? (object) $it : $it;
        $hijo = trim((string) ($o->hijo ?? $o->nombre ?? ''));
        $val = trim((string) ($o->regvalues ?? ''));
        $sep = (int) ($o->es_separador ?? 0) === 1 ? ' [SEP]' : '';
        echo "  - $hijo | valor=$val | pria=" . (int) ($o->prianacategoria_id ?? 0) . "$sep\n";
        $totalItems++;
    }
}
echo "total items en grupos: $totalItems\n\n";

$shell = $rs->prepareViewreportPageData($id);
echo "viewreport shell grupos areas: " . count($shell['grupos'] ?? []) . "\n";

helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);
preg_match_all('/report-pdf-grupo-cabecera-line--title[^>]*>([^<]{2,120})</', $html, $titles);
echo "\nPDF HTML titles: " . implode(' | ', array_unique($titles[1] ?? [])) . "\n";
