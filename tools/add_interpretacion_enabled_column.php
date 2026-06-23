<?php
$m = new mysqli('localhost','root','','laboratorio');
if ($m->connect_error) {
    echo 'CONNECT_ERR:' . $m->connect_error . PHP_EOL;
    exit(1);
}
$sql = "ALTER TABLE dom_doctors ADD COLUMN interpretacion_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER display_mode";
if ($m->query($sql) === TRUE) {
    echo "OK\n";
} else {
    echo 'ERR:' . $m->error . PHP_EOL;
}
$m->close();
