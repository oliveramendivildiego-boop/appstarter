<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$style = "font-family:'DejaVu Sans' !important;font-size:12pt !important;font-weight:bold !important;color:#333333 !important;margin:0 !important;";
$htmlDiv = '<div class="report-pdf-grupo-cabecera-line--title" style="' . $style . '">HEMOGRAMA + PLAQUETAS</div><p>Método: test</p>';
$htmlH4 = '<h4 class="report-pdf-grupo-cabecera-line--title" style="' . $style . '">HEMOGRAMA + PLAQUETAS</h4><p>Método: test</p>';
$css = 'body.pdf-engine-mpdf .report-pdf-grupo-cabecera-line--title{font-family:\'DejaVu Sans\',sans-serif !important;font-weight:bold !important;}';

foreach (['div' => $htmlDiv, 'h4' => $htmlH4] as $label => $body) {
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => dirname(__DIR__) . '/cache/mpdf']);
    $mpdf->WriteHTML('<style>' . $css . '</style><body class="pdf-engine-mpdf">' . $body . '</body>');
    $out = dirname(__DIR__) . '/cache/wm_title_test_' . $label . '.pdf';
    $mpdf->Output($out, \Mpdf\Output\Destination::FILE);
    echo $label . ' -> ' . $out . PHP_EOL;
}
