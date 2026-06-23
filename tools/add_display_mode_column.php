<?php
$m = new mysqli('localhost','root','','laboratorio');
if ($m->connect_error) { echo 'CONNECT_ERR:' . $m->connect_error . PHP_EOL; exit(1); }
$sql = "ALTER TABLE dom_doctors ADD COLUMN display_mode VARCHAR(32) NOT NULL DEFAULT 'clinico' AFTER hide_commission_details";
if ($m->query($sql) === TRUE) {
    echo "OK\n";
} else {
    echo 'ERR:' . $m->error . PHP_EOL;
}
