<?php
declare(strict_types=1);
foreach (glob(dirname(__DIR__) . '/debug/footer_dup_test_*.pdf') as $path) {
    $pdf = file_get_contents($path);
    $name = basename($path);
    echo $name . PHP_EOL;
    foreach (['Direccion', 'Aniceto', 'biocenter', 'Cochabamba', 'Telefonos'] as $n) {
        $c = substr_count($pdf, $n);
        if ($c > 0) {
            echo "  {$n}: {$c}\n";
        }
    }
    // decompress streams
    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams)) {
        $text = '';
        foreach ($streams[1] as $raw) {
            $dec = @gzuncompress($raw);
            if ($dec === false) {
                $dec = @gzdecode($raw);
            }
            if (is_string($dec)) {
                $text .= $dec;
            }
        }
        foreach (['Direccion', 'Aniceto', 'biocenter', 'Cochabamba'] as $n) {
            $c = substr_count($text, $n);
            echo "  [decompressed] {$n}: {$c}\n";
        }
    }
    echo PHP_EOL;
}
