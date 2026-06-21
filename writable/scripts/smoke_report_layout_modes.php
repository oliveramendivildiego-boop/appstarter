<?php
/**
 * Smoke test: LayoutEngine en PDF/PRINT con los 4 modos de paginación.
 * Uso: php writable/scripts/smoke_report_layout_modes.php [registro_id]
 */
declare(strict_types=1);

$registroId = (int) ($argv[1] ?? 0);

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
defined('CI_DEBUG') || define('CI_DEBUG', true);
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

use App\Libraries\PdfService;
use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportPdfLayoutService;
use App\Services\RegisterService;

$db = \Config\Database::connect();

if ($registroId < 1) {
    $row = $db->query(
        'SELECT r.registro_id, COUNT(DISTINCT p.nombre) AS areas
         FROM registro r
         INNER JOIN regvalues rv ON rv.registro_id = r.registro_id
         INNER JOIN prianacategoria p ON p.idprianacategoria = rv.idprianacategoria
         GROUP BY r.registro_id
         HAVING areas >= 2
         ORDER BY r.registro_id DESC
         LIMIT 1'
    )->getRowArray();
    $registroId = (int) ($row['registro_id'] ?? 0);
    if ($registroId < 1) {
        $registroId = (int) ($db->query('SELECT MAX(registro_id) AS id FROM registro')->getRow('id') ?? 0);
    }
}

if ($registroId < 1) {
    fwrite(STDERR, "No hay registros para probar.\n");
    exit(1);
}

$registerService = new RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);

$data = $registerService->prepareReportData($registroId);
if ($data === null) {
    fwrite(STDERR, "Registro {$registroId} no encontrado o sin datos.\n");
    exit(1);
}

$grupos = is_array($data['grupos'] ?? null) ? $data['grupos'] : [];
$layoutService = new ReportPdfLayoutService();
$baseLayout = $layoutService->getActiveLayoutForRender();
$labConfig = $registerService->getLabConfig();
$pageSize = ReportPdfLayoutService::resolveGlobalPageSizeMm($labConfig);

$modes = ReportPaginationMode::ALL;
$legacyMarkers = [
    'ReportPdfDompdfGrupoPageBreakService',
    'report_pdf_grupo_page_break_script',
    'pdf-gpb-grupo-intact',
    'pdf-gpb-keep-together',
    'pdf-gpb-segment-rules',
];

$failures = 0;
$reportUrl = 'http://localhost/registers/viewreport/' . $registroId;
$qrDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

echo sprintf(
    "Smoke LayoutEngine — registro #%d, áreas: %d, papel: %s\n\n",
    $registroId,
    count($grupos),
    is_array($pageSize) ? (string) ($pageSize['key'] ?? '?') : '?'
);

foreach ($modes as $mode) {
    echo str_repeat('-', 72) . "\n";
    echo 'Modo: ' . $mode . "\n";

    $layout = $baseLayout;
    if (! isset($layout['page_style']) || ! is_array($layout['page_style'])) {
        $layout['page_style'] = ReportPdfLayoutService::defaultPageStyleStatic();
    }
    $layout['page_style']['pagination_mode'] = $mode;

    $bodyClass = ReportPdfLayoutService::grupoPruebaPageBreakBodyClass($layout);
    $ctx = $registerService->buildReportLayoutContext($data, $layout, $labConfig);
    $applier = $ctx['applier'];
    $plan = $ctx['plan'];

    echo sprintf(
        "  Plan: %d placement(s), totalPages=%d, applier activo=%s\n",
        count($plan->placements),
        $applier->totalPages(),
        $applier->isActive() ? 'sí' : 'no'
    );

    if (! str_contains($bodyClass, 'pdf-layout-engine')) {
        echo "  FAIL: body class sin pdf-layout-engine: {$bodyClass}\n";
        $failures++;
    }
    if ($plan->mode !== $mode) {
        echo "  FAIL: plan.mode={$plan->mode} != {$mode}\n";
        $failures++;
    }

    $pdfHtml = view('registers/report_pdf', [
        'register_info' => $data['register_info'],
        'paciente'      => $data['paciente'],
        'doctor'        => $data['doctor'],
        'grupos'        => $grupos,
        'lab_config'    => $labConfig,
        'report_url'    => $reportUrl,
        'qr_data_uri'   => $qrDataUri,
        'pdf_layout'    => $layout,
        'report_emitido_en' => '01/01/2026 12:00:00',
        'report_pria_tipo_muestra_nombre' => $data['report_pria_tipo_muestra_nombre'] ?? [],
        'report_pria_metodo_nombre'       => $data['report_pria_metodo_nombre'] ?? [],
        'report_lab_firmas'               => $data['report_lab_firmas'] ?? [],
        'report_pria_refs_consolidada'    => $data['report_pria_refs_consolidada'] ?? [],
        'report_layout_plan'              => $plan,
        'report_layout_applier'           => $applier,
    ]);

    $printHtml = view('registers/report_print', [
        'register_info' => $data['register_info'],
        'paciente'      => $data['paciente'],
        'doctor'        => $data['doctor'],
        'grupos'        => $grupos,
        'lab_config'    => $labConfig,
        'report_url'    => $reportUrl,
        'qr_data_uri'   => $qrDataUri,
        'pdf_layout'    => $layout,
        'registro_id'   => $registroId,
        'report_emitido_en' => '01/01/2026 12:00:00',
        'layout_report_mode' => false,
        'report_pria_tipo_muestra_nombre' => $data['report_pria_tipo_muestra_nombre'] ?? [],
        'report_pria_metodo_nombre'       => $data['report_pria_metodo_nombre'] ?? [],
        'report_lab_firmas'               => $data['report_lab_firmas'] ?? [],
        'report_pria_refs_consolidada'    => $data['report_pria_refs_consolidada'] ?? [],
        'report_layout_plan'              => $plan,
        'report_layout_applier'           => $applier,
    ]);

    foreach (['PDF' => $pdfHtml, 'PRINT' => $printHtml] as $label => $html) {
        if (! str_contains($html, 'pdf-layout-engine')) {
            echo "  FAIL {$label}: HTML sin clase pdf-layout-engine en body\n";
            $failures++;
        }
        if ($label === 'PRINT' && ! str_contains($html, 'reportLayoutPlan')) {
            echo "  FAIL {$label}: sin script reportLayoutPlan\n";
            $failures++;
        }
        if ($label === 'PDF' && ! str_contains($html, 'report-segment-force-break-before')
            && ! str_contains($html, 'report-pdf-grupo-prueba-new-page-start')
            && count($grupos) > 1
        ) {
            echo "  WARN {$label}: sin marcadores de salto visibles (revisar plan)\n";
        }
        if (str_contains($html, 'report_pdf_grupo_page_break_script')) {
            echo "  FAIL {$label}: referencia al script legacy\n";
            $failures++;
        }
        foreach ($legacyMarkers as $marker) {
            if (str_contains($html, $marker)) {
                echo "  FAIL {$label}: contiene legacy «{$marker}»\n";
                $failures++;
            }
        }
        if (! str_contains($html, 'data-layout-block-id=')) {
            echo "  WARN {$label}: sin marcadores data-layout-block-id (¿reporte vacío?)\n";
        }
        echo sprintf("  OK {$label}: %d bytes HTML\n", strlen($html));
    }

    try {
        ini_set('memory_limit', '512M');
        $pdfService = new PdfService();
        $pdfBytes = $pdfService->generate($pdfHtml, 'smoke.pdf', $pageSize);
        $pdfLen = strlen($pdfBytes);
        if ($pdfLen < 500 || ! str_starts_with($pdfBytes, '%PDF')) {
            echo "  FAIL Dompdf: salida inválida ({$pdfLen} bytes)\n";
            $failures++;
        } else {
            $outDir = WRITEPATH . 'debug';
            if (! is_dir($outDir)) {
                mkdir($outDir, 0775, true);
            }
            $safeMode = str_replace('_', '-', $mode);
            $outFile = $outDir . DIRECTORY_SEPARATOR . "layout_smoke_{$registroId}_{$safeMode}.pdf";
            file_put_contents($outFile, $pdfBytes);
            echo sprintf("  OK Dompdf: %d bytes → %s\n", $pdfLen, $outFile);
        }
    } catch (\Throwable $e) {
        echo '  FAIL Dompdf: ' . $e->getMessage() . "\n";
        $failures++;
    }

    unset($pdfHtml, $printHtml);
    gc_collect_cycles();
}

echo str_repeat('-', 72) . "\n";
if ($failures > 0) {
    echo "RESULTADO: {$failures} fallo(s)\n";
    exit(1);
}

echo "RESULTADO: todos los modos pasaron (PDF + PRINT + Dompdf)\n";
exit(0);
