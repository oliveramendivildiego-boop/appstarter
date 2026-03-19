<?php
/**
 * Script de prueba para diagnosticar sesiones activas
 */

// Cargar CodeIgniter
require_once __DIR__ . '/../app/Config/Constants.php';

// Mostrar información de sesiones
echo "<h1>Diagnóstico de Sesiones</h1>";

$sessionPath = WRITEPATH . 'session';
echo "<h2>Ruta de sesiones: " . $sessionPath . "</h2>";

// Verificar si la ruta existe
if (!is_dir($sessionPath)) {
    echo "<div style='color: red;'>❌ La carpeta de sesiones NO existe</div>";
} else {
    echo "<div style='color: green;'>✅ La carpeta de sesiones existe</div>";

    // Listar archivos de sesión
    $files = glob($sessionPath . '/ci_session_*');
    
    if (!is_array($files) || count($files) === 0) {
        echo "<div style='color: orange;'>⚠️ No hay archivos de sesión (ci_session_*)</div>";
    } else {
        echo "<div style='color: green;'>✅ Encontrados " . count($files) . " archivo(s) de sesión</div>";
        echo "<h3>Detalles de sesiones:</h3>";
        
        foreach ($files as $file) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
            echo "<strong>Archivo:</strong> " . basename($file) . "<br>";
            echo "<strong>Tamaño:</strong> " . filesize($file) . " bytes<br>";
            echo "<strong>Última modificación:</strong> " . date('Y-m-d H:i:s', filemtime($file)) . "<br>";
            
            // Intentar leer el contenido
            $content = @file_get_contents($file);
            if ($content !== false) {
                // Buscar person_id
                if (preg_match('/s:9:"person_id";i:(\d+);/', $content, $matches)) {
                    echo "<strong>Person ID encontrado:</strong> " . $matches[1] . "<br>";
                    echo "<span style='color: green;'>✅ person_id extraído correctamente</span>";
                } else {
                    echo "<span style='color: red;'>❌ No se encontró person_id en los datos serializados</span><br>";
                    echo "<strong>Contenido (primeros 500 chars):</strong><br>";
                    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";
                }
            } else {
                echo "<span style='color: red;'>❌ No se pudo leer el archivo</span>";
            }
            
            echo "</div>";
        }
    }
}

// Información de configuración
echo "<h2>Configuración de sesión:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Configuración</th><th>Valor</th></tr>";
echo "<tr><td>WRITEPATH</td><td>" . WRITEPATH . "</td></tr>";
echo "<tr><td>SESSION_DRIVER</td><td>" . (defined('SESSION_DRIVER') ? SESSION_DRIVER : 'No definido') . "</td></tr>";
echo "<tr><td>APP_DEBUG</td><td>" . (defined('CI_DEBUG') ? (CI_DEBUG ? 'Sí' : 'No') : 'No definido') . "</td></tr>";
echo "</table>";

// Verificar permisos
echo "<h2>Permisos de carpeta:</h2>";
if (is_writable($sessionPath)) {
    echo "<div style='color: green;'>✅ La carpeta tiene permisos de escritura</div>";
} else {
    echo "<div style='color: red;'>❌ La carpeta NO tiene permisos de escritura</div>";
}
?>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background-color: #f5f5f5;
    }
    h1, h2 { color: #333; }
    table { background-color: white; border-collapse: collapse; }
    pre { background-color: #eee; padding: 10px; overflow-x: auto; }
</style>
