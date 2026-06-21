<?php
declare(strict_types=1);

$html = file_get_contents(dirname(__DIR__) . '/debug/report_262.html');
$bodyStart = stripos($html, '<body');
$bodyEnd = stripos($html, '</body>');
$body = substr($html, $bodyStart, $bodyEnd - $bodyStart);

echo 'body size: ' . round(strlen($body) / 1024, 1) . " KB\n";
echo 'data:image: ' . substr_count($body, 'data:image') . "\n";
echo 'base64 chunks: ' . preg_match_all('/data:[^;]+;base64,[A-Za-z0-9+\/=]{100,}/', $body) . "\n";

preg_match_all('/data:[^;]+;base64,([A-Za-z0-9+\/=]+)/', $body, $b64);
$b64total = 0;
foreach ($b64[1] as $chunk) {
    $b64total += strlen($chunk);
}
echo 'base64 payload: ' . round($b64total / 1024, 1) . " KB\n";

echo 'style= attrs: ' . substr_count($body, 'style=') . "\n";
echo 'inline style bytes (approx): ';
preg_match_all('/style="([^"]*)"/', $body, $st);
$stTotal = 0;
foreach ($st[1] as $s) { $stTotal += strlen($s); }
echo round($stTotal / 1024, 1) . " KB\n";

// largest repeated strings
$chunks = preg_split('/(<(?:table|div|tr|p)[^>]*>)/', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
$freq = [];
foreach ($chunks as $c) {
    if (strlen($c) < 200) continue;
    $key = substr($c, 0, 80);
    $freq[$key] = ($freq[$key] ?? 0) + 1;
}
arsort($freq);
echo "Repeated large chunk prefixes:\n";
$i = 0;
foreach ($freq as $k => $v) {
    if ($v < 3) break;
    echo "  x{$v}: " . substr(str_replace("\n", ' ', $k), 0, 70) . "\n";
    if (++$i >= 8) break;
}
