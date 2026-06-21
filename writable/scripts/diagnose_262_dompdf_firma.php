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
$outHtml = dirname(__DIR__) . '/debug/check_262_pdf_render.html';
file_put_contents($outHtml, $html);

$d = new Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
$d->setPaper('letter', 'portrait');
$d->loadHtml($html, 'UTF-8');
$d->render();
file_put_contents(dirname(__DIR__) . '/debug/262_mode2_test.pdf', $d->output());

$pageH = $d->getCanvas()->get_height();
echo 'pages=' . $d->getCanvas()->get_page_count() . ' pageH=' . $pageH . PHP_EOL;

// Buscar posiciones via reflexión en frames de texto
$root = $d->getTree()->get_root();
$found = [];

$walk = static function ($frame) use (&$walk, &$found, $pageH): void {
    if ($frame === null) {
        return;
    }
    try {
        $node = $frame->get_node();
        if ($node instanceof DOMText) {
            $t = trim(preg_replace('/\s+/', ' ', $node->textContent ?? ''));
            foreach (['MICROSCOPICO DEL SEDIMENTO', 'Bacterias', 'ATENTAMENTE', 'Ximena Ortega'] as $needle) {
                if ($t !== '' && str_contains($t, $needle)) {
                    $dec = $frame->get_decorator();
                    if ($dec !== null && method_exists($dec, 'get_padding_box')) {
                        $box = $dec->get_padding_box();
                        $y = (float) ($box['y'] ?? 0);
                        $h = (float) ($box['h'] ?? 0);
                        if ($y > 0 || $h > 0) {
                            $page = (int) floor($y / $pageH) + 1;
                            $found[$needle] = ['page' => $page, 'y' => $y - ($page - 1) * $pageH, 'h' => $h, 'text' => substr($t, 0, 40)];
                        }
                    }
                }
            }
        }
    } catch (Throwable) {
    }
    for ($child = $frame->get_first_child(); $child; $child = $child->get_next_sibling()) {
        $walk($child);
    }
};

// Dompdf 3: el árbol puede estar bajo html > body
for ($child = $root->get_first_child(); $child; $child = $child->get_next_sibling()) {
    $walk($child);
}

foreach ($found as $k => $v) {
    echo sprintf("%s => page %d y=%.0f h=%.0f (%s)\n", $k, $v['page'], $v['y'], $v['h'], $v['text']);
}

// DOM: ¿firma dentro de tail group en ORINA (area 2)?
$dom = new DOMDocument();
@$dom->loadHTML($html);
$x = new DOMXPath($dom);
$tails = $x->query("//div[contains(@class,'report-signature-tail-group')]");
echo 'tail groups in dom=' . $tails->length . PHP_EOL;
for ($i = 0; $i < $tails->length; $i++) {
    $tg = $tails->item($i);
    if (! str_contains($tg->textContent, 'MICROSCOPICO')) {
        continue;
    }
    $firmaInside = $x->query(".//div[contains(@class,'report-lab-firma-grupo-inline')]", $tg)->length;
    $leaderBefore = $tg->previousSibling;
    $leaderClass = ($leaderBefore instanceof DOMElement) ? $leaderBefore->getAttribute('class') : '';
    echo "ORINA tail: firmaInside={$firmaInside} prevClass={$leaderClass}\n";
}
