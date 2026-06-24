<?php
declare(strict_types=1);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$svc = new App\Libraries\PdfService();
$ref = new ReflectionClass($svc);
$extract = $ref->getMethod('extractOrderSheetHeaderSlot');
$extract->setAccessible(true);

$rs = new App\Services\RegisterService();
$data = $rs->prepareReportData(262);
helper('qr');
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));
$slot = $extract->invoke($svc, $html);
echo 'slot: ' . json_encode($slot, JSON_UNESCAPED_UNICODE) . PHP_EOL;

$paint = $ref->getMethod('paintOrderSheetHeaderOnPage');
$paint->setAccessible(true);
$build = $ref->getMethod('buildOrderSheetHeaderCallbacks');
$build->setAccessible(true);
$callbacks = $build->invoke($svc, $slot);
echo 'callbacks: ' . count($callbacks) . PHP_EOL;

$paintPag = $ref->getMethod('paintPaginationOnPage');
$paintPag->setAccessible(true);
$pagSlot = [
    'prefix' => 'P ',
    'zone' => 'footer',
    'align' => 'left',
    'fontSize' => 10,
    'fontFamily' => 'DejaVu Sans',
    'color' => '#333333',
    'mt' => 15,
    'mr' => 15,
    'mb' => 15,
    'ml' => 15,
];

$errors = [];
$dompdf = new Dompdf\Dompdf();
$dompdf->setCallbacks([[
    'event' => 'end_document',
    'f'     => function (int $pn, int $pc, $canvas, $fontMetrics) use ($svc, $paint, $paintPag, $slot, $pagSlot, &$errors): void {
        $errors[] = 'page ' . $pn . '/' . $pc;
        if ($pn < 2) {
            return;
        }
        try {
            $paintPag->invoke($svc, $canvas, $fontMetrics, $pagSlot, $pn, $pc);
            $paint->invoke($svc, $canvas, $fontMetrics, $slot);
            $errors[] = 'painted page ' . $pn;
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
        }
    },
]]);
$dompdf->loadHtml('<html><body><p style="page-break-after:always">p1</p><p>p2</p><p style="page-break-after:always">p3</p><p>p4</p></body></html>');
$dompdf->render();
$pdf = $dompdf->output(['compress' => 0]);
file_put_contents(dirname(__DIR__) . '/debug/order_sheet_callback_probe.pdf', $pdf);
echo 'errors/log: ' . implode(' | ', $errors) . PHP_EOL;
echo 'TEST-2 in pdf: ' . (str_contains($pdf, 'TEST-2') ? 'yes' : 'no') . PHP_EOL;
