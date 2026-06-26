<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Options;

$mode = ($argv[1] ?? 'nopad') === 'pad' ? 'pad' : 'nopad';
$ml = 25;
$mr = 10;
$mb = 2;
$reserve = 22;
$bodyMb = $mb + $reserve;
$padCls = $mode === 'pad' ? 'inner-pad' : '';
$padCss = $mode === 'pad' ? "padding-left:{$ml}mm;padding-right:{$mr}mm;" : '';

$html = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
@page { margin: 5mm {$mr}mm {$bodyMb}mm {$ml}mm; }
body { margin:0; font-family: DejaVu Sans; font-size: 9pt; }
.content { height: 9in; }
.anchor { position:fixed; left:0; right:0; bottom:-{$reserve}mm; height:0; overflow:visible; z-index:0; }
.inner { position:absolute; left:0; right:0; bottom:0; min-height:{$reserve}mm; background:#fff; padding-top:6px; box-sizing:border-box; {$padCss} }
table { width:100%; table-layout:fixed; border-collapse:collapse; }
td { border:1px solid #ccc; font-size:8pt; vertical-align:top; }
.c0 { width:40%; } .c1 { width:20%; } .c2 { width:40%; text-align:right; }
</style></head><body>
<div class="content"><p>LINE@BODY-START</p><p style="page-break-before:always">PAGE2</p></div>
<div class="anchor"><div class="inner {$padCls}">
<table><tr>
<td class="c0">FOOT-LEFT</td>
<td class="c1">FOOT-MID</td>
<td class="c2">FOOT-RIGHT</td>
</tr></table>
</div></div>
</body></html>
HTML;

$opts = new Options();
$opts->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($opts);
$dompdf->loadHtml($html);
$dompdf->setCallbacks([[
    'event' => 'end_document',
    'f' => function (int $pn, int $pc, $canvas, FontMetrics $fm) use ($ml, $mr, $mb, $reserve, $mode): void {
        unset($pn, $pc);
        $pageW = (float) $canvas->get_width();
        $pageH = (float) $canvas->get_height();
        $mm = 72 / 25.4;
        $mlPt = $ml * $mm;
        $mrPt = $mr * $mm;
        $mbPt = $mb * $mm;
        $resPt = $reserve * $mm;
        $font = $fm->getFont('DejaVu Sans', 'normal');
        $fs = 8.0;
        $contentW = $pageW - $mlPt - $mrPt;
        $xCanvas = $mlPt + 0.6 * $contentW;
        $xFull = 0.6 * $pageW;
        $y = $pageH - $mbPt - $resPt + 6 * (72 / 96) + $fs * 0.82;
        $canvas->text($xCanvas, $y, "CANVAS@{$mode}", $font, $fs, [1, 0, 0]);
        $canvas->text($xFull, $y + 12, "FULL@{$mode}", $font, $fs, [0, 0, 1]);
    },
]]);
$dompdf->render();
$out = dirname(__DIR__) . "/debug/footer_align_{$mode}.pdf";
@mkdir(dirname($out), 0777, true);
file_put_contents($out, $dompdf->output());
echo "saved $out\n";
