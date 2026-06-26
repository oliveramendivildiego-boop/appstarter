<?php

/**
 * Mide tiempos de las fases de guardado de resultados (sin HTTP).
 * Uso: php writable/scripts/benchmark_saveregvalues.php [registro_id]
 */

define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/') . '/Boot.php';

CodeIgniter\Boot::bootSpark($paths);

$registroId = (int) ($argv[1] ?? 308);
if ($registroId < 1) {
    fwrite(STDERR, "registro_id inválido\n");
    exit(1);
}

$registerModel = new \App\Models\RegisterModel();
$registerService = new \App\Services\RegisterService();

$analisis = $registerModel->getInfoAnalisis($registroId);
$data = [];
foreach ($analisis as $row) {
    $name = trim((string) ($row['name'] ?? ''));
    if ($name === '') {
        continue;
    }
    $data[] = [
        'id' => $name,
        'valor' => (string) ($row['regvalues'] ?? '1'),
        'registro_id' => $registroId,
    ];
}

$steps = [];

$t = microtime(true);
$registroRow = $registerModel->getInfoRefill($registroId);
$pruebasIds = $registerService->extractPrianacategoriaIdsFromRegistroPruebas((string) ($registroRow->pruebas ?? ''));
$retiredIds = $registerModel->getRetiredPrianacategoriaIds($pruebasIds);
$valoresAnteriores = [];
foreach ($registerModel->getInfoAnalisis($registroId) as $rowPrev) {
    $clave = trim((string) ($rowPrev['name'] ?? ''));
    if ($clave !== '') {
        $valoresAnteriores[$clave] = trim((string) ($rowPrev['regvalues'] ?? ''));
    }
}
$steps['prefetch'] = microtime(true) - $t;

$t = microtime(true);
$registerModel->saveRegistro(['comentario_resultado' => 'bench'], $registroId);
$registerModel->deleteRegvaluesByRegistroId($registroId);
$steps['delete'] = microtime(true) - $t;

$t = microtime(true);
foreach ($data as $item) {
    $registerModel->saveRegvalues([
        'regvalues' => $item['valor'],
        'registro_id' => $registroId,
        'name' => $item['id'],
        'id_session' => 1,
    ]);
}
$steps['insert_' . count($data)] = microtime(true) - $t;

$t = microtime(true);
$auto = (new \App\Services\AutoReactivoConsumptionService())->applyFromRegValues($registroId, $data, 1);
$steps['auto_reactivo'] = microtime(true) - $t;

$t = microtime(true);
(new \App\Services\DeliveryNotificationService())->syncForRegistro($registroId);
$steps['delivery_sync'] = microtime(true) - $t;

$t = microtime(true);
$registerService->clearReportDataCache($registroId);
$registerService->clearReportPdfPreviewCache($registroId);
$steps['clear_cache'] = microtime(true) - $t;

$t = microtime(true);
(new \App\Services\Report\ReportPipelineService($registerService))->warmSync($registroId);
$steps['warm_sync'] = microtime(true) - $t;

$t = microtime(true);
$registerService->prepareReportData($registroId, false, true);
$steps['prepare_report_data'] = microtime(true) - $t;

echo "Registro #{$registroId} — " . count($data) . " valores\n";
foreach ($steps as $name => $sec) {
    printf("  %-22s %7.0f ms\n", $name . ':', $sec * 1000);
}
printf("  %-22s %7.0f ms\n", 'TOTAL (sin audit):', array_sum($steps) * 1000);
