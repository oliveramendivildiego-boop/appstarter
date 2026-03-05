<?php
/**
 * Script puntual para añadir columna registro_id a dom_reactivo_movimiento.
 * Ejecutar una vez: http://localhost/john_ci4/public/fix_reactivo_registro.php
 * Luego eliminar este archivo por seguridad.
 */
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);
$pathsPath = FCPATH . '../app/Config/Paths.php';
require realpath($pathsPath) ?: $pathsPath;
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$prefix = $db->getPrefix();
$table = $prefix . 'reactivo_movimiento';

try {
    $db->query("ALTER TABLE `{$table}` ADD COLUMN `registro_id` INT DEFAULT NULL COMMENT 'Orden asociada (opcional)'");
    echo "<p style='color:green'>Columna registro_id añadida correctamente a {$table}.</p>";
} catch (\Throwable $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<p style='color:blue'>La columna registro_id ya existe. No es necesario hacer nada.</p>";
    } else {
        echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
echo "<p><a href='" . (rtrim(config('App')->baseURL, '/') ?: '/') . "'>Volver al inicio</a></p>";
