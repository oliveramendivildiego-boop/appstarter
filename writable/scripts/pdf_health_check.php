<?php
declare(strict_types=1);
/**
 * Diagnóstico PDF en servidor (SSH): php writable/scripts/pdf_health_check.php [registro_id]
 */
$_SERVER['CI_ENVIRONMENT'] = $_SERVER['CI_ENVIRONMENT'] ?? 'production';
define('ENVIRONMENT', $_SERVER['CI_ENVIRONMENT']);
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 0);
$ok = true;

$check = static function (string $label, bool $pass, string $detail = '') use (&$ok): void {
    if (! $pass) {
        $ok = false;
    }
    echo ($pass ? '[OK] ' : '[FAIL] ') . $label;
    if ($detail !== '') {
        echo ' — ' . $detail;
    }
    echo PHP_EOL;
};

$check('PHP', PHP_VERSION_ID >= 80100, PHP_VERSION);
$check('ext-mbstring', extension_loaded('mbstring'));
$check('ext-gd', extension_loaded('gd'));
$check('class Mpdf\\Mpdf', class_exists(\Mpdf\Mpdf::class));
$check('PdfEngine mpdf', \App\Libraries\Pdf\PdfEngine::isMpdf(), (string) config('Pdf')->renderer);
$check(
    'CACHE_REVISION',
    defined(\App\Libraries\Pdf\HtmlMpdfAdapter::class . '::CACHE_REVISION'),
    \App\Libraries\Pdf\HtmlMpdfAdapter::CACHE_REVISION,
);
$check(
    'footerGridSectionTableBorderTopCss',
    method_exists(\App\Services\ReportPdfLayoutService::class, 'footerGridSectionTableBorderTopCss'),
);
$check(
    'MpdfFooterStyles::wrapForSetHtmlFooter',
    method_exists(\App\Libraries\Pdf\MpdfFooterStyles::class, 'wrapForSetHtmlFooter'),
);

$mpdfTmp = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
if (! is_dir($mpdfTmp)) {
    @mkdir($mpdfTmp, 0755, true);
}
$check('writable mpdf temp', is_dir($mpdfTmp) && is_writable($mpdfTmp), $mpdfTmp);

$previewDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'report_pdf_preview';
if (! is_dir($previewDir)) {
    @mkdir($previewDir, 0755, true);
}
$check('writable pdf preview cache', is_dir($previewDir) && is_writable($previewDir), $previewDir);

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode'    => 'utf-8',
        'format'  => 'Letter',
        'tempDir' => $mpdfTmp,
    ]);
    $mpdf->WriteHTML('<html><body><p>test</p></body></html>');
    $bin = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    $check('mPDF smoke test', $bin !== '' && str_starts_with($bin, '%PDF'), strlen($bin) . ' bytes');
} catch (\Throwable $e) {
    $check('mPDF smoke test', false, $e->getMessage());
}

if ($id > 0) {
    echo PHP_EOL . "Registro {$id}:" . PHP_EOL;
    try {
        $rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
        $data = $rs->prepareReportData($id);
        $check('prepareReportData', is_array($data) && $data !== []);
        if (is_array($data) && $data !== []) {
            helper('qr');
            $layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
            $url = $rs->publicReportViewerUrlForQr($id);
            $emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
            $qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
            $pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout, $id);
            $check('generateReportPdfBinary', $pdf !== '' && str_starts_with($pdf, '%PDF'), strlen($pdf) . ' bytes');
        }
    } catch (\Throwable $e) {
        $check('generateReportPdfBinary', false, get_class($e) . ': ' . $e->getMessage());
        echo '  at ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    }
}

echo PHP_EOL . ($ok ? 'Diagnóstico: todo OK' : 'Diagnóstico: hay fallos — corrija los [FAIL]') . PHP_EOL;
exit($ok ? 0 : 1);
