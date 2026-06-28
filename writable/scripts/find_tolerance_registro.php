<?php
declare(strict_types=1);
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$p = new Config\Paths();
require $p->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($p);

$db = \Config\Database::connect();
$prefix = $db->getPrefix();
echo "prefix=$prefix\n";

$regs = $db->table('registro')
    ->select('registro_id, pruebas')
    ->like('pruebas', '250')
    ->orderBy('registro_id', 'DESC')
    ->limit(15)
    ->get()
    ->getResultArray();

echo "Registros con prueba 250:\n";
foreach ($regs as $r) {
    echo '  ' . ($r['registro_id'] ?? '') . ' => ' . ($r['pruebas'] ?? '') . "\n";
}

$id = (int) ($regs[0]['registro_id'] ?? 0);
if ($id > 0) {
    echo "\nRunning tolerance debug for $id\n";
    passthru('php ' . escapeshellarg(__DIR__ . '/debug_tolerance_chart.php') . ' ' . $id);
}
