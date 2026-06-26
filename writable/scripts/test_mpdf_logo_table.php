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
$srcAttr = str_replace('"', '&quot;', $src);

$html = <<<HTML
<!DOCTYPE html><html><head>
<link rel="stylesheet" href="file:///ignored">
<style>
.pdf-section-table td.pdf-cell[valign="middle"]:not(.pdf-cell--has-explicit-height),
.pdf-section-table td.pdf-cell[valign="bottom"]:not(.pdf-cell--has-explicit-height) { height: 1px; }
body.pdf-engine-mpdf .pdf-hg-block .header-piece-logo img { max-width: 100% !important; height: auto !important; }
</style>
</head>
<body class="pdf-engine-mpdf pdf-hg-block header header-grid">
<table class="pdf-section-table header-grid-table" width="100%" data-pdf-cols="5" style="width:100%;table-layout:fixed;border-collapse:collapse;">
<tr>
<td class="pdf-cell pdf-cell--left pdf-cell--h-left pdf-cell--v-top" colspan="2" align="left" valign="top" style="width:40%;padding:0 6px;">
<div class="header-piece header-piece-logo">
<img src="{$srcAttr}" alt="Logo" style="max-height:70px;max-width:240px;width:auto;height:auto;display:block;box-sizing:border-box;margin-left:0;margin-right:auto;">
</div>
</td>
<td class="pdf-cell pdf-cell--center pdf-cell--h-center pdf-cell--v-middle pdf-cell--has-explicit-height" colspan="3" align="center" valign="middle" height="70" style="width:60%;height:70px;padding:0 6px;">
<div class="header-piece header-piece-company"><h1 style="margin:0;">Laboratorio Quantum S.R.L.</h1></div>
</td>
</tr>
</table>
</body></html>
HTML;

$adapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null));
$pdf = (new \App\Libraries\PdfService())->generate($adapted, 'test.pdf', null);
$out = WRITEPATH . 'cache/logo_table_test.pdf';
file_put_contents($out, $pdf);
echo 'saved ' . $out . ' bytes=' . strlen($pdf) . PHP_EOL;
