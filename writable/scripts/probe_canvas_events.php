<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

foreach (['end_document', 'end_page_render', 'begin_page_render'] as $event) {
    $opts = new Options();
    $opts->set('defaultFont', 'DejaVu Sans');
    $dompdf = new Dompdf($opts);
    if ($event === 'end_document') {
        $dompdf->setCallbacks([[
            'event' => $event,
            'f'     => static function (int $pn, int $pc, $canvas, $fm) use ($event): void {
                $font = $fm->getFont('DejaVu Sans', 'normal');
                $canvas->text(72, 72, "{$event} {$pn}/{$pc}", $font, 14, [0, 0, 0]);
            },
        ]]);
    } else {
        $dompdf->setCallbacks([[
            'event' => $event,
            'f'     => static function ($frame, $canvas, $fm) use ($event): void {
                $pn = (int) $canvas->get_page_number();
                $font = $fm->getFont('DejaVu Sans', 'normal');
                $canvas->text(72, 72, "{$event} {$pn}", $font, 14, [0, 0, 0]);
            },
        ]]);
    }
    $html = '<html><body><p>Page1</p><p style="page-break-before:always">Page2</p></body></html>';
    $dompdf->loadHtml($html);
    $dompdf->render();
    $out = dirname(__DIR__) . "/cache/probe_{$event}.pdf";
    file_put_contents($out, $dompdf->output(['compress' => 0]));
    echo $event . ' -> ' . $out . ' (' . filesize($out) . ' bytes)' . PHP_EOL;
}
