<?php
$pdf = (string) file_get_contents(dirname(__DIR__) . '/debug/order_sheet_callback_probe.pdf');
if (preg_match_all('/\[\(([^\)]{1,120})\)\]/', $pdf, $m)) {
    foreach ($m[1] as $s) {
        if (stripos($s, 'Paciente') !== false || stripos($s, 'JHONATAN') !== false || stripos($s, 'Orden') !== false || stripos($s, 'gina') !== false || stripos($s, 'TEST') !== false) {
            echo $s . PHP_EOL;
        }
    }
}
