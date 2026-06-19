<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

// Bootstrap mínimo para probar lógica sin BD completa
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$svc = new \App\Services\CategoricalSerialComparisonService();

$trendCases = [
    ['Ausente', 'Ausente', 'Presente', '↑'],
    ['Ausente', 'Ausente', 'Ausente', '='],
    ['Escasos', 'Moderados', 'Abundantes', '↑'],
    ['Abundantes', 'Moderados', 'Escasos', '↓'],
    ['Giardia', 'Giardia', 'Ausente', '↓'],
    ['Positivo', 'Positivo', 'Negativo', '↓'],
    ['Marron', 'Amarillo', 'Verde', '↔'],
];

$ok = 0;
$fail = 0;
foreach ($trendCases as [$m1, $m2, $m3, $expected]) {
    $result = $svc->computeTrend(['M1' => $m1, 'M2' => $m2, 'M3' => $m3]);
    $symbol = $result['symbol'] ?? '';
    if ($symbol === $expected) {
        $ok++;
        echo "OK trend {$m1} / {$m2} / {$m3} => {$symbol}\n";
    } else {
        $fail++;
        echo "FAIL trend {$m1} / {$m2} / {$m3} => {$symbol} (expected {$expected})\n";
    }
}

$badgeCases = [
    ['Ausente', 'catcmp-badge--neutral'],
    ['Escasos', 'catcmp-badge--escaso'],
    ['Moderados', 'catcmp-badge--moderado'],
    ['Abundantes', 'catcmp-badge--abundante'],
    ['Presente', 'catcmp-badge--presente'],
    ['Giardia', 'catcmp-badge--descriptivo'],
];

foreach ($badgeCases as [$value, $expectedClass]) {
    $badge = $svc->resolveBadge($value);
    $class = $badge['class'] ?? '';
    if ($class === $expectedClass) {
        $ok++;
        echo "OK badge {$value} => {$class}\n";
    } else {
        $fail++;
        echo "FAIL badge {$value} => {$class} (expected {$expectedClass})\n";
    }
}

// Prueba con ítems sintéticos (estructura parasitológico seriado)
$items = [
    (object) ['es_separador' => 1, 'nombre' => '1RA MUESTRA - EXAMEN MACROSCOPICO'],
    (object) ['es_separador' => 0, 'nombre' => 'MOCO', 'opcion_id' => 37, 'regvalues' => 'Ausente'],
    (object) ['es_separador' => 0, 'nombre' => 'SANGRE', 'opcion_id' => 37, 'regvalues' => 'Ausente'],
    (object) ['es_separador' => 1, 'nombre' => '1RA MUESTRA - OTROS ELEMENTOS'],
    (object) ['es_separador' => 0, 'nombre' => 'LEUCOCITOS', 'opcion_id' => 46, 'regvalues' => 'Escasos'],
    (object) ['es_separador' => 1, 'nombre' => '1RA MUESTRA - EXAMEN MICROSCOPICO PARASITOS'],
    (object) ['es_separador' => 0, 'nombre' => 'QUISTES', 'opcion_id' => 38, 'regvalues' => 'Giardia'],
    (object) ['es_separador' => 1, 'nombre' => '2RA MUESTRA - EXAMEN MACROSCOPICO'],
    (object) ['es_separador' => 0, 'nombre' => 'MOCO', 'opcion_id' => 37, 'regvalues' => 'Ausente'],
    (object) ['es_separador' => 0, 'nombre' => 'SANGRE', 'opcion_id' => 37, 'regvalues' => 'Ausente'],
    (object) ['es_separador' => 1, 'nombre' => '2RA MUESTRA - OTROS ELEMENTOS'],
    (object) ['es_separador' => 0, 'nombre' => 'LEUCOCITOS', 'opcion_id' => 46, 'regvalues' => 'Moderados'],
    (object) ['es_separador' => 1, 'nombre' => '2RA MUESTRA - EXAMEN MICROSCOPICO PARASITOS'],
    (object) ['es_separador' => 0, 'nombre' => 'QUISTES', 'opcion_id' => 38, 'regvalues' => 'Giardia'],
    (object) ['es_separador' => 1, 'nombre' => '3RA MUESTRA - EXAMEN MACROSCOPICO'],
    (object) ['es_separador' => 0, 'nombre' => 'MOCO', 'opcion_id' => 37, 'regvalues' => 'Presente'],
    (object) ['es_separador' => 0, 'nombre' => 'SANGRE', 'opcion_id' => 37, 'regvalues' => 'Ausente'],
    (object) ['es_separador' => 1, 'nombre' => '3RA MUESTRA - OTROS ELEMENTOS'],
    (object) ['es_separador' => 0, 'nombre' => 'LEUCOCITOS', 'opcion_id' => 46, 'regvalues' => 'Abundantes'],
    (object) ['es_separador' => 1, 'nombre' => '3RA MUESTRA - EXAMEN MICROSCOPICO PARASITOS'],
    (object) ['es_separador' => 0, 'nombre' => 'QUISTES', 'opcion_id' => 38, 'regvalues' => 'Ausente'],
];

// Mock graficar=1 vía stub no trivial; probamos parse interno con reflexión no necesaria.
// buildFromReportItems requiere graficar en BD — activamos manualmente en prianacategoria 6 si existe.
$db = Config\Database::connect();
$table = $db->prefixTable('prianacategoria');
if ($db->fieldExists('graficar', $table)) {
    $db->table('prianacategoria')->where('prianacategoria_id', 6)->update(['graficar' => 1]);
}
$built = $svc->buildFromReportItems($items, 6);
if (is_array($built) && ! empty($built['sections'])) {
    $ok++;
    echo "OK buildFromReportItems sections=" . count($built['sections']) . "\n";
    foreach ($built['sections'] as $sec) {
        echo '  - ' . ($sec['title'] ?? '') . ' rows=' . count($sec['rows'] ?? []) . "\n";
    }
} else {
    $fail++;
    echo "FAIL buildFromReportItems returned null/empty\n";
}

echo "\nResumen: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);
