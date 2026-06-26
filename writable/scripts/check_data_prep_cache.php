<?php
declare(strict_types=1);
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$p = new Config\Paths();
require $p->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($p);

foreach (array_slice($argv, 1) ?: ['288', '302'] as $rawId) {
    $id = (int) $rawId;
    $path = WRITEPATH . 'cache/report_data_prep/registro_' . $id . '.dat';
    if (! is_file($path)) {
        echo "ID $id: no data cache\n";
        continue;
    }
    $data = @unserialize(file_get_contents($path), ['allowed_classes' => true]);
    $info = $data['register_info'] ?? null;
    $rid = (int) ($info->registro_id ?? 0);
    $name = trim(($info->first_name ?? '') . ' ' . ($info->last_name_fa ?? ''));
    $grupos = array_keys($data['grupos'] ?? []);
    echo "ID $id path ok | registro_id in blob=$rid | patient: $name | grupos: " . implode(', ', array_slice($grupos, 0, 6)) . "\n";
    if ($rid !== $id) {
        echo "  *** MISMATCH registro_id in cache blob ***\n";
    }
}
