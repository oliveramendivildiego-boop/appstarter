<?php
declare(strict_types=1);
putenv('CI_ENVIRONMENT=development');
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

helper('qr');
$hg = \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle(['qr_size_percent' => 150]);
$px = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout(['page_style' => ['header_grid' => $hg]]);
$uri = qr_base64('https://example.test/report/preview', $px);
echo 'px=' . $px . ' len=' . strlen($uri) . PHP_EOL;
