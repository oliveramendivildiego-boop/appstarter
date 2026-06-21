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
function countFrames($f): int {
    $n = 1;
    for ($c = $f->get_first_child(); $c; $c = $c->get_next_sibling()) {
        $n += countFrames($c);
    }
    return $n;
}
echo 'recursive frames=' . countFrames($root) . ' pages=' . $d->getCanvas()->get_page_count() . "\n";
$child = $root->get_first_child();
echo 'root node=' . ($root->get_node()->nodeName ?? '?') . ' firstChild=' . ($child ? $child->get_node()->nodeName : 'null') . "\n";
