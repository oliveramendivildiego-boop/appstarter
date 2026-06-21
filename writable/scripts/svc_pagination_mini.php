<?php
declare(strict_types=1);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$config = [
    'prefix' => 'Página ',
    'zone' => 'footer',
    'align' => 'right',
    'fontSize' => 10,
    'fontFamily' => 'DejaVu Sans',
    'color' => '#333333',
    'mt' => 15,
    'mr' => 15,
    'mb' => 15,
    'ml' => 15,
];
$marker = '<!-- pdf-pagination:' . base64_encode(json_encode($config)) . ' -->';

$html = <<<HTML
<!DOCTYPE html><html><body>
{$marker}
<p>Page one.</p>
<p style="page-break-before:always">Page two.</p>
</body></html>
HTML;

$svc = new \App\Libraries\PdfService();
$pdf = $svc->generate($html, 'mini.pdf');
file_put_contents(dirname(__DIR__) . '/debug/svc_pagination_mini.pdf', $pdf);

foreach (['Página 1 de 2', '1 de 2', 'Página 2 de 2'] as $p) {
    echo "Contains '{$p}': " . (str_contains($pdf, $p) ? 'yes' : 'no') . PHP_EOL;
}

if (preg_match_all('/\[\(([^)]{1,80})\)\]\s*TJ/', $pdf, $m)) {
    echo "TJ chunks:\n";
    foreach ($m[1] as $chunk) {
        if (str_contains($chunk, 'de') || str_contains($chunk, 'P')) {
            echo "  - {$chunk}\n";
        }
    }
}
