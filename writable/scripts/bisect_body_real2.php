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
$close = '</body></html>';

$bodyOpen = stripos($body, '<body');
$main = stripos($body, '<div class="pdf-main-stack">', $bodyOpen ?: 0);

echo 'head only: ' . (hasFooter(render(substr($body, 0, $bodyOpen) . '<body></body></html>', $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
echo 'through body open: ' . (hasFooter(render(substr($body, 0, strpos($body, '>', $bodyOpen) + 1) . $close, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;

if ($main !== false) {
    for ($extra = 0; $extra <= 50000; $extra += 2500) {
        $chunk = substr($body, 0, $main + $extra) . $close;
        $ok = hasFooter(render($chunk, $footer, $metrics));
        echo "main+{$extra}: " . ($ok ? 'OK' : 'FAIL') . PHP_EOL;
        if (! $ok && $extra > 0) {
            break;
        }
    }
}

// head without footer CSS block
$noFtCss = preg_replace('/<style>\s*\/\*\s*mPDF footer \(plantilla\)\s*\*\/[\s\S]*?<\/style>/', '', $body, 1);
echo 'full body no footer css in head: ' . (hasFooter(render($noFtCss, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;

// head without mpdf compat inject styles only
$noCompat = preg_replace('/<style>\s*\/\*\s*mPDF: paridad[\s\S]*?<\/style>/', '', $body, 1);
echo 'full body no compat css: ' . (hasFooter(render($noCompat, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
