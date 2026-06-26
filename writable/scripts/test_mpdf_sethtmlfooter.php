<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

$tempDir = dirname(__DIR__) . '/cache/mpdf';
if (! is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

$footerReserve = 22.0;
$marginBottom = 2.0 + $footerReserve;

function makeMpdf(float $marginBottom, float $footerReserve): Mpdf
{
    global $tempDir;
    return new Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'Letter',
        'tempDir'       => $tempDir,
        'margin_left'   => 25,
        'margin_right'  => 10,
        'margin_top'    => 5,
        'margin_bottom' => $marginBottom,
        'margin_footer' => $footerReserve,
        'default_font'  => 'dejavusans',
    ]);
}

$body = '<html><head><style>
.mpdf-ft-root { font-size:7pt; color:#186048; }
.mpdf-ft-root .mpdf-ft-table { width:100%; border-top:2px solid #236149; border-collapse:collapse; }
</style></head><body><h1>Contenido prueba</h1><p>Lorem ipsum dolor sit amet.</p></body></html>';

// Test A: footer simple inline
$mpdf = makeMpdf($marginBottom, $footerReserve);
$mpdf->SetHTMLFooter('<div style="font-size:9pt;color:#c00;border-top:2px solid #236149;padding-top:4px;">FOOTER SIMPLE Direccion: test</div>');
$mpdf->WriteHTML($body);
file_put_contents(dirname(__DIR__) . '/debug/mpdf_ft_simple.pdf', $mpdf->Output('', Destination::STRING_RETURN));

// Test B: footer con style block (debe fallar / invisible)
$mpdf = makeMpdf($marginBottom, $footerReserve);
$mpdf->SetHTMLFooter('<div><style>.x{color:red;font-size:12pt}</style><div class="x">FOOTER STYLE BLOCK</div></div>');
$mpdf->WriteHTML($body);
file_put_contents(dirname(__DIR__) . '/debug/mpdf_ft_styleblock.pdf', $mpdf->Output('', Destination::STRING_RETURN));

// Test C: footer con clases del documento principal
$mpdf = makeMpdf($marginBottom, $footerReserve);
$mpdf->SetHTMLFooter('<div class="mpdf-ft-root"><table class="mpdf-ft-table"><tr><td>Direccion: C/Mendez</td><td>Celular: 123</td></tr></table></div>');
$mpdf->WriteHTML($body);
file_put_contents(dirname(__DIR__) . '/debug/mpdf_ft_classes.pdf', $mpdf->Output('', Destination::STRING_RETURN));

// Test D: wrong margins (2 bottom, 22 footer reserve) — body overlaps footer
$mpdf = makeMpdf(2.0, $footerReserve);
$mpdf->SetHTMLFooter('<div style="font-size:9pt;color:#c00;border-top:2px solid #236149;">FOOTER WRONG MARGIN</div>');
$mpdf->WriteHTML(str_repeat('<p>Linea de contenido largo para llenar la pagina.</p>', 40));
file_put_contents(dirname(__DIR__) . '/debug/mpdf_ft_wrong_margin.pdf', $mpdf->Output('', Destination::STRING_RETURN));

function pdfContains(string $path, string $needle): bool
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        return false;
    }
    if (str_contains($raw, $needle)) {
        return true;
    }
    // decompress flate streams
    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $m)) {
        foreach ($m[1] as $stream) {
            $dec = @gzuncompress($stream);
            if ($dec === false) {
                $dec = @gzinflate(substr($stream, 2));
            }
            if (is_string($dec) && str_contains($dec, $needle)) {
                return true;
            }
        }
    }
    return false;
}

foreach (['mpdf_ft_simple.pdf', 'mpdf_ft_styleblock.pdf', 'mpdf_ft_classes.pdf', 'mpdf_ft_wrong_margin.pdf'] as $f) {
    $p = dirname(__DIR__) . '/debug/' . $f;
    echo $f . ': FOOTER_SIMPLE=' . (pdfContains($p, 'FOOTER SIMPLE') || pdfContains($p, 'Direccion') || pdfContains($p, 'FOOTER STYLE') || pdfContains($p, 'FOOTER WRONG') ? 'yes' : 'NO') . PHP_EOL;
    echo '  Direccion=' . (pdfContains($p, 'Direccion') ? 'yes' : 'no') . PHP_EOL;
}
