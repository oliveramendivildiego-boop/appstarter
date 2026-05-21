<?php
/**
 * Vista previa: nombres del CSV sin coincidencia exacta en dom_prianacategoria.name
 * Ejecutar: php database/preview_no_actualizados.php
 */
$csvPath = 'C:/Users/Diego/Downloads/LISTA DE PRECIOS PRUEBAS COMPARATIVOS  QUANTUM.csv';

$mysqli = new mysqli('localhost', 'root', '', 'laboratorio');
if ($mysqli->connect_error) {
    die('Error conexion: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

$names = [];
$res = $mysqli->query('SELECT TRIM(name) AS n FROM dom_prianacategoria WHERE deleted = 0 OR deleted IS NULL');
while ($row = $res->fetch_assoc()) {
    $names[$row['n']] = true;
}

$lines = file($csvPath, FILE_IGNORE_NEW_LINES);
$ok = 0;
$no = [];

foreach ($lines as $i => $line) {
    if ($i === 0) {
        continue;
    }
    $p = explode(';', $line);
    if (count($p) < 6) {
        continue;
    }
    $desc = trim($p[2]);
    if ($desc === '' || stripos($desc, 'Sub Grupo') !== false) {
        continue;
    }
    $raw = trim($p[5]);
    if (!preg_match('/^(\d+)/', $raw)) {
        continue;
    }
    $key = $desc;
    $keyUpper = mb_strtoupper($desc, 'UTF-8');
    $found = isset($names[$desc]);
    if (!$found) {
        foreach (array_keys($names) as $dbName) {
            if (mb_strtoupper($dbName, 'UTF-8') === $keyUpper) {
                $found = true;
                break;
            }
        }
    }
    if (!$found) {
        $no[] = [trim($p[1]), $desc, (int) preg_replace('/\D.*/', '', $raw)];
    } else {
        $ok++;
    }
}

$matched = [];
foreach ($lines as $i => $line) {
    if ($i === 0) {
        continue;
    }
    $p = explode(';', $line);
    if (count($p) < 6) {
        continue;
    }
    $desc = trim($p[2]);
    if ($desc === '' || stripos($desc, 'Sub Grupo') !== false) {
        continue;
    }
    $raw = trim($p[5]);
    if (!preg_match('/^(\d+)/', $raw)) {
        continue;
    }
    if (isset($names[$desc])) {
        $matched[] = $desc;
    }
}

echo "CSV con precio valido: " . ($ok + count($no)) . PHP_EOL;
echo "Coinciden (nombre exacto): {$ok}" . PHP_EOL;
echo "No encontrados: " . count($no) . PHP_EOL . PHP_EOL;

if ($matched) {
    echo "--- SI COINCIDEN ---" . PHP_EOL;
    foreach ($matched as $m) {
        echo "  " . $m . PHP_EOL;
    }
    echo PHP_EOL;
}

echo "--- NO ENCONTRADOS (primeros 30) ---" . PHP_EOL;
foreach (array_slice($no, 0, 30) as $x) {
    echo $x[0] . ' | ' . $x[1] . ' | precio=' . $x[2] . PHP_EOL;
}
if (count($no) > 30) {
    echo '... y ' . (count($no) - 30) . ' mas (ver SQL SELECT motivo NO_ENCONTRADO_EN_SISTEMA)' . PHP_EOL;
}

$mysqli->close();
