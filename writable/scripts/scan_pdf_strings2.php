<?php
declare(strict_types=1);

$path = $argv[1] ?? dirname(__DIR__) . '/cache/dompdf_test_308.pdf';
$bin = (string) file_get_contents($path);

// decompress streams
$out = preg_replace_callback(
    '/stream\r?\n(.*?)\r?\nendstream/s',
    static function (array $m): string {
        $raw = $m[1];
        if (str_starts_with($raw, "\x78\x9c") || str_starts_with($raw, "\x78\x01")) {
            $decoded = @gzuncompress($raw);
            if ($decoded !== false) {
                return "stream\n" . $decoded . "\nendstream";
            }
        }

        return $m[0];
    },
    $bin
);

$needles = ['ATENTAMENTE', 'Verificado', 'Ximena', 'Marisol', 'Página', '1 de 4', '2 de 4', 'de 4'];
foreach ($needles as $n) {
    echo "$n: " . (str_contains($out, $n) ? 'yes' : 'no') . PHP_EOL;
}

if (preg_match_all('/\(([^\)]{2,60})\)/', $out, $m)) {
    foreach ($m[1] as $s) {
        $s = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $s);
        if (preg_match('/Ximena|Marisol|ATENTAMENTE|Verificado|Página| de /u', $s)) {
            echo "lit: $s\n";
        }
    }
}
