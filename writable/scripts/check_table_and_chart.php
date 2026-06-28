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

$id = (int) ($argv[1] ?? 143);
$rm = new \App\Models\RegisterModel();
$rs = new \App\Services\RegisterService($rm, new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id, false, false);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);

$hasTable = preg_match('/GLUCOSA BASAL/i', $html) && preg_match('/<table[^>]*class="[^"]*results/i', $html);
$hasChart = str_contains($html, 'tol-chart-wrap');
preg_match_all('/<table[^>]*class="([^"]*results[^"]*)"/', $html, $tables);
echo "registro $id\n";
echo "modo graficar: " . json_encode($data['report_graficar_modo'] ?? []) . "\n";
echo "tabla resultados (GLUCOSA BASAL en table.results): " . ($hasTable ? 'SI' : 'NO') . "\n";
echo "grafica tol-chart-wrap: " . ($hasChart ? 'SI' : 'NO') . "\n";
echo "cantidad table.results: " . count($tables[0] ?? []) . "\n";

$out = WRITEPATH . 'debug/tolerance_table_check_' . $id . '.html';
file_put_contents($out, $html);
echo "saved: $out\n";
