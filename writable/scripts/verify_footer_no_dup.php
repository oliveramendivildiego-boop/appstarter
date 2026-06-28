<?php
declare(strict_types=1);
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$id = (int) ($argv[1] ?? 143);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
if ($data === null) {
    fwrite(STDERR, "No data for register {$id}\n");
    exit(1);
}
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));

$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout, $id);
$parser = new Smalot\PdfParser\Parser();
$text = $parser->parseContent($pdf)->getText();

echo "register={$id} revision=" . \App\Libraries\Pdf\HtmlMpdfAdapter::CACHE_REVISION . PHP_EOL;
$keys = ['Paciente:', 'No. Orden:', 'Direccion:', 'Correo:', 'Telefonos:', 'Página'];
$failed = false;
foreach ($keys as $k) {
    $c = substr_count($text, $k);
    echo "{$k} count={$c}" . ($c > 1 ? ' DUPLICATE' : ' OK') . PHP_EOL;
    if ($c > 1 && in_array($k, ['Paciente:', 'No. Orden:', 'Direccion:', 'Correo:', 'Telefonos:'], true)) {
        $failed = true;
    }
}

$out = WRITEPATH . 'debug/pdf_text_tail_' . $id . '.txt';
file_put_contents($out, $text);
echo 'text tail (last 1500 chars):' . PHP_EOL . substr($text, -1500) . PHP_EOL;
echo "full text saved: {$out}" . PHP_EOL;

exit($failed ? 1 : 0);
