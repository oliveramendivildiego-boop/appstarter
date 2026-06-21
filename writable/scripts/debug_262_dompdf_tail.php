<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Services\RegisterService;
use App\Services\ReportPdfLayoutService;
use App\Services\ReportLayout\ReportPaginationMode;

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData(262);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$layout['page_style']['pagination_mode'] = ReportPaginationMode::FLOW_NO_LONE_SIGNATURE;
$html = $rs->renderReportPdfHtml($data, 'http://x', 'x', '01/01/2025', $layout);

$o = new Dompdf\Options();
$o->set('isRemoteEnabled', true);
$o->set('isHtml5ParserEnabled', true);
$d = new Dompdf\Dompdf($o);
$d->setPaper('letter', 'portrait');
$d->loadHtml($html, 'UTF-8');
$d->render();

$pageH = $d->getCanvas()->get_height();
$pageCount = $d->getCanvas()->get_page_count();
echo "pages={$pageCount} pageH={$pageH}\n";

$root = $d->getTree()->get_root();
$needles = ['report-signature-tail-group', 'report-lab-firma-grupo-inline', 'report-segment-title'];

foreach (new Dompdf\Frame\FrameTreeIterator($root) as $frame) {
    $node = $frame->get_node();
    if (! $node instanceof DOMElement) {
        continue;
    }
    $cls = $node->getAttribute('class');
    $text = trim(preg_replace('/\s+/', ' ', $node->textContent ?? ''));
    $hit = false;
    foreach ($needles as $n) {
        if ($cls !== '' && str_contains($cls, $n)) {
            $hit = true;
            break;
        }
    }
    if (! $hit && str_contains($text, 'EXAMEN MICROSCOPICO DEL SEDIMENTO')) {
        $hit = true;
        $cls = 'text:MICROSCOPICO';
    }
    if (! $hit && str_contains($text, 'ATENTAMENTE') && str_contains($cls, 'lab-firmas')) {
        $hit = true;
    }
    if (! $hit) {
        continue;
    }
    $dec = $frame->get_decorator() ?? $frame;
    $box = $dec->get_border_box();
    $y = (float) ($box['y'] ?? 0);
    $h = (float) ($box['h'] ?? 0);
    $page = (int) floor($y / $pageH) + 1;
    $yOnPage = $y - ($page - 1) * $pageH;
    $snippet = substr($text, 0, 80);
    echo sprintf("[%s] page=%d y=%.0f h=%.0f end=%.0f | %s\n", $cls ?: $node->nodeName, $page, $yOnPage, $h, $yOnPage + $h, $snippet);
}

// Last page: all firma blocks
echo "\n--- firmas por pagina ---\n";
foreach (new Dompdf\Frame\FrameTreeIterator($root) as $frame) {
    $node = $frame->get_node();
    if (! $node instanceof DOMElement || ! str_contains($node->getAttribute('class'), 'report-lab-firma-grupo-inline')) {
        continue;
    }
    $dec = $frame->get_decorator() ?? $frame;
    $box = $dec->get_border_box();
    $y = (float) ($box['y'] ?? 0);
    $h = (float) ($box['h'] ?? 0);
    $page = (int) floor($y / $pageH) + 1;
    $yOnPage = $y - ($page - 1) * $pageH;
    $area = '';
    $p = $node->parentNode;
    while ($p instanceof DOMElement) {
        if ($p->hasAttribute('data-layout-area-index')) {
            $area = 'area=' . $p->getAttribute('data-layout-area-index');
            break;
        }
        $p = $p->parentNode;
    }
    echo sprintf("firma page=%d y=%.0f h=%.0f end=%.0f %s\n", $page, $yOnPage, $h, $yOnPage + $h, $area);
}
