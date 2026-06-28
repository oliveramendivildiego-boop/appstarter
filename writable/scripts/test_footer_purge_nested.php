<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$nested = <<<'HTML'
<body class="pdf-engine-mpdf">
<div class="pdf-main-stack">content</div>
<div class="mpdf-ft-root pdf-ft-block footer-grid">
<div class="mpdf-ft-top-border"></div>
<table class="mpdf-ft-table"><tr><td class="mpdf-ft-cell">Direccion: X</td></tr></table>
</div>
</body>
HTML;

[$body, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($nested);
$rootDivs = preg_match_all('/<div\b[^>]*\bclass="[^"]*\bmpdf-ft-root\b/', $body, $m);

echo 'nested without markers: footerInner=' . ($fi === null ? 'null' : 'set') . PHP_EOL;
echo 'body root divs: ' . ($rootDivs ?: 0) . PHP_EOL;
echo 'body has Direccion: ' . (str_contains($body, 'Direccion') ? 'YES BAD' : 'no') . PHP_EOL;

$marked = str_replace(
    '<div class="mpdf-ft-root',
    '<!-- report-pdf-footer:start --><div class="mpdf-ft-root',
    $nested,
);
$marked = str_replace('</div>' . "\n" . '</body>', '<!-- report-pdf-footer:end --></body>', $marked);
[$body2, $fi2] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($marked);
echo 'marked extract: inner len=' . strlen((string) $fi2) . ' body roots=' . preg_match_all('/mpdf-ft-root/', $body2, $m2) . PHP_EOL;

echo $rootDivs === 0 && $fi === null && strlen((string) $fi2) > 0 ? "OK\n" : "FAIL\n";
