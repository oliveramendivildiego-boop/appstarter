<?php
declare(strict_types=1);

$htmlFile = dirname(__DIR__) . '/debug/report_262.html';
if (! is_file($htmlFile)) {
    fwrite(STDERR, "Run debug_pdf_html.php first\n");
    exit(1);
}
$html = file_get_contents($htmlFile);

preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $html, $styles);
$cssBytes = 0;
foreach ($styles[1] as $i => $css) {
    $len = strlen($css);
    $cssBytes += $len;
    echo "style[$i]: " . round($len / 1024, 1) . " KB\n";
}

$bodyPos = stripos($html, '<body');
$headLen = $bodyPos !== false ? $bodyPos : 0;
echo 'head approx: ' . round($headLen / 1024, 1) . " KB\n";
echo 'total: ' . round(strlen($html) / 1024, 1) . " KB\n";
echo 'tables: ' . substr_count($html, '<table') . "\n";
echo 'tr: ' . substr_count($html, '<tr') . "\n";
echo 'force-break: ' . substr_count($html, 'force-break') . "\n";
echo 'layout markers: ' . substr_count($html, 'data-layout-') . "\n";
