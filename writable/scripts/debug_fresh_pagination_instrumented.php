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

$html = (string) file_get_contents(dirname(__DIR__) . '/debug/report_262_fresh.html');

$svc = new class extends \App\Libraries\PdfService {
    /** @var list<string> */
    public static array $log = [];

    protected function paintPaginationOnPage($canvas, $fontMetrics, array $slot, int $pageNumber, int $pageCount): void
    {
        self::$log[] = 'paint page ' . $pageNumber . '/' . $pageCount
            . ' gridCol=' . ($slot['gridColumn'] ?? '?')
            . ' align=' . ($slot['align'] ?? '?');
        parent::paintPaginationOnPage($canvas, $fontMetrics, $slot, $pageNumber, $pageCount);
        self::$log[] = 'paint ok page ' . $pageNumber;
    }
};

$pdf = $svc->generate($html, 'test.pdf');
file_put_contents(dirname(__DIR__) . '/debug/pagination_fresh_instrumented.pdf', $pdf);
echo implode(PHP_EOL, $svc::$log) . PHP_EOL;
echo 'pdf size: ' . strlen($pdf) . PHP_EOL;

if (preg_match_all('/\[\(([^\)]{1,80})\)\]/', $pdf, $m)) {
    foreach ($m[1] as $s) {
        if (str_contains($s, 'de') || str_contains($s, 'P') || str_contains($s, 'gina')) {
            echo 'Tj: ' . $s . PHP_EOL;
        }
    }
}
