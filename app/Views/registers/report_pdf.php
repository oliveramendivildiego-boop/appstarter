<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados - <?= esc($paciente->first_name ?? '') ?> <?= esc($paciente->last_name_fa ?? '') ?></title>
    <base href="<?= base_url() ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/report_pdf.css') ?>" />
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
                <img src="<?= $logoDataUri ?>" alt="Logo">
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
        <?php if (!empty($report_url) && !empty($qr_data_uri)): ?>
        <div class="qr-cell">
            <img src="<?= $qr_data_uri ?>" alt="Ver resultados online" class="qr-img">
            <p class="qr-label">Escanee para ver sus resultados online</p>
        </div>
        <?php endif; ?>
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
                <span class="label">No. Orden:</span> <?= esc(registro_orden_display($register_info)) ?>
            </div>
        </div>
    </div>

<?php foreach ($grupos ?? [] as $padre => $items): ?>
    <?php
    $conResultado = [];
    $sinResultado = [];
    foreach ($items as $it) {
        $valTmp = trim((string)($it->regvalues ?? ''));
        if ($valTmp === '' || $valTmp === '-') $sinResultado[] = $it;
        else $conResultado[] = $it;
    }
    ?>
    <div class="group-title"><?= esc($padre) ?> - <?= esc($items[0]->hijo ?? '') ?></div>
    <table class="results">
        <thead>
            <tr>
                <th>ANÁLISIS</th>
                <th class="text-center">RESULTADO</th>
                <th class="text-center">RANGO REFERENCIAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($conResultado as $item): ?>
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
                $resMostrar = registro_resultado_con_unidad($item->regvalues ?? '', $item->umedida ?? '');
                $refRange = registro_rango_referencial_texto($min, $max, $item->umedida ?? '');
                ?>
                <tr>
                    <td><?= esc($item->nombre ?? '') ?></td>
                    <td class="text-center <?= $isOut ? 'out-range' : '' ?>"><?= esc($resMostrar) ?></td>
                    <td class="text-center ref-range"><?= esc($refRange) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!empty($sinResultado)): ?>
    <table class="results" style="margin-top:8px;">
        <thead>
            <tr>
                <th>ANÁLISIS</th>
                <th class="text-center">RANGO REFERENCIAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sinResultado as $item): ?>
                <?php
                $refRange = registro_rango_referencial_texto($item->valor_min ?? '', $item->valor_max ?? '', $item->umedida ?? '');
                if ($refRange === '-' && trim((string)($item->umedida ?? '')) === '' && !((bool)($item->show_reference ?? false))) {
                    continue;
                }
                ?>
                    <tr>
                        <td><?= esc($item->nombre ?? '') ?></td>
                        <td class="text-center ref-range"><?= esc($refRange) ?></td>
                    </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
<?php endforeach; ?>

<?php $notaResultado = trim((string)($register_info->comentario_resultado ?? '')); ?>
<?php if ($notaResultado !== ''): ?>
    <div class="group-title" style="margin-top: 10px;">NOTAS</div>
    <table class="results">
        <tbody>
            <tr>
                <td style="white-space: pre-wrap;"><?= esc($notaResultado) ?></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>

    <div class="footer">
        <?= esc($lab_config['company'] ?? '') ?> - Resultados generados el <?= date('d/m/Y H:i') ?>
        <?php if (!empty($lab_config['return_policy'])): ?>
            <br><small><?= esc($lab_config['return_policy']) ?></small>
        <?php endif; ?>
    </div>
</body>
</html>
