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
helper('qr');

$registroId = (int) ($argv[1] ?? 308);
$pdfPath = dirname(__DIR__) . '/cache/dompdf_test_' . $registroId . '.pdf';
if (! is_file($pdfPath)) {
    echo "missing $pdfPath - run test_dompdf_report.php first\n";
    exit(1);
}

$bin = (string) file_get_contents($pdfPath);
$patterns = ['Página', 'de ', 'ATENTAMENTE', 'Verificado', 'Matr', 'gina'];
foreach ($patterns as $p) {
    echo "pattern [$p]: " . (substr_count($bin, $p) + substr_count($bin, mb_convert_encoding($p, 'UTF-16BE', 'UTF-8'))) . PHP_EOL;
}

if (preg_match_all('/\(([^\)]{3,80})\)/', $bin, $m)) {
    $seen = [];
    foreach ($m[1] as $raw) {
        $s = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $raw);
        if (preg_match('/Página|ATENTAMENTE|Verificado|Matr|de [0-9]/u', $s) && ! isset($seen[$s])) {
            $seen[$s] = true;
            echo "text: $s\n";
        }
    }
}
