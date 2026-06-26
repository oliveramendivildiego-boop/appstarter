<?php
require __DIR__ . '/../../vendor/autoload.php';

$ref = new ReflectionClass(\App\Libraries\Pdf\MpdfFontMapper::class);
$method = $ref->getMethod('restoreHumanFontFamiliesInInlineStyle');
$method->setAccessible(true);

$samples = [
    'font-family:"DejaVu Sans" !important;font-size:12pt !important;font-weight:bold !important;',
    "font-family:'DejaVu Sans' !important;font-size:12pt !important;",
    'font-family:dejavusans;font-weight:bold;',
];

foreach ($samples as $s) {
    echo $method->invoke(null, $s) . PHP_EOL;
}
