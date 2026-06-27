<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['PDF_RENDERER'] = 'mpdf';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$variants = [
    'with_colgroup' => <<<'HTML'
<table width="100%" style="table-layout:fixed;border-collapse:collapse;">
<colgroup><col style="width:20%;"/><col style="width:20%;"/><col style="width:20%;"/><col style="width:20%;"/><col style="width:20%;"/></colgroup>
<tr><td colspan="3" width="60%" style="width:60%;background:#eee;">LEFT 60</td><td colspan="2" width="40%" style="width:40%;background:#ccc;">RIGHT 40</td></tr>
</table>
HTML,
    'no_colgroup' => <<<'HTML'
<table width="100%" style="table-layout:fixed;border-collapse:collapse;">
<tr><td colspan="3" width="60%" style="width:60%;background:#eee;">LEFT 60</td><td colspan="2" width="40%" style="width:40%;background:#ccc;">RIGHT 40</td></tr>
</table>
HTML,
    'two_col_table' => <<<'HTML'
<table width="100%" style="table-layout:fixed;border-collapse:collapse;">
<tr><td width="60%" style="width:60%;background:#eee;">LEFT 60</td><td width="40%" style="width:40%;background:#ccc;">RIGHT 40</td></tr>
</table>
HTML,
];

$pdfService = new \App\Libraries\PdfService();
foreach ($variants as $name => $body) {
    $html = '<!DOCTYPE html><html><body style="font-family:DejaVu Sans;font-size:10pt;margin:75px;">' . $body . '</body></html>';
    $html = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html);
    $pdf = $pdfService->generate($html, 'test.pdf', ['width' => 210, 'height' => 297]);
    $path = WRITEPATH . 'cache/colspan_test_' . $name . '.pdf';
    file_put_contents($path, $pdf);
    echo "$name => $path (" . strlen($pdf) . " bytes)\n";
}
