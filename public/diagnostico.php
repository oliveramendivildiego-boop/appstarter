<?php
/**
 * Diagnóstico mínimo - NO depende de CodeIgniter.
 * Acceder a: http://localhost/john_ci4/public/diagnostico.php
 * ELIMINAR en producción.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Diagnóstico</h1>\n";
echo "<p><strong>1. PHP funciona:</strong> Sí</p>\n";

$basePath = dirname(__DIR__);
$envFile = $basePath . '/.env';
echo "<p><strong>2. Archivo .env:</strong> " . (file_exists($envFile) ? 'Existe' : 'NO EXISTE') . "</p>\n";

$dbConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'laboratorio',
];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (preg_match('/^database\.default\.(\w+)\s*=\s*(.*)$/', $line, $m)) {
            $v = trim($m[2], " \t'\"");
            if ($m[1] === 'hostname') $dbConfig['host'] = $v;
            elseif ($m[1] === 'username') $dbConfig['user'] = $v;
            elseif ($m[1] === 'password') $dbConfig['pass'] = $v;
            elseif ($m[1] === 'database') $dbConfig['name'] = $v;
        }
    }
}

echo "<p><strong>3. Config DB:</strong> host={$dbConfig['host']}, user={$dbConfig['user']}, database={$dbConfig['name']}</p>\n";

$mysqli = @new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['name']);
$dbOk = !$mysqli->connect_error;
if ($mysqli->connect_error) {
    echo "<p><strong>4. Conexión MySQL:</strong> <span style='color:red'>ERROR - " . $mysqli->connect_error . "</span></p>\n";
} else {
    echo "<p><strong>4. Conexión MySQL:</strong> <span style='color:green'>OK</span></p>\n";
    $r = $mysqli->query("SELECT username, person_id FROM dom_employees WHERE deleted = 0");
    if ($r) {
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        echo "<p><strong>5. Empleados activos:</strong> " . count($rows) . "</p>\n";
        if (!empty($rows)) {
            echo "<ul>";
            foreach ($rows as $emp) {
                echo "<li>" . htmlspecialchars($emp['username']) . " (person_id: " . $emp['person_id'] . ")</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p><strong>5. Tabla dom_employees:</strong> <span style='color:red'>ERROR - " . $mysqli->error . "</span></p>\n";
    }
}

$sessionPath = $basePath . '/writable/session';
echo "<p><strong>6. Carpeta session:</strong> " . (is_dir($sessionPath) ? 'Existe' : 'NO existe');
echo " | " . (is_writable($sessionPath) ? 'Escribible' : 'NO escribible') . "</p>\n";

if ($dbOk) {
    $r = $mysqli->query("SHOW TABLES LIKE 'dom_ci_sessions'");
    $ciSessionsExists = $r && $r->num_rows > 0;
    if (!$ciSessionsExists) {
        $create = "CREATE TABLE IF NOT EXISTS `dom_ci_sessions` (
          `id` varchar(128) NOT NULL,
          `ip_address` varchar(45) NOT NULL,
          `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
          `data` blob NOT NULL,
          PRIMARY KEY (`id`),
          KEY `dom_ci_sessions_timestamp` (`timestamp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        if ($mysqli->query($create)) {
            echo "<p><strong>6b. Tabla dom_ci_sessions:</strong> <span style='color:green'>Creada</span></p>\n";
        } else {
            echo "<p><strong>6b. Tabla dom_ci_sessions:</strong> <span style='color:red'>Error - " . htmlspecialchars($mysqli->error) . "</span></p>\n";
        }
    } else {
        echo "<p><strong>6b. Tabla dom_ci_sessions:</strong> <span style='color:green'>Existe</span></p>\n";
    }
}

echo "<hr>";

if ($dbOk) {
    $md5Password = md5('password');
    $res = $mysqli->query("SELECT username, password FROM dom_employees WHERE username='admin' AND deleted=0");
    $adminRow = $res ? $res->fetch_assoc() : null;
    echo "<p><strong>7. Admin actual:</strong> ";
    if ($adminRow) {
        echo "hash=" . htmlspecialchars($adminRow['password']) . " | md5(password)=" . $md5Password;
        echo ($adminRow['password'] === $md5Password) ? " <span style='color:green'>✓ Coinciden</span>" : " <span style='color:orange'>≠ Diferentes</span>";
    } else {
        echo "<span style='color:red'>No encontrado</span>";
    }
    echo "</p>\n";

    if (isset($_GET['reset_admin'])) {
        $r = $mysqli->query("UPDATE dom_employees SET password='" . $mysqli->real_escape_string($md5Password) . "' WHERE username='admin' AND deleted=0");
        if ($r) {
            if ($mysqli->affected_rows > 0) {
                echo "<p style='color:green'><strong>Contraseña de admin actualizada.</strong> Usa: admin / password</p>";
            } else {
                echo "<p style='color:green'><strong>La contraseña ya estaba configurada.</strong> Prueba: admin / password</p>";
            }
        } else {
            echo "<p style='color:red'>Error SQL: " . htmlspecialchars($mysqli->error) . "</p>";
        }
    } else {
        echo "<p><a href='?reset_admin=1'>Restablecer contraseña de admin a: password</a></p>";
    }
    echo "<p><strong>Credenciales de prueba:</strong> usuario <code>admin</code> / contraseña <code>password</code></p>";
    $mysqli->close();
}

echo "<p><a href='index.php/login'>Ir al login</a></p>\n";
