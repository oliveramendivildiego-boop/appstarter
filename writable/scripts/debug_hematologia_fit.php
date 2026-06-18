<?php
/**
 * Analiza si Hematología cabe en hoja 1. Uso: php writable/scripts/debug_hematologia_fit.php 253
 */
declare(strict_types=1);

$registroId = (int) ($argv[1] ?? 0);
if ($registroId < 1) {
    fwrite(STDERR, "Uso: php writable/scripts/debug_hematologia_fit.php <registro_id>\n");
    exit(1);
}

$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');
defined('CI_DEBUG') || define('CI_DEBUG', true);

define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

helper('registro');

$registerService = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);

$data = $registerService->prepareReportData($registroId);
if ($data === null) {
    fwrite(STDERR, "Registro {$registroId} no encontrado.\n");
    exit(1);
}

$layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$ps     = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
$gpb    = \App\Services\ReportPdfLayoutService::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
$mm     = is_array($layout['margins_mm'] ?? null) ? $layout['margins_mm'] : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();

$pageHeightMm = 279.4;
$marginTop    = (float) ($mm['top'] ?? 15);
$marginBottom = (float) ($mm['bottom'] ?? 15);
$footerMm     = \App\Services\ReportPdfLayoutService::estimatePdfFooterReserveMm($layout);
$headerMm     = \App\Services\ReportPdfLayoutService::estimatePdfHeaderBeforeResultsMm($layout);
$baseContentMm = max(40.0, $pageHeightMm - $marginTop - $marginBottom - $footerMm);
$firstPageResultsMm = max(0.0, $baseContentMm - $headerMm);

$grupos   = is_array($data['grupos'] ?? null) ? $data['grupos'] : [];
$firstKey = array_key_first($grupos);
$items    = is_array($grupos[$firstKey] ?? null) ? $grupos[$firstKey] : [];

$hasFirma = false;
$lf = \App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle($ps['lab_firmas'] ?? []);
if (\App\Services\ReportPdfLayoutService::isLabFirmasBlockEnabled($layout)
    && \App\Services\ReportPdfLayoutService::labFirmasPlacementShowsPerGroup($lf)) {
    foreach ($data['report_lab_firmas'] ?? [] as $firmaRow) {
        if (is_array($firmaRow) && trim((string) ($firmaRow['prueba_nombre'] ?? '')) === (string) $firstKey) {
            $hasFirma = true;
            break;
        }
    }
}

$pb = \App\Services\ReportPdfDompdfGrupoPageBreakService::create($layout, $headerMm);
$pb->setRefsConsolidada(is_array($data['report_pria_refs_consolidada'] ?? null) ? $data['report_pria_refs_consolidada'] : []);
$meta = $pb->beginGrupo(true, $items, $hasFirma);

// Estimación directa altura hematología (sin firma en grupo si no hay)
$reflection = new ReflectionClass($pb);
$estMethod  = $reflection->getMethod('estimateGrupoHeightFast');
$estMethod->setAccessible(true);
$heightNormal  = $estMethod->invoke($pb, $items, $hasFirma, false);
$heightCompact = $estMethod->invoke($pb, $items, $hasFirma, true);

$rowCount = 0;
foreach ($items as $raw) {
    $it = is_array($raw) ? (object) $raw : $raw;
    $v  = trim((string) ($it->regvalues ?? ''));
    if (($v !== '' && $v !== '-') || ! empty($it->show_reference)) {
        $rowCount++;
    }
}

echo "=== Registro {$registroId} — Hematología en hoja 1 ===\n\n";
echo "Área: {$firstKey}\n";
echo "Modo paginación: {$gpb['mode']}\n";
echo "Firma al pie del área: " . ($hasFirma ? 'sí' : 'no') . "\n";

echo "--- Espacio en hoja 1 (estimación servidor) ---\n";
echo sprintf("Altura hoja útil (sin márgenes/pie): %.1f mm\n", $baseContentMm);
echo sprintf("Bloques antes de resultados (header+paciente): %.1f mm\n", $headerMm);
echo sprintf("Espacio restante para Hematología en pág. 1: %.1f mm\n", $firstPageResultsMm);
echo sprintf("Altura estimada Hematología (normal): %.1f mm\n", $heightNormal);
echo sprintf("Altura estimada Hematología (compacta 75%%): %.1f mm\n", $heightCompact);
echo sprintf("Altura máxima de un área en una hoja: %.1f mm\n\n", $baseContentMm);

$cabeEnPrimera = $heightNormal <= $firstPageResultsMm;
$cabeEnHojaCompleta = $heightNormal <= $baseContentMm;

echo "--- Veredicto (estimación PHP, igual criterio que motor PDF) ---\n";
if ($cabeEnPrimera) {
    echo "SÍ cabe en la primera hoja (después del encabezado).\n";
    echo sprintf("Sobran ~%.1f mm en pág. 1 tras Hematología.\n", $firstPageResultsMm - $heightNormal);
} else {
    echo "NO cabe completa en la primera hoja.\n";
    echo sprintf("Faltan ~%.1f mm (altura %.1f > espacio %.1f).\n", $heightNormal - $firstPageResultsMm, $heightNormal, $firstPageResultsMm);
    if ($cabeEnHojaCompleta) {
        echo "Motivo: el encabezado del informe ocupa espacio en pág. 1; el área sí cabría en una hoja vacía.\n";
    } else {
        echo "Motivo: el bloque es más alto que una hoja completa";
        if ($gpb['mode'] === 'keep_together_compact') {
            $scale = max(75, min(100, (int) ($gpb['compact_min_scale_percent'] ?? 85))) / 100;
            if ($heightCompact <= $baseContentMm) {
                echo "; con compactación al " . (int) ($scale * 100) . "% sí cabría en una hoja.\n";
            } else {
                echo "; ni siquiera con compactación cabe en una sola hoja → se parte por segmentos.\n";
            }
        } else {
            echo ".\n";
        }
    }
}

echo "\nClases que el motor PDF asignaría: {$meta['classes']}\n";
echo "Compactación PDF: " . (! empty($meta['apply_compact']) ? 'sí' : 'no') . "\n";

echo "\n--- Nota viewreport (/registers/viewreport/{$registroId}) ---\n";
echo "La vista previa (screen_pdf) NO ejecuta el script de impresión; solo muestra el HTML con\n";
echo "page-break-inside:avoid en el div del grupo. La paginación real se aplica al imprimir (printreport).\n";
