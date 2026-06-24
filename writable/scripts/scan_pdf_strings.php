<?php
declare(strict_types=1);

$pdf = (string) file_get_contents(dirname(__DIR__) . '/debug/report_262_fresh.pdf');
// Buscar texto literal o UTF-16 en streams
$patterns = ['1 de 4', '2 de 4', '3 de 4', '4 de 4', 'Página'];
foreach ($patterns as $p) {
    echo $p . ': ' . (str_contains($pdf, $p) ? 'yes' : 'no') . PHP_EOL;
}
if (preg_match_all('/\(([^\\\\\)]{4,30})\)/', $pdf, $m)) {
    foreach ($m[1] as $s) {
        if (str_contains($s, 'de') || str_contains($s, 'gina')) {
            echo 'lit: ' . $s . PHP_EOL;
        }
    }
}
