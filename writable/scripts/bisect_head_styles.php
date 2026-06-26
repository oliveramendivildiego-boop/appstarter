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
    return trim((string) shell_exec('python ' . escapeshellarg(WRITEPATH . 'cache/_has_footer.py') . ' ' . escapeshellarg($tmp))) === '1';
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
$close = '<body><p>x</p></body></html>';

preg_match_all('/<style\b[^>]*>[\s\S]*?<\/style>/i', $body, $styles, PREG_OFFSET_CAPTURE);
echo 'style blocks: ' . count($styles[0]) . PHP_EOL;

$headStart = stripos($body, '<head');
$headEnd = stripos($body, '</head>') + 7;
$headPrefix = substr($body, 0, $headStart);
$headSuffix = substr($body, $headEnd);

$accum = '';
$idx = 0;
foreach ($styles[0] as $i => $styleMatch) {
    $style = $styleMatch[0];
    $pos = $styleMatch[1];
    if ($pos < $headStart || $pos > $headEnd) {
        continue;
    }
    $idx++;
    $accum .= $style;
    $test = $headPrefix . '<head>' . $accum . '</head>' . $close;
    $ok = hasFooter(render($test, $footer, $metrics));
    echo "after style block #$idx (" . strlen($style) . "b): " . ($ok ? 'OK' : 'FAIL') . PHP_EOL;
    if (! $ok) {
        file_put_contents(WRITEPATH . 'debug/break_style_block.html', $style);
        echo "saved breaking block\n";
        break;
    }
}

// also test: all styles at once but empty body
$allHead = substr($body, 0, $headEnd) . $close;
echo 'all head styles empty body: ' . (hasFooter(render($allHead, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
