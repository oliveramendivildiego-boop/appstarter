<?php
declare(strict_types=1);
/**
 * Prueba rápida de un registro (obligatorio registro + tenant):
 *   php writable/scripts/pdf_probe_registro.php 303 shuelin
 */
if ($argc < 3) {
    fwrite(STDERR, PHP_EOL . 'Uso: php pdf_probe_registro.php REGISTRO_ID TENANT_KEY' . PHP_EOL
        . 'Ejemplo: php pdf_probe_registro.php 303 shuelin' . PHP_EOL . PHP_EOL);
    exit(2);
}

$id = (int) $argv[1];
$tenantKey = trim((string) $argv[2]);
if ($id < 1 || $tenantKey === '') {
    fwrite(STDERR, "Registro y tenant son obligatorios.\n");
    exit(2);
}

$_SERVER['CI_ENVIRONMENT'] = $_SERVER['CI_ENVIRONMENT'] ?? 'production';
define('ENVIRONMENT', $_SERVER['CI_ENVIRONMENT']);
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$resolver = new \App\Libraries\TenantResolver();
$tenantDb = $resolver->resolveDatabaseConfig($tenantKey);
if ($tenantDb === []) {
    fwrite(STDERR, "Tenant \"{$tenantKey}\" no encontrado en tenant_configs.\n");
    exit(1);
}
$resolver->applyResolvedTenantToAppDatabase($tenantKey);
echo "Tenant: {$tenantKey} → BD " . ($tenantDb['database'] ?? '?') . PHP_EOL;

try {
    $rs = new \App\Services\RegisterService();
    $data = $rs->prepareReportData($id);
    if (! is_array($data) || $data === []) {
        fwrite(STDERR, "prepareReportData({$id}) vacío — registro inexistente en esta BD.\n");
        exit(1);
    }
    helper('qr');
    $layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
    $url = $rs->publicReportViewerUrlForQr($id);
    $emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
    $qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
    $pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout, $id);
    if ($pdf === '' || ! str_starts_with($pdf, '%PDF')) {
        fwrite(STDERR, "PDF generado vacío o inválido.\n");
        exit(1);
    }
    echo 'OK — PDF ' . strlen($pdf) . " bytes\n";
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, '  at ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL);
    exit(1);
}
