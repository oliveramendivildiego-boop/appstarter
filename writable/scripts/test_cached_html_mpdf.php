<?php
declare(strict_types=1);

putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 308);
$htmlPath = WRITEPATH . 'cache/report_pdf_html/registro_' . $id . '.html';
if (! is_file($htmlPath)) {
    fwrite(STDERR, "No HTML cache for {$id}\n");
    exit(1);
}
$html = (string) file_get_contents($htmlPath);
$opts = \App\Libraries\Pdf\PdfOptions::fromLegacyPageSize(['key' => 'letter']);
try {
    $bin = (new \App\Libraries\Pdf\MpdfPdfRenderer())->renderHtml($html, $opts);
    $out = WRITEPATH . 'cache/debug_mpdf_from_html_' . $id . '.pdf';
    file_put_contents($out, $bin);
    echo "OK bytes=" . strlen($bin) . " saved={$out}\n";
    if (preg_match_all('/\/Type\s*\/Page\b/', $bin, $m)) {
        echo 'pages~=' . count($m[0]) . "\n";
    }
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(2);
}
