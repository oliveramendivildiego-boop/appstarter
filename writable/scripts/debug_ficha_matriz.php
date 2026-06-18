<?php
declare(strict_types=1);

$id = (int) ($argv[1] ?? 1);
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
defined('CI_DEBUG') || define('CI_DEBUG', true);

define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$m = model(\App\Models\FichaClinicaModel::class);
$cfg = $m->getMatrizConfig($id);
foreach ($cfg['bloques'] ?? [] as $bloque) {
    $bid = $bloque['id'] ?? '?';
    $filas = (int) ($bloque['filas'] ?? 0);
    $cols = (int) ($bloque['columnas'] ?? 0);
    echo "Bloque {$bid}: filas={$filas} cols={$cols}\n";
    if ($filas >= 12 && $cols >= 1) {
        $cell = $bloque['celdas'][11][0] ?? null;
        echo '  F12 C1: ' . json_encode($cell, JSON_UNESCAPED_UNICODE) . "\n";
    }
}
