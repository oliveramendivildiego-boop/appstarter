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

$id = (int) ($argv[1] ?? 0);
$db = \Config\Database::connect();

echo "=== Diagnóstico gráfica tolerancia ===\n\n";

// Buscar prianacategoria Tolerancia de Glucosa
$prias = $db->table('prianacategoria')
    ->like('name', 'Tolerancia', 'both')
    ->orLike('name', 'Glucosa', 'both')
    ->get()
    ->getResultArray();

echo "--- prianacategoria (tolerancia/glucosa) ---\n";
foreach ($prias as $row) {
    $pid = (int) ($row['prianacategoria_id'] ?? 0);
    $graficar = (int) ($row['graficar'] ?? 0);
    echo "  id=$pid name=" . ($row['name'] ?? '') . " compleja=" . ($row['compleja'] ?? '') . " graficar=$graficar\n";
}

if ($id < 1) {
    // Buscar registros recientes con tolerancia
    echo "\n--- registros recientes con prueba tolerancia ---\n";
    $regs = $db->query("
        SELECT r.registro_id, r.pruebas, r.ingreso
        FROM registro r
        WHERE r.pruebas LIKE '%46%' OR r.pruebas REGEXP '[[:<:]]46[[:>:]]'
        ORDER BY r.registro_id DESC
        LIMIT 10
    ")->getResultArray();
    foreach ($regs as $r) {
        echo "  registro_id=" . ($r['registro_id'] ?? '') . " pruebas=" . substr((string) ($r['pruebas'] ?? ''), 0, 80) . "\n";
    }
    if ($regs !== []) {
        $id = (int) ($regs[0]['registro_id'] ?? 0);
        echo "\nUsando registro más reciente: $id\n";
    }
}

if ($id < 1) {
    echo "\nPase registro_id como argumento: php debug_tolerance_chart.php 308\n";
    exit(0);
}

echo "\n=== REGISTRO $id ===\n";
$rm = new \App\Models\RegisterModel();
$rs = new \App\Services\RegisterService($rm, new \App\Models\AppConfigModel());

$refill = $rm->getInfoRefill($id);
echo 'pruebas: ' . trim((string) ($refill->pruebas ?? '')) . "\n";

$data = $rs->prepareReportData($id, false, false);
if (! $data) {
    echo "prepareReportData: NULL\n";
    exit(1);
}

$modos = $data['report_graficar_modo'] ?? [];
$tolerance = $data['report_tolerance_chart'] ?? [];
$heatmaps = $data['report_categorical_heatmap'] ?? [];

echo "\nreport_graficar_modo:\n";
print_r($modos);

echo "\nreport_tolerance_chart keys:\n";
foreach ($tolerance as $pid => $chart) {
    $pts = is_array($chart) ? count($chart['points'] ?? []) : 0;
    echo "  pria $pid => " . ($chart === null ? 'null' : "$pts puntos") . "\n";
}

echo "\nItems glucosa en grupos:\n";
foreach ($data['grupos'] ?? [] as $padre => $items) {
    foreach ($items as $raw) {
        $it = is_array($raw) ? (object) $raw : $raw;
        $nombre = trim((string) ($it->nombre ?? $it->hijo ?? ''));
        if (! preg_match('/glucosa|basal|hora/i', $nombre)) {
            continue;
        }
        echo "  [$padre] $nombre | val=" . ($it->regvalues ?? '') . " | min=" . ($it->valor_min ?? '') . " | max=" . ($it->valor_max ?? '') . " | opcion=" . ($it->opcion_id ?? '') . " | pria=" . ($it->prianacategoria_id ?? '') . "\n";
    }
}

$svc = new \App\Services\ToleranceCurveChartService();
foreach ($data['grupos'] ?? [] as $items) {
    $byPria = [];
    foreach ($items as $raw) {
        $it = is_array($raw) ? (object) $raw : $raw;
        $pid = (int) ($it->prianacategoria_id ?? 0);
        if ($pid > 0) {
            $byPria[$pid][] = $raw;
        }
    }
    foreach ($byPria as $pid => $subItems) {
        $modo = (int) ($modos[$pid] ?? 0);
        if ($modo <= 0) {
            continue;
        }
        $built = $svc->buildFromReportItemsWithModo($subItems, $pid, $modo);
        echo "\nBuild directo pria $pid modo=$modo => " . (is_array($built) ? count($built['points'] ?? []) . ' puntos' : 'null') . "\n";
    }
}

helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);

$hasTol = str_contains($html, 'tol-chart-wrap');
$hasTolSvg = str_contains($html, 'tol-chart-svg');
$hasCurva = str_contains($html, 'Curva de tolerancia');
echo "\nPDF HTML contiene tol-chart-wrap: " . ($hasTol ? 'SI' : 'NO') . "\n";
echo "PDF HTML contiene tol-chart-svg: " . ($hasTolSvg ? 'SI' : 'NO') . "\n";
echo "PDF HTML contiene 'Curva de tolerancia': " . ($hasCurva ? 'SI' : 'NO') . "\n";

if (! $hasTol) {
    $out = WRITEPATH . 'debug/tolerance_diag_' . $id . '.html';
    @mkdir(dirname($out), 0777, true);
    file_put_contents($out, $html);
    echo "HTML guardado en $out para inspección\n";
}
