<?php
$pdf = (string) file_get_contents(dirname(__DIR__) . '/debug/order_sheet_test_262.pdf');
if (preg_match_all('/\[\(([^\)]{1,120})\)\]/', $pdf, $m)) {
    foreach ($m[1] as $s) {
        echo $s . PHP_EOL;
    }
} else {
    echo "no Tj matches\n";
}
