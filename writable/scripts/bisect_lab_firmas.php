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

$head = '';
if (preg_match('/^[\s\S]*<\/head>/i', $body, $m)) {
    $head = $m[0];
}
$posLf = stripos($body, 'lab-firmas-pdf-block');
$posEnd = stripos($body, 'lab-firmas-pdf-block-global');
// find closing div for lab-firmas - count nesting
$start = $posLf;
$depth = 0;
$len = strlen($body);
$end = $start;
for ($i = $start; $i < $len; $i++) {
    if (str_starts_with(strtolower(substr($body, $i, 4)), '<div')) {
        $depth++;
    }
    if (str_starts_with(strtolower(substr($body, $i, 6)), '</div>')) {
        $depth--;
        if ($depth === 0) {
            $end = $i + 6;
            break;
        }
    }
}
$lfBlock = substr($body, $start, $end - $start);
file_put_contents(WRITEPATH . 'debug/lab_firmas_block_308.html', $lfBlock);
echo 'lab-firmas block len: ' . strlen($lfBlock) . PHP_EOL;

$wrap = static fn (string $chunk): string => $head . '<body class="pdf-engine-mpdf">' . $chunk . '</body></html>';

echo 'lab-firmas only: ' . (hasFooter(render($wrap($lfBlock), $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;

$before = substr($body, 0, $posLf);
echo 'before only: ' . (hasFooter(render($before . '</body></html>', $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;

$combined = $before . $lfBlock . '</body></html>';
echo 'before + lab-firmas: ' . (hasFooter(render($combined, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;

$after = substr($body, $end);
echo 'after lab-firmas len: ' . strlen($after) . PHP_EOL;
echo 'before + lf + after: ' . (hasFooter(render($before . $lfBlock . $after . '</body></html>', $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;

// strip header-piece-pagination from lab-firmas
$lfNoPag = preg_replace('/<div class="header-piece header-piece-pagination[\s\S]*?<\/div>/i', '', $lfBlock) ?? $lfBlock;
echo 'lab-firmas sans pagination div: ' . (hasFooter(render($wrap($lfNoPag), $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
echo 'before+lfNoPag: ' . (hasFooter(render($before . $lfNoPag . '</body></html>', $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
