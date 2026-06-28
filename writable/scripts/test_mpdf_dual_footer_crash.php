<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

$tempDir = dirname(__DIR__) . '/cache/mpdf';
$ft = [
    'section_top_border_enabled'  => true,
    'section_top_border_width_px' => 2,
    'section_top_border_color'    => '#236149',
    'body_bg_color'               => '#ffffff',
    'body_text_color'             => '#333333',
    'font_size_pt'                => 8,
    'line_height'                 => 1.35,
];
$layout = [
    'page_style'      => ['footer_grid' => $ft],
    'blocks'          => [['id' => 'footer', 'enabled' => true]],
    'section_layouts' => ['footer' => ['columns' => 5, 'row_gap_px' => 2]],
    'margins_mm'      => ['top' => 15, 'right' => 15, 'bottom' => 15, 'left' => 25],
];

$inner = '<div class="mpdf-ft-root pdf-ft-block footer-grid"><table class="pdf-section-table mpdf-ft-table" width="100%" data-pdf-cols="5" style="table-layout:fixed;border-collapse:collapse;border-top:2px solid #236149;"><tr><td class="mpdf-ft-cell" colspan="4">Direccion: Calle 1</td><td class="mpdf-ft-cell">Pag {PAGENO}</td></tr><tr><td class="mpdf-ft-cell" colspan="2">Correo</td><td class="mpdf-ft-cell">Ciudad</td><td class="mpdf-ft-cell" colspan="2">Tel</td></tr></table></div>';
$p1 = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter($inner, $layout);
$band = '<table class="mpdf-order-sheet-above" width="100%" style="table-layout:fixed;border-collapse:collapse;margin:0 0 2px 0;"><tr class="mpdf-order-sheet-row"><td class="mpdf-order-sheet-patient">PACIENTE TEST</td><td class="mpdf-order-sheet-spacer"></td><td class="mpdf-order-sheet-order">ORD-318</td></tr></table>';
$p2 = preg_replace('/(<table\b[^>]*\bmpdf-ft-table\b)/i', $band . '$1', $p1, 1) ?? $p1;
$css  = \App\Libraries\Pdf\MpdfFooterStyles::buildFooterCssRules($layout);
$body = '<html><head><style>' . $css . '</style></head><body>';
for ($i = 1; $i <= 5; $i++) {
    $body .= '<p style="font-size:12pt">Linea de contenido pagina fill ' . $i . ' ' . str_repeat('Lorem ipsum dolor sit amet. ', 40) . '</p>';
}
$body .= '</body></html>';

function mkMpdf(float $marginBottom = 40, float $marginFooter = 15): Mpdf
{
    return new Mpdf([
        'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => dirname(__DIR__) . '/cache/mpdf',
        'margin_left' => 25, 'margin_right' => 15, 'margin_top' => 15,
        'margin_bottom' => $marginBottom, 'margin_footer' => $marginFooter,
        'default_font' => 'dejavusans', 'use_kwt' => true,
    ]);
}

// A: single SetHTMLFooter
try {
    $m = mkMpdf();
    $m->SetHTMLFooter($p1);
    $m->WriteHTML($body);
    file_put_contents(dirname(__DIR__) . '/debug/mpdf_test_A_single.pdf', $m->Output('', Destination::STRING_RETURN));
    echo "[OK] A single footer pages=" . $m->page . PHP_EOL;
} catch (Throwable $e) {
    echo '[FAIL] A ' . $e->getMessage() . PHP_EOL;
}

// B: named dual footers + @page (current production path)
try {
    $m = mkMpdf();
    $html = \App\Libraries\Pdf\MpdfNamedFooterInjector::injectPageCss($body, 15);
    \App\Libraries\Pdf\MpdfNamedFooterInjector::registerFooters($m, $p1, $p2);
    $m->WriteHTML($html);
    file_put_contents(dirname(__DIR__) . '/debug/mpdf_test_B_dual.pdf', $m->Output('', Destination::STRING_RETURN));
    echo "[OK] B dual footer pages=" . $m->page . PHP_EOL;
} catch (Throwable $e) {
    echo '[FAIL] B ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . PHP_EOL;
}

// C: dual without margin-footer in @page
try {
    $m = mkMpdf();
    $cssPage = "<style>@page { footer: html_report-ft-rest; } @page :first { footer: html_report-ft-p1; }</style>";
    $html = str_ireplace('</head>', $cssPage . '</head>', $body);
    \App\Libraries\Pdf\MpdfNamedFooterInjector::registerFooters($m, $p1, $p2);
    $m->WriteHTML($html);
    file_put_contents(dirname(__DIR__) . '/debug/mpdf_test_C_dual_nomargin.pdf', $m->Output('', Destination::STRING_RETURN));
    echo "[OK] C dual no margin-footer pages=" . $m->page . PHP_EOL;
} catch (Throwable $e) {
    echo '[FAIL] C ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . PHP_EOL;
}

// D: countDocumentPages simulation
try {
    $m = mkMpdf();
    $m->SetHTMLFooter($p1);
    $m->WriteHTML($body);
    $pages = max(1, (int) $m->page);
    echo "[OK] D count pages=$pages" . PHP_EOL;
    $m2 = mkMpdf();
    $html = \App\Libraries\Pdf\MpdfNamedFooterInjector::injectPageCss($body, 15);
    \App\Libraries\Pdf\MpdfNamedFooterInjector::registerFooters($m2, $p1, $p2);
    $m2->WriteHTML($html);
    echo "[OK] D2 dual after count pages=" . $m2->page . PHP_EOL;
} catch (Throwable $e) {
    echo '[FAIL] D ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . PHP_EOL;
}
