<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$html = file_get_contents(WRITEPATH . 'debug/adapted_body_308.html');
preg_match('/<head>(.*?)<\/head>/is', $html, $headMatch);
$head = $headMatch[1] ?? '';
$start = strpos($html, '<div class="pdf-rs-block">');
$end = strpos($html, '<div class="report-grupo-inter-page-break', $start);
$block = substr($html, $start, $end - $start);
$titlePos = strpos($block, 'HEMOGRAMA + PLAQUETAS');
$afterTitle = strpos($block, '</div>', $titlePos) + 6;

function renderBlock(string $head, string $body, bool $readapt): bool {
    $doc = '<!DOCTYPE html><html><head>' . $head . '</head><body class="pdf-layout-engine pdf-dompdf-download pdf-engine-mpdf">' . $body . '</body></html>';
    if ($readapt) {
        $doc = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($doc, new \App\Libraries\Pdf\PdfOptions());
    }
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf']);
    $mpdf->WriteHTML($doc);
    $out = WRITEPATH . 'cache/bisect_tail.pdf';
    $mpdf->Output($out, \Mpdf\Output\Destination::FILE);
    $text = (new \Mpdf\Mpdf(['tempDir' => WRITEPATH . 'cache/mpdf']))::class; // noop
    unset($text);
    $pdfText = shell_exec('python -c "import fitz; print(\'HEMOGRAM\' in fitz.open(r\'' . str_replace('\\', '/', $out) . '\')[0].get_text())"');
    return trim((string) $pdfText) === 'True';
}

$lo = 0;
$hi = strlen($block) - $afterTitle;
while ($lo < $hi) {
    $mid = intdiv($lo + $hi, 2);
    $slice = substr($block, 0, $afterTitle + $mid);
    if (! str_ends_with(trim($slice), '</div>')) {
        $slice .= str_repeat('</div>', 5);
    }
    $visible = renderBlock($head, $slice, true);
    echo "tail={$mid} visible=" . ($visible ? 'yes' : 'no') . PHP_EOL;
    if ($visible) {
        $lo = $mid + 1;
    } else {
        $hi = $mid;
    }
}

echo 'threshold tail chars ~' . $lo . PHP_EOL;

// no re-adapt
$visibleNoReadapt = renderBlock($head, $block, false);
echo 'full block re-adapt: ' . (renderBlock($head, $block, true) ? 'yes' : 'no') . PHP_EOL;
echo 'full block no re-adapt: ' . ($visibleNoReadapt ? 'yes' : 'no') . PHP_EOL;
