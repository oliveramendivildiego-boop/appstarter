<?php
$m = new mysqli('localhost','root','','laboratorio');
if ($m->connect_error) { echo 'CONNECT_ERR: ' . $m->connect_error . PHP_EOL; exit(1); }
$found = false;
$res = $m->query("SHOW TABLES");
while ($row = $res->fetch_row()) {
    foreach ($row as $col) {
        if (stripos($col, 'migrations') !== false) {
            echo "TABLE:" . $col . PHP_EOL;
            $found = true;
            $r2 = $m->query("SELECT * FROM $col");
            if ($r2) {
                while ($r = $r2->fetch_assoc()) {
                    echo ($r['version'] ?? '') . '|' . ($r['name'] ?? '') . '|' . ($r['time'] ?? '') . PHP_EOL;
                }
            }
        }
    }
}
if (! $found) echo "NO_MIGRATIONS_TABLE\n";
$m->close();
