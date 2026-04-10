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
    'paciente_edad'      => 'Edad:',
    'paciente_telefono'  => 'Teléfono:',
    'medico'             => 'Médico:',
    'numero_orden'       => 'No. Orden:',
];

$reportEmitidoEl = (string) ($report_emitido_en ?? \App\Services\RegisterService::formatNowForReport());

$valueOf = static function (string $id) use ($paciente, $doctor, $register_info): string {
    switch ($id) {
        case 'paciente_edad':
            return (string) ($paciente->edad ?? '-');
        case 'paciente_telefono':
            return (string) ($paciente->phone_number ?? '-');
        case 'medico':
            $tit = ((int) ($doctor->gender ?? 0) === 1) ? 'Dr.' : 'Dra.';

            return $tit . ' ' . ($doctor->name ?? '-');
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

if ($type === 'paciente_nombre') {
    $nom = trim(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? ''));
    ?>
                <div class="patient-line">
                    <span class="label">Paciente:</span>
                    <?= esc($nom !== '' ? $nom : '—') ?>
                </div>
                <div class="patient-line">
                    <span class="label">Género:</span>
                    <?= esc(paciente_genero_texto($paciente)) ?>
                </div>
    <?php

    return;
}

if ($type === 'fecha_ingreso') {
    $rec = (string) ($register_info->recepcion_fecha_hora ?? '');
    ?>
                <div class="patient-line">
                    <span class="label">Fecha de recepción:</span>
                    <?= esc($rec !== '' ? $rec : '—') ?>
                </div>
                <div class="patient-line">
                    <span class="label">Fecha de reporte:</span>
                    <?= esc($reportEmitidoEl) ?>
                </div>
    <?php

    return;
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
}
