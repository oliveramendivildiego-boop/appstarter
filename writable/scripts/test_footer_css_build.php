<?php
declare(strict_types=1);
$_SERVER['CI_ENVIRONMENT'] = 'production';
define('ENVIRONMENT', 'production');
define('FCPATH', dirname(__DIR__, 2) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

set_error_handler(static function (int $s, string $m, string $f, int $l): bool {
    throw new ErrorException($m, 0, $s, $f, $l);
});

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
// Force footer column border like shuelin might have
$layout['page_style']['footer_grid']['column_border_width_px'] = 2;
$css = \App\Libraries\Pdf\MpdfFooterStyles::buildFooterCssRules($layout);
echo 'OK css len ' . strlen($css) . "\n";
