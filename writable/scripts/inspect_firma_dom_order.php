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

$rs = new App\Services\RegisterService(new App\Models\RegisterModel(), new App\Models\AppConfigModel());
$data = $rs->prepareReportData(308);
$html = $rs->renderReportPdfHtml($data, 'http://x', qr_base64('http://x', 120));

$dom = new DOMDocument();
@$dom->loadHTML($html);
$x = new DOMXPath($dom);

foreach ($x->query("//div[contains(@class,'report-pdf-grupo-prueba')]") as $area) {
    if (! $area instanceof DOMElement) {
        continue;
    }
    $idx = $area->getAttribute('data-layout-area-index');
    $sep = $x->query(".//div[contains(@class,'report-pdf-grupo-area-separator')]", $area)->item(0);
    $title = $sep ? trim(preg_replace('/\s+/', ' ', $sep->textContent)) : '?';

    $nodes = [];
    $walker = function (DOMNode $node) use (&$walker, &$nodes): void {
        if ($node instanceof DOMElement) {
            $cls = $node->getAttribute('class');
            if (str_contains($cls, 'report-lab-firma-grupo-inline')) {
                $nodes[] = 'FIRMA@' . ($node->previousSibling ? 'after-sibling' : 'start');
            } elseif (str_contains($cls, 'report-segment-table-wrap') || ($node->tagName === 'table' && str_contains($cls, 'results'))) {
                $seg = $x->query('.//div[contains(@class,"report-segment-title")]', $node)->item(0);
                $label = $seg ? trim($seg->textContent) : 'table';
                $nodes[] = 'TABLE:' . mb_substr($label, 0, 40);
            } elseif (str_contains($cls, 'report-signature-tail-bundle')) {
                $nodes[] = 'TAIL-BUNDLE-OPEN';
            }
        }
        foreach ($node->childNodes as $ch) {
            $walker($ch);
        }
    };
    $walker($area);

    echo "=== area $idx ($title) ===" . PHP_EOL;
    foreach ($nodes as $n) {
        echo "  $n" . PHP_EOL;
    }
    $firmaText = $x->query(".//div[contains(@class,'report-lab-firma-grupo-inline')]//text()[normalize-space()]", $area);
    $sample = '';
    foreach ($firmaText as $t) {
        $s = trim($t->textContent);
        if ($s !== '' && mb_strlen($s) > 2) {
            $sample = $s;
            break;
        }
    }
    echo "  firma sample text: " . ($sample ?: '(empty)') . PHP_EOL;
}
