<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de calidad — <?= esc($control['nombre'] ?? '') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            padding: 22px 28px 28px;
            line-height: 1.45;
        }
        .sheet { max-width: 100%; }

        .header-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .header-wrap td { vertical-align: middle; padding: 0; }
        .logo-box { width: 32%; padding-right: 16px; }
        .logo-box img {
            max-height: 68px;
            max-width: 200px;
            width: auto;
            height: auto;
            display: block;
        }
        .logo-placeholder {
            width: 68px;
            height: 68px;
            background: #0d9488;
            border-radius: 10px;
            color: #fff;
            font-size: 22pt;
            font-weight: bold;
            text-align: center;
            line-height: 68px;
        }
        .company-block { padding-left: 8px; }
        .company-name {
            font-size: 17pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }
        .company-line {
            font-size: 8.5pt;
            color: #64748b;
            margin: 2px 0;
        }

        .accent-line {
            height: 4px;
            background: #0d9488;
            margin: 14px 0 18px;
            border-radius: 2px;
        }

        .doc-title-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .doc-title-row td { vertical-align: middle; }
        .doc-badge {
            background: #0f766e;
            color: #fff;
            font-size: 11pt;
            font-weight: bold;
            padding: 10px 16px;
            letter-spacing: 0.04em;
        }
        .doc-meta {
            text-align: right;
            font-size: 9pt;
            color: #64748b;
        }
        .doc-meta strong { color: #334155; }

        .section-title {
            font-size: 10.5pt;
            font-weight: bold;
            color: #0f766e;
            margin: 14px 0 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #99f6e4;
        }

        .two-cols {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .two-cols td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px 0 0;
        }
        .two-cols td:last-child { padding: 0 0 0 10px; }

        .panel {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 12px;
            background: #f8fafc;
            min-height: 120px;
        }
        .panel h3 {
            font-size: 9.5pt;
            color: #0f766e;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .stat-list { list-style: none; font-size: 9pt; }
        .stat-list li { margin-bottom: 5px; }
        .stat-list strong { color: #334155; }

        .westgard-ok { color: #15803d; font-size: 9pt; }
        .westgard-warn { color: #b91c1c; font-size: 9pt; }
        .westgard-warn li { margin-bottom: 4px; }

        .muted { color: #64748b; font-size: 9pt; }

        .chart-wrap {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            margin-top: 6px;
            background: #fff;
            text-align: center;
        }
        .chart-wrap svg { max-width: 100%; height: auto; }

        .legend {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 6px;
            text-align: center;
        }
        .legend span { margin: 0 8px; }
        .lg-measured { color: #198754; }
        .lg-mean { color: #0d6efd; }
        .lg-sd1 { color: #6c757d; }
        .lg-sd2 { color: #d39e00; }
        .lg-sd3 { color: #dc3545; }
    </style>
</head>
<body>
<?php
helper('layout');
$layoutCfg = layout_config();

$qc = $qc_stats ?? null;
$n = (int) ($qc['n'] ?? 0);
$fmt = static function ($x, int $dec = 4): string {
    if ($x === null) {
        return '—';
    }
    return number_format((float) $x, $dec, ',', '');
};

$logoRel = $layoutCfg['logo'] ?? 'images/logo-john.png';
$logoPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);
$pdf_logo_data_uri = '';
if (is_file($logoPath)) {
    $logoData = base64_encode((string) file_get_contents($logoPath));
    $finfo    = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $mime     = $finfo ? finfo_file($finfo, $logoPath) : false;
    if ($finfo) {
        finfo_close($finfo);
    }
    $pdf_logo_data_uri = 'data:' . ($mime ?: 'image/png') . ';base64,' . $logoData;
}

$company = (string) ($lab_config['company'] ?? $layoutCfg['company'] ?? 'Laboratorio');
$initial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($company, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($company, 0, 1));

$controlNombre = (string) ($control['nombre'] ?? 'Control');
$tipoLabel = ((int) ($control['tipo'] ?? 1) === 2) ? 'Externo' : 'Interno';

$chartSvg = '';
if ($qc !== null && $n > 0) {
    $chartW = 680;
    $chartH = 280;
    $padL = 52;
    $padR = 18;
    $padT = 22;
    $padB = 48;

    $points = [];
    foreach ($valores ?? [] as $v) {
        $points[] = [
            'fecha'    => (string) ($v['fecha'] ?? ''),
            'valor'    => (float) ($v['valor'] ?? 0),
            'esperado' => isset($v['esperado']) && $v['esperado'] !== null && $v['esperado'] !== ''
                ? (float) $v['esperado']
                : null,
        ];
    }

    $mean = (float) $qc['mean'];
    $sd   = (float) ($qc['sd'] ?? 0);
    $yVals = array_map(static fn ($p) => $p['valor'], $points);
    $yVals[] = $mean;
    if ($sd > 0) {
        for ($k = -3; $k <= 3; $k++) {
            $yVals[] = $mean + ($k * $sd);
        }
    }
    foreach ($points as $p) {
        if ($p['esperado'] !== null) {
            $yVals[] = $p['esperado'];
        }
    }

    $yMin = min($yVals);
    $yMax = max($yVals);
    $span = $yMax - $yMin;
    $margin = $span > 0 ? $span * 0.08 : 1.0;
    $yMin -= $margin;
    $yMax += $margin;
    if ($yMax <= $yMin) {
        $yMax = $yMin + 1.0;
    }

    $plotW = $chartW - $padL - $padR;
    $plotH = $chartH - $padT - $padB;
    $count = count($points);

    $yToPx = static function (float $y) use ($yMin, $yMax, $plotH, $padT): float {
        return $padT + $plotH * (1.0 - (($y - $yMin) / ($yMax - $yMin)));
    };
    $xToPx = static function (int $i) use ($count, $padL, $plotW): float {
        if ($count <= 1) {
            return $padL + ($plotW / 2);
        }
        return $padL + ($i / ($count - 1)) * $plotW;
    };

    $lines = [];
    if ($sd > 0) {
        $specs = [
            ['y' => $mean, 'color' => '#0d6efd', 'dash' => '', 'w' => 2],
            ['y' => $mean + $sd, 'color' => '#6c757d', 'dash' => '4,4', 'w' => 1],
            ['y' => $mean - $sd, 'color' => '#6c757d', 'dash' => '4,4', 'w' => 1],
            ['y' => $mean + 2 * $sd, 'color' => '#d39e00', 'dash' => '6,3', 'w' => 1],
            ['y' => $mean - 2 * $sd, 'color' => '#d39e00', 'dash' => '6,3', 'w' => 1],
            ['y' => $mean + 3 * $sd, 'color' => '#dc3545', 'dash' => '2,3', 'w' => 1],
            ['y' => $mean - 3 * $sd, 'color' => '#dc3545', 'dash' => '2,3', 'w' => 1],
        ];
        foreach ($specs as $spec) {
            $yy = $yToPx((float) $spec['y']);
            $dash = $spec['dash'] !== '' ? ' stroke-dasharray="' . $spec['dash'] . '"' : '';
            $lines[] = sprintf(
                '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="%s"%s/>',
                $padL,
                $yy,
                $padL + $plotW,
                $yy,
                $spec['color'],
                $spec['w'],
                $dash
            );
        }
    } else {
        $yy = $yToPx($mean);
        $lines[] = sprintf(
            '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#0d6efd" stroke-width="2"/>',
            $padL,
            $yy,
            $padL + $plotW,
            $yy
        );
    }

    $poly = [];
    $dots = [];
    foreach ($points as $i => $p) {
        $x = $xToPx($i);
        $y = $yToPx($p['valor']);
        $poly[] = round($x, 1) . ',' . round($y, 1);
        $dots[] = sprintf('<circle cx="%s" cy="%s" r="3.5" fill="#198754" stroke="#fff" stroke-width="1"/>', round($x, 1), round($y, 1));
    }

    $esperadoSegs = [];
    $seg = [];
    foreach ($points as $i => $p) {
        if ($p['esperado'] === null) {
            if ($seg !== []) {
                $esperadoSegs[] = $seg;
                $seg = [];
            }
            continue;
        }
        $seg[] = ['x' => $xToPx($i), 'y' => $yToPx($p['esperado'])];
    }
    if ($seg !== []) {
        $esperadoSegs[] = $seg;
    }
    $esperadoLines = [];
    foreach ($esperadoSegs as $seg) {
        if (count($seg) < 2) {
            continue;
        }
        $pts = array_map(static fn ($s) => round($s['x'], 1) . ',' . round($s['y'], 1), $seg);
        $esperadoLines[] = '<polyline fill="none" stroke="#d63384" stroke-width="1.5" stroke-dasharray="5,5" points="' . implode(' ', $pts) . '"/>';
    }

    $yTicks = [];
    for ($t = 0; $t <= 4; $t++) {
        $val = $yMin + (($yMax - $yMin) * $t / 4);
        $yy = $yToPx($val);
        $yTicks[] = sprintf(
            '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#e2e8f0" stroke-width="1"/>',
            $padL,
            $yy,
            $padL + $plotW,
            $yy
        );
        $yTicks[] = sprintf(
            '<text x="%s" y="%s" font-size="7" fill="#64748b" text-anchor="end">%s</text>',
            $padL - 6,
            $yy + 3,
            $fmt($val)
        );
    }

    $xLabels = [];
    $step = max(1, (int) ceil($count / 8));
    foreach ($points as $i => $p) {
        if ($i % $step !== 0 && $i !== $count - 1) {
            continue;
        }
        $x = $xToPx($i);
        $xLabels[] = sprintf(
            '<text x="%s" y="%s" font-size="6.5" fill="#64748b" text-anchor="middle" transform="rotate(-35 %s %s)">%s</text>',
            $x,
            $chartH - 8,
            $x,
            $chartH - 8,
            esc($p['fecha'])
        );
    }

    $chartSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $chartW . '" height="' . $chartH . '" viewBox="0 0 ' . $chartW . ' ' . $chartH . '">'
        . '<rect x="' . $padL . '" y="' . $padT . '" width="' . $plotW . '" height="' . $plotH . '" fill="#fafafa" stroke="#e2e8f0"/>'
        . implode('', $yTicks)
        . implode('', $lines)
        . implode('', $esperadoLines)
        . '<polyline fill="none" stroke="#198754" stroke-width="2" points="' . implode(' ', $poly) . '"/>'
        . implode('', $dots)
        . implode('', $xLabels)
        . '</svg>';
}
?>
<div class="sheet">
    <table class="header-wrap">
        <tr>
            <td class="logo-box">
                <?php if ($pdf_logo_data_uri !== ''): ?>
                    <img src="<?= $pdf_logo_data_uri ?>" alt="">
                <?php else: ?>
                    <div class="logo-placeholder"><?= esc($initial) ?></div>
                <?php endif; ?>
            </td>
            <td class="company-block">
                <div class="company-name"><?= esc($company) ?></div>
                <?php
                $sucursal = trim((string) ($lab_config['sucursal'] ?? $layoutCfg['sucursal'] ?? ''));
                if ($sucursal !== ''): ?>
                    <p class="company-line"><?= esc($sucursal) ?></p>
                <?php endif; ?>
                <?php if (!empty($lab_config['address'])): ?>
                    <p class="company-line"><?= esc($lab_config['address']) ?></p>
                <?php endif; ?>
                <?php if (!empty($lab_config['phone'])): ?>
                    <p class="company-line">Tel. <?= esc($lab_config['phone']) ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <div class="accent-line"></div>

    <table class="doc-title-row">
        <tr>
            <td>
                <span class="doc-badge">CONTROL DE CALIDAD &nbsp;·&nbsp; LEVEY-JENNINGS</span>
            </td>
            <td class="doc-meta">
                <strong><?= esc($controlNombre) ?></strong> (<?= esc($tipoLabel) ?>)<br>
                Periodo: <?= esc($fecha_ini ?? '') ?> — <?= esc($fecha_fin ?? '') ?><br>
                Generado: <?= esc($generado_en ?? '') ?>
            </td>
        </tr>
    </table>

    <table class="two-cols">
        <tr>
            <td>
                <div class="panel">
                    <h3>Estadísticos del periodo (n = <?= $n ?>)</h3>
                    <?php if ($qc === null): ?>
                        <p class="muted">No hay valores en el rango de fechas seleccionado.</p>
                    <?php else: ?>
                        <ul class="stat-list">
                            <li><strong>Media (x̄):</strong> <?= $fmt($qc['mean']) ?></li>
                            <li><strong>Desviación estándar (DE):</strong> <?= $fmt($qc['sd']) ?></li>
                            <li><strong>Coeficiente de variación (CV%):</strong>
                                <?= $qc['cv_percent'] !== null ? $fmt($qc['cv_percent'], 2) . ' %' : '—' ?>
                            </li>
                            <?php if (isset($control['sesgo']) && $control['sesgo'] !== null && $control['sesgo'] !== ''): ?>
                                <li><strong>Sesgo (asignado):</strong> <?= $fmt((float) $control['sesgo']) ?></li>
                            <?php else: ?>
                                <li class="muted"><strong>Sesgo (asignado):</strong> sin valor</li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <div class="panel">
                    <h3>Evaluación Westgard</h3>
                    <?php if ($qc === null || ($qc['sd'] ?? 0) <= 0): ?>
                        <p class="muted">Se requiere al menos 2 mediciones con variación para aplicar las reglas.</p>
                    <?php elseif (empty($westgard ?? [])): ?>
                        <p class="westgard-ok">No se detectaron violaciones de las reglas 1-3s, 2-2s, R-4s, 4-1s ni 10x en el periodo analizado.</p>
                    <?php else: ?>
                        <ul class="westgard-warn">
                            <?php foreach ($westgard as $msg): ?>
                                <li><?= $msg ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Gráfica de Levey-Jennings</div>
    <?php if ($chartSvg === ''): ?>
        <p class="muted">Agregue valores en el rango o amplíe las fechas para ver la gráfica.</p>
    <?php else: ?>
        <div class="chart-wrap">
            <?= $chartSvg ?>
        </div>
        <div class="legend">
            <span class="lg-measured">■ Valor medido</span>
            <span class="lg-mean">— Media</span>
            <?php if ($qc !== null && ($qc['sd'] ?? 0) > 0): ?>
                <span class="lg-sd1">- - ±1 DE</span>
                <span class="lg-sd2">- - ±2 DE</span>
                <span class="lg-sd3">- - ±3 DE</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
