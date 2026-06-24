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
$log = [];

$svc = new class extends \App\Libraries\PdfService {
    /** @var list<string> */
    public static array $log = [];

    protected function paintPaginationOnPage($canvas, $fontMetrics, array $slot, int $pageNumber, int $pageCount): void
    {
        self::$log[] = 'paint page ' . $pageNumber . '/' . $pageCount
            . ' get_cpdf=' . (method_exists($canvas, 'get_cpdf') ? 'yes' : 'no')
            . ' canvas=' . (is_object($canvas) ? $canvas::class : 'null');
        try {
            parent::paintPaginationOnPage($canvas, $fontMetrics, $slot, $pageNumber, $pageCount);
            self::$log[] = 'paint ok page ' . $pageNumber;
        } catch (\Throwable $e) {
            self::$log[] = 'paint ERR page ' . $pageNumber . ': ' . $e->getMessage();
        }
    }
};

$pdf = $svc->generate($html, 'test.pdf');
file_put_contents(dirname(__DIR__) . '/debug/pagination_instrumented.pdf', $pdf);
file_put_contents(dirname(__DIR__) . '/debug/pagination_instrumented_log.txt', implode(PHP_EOL, $svc::$log));
echo implode(PHP_EOL, $svc::$log);
