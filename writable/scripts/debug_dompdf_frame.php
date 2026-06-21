<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$html = file_get_contents(dirname(__DIR__) . '/debug/diagnose_253_pdf.html');
$o = new Dompdf\Options();
$o->set('isRemoteEnabled', true);
$d = new Dompdf\Dompdf($o);
$d->setPaper('letter', 'portrait');
$d->loadHtml($html, 'UTF-8');
$dom = $d->getDom();
$pre = $dom->getElementsByTagName('div');
echo "divs with layout id pre-render: ";
foreach ($pre as $el) {
    if ($el instanceof DOMElement && $el->hasAttribute('data-layout-block-id')) {
        echo $el->getAttribute('data-layout-block-id') . ' ';
    }
}
echo "\n";
$d->render();
$root = $d->getTree()->get_root();
$pageH = $d->getCanvas()->get_height();
$n = 0;
foreach (new Dompdf\Frame\FrameTreeIterator($root) as $frame) {
    $node = $frame->get_node();
    if (! $node instanceof DOMElement || ! $node->hasAttribute('data-layout-block-id')) {
        continue;
    }
    $id = $node->getAttribute('data-layout-block-id');
    $dec = $frame->get_decorator() ?? $frame;
    $box = $dec->get_border_box();
    $y = (float) ($box['y'] ?? 0);
    $h = (float) ($box['h'] ?? 0);
    echo sprintf("%s y=%.1f h=%.1f margin_h=%.1f\n", $id, $y, $h, $dec->get_margin_height());
    if (++$n >= 5) {
        break;
    }
}
echo "pageH={$pageH} pages=" . $d->getCanvas()->get_page_count() . "\n";
