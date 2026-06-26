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
$data = $rs->prepareReportData(308);
helper('qr');
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

$renderer = new App\Libraries\Pdf\DompdfPdfRenderer();
$ref = new ReflectionClass($renderer);
$gen = $ref->getMethod('generateDompdf');
$gen->setAccessible(true);
$bin = $gen->invoke($renderer, $html, null);

$out = WRITEPATH . 'cache/dompdf_uncompressed.pdf';
// rewrite via dompdf output compress 0 - generateDompdf uses default compress true
$dompdf = new Dompdf\Dompdf();
$extract = $ref->getMethod('extractPaginationSlots');
$extract->setAccessible(true);
$slots = $extract->invoke($renderer, $html);
$register = $ref->getMethod('registerDompdfCallbacks');
$register->setAccessible(true);
$register->invoke($renderer, $dompdf, null, $slots, null);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();
$bin2 = $dompdf->output(['compress' => 0]);
file_put_contents($out, $bin2);

echo 'size: ' . strlen($bin2) . PHP_EOL;
echo 'has Página utf8: ' . (str_contains($bin2, 'Página') ? 'yes' : 'no') . PHP_EOL;
echo 'has Pagina ascii: ' . (str_contains($bin2, 'Pagina') ? 'yes' : 'no') . PHP_EOL;
echo 'has 1 de 3: ' . (str_contains($bin2, '1 de 3') ? 'yes' : 'no') . PHP_EOL;
preg_match_all('/\d+ de \d+/', $bin2, $m);
echo 'patterns: ' . implode(', ', array_unique($m[0] ?? [])) . PHP_EOL;
