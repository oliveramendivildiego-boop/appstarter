<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\FontMetrics;

$html = '<!DOCTYPE html><html><body><p>One</p><p style="page-break-before:always">Two</p></body></html>';
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->render();
$canvas = $dompdf->getCanvas();
$fm = $dompdf->getFontMetrics();
$font = $fm->getFont('DejaVu Sans', 'normal');
$pageW = $canvas->get_width();
$pageH = $canvas->get_height();
$mmToPt = 72/25.4;
$mb = 5*$mmToPt;
$mr = 10*$mmToPt;
$ml = 25*$mmToPt;
$fontSize = 10;
$text = 'Página 1 de 2';
$tw = $canvas->get_text_width($text, $font, $fontSize);
$x = $pageW - $mr - $tw;
$footerReservePt = 22*$mmToPt;
$y = $pageH - $mb - ($footerReservePt * 0.42) - ($fontSize * 0.15);
echo "pageW=$pageW pageH=$pageH x=$x y=$y tw=$tw\n";
try {
    $canvas->text($x, $y, $text, $font, $fontSize, [0.2,0.2,0.2]);
    echo "text ok after render\n";
} catch (Throwable $e) {
    echo 'ERR: '.$e->getMessage()."\n";
}

// Now try during end_document on fresh render
$dompdf2 = new Dompdf();
$dompdf2->setCallbacks([['event'=>'end_document','f'=>function($pn,$pc,$c,$f) use ($x,$y,$font,$fontSize) {
    $t = "Página {$pn} de {$pc}";
    try {
        $c->text(72, 40, $t, $font, $fontSize, [0,0,0]);
        echo "cb page $pn text ok\n";
    } catch (Throwable $e) { echo 'cb ERR: '.$e->getMessage()."\n"; }
}]]);
$dompdf2->loadHtml($html);
$dompdf2->render();
$pdf = $dompdf2->output(['compress'=>0]);
file_put_contents(dirname(__DIR__).'/debug/manual_text_test.pdf', $pdf);
if (preg_match_all('/\[\(([^\)]{1,40})\)\]/', $pdf, $m)) {
    foreach ($m[1] as $s) if (str_contains($s,'de')) echo "found: $s\n";
}
