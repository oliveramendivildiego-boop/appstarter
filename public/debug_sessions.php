<?php
/**
 * Script de depuración detallado de sesiones
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar constantes
require_once __DIR__ . '/../app/Config/Constants.php';

echo "<style>
    body { font-family: Courier; font-size: 12px; margin: 20px; background: #f5f5f5; }
    .session { background: white; border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 4px; }
    .found { color: green; font-weight: bold; }
    .error { color: red; }
    .info { color: blue; }
    pre { background: #eee; padding: 10px; overflow-x: auto; border-left: 3px solid #999; }
    table { border-collapse: collapse; width: 100%; background: white; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f0f0f0; }
</style>";

echo '<h1>Depuración de Sesiones</h1>';

$sessionPath = WRITEPATH . 'session';
echo "<h2>Ruta: " . htmlspecialchars($sessionPath) . "</h2>";

// Verificar existencia
if (!is_dir($sessionPath)) {
    echo '<p class="error">❌ Carpeta NO existe</p>';
    exit;
}

echo '<p class="found">✅ Carpeta existe</p>';
echo '<p class="info">Permisos: ' . substr(sprintf('%o', fileperms($sessionPath)), -4) . '</p>';

// Listar archivos
$files = glob($sessionPath . '/ci_session_*');
echo "<h3>Archivos encontrados: " . count($files) . "</h3>";

if (empty($files)) {
    echo '<p class="error">❌ No hay archivos de sesión</p>';
} else {
    echo '<table>';
    echo '<tr><th>Archivo</th><th>Tamaño</th><th>Modificado</th><th>Person ID</th><th>Datos</th></tr>';
    
    foreach ($files as $file) {
        $content = @file_get_contents($file);
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        $personId = null;
        
        // Intentar extraer person_id
        if (preg_match('/s:9:"person_id";i:(\d+);/', $content, $matches)) {
            $personId = $matches[1];
        } elseif (preg_match('/s:9:"person_id";s:\d+:"(\d+)";/', $content, $matches)) {
            $personId = $matches[1];
        }
        
        // Intentar deserializar
        $unserialized = null;
        $unserializeError = null;
        
        if ($content !== false) {
            // Intentar deserializar directamente
            set_error_handler(function($errno, $errstr) {
                // Ignorar errores de desserialización
            });
            $unserialized = @unserialize($content);
            restore_error_handler();
            
            if ($unserialized === false && $content !== '') {
                $unserializeError = 'Failed to unserialize or empty after unserialize';
            }
        }
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars(basename($file)) . '</td>';
        echo '<td>' . $size . ' bytes</td>';
        echo '<td>' . $modified . '</td>';
        echo '<td>' . ($personId ? '<span class="found">✅ ' . $personId . '</span>' : '<span class="error">❌ No found</span>') . '</td>';
        echo '<td>';
        
        if ($unserialized !== false && is_array($unserialized)) {
            echo '<pre>Array keys: ' . implode(', ', array_keys($unserialized)) . '</pre>';
            if (isset($unserialized['person_id'])) {
                echo '<div class="found">✅ person_id en unserialized: ' . htmlspecialchars((string)$unserialized['person_id']) . '</div>';
            }
        } else {
            echo '<small class="info">Raw (first 200 chars):</small><br>';
            echo '<pre>' . htmlspecialchars(substr((string)$content, 0, 200)) . '...</pre>';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
}

// Información adicional
echo '<h2>Información de Sistema</h2>';
echo '<table>';
echo '<tr><th>Parámetro</th><th>Valor</th></tr>';
echo '<tr><td>WRITEPATH</td><td>' . WRITEPATH . '</td></tr>';
echo '<tr><td>SESSION_DRIVER</td><td>File (codeigniter config)</td></tr>';
echo '<tr><td>Sesiones activas</td><td>' . count($files) . '</td></tr>';
echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
echo '</table>';

?>
