<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 143);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
if ($data === null) {
    fwrite(STDERR, "No data for register {$id}\n");
    exit(1);
}
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
$html = $rs->getOrBuildReportPdfHtml($id, $data, $url, $qr, $emitido, $layout);

echo 'raw html markers: ' . substr_count($html, 'report-pdf-footer:start') . PHP_EOL;
$countRootDivs = static function (string $html): int {
    preg_match_all('/<div\b[^>]*\bclass="[^"]*\bmpdf-ft-root\b[^"]*"[^>]*>/i', $html, $m);

    return count($m[0]);
};

echo 'raw html mpdf-ft-root divs: ' . $countRootDivs($html) . PHP_EOL;

$h = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
$h = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($h);
$h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
[$body, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);
$layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
$opts = \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(null);
$bodyAdapted = \App\Libraries\Pdf\HtmlMpdfAdapter::adapt($body, $opts);

echo "register={$id}\n";
echo 'after extract body mpdf-ft-root divs: ' . $countRootDivs($body) . PHP_EOL;
echo 'footer inner mpdf-ft-root divs: ' . $countRootDivs((string) $fi) . PHP_EOL;
echo 'after adapt body mpdf-ft-root divs: ' . $countRootDivs($bodyAdapted) . PHP_EOL;

echo 'body has pdf-dompdf-footer-anchor: ' . (str_contains($bodyAdapted, 'pdf-dompdf-footer-anchor') ? 'YES' : 'no') . PHP_EOL;
echo 'body has report-pdf-footer marker: ' . (str_contains($bodyAdapted, 'report-pdf-footer') ? 'YES' : 'no') . PHP_EOL;

preg_match_all('/pdf-ft-piece/', $bodyAdapted, $bm);
preg_match_all('/pdf-ft-piece/', (string) $fi, $fm);
echo 'pdf-ft-piece count body=' . count($bm[0]) . ' footer=' . count($fm[0]) . PHP_EOL;

$slot = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::extractSlot($html);
$wrapped = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter((string) $fi, $layoutSnap);
if (\App\Libraries\Pdf\MpdfOrderSheetFooterInjector::shouldPrependOrderSheetBand($slot, (string) $fi)) {
    $wrapped = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::prependOrderSheetRowToFooter(
        $wrapped,
        $slot,
        \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::countFooterColumns($wrapped),
    );
}

echo 'order sheet band prepended: ' . (str_contains($wrapped, 'mpdf-order-sheet-above') ? 'yes' : 'no') . PHP_EOL;
echo 'pdf-order-sheet-table-row count: ' . substr_count($wrapped, 'pdf-order-sheet-table-row') . PHP_EOL;

preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $wrapped, $cells);
$texts = [];
foreach ($cells[1] as $raw) {
    $t = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    if ($t !== '' && $t !== "\xE2\x80\x8B") {
        $texts[] = $t;
    }
}
$counts = array_count_values($texts);
$dups = array_filter($counts, static fn (int $c): bool => $c > 1);
echo 'duplicate cell texts in SetHTMLFooter HTML: ';
if ($dups === []) {
    echo "none\n";
} else {
    echo json_encode($dups, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

// Stack tables: nested pdf-ft-piece in same cell
preg_match_all('/<td[^>]*class="[^"]*mpdf-ft-cell[^"]*"[^>]*>([\s\S]*?)<\/td>/i', $wrapped, $ftCells);
$nestedDup = 0;
foreach ($ftCells[1] as $cellHtml) {
    preg_match_all('/class="[^"]*pdf-ft-piece[^"]*"[^>]*>([^<]+)</', $cellHtml, $pieces);
    $vals = array_map('trim', $pieces[1] ?? []);
    $vals = array_filter($vals, static fn (string $v): bool => $v !== '');
    $vc = array_count_values($vals);
    foreach ($vc as $v => $c) {
        if ($c > 1) {
            $nestedDup++;
            echo "nested dup in cell: \"{$v}\" x{$c}\n";
        }
    }
}
if (preg_match('/(<div\b[^>]*\bpdf-dompdf-footer-anchor\b[\s\S]*?<\/body>)/i', $bodyAdapted, $tail)) {
    file_put_contents(WRITEPATH . 'debug/footer_body_tail_' . $id . '.html', $tail[1]);
    echo 'wrote body tail snippet to writable/debug/footer_body_tail_' . $id . '.html' . PHP_EOL;
}
