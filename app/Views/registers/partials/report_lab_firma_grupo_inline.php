<?php
/**
 * Una validación/firma de laboratorio inline (debajo de un grupo de pruebas).
 *
 * @var array<string, mixed> $firma
 * @var string $area_label
 * @var string $analisis_variant pdf|screen_pdf|...
 * @var array<string, mixed>|null $lab_firmas_style
 */
$firma = is_array($firma ?? null) ? $firma : [];
if ($firma === []) {
    return;
}
$variant = (string) ($analisis_variant ?? 'pdf');
$isScreen = $variant === 'screen_pdf' || $variant === 'screen';
$areaLabel = trim((string) ($area_label ?? ($firma['prueba_nombre'] ?? '')));

$layout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$lfTxt = is_array($lab_firmas_style ?? null)
    ? $lab_firmas_style
    : \App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle(
        is_array($layout['page_style']['lab_firmas'] ?? null) ? $layout['page_style']['lab_firmas'] : []
    );

$marginTop = (float) ($lfTxt['inline_margin_top_pt'] ?? 8);
$marginBottom = (float) ($lfTxt['inline_margin_bottom_pt'] ?? 6);
$sealMaxH = (int) ($lfTxt['seal_max_height_px'] ?? 110);
$sigMaxH = (int) ($lfTxt['signature_max_height_px'] ?? 72);
$sigMaxW = (int) ($lfTxt['signature_max_width_px'] ?? 220);
$showAreaHeading = ! empty($lfTxt['show_area_heading']) && $areaLabel !== '';

$lblValidator = trim((string) ($lfTxt['label_validator'] ?? 'Verificado por:'));
$lblFirma = trim((string) ($lfTxt['label_firma'] ?? 'ATENTAMENTE'));
$lblMatricula = trim((string) ($lfTxt['label_matricula'] ?? 'Matrícula:'));

$seal = trim((string) ($firma['approver_seal'] ?? ''));
$sig  = trim((string) ($firma['approver_signature'] ?? ''));
$hasSealFile = $seal !== '' && is_file(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $seal));
$hasSigFile  = $sig !== '' && is_file(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $sig));
$imgNone = 'border: none; outline: none; box-shadow: none; background: transparent;';

$wrapStyle = 'margin-top:' . esc((string) $marginTop, 'attr') . 'pt;margin-bottom:' . esc((string) $marginBottom, 'attr') . 'pt;';

if ($isScreen):
?>
<div class="report-lab-firma-grupo-inline card border-light shadow-sm" style="<?= $wrapStyle ?>">
    <div class="card-body py-3">
        <?php if ($showAreaHeading): ?>
            <div class="report-lab-firma-area-heading small fw-semibold text-muted text-uppercase border-bottom pb-1 mb-3"><?= esc($areaLabel) ?></div>
        <?php endif; ?>
        <div class="row align-items-start g-3">
            <div class="col-12 col-md-4">
                <?php if ($lblValidator !== '' && ! empty($lfTxt['show_label_validator'])): ?>
                    <div class="small text-muted mb-1"><?= esc($lblValidator) ?></div>
                <?php endif; ?>
                <div><?= esc(($firma['validator_name'] ?? '') !== '' ? $firma['validator_name'] : '—') ?></div>
            </div>
            <div class="col-12 col-md-4 text-center">
                <?php if ($hasSealFile): ?>
                    <img src="<?= base_url($seal) ?>" alt="" class="d-inline-block" style="max-height: <?= (int) $sealMaxH ?>px; max-width: 100%; <?= $imgNone ?>">
                <?php else: ?>
                    <div class="text-muted small">—</div>
                <?php endif; ?>
            </div>
            <div class="col-12 col-md-4">
                <?php if ($lblFirma !== '' && ! empty($lfTxt['show_label_firma'])): ?>
                    <div class="small text-muted mb-1"><?= esc($lblFirma) ?></div>
                <?php endif; ?>
                <?php if ($hasSigFile): ?>
                    <div class="mb-2">
                        <img src="<?= base_url($sig) ?>" alt="" class="d-block" style="max-height: <?= (int) $sigMaxH ?>px; max-width: <?= (int) $sigMaxW ?>px; <?= $imgNone ?>">
                    </div>
                <?php endif; ?>
                <div class="fw-semibold"><?= esc(($firma['approver_name'] ?? '') !== '' ? $firma['approver_name'] : '—') ?></div>
                <?php $cargo = trim((string) ($firma['approver_cargo'] ?? '')); ?>
                <?php if ($cargo !== ''): ?>
                    <div class="mt-2"><?= esc($cargo) ?></div>
                <?php endif; ?>
                <?php $matricula = trim((string) ($firma['approver_matricula'] ?? '')); ?>
                <?php if ($matricula !== ''): ?>
                    <div class="mt-1">
                        <?php if ($lblMatricula !== '' && ! empty($lfTxt['show_label_matricula'])): ?>
                            <span class="small text-muted"><?= esc($lblMatricula) ?></span>
                        <?php endif; ?>
                        <?= esc($matricula) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
    return;
endif;

if (empty($layout['instances']) || ! is_array($layout['instances'])) {
    $layout = (new \App\Services\ReportPdfLayoutService())->getDefaultLayout();
}

[$n, $allGridItems] = \App\Services\ReportPdfLayoutService::gridItemsForSection($layout, 'lab_firmas');
$n             = max(1, $n);
$secLayouts    = is_array($layout['section_layouts'] ?? null) ? $layout['section_layouts'] : [];
$sectionLayout = is_array($secLayouts['lab_firmas'] ?? null) ? $secLayouts['lab_firmas'] : [];

$titleItems = [];
$bodyItems  = [];
foreach ($allGridItems as $it) {
    if (($it['element_type'] ?? '') === 'lab_firmas_title') {
        $titleItems[] = $it;
    } else {
        $bodyItems[] = $it;
    }
}

$areaHeadingStyle = 'color:' . esc((string) ($lfTxt['area_heading_color'] ?? '#664D03'), 'attr')
    . ';font-size:' . esc((string) ($lfTxt['area_heading_font_size_pt'] ?? 9), 'attr') . 'pt'
    . ';font-weight:' . esc((string) ($lfTxt['area_heading_font_weight'] ?? '600'), 'attr')
    . ';text-transform:' . esc((string) ($lfTxt['area_heading_text_transform'] ?? 'uppercase'), 'attr')
    . ';border-bottom:1px solid #dee2e6;padding-bottom:4px;margin-bottom:6px;';
?>
<div class="lab-firmas-pdf-block lab-firmas-pdf-block-inline report-lab-firma-grupo-inline" style="<?= $wrapStyle ?>">
<?php if ($showAreaHeading): ?>
    <div class="pdf-lab-f-area-heading" style="<?= $areaHeadingStyle ?>"><?= esc($areaLabel) ?></div>
<?php endif; ?>
<?php if ($titleItems !== []): ?>
    <?= view('registers/pdf/section_layout_grid', [
        'section_wrapper_class' => 'lab-firmas-title-grid',
        'n_columns'             => $n,
        'grid_items'            => $titleItems,
        'element_ctx'           => [
            'lab_config'       => $lab_config ?? [],
            'lab_firmas_style' => $lfTxt,
            'pdf_firma_row'    => [],
        ],
        'section_layout'        => $sectionLayout,
    ]) ?>
<?php endif; ?>
<?php if ($bodyItems !== []): ?>
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
<?php endif; ?>
</div>
