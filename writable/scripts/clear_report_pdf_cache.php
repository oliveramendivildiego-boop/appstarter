<?php
declare(strict_types=1);
putenv('CI_ENVIRONMENT=development');
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 308);
(new \App\Services\Report\ReportPdfHtmlCacheService())->clear($id);
$previewDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'report_pdf_preview';
foreach (['.pdf', '.pdf.meta'] as $ext) {
    $p = $previewDir . DIRECTORY_SEPARATOR . 'registro_' . $id . $ext;
    if (is_file($p)) {
        @unlink($p);
    }
}
echo "Cleared report_pdf_html + report_pdf_preview cache for registro {$id}\n";
