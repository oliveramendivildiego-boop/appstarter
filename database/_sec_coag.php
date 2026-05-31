<?php
$m = new mysqli('localhost', 'root', '', 'laboratorio');
$r = $m->query('SELECT secanacategoria_id, nombre, paciente_id, sexo FROM dom_secanacategoria WHERE prianacategoria_id=231');
while ($x = $r->fetch_assoc()) {
    echo json_encode($x, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
