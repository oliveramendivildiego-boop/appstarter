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
foreach ($d['grupos'] as $padre => $items) {
    if (stripos($padre, 'orina') === false) {
        continue;
    }
    echo "=== Registro {$id} [{$padre}] " . count($items) . " items ===\n";
    $prias = [];
    foreach ($items as $it) {
        $o = is_array($it) ? (object) $it : $it;
        $pid = (int) ($o->prianacategoria_id ?? 0);
        $h = trim((string) ($o->hijo ?? ''));
        $n = trim((string) ($o->nombre ?? ''));
        $prias[$pid] = $h !== '' ? $h : ($n !== '' ? $n : ('pria_' . $pid));
    }
    echo implode(', ', array_values($prias)) . "\n";
}
