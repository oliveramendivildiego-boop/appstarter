<?php
declare(strict_types=1);
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$p = new Config\Paths();
require $p->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($p);

$id = (int) ($argv[1] ?? 307);
$priaId = (int) ($argv[2] ?? 274);
$rm = new \App\Models\RegisterModel();
$rs = new \App\Services\RegisterService($rm, new \App\Models\AppConfigModel());

$cfg = $rm->getPrianacategoriaConfigByIds([$priaId], true);
echo "=== prianacategoria $priaId ===\n";
print_r($cfg[0] ?? 'NOT FOUND');

$analisis = $rm->getInfoAnalisis($id);
echo "\n=== regvalues matching pria $priaId ===\n";
foreach ($analisis as $row) {
    $name = (string) ($row['name'] ?? '');
    if (preg_match('/_(\d+)$/', $name, $m) && (int) $m[1] === $priaId) {
        echo "  $name = " . ($row['regvalues'] ?? '') . "\n";
    }
    if (preg_match('/^cv(n|u)?_' . $priaId . '\b/', $name) || str_contains($name, "|") && str_starts_with($name, $priaId . '|')) {
        echo "  $name = " . ($row['regvalues'] ?? '') . "\n";
    }
}

$refill = $rm->getInfoRefill($id);
$master = $rm->getInforeport($id);
$patientGender = isset($refill->gender) ? (int) $refill->gender : null;
$matchingPoblacionIds = $rs->getMatchingPoblacionIds($refill->birthday ?? null, $patientGender, $refill->ingreso ?? null);

echo "\npruebas CSV: " . ($refill->pruebas ?? '') . "\n";
echo "matching poblacion: " . implode(',', $matchingPoblacionIds) . "\n";

$gruposRaw = $rs->buildGruposParaReporte($id, $analisis, $matchingPoblacionIds, $patientGender);
echo "\n=== buildGruposParaReporte (before filters) - pria $priaId ===\n";
$found = false;
foreach ($gruposRaw as $padre => $items) {
    foreach ($items as $it) {
        $o = is_array($it) ? (object) $it : $it;
        if ((int) ($o->prianacategoria_id ?? 0) === $priaId) {
            $found = true;
            echo "  FOUND in $padre: hijo=" . ($o->hijo ?? '') . " valor=" . ($o->regvalues ?? '') . "\n";
        }
    }
}
if (! $found) {
    echo "  NOT FOUND in raw grupos\n";
}

$data = $rs->prepareReportData($id, false, false);
echo "\n=== prepareReportData final - pria $priaId ===\n";
$found = false;
foreach ($data['grupos'] ?? [] as $padre => $items) {
    foreach ($items as $it) {
        $o = is_array($it) ? (object) $it : $it;
        if ((int) ($o->prianacategoria_id ?? 0) === $priaId) {
            $found = true;
            echo "  FOUND in $padre: hijo=" . ($o->hijo ?? '') . " valor=" . ($o->regvalues ?? '') . "\n";
        }
    }
}
if (! $found) {
    echo "  NOT FOUND in final grupos\n";
}

$pruebasInfo = $rm->getPruebasInput($refill->pruebas ?? '', $matchingPoblacionIds, $patientGender, true);
echo "\n=== getPruebasInput for pria $priaId ===\n";
foreach ($pruebasInfo as $row) {
    if ((int) ($row['prianacategoria_id'] ?? 0) === $priaId) {
        print_r($row);
    }
}
