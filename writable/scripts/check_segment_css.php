<?php

declare(strict_types=1);

define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$db = \Config\Database::connect();
$row = $db->table('report_pdf_templates')->where('id', 4)->get()->getRowArray();
if (! $row) {
    echo "no template 4\n";
    exit(1);
}
$svc = new \App\Services\ReportPdfLayoutService();
$layoutJson = (string) ($row['layout_json'] ?? '{}');
$normalized = $svc->normalizeLayout($layoutJson);
$layout = json_decode($layoutJson, true);
$rs = $normalized['page_style']['results_table'] ?? [];
echo "RAW segment_font_size_pt: " . ($layout['page_style']['results_table']['segment_font_size_pt'] ?? 'MISSING') . "\n";
echo "NORM segment_font_size_pt: " . ($rs['segment_font_size_pt'] ?? 'MISSING') . "\n";
echo "NORM segment_font_weight: " . ($rs['segment_font_weight'] ?? 'MISSING') . "\n";
echo "NORM segment_text_color: " . ($rs['segment_text_color'] ?? 'MISSING') . "\n";
echo "NORM segment_bg_color: " . ($rs['segment_bg_color'] ?? 'MISSING') . "\n";

$css = \App\Services\ReportPdfLayoutService::buildResultsTableParityCss('.pdf-rs-block', $normalized);
if (preg_match('/\.report-segment-title:not\([^{]+\{([^}]+)\}/s', $css, $m)) {
    echo "\nSEGMENT CSS BLOCK:\n" . trim($m[1]) . "\n";
} else {
    echo "\nNO SEGMENT RULE\n";
}
