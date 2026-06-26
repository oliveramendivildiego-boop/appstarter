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

$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$d = $rs->prepareReportData(305);
helper('qr');
$sourceHtml = $rs->renderReportPdfHtml($d, 'http://x', qr_base64('http://x', 120));
$pageSize = \App\Services\ReportPdfLayoutService::resolveGlobalPageSizeMm($rs->getLabConfig());
$opts = \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize($pageSize);
$adapted = \App\Libraries\Pdf\HtmlChromiumAdapter::adapt($sourceHtml, $opts);
$renderer = new \App\Libraries\Pdf\ChromiumPdfRenderer();
$ref = new ReflectionClass($renderer);
$m = $ref->getMethod('renderAdaptedHtmlToPdf');
$m->setAccessible(true);
$raw = $m->invoke($renderer, $adapted, $opts);
file_put_contents(WRITEPATH . 'cache/chromium_raw_305.pdf', $raw);

$slots = \App\Libraries\Pdf\ChromiumPdfPaginationStamper::extractFooterPaginationSlots($sourceHtml);
$pagOnly = \App\Libraries\Pdf\ChromiumPdfPaginationPhpStamper::stampBinary($raw, $slots, WRITEPATH . 'cache/chromium_pdf', null);
file_put_contents(WRITEPATH . 'cache/pag_only_305.pdf', $pagOnly);
echo 'raw: ' . strlen($raw) . ' pag: ' . strlen($pagOnly ?? '') . PHP_EOL;
