<?php
declare(strict_types=1);
/**
 * Audita que las clases del LayoutPlan coincidan con el HTML renderizado (PDF).
 * Uso: php writable/scripts/audit_plan_vs_html.php [registro_id] [mode]
 */
$registroId = (int) ($argv[1] ?? 253);
$modeArg = $argv[2] ?? '';
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\RegisterService;
use App\Services\ReportPdfLayoutService;

$rs = new RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
$data = $rs->prepareReportData($registroId);
$layout = (new ReportPdfLayoutService())->getActiveLayoutForRender();
if ($modeArg !== '') {
    $layout['page_style']['pagination_mode'] = match ($modeArg) {
        'mode2' => ReportPaginationMode::FLOW_NO_LONE_SIGNATURE,
        'mode3' => ReportPaginationMode::AREA_HARD_PAGE_BREAK,
        'mode4' => ReportPaginationMode::AREA_SOFT_FIT_SIGNATURE,
        default => ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
    };
}
$ctx = $rs->buildReportLayoutContext($data, $layout, $rs->getLabConfig());
$html = $rs->renderReportPdfHtml($data, 'http://test', '', '', $layout);

$errors = [];
$ok = 0;
foreach ($ctx['plan']->placements as $p) {
    if ($p->nodeType !== 'analysis') {
        continue;
    }
    $id = preg_quote($p->nodeId, '/');
    if (! preg_match('/data-layout-block-id="' . $id . '"/', $html)) {
        $errors[] = "Falta bloque HTML: {$p->nodeId}";
        continue;
    }
    $expectedSub = $p->markers->subgrupoClass;
    $expectedCab = $p->markers->cabeceraClass;
    if (! preg_match(
        '/<div\b(?=[^>]*data-layout-block-id="' . $id . '")[^>]*class="([^"]*)"[^>]*>/',
        $html,
        $m,
    ) && ! preg_match(
        '/<div\b(?=[^>]*class="([^"]*)")[^>]*data-layout-block-id="' . $id . '"[^>]*>/',
        $html,
        $m,
    )) {
        $errors[] = "No se leyó class de {$p->nodeId}";
        continue;
    }
    $classes = $m[1];
    if ($expectedSub !== '' && ! str_contains($classes, $expectedSub)) {
        $errors[] = "{$p->nodeId}: falta {$expectedSub} en HTML";
    }
    if ($expectedSub === '' && str_contains($classes, 'report-subgrupo-force-break-before')) {
        $errors[] = "{$p->nodeId}: force-break en HTML pero no en plan";
    }
    if ($expectedCab !== '' && ! preg_match('/data-layout-block-id="' . $id . '".*report-cabecera-force-break-before/s', $html)) {
        $errors[] = "{$p->nodeId}: falta cabecera-force-break en HTML";
    }
    if ($expectedCab === '' && ReportPaginationMode::usesFlowContinuousPagination($ctx['plan']->mode)
        && preg_match('/data-layout-block-id="' . $id . '".*report-cabecera-force-break-before/s', $html)) {
        $errors[] = "{$p->nodeId}: cabecera-force-break en HTML pero no en plan (modo flujo)";
    }
    $ok++;
}

$mode = $ctx['plan']->mode;
$keepIntactInHtml = preg_match_all(
    '/<div\b[^>]*data-layout-block-id="[^"]+"[^>]*class="[^"]*report-subgrupo-keep-intact/',
    $html,
) + preg_match_all(
    '/<div\b[^>]*class="[^"]*report-subgrupo-keep-intact[^"]*"[^>]*data-layout-block-id="/',
    $html,
);
$flowMode = ReportPaginationMode::usesFlowContinuousPagination($mode);
$areaMode = ReportPaginationMode::usesAreaHardPageBreak($mode);

echo "Audit plan vs HTML — registro {$registroId} mode={$mode}\n";
echo "Bloques verificados: {$ok}\n";
echo "keep-intact en HTML: {$keepIntactInHtml}\n";
if ($flowMode && $keepIntactInHtml > 0) {
    $errors[] = 'MODE flujo no debe emitir report-subgrupo-keep-intact';
}
if ($areaMode && $keepIntactInHtml === 0) {
    echo "Nota: MODE área sin keep-intact (puede ser normal si no hay bloques pequeños).\n";
}

if ($errors === []) {
    echo "OK — plan y HTML coherentes.\n";
    exit(0);
}

echo "ERRORES:\n";
foreach ($errors as $e) {
    echo "  - {$e}\n";
}
exit(1);
