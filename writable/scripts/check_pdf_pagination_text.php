<?php
declare(strict_types=1);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
defined('CI_DEBUG') || define('CI_DEBUG', true);
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$htmlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . 'report_262.html';
$html = (string) file_get_contents($htmlFile);

$svc = new \App\Libraries\PdfService();
$ref = new ReflectionClass($svc);
$extract = $ref->getMethod('extractPaginationSlots');
$extract->setAccessible(true);
$slots = $extract->invoke($svc, $html);
echo 'Slots: ' . count($slots) . PHP_EOL;
if ($slots !== []) {
    echo json_encode($slots[0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
}

$pdf = $svc->generate($html, 'test.pdf');
file_put_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . 'pagination_check.pdf', $pdf);

foreach (['Página 1 de', '1 de 4', '2 de 4', 'gina 1'] as $p) {
    echo "Contains '{$p}': " . (str_contains($pdf, $p) ? 'yes' : 'no') . PHP_EOL;
}
