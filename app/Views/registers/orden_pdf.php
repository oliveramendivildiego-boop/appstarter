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
        .orden-pruebas-items { list-style: none; padding: 0; margin: 0; text-align: left; }
        .orden-prueba-item { display: flex; align-items: baseline; margin: 0 0 2px; padding: 0; text-align: left; line-height: 1.35; }
        .orden-prueba-num { flex: 0 0 auto; margin-right: 0.2em; }
        .orden-prueba-nombre { flex: 1 1 auto; min-width: 0; }
        .orden-prueba-costo { flex: 0 0 auto; margin-left: auto; white-space: nowrap; text-align: right; }
        .orden-prueba-block { margin: 0 0 6px; padding: 0; list-style: none; }
        .orden-prueba-ficha { margin: 2px 0 0 14px; padding: 4px 6px; border-left: 2px solid #0aa2c0; background: #f7fcfd; font-size: 10px; }
        .orden-prueba-ficha-titulo { font-weight: 700; font-size: 9px; color: #0a6ebd; margin-bottom: 3px; }
        .orden-prueba-ficha-body .report-cultivo-seccion { margin-bottom: 4px !important; }
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

    <?= view('registers/orden_pruebas_block', [
        'pruebas_en_orden'      => $pruebas_en_orden ?? [],
        'total_pruebas'         => (int) ($total_pruebas ?? 0),
        'show_order_costs'      => !empty($show_order_costs),
        'costos_por_id'         => $costos_por_id ?? [],
        'pago'                  => $pago ?? null,
        'abonos'                => $abonos ?? [],
        'tipo_pago_nombre'      => $tipo_pago_nombre ?? '-',
        'fichas_clinicas_orden' => $fichas_clinicas_orden ?? [],
        'for_pdf'               => true,
    ]) ?>
</body>
</html>
