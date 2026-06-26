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

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$sec    = is_array($layout['section_layouts']['footer'] ?? null) ? $layout['section_layouts']['footer'] : [];
echo 'footer section: cols=' . ($sec['columns'] ?? '?') . ' rows=' . ($sec['rows'] ?? '?') . ' row_gap=' . ($sec['row_gap_px'] ?? '?') . "\n\n";

foreach ($layout['instances'] as $inst) {
    if (($inst['section'] ?? '') !== 'footer' || empty($inst['enabled'])) {
        continue;
    }
    $type = (string) ($inst['element_type'] ?? '');
    echo json_encode([
        'type'    => $type,
        'col'     => $inst['column'] ?? 0,
        'span'    => $inst['column_span'] ?? 1,
        'row'     => $inst['grid_row'] ?? null,
        'stack'   => $inst['grid_stack'] ?? null,
        'align_h' => $inst['align_h'] ?? null,
        'align_v' => $inst['align_v'] ?? null,
        'ts'      => $inst['text_style'] ?? [],
        'custom_text' => $inst['custom_text'] ?? null,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
}
