<?php
$bin = file_get_contents($argv[1] ?? 'writable/cache/dompdf_test_308.pdf');
$needles = array_slice($argv, 2);
if ($needles === []) {
    $needles = ['HEMATOLOGIA', 'COAGULOGRAMA', 'QUIMICA', 'Paciente', 'Orden', 'Glucosa'];
}
foreach ($needles as $n) {
    echo $n . ': ' . (str_contains($bin, $n) ? 'yes' : 'no') . PHP_EOL;
}
