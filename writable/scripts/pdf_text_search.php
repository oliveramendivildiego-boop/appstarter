<?php
declare(strict_types=1);
$pdfPath = $argv[1] ?? WRITEPATH . 'cache/mpdf_pipeline_308.pdf';
$pdf = file_get_contents($pdfPath);
if ($pdf === false) {
    fwrite(STDERR, "Cannot read $pdfPath\n");
    exit(1);
}
$terms = array_slice($argv, 2);
if ($terms === []) {
    $terms = ['HEMOGRAMA', 'PLAQUETAS', 'HEMATOLOGIA', 'Tiempo de Protrombina', 'Glucosa', 'Serie Roja'];
}
foreach ($terms as $t) {
    $ascii = strpos($pdf, $t) !== false;
    $u = '';
    for ($i = 0, $len = strlen($t); $i < $len; $i++) {
        $u .= $t[$i] . "\0";
    }
    $utf16 = strpos($pdf, $u) !== false;
    echo $t . ': ascii=' . ($ascii ? 'yes' : 'no') . ' utf16=' . ($utf16 ? 'yes' : 'no') . PHP_EOL;
}
