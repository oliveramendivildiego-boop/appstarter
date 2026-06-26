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
    if (! is_file($py)) {
        file_put_contents($py, <<<'PY'
import sys
from pypdf import PdfReader
r = PdfReader(sys.argv[1])
t = "\n".join((p.extract_text() or "") for p in r.pages)
sys.stdout.write("1" if "Direccion" in t or "68724938" in t else "0")
PY);
    }
    return trim((string) shell_exec('python ' . escapeshellarg($py) . ' ' . escapeshellarg($tmp))) === '1';
}

function render(string $body, string $footer, array $metrics, bool $useKwt = true): string
{
    $fr = (float) $metrics['footer_reserve_mm'];
    $mb = (float) $metrics['bottom'] + $fr;
    $mpdf = new Mpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf',
        'margin_left' => $metrics['left'], 'margin_right' => $metrics['right'],
        'margin_top' => $metrics['top'], 'margin_bottom' => $mb,
        'margin_footer' => $fr, 'default_font' => 'dejavusans', 'use_kwt' => $useKwt,
    ]);
    $mpdf->SetHTMLFooter($footer);
    $mpdf->WriteHTML($body);
    return $mpdf->Output('', Destination::STRING_RETURN);
}

$body = file_get_contents(WRITEPATH . 'debug/adapted_body_308.html');
$footer = file_get_contents(WRITEPATH . 'debug/ft_test_footer_final.html');
$metrics = ['left'=>25,'right'=>10,'top'=>5,'bottom'=>2,'footer_reserve_mm'=>22.0];

echo 'full use_kwt=true: ' . (hasFooter(render($body, $footer, $metrics, true)) ? 'YES' : 'NO') . PHP_EOL;
echo 'full use_kwt=false: ' . (hasFooter(render($body, $footer, $metrics, false)) ? 'YES' : 'NO') . PHP_EOL;

$markers = [
    'lab-firmas' => 'lab-firmas-pdf-block',
    'results-end' => '</table>',
    'main-stack-end' => '</div><!-- pdf-main-stack? no',
];
$posLf = stripos($body, 'lab-firmas-pdf-block');
$posMain = stripos($body, 'pdf-main-stack');
$headEnd = stripos($body, '</head>') + 7;
$bodyOpen = stripos($body, '<body');
$bodyTagEnd = strpos($body, '>', $bodyOpen) + 1;

if ($posLf !== false) {
    $beforeLf = substr($body, 0, $posLf) . '</body></html>';
    echo 'before lab-firmas: ' . (hasFooter(render($beforeLf, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
    $fromLf = substr($body, 0, $bodyTagEnd) . substr($body, $posLf);
    echo 'only from lab-firmas: ' . (hasFooter(render($fromLf, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
}

// remove lab-firmas block
if ($posLf !== false) {
    $end = stripos($body, 'lab-firmas-pdf-block-global');
    $close = stripos($body, '</div>', $posLf);
    // naive: remove 8000 chars from lab-firmas
    $without = substr($body, 0, $posLf) . '<!-- removed lab firmas -->' . substr($body, $posLf + 12000);
    echo 'body minus ~lab-firmas chunk: ' . (hasFooter(render($without, $footer, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
}
