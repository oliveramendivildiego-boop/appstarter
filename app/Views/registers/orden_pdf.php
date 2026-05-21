<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Hoja de trabajo</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        .row { width: 100%; }
        .muted { color: #666; }
        .meta { margin-bottom: 10px; }
        .meta div { margin: 2px 0; }
        .section { margin-top: 12px; }
        .section-title { font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 6px; }
        ul { margin: 0; padding-left: 18px; }
        li { margin: 2px 0; }
        .orden-header { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .orden-header td { vertical-align: top; padding: 0; }
        .orden-header .lab-info { width: 38%; font-size: 11px; line-height: 1.4; }
        .orden-header .lab-name { font-weight: 700; font-size: 13px; margin-bottom: 4px; }
        .orden-header .title-cell { width: 24%; text-align: center; vertical-align: middle; }
        .orden-header .title-cell h1 {
            font-size: 16px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .orden-header .spacer { width: 38%; }
    </style>
</head>
<body>
    <?php
    helper('registro');
    $labCfg = is_array($lab_config ?? null) ? $lab_config : [];
    $labCompany = trim((string) ($labCfg['company'] ?? 'Laboratorio'));
    $labAddress = trim((string) ($labCfg['address'] ?? ''));
    $labPhone = trim((string) ($labCfg['phone'] ?? ''));
    $codigoOrden = registro_orden_display($register_info);
    $telefonoPaciente = trim((string) ($register_info->phone_number ?? ''));
    $direccionPaciente = trim((string) ($register_info->address_1 ?? ''));
    $doctorOrdenPdf = trim((string) ($register_info->doctor_name ?? ''));
    if ($doctorOrdenPdf === '') {
        $doctorOrdenPdf = trim((string) ($register_info->doctor ?? ''));
    }
    if ($doctorOrdenPdf === '') {
        $doctorOrdenPdf = isset($label_sin_doctor) ? trim((string) $label_sin_doctor) : 'Sin doctor';
        if ($doctorOrdenPdf === '') {
            $doctorOrdenPdf = 'Sin doctor';
        }
    }
    ?>
    <table class="orden-header">
        <tr>
            <td class="lab-info">
                <div class="lab-name"><?= esc($labCompany) ?></div>
                <?php if ($labAddress !== ''): ?>
                    <div><?= esc($labAddress) ?></div>
                <?php endif; ?>
                <?php if ($labPhone !== ''): ?>
                    <div><strong>Tel.</strong> <?= esc($labPhone) ?></div>
                <?php endif; ?>
            </td>
            <td class="title-cell">
                <h1>Hoja de trabajo</h1>
            </td>
            <td class="spacer"></td>
        </tr>
    </table>

    <div class="meta">
        <div><strong>Orden:</strong> <?= esc($codigoOrden) ?></div>
        <div><strong>Fecha:</strong> <?= esc($fecha ?? '') ?></div>
        <div><strong>Dirección:</strong> <?= esc($direccionPaciente !== '' ? $direccionPaciente : '-') ?></div>
        <div><strong>Paciente:</strong> <?= esc(($register_info->first_name ?? '') . ' ' . ($register_info->last_name_fa ?? '') . ' ' . ($register_info->last_name_mom ?? '')) ?></div>
        <div><strong>Edad:</strong> <?= esc($edad_paciente_orden ?? '-') ?></div>
        <?php if ($telefonoPaciente !== ''): ?>
            <div><strong>Tel. paciente:</strong> <?= esc($telefonoPaciente) ?></div>
        <?php endif; ?>
        <div><strong>Doctor:</strong> <?= esc($doctorOrdenPdf) ?></div>
    </div>

    <?php if (!empty($grupos_pruebas ?? [])): ?>
        <?php foreach (($grupos_pruebas ?? []) as $padre => $items): ?>
            <div class="section">
                <div class="section-title"><?= esc($padre) ?></div>
                <ul>
                    <?php foreach (($items ?? []) as $it): ?>
                        <li><?= esc($it['hijo'] ?? '') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="muted">No hay pruebas para esta orden.</p>
    <?php endif; ?>
</body>
</html>
