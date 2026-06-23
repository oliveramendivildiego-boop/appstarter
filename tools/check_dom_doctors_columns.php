<?php
$m = new mysqli('localhost','root','','laboratorio');
if ($m->connect_error) {
    echo 'CONNECT_ERR:' . $m->connect_error . PHP_EOL;
    exit(1);
}
$res = $m->query('SHOW COLUMNS FROM dom_doctors');
if (!$res) {
    echo 'ERR:' . $m->error . PHP_EOL;
    exit(1);
}
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . '|' . $row['Type'] . '|' . ($row['Default'] ?? 'NULL') . PHP_EOL;
}
