<?php
declare(strict_types=1);
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData(308);
$lab = is_array($data['lab_config'] ?? null) ? $data['lab_config'] : [];
helper('registro');
$logoDataUri = (string) ($data['pdf_logo_data_uri'] ?? '');
$src = $logoDataUri !== '' ? $logoDataUri : report_image_src_for_variant((string) ($lab['logo'] ?? ''), 'pdf');
$row = \App\Services\ReportPdfLayoutService::logoMaxHeightPxFromTextStyle([]);
$h = \App\Services\ReportPdfLayoutService::logoIntrinsicRenderedHeightPx([], 2, 5, $src, null);
echo 'intrinsicH=' . $h . ' rowMax=' . $row . PHP_EOL;
echo 'spacer=' . \App\Services\ReportPdfLayoutService::pdfValignSpacerHeightPx($row, $h, 'middle') . PHP_EOL;
