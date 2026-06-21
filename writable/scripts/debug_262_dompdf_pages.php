<?php
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$html = file_get_contents(dirname(__DIR__) . '/debug/check_262_pdf_render.html');
$d = new Dompdf\Dompdf(['isRemoteEnabled' => true]);
$d->setPaper('letter', 'portrait');
$d->loadHtml($html, 'UTF-8');
$d->render();
$root = $d->getTree()->get_root();
$n = 0;
$walk = function ($f) use (&$walk, &$n) {
    if (!$f) return;
    $node = $f->get_node();
    if ($node instanceof DOMText && str_contains($node->textContent, 'MICROSCOPICO')) $n++;
    for ($c = $f->get_first_child(); $c; $c = $c->get_next_sibling()) $walk($c);
};
$walk($root);
echo "micro frames={$n} total recursive=";
function cf($f){ $n=1; for($c=$f->get_first_child();$c;$c=$c->get_next_sibling()) $n+=cf($c); return $n;}
echo cf($root)."\n";
