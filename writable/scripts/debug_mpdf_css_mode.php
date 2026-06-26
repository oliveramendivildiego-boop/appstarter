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

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->renderReportPdfHtml($data, $url, $qr, \App\Services\RegisterService::formatNowForReport(), $layout);
$html = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($html);
$html = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($html, \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(['key' => 'letter']));

function renderPages(string $html, bool $split): int
{
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'Letter',
        'tempDir'       => WRITEPATH . 'cache/mpdf',
        'margin_left'   => 0,
        'margin_right'  => 0,
        'margin_top'    => 0,
        'margin_bottom' => 0,
        'default_font'  => 'dejavusans',
    ]);
    if ($split) {
        $styles = '';
        if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $matches)) {
            $styles = implode("\n", $matches[1]);
            $html   = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html) ?? $html;
        }
        if ($styles !== '') {
            $mpdf->WriteHTML($styles, \Mpdf\HTMLParserMode::HEADER_CSS);
        }
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    } else {
        $mpdf->WriteHTML($html);
    }

    return (int) $mpdf->page;
}

// Test CSS var support
$varTest = '<style>:root{--c:#ff0000;} .x{background:var(--c,#00ff00);width:100px;height:50px;}</style><div class="x">test</div>';
$mpdf = new \Mpdf\Mpdf(['tempDir' => WRITEPATH . 'cache/mpdf']);
$mpdf->WriteHTML($varTest);
file_put_contents(WRITEPATH . 'cache/mpdf_var_test.pdf', $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN));
echo "var_test pdf: " . WRITEPATH . "cache/mpdf_var_test.pdf\n";

echo 'single WriteHTML pages: ' . renderPages($html, false) . PHP_EOL;

$htmlResolved = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt(
    \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($rs->renderReportPdfHtml($data, $url, $qr, \App\Services\RegisterService::formatNowForReport(), $layout)),
    \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(['key' => 'letter'])
);
preg_match_all('/var\(--[^)]+\)/', $htmlResolved, $varsAfter);
echo 'var() after adapt: ' . count($varsAfter[0]) . PHP_EOL;

$bin = (new \App\Libraries\Pdf\MpdfPdfRenderer())->renderHtml(
    \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($rs->renderReportPdfHtml($data, $url, $qr, \App\Services\RegisterService::formatNowForReport(), $layout)),
    \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(['key' => 'letter'])
);
file_put_contents(WRITEPATH . 'cache/mpdf_test_resolved_308.pdf', $bin);
echo 'saved: ' . WRITEPATH . 'cache/mpdf_test_resolved_308.pdf' . PHP_EOL;
