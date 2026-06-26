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

function pdfHasFooter(string $bin): bool
{
    $tmp = WRITEPATH . 'cache/_ft_probe.pdf';
    file_put_contents($tmp, $bin);
    $cmd = 'python ' . escapeshellarg(dirname(__DIR__) . '/scripts/extract_pdf_text.py') . ' 2>nul';
    // inline check
    $py = <<<'PY'
import sys
from pypdf import PdfReader
r = PdfReader(sys.argv[1])
t = "\n".join((p.extract_text() or "") for p in r.pages)
sys.stdout.write("1" if "Direccion" in t or "68724938" in t else "0")
PY;
    $pyFile = WRITEPATH . 'cache/_has_footer.py';
    file_put_contents($pyFile, $py);
    $out = shell_exec('python ' . escapeshellarg($pyFile) . ' ' . escapeshellarg($tmp));

    return trim((string) $out) === '1';
}

function render(string $body, ?string $footer, ?string $header, array $metrics, ?array $watermark = null): string
{
    $footerReserve = max(0.0, (float) ($metrics['footer_reserve_mm'] ?? 0));
    $pageBottomGap = max(0.0, (float) ($metrics['bottom'] ?? 15));
    $marginBottom  = $footerReserve > 0.0 ? ($pageBottomGap + $footerReserve) : $pageBottomGap;
    $mpdf = new Mpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf',
        'margin_left' => (float) $metrics['left'], 'margin_right' => (float) $metrics['right'],
        'margin_top' => (float) $metrics['top'], 'margin_bottom' => $marginBottom,
        'margin_footer' => $footerReserve > 0 ? $footerReserve : 8,
        'default_font' => 'dejavusans', 'use_kwt' => true,
    ]);
    if ($header) {
        $mpdf->SetHTMLHeader($header);
    }
    if ($footer) {
        $mpdf->SetHTMLFooter($footer);
    }
    if ($watermark) {
        $path = $watermark['path'] ?? null;
        if ($path && is_file($path)) {
            $mpdf->SetWatermarkImage($path, 0.12, [45, 0], [50, 50]);
            $mpdf->showWatermarkImage = true;
        }
    }
    $mpdf->WriteHTML($body);

    return $mpdf->Output('', Destination::STRING_RETURN);
}

$id = 308;
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);

$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$watermark  = \App\Libraries\Pdf\HtmlMpdfAdapter::extractWatermarkData($html);
$orderSheet = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::extractSlot($html);
$raw = $html;
$raw = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($raw);
$raw = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($raw);
$raw = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($raw);
[$bodyRaw, $footerInner] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($raw);
[$bodyRaw, $headerHtml] = \App\Libraries\Pdf\MpdfPaginationHtmlInjector::extractHeaderHtml($bodyRaw);
$body = \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($bodyRaw, $layoutSnap);
$body = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, null);
$footer = $footerInner ? \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter($footerInner, $layoutSnap) : '';
if ($footer && is_array($orderSheet)) {
    $footer = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::prependOrderSheetRowToFooter(
        $footer, $orderSheet, \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::countFooterColumns($footer),
    );
}
if ($footer) {
    $footer = \App\Libraries\Pdf\HtmlMpdfAdapter::adaptFooterForMpdf($footer);
}
$metrics = \App\Libraries\Pdf\MpdfFooterStyles::resolveLayoutMetrics($layoutSnap);

$simpleBody = '<html><head></head><body><p>Simple</p></body></html>';
echo 'A simple body + real footer: ' . (pdfHasFooter(render($simpleBody, $footer, null, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
echo 'B real body + real footer: ' . (pdfHasFooter(render($body, $footer, null, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
echo 'C real body + footer + header: ' . (pdfHasFooter(render($body, $footer, $headerHtml, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
echo 'D real body + footer + header + wm: ' . (pdfHasFooter(render($body, $footer, $headerHtml, $metrics, $watermark)) ? 'YES' : 'NO') . PHP_EOL;
echo 'header len=' . strlen($headerHtml) . ' body len=' . strlen($body) . PHP_EOL;

// E: footer NOT extracted - footer still in body
$bodyWithFooter = \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($bodyRaw, $layoutSnap);
$bodyWithFooter = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($bodyWithFooter, null);
echo 'E footer left in body (no SetHTMLFooter): ' . (pdfHasFooter(render($bodyWithFooter, null, null, $metrics)) ? 'YES' : 'NO') . PHP_EOL;

// F: half body
$half = substr($body, 0, (int) (strlen($body) * 0.5));
echo 'F half body + footer: ' . (pdfHasFooter(render($half, $footer, $headerHtml, $metrics)) ? 'YES' : 'NO') . PHP_EOL;
