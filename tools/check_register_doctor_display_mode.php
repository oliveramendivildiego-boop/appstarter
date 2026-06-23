<?php
$regId = $argv[1] ?? '268';
$m = new mysqli('localhost','root','','laboratorio');
if ($m->connect_error) { echo 'CONNECT_ERR:' . $m->connect_error . PHP_EOL; exit(1); }
$res = $m->query("SELECT doctor_id FROM dom_registro WHERE registro_id = " . intval($regId) . " LIMIT 1");
if (!$res) { echo 'ERR:' . $m->error . PHP_EOL; exit(1); }
$row = $res->fetch_assoc();
$did = $row['doctor_id'] ?? null;
echo "registro_id={$regId} doctor_id=" . ($did ?? 'NULL') . PHP_EOL;
if ($did) {
    $r2 = $m->query("SELECT doctor_id, name, display_mode FROM dom_doctors WHERE doctor_id = " . intval($did) . " LIMIT 1");
    if ($r2) {
        $d = $r2->fetch_assoc();
        if ($d) {
            echo "doctor_id={$d['doctor_id']} name={$d['name']} display_mode={$d['display_mode']}\n";
        } else {
            echo "no doctor row found\n";
        }
    } else {
        echo 'ERR:' . $m->error . PHP_EOL;
    }
}
