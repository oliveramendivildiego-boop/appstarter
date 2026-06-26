<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
$_SERVER['PDF_RENDERER'] = 'mpdf';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

$footerHtml = file_get_contents(WRITEPATH . 'debug/mpdf_footer_final_308.html');
if ($footerHtml === false) {
    fwrite(STDERR, "Run debug_mpdf_footer_visibility.php first\n");
    exit(1);
}

foreach ([
    'wrong' => ['margin_bottom' => 2, 'margin_footer' => 22],
    'fixed' => ['margin_bottom' => 24, 'margin_footer' => 22],
] as $label => $m) {
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'Letter',
        'margin_left' => 25,
        'margin_right' => 10,
        'margin_top' => 5,
        'margin_bottom' => $m['margin_bottom'],
        'margin_footer' => $m['margin_footer'],
        'default_font' => 'dejavusans',
    ]);
    $mpdf->SetHTMLFooter($footerHtml);
    $mpdf->WriteHTML('<body style="font-family:dejavusans"><p>Pagina de prueba con mucho texto.</p><p>Linea 2</p></body>');
    $out = WRITEPATH . 'debug/mpdf_margin_test_' . $label . '.pdf';
    file_put_contents($out, $mpdf->Output('', Destination::STRING_RETURN));
    echo "$label: $out (" . $m['margin_bottom'] . '/' . $m['margin_footer'] . ")\n";
}
