<?php
declare(strict_types=1);

$html = file_get_contents(dirname(__DIR__) . '/debug/check_262_pdf_render.html');
preg_match_all('/<div class="report-signature-tail-bundle"/', $html, $opens);
echo 'bundle opens=' . count($opens[0]) . PHP_EOL;

$dom = new DOMDocument();
@$dom->loadHTML($html);
$x = new DOMXPath($dom);
$bundles = $x->query("//div[contains(@class,'report-signature-tail-bundle')]");
foreach ($bundles as $b) {
    if (! str_contains($b->textContent, 'MICROSCOPICO')) {
        continue;
    }
    $firmaInside = $x->query(".//div[contains(@class,'report-lab-firma-grupo-inline')]", $b)->length;
    echo 'ORINA bundle firma inside=' . $firmaInside . PHP_EOL;
    $next = $b->nextSibling;
    while ($next && ! ($next instanceof DOMElement)) {
        $next = $next->nextSibling;
    }
    if ($next instanceof DOMElement && str_contains($next->getAttribute('class'), 'report-lab-firma')) {
        echo "firma is sibling AFTER bundle (outside)\n";
    }
}
