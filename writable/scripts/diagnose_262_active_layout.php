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

use App\Services\RegisterService;
use App\Services\ReportPdfLayoutService;
use App\Services\ReportLayout\ReportPaginationMode;

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData(262);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
$printLayout = (new ReportPdfLayoutService())->getPrintLayoutForRender();
$bindings = (new ReportPdfLayoutService())->getResultTemplateBindingsForReport();
$mode = ReportPdfLayoutService::resolvePaginationModeFromLayout($layout);
$printMode = ReportPdfLayoutService::resolvePaginationModeFromLayout($printLayout);
echo 'PDF template: ' . ($bindings['pdf']['name'] ?? '?') . ' id=' . ($bindings['pdf']['resolved_template_id'] ?? '?') . PHP_EOL;
echo 'Print template: ' . ($bindings['print']['name'] ?? '?') . ' id=' . ($bindings['print']['resolved_template_id'] ?? '?') . PHP_EOL;
echo 'PDF pagination_mode=' . $mode . PHP_EOL;
echo 'Print pagination_mode=' . $printMode . PHP_EOL;

$html = $rs->renderReportPdfHtml($data, 'http://x', 'x', '01/01/2025', $printLayout);
echo "--- using PRINT template layout ---" . PHP_EOL;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$d = new Dompdf\Dompdf(['isRemoteEnabled' => true]);
$d->setPaper('letter', 'portrait');
$d->loadHtml($html, 'UTF-8');
$d->render();
echo 'pages=' . $d->getCanvas()->get_page_count() . PHP_EOL;

$dom = new DOMDocument();
@$dom->loadHTML($html);
$x = new DOMXPath($dom);
$tg = null;
foreach ($x->query("//div[contains(@class,'report-signature-tail-group')]") as $node) {
    if (str_contains($node->textContent, 'MICROSCOPICO')) {
        $tg = $node;
        break;
    }
}
if ($tg === null) {
    echo "ORINA tail group NOT FOUND - mode may not be flow_no_lone_signature or no tail split\n";
    echo 'tail groups=' . $x->query("//div[contains(@class,'report-signature-tail-group')]")->length . PHP_EOL;
    exit(0);
}
$shell = $x->query('.//table[contains(@class,"report-signature-tail-shell")]', $tg)->length;
$firmaInShell = $x->query('.//table[contains(@class,"report-signature-tail-shell")]//div[contains(@class,"report-lab-firma-grupo-inline")]', $tg)->length;
$firmaInSameTd = 0;
$tds = $x->query('.//table[contains(@class,"report-signature-tail-shell")]//td', $tg);
foreach ($tds as $td) {
    if ($x->query('.//div[contains(@class,"report-lab-firma-grupo-inline")]', $td)->length > 0
        && str_contains($td->textContent, 'MICROSCOPICO')) {
        $firmaInSameTd++;
    }
}
echo "shell={$shell} firmaInShell={$firmaInShell} firmaSameTdAsSediment={$firmaInSameTd}\n";
