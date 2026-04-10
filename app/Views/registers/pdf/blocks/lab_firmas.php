<?php
declare(strict_types=1);

$report_lab_firmas = $report_lab_firmas ?? [];
if ($report_lab_firmas === []) {
    return;
}
$layout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
if (empty($layout['instances']) || ! is_array($layout['instances'])) {
    $layout = (new \App\Services\ReportPdfLayoutService())->getDefaultLayout();
}

[$n, $allGridItems] = \App\Services\ReportPdfLayoutService::gridItemsForSection($layout, 'lab_firmas');
$n                = max(1, $n);
$secLayouts       = is_array($layout['section_layouts'] ?? null) ? $layout['section_layouts'] : [];
$sectionLayout    = is_array($secLayouts['lab_firmas'] ?? null) ? $secLayouts['lab_firmas'] : [];

$titleItems = [];
$bodyItems  = [];
foreach ($allGridItems as $it) {
    if (($it['element_type'] ?? '') === 'lab_firmas_title') {
        $titleItems[] = $it;
    } else {
        $bodyItems[] = $it;
    }
}

$ps    = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
$lfTxt = \App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle($ps['lab_firmas'] ?? []);
$multi = count($report_lab_firmas) > 1;
?>
<div class="lab-firmas-pdf-block">
<?php if ($titleItems !== []): ?>
    <?= view('registers/pdf/section_layout_grid', [
        'section_wrapper_class' => 'lab-firmas-title-grid',
        'n_columns'             => $n,
        'grid_items'            => $titleItems,
        'element_ctx'           => [
            'lab_config'         => $lab_config ?? [],
            'lab_firmas_style'   => $lfTxt,
            'pdf_firma_row'      => [],
        ],
        'section_layout'        => $sectionLayout,
    ]) ?>
<?php endif; ?>

<?php if ($bodyItems !== []): ?>
    <?php foreach ($report_lab_firmas as $idx => $firma): ?>
        <?php if ($multi): ?>
            <div class="pdf-lab-f-prueba-name" style="border-bottom:1px solid #dee2e6;font-weight:600;margin-top:8px;padding-bottom:4px;">
                <?= esc(strtoupper((string) ($firma['prueba_nombre'] ?? ''))) ?>
            </div>
        <?php endif; ?>
        <?= view('registers/pdf/section_layout_grid', [
            'section_wrapper_class' => 'lab-firmas-body-grid',
            'n_columns'             => $n,
            'grid_items'            => $bodyItems,
            'element_ctx'           => [
                'lab_config'       => $lab_config ?? [],
                'lab_firmas_style' => $lfTxt,
                'pdf_firma_row'    => $firma,
            ],
            'section_layout'      => $sectionLayout,
        ]) ?>
        <?php if ($multi && $idx < count($report_lab_firmas) - 1): ?>
            <div style="height:12px;" aria-hidden="true"></div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
</div>
