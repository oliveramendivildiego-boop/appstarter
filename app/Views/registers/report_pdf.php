<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados - <?= esc($paciente->first_name ?? '') ?> <?= esc($paciente->last_name_fa ?? '') ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; margin: 15px; color: #333; }
        .header { display: table; width: 100%; margin-bottom: 15px; border-bottom: 2px solid #0066cc; padding-bottom: 10px; }
        .logo-cell { display: table-cell; width: 120px; vertical-align: middle; }
        .logo-cell img { max-height: 70px; }
        .lab-info { display: table-cell; vertical-align: middle; padding-left: 15px; }
        .lab-info h1 { margin: 0; font-size: 14pt; color: #0066cc; }
        .lab-info p { margin: 3px 0; font-size: 9pt; }
        .patient-section { margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .patient-section .row { display: table; width: 100%; }
        .patient-section .col { display: table-cell; width: 50%; padding: 5px 10px 5px 0; vertical-align: top; }
        .patient-section .label { font-weight: bold; }
        table.results { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table.results th, table.results td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        table.results th { background: #0066cc; color: white; font-size: 9pt; }
        table.results td { font-size: 9pt; }
        .group-title { font-weight: bold; font-size: 11pt; margin: 15px 0 8px 0; color: #333; }
        .out-range { color: #c00; font-weight: bold; }
        .ref-range { font-size: 8pt; color: #666; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 8pt; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-cell">
            <?php
            $logoRel = $lab_config['logo'] ?? 'images/logo-john.png';
            $logoPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);
            $logoDataUri = '';
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $logoPath);
                finfo_close($finfo);
                $logoDataUri = 'data:' . ($mime ?: 'image/png') . ';base64,' . $logoData;
            }
            ?>
            <?php if ($logoDataUri): ?>
                <img src="<?= $logoDataUri ?>" alt="Logo" style="max-height:70px">
            <?php else: ?>
                <strong><?= esc($lab_config['company'] ?? 'Laboratorio') ?></strong>
            <?php endif; ?>
        </div>
        <div class="lab-info">
            <h1><?= esc($lab_config['company'] ?? 'Laboratorio') ?></h1>
            <?php if (!empty($lab_config['address'])): ?>
                <p><?= esc($lab_config['address']) ?></p>
            <?php endif; ?>
            <?php if (!empty($lab_config['phone'])): ?>
                <p>Tel: <?= esc($lab_config['phone']) ?></p>
            <?php endif; ?>
            <?php if (!empty($lab_config['email'])): ?>
                <p>Email: <?= esc($lab_config['email']) ?></p>
            <?php endif; ?>
            <?php if (!empty($lab_config['website'])): ?>
                <p><?= esc($lab_config['website']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="patient-section">
        <div class="row">
            <div class="col">
                <span class="label">Paciente:</span> <?= esc(trim(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? ''))) ?><br>
                <span class="label">Edad:</span> <?= esc($paciente->edad ?? '-') ?><br>
                <span class="label">Teléfono:</span> <?= esc($paciente->phone_number ?? '-') ?>
            </div>
            <div class="col">
                <span class="label">Médico:</span> <?= (($doctor->gender ?? 0) == 1) ? 'Dr.' : 'Dra.' ?> <?= esc($doctor->name ?? '-') ?><br>
                <span class="label">Fecha:</span> <?= esc($register_info->ingreso ?? '') ?><br>
                <span class="label">No. Orden:</span> <?= esc($register_info->registro_id ?? '') ?>
            </div>
        </div>
    </div>

<?php foreach ($grupos ?? [] as $padre => $items): ?>
    <?php
    $hasUnidad = false;
    $hasRango = false;
    foreach ($items as $it) {
        if (trim($it->umedida ?? '') !== '') $hasUnidad = true;
        if (trim($it->valor_min ?? '') !== '' || trim($it->valor_max ?? '') !== '') $hasRango = true;
    }
    ?>
    <div class="group-title"><?= esc($padre) ?> - <?= esc($items[0]->hijo ?? '') ?></div>
    <table class="results">
        <thead>
            <tr>
                <th>ANÁLISIS</th>
                <th style="text-align:center">RESULTADO</th>
                <?php if ($hasUnidad): ?><th style="text-align:center">UNID</th><?php endif; ?>
                <?php if ($hasRango): ?><th style="text-align:center">RANGO REFERENCIAL</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $valor = $item->regvalues ?? '-';
                $min = $item->valor_min ?? '';
                $max = $item->valor_max ?? '';
                $isOut = false;
                $valNorm = is_string($valor) ? trim(strtolower($valor)) : '';
                if (in_array($valNorm, ['positivo', 'reactivo'], true)) {
                    $isOut = true;
                } elseif ($valor !== '-' && $valor !== '' && is_numeric($valor) && $min !== '' && $max !== '') {
                    $v = (float) $valor;
                    $mn = (float) $min;
                    $mx = (float) $max;
                    $isOut = ($v < $mn || $v > $mx);
                }
                $refRange = trim($min . ' - ' . $max);
                if ($refRange === ' - ') $refRange = '-';
                ?>
                <tr>
                    <td><?= esc($item->nombre ?? '') ?></td>
                    <td style="text-align:center" class="<?= $isOut ? 'out-range' : '' ?>"><?= esc($valor) ?></td>
                    <?php if ($hasUnidad): ?><td style="text-align:center"><?= esc($item->umedida ?? '') ?></td><?php endif; ?>
                    <?php if ($hasRango): ?><td style="text-align:center" class="ref-range"><?= esc($refRange) ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>

    <div class="footer">
        <?= esc($lab_config['company'] ?? '') ?> - Resultados generados el <?= date('d/m/Y H:i') ?>
        <?php if (!empty($lab_config['return_policy'])): ?>
            <br><small><?= esc($lab_config['return_policy']) ?></small>
        <?php endif; ?>
    </div>
</body>
</html>
