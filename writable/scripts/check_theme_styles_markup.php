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

$layout = (new App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$html = view('registers/partials/report_pdf_theme_styles', [
    'pdf_layout' => $layout,
    'embed_stylesheet_for_pdf' => false,
]);

if (preg_match('/<\/style>\s*\.report-cultivo-seccion/s', $html)) {
    fwrite(STDERR, "BUG: CSS cultivo fuera de <style>\n");
    exit(1);
}
$open = substr_count($html, '<style>');
$close = substr_count($html, '</style>');
if ($open !== $close) {
    fwrite(STDERR, "BUG: style desbalanceado {$open} vs {$close}\n");
    exit(1);
}

echo "OK\n";
