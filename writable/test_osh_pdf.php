<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Libraries\PdfService;

$footerReserve = 22;
$mb = 10;
$payload = base64_encode((string) json_encode([
    'patient'             => 'Paciente: Juan Perez',
    'order'               => 'No. Orden: ORD-001',
    'margin_bottom_mm'    => $mb,
    'margin_left_mm'      => 10,
    'margin_right_mm'     => 10,
    'footer_enabled'      => true,
    'footer_reserve_mm'   => $footerReserve,
    'gap_above_footer_mm' => 1.5,
], JSON_UNESCAPED_UNICODE));

$html = <<<HTML
<!DOCTYPE html>
<html><head>
<style>
@page { margin: 10mm; margin-bottom: 32mm; }
.pdf-ft-block.footer-grid { position:fixed;left:0;right:0;bottom:-{$footerReserve}mm;min-height:{$footerReserve}mm;background:#fff;z-index:2;padding-top:6px; }
</style>
</head><body>
<!-- pdf-order-sheet-header-dompdf -->
<!-- pdf-order-sheet-header-data:{$payload} -->
<div style="height:900px">P1</div>
<div style="page-break-before:always;height:900px">P2</div>
<div class="pdf-ft-block footer-grid">Pie fijo</div>
</body></html>
HTML;

$pdf = (new PdfService())->generate($html, 'test.pdf');
file_put_contents(__DIR__ . '/test_osh_pdf.pdf', $pdf);
echo 'ok pages test written' . PHP_EOL;
