<?php
declare(strict_types=1);

/**
 * Diagnóstico PDF/Chromium en el servidor. Ejecutar: php writable/scripts/pdf_chrome_doctor.php
 */
$_SERVER['CI_ENVIRONMENT'] = $_SERVER['CI_ENVIRONMENT'] ?? 'development';
define('ENVIRONMENT', $_SERVER['CI_ENVIRONMENT']);
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

echo 'PHP_OS_FAMILY=' . PHP_OS_FAMILY . PHP_EOL;
echo 'CI_ENVIRONMENT=' . ENVIRONMENT . PHP_EOL;
echo 'open_basedir=' . (ini_get('open_basedir') ?: '(no)') . PHP_EOL;

$fromEnv = Config\Pdf::readChromeExecutableFromEnvironment();
$default = Config\Pdf::defaultChromeExecutableForPlatform();
$config  = (string) (config('Pdf')->executablePath ?? '');

echo 'env CHROME_EXECUTABLE_PATH=' . ($fromEnv !== '' ? $fromEnv : '(vacío)') . PHP_EOL;
echo 'config Pdf.executablePath=' . ($config !== '' ? $config : '(vacío)') . PHP_EOL;
echo 'default plataforma=' . ($default !== '' ? $default : '(vacío)') . PHP_EOL;

try {
    $r   = new ReflectionClass(\App\Libraries\Pdf\ChromiumPdfRenderer::class);
    $m   = $r->getMethod('resolveExecutable');
    $m->setAccessible(true);
    $exe = $m->invoke(new \App\Libraries\Pdf\ChromiumPdfRenderer());
    echo 'resolveExecutable OK: ' . $exe . PHP_EOL;
} catch (Throwable $e) {
    echo 'resolveExecutable ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Listo. Si resolveExecutable OK pero el PDF falla, revise proc_open y permisos de writable/cache.' . PHP_EOL;
