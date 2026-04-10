<?php
/**
 * Un fragmento de contenido del PDF por tipo de elemento (cualquier sección).
 */
declare(strict_types=1);

helper('registro');

$type = (string) ($pdf_element_type ?? '');
$lab  = is_array($lab_config ?? null) ? $lab_config : [];
$ts   = \App\Services\ReportPdfLayoutService::normalizeTextStyle($pdf_text_style ?? []);

$labelsPdf = [
    'paciente_nombre'    => 'Paciente:',
    'paciente_genero'    => 'Género:',
    'paciente_edad'      => 'Edad:',
    'paciente_telefono'  => 'Teléfono:',
    'medico'             => 'Médico:',
    'fecha_recepcion'    => 'Fecha de recepción:',
    'fecha_reporte'      => 'Fecha de reporte:',
    'numero_orden'       => 'No. Orden:',
];

$reportEmitidoEl = (string) ($report_emitido_en ?? \App\Services\RegisterService::formatNowForReport());

$valueOf = static function (string $id) use ($paciente, $doctor, $register_info, $reportEmitidoEl): string {
    switch ($id) {
        case 'paciente_nombre':
            $nom = trim(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? ''));

            return $nom !== '' ? $nom : '—';
        case 'paciente_genero':
            return paciente_genero_texto($paciente);
        case 'paciente_edad':
            return (string) ($paciente->edad ?? '-');
        case 'paciente_telefono':
            return (string) ($paciente->phone_number ?? '-');
        case 'medico':
            $tit = ((int) ($doctor->gender ?? 0) === 1) ? 'Dr.' : 'Dra.';

            return $tit . ' ' . ($doctor->name ?? '-');
        case 'fecha_recepcion':
            $rec = (string) ($register_info->recepcion_fecha_hora ?? '');

            return $rec !== '' ? $rec : '—';
        case 'fecha_reporte':
            return $reportEmitidoEl;
        case 'numero_orden':
            return registro_orden_display($register_info);
        default:
            return '';
    }
};

$logoDataUri = (string) ($pdf_logo_data_uri ?? '');
if ($logoDataUri === '' && $type === 'logo') {
    $logoRel  = $lab['logo'] ?? 'images/logo-john.png';
    $logoPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);
    if (file_exists($logoPath)) {
        $logoData     = base64_encode((string) file_get_contents($logoPath));
        $finfo        = finfo_open(FILEINFO_MIME_TYPE);
        $mime         = $finfo ? finfo_file($finfo, $logoPath) : false;
        if ($finfo) {
            finfo_close($finfo);
        }
        $logoDataUri = 'data:' . ($mime ?: 'image/png') . ';base64,' . $logoData;
    }
}

if (isset($labelsPdf[$type])) {
    ?>
                <div class="patient-line">
                    <span class="label"><?= esc($labelsPdf[$type]) ?></span>
                    <?= esc($valueOf($type)) ?>
                </div>
    <?php

    return;
}

switch ($type) {
    case 'logo':
        ?>
                <div class="header-piece header-piece-logo">
                    <?php if ($logoDataUri !== ''): ?>
                        <img src="<?= $logoDataUri ?>" alt="Logo">
                    <?php else: ?>
                        <strong><?= esc($lab['company'] ?? 'Laboratorio') ?></strong>
                    <?php endif; ?>
                </div>
        <?php
        break;

    case 'lab_company':
        ?>
                <div class="header-piece header-piece-company">
                    <h1 style="color:<?= esc($ts['font_color'], 'attr') ?> !important;"><?= esc($lab['company'] ?? 'Laboratorio') ?></h1>
                </div>
        <?php
        break;

    case 'lab_address':
        if (! empty($lab['address'])) {
            ?>
                <div class="header-piece"><p><?= esc($lab['address']) ?></p></div>
            <?php
        }
        break;

    case 'lab_phone':
        if (! empty($lab['phone'])) {
            ?>
                <div class="header-piece"><p>Tel: <?= esc($lab['phone']) ?></p></div>
            <?php
        }
        break;

    case 'lab_email':
        if (! empty($lab['email'])) {
            ?>
                <div class="header-piece"><p>Email: <?= esc($lab['email']) ?></p></div>
            <?php
        }
        break;

    case 'lab_website':
        if (! empty($lab['website'])) {
            ?>
                <div class="header-piece"><p><?= esc($lab['website']) ?></p></div>
            <?php
        }
        break;

    case 'qr':
        if (! empty($report_url) && ! empty($qr_data_uri)) {
            ?>
                <div class="header-piece header-piece-qr">
                    <img src="<?= $qr_data_uri ?>" alt="QR" class="qr-img">
                    <p class="qr-label">Escanee para ver sus resultados online</p>
                </div>
            <?php
        }
        break;

    case 'footer_company':
        ?>
                <div class="footer-piece"><?= esc($lab['company'] ?? '') ?></div>
        <?php
        break;

    case 'footer_generated':
        ?>
                <div class="footer-piece">Resultados generados el <?= esc($reportEmitidoEl) ?></div>
        <?php
        break;

    case 'footer_policy':
        if (! empty($lab['return_policy'])) {
            ?>
                <div class="footer-piece footer-piece-policy"><small><?= esc($lab['return_policy']) ?></small></div>
            <?php
        }
        break;

    case 'lab_firmas_title':
        $lfS = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        $tit = (string) ($lfS['section_title'] ?? 'VALIDACIÓN Y APROBACIÓN');
        $shadowMap = [
            'none'   => 'none',
            'soft'   => '0.4px 0.4px 1px rgba(0,0,0,0.28)',
            'medium' => '0.7px 0.7px 1.4px rgba(0,0,0,0.35)',
            'strong' => '1px 1px 2px rgba(0,0,0,0.45)',
        ];
        $sh = $shadowMap[$ts['text_shadow']] ?? 'none';
        $titleStyle = 'margin:0;box-sizing:border-box;'
            . 'font-family:' . $ts['font_family'] . ';'
            . 'font-size:' . $ts['font_size_pt'] . 'pt;'
            . 'font-weight:' . $ts['font_weight'] . ';'
            . 'color:' . $ts['font_color'] . ';'
            . 'font-style:' . $ts['font_style'] . ';'
            . 'text-transform:' . $ts['text_transform'] . ';'
            . 'letter-spacing:' . $ts['letter_spacing_em'] . 'em;'
            . 'line-height:' . $ts['line_height'] . ';'
            . 'text-shadow:' . $sh . ';';
        ?>
                <div class="group-title pdf-lab-f-title" style="<?= esc($titleStyle, 'attr') ?>"><?= esc($tit) ?></div>
        <?php
        break;

    case 'lab_firmas_validator':
        $lfS = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        $fr  = is_array($pdf_firma_row ?? null) ? $pdf_firma_row : [];
        $nm  = trim((string) ($fr['validator_name'] ?? ''));
        ?>
                <div class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;margin-bottom:4px;"><?= esc($lfS['label_validator'] ?? 'Validado por:') ?></div>
                <div><?= esc($nm !== '' ? $nm : '—') ?></div>
        <?php
        break;

    case 'lab_firmas_seal':
        $lfS    = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        $fr     = is_array($pdf_firma_row ?? null) ? $pdf_firma_row : [];
        $seal   = trim((string) ($fr['approver_seal'] ?? ''));
        $sealUri = ($seal !== '') ? report_image_data_uri($seal) : '';
        ?>
                <div class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;margin-bottom:6px;"><?= esc($lfS['label_seal'] ?? 'Sello') ?></div>
                <?php if ($sealUri !== ''): ?>
                    <img src="<?= esc($sealUri, 'attr') ?>" alt="" class="pdf-lab-f-img" style="max-height:110px;max-width:100%;">
                <?php else: ?>
                    <div style="opacity:0.6;">—</div>
                <?php endif; ?>
        <?php
        break;

    case 'lab_firmas_approver':
        $lfS   = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        $fr    = is_array($pdf_firma_row ?? null) ? $pdf_firma_row : [];
        $sig   = trim((string) ($fr['approver_signature'] ?? ''));
        $sigUri = ($sig !== '') ? report_image_data_uri($sig) : '';
        $apNm  = trim((string) ($fr['approver_name'] ?? ''));
        $cargo = trim((string) ($fr['approver_cargo'] ?? ''));
        ?>
                <div class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;margin-bottom:6px;"><?= esc($lfS['label_approver'] ?? 'Aprobado por:') ?></div>
                <?php if ($sigUri !== ''): ?>
                    <div style="margin-bottom:8px;">
                        <img src="<?= esc($sigUri, 'attr') ?>" alt="" class="pdf-lab-f-img" style="max-height:72px;max-width:220px;">
                    </div>
                <?php endif; ?>
                <div style="font-weight:600;margin-top:4px;"><?= esc($apNm !== '' ? $apNm : '—') ?></div>
                <?php if ($cargo !== ''): ?>
                    <div style="margin-top:6px;opacity:0.85;font-size:0.95em;"><span style="opacity:0.75;"><?= esc($lfS['label_cargo'] ?? 'Cargo:') ?></span> <?= esc($cargo) ?></div>
                <?php endif; ?>
        <?php
        break;
}
