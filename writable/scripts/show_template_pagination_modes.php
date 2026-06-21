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

use App\Models\ReportPdfTemplateModel;
use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportPdfLayoutService;

$model = model(ReportPdfTemplateModel::class);
foreach ([4 => 'Quantum PDF', 5 => 'Quantum Printer'] as $id => $label) {
    $row = $model->find($id);
    if (! $row) {
        echo "template {$id} not found\n";
        continue;
    }
    $layout = (new ReportPdfLayoutService())->layoutJsonForEditor($row);
    $mode = ReportPdfLayoutService::resolvePaginationModeFromLayout($layout);
    echo "{$label} (id={$id}): pagination_mode={$mode}\n";
}
