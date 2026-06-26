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

function render(string $body, string $footer, array $metrics): string
{
    $fr = (float) $metrics['footer_reserve_mm'];
    $mb = (float) $metrics['bottom'] + $fr;
    $mpdf = new Mpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => WRITEPATH . 'cache/mpdf',
        'margin_left' => $metrics['left'], 'margin_right' => $metrics['right'],
        'margin_top' => $metrics['top'], 'margin_bottom' => $mb,
        'margin_footer' => $fr, 'default_font' => 'dejavusans', 'use_kwt' => true,
    ]);
    $mpdf->SetHTMLFooter($footer);
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
$h = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$h = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($h);
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$bodyRaw, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
[$bodyRaw, ] = \App\Libraries\Pdf\MpdfPaginationHtmlInjector::extractHeaderHtml($bodyRaw);
$body = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt(
    \App\Libraries\Pdf\MpdfFooterStyles::injectDocumentFooterCss($bodyRaw, $layoutSnap),
    null,
);
$footer = \App\Libraries\Pdf\HtmlMpdfAdapter::adaptFooterForMpdf(
    \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter($fi, $layoutSnap),
);
$metrics = \App\Libraries\Pdf\MpdfFooterStyles::resolveLayoutMetrics($layoutSnap);

// binary search by length
$lo = 1000;
$hi = strlen($body);
$firstFail = $hi;
while ($lo <= $hi) {
    $mid = (int) (($lo + $hi) / 2);
    $chunk = substr($body, 0, $mid) . '</body></html>';
    $ok = hasFooter(render($chunk, $footer, $metrics));
    echo "len=$mid => " . ($ok ? 'OK' : 'FAIL') . PHP_EOL;
    if ($ok) {
        $lo = $mid + 1;
    } else {
        $firstFail = $mid;
        $hi = $mid - 1;
    }
}

echo "first fail around byte: $firstFail\n";
$start = max(0, $firstFail - 500);
$snippet = substr($body, $start, 1200);
file_put_contents(WRITEPATH . 'debug/footer_break_snippet.html', $snippet);
echo "snippet saved\n";

// patterns near break
foreach (['pagebreak', 'formfeed', 'htmlpagefooter', 'sethtmlpagefooter', 'position:fixed', 'overflow:hidden', 'height:11in', 'height:100%'] as $pat) {
    $pos = stripos($body, $pat, max(0, $firstFail - 5000));
    echo "$pat near break: " . ($pos !== false ? $pos : 'none') . PHP_EOL;
}
