<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Libraries\PdfService;

$footerReserve = 22;
$mb = 10;
$mt = 10;
$ml = 10;
$mr = 10;
$bodyMarginBottom = $mb + $footerReserve;

$patientLine = 'Paciente: Juan Perez';
$orderLine = 'No. Orden: ORD-001';
$payload = base64_encode((string) json_encode([
    'patient'             => $patientLine,
    'order'               => $orderLine,
    'margin_bottom_mm'    => $mb,
    'margin_left_mm'      => $ml,
    'margin_right_mm'     => $mr,
    'footer_enabled'      => true,
    'footer_reserve_mm'   => $footerReserve,
    'gap_above_footer_mm' => 1.5,
], JSON_UNESCAPED_UNICODE));

$html = <<<HTML
<!DOCTYPE html>
<html><head>
<style>
@page { margin-top: {$mt}mm; margin-right: {$mr}mm; margin-bottom: {$bodyMarginBottom}mm; margin-left: {$ml}mm; }
body { margin: 0; font-family: DejaVu Sans, sans-serif; }
.pdf-ft-block.footer-grid {
    position: fixed !important;
    left: 0 !important;
    right: 0 !important;
    bottom: -{$footerReserve}mm !important;
    min-height: {$footerReserve}mm !important;
    background: #ffffff;
    padding-top: 6px;
    z-index: 2;
}
</style>
</head><body>
<!-- pdf-order-sheet-header-dompdf -->
<!-- pdf-order-sheet-header-data:{$payload} -->
<div style="height:900px;background:#f5f5f5">Pagina 1</div>
<div style="page-break-before:always;height:900px;background:#e8e8e8">Pagina 2</div>
<div class="pdf-ft-block footer-grid">Pie fijo laboratorio - pagina en todas las hojas</div>
</body></html>
HTML;

$pdf = (new PdfService())->generate($html, 'test.pdf');
$out = __DIR__ . '/test_order_header_footer.pdf';
file_put_contents($out, $pdf);
echo 'written ' . $out . ' bytes=' . strlen($pdf) . PHP_EOL;
