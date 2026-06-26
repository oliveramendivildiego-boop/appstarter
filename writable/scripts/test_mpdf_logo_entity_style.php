<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

helper('registro');
$lab = (new \App\Services\ConfigService())->getAllAsArray();
$rel = trim((string) ($lab['logo'] ?? 'images/logo-john.png'));
$src = report_image_data_uri($rel);

$styleEncoded = 'max-height&#x3A;70px&#x3B;max-width&#x3A;240px&#x3B;width&#x3A;auto&#x3B;height&#x3A;auto&#x3B;display&#x3A;block&#x3B;box-sizing&#x3A;border-box&#x3B;margin-left&#x3A;0&#x3B;margin-right&#x3A;auto&#x3B;';

$html = <<<HTML
<!DOCTYPE html><html><head></head>
<body class="pdf-engine-mpdf pdf-layout-engine">
<div class="pdf-hg-block header header-grid">
<table class="pdf-section-table" width="100%" data-pdf-cols="5" style="table-layout:fixed;border-collapse:collapse;">
<tr>
<td class="pdf-cell&#x20;pdf-cell--left&#x20;pdf-cell--h-left&#x20;pdf-cell--v-top" colspan="2" align="left" valign="top" style="width&#x3A;40&#x25;&#x3B;padding&#x3A;0&#x20;6px&#x3B;">
<div class="header-piece header-piece-logo">
<img src="{$src}" alt="Logo" style="{$styleEncoded}">
</div>
</td>
<td colspan="3"><h1>Company</h1></td>
</tr>
</table>
</div>
</body></html>
HTML;

$adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
if (preg_match('/<img[^>]+alt="Logo"[^>]*>/', $adapted, $m)) {
    $tag = preg_replace('/src="data:image[^"]+"/', 'src="[DATA]"', $m[0]) ?? $m[0];
    echo "adapted: $tag\n";
}
$pdf = (new \App\Libraries\PdfService())->generate($adapted, 'test.pdf', null);
$out = WRITEPATH . 'cache/logo_entity_encoded_test.pdf';
file_put_contents($out, $pdf);
echo "saved $out bytes=" . strlen($pdf) . "\n";
