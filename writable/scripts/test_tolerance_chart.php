<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$svc = new App\Services\ToleranceCurveChartService();
$items = [
    (object) ['nombre' => 'GLUCOSA BASAL', 'regvalues' => '70', 'valor_min' => '60', 'valor_max' => '100', 'umedida' => 'mg/dl', 'opcion_id' => 3, 'orden' => 1, 'es_separador' => 0],
    (object) ['nombre' => 'Glucosa Primera Hora', 'regvalues' => '100', 'valor_min' => '70', 'valor_max' => '180', 'umedida' => 'mg/dl', 'opcion_id' => 3, 'orden' => 2, 'es_separador' => 0],
    (object) ['nombre' => 'Glucosa Segunda Hora', 'regvalues' => '120', 'valor_min' => '70', 'valor_max' => '155', 'umedida' => 'mg/dl', 'opcion_id' => 3, 'orden' => 3, 'es_separador' => 0],
    (object) ['nombre' => 'Glucosa Tercera Hora', 'regvalues' => '125', 'valor_min' => '70', 'valor_max' => '140', 'umedida' => 'mg/dl', 'opcion_id' => 3, 'orden' => 4, 'es_separador' => 0],
];

$chart = $svc->buildFromReportItemsWithModo($items, 46, 2);
echo json_encode($chart, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
