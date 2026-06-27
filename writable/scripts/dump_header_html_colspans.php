<?php
declare(strict_types=1);
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

if (preg_match('/<table class="[^"]*pdf-section-table[^"]*"[^>]*data-pdf-cols="(\d+)"/', $html, $m)) {
    echo 'data-pdf-cols=' . $m[1] . PHP_EOL;
}
if (preg_match('/pdf-hg-block[\s\S]*?<tr class="pdf-section-row"[\s\S]*?<\/tr>/', $html, $row)) {
    preg_match_all('/<td\b[^>]*>/', $row[0], $tds);
    echo 'td count=' . count($tds[0]) . PHP_EOL;
    foreach ($tds[0] as $i => $td) {
        $colspan = preg_match('/colspan="(\d+)"/', $td, $cs) ? $cs[1] : '1';
        $width = preg_match('/width:([\d.]+)%/', $td, $w) ? $w[1] . '%' : (preg_match('/style="([^"]*)"/', $td, $st) && preg_match('/width:([\d.]+)%/', html_entity_decode($st[1]), $w2) ? $w2[1].'%' : '?');
        echo '  td' . ($i + 1) . ': colspan=' . $colspan . ' (tag snippet) ' . substr($td, 0, 120) . PHP_EOL;
    }
    if (preg_match_all('/<td[^>]*style="([^"]*)"/', $row[0], $styles, PREG_SET_ORDER)) {
        foreach ($styles as $i => $s) {
            preg_match('/width:([\d.]+)%/', html_entity_decode($s[1]), $w);
            echo '  td' . ($i + 1) . ' width=' . ($w[1] ?? '?') . '%' . PHP_EOL;
        }
    }
}
