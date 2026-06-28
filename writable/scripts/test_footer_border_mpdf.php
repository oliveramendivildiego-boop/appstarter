<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

$tempDir = dirname(__DIR__) . '/cache/mpdf';
if (! is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

$ft = [
    'section_top_border_enabled'    => true,
    'section_top_border_width_px'   => 2,
    'section_top_border_color'      => '#236149',
    'body_bg_color'                 => '#ffffff',
    'body_text_color'               => '#333333',
    'font_size_pt'                  => 8,
    'line_height'                   => 1.35,
];
$layout = [
    'page_style'       => ['footer_grid' => $ft],
    'blocks'           => [['id' => 'footer', 'enabled' => true]],
    'section_layouts'  => ['footer' => ['row_gap_px' => 0]],
    'margins_mm'       => ['top' => 15, 'right' => 15, 'bottom' => 15, 'left' => 15],
];
$borderInline = \App\Services\ReportPdfLayoutService::footerGridSectionTableBorderStyleAttr($ft);
$borderCss    = \App\Services\ReportPdfLayoutService::footerGridSectionTableBorderTopCss($ft, true);
$footerCss    = \App\Libraries\Pdf\MpdfFooterStyles::buildFooterCssRules($layout);

echo "inline attr: {$borderInline}\n";
echo "css rule: {$borderCss}\n";
echo 'buildFooterCssRules has border: ' . (str_contains($footerCss, '#236149') ? 'yes' : 'NO') . "\n";

$bodyHead = '<html><head><style>' . $footerCss . '</style></head><body><p>Body</p></body></html>';

$cases = [
    'table_inline_plain' => '<div class="mpdf-ft-root"><table class="pdf-section-table mpdf-ft-table" style="width:100%;border-collapse:collapse;border-top:2px solid #236149;"><tr><td>Col A</td><td>Col B</td></tr></table></div>',
    'table_inline_imp'   => '<div class="mpdf-ft-root"><table class="pdf-section-table mpdf-ft-table" style="width:100%;border-collapse:collapse;' . $borderInline . '"><tr><td>Col A</td><td>Col B</td></tr></table></div>',
    'root_div_border'    => '<div class="mpdf-ft-root" style="border-top:2px solid #236149;padding-top:6px;"><table class="pdf-section-table mpdf-ft-table" style="width:100%;border-collapse:collapse;"><tr><td>Col A</td><td>Col B</td></tr></table></div>',
    'css_only'           => '<div class="mpdf-ft-root"><table class="pdf-section-table mpdf-ft-table"><tr><td>Col A</td><td>Col B</td></tr></table></div>',
];

foreach ($cases as $name => $footerHtml) {
    $mpdf = new Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'Letter',
        'tempDir'       => $tempDir,
        'margin_bottom' => 24,
        'margin_footer' => 22,
        'default_font'  => 'dejavusans',
    ]);
    $mpdf->SetHTMLFooter($name === 'css_only' ? $footerHtml : $footerHtml);
    $html = $name === 'css_only'
        ? str_replace('</head>', '<style>' . $footerCss . '</style></head>', $bodyHead)
        : $bodyHead;
    $mpdf->SetHTMLFooter($footerHtml);
    $mpdf->WriteHTML($html);
    $path = dirname(__DIR__) . '/debug/ft_border_' . $name . '.pdf';
    file_put_contents($path, $mpdf->Output('', Destination::STRING_RETURN));
    echo "wrote {$name}\n";
}
