<?php
declare(strict_types=1);

$id = (int) ($argv[1] ?? 255);
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
    'analisis_variant'                => 'browser_print',
    'paciente'                        => $d['paciente'] ?? null,
    'register_info'                   => $d['register_info'] ?? null,
]);

if (! preg_match_all('/<div class="([^"]*report-pdf-grupo-prueba[^"]*)"/i', $html, $ms)) {
    echo "Sin grupos\n";
    exit(1);
}

foreach ($ms[1] as $i => $cls) {
    echo 'grupo_' . $i . ': ' . $cls . "\n";
}
echo "\nORINA blocks:\n";
if (preg_match_all('/<div class="([^"]*report-pdf-grupo-prueba[^"]*)"[^>]*>[\s\S]*?ORINA[\s\S]{0,120}?keep-on-page/si', $html, $om)) {
    echo "found\n";
}
if (preg_match('/ORINA[\s\S]{0,5000}?<\/div>\s*<\/div>\s*(?:<div class="report-lab-firma|<\/div>)/si', $html, $chunk)) {
    if (preg_match('/report-pdf-grupo-prueba[^"]*/', $chunk[0], $gc)) {
        echo 'near orina: ' . $gc[0] . "\n";
    }
}
$last = end($ms[1]);
echo "\nLAST: " . $last . "\n";
echo 'split-segments-only in last: ' . (str_contains($last, 'split-segments-only') ? 'YES' : 'NO') . "\n";
echo 'keep-on-page in last: ' . (str_contains($last, 'keep-on-page') ? 'YES' : 'NO') . "\n";
