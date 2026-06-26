<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['PDF_RENDERER'] = 'mpdf';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

function hasFooter(string $bin): bool
{
    $tmp = WRITEPATH . 'cache/_ft_probe.pdf';
    file_put_contents($tmp, $bin);
    $py = WRITEPATH . 'cache/_has_footer.py';
    return trim((string) shell_exec('python ' . escapeshellarg($py) . ' ' . escapeshellarg($tmp))) === '1';
}

function render(string $body, string $footer, array $metrics): string
{
    $fr = (float) $metrics['footer_reserve_mm'];
    $mb = (float) $metrics['bottom'] + $fr;
    $mpdf = new Mpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf',
        'margin_left' => $metrics['left'], 'margin_right' => $metrics['right'],
        'margin_top' => $metrics['top'], 'margin_bottom' => $mb,
        'margin_footer' => $fr, 'default_font' => 'dejavusans',
    ]);
    $mpdf->SetHTMLFooter($footer);
    $mpdf->WriteHTML($body);
    return $mpdf->Output('', Destination::STRING_RETURN);
}

$body = file_get_contents(WRITEPATH . 'debug/adapted_body_308.html');
$footer = file_get_contents(WRITEPATH . 'debug/ft_test_footer_final.html');
$metrics = ['left'=>25,'right'=>10,'top'=>5,'bottom'=>2,'footer_reserve_mm'=>22.0];

function findAfterHead(string $body, string $needle): int|false
{
    $headEnd = stripos($body, '</head>');
    if ($headEnd === false) {
        return false;
    }
    $pos = stripos($body, $needle, $headEnd);
    return $pos === false ? false : $pos;
}

$main = findAfterHead($body, '<div class="pdf-main-stack">');
$lfGlobal = findAfterHead($body, '<div class="lab-firmas-pdf-block lab-firmas-pdf-block-global">');
$lfInline = findAfterHead($body, '<div class="lab-firmas-pdf-block lab-firmas-pdf-block-inline');
$results = findAfterHead($body, '<table class="results');

echo "main=$main lfGlobal=$lfGlobal lfInline=$lfInline results=$results\n";

$head = substr($body, 0, stripos($body, '</head>') + 7);
$close = '</body></html>';

if ($main !== false) {
    $chunk = $head . substr($body, stripos($body, '<body')) . substr($body, $main, 500) . $close;
    // fix: from body tag
    $bodyOpen = stripos($body, '<body');
    $chunk = substr($body, 0, $bodyOpen) . substr($body, $bodyOpen, $main - $bodyOpen + 500) . $close;
    echo 'head+main start 500: ' . (hasFooter(render($chunk, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
}

if ($main !== false && $lfInline !== false) {
    $chunk = substr($body, 0, $lfInline) . $close;
    echo 'up to first inline lab-firma: ' . (hasFooter(render($chunk, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
}

if ($main !== false && $results !== false) {
    $chunk = substr($body, 0, $results) . $close;
    echo 'up to first results table: ' . (hasFooter(render($chunk, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
}

// progressive: add 20k chunks after results
if ($results !== false) {
    $step = 20000;
    for ($end = $results + $step; $end < strlen($body); $end += $step) {
        $chunk = substr($body, 0, $end) . $close;
        $ok = hasFooter(render($chunk, $footer, $metrics));
        echo "len=$end => " . ($ok ? 'OK' : 'FAIL') . PHP_EOL;
        if (! $ok) {
            break;
        }
    }
}
