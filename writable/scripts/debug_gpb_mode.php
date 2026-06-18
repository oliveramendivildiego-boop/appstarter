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

$ls = new \App\Services\ReportPdfLayoutService();
$layout = $ls->getActiveLayoutForRender();
$gpb = \App\Services\ReportPdfLayoutService::normalizeGrupoPruebaPageBreakStyle($layout['page_style']['grupo_prueba_page_break'] ?? []);
echo 'mode: ' . ($gpb['mode'] ?? '') . PHP_EOL;
echo 'min_remaining_mm: ' . ($gpb['min_remaining_mm_to_force_break'] ?? 0) . PHP_EOL;
