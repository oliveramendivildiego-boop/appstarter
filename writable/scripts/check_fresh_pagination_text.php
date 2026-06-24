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

$id = (int) ($argv[1] ?? 262);
$htmlFile = dirname(__DIR__) . "/debug/report_{$id}_fresh.html";
if (! is_file($htmlFile)) {
    fwrite(STDERR, "Missing {$htmlFile}\n");
    exit(1);
}
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
foreach (['Página 1 de', 'Página 2 de', 'Página 3 de', 'Página 4 de', '1 de 4'] as $p) {
    echo "Contains '{$p}': " . (str_contains($pdf, $p) ? 'yes' : 'no') . PHP_EOL;
}
