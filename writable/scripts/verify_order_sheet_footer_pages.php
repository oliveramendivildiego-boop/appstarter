<?php
declare(strict_types=1);
/**
 * Verifica Paciente/No. Orden en grilla del pie solo desde hoja 2.
 * Hoja 1: footer con Direccion pero sin fila order-sheet en el pie.
 */
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

$id = (int) ($argv[1] ?? 308);
$rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($id);
helper('qr');
$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$url = $rs->publicReportViewerUrlForQr($id);
$emitido = $rs->lockReportEmitidoEnForPrintOrPdf($id);
$qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));

$pdf = $rs->generateReportPdfBinary($data, $url, $qr, $emitido, $layout);
$out = WRITEPATH . 'cache/order_sheet_footer_test_' . $id . '.pdf';
file_put_contents($out, $pdf);

$py = WRITEPATH . 'cache/_verify_osh.py';
file_put_contents($py, <<<'PY'
import sys
from pypdf import PdfReader

def footer_order_row(t: str) -> bool:
    """Fila del pie: Paciente y No. Orden en la misma línea (grilla order-sheet)."""
    for line in t.splitlines():
        s = line.strip()
        if 'Paciente:' in s and ('No. Orden' in s or 'No Orden' in s):
            return True
    return False

r = PdfReader(sys.argv[1])
ok = True
for i, p in enumerate(r.pages, 1):
    t = p.extract_text() or ''
    has_row = footer_order_row(t)
    has_dir = 'Direccion' in t
    expect_row = i > 1
    status = 'OK' if has_row == expect_row else 'FAIL'
    if status == 'FAIL':
        ok = False
    print(f"page {i}: footer_order_row={has_row} Direccion={has_dir} expected_row={expect_row} [{status}]")
sys.exit(0 if ok else 1)
PY);
$code = 0;
passthru('python ' . escapeshellarg($py) . ' ' . escapeshellarg($out), $code);
exit($code);
