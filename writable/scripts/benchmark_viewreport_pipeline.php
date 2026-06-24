<?php
declare(strict_types=1);

/**
 * Benchmark del pipeline viewreport → PDF (sin modificar lógica clínica ni salida visual).
 * Uso: php writable/scripts/benchmark_viewreport_pipeline.php [registro_id]
 */

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Libraries\PdfService;
use App\Services\PrianacategoriaReferenceService;
use App\Services\RegisterService;
use App\Services\ReportPdfLayoutService;
use Dompdf\Dompdf;
use Dompdf\Options;

$registroId = (int) ($argv[1] ?? 268);
if ($registroId < 1) {
    fwrite(STDERR, "Uso: php benchmark_viewreport_pipeline.php <registro_id>\n");
    exit(1);
}

$timings = [];
$queryLog = [];
$ms = static function (float $start): float {
    return round((microtime(true) - $start) * 1000, 2);
};

$db = \Config\Database::connect();
$collectedQueries = [];
// Escuchar consultas SQL vía eventos CI4
\CodeIgniter\Events\Events::on('DBQuery', static function ($query) use (&$collectedQueries) {
    $collectedQueries[] = [
        'sql'    => $query->getQuery(),
        'time'   => (float) ($query->getDuration() ?? 0),
        'caller' => '',
    ];
});

function analyzeHtml(string $html): array
{
    $base64Images = preg_match_all('/data:image\/[^;]+;base64,[A-Za-z0-9+\/=]+/', $html, $m) ? count($m[0]) : 0;
    $base64Bytes = 0;
    if (! empty($m[0])) {
        foreach ($m[0] as $uri) {
            $base64Bytes += strlen($uri);
        }
    }
    $tableCount = preg_match_all('/<table\b/i', $html, $t) ?: 0;
    $trCount = preg_match_all('/<tr\b/i', $html, $tr) ?: 0;
    $nestedTable = (bool) preg_match('/<table[^>]*>[\s\S]*?<table/i', $html);
    $pageBreakRules = preg_match_all('/page-break-(before|after|inside)/i', $html, $pb) ?: 0;
    $positionFixed = (bool) preg_match('/position\s*:\s*fixed/i', $html);
    $atPage = preg_match_all('/@page\b/i', $html, $ap) ?: 0;
    $borderCollapse = (bool) preg_match('/border-collapse\s*:\s*collapse/i', $html);

    return [
        'html_bytes'           => strlen($html),
        'html_kb'              => round(strlen($html) / 1024, 1),
        'table_count'          => $tableCount,
        'tr_count'             => $trCount,
        'nested_tables'        => $nestedTable,
        'base64_image_count'   => $base64Images,
        'base64_embedded_kb'   => round($base64Bytes / 1024, 1),
        'page_break_css_hits'  => $pageBreakRules,
        'position_fixed'       => $positionFixed,
        'at_page_rules'        => $atPage,
        'border_collapse'      => $borderCollapse,
        'total_pages_token'    => str_contains($html, '__PDF_TOTAL_PAGES__'),
    ];
}

function countGruposItems(?array $data): array
{
    $grupos = $data['grupos'] ?? [];
    $items = 0;
    $maxGrupo = 0;
    foreach ($grupos as $padre => $rows) {
        $c = is_countable($rows) ? count($rows) : 0;
        $items += $c;
        $maxGrupo = max($maxGrupo, $c);
    }

    return [
        'grupo_count'    => count($grupos),
        'item_count'     => $items,
        'max_items_grupo'=> $maxGrupo,
        'analisis_rows'  => count($data['analisis'] ?? []),
    ];
}

function dompdfPhaseTimings(string $html, ?array $pageSize, bool $probeOnly = false): array
{
    $options = new Options();
    $options->set('isHtml5ParserEnabled', false);
    $options->set('isRemoteEnabled', ! $probeOnly);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isFontSubsettingEnabled', true);
    $tempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'dompdf';
    if (is_dir($tempDir)) {
        $options->set('tempDir', $tempDir);
        $options->set('fontCache', $tempDir);
    }

    $dompdf = new Dompdf($options);
    if (is_array($pageSize) && isset($pageSize['key'])) {
        $key = (string) $pageSize['key'];
        if (in_array($key, ['letter', 'a4', 'legal'], true)) {
            $dompdf->setPaper($key, 'portrait');
        }
    } else {
        $dompdf->setPaper('letter', 'portrait');
    }

    $t0 = microtime(true);
    $dompdf->loadHtml($html, 'UTF-8');
    $loadMs = round((microtime(true) - $t0) * 1000, 2);

    $t1 = microtime(true);
    $dompdf->render();
    $renderMs = round((microtime(true) - $t1) * 1000, 2);

    $t2 = microtime(true);
    $out = $dompdf->output();
    $outputMs = round((microtime(true) - $t2) * 1000, 2);
    $pages = (int) $dompdf->getCanvas()->get_page_count();

    return [
        'loadHtml_ms'  => $loadMs,
        'render_ms'    => $renderMs,
        'output_ms'    => $outputMs,
        'total_ms'     => round($loadMs + $renderMs + $outputMs, 2),
        'pages'        => $pages,
        'pdf_bytes'    => strlen($out),
        'pdf_kb'       => round(strlen($out) / 1024, 1),
    ];
}

echo "=== Benchmark viewreport pipeline — registro {$registroId} ===\n\n";

$rs = new RegisterService();
$totalStart = microtime(true);

// --- viewreport: repair ---
$t = microtime(true);
$repairCount = (new PrianacategoriaReferenceService())->repairRegvaluesForRegistro($registroId);
$timings['repair_regvalues_ms'] = $ms($t);

// --- prepareReportData (1ª vez, como viewreport) ---
$collectedQueries = [];
$t = microtime(true);
$data = $rs->prepareReportData($registroId);
$timings['prepareReportData_1_ms'] = $ms($t);
$queriesAfterPrep1 = count($collectedQueries);
$sqlTimePrep1 = array_sum(array_column($collectedQueries, 'time'));

if (! $data) {
    fwrite(STDERR, "Registro {$registroId} no encontrado o sin datos.\n");
    exit(1);
}

$struct = countGruposItems($data);

// --- viewreport extras (simulado) ---
$t = microtime(true);
$pdfLayout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$labConfig = $rs->getLabConfig();
$layoutCtx = $rs->buildReportLayoutContext($data, $pdfLayout, $labConfig);
$timings['viewreport_extras_ms'] = $ms($t);

// --- prepareReportData (2ª vez, como pdf endpoint) ---
$collectedQueries2 = [];
$t = microtime(true);
$data2 = $rs->prepareReportData($registroId);
$timings['prepareReportData_2_ms'] = $ms($t);
$queriesAfterPrep2 = count($collectedQueries2);

// --- pdf endpoint overhead ---
helper('qr');
$t = microtime(true);
$reportUrl = site_url('resultados/token-demo');
$qrPx = ReportPdfLayoutService::qrImagePixelSizeFromLayout($pdfLayout);
$qrDataUri = qr_base64($reportUrl, $qrPx);
$emitidoEn = $rs->lockReportEmitidoEnForPrintOrPdf($registroId);
$fingerprint = $rs->reportPdfPreviewCacheFingerprint($registroId, $data2, $emitidoEn, $pdfLayout);
$timings['pdf_endpoint_setup_ms'] = $ms($t);

// --- cache check ---
$t = microtime(true);
$cachedPdf = $rs->readReportPdfPreviewCache($registroId, $fingerprint);
$timings['cache_read_ms'] = $ms($t);
$cacheHit = $cachedPdf !== null;

// --- render HTML ---
$t = microtime(true);
$html = $rs->renderReportPdfHtml($data2, $reportUrl, $qrDataUri, $emitidoEn);
$timings['renderReportPdfHtml_ms'] = $ms($t);
$htmlStats = analyzeHtml($html);

// --- Dompdf: probe (si aplica) ---
$pageSize = ReportPdfLayoutService::resolveGlobalPageSizeMm($labConfig);
$pdfService = new PdfService();
$needsProbe = (new ReflectionMethod($pdfService, 'htmlNeedsPageCountProbe'))->invoke($pdfService, $html);
$timings['dompdf_needs_page_probe'] = $needsProbe;

$dompdfProbe = null;
if ($needsProbe) {
    $dompdfProbe = dompdfPhaseTimings($html, $pageSize, true);
    $timings['dompdf_probe_loadHtml_ms'] = $dompdfProbe['loadHtml_ms'];
    $timings['dompdf_probe_render_ms'] = $dompdfProbe['render_ms'];
    $timings['dompdf_probe_output_ms'] = $dompdfProbe['output_ms'];
    $timings['dompdf_probe_total_ms'] = $dompdfProbe['total_ms'];
    $timings['dompdf_probe_pages'] = $dompdfProbe['pages'];
}

// --- Dompdf: render final (fases) ---
$finalHtml = $html;
if ($needsProbe && $dompdfProbe !== null) {
    $pageCount = max(1, $dompdfProbe['pages']);
    $finalHtml = str_replace('__PDF_TOTAL_PAGES__', (string) $pageCount, $html);
    $finalHtml = str_replace('data-total="__PDF_TOTAL_PAGES__"', 'data-total="' . $pageCount . '"', $finalHtml);
}

$dompdfFinal = dompdfPhaseTimings($finalHtml, $pageSize, false);
$timings['dompdf_final_loadHtml_ms'] = $dompdfFinal['loadHtml_ms'];
$timings['dompdf_final_render_ms'] = $dompdfFinal['render_ms'];
$timings['dompdf_final_output_ms'] = $dompdfFinal['output_ms'];
$timings['dompdf_final_total_ms'] = $dompdfFinal['total_ms'];
$timings['dompdf_final_pages'] = $dompdfFinal['pages'];
$timings['dompdf_combined_ms'] = ($dompdfProbe['total_ms'] ?? 0) + $dompdfFinal['total_ms'];

// --- PdfService.generate (comparación) ---
$t = microtime(true);
$pdfBinary = $pdfService->generate($html, 'bench.pdf', $pageSize);
$timings['pdfService_generate_ms'] = $ms($t);

// --- cache write ---
$t = microtime(true);
$rs->writeReportPdfPreviewCache($registroId, $fingerprint, $pdfBinary);
$timings['cache_write_ms'] = $ms($t);

$cachePath = WRITEPATH . 'cache/report_pdf_preview/registro_' . $registroId . '.pdf';
$cacheMetaPath = $cachePath . '.meta';

$timings['total_server_pipeline_ms'] = $ms($totalStart);

// --- Query analysis (re-run with fresh log) ---
$collectedQueries = [];
$t = microtime(true);
$rs->prepareReportData($registroId);
$prepSqlMs = $ms($t);
$sqlCount = count($collectedQueries);
$sqlTotalMs = array_sum(array_column($collectedQueries, 'time'));

// Normalize SQL for duplicate detection
$normalized = [];
foreach ($collectedQueries as $q) {
    $norm = preg_replace('/\s+/', ' ', trim($q['sql']));
    $norm = preg_replace('/=\s*\d+/', '= ?', $norm);
    $normalized[] = $norm;
}
$dupCounts = array_count_values($normalized);
arsort($dupCounts);
$duplicates = array_filter($dupCounts, static fn(int $c): bool => $c > 1);

// Catalog-like queries
$catalogPatterns = ['tipo_muestra', 'metodo', 'app_config', 'anacategoria', 'prianacategoria', 'formulas'];
$catalogHits = [];
foreach ($collectedQueries as $q) {
    foreach ($catalogPatterns as $pat) {
        if (stripos($q['sql'], $pat) !== false) {
            $catalogHits[$pat] = ($catalogHits[$pat] ?? 0) + 1;
        }
    }
}

// --- Output report ---
echo "## 1. Estructura de datos clínicos\n";
foreach ($struct as $k => $v) {
    echo "  {$k}: {$v}\n";
}
echo "\n";

echo "## 2. Tiempos del pipeline (ms)\n";
$phases = [
    'repair_regvalues_ms'         => 'repairRegvalues (viewreport)',
    'prepareReportData_1_ms'      => 'prepareReportData #1 (viewreport)',
    'viewreport_extras_ms'        => 'Layout + labConfig (viewreport)',
    'prepareReportData_2_ms'      => 'prepareReportData #2 (pdf endpoint) — DUPLICADO',
    'pdf_endpoint_setup_ms'       => 'QR + emitidoEn + fingerprint',
    'cache_read_ms'               => 'Lectura caché PDF (' . ($cacheHit ? 'HIT' : 'MISS') . ')',
    'renderReportPdfHtml_ms'      => 'renderReportPdfHtml',
    'dompdf_probe_total_ms'       => 'Dompdf probe (1er render)',
    'dompdf_final_total_ms'       => 'Dompdf final (2do render)',
    'dompdf_combined_ms'          => 'Dompdf total (probe+final)',
    'pdfService_generate_ms'      => 'PdfService::generate (oficial)',
    'cache_write_ms'              => 'Escritura caché PDF',
    'total_server_pipeline_ms'    => 'TOTAL servidor (simulado)',
];
foreach ($phases as $key => $label) {
    if (! isset($timings[$key])) {
        continue;
    }
    $val = $timings[$key];
    if ($key === 'dompdf_probe_total_ms' && ! $needsProbe) {
        echo "  {$label}: N/A (sin probe)\n";
        continue;
    }
    echo "  {$label}: {$val} ms\n";
}
echo "\n";

echo "## 3. Dompdf — desglose por fase (ms)\n";
if ($needsProbe) {
    echo "  [PROBE]  loadHtml: {$timings['dompdf_probe_loadHtml_ms']} | render: {$timings['dompdf_probe_render_ms']} | output: {$timings['dompdf_probe_output_ms']}\n";
}
echo "  [FINAL]  loadHtml: {$timings['dompdf_final_loadHtml_ms']} | render: {$timings['dompdf_final_render_ms']} | output: {$timings['dompdf_final_output_ms']}\n";
echo "  Páginas PDF: {$timings['dompdf_final_pages']}\n";
echo "  Probe activo: " . ($needsProbe ? 'SÍ (doble render)' : 'NO') . "\n\n";

echo "## 4. HTML generado\n";
foreach ($htmlStats as $k => $v) {
    $display = is_bool($v) ? ($v ? 'sí' : 'no') : $v;
    echo "  {$k}: {$display}\n";
}
echo "\n";

echo "## 5. SQL en prepareReportData (una ejecución)\n";
echo "  Consultas: {$sqlCount}\n";
echo "  Tiempo SQL reportado: " . round($sqlTotalMs, 2) . " ms\n";
echo "  Tiempo wall-clock prepareReportData: {$prepSqlMs} ms\n";
echo "  % SQL vs wall: " . ($prepSqlMs > 0 ? round(100 * $sqlTotalMs / $prepSqlMs, 1) : 0) . "%\n";
echo "  Consultas duplicadas (top 5):\n";
$i = 0;
foreach ($duplicates as $sql => $cnt) {
    if ($i++ >= 5) {
        break;
    }
    $short = strlen($sql) > 120 ? substr($sql, 0, 117) . '...' : $sql;
    echo "    [{$cnt}x] {$short}\n";
}
echo "  Hits catálogos:\n";
foreach ($catalogHits as $pat => $cnt) {
    echo "    {$pat}: {$cnt}\n";
}
echo "\n";

echo "## 6. Caché — tamaños\n";
echo "  PDF generado: {$dompdfFinal['pdf_kb']} KB ({$dompdfFinal['pdf_bytes']} bytes)\n";
echo "  HTML: {$htmlStats['html_kb']} KB\n";
if (is_file($cachePath)) {
    echo "  Caché disco PDF: " . round(filesize($cachePath) / 1024, 1) . " KB\n";
}
echo "  Base64 embebido en HTML: {$htmlStats['base64_embedded_kb']} KB ({$htmlStats['base64_image_count']} imágenes)\n";
echo "\n";

echo "## 7. Reparto tiempo servidor (cache MISS)\n";
$sqlBoth = $timings['prepareReportData_1_ms'] + $timings['prepareReportData_2_ms'];
$dompdfMs = $timings['pdfService_generate_ms'];
$renderMs = $timings['renderReportPdfHtml_ms'];
$otherMs = $timings['total_server_pipeline_ms'] - $sqlBoth - $dompdfMs - $renderMs;
$total = max(1, $timings['total_server_pipeline_ms']);
echo "  prepareReportData (x2): {$sqlBoth} ms (" . round(100 * $sqlBoth / $total, 1) . "%)\n";
echo "  renderReportPdfHtml: {$renderMs} ms (" . round(100 * $renderMs / $total, 1) . "%)\n";
echo "  Dompdf (PdfService): {$dompdfMs} ms (" . round(100 * $dompdfMs / $total, 1) . "%)\n";
echo "  Otros: " . round($otherMs, 2) . " ms (" . round(100 * $otherMs / $total, 1) . "%)\n";
echo "\n";

echo "## 8. PDF.js (estimado cliente, no medido en CLI)\n";
echo "  Descarga PDF (~{$dompdfFinal['pdf_kb']} KB): depende de red\n";
echo "  Parse PDF.js: ~50-200 ms típico para " . $timings['dompdf_final_pages'] . " página(s)\n";
echo "  Render pág.1 @ 135%: ~30-150 ms por página\n";
echo "  Render todas las páginas secuenciales: ~" . ($timings['dompdf_final_pages'] * 80) . "-" . ($timings['dompdf_final_pages'] * 150) . " ms estimado\n";
echo "  NOTA: lazy render (solo pág.1 primero) reduciría TTFP ~" . max(0, $timings['dompdf_final_pages'] - 1) . " páginas de canvas\n";
echo "\n";

echo "=== FIN ===\n";
