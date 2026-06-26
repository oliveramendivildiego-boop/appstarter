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

$id = 308;
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, $emitido, $layout);

[$body, $footerInner] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($html);
$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$footer = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter((string) $footerInner, $layoutSnap);

file_put_contents(WRITEPATH . 'cache/footer_grid_dump.html', $footer);

if (preg_match('/<table[^>]*mpdf-ft-table[^>]*>[\s\S]*?<\/table>/', $footer, $m)) {
    $table = $m[0];
    preg_match_all('/<td\b([^>]*)>/i', $table, $tds);
    foreach ($tds[1] as $i => $attrs) {
        $align = preg_match('/align="([^"]+)"/i', $attrs, $am) ? $am[1] : '-';
        $colspan = preg_match('/colspan="(\d+)"/i', $attrs, $cm) ? $cm[1] : '1';
        $cls = preg_match('/class="([^"]+)"/i', $attrs, $clm) ? $clm[1] : '';
        $width = preg_match('/width:([^;]+)/i', $attrs, $wm) ? $wm[1] : (preg_match('/style="[^"]*width:([^;!]+)/i', $attrs, $wm) ? $wm[1] : '-');
        echo 'td' . ($i + 1) . ": align={$align} colspan={$colspan} width={$width}\n";
        echo '  class: ' . substr($cls, 0, 80) . "\n";
    }
}

$cols = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::countFooterColumns($footer);
echo "data-pdf-cols count: {$cols}\n";
