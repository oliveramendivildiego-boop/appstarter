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

$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData((int) ($argv[1] ?? 308));
helper('qr');
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

$renderer = new App\Libraries\Pdf\DompdfPdfRenderer();
$ref = new ReflectionClass($renderer);
$extract = $ref->getMethod('extractPaginationSlots');
$extract->setAccessible(true);
$build = $ref->getMethod('buildPaginationCallbacks');
$build->setAccessible(true);

$slots = $extract->invoke($renderer, $html);
echo 'slots: ' . count($slots) . PHP_EOL;
foreach ($slots as $i => $slot) {
    echo "slot {$i} zone=" . ($slot['zone'] ?? '?') . ' inline=' . (!empty($slot['inlineAfterLabel']) ? 'yes' : 'no') . PHP_EOL;
}
$callbacks = $build->invoke($renderer, $slots);
echo 'callbacks: ' . count($callbacks) . PHP_EOL;

$logged = [];
$dompdf = new Dompdf\Dompdf();
$dompdf->setCallbacks([[
    'event' => 'end_document',
    'f' => function (int $pn, int $pc, $canvas, $fm) use ($renderer, $slots, &$logged): void {
        foreach ($slots as $slot) {
            $ref = new ReflectionClass($renderer);
            $paint = $ref->getMethod('paintPaginationOnPage');
            $paint->setAccessible(true);
            try {
                $paint->invoke($renderer, $canvas, $fm, $slot, $pn, $pc);
                $logged[] = "painted p{$pn}/{$pc} zone=" . ($slot['zone'] ?? '?');
            } catch (Throwable $e) {
                $logged[] = 'err: ' . $e->getMessage();
            }
        }
    },
]]);
$dompdf->loadHtml('<html><body><p>one</p><p style="page-break-before:always">two</p></body></html>');
$dompdf->render();
echo implode(PHP_EOL, $logged) . PHP_EOL;
