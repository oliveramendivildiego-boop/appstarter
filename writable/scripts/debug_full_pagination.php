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

$html = (string) file_get_contents(dirname(__DIR__) . '/debug/report_262.html');
$logFile = dirname(__DIR__) . '/debug/pagination_paint_log.txt';
@unlink($logFile);

$svc = new \App\Libraries\PdfService();
$ref = new ReflectionClass($svc);

// Patch paintPaginationOnPage to log
$orig = $ref->getMethod('paintPaginationOnPage');
$orig->setAccessible(true);

$extract = $ref->getMethod('extractPaginationSlots');
$extract->setAccessible(true);
$slots = $extract->invoke($svc, $html);
file_put_contents($logFile, 'slots=' . count($slots) . PHP_EOL, FILE_APPEND);

$buildCb = $ref->getMethod('buildPaginationCallbacks');
$buildCb->setAccessible(true);
$cbs = $buildCb->invoke($svc, $slots);
file_put_contents($logFile, 'callbacks=' . count($cbs) . PHP_EOL, FILE_APPEND);

$pdf = $svc->generate($html, 'test.pdf');
file_put_contents(dirname(__DIR__) . '/debug/pagination_full_test.pdf', $pdf);

$found = [];
if (preg_match_all('/\[\(([^\)]{1,80})\)\]/', $pdf, $m)) {
    foreach ($m[1] as $s) {
        if (str_contains($s, 'de')) {
            $found[] = $s;
        }
    }
}
file_put_contents($logFile, 'found=' . json_encode($found, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
echo file_get_contents($logFile);
