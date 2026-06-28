<?php
/**
 * Curva de tolerancia (p. ej. glucosa) — SVG embebido compatible con PDF (mPDF/Dompdf).
 *
 * @var array<string, mixed> $chart
 * @var string               $variant
 * @var bool                 $use_pdf_chrome
 */
$chart = is_array($chart ?? null) ? $chart : [];
$points = is_array($chart['points'] ?? null) ? $chart['points'] : [];
$usePdfChrome = ! empty($use_pdf_chrome);
$variant = (string) ($variant ?? 'web');

if ($points === []) {
    return;
}

helper('registro');

$pointColors = static function (array $pt): array {
    $value = (float) ($pt['value'] ?? 0);
    $refMin = (float) ($pt['ref_min'] ?? 0);
    $refMax = (float) ($pt['ref_max'] ?? 0);
    $interp = registro_interpretacion_referencial_desde_rango($value, $value, $refMin, $refMax);

    return match ($interp['nivel'] ?? 'normal') {
        'alto' => ['color' => '#dc3545', 'nivel' => 'alto'],
        'bajo' => ['color' => '#e67700', 'nivel' => 'bajo'],
        default => ['color' => '#0d6efd', 'nivel' => 'normal'],
    };
};

$hasAlto = false;
$hasBajo = false;
foreach ($points as $pt) {
    $nivel = $pointColors($pt)['nivel'];
    $hasAlto = $hasAlto || $nivel === 'alto';
    $hasBajo = $hasBajo || $nivel === 'bajo';
}

$unit = trim((string) ($chart['unit'] ?? 'mg/dL'));
$title = trim((string) ($chart['title'] ?? 'Curva de tolerancia a la glucosa'));
$yMin = (float) ($chart['y_min'] ?? 0);
$yMax = (float) ($chart['y_max'] ?? 200);
$ySpan = max(1.0, $yMax - $yMin);

$svgW = 540;
$svgH = 260;
$padL = 48;
$padR = 40;
$padT = 28;
$padB = 40;
$plotW = $svgW - $padL - $padR;
$plotH = $svgH - $padT - $padB;
$count = count($points);
$lastIdx = max(0, $count - 1);
$xInset = $plotW * 0.07;

$xAt = static function (int $idx) use ($count, $padL, $plotW, $xInset): float {
    if ($count <= 1) {
        return (float) $padL + $plotW / 2.0;
    }
    $innerW = max(1.0, $plotW - (2.0 * $xInset));

    return (float) $padL + $xInset + ($innerW * $idx / ($count - 1));
};

$yAt = static function (float $value) use ($yMin, $ySpan, $padT, $plotH): float {
    $ratio = ($value - $yMin) / $ySpan;

    return (float) $padT + $plotH - ($ratio * $plotH);
};

$patientPoly = [];
foreach ($points as $idx => $pt) {
    $x = $xAt($idx);
    $patientPoly[] = round($x, 1) . ',' . round($yAt((float) $pt['value']), 1);
}

$gridLines = [];
$step = $ySpan <= 80 ? 20.0 : ($ySpan <= 160 ? 40.0 : 50.0);
$gridStart = ceil($yMin / $step) * $step;
for ($gv = $gridStart; $gv <= $yMax; $gv += $step) {
    $gy = round($yAt($gv), 1);
    $gridLines[] = [
        'y'     => $gy,
        'label' => (string) (int) round($gv),
    ];
}

$wrapClass = $usePdfChrome ? 'report-segment-table-wrap tol-chart-wrap' : 'table-responsive mb-3 tol-chart-wrap';
$titleClass = $usePdfChrome
    ? 'report-segment-title pdf-card-header tol-chart-title'
    : 'report-segment-title-web px-2 py-2 mb-2 bg-secondary bg-opacity-10 border-start border-4 border-secondary rounded-end fw-semibold text-uppercase small tol-chart-title';
?>
<div class="<?= esc($wrapClass, 'attr') ?>">
    <div class="<?= esc($titleClass, 'attr') ?>"><?= esc($title) ?></div>
    <div class="tol-chart-body">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= (int) $svgW ?> <?= (int) $svgH ?>" class="tol-chart-svg" role="img" aria-label="<?= esc($title, 'attr') ?>">
        <rect x="0" y="0" width="<?= (int) $svgW ?>" height="<?= (int) $svgH ?>" fill="#ffffff"/>
        <?php foreach ($gridLines as $gl): ?>
            <line x1="<?= (int) $padL ?>" y1="<?= (float) $gl['y'] ?>" x2="<?= (int) ($svgW - $padR) ?>" y2="<?= (float) $gl['y'] ?>" stroke="#e9ecef" stroke-width="1"/>
            <text x="<?= (int) ($padL - 6) ?>" y="<?= (float) ($gl['y'] + 3) ?>" text-anchor="end" font-size="9" fill="#6c757d" font-family="DejaVu Sans, sans-serif"><?= esc($gl['label']) ?></text>
        <?php endforeach; ?>
        <line x1="<?= (int) $padL ?>" y1="<?= (int) $padT ?>" x2="<?= (int) $padL ?>" y2="<?= (int) ($padT + $plotH) ?>" stroke="#adb5bd" stroke-width="1"/>
        <line x1="<?= (int) $padL ?>" y1="<?= (int) ($padT + $plotH) ?>" x2="<?= (int) ($svgW - $padR) ?>" y2="<?= (int) ($padT + $plotH) ?>" stroke="#adb5bd" stroke-width="1"/>
        <?php foreach ($points as $idx => $pt): ?>
            <?php
            $x = $xAt($idx);
            $yRefMin = $yAt((float) $pt['ref_min']);
            $yRefMax = $yAt((float) $pt['ref_max']);
            ?>
            <line x1="<?= round($x, 1) ?>" y1="<?= round($yRefMin, 1) ?>" x2="<?= round($x, 1) ?>" y2="<?= round($yRefMax, 1) ?>" stroke="#198754" stroke-width="1.5" stroke-dasharray="4,3"/>
        <?php endforeach; ?>
        <polyline points="<?= esc(implode(' ', $patientPoly), 'attr') ?>" fill="none" stroke="#0d6efd" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/>
        <?php foreach ($points as $idx => $pt): ?>
            <?php
            $x = $xAt($idx);
            $yVal = $yAt((float) $pt['value']);
            $style = $pointColors($pt);
            $pointColor = $style['color'];
            $valNum = (float) $pt['value'];
            $displayVal = abs($valNum - round($valNum)) < 0.05
                ? (string) (int) round($valNum)
                : rtrim(rtrim(number_format($valNum, 1, '.', ''), '0'), '.');
            $labelText = $unit !== '' ? $displayVal . ' ' . $unit : $displayVal;
            $labelLen = max(4, strlen($labelText));
            $labelW = min(84.0, max(38.0, $labelLen * 5.8 + 12.0));
            $labelH = 16.0;
            $edgeMargin = 4.0;

            if ($idx === $lastIdx) {
                $labelX = $x - $labelW - 6.0;
            } elseif ($idx === 0) {
                $labelX = $x + 6.0;
            } else {
                $labelX = $x - ($labelW / 2.0);
            }

            $labelY = $yVal - 26.0;
            if ($labelY < $padT + 2.0) {
                $labelY = $yVal + 14.0;
            }

            if ($labelX + $labelW > $svgW - $edgeMargin) {
                $labelX = $svgW - $edgeMargin - $labelW;
            }
            if ($labelX < $edgeMargin) {
                $labelX = $edgeMargin;
            }

            $textX = $labelX + ($labelW / 2.0);
            $textY = $labelY + ($labelH / 2.0) + 3.5;
            $axisLabelX = max($padL + 6.0, min($svgW - $padR - 6.0, $x));
            ?>
            <circle cx="<?= round($x, 1) ?>" cy="<?= round($yVal, 1) ?>" r="5.5" fill="<?= esc($pointColor, 'attr') ?>" stroke="#ffffff" stroke-width="2"/>
            <rect x="<?= round($labelX, 1) ?>" y="<?= round($labelY, 1) ?>" width="<?= round($labelW, 1) ?>" height="<?= round($labelH, 1) ?>" rx="3" ry="3" fill="#ffffff" stroke="<?= esc($pointColor, 'attr') ?>" stroke-width="1.2"/>
            <text x="<?= round($textX, 1) ?>" y="<?= round($textY, 1) ?>" text-anchor="middle" font-size="9.5" font-weight="700" fill="<?= esc($pointColor, 'attr') ?>" font-family="DejaVu Sans, sans-serif"><?= esc($labelText) ?></text>
            <text x="<?= round($axisLabelX, 1) ?>" y="<?= (int) ($padT + $plotH + 18) ?>" text-anchor="middle" font-size="9" fill="#495057" font-family="DejaVu Sans, sans-serif"><?= esc((string) $pt['label']) ?></text>
        <?php endforeach; ?>
        <text x="<?= (int) ($padL - 28) ?>" y="<?= (int) ($padT + $plotH / 2) ?>" text-anchor="middle" font-size="8" fill="#6c757d" font-family="DejaVu Sans, sans-serif" transform="rotate(-90, <?= (int) ($padL - 28) ?>, <?= (int) ($padT + $plotH / 2) ?>)"><?= esc($unit) ?></text>
    </svg>
    </div>
    <div class="tol-chart-legend" aria-label="Leyenda">
        <span class="tol-chart-legend-item"><span class="tol-chart-legend-swatch tol-chart-legend-swatch--patient"></span> Dentro de rango</span>
        <?php if ($hasAlto): ?>
        <span class="tol-chart-legend-item"><span class="tol-chart-legend-swatch tol-chart-legend-swatch--alto"></span> Por encima del rango</span>
        <?php endif; ?>
        <?php if ($hasBajo): ?>
        <span class="tol-chart-legend-item"><span class="tol-chart-legend-swatch tol-chart-legend-swatch--bajo"></span> Por debajo del rango</span>
        <?php endif; ?>
        <span class="tol-chart-legend-item"><span class="tol-chart-legend-swatch tol-chart-legend-swatch--ref"></span> Rango referencial normal</span>
    </div>
</div>
