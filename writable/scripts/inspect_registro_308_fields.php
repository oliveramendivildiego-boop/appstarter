<?php

declare(strict_types=1);

$mysqli = new mysqli('localhost', 'root', '', 'laboratorio');
if ($mysqli->connect_error) {
    fwrite(STDERR, $mysqli->connect_error . PHP_EOL);
    exit(1);
}

$rid = (int) ($argv[1] ?? 308);
$res = $mysqli->query("SELECT registro_id FROM dom_registro WHERE registro_id = {$rid} LIMIT 1");
if (!$res || !$res->fetch_assoc()) {
    echo "Registro {$rid} no encontrado\n";
    exit(1);
}

$names = ['basofil', 'cayad', 'vsg'];
$sql = "SELECT s.secanacategoria_id, s.nombre, s.es_separador, s.formulas_id, s.opcion_id, s.valor_min, s.valor_max, s.umedida, p.name AS prueba
        FROM dom_secanacategoria s
        JOIN dom_prianacategoria p ON p.prianacategoria_id = s.prianacategoria_id
        WHERE (s.deleted = 0 OR s.deleted IS NULL)
          AND (" . implode(' OR ', array_map(static fn ($n) => "LOWER(s.nombre) LIKE '%{$n}%'", $names)) . ")
        ORDER BY s.orden, s.secanacategoria_id";
$q = $mysqli->query($sql);
while ($row = $q->fetch_assoc()) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
