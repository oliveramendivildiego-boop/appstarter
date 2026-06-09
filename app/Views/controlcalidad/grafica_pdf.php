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
        .chart-wrap img { max-width: 100%; height: auto; display: block; margin: 0 auto; }

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

$chartImageUri = \App\Libraries\QcLeveyJenningsChartImage::toPngDataUri($valores ?? [], $qc);
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
    <?php if ($chartImageUri === null || $chartImageUri === ''): ?>
        <p class="muted">Agregue valores en el rango o amplíe las fechas para ver la gráfica.</p>
    <?php else: ?>
        <div class="chart-wrap">
            <img src="<?= $chartImageUri ?>" alt="Gráfica Levey-Jennings">
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
