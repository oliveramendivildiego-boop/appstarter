<?php
$raw = file_get_contents($argv[1] ?? '');
preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)/s', $raw, $m);
echo 'total strings: ' . count($m[0]) . PHP_EOL;
foreach (array_slice($m[0], 0, 30) as $s) {
    echo substr($s, 0, 120) . PHP_EOL;
}
