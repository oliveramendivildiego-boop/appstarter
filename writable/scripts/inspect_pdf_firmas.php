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
helper('qr');

$registroId = (int) ($argv[1] ?? 308);
$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

if (preg_match('/<body class="([^"]+)"/', $html, $m)) {
    echo 'body: ' . $m[1] . PHP_EOL;
}
echo 'tail bundles: ' . substr_count($html, 'report-signature-tail-bundle') . PHP_EOL;
echo 'firma inline: ' . substr_count($html, 'report-lab-firma-grupo-inline') . PHP_EOL;
echo 'global block: ' . substr_count($html, 'lab-firmas-pdf-block-global') . PHP_EOL;

// Order: firma after each grupo?
$dom = new DOMDocument();
@$dom->loadHTML($html);
$x = new DOMXPath($dom);
$areas = $x->query("//div[contains(@class,'report-pdf-grupo-prueba')]");
echo 'areas: ' . $areas->length . PHP_EOL;
foreach ($areas as $i => $area) {
    if (! $area instanceof DOMElement) {
        continue;
    }
    $idx = $area->getAttribute('data-layout-area-index');
    $sep = $x->query(".//div[contains(@class,'report-pdf-grupo-area-separator')]", $area)->item(0);
    $title = $sep ? trim($sep->textContent) : '?';
    $firmaInside = $x->query(".//div[contains(@class,'report-lab-firma-grupo-inline')]", $area)->length;
    $firmaAfter = 0;
    $next = $area->nextSibling;
    while ($next) {
        if ($next instanceof DOMElement && str_contains($next->getAttribute('class'), 'report-lab-firma')) {
            $firmaAfter = 1;
            break;
        }
        if ($next instanceof DOMElement && str_contains($next->getAttribute('class'), 'report-pdf-grupo-prueba')) {
            break;
        }
        $next = $next->nextSibling;
    }
    echo "area {$idx} ({$title}): firma inside={$firmaInside} after={$firmaAfter}" . PHP_EOL;
}
