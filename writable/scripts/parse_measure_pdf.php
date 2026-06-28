<?php
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$p = dirname(__DIR__) . '/debug/footer_stack_measure_143.pdf';
$parser = new Smalot\PdfParser\Parser();
$pdf = $parser->parseFile($p);
$page = $pdf->getPages()[0];
$details = $page->getTextArray();
echo "=== Text fragments in PDF footer zone ===\n";
foreach ($details as $i => $t) {
    $t = trim($t);
    if ($t === '') {
        continue;
    }
    if (preg_match('/Celular|Correo|Tarija|Página|68724938|gmail|Bolivia|DIAG|amarillo|de 1/u', $t)) {
        echo sprintf("%3d: %s\n", $i, $t);
    }
}
