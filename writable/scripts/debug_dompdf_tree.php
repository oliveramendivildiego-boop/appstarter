<?php
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$html = file_get_contents(dirname(__DIR__) . '/debug/check_262_pdf_render.html');
$d = new Dompdf\Dompdf();
$d->setPaper('letter');
$d->loadHtml($html);
$d->render();
$root = $d->getTree()->get_root();
echo 'root node: ' . $root->get_node()->nodeName . "\n";
$count = 0;
foreach ($root->get_children() as $child) {
    $count++;
}
echo "root children={$count}\n";
$iter = 0;
foreach (new Dompdf\Frame\FrameTreeIterator($root) as $f) {
    $iter++;
}
echo "iterator frames={$iter}\n";

$dom = $d->getDom();
echo 'dom body children: ' . $dom->getElementsByTagName('body')->item(0)?->childNodes->length . "\n";
echo 'dom tail xpath: ';
$x = new DOMXPath($dom);
echo $x->query("//div[contains(@class,'report-signature-tail-group')]")->length . "\n";
