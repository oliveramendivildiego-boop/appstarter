<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$html = file_get_contents(dirname(__DIR__) . '/debug/check_262_pdf_render.html');
$o = new Dompdf\Options();
$o->set('isRemoteEnabled', true);
$d = new Dompdf\Dompdf($o);
$d->setPaper('letter', 'portrait');
$d->loadHtml($html, 'UTF-8');
$d->render();

$pageH = $d->getCanvas()->get_height();
echo "pages=" . $d->getCanvas()->get_page_count() . " pageH={$pageH}\n";

$root = $d->getTree()->get_root();
$n = 0;
foreach (new Dompdf\Frame\FrameTreeIterator($root) as $frame) {
    $node = $frame->get_node();
    if (! $node instanceof DOMElement || $node->nodeName !== 'div') {
        continue;
    }
    $cls = html_entity_decode($node->getAttribute('class'));
    if ($cls === '' || (
        ! str_contains($cls, 'signature-tail')
        && ! str_contains($cls, 'firma-grupo-inline')
        && ! str_contains($cls, 'segment-title')
    )) {
        continue;
    }
    if (str_contains($cls, 'segment-title') && ! str_contains($node->textContent, 'MICROSCOPICO')) {
        continue;
    }
    $dec = $frame->get_decorator();
    if ($dec === null) {
        continue;
    }
    $box = $dec->get_border_box();
    $y = (float) ($box['y'] ?? 0);
    $h = (float) ($box['h'] ?? 0);
    if ($h <= 0) {
        continue;
    }
    $page = (int) floor($y / $pageH) + 1;
    $yOnPage = $y - ($page - 1) * $pageH;
    echo sprintf("[%s] page=%d y=%.0f h=%.0f\n", substr($cls, 0, 55), $page, $yOnPage, $h);
    if (++$n >= 15) {
        break;
    }
}
echo "matched={$n}\n";

$totalDiv = 0;
$withClass = 0;
foreach (new Dompdf\Frame\FrameTreeIterator($root) as $frame) {
    $node = $frame->get_node();
    if (! $node instanceof DOMElement || $node->nodeName !== 'div') {
        continue;
    }
    $totalDiv++;
    if ($node->hasAttribute('class')) {
        $withClass++;
    }
}
echo "totalDivFrames={$totalDiv} withClass={$withClass}\n";

// ORINA tail group (last one with MICROSCOPICO)
$dom = $d->getDom();
$xpath = new DOMXPath($dom);
$nodes = $xpath->query("//div[contains(@class,'report-signature-tail-group')]");
echo 'xpath tail groups=' . $nodes->length . "\n";
for ($i = 0; $i < $nodes->length; $i++) {
    $node = $nodes->item($i);
    if (! str_contains($node->textContent, 'MICROSCOPICO')) {
        continue;
    }
    $frame = $d->getTree()->get_node($node);
    if ($frame === null) {
        echo "no frame via get_node\n";
        continue;
    }
    $dec = $frame->get_decorator() ?? $frame;
    $box = $dec->get_border_box();
    $y = (float) ($box['y'] ?? 0);
    $h = (float) ($box['h'] ?? 0);
    $page = (int) floor($y / $pageH) + 1;
    $yOnPage = $y - ($page - 1) * $pageH;
    echo sprintf("ORINA tail page=%d y=%.0f h=%.0f end=%.0f\n", $page, $yOnPage, $h, $yOnPage + $h);
    // firma inside
    $firma = $xpath->query(".//div[contains(@class,'report-lab-firma-grupo-inline')]", $node);
    if ($firma->length > 0) {
        echo "firma INSIDE tail group\n";
    } else {
        echo "firma NOT inside tail group (DOM)\n";
    }
    break;
}

// ORINA firma (area index 2)
$areas = $xpath->query("//div[@data-layout-area-index='2']//div[contains(@class,'report-lab-firma-grupo-inline')]");
echo 'ORINA firmas=' . $areas->length . "\n";
if ($areas->length > 0) {
    $node = $areas->item(0);
    $frame = $d->getTree()->get_node($node);
    if ($frame !== null) {
        $dec = $frame->get_decorator() ?? $frame;
        $box = $dec->get_border_box();
        $y = (float) ($box['y'] ?? 0);
        $h = (float) ($box['h'] ?? 0);
        $page = (int) floor($y / $pageH) + 1;
        $yOnPage = $y - ($page - 1) * $pageH;
        echo sprintf("ORINA firma page=%d y=%.0f h=%.0f end=%.0f\n", $page, $yOnPage, $h, $yOnPage + $h);
    }
}
