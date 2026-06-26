<?php
$r = file_get_contents($argv[1]);
foreach (['Contenido', 'Lorem', 'FOOTER', 'Direccion'] as $n) {
    echo "$n: " . (str_contains($r, $n) ? 'yes' : 'no') . PHP_EOL;
}
