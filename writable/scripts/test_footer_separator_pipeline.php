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
    'section_layouts' => ['footer' => ['row_gap_px' => 0]],
];

$inner = '<div class="mpdf-ft-root pdf-ft-block footer-grid"><table class="pdf-section-table mpdf-ft-table" width="100%" data-pdf-cols="5" style="table-layout:fixed;border-collapse:collapse;border-top:2px solid #236149;"><tr><td class="mpdf-ft-cell">A</td><td class="mpdf-ft-cell">B</td></tr></table></div>';

$wrapped = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter($inner, $layout);
$css     = \App\Libraries\Pdf\MpdfFooterStyles::buildFooterCssRules($layout);

echo "Has mpdf-ft-top-border: " . (str_contains($wrapped, 'mpdf-ft-top-border') ? 'yes' : 'NO') . PHP_EOL;
echo "Table tag well-formed: " . (preg_match('/<table\b[^>]*\bmpdf-ft-table\b[^>]*>/i', $wrapped) ? 'yes' : 'NO BROKEN') . PHP_EOL;
echo "Table still has border-top: " . (preg_match('/mpdf-ft-table[^>]*border-top/i', $wrapped) ? 'yes' : 'no') . PHP_EOL;
echo "Separator inline color: " . (str_contains($wrapped, '#236149') ? 'yes' : 'NO') . PHP_EOL;

file_put_contents(dirname(__DIR__) . '/debug/ft_wrapped_separator.html', $wrapped);

$mpdf = new Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'Letter',
    'tempDir'       => $tempDir,
    'margin_bottom' => 24,
    'margin_footer' => 22,
    'default_font'  => 'dejavusans',
]);
$html = '<html><head><style>' . $css . '</style></head><body><p>Test body</p></body></html>';
$mpdf->SetHTMLFooter($wrapped);
$mpdf->WriteHTML($html);
file_put_contents(dirname(__DIR__) . '/debug/ft_separator_pipeline.pdf', $mpdf->Output('', Destination::STRING_RETURN));
echo "wrote ft_separator_pipeline.pdf\n";
