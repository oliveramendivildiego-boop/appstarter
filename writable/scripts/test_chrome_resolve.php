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

echo 'PROGRAMFILES=' . var_export(getenv('PROGRAMFILES'), true) . PHP_EOL;
echo 'PROGRAMFILES(X86)=' . var_export(getenv('PROGRAMFILES(X86)'), true) . PHP_EOL;
echo 'LOCALAPPDATA=' . var_export(getenv('LOCALAPPDATA'), true) . PHP_EOL;
echo 'CHROME_EXECUTABLE_PATH=' . var_export(getenv('CHROME_EXECUTABLE_PATH'), true) . PHP_EOL;
echo 'config executablePath=' . var_export(config('Pdf')->executablePath ?? '', true) . PHP_EOL;

$candidates = [
    getenv('PROGRAMFILES') . '\\Google\\Chrome\\Application\\chrome.exe',
    getenv('PROGRAMFILES(X86)') . '\\Google\\Chrome\\Application\\chrome.exe',
    getenv('LOCALAPPDATA') . '\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files\\Chromium\\Application\\chrome.exe',
];
foreach ($candidates as $path) {
    $ok = @is_file($path);
    echo ($ok ? 'OK ' : 'NO ') . $path . PHP_EOL;
}

try {
    $r = new ReflectionClass(\App\Libraries\Pdf\ChromiumPdfRenderer::class);
    $m = $r->getMethod('resolveExecutable');
    $m->setAccessible(true);
    echo 'open_basedir=' . var_export(ini_get('open_basedir'), true) . PHP_EOL;
    echo 'resolve: ' . $m->invoke(new \App\Libraries\Pdf\ChromiumPdfRenderer()) . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . PHP_EOL;
}
