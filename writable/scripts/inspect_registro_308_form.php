<?php

declare(strict_types=1);

define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$_SERVER['CI_ENVIRONMENT'] = 'development';
$pathsConfig = dirname(__DIR__, 2) . '/app/Config/Paths.php';
require $pathsConfig;
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/') . '/Boot.php';

// Minimal CI bootstrap for models
$app = CodeIgniter\Config\Services::codeigniter();
$app->initialize();

$rid = (int) ($argv[1] ?? 308);
$registerModel = model(\App\Models\RegisterModel::class);
$reg = $registerModel->getRegisterInfo($rid);
if (!$reg) {
    echo "No registro\n";
    exit(1);
}

$matching = (new \App\Services\RegisterService())->matchingPoblacionIdsForRegister($reg);
$gender = isset($reg->gender) ? (int) $reg->gender : null;

// Find hemograma prianacategoria
$db = \Config\Database::connect();
$rows = $db->query("SELECT prianacategoria_id, name FROM dom_prianacategoria WHERE name LIKE '%HEMOGRAMA%' AND deleted = 0")->getResultArray();
foreach ($rows as $p) {
    $pid = (int) $p['prianacategoria_id'];
    $valores = $registerModel->getValoresComplejaSiempre($pid, $matching, $gender);
    echo "Prueba {$p['name']} ({$pid}): " . count($valores) . " subfilas\n";
    foreach ($valores as $v) {
        $nom = (string) ($v['nombre'] ?? '');
        if (stripos($nom, 'baso') !== false || stripos($nom, 'cayad') !== false || stripos($nom, 'vsg') !== false) {
            echo '  ' . json_encode([
                'id' => 'c_' . ($v['secanacategoria_id'] ?? ''),
                'nombre' => $nom,
                'es_separador' => $v['es_separador'] ?? 0,
                'paciente_id' => $v['paciente_id'] ?? null,
                'sexo' => $v['sexo'] ?? null,
            ], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}
