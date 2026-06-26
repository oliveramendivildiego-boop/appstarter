<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

function hasFooter(string $bin): bool
{
    $tmp = dirname(__DIR__) . '/cache/_ft_probe.pdf';
    file_put_contents($tmp, $bin);
    return trim((string) shell_exec('python ' . escapeshellarg(dirname(__DIR__) . '/scripts/extract_pdf_text.py') . ' 2>nul')) !== '';
}

$footer = file_get_contents(dirname(__DIR__) . '/debug/ft_test_footer_final.html');
$block = file_get_contents(dirname(__DIR__) . '/debug/break_style_block.html');
$metrics = ['left'=>25,'right'=>10,'top'=>5,'bottom'=>2,'footer_reserve_mm'=>22.0];
$fr=22; $mb=24;
$render = function(string $css) use ($footer, $fr, $mb): string {
    $mpdf = new Mpdf(['mode'=>'utf-8','format'=>'Letter','tempDir'=>dirname(__DIR__).'/cache/mpdf',
        'margin_left'=>25,'margin_right'=>10,'margin_top'=>5,'margin_bottom'=>$mb,'margin_footer'=>$fr,'default_font'=>'dejavusans']);
    $mpdf->SetHTMLFooter($footer);
    $mpdf->WriteHTML('<html><head>'.$css.'</head><body><p>test</p></body></html>');
    return $mpdf->Output('', Destination::STRING_RETURN);
};

$py = dirname(__DIR__).'/cache/_has_footer.py';
$check = function(string $bin) use ($py): bool {
    $tmp = dirname(__DIR__).'/cache/_ft_probe.pdf'; file_put_contents($tmp,$bin);
    return trim((string)shell_exec('python '.escapeshellarg($py).' '.escapeshellarg($tmp)))==='1';
};

echo '@page only: ';
$css = '@page{margin-top:5mm;margin-right:10mm;margin-bottom:24mm;margin-left:25mm;}';
echo ($check($render('<style>'.$css.'</style>'))?'YES':'NO').PHP_EOL;

echo 'full block: '.($check($render($block))?'YES':'NO').PHP_EOL;
$noPage = preg_replace('/@page\s*\{[^}]*\}/s', '', $block) ?? $block;
echo 'block sin @page: '.($check($render($noPage))?'YES':'NO').PHP_EOL;

$bodyFull = file_get_contents(dirname(__DIR__).'/debug/adapted_body_308.html');
$stripped = preg_replace('/@page\s*\{[^}]*\}/s', '', $bodyFull) ?? $bodyFull;
$mpdf = new Mpdf(['mode'=>'utf-8','format'=>'Letter','tempDir'=>dirname(__DIR__).'/cache/mpdf',
    'margin_left'=>25,'margin_right'=>10,'margin_top'=>5,'margin_bottom'=>24,'margin_footer'=>22,'default_font'=>'dejavusans']);
$mpdf->SetHTMLFooter($footer);
$mpdf->WriteHTML($stripped);
echo 'full doc sin @page: '.($check($mpdf->Output('', Destination::STRING_RETURN))?'YES':'NO').PHP_EOL;
