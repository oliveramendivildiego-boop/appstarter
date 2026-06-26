<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
use Dompdf\Dompdf;
$dompdf = new Dompdf();
$log = [];
$dompdf->setCallbacks([[
    'event' => 'end_page_render',
    'f' => function ($frame, $canvas, $fm) use (&$log): void {
        $log[] = 'pn=' . $canvas->get_page_number() . ' pc=' . $canvas->get_page_count();
    },
]]);
$dompdf->loadHtml('<html><body><p>1</p><p style="page-break-before:always">2</p><p style="page-break-before:always">3</p></body></html>');
$dompdf->render();
echo implode(PHP_EOL, $log) . PHP_EOL;
