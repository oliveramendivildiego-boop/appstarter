<?php
declare(strict_types=1);
$pdf = file_get_contents(dirname(__DIR__) . '/debug/dup_test_footer_only.pdf');
$text = '';
if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams)) {
    foreach ($streams[1] as $raw) {
        $dec = @gzuncompress($raw);
        if ($dec === false) {
            $dec = @gzdecode($raw);
        }
        if (is_string($dec)) {
            $text .= $dec . "\n";
        }
    }
}
// Also raw scan
$raw = $pdf . $text;
foreach (['Paciente', 'Direccion', 'LORENA', 'Aniceto'] as $n) {
    echo "{$n}: raw=" . substr_count($pdf, $n) . " decompressed=" . substr_count($text, $n) . "\n";
}
// TJ arrays often split text - count (Paciente) or Tj near Paciente
preg_match_all('/\(([^)]*Paciente[^)]*)\)/', $raw, $m);
echo 'PDF literal strings with Paciente: ' . count($m[1]) . "\n";
foreach ($m[1] as $s) {
    echo '  ' . $s . "\n";
}
