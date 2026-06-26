<?php
declare(strict_types=1);

$path = $argv[1] ?? dirname(__DIR__) . '/cache/dompdf_test_308.pdf';
$bin  = file_get_contents($path);
if ($bin === false) {
    fwrite(STDERR, "Cannot read {$path}\n");
    exit(1);
}

echo 'file: ' . $path . PHP_EOL;
echo 'has Pagina label: ' . (preg_match('/P[aá]gina/i', $bin) ? 'yes' : 'no') . PHP_EOL;
preg_match_all('/\d+ de \d+/', $bin, $matches);
$unique = array_values(array_unique($matches[0] ?? []));
echo 'page patterns: ' . count($unique) . PHP_EOL;
foreach ($unique as $m) {
    echo '  - ' . $m . PHP_EOL;
}
