<?php
declare(strict_types=1);

/**
 * Asegura columna es_separador y marca filas título existentes.
 * Uso: php writable/scripts/ensure_es_separador.php
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
$table = $db->prefixTable('secanacategoria');

if (! $db->fieldExists('es_separador', $table)) {
    $db->query('ALTER TABLE ' . $table . ' ADD COLUMN es_separador TINYINT(1) NOT NULL DEFAULT 0');
    echo "Columna es_separador agregada en {$table}\n";
} else {
    echo "Columna es_separador ya existe en {$table}\n";
}

$sql = "
    UPDATE {$table}
    SET es_separador = 1
    WHERE (deleted = 0 OR deleted IS NULL)
      AND opcion_id = 3
      AND formulas_id = 1
      AND (valor_min IS NULL OR TRIM(valor_min) = '')
      AND (valor_max IS NULL OR TRIM(valor_max) = '')
      AND (
            UPPER(nombre) LIKE '%MUESTRA%'
         OR UPPER(nombre) LIKE '%MUETRA%'
         OR UPPER(nombre) LIKE 'EXAMEN %'
      )
";
$db->query($sql);
$updated = $db->affectedRows();
echo "Separadores marcados: {$updated}\n";

$check = $db->table('secanacategoria')
    ->where('prianacategoria_id', 6)
    ->where('es_separador', 1)
    ->orderBy('orden', 'ASC')
    ->get()->getResultArray();
echo "Parasitológico seriado (pria=6) separadores=" . count($check) . PHP_EOL;
foreach ($check as $row) {
    echo '  - ' . ($row['nombre'] ?? '') . PHP_EOL;
}
