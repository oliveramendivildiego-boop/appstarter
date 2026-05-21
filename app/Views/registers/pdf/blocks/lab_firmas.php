<?php
declare(strict_types=1);

$layout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
if (empty($layout['instances']) || ! is_array($layout['instances'])) {
    $layout = (new \App\Services\ReportPdfLayoutService())->getDefaultLayout();
}
if (! \App\Services\ReportPdfLayoutService::isLabFirmasBlockEnabled($layout)) {
    return;
}
$lfPlacementStyle = \App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle(
    is_array($layout['page_style']['lab_firmas'] ?? null) ? $layout['page_style']['lab_firmas'] : []
);
if (! \App\Services\ReportPdfLayoutService::labFirmasPlacementShowsBlockEnd($lfPlacementStyle)) {
    return;
}

$report_lab_firmas = $report_lab_firmas ?? [];
$placementMode = (string) ($lfPlacementStyle['placement'] ?? 'per_group');
if ($placementMode === 'block_end') {
    $report_lab_firmas = array_values(array_filter($report_lab_firmas, static function ($firma): bool {
        return is_array($firma);
    }));
} else {
    $report_lab_firmas = array_values(array_filter($report_lab_firmas, static function ($firma): bool {
        return is_array($firma) && trim((string) ($firma['prueba_nombre'] ?? '')) === '';
    }));
}
if ($report_lab_firmas === []) {
    return;
}
?>
<div class="lab-firmas-pdf-block lab-firmas-pdf-block-global">
    <?php foreach ($report_lab_firmas as $idx => $firma): ?>
        <?= view('registers/partials/report_lab_firma_grupo_inline', [
            'firma'            => $firma,
            'analisis_variant' => $analisis_variant ?? 'pdf',
            'pdf_layout'       => $layout,
            'lab_config'       => $lab_config ?? [],
        ]) ?>
        <?php if ($idx < count($report_lab_firmas) - 1): ?>
            <div style="height:12px;" aria-hidden="true"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
