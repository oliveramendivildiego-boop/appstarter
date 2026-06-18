<?php
declare(strict_types=1);

$id = (int) ($argv[1] ?? 255);
$av = (string) ($argv[2] ?? 'browser_print');

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$rs = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);
$d = $rs->prepareReportData($id);
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
helper('registro');

$html = view('registers/pdf/blocks/results', [
    'grupos'                          => $d['grupos'],
    'pdf_layout'                      => $layout,
    'lab_config'                      => $d['lab_config'] ?? [],
    'report_lab_firmas'               => $d['report_lab_firmas'] ?? [],
    'report_pria_tipo_muestra_nombre' => $d['report_pria_tipo_muestra_nombre'] ?? [],
    'report_pria_metodo_nombre'       => $d['report_pria_metodo_nombre'] ?? [],
    'report_pria_refs_consolidada'    => $d['report_pria_refs_consolidada'] ?? [],
    'analisis_variant'                => $av,
    'paciente'                        => $d['paciente'] ?? null,
    'register_info'                   => $d['register_info'] ?? null,
    'doctor'                          => $d['doctor'] ?? null,
]);

if (! preg_match_all('/<div class="([^"]*report-pdf-grupo-prueba[^"]*)"/i', $html, $ms)) {
    echo "Sin grupos\n";
    exit(1);
}

$last = end($ms[1]);
echo "variant={$av}\n";
echo 'last_grupo_classes: ' . html_entity_decode($last) . "\n";
echo 'keep-on-page: ' . (str_contains($last, 'keep-on-page') ? 'YES' : 'NO') . "\n";
echo 'allow-split: ' . (str_contains($last, 'allow-split') ? 'YES' : 'NO') . "\n";
echo 'split-segments-only: ' . (str_contains($last, 'split-segments-only') ? 'YES' : 'NO') . "\n";
echo 'compact: ' . (str_contains($last, 'compact') ? 'YES' : 'NO') . "\n";

if (preg_match('/ORINA[\s\S]{0,8000}report-segment-table-wrap/gi', $html, $m)) {
    echo 'orina_segment_wraps: ' . substr_count($m[0], 'report-segment-table-wrap') . "\n";
}
