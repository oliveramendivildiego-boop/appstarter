<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$html = file_get_contents(dirname(__DIR__) . '/debug/diagnose_253_pdf.html');
$o = new Dompdf\Options();
$o->set('isRemoteEnabled', true);
$d = new Dompdf\Dompdf($o);
$d->setPaper('letter', 'portrait');
$d->loadHtml($html, 'UTF-8');
$d->render();
$root = $d->getTree()->get_root();
$total = 0;
$withAttr = 0;
foreach (new Dompdf\Frame\FrameTreeIterator($root) as $frame) {
    $total++;
    $node = $frame->get_node();
    if ($node instanceof DOMElement && $node->hasAttribute('data-layout-block-id')) {
        $withAttr++;
        echo $node->getAttribute('data-layout-block-id') . "\n";
    }
}
echo "frames={$total} withAttr={$withAttr}\n";
