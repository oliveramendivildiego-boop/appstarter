<?php
/**
 * Traza paginación PHP (motor Dompdf) para Química — cursor desde inicio hoja 2.
 * Uso: php writable/scripts/debug_quimica_placement.php 253
 */
declare(strict_types=1);

$registroId = (int) ($argv[1] ?? 0);
if ($registroId < 1) {
    fwrite(STDERR, "Uso: php writable/scripts/debug_quimica_placement.php <registro_id>\n");
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

$registerService = new \App\Services\RegisterService(
    new \App\Models\RegisterModel(),
    new \App\Models\AppConfigModel(),
);
$data = $registerService->prepareReportData($registroId);
if ($data === null) {
    fwrite(STDERR, "Registro {$registroId} no encontrado.\n");
    exit(1);
}

$layout   = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
$headerMm = \App\Services\ReportPdfLayoutService::estimatePdfHeaderBeforeResultsMm($layout);
$grupos   = is_array($data['grupos'] ?? null) ? $data['grupos'] : [];
$refs     = is_array($data['report_pria_refs_consolidada'] ?? null) ? $data['report_pria_refs_consolidada'] : [];
$grupoKeys = array_keys($grupos);
$hemKey  = $grupoKeys[0];
$quimKey = $grupoKeys[1];
$hemItems  = is_array($grupos[$hemKey]) ? $grupos[$hemKey] : [];
$quimItems = is_array($grupos[$quimKey]) ? $grupos[$quimKey] : [];

$lfStyle = \App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle(
    is_array($layout['page_style']['lab_firmas'] ?? null) ? $layout['page_style']['lab_firmas'] : []
);
$labFirmasEnabled = \App\Services\ReportPdfLayoutService::isLabFirmasBlockEnabled($layout);
$showFirmaPerGroup = $labFirmasEnabled && \App\Services\ReportPdfLayoutService::labFirmasPlacementShowsPerGroup($lfStyle);
$firmasPorPadre = [];
if ($showFirmaPerGroup) {
    foreach ($data['report_lab_firmas'] ?? [] as $firmaRow) {
        if (! is_array($firmaRow)) {
            continue;
        }
        $k = trim((string) ($firmaRow['prueba_nombre'] ?? ''));
        if ($k !== '') {
            $firmasPorPadre[$k] = $firmaRow;
        }
    }
}
$quimHasFirma = $showFirmaPerGroup && isset($firmasPorPadre[trim((string) $quimKey)]);

$reflection = new ReflectionClass(\App\Services\ReportPdfDompdfGrupoPageBreakService::class);
$iterMethod      = $reflection->getMethod('iterVisiblePlacementUnits');
$estUnitMethod   = $reflection->getMethod('estimateUnitHeightMm');
$placeSegMethod  = $reflection->getMethod('placeSegmentUnit');
$placeLastMethod = $reflection->getMethod('placeLastUnitWithFirma');
$areaSepProp     = $reflection->getProperty('areaSeparatorMm');
$firmaProp       = $reflection->getProperty('firmaHeightMm');
$baseProp        = $reflection->getProperty('basePageContentMm');
foreach ([$iterMethod, $estUnitMethod, $placeSegMethod, $placeLastMethod, $areaSepProp, $firmaProp, $baseProp] as $m) {
    $m->setAccessible(true);
}

$pb = \App\Services\ReportPdfDompdfGrupoPageBreakService::create($layout, $headerMm);
$pb->setRefsConsolidada($refs);
$baseMm  = $baseProp->getValue($pb);
$firmaMm = $firmaProp->getValue($pb);
$areaSep = $areaSepProp->getValue($pb);

$pb->beginGrupo(true, $hemItems, true);
$pb->beginGrupo(false, $quimItems, $quimHasFirma);

$units = iterator_to_array($iterMethod->invoke($pb, $quimItems));
$total = count($units);
$priaNames = [];
foreach ($quimItems as $raw) {
    $it = is_array($raw) ? (object) $raw : $raw;
    if ((int) ($it->es_separador ?? 0) === 1) {
        continue;
    }
    $pid = (int) ($it->prianacategoria_id ?? 0);
    $v   = trim((string) ($it->regvalues ?? ''));
    if (($v === '' || $v === '-') && empty($it->show_reference)) {
        continue;
    }
    if (! isset($priaNames[$pid])) {
        $priaNames[$pid] = trim((string) ($it->nombre ?? ''));
    }
}

// Re-simular solo colocación Química desde salto inter-área (hoja 2).
$simPb = \App\Services\ReportPdfDompdfGrupoPageBreakService::create($layout, $headerMm);
$simPb->setRefsConsolidada($refs);
$simPb->beginGrupo(true, $hemItems, true);

$cp = $reflection->getProperty('cursorPage');
$cy = $reflection->getProperty('cursorY');
$fn = $reflection->getMethod('forceNextPage');
$bm = $reflection->getMethod('bumpCursor');
foreach ([$cp, $cy, $fn, $bm] as $m) {
    $m->setAccessible(true);
}
$fn->invoke($simPb); // inter-área → hoja 2
$bm->invoke($simPb, $areaSep);

$cursorPage = $cp->getValue($simPb);
$cursorY    = $cy->getValue($simPb);

echo sprintf("Inicio Química hoja %d, y=%.1f mm (base=%.1f)\n\n", $cursorPage + 1, $cursorY, $baseMm);

$lastSubgrupoKey = -1;
$cabeceraCounted = false;
$priaIds = array_keys($priaNames);

foreach ($units as $idx => $unit) {
    $countCabecera = false;
    if ($unit['subgrupo_key'] !== $lastSubgrupoKey) {
        $lastSubgrupoKey = $unit['subgrupo_key'];
        $cabeceraCounted = false;
    }
    if (! $cabeceraCounted) {
        $countCabecera   = true;
        $cabeceraCounted = true;
    }
    $unitH  = $estUnitMethod->invoke($simPb, $unit['rows'], $unit['has_title'], $countCabecera, $unit['is_matrix']);
    $isLast = ($idx === $total - 1) && $quimHasFirma;
    $pageEnd = $baseMm * ($cursorPage + 1);
    $remaining = max(0.0, $pageEnd - $cursorY);
    $label = $priaNames[$priaIds[$unit['subgrupo_key']] ?? 0] ?? ('sub#' . $unit['subgrupo_key']);
    if ($unit['rows'] > 1) {
        $label .= " ({$unit['rows']} filas)";
    }

    $attrs = $isLast
        ? $placeLastMethod->invoke($simPb, $unitH, $countCabecera)
        : $placeSegMethod->invoke($simPb, $unitH, $countCabecera);
    $break = trim(($attrs['subgrupo_class'] ?? '') . ' ' . ($attrs['segment_class'] ?? ''));

    $bloque = $isLast ? $unitH + $firmaMm : $unitH;
    echo sprintf(
        "%2d p%d y=%.0f rem=%.1f h=%.1f%s %-28s %s\n",
        $idx + 1,
        $cursorPage + 1,
        $cursorY,
        $remaining,
        $unitH,
        $isLast ? sprintf('+%.0fF=%.0f', $firmaMm, $bloque) : '      ',
        $label,
        $break !== '' ? $break : '-'
    );

    $cursorPage = $cp->getValue($simPb);
    $cursorY    = $cy->getValue($simPb);
    if ($break !== '') {
        echo "    → force-break → página " . ($cursorPage + 1) . "\n";
    }
}

echo sprintf("\nFin: página %d, y=%.1f mm\n", $cursorPage + 1, $cursorY);
