<?php
declare(strict_types=1);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
helper('qr');

$registroId = (int) ($argv[1] ?? 308);
$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

$outHtml = dirname(__DIR__) . '/debug/firma_diag_' . $registroId . '.html';
@mkdir(dirname($outHtml), 0777, true);
file_put_contents($outHtml, $html);

echo "saved html: $outHtml\n";
echo 'lab_firmas enabled: ' . (\App\Services\ReportPdfLayoutService::isLabFirmasBlockEnabled($data['pdf_layout'] ?? []) ? 'yes' : 'no') . PHP_EOL;
echo 'placement per_group: ' . (\App\Services\ReportPdfLayoutService::labFirmasPlacementShowsPerGroup(
    \App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle($data['pdf_layout']['page_style']['lab_firmas'] ?? [])
) ? 'yes' : 'no') . PHP_EOL;
echo 'report_lab_firmas count: ' . count($data['report_lab_firmas'] ?? []) . PHP_EOL;
echo 'inline firma divs: ' . substr_count($html, 'report-lab-firma-grupo-inline') . PHP_EOL;
echo 'lab-firmas-body-grid: ' . substr_count($html, 'lab-firmas-body-grid') . PHP_EOL;
echo 'validator in html: ' . (str_contains($html, 'Verificado') ? 'yes' : 'no') . PHP_EOL;
echo 'approver in html: ' . (str_contains($html, 'Ximena') || str_contains($html, 'Marisol') ? 'yes' : 'no') . PHP_EOL;
echo 'img in firma: ' . (preg_match('/report-lab-firma-grupo-inline[\s\S]{0,3000}<img/s', $html) ? 'yes' : 'no') . PHP_EOL;

// Posición: firma después de última tabla por área
$dom = new DOMDocument();
@$dom->loadHTML($html);
$x = new DOMXPath($dom);
foreach ($x->query("//div[contains(@class,'report-pdf-grupo-prueba')]") as $area) {
    if (! $area instanceof DOMElement) {
        continue;
    }
    $idx = $area->getAttribute('data-layout-area-index');
    $sep = $x->query(".//div[contains(@class,'report-pdf-grupo-area-separator')]", $area)->item(0);
    $title = $sep ? trim(preg_replace('/\s+/', ' ', $sep->textContent)) : '?';
    $firma = $x->query(".//div[contains(@class,'report-lab-firma-grupo-inline')]", $area)->item(0);
    $tables = $x->query(".//table[contains(@class,'results')]", $area);
    $lastTable = $tables->length > 0 ? $tables->item($tables->length - 1) : null;
    $firmaAfterLast = 'n/a';
    if ($firma instanceof DOMElement && $lastTable instanceof DOMElement) {
        $firmaAfterLast = ($firma->compareDocumentPosition($lastTable) & DOMNode::DOCUMENT_POSITION_FOLLOWING) ? 'yes' : 'no';
    }
    $text = $firma ? trim(preg_replace('/\s+/', ' ', substr(strip_tags($firma->textContent ?? ''), 0, 120))) : '(no firma node)';
    echo "area $idx [$title] firma_after_last_table=$firmaAfterLast text=[$text]" . PHP_EOL;
}

// Generar PDF y buscar contenido
$renderer = new App\Libraries\Pdf\DompdfPdfRenderer();
$pdf = $renderer->generateDompdf($html, null);
$pdfPath = dirname(__DIR__) . '/debug/firma_diag_' . $registroId . '.pdf';
file_put_contents($pdfPath, $pdf);
echo "saved pdf: $pdfPath (" . round(strlen($pdf) / 1024, 1) . " KB)\n";

// Extraer streams descomprimidos
$decoded = preg_replace_callback(
    '/stream\r?\n(.*?)\r?\nendstream/s',
    static function (array $m): string {
        $raw = $m[1];
        if (str_starts_with($raw, "\x78\x9c") || str_starts_with($raw, "\x78\x01")) {
            $d = @gzuncompress($raw);
            if ($d !== false) {
                return "stream\n" . $d . "\nendstream";
            }
        }
        return $m[0];
    },
    $pdf
);
foreach (['Verificado', 'ATENTAMENTE', 'Ximena', 'Marisol', 'HEMATOLOGIA'] as $needle) {
    echo "pdf contains [$needle]: " . (str_contains($decoded, $needle) ? 'yes' : 'no') . PHP_EOL;
}
