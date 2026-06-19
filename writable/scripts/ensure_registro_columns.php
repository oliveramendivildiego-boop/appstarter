<?php
declare(strict_types=1);
/**
 * Repara columnas frecuentes ausentes por migraciones pendientes.
 * Uso: php writable/scripts/ensure_registro_columns.php
 */
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$db = Config\Database::connect();
$table = $db->prefixTable('registro');

$columns = [
    'comentario_resultado' => 'ALTER TABLE %s ADD COLUMN comentario_resultado TEXT NULL',
];

foreach ($columns as $col => $sqlTpl) {
    if ($db->fieldExists($col, $table)) {
        echo "ok {$col}\n";
        continue;
    }
    $db->query(sprintf($sqlTpl, $table));
    echo "added {$col}\n";
}
