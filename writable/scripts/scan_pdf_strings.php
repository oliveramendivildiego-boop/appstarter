<?php
function scanPdf(string $path): void
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        echo "$path: unreadable\n";
        return;
    }
    preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)/s', $raw, $m);
    $hits = array_filter($m[0], static function (string $s): bool {
        return stripos($s, 'footer') !== false
            || stripos($s, 'Direccion') !== false
            || stripos($s, 'FOOTER') !== false
            || stripos($s, 'Celular') !== false
            || stripos($s, 'Tarija') !== false
            || stripos($s, 'Méndez') !== false
            || stripos($s, 'Mendez') !== false;
    });
    echo basename($path) . ': ' . count($hits) . " string hits\n";
    foreach (array_slice(array_values($hits), 0, 8) as $h) {
        echo '  ' . substr($h, 0, 100) . "\n";
    }
}

foreach ($argv as $i => $arg) {
    if ($i === 0) {
        continue;
    }
    scanPdf($arg);
}
