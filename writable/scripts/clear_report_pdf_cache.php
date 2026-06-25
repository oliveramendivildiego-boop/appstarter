<?php
declare(strict_types=1);

$registroId = isset($argv[1]) ? (int) $argv[1] : 0;

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

$dir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'report_pdf_preview';

if ($registroId > 0) {
    $rs->clearReportPdfPreviewCache($registroId);
    echo "Caché PDF limpiada para registro {$registroId}\n";
} elseif (is_dir($dir)) {
    foreach (glob($dir . DIRECTORY_SEPARATOR . 'registro_*.pdf*') ?: [] as $file) {
        @unlink($file);
        echo 'Eliminado: ' . basename($file) . "\n";
    }
    echo "Caché PDF de reportes vaciada.\n";
} else {
    echo "No existe directorio de caché.\n";
}
