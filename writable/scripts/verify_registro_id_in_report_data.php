<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$p = new Config\Paths();
require $p->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($p);

$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$ref = new ReflectionClass($rs);
$m = $ref->getMethod('registroIdFromReportData');
$m->setAccessible(true);

foreach ([288, 298, 302, 308] as $id) {
    $data = $rs->prepareReportData($id, false, false);
    $fromData = (int) $m->invoke($rs, $data);
    $infoId = (int) ($data['register_info']->registro_id ?? 0);
    echo "requested=$id registroIdFromReportData=$fromData register_info->registro_id=$infoId " . ($id === $fromData ? 'OK' : 'MISMATCH') . "\n";
}
