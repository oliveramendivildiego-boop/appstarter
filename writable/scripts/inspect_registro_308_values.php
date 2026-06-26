<?php
$m = new mysqli('localhost', 'root', '', 'laboratorio');
$ids = [86, 23, 115, 130, 549];
foreach ($ids as $id) {
    $name = 'c_' . $id;
    $r = $m->query("SELECT name, regvalues FROM dom_regvalues WHERE registro_id = 308 AND name = '{$name}' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    echo $name . ': ' . json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
