<?php
declare(strict_types=1);
/**
 * Verifica que el deploy PDF esté completo (SSH): php writable/scripts/pdf_deploy_verify.php
 */
$root = dirname(__DIR__, 2);
$requiredFiles = [
    'app/Controllers/registers.php',
    'app/Services/RegisterService.php',
    'app/Services/ReportPdfLayoutService.php',
    'app/Libraries/PdfService.php',
    'app/Libraries/Pdf/HtmlMpdfAdapter.php',
    'app/Libraries/Pdf/MpdfFooterStyles.php',
    'app/Libraries/Pdf/MpdfFooterExtractor.php',
    'app/Libraries/Pdf/MpdfPdfRenderer.php',
    'app/Libraries/Pdf/SafeMpdf.php',
    'app/Libraries/Pdf/MpdfCssVariablesResolver.php',
    'app/Libraries/Pdf/MpdfNamedFooterInjector.php',
    'app/Libraries/Pdf/MpdfOrderSheetFooterInjector.php',
    'public/assets/css/report_pdf.css',
    'vendor/mpdf/mpdf/src/Mpdf.php',
];

$requiredMethods = [
    [\App\Services\ReportPdfLayoutService::class, 'footerGridSectionTableBorderTopCss'],
    [\App\Services\ReportPdfLayoutService::class, 'footerGridSectionTableBorderStyleAttr'],
    [\App\Libraries\Pdf\MpdfFooterStyles::class, 'wrapForSetHtmlFooter'],
    [\App\Libraries\Pdf\MpdfFooterStyles::class, 'injectDocumentFooterCss'],
];

$ok = true;
echo 'Verificación de deploy PDF' . PHP_EOL . str_repeat('-', 40) . PHP_EOL;

foreach ($requiredFiles as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $pass = is_file($path);
    if (! $pass) {
        $ok = false;
    }
    echo ($pass ? '[OK] ' : '[FAIL] ') . $rel . PHP_EOL;
}

define('FCPATH', $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
$_SERVER['CI_ENVIRONMENT'] = $_SERVER['CI_ENVIRONMENT'] ?? 'production';
define('ENVIRONMENT', $_SERVER['CI_ENVIRONMENT']);
CodeIgniter\Boot::bootConsole($paths);

echo PHP_EOL . 'Clases y métodos:' . PHP_EOL;
foreach ($requiredMethods as [$class, $method]) {
    $pass = class_exists($class) && method_exists($class, $method);
    if (! $pass) {
        $ok = false;
    }
    echo ($pass ? '[OK] ' : '[FAIL] ') . $class . '::' . $method . PHP_EOL;
}

$revision = class_exists(\App\Libraries\Pdf\HtmlMpdfAdapter::class)
    ? \App\Libraries\Pdf\HtmlMpdfAdapter::CACHE_REVISION
    : '(no definida)';
echo PHP_EOL . 'CACHE_REVISION: ' . $revision . PHP_EOL;
echo PHP_EOL . ($ok ? 'Deploy PDF: completo' : 'Deploy PDF: INCOMPLETO — suba los archivos [FAIL]') . PHP_EOL;
exit($ok ? 0 : 1);
