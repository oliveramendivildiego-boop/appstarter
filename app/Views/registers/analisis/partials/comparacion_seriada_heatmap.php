<?php
/**
 * Tabla heatmap de comparación seriada (M1, M2, M3) para resultados categóricos.
 *
 * @var array<string, mixed> $heatmap
 * @var string               $variant
 * @var bool                 $use_pdf_chrome
 */
$heatmap = is_array($heatmap ?? null) ? $heatmap : [];
$sections = is_array($heatmap['sections'] ?? null) ? $heatmap['sections'] : [];
$sampleLabels = is_array($heatmap['sample_labels'] ?? null) ? $heatmap['sample_labels'] : ['M1', 'M2', 'M3'];
$legend = is_array($heatmap['legend'] ?? null) ? $heatmap['legend'] : [];
$usePdfChrome = ! empty($use_pdf_chrome);
$variant = (string) ($variant ?? 'web');

if ($sections === []) {
    return;
}

$tableClass = $usePdfChrome ? 'results catcmp-table' : 'table table-bordered table-sm catcmp-table mb-0';
$wrapClass = $usePdfChrome ? 'report-segment-table-wrap catcmp-wrap' : 'table-responsive mb-3 catcmp-wrap';
$sectionTitleClass = $usePdfChrome
    ? 'report-segment-title pdf-card-header catcmp-section-title'
    : 'report-segment-title-web px-2 py-2 mb-2 bg-secondary bg-opacity-10 border-start border-4 border-secondary rounded-end fw-semibold text-uppercase small catcmp-section-title';
?>
<div class="<?= esc($wrapClass, 'attr') ?>">
    <div class="<?= esc($sectionTitleClass, 'attr') ?>">Comparación seriada</div>
    <?php foreach ($sections as $section): ?>
        <?php
        $sectionTitle = trim((string) ($section['title'] ?? ''));
        $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
        if ($rows === []) {
            continue;
        }
        ?>
        <?php if ($sectionTitle !== ''): ?>
            <div class="catcmp-subsection-title"><?= esc($sectionTitle) ?></div>
        <?php endif; ?>
        <table class="<?= esc($tableClass, 'attr') ?>">
            <thead<?= $usePdfChrome ? '' : ' class="table-light"' ?>>
                <tr>
                    <th class="catcmp-col-param">Parámetro</th>
                    <?php foreach ($sampleLabels as $sampleLabel): ?>
                        <th class="text-center catcmp-col-sample"><?= esc((string) $sampleLabel) ?></th>
                    <?php endforeach; ?>
                    <th class="text-center catcmp-col-trend">Tendencia</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $samples = is_array($row['samples'] ?? null) ? $row['samples'] : [];
                    $trend = is_array($row['trend'] ?? null) ? $row['trend'] : ['symbol' => '', 'label' => ''];
                    ?>
                    <tr>
                        <td class="catcmp-col-param"><?= esc((string) ($row['parametro'] ?? '')) ?></td>
                        <?php foreach ($samples as $cell): ?>
                            <?php
                            $badgeClass = trim((string) ($cell['badge_class'] ?? 'catcmp-badge--default'));
                            $display = trim((string) ($cell['display'] ?? ''));
                            if ($display === '') {
                                $display = '—';
                            }
                            ?>
                            <td class="text-center catcmp-col-sample">
                                <span class="catcmp-badge <?= esc($badgeClass, 'attr') ?>"><?= esc($display) ?></span>
                            </td>
                        <?php endforeach; ?>
                        <td class="text-center catcmp-col-trend">
                            <span class="catcmp-trend-symbol" title="<?= esc((string) ($trend['label'] ?? ''), 'attr') ?>">
                                <?= esc((string) ($trend['symbol'] ?? '')) ?>
                            </span>
                            <?php if (trim((string) ($trend['label'] ?? '')) !== ''): ?>
                                <div class="catcmp-trend-label"><?= esc((string) $trend['label']) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <?php if ($legend !== []): ?>
        <div class="catcmp-legend" aria-label="Leyenda de colores">
            <div class="catcmp-legend-title">Leyenda</div>
            <div class="catcmp-legend-items">
                <?php foreach ($legend as $legendItem): ?>
                    <?php
                    $legendClass = trim((string) ($legendItem['class'] ?? 'catcmp-badge--default'));
                    $legendLabel = trim((string) ($legendItem['label'] ?? ''));
                    if ($legendLabel === '') {
                        continue;
                    }
                    ?>
                    <span class="catcmp-legend-item">
                        <span class="catcmp-badge <?= esc($legendClass, 'attr') ?>"><?= esc($legendLabel) ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
