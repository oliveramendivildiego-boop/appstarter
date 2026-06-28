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

$m = new \App\Models\RegisterModel();
$r = $m->orderBy('registro_id', 'DESC')->first();
$id = (int) ($r->registro_id ?? 0);
echo "latest id: {$id}\n";
if ($id < 1) {
    exit(1);
}
$argv[1] = (string) $id;
require __DIR__ . '/probe_pdf_error.php';
