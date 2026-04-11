<?php
/**
 * Un fragmento de contenido del PDF por tipo de elemento (cualquier sección).
 */
declare(strict_types=1);

helper('registro');

$type = (string) ($pdf_element_type ?? '');
$lab  = is_array($lab_config ?? null) ? $lab_config : [];
$ts   = \App\Services\ReportPdfLayoutService::normalizeTextStyle($pdf_text_style ?? []);
$stInst = \App\Services\ReportPdfLayoutService::textStyleNormalizedToInlineCss($ts);

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

if (array_key_exists($type, \App\Services\ReportPdfLayoutService::PATIENT_DOCTOR_GRID_LABEL_DEFAULTS)) {
    $pdS = is_array($pdf_patient_doctor_grid_style ?? null)
        ? $pdf_patient_doctor_grid_style
        : \App\Services\ReportPdfLayoutService::normalizePatientDoctorGridStyle([]);
    $showL = \App\Services\ReportPdfLayoutService::labFirmasBool($pdS, 'show_label_' . $type, true);
    $lbl   = trim((string) ($pdS['label_' . $type] ?? ''));
    $inline = (($pdS['label_' . $type . '_line_mode'] ?? 'stacked') === 'inline');
    $val   = $valueOf($type);
    $showLblText = $showL && $lbl !== '';
    if ($inline) {
        ?>
                <div class="patient-line"><?php if ($showLblText): ?><span class="label"><?= esc($lbl) ?></span> <?php endif; ?><?= esc($val) ?></div>
        <?php
    } else {
        if ($showLblText) {
            ?>
                <div class="patient-line"><span class="label"><?= esc($lbl) ?></span></div>
            <?php
        }
        ?>
                <div class="patient-line"><?= esc($val) ?></div>
        <?php
    }

    return;
}

if ($type === 'custom_text') {
    $ct = is_array($pdf_custom_text ?? null)
        ? \App\Services\ReportPdfLayoutService::normalizeCustomTextPayload($pdf_custom_text)
        : \App\Services\ReportPdfLayoutService::normalizeCustomTextPayload([]);
    $stL = \App\Services\ReportPdfLayoutService::textStyleArrayToInlineCss($ct['label_style']);
    $stV = \App\Services\ReportPdfLayoutService::textStyleArrayToInlineCss($ct['value_style']);
    $labT = $ct['label'];
    $valT = $ct['value'];
    $showLbl = $ct['show_label'] && $labT !== '';
    $inlineM = ($ct['line_mode'] === 'inline');
    ?>
                <div class="pdf-custom-text">
                <?php if ($inlineM && $showLbl): ?>
                <p style="margin:0;"><span style="<?= esc($stL, 'attr') ?>"><?= esc($labT) ?></span> <span style="<?= esc($stV, 'attr') ?>"><?= esc($valT !== '' ? $valT : '—') ?></span></p>
                <?php elseif ($inlineM): ?>
                <p style="margin:0;"><span style="<?= esc($stV, 'attr') ?>"><?= esc($valT !== '' ? $valT : '—') ?></span></p>
                <?php elseif ($showLbl): ?>
                <p style="margin:0;"><span style="<?= esc($stL, 'attr') ?>"><?= esc($labT) ?></span></p>
                <p style="margin:0;"><span style="<?= esc($stV, 'attr') ?>"><?= esc($valT !== '' ? $valT : '—') ?></span></p>
                <?php else: ?>
                <p style="margin:0;"><span style="<?= esc($stV, 'attr') ?>"><?= esc($valT !== '' ? $valT : '—') ?></span></p>
                <?php endif; ?>
                </div>
    <?php

    return;
}

switch ($type) {
    case 'logo':
        $hgLogo = is_array($pdf_header_grid_style ?? null)
            ? $pdf_header_grid_style
            : \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle([]);
        $lblLogo     = trim((string) ($hgLogo['label_logo'] ?? ''));
        $showLogoL   = \App\Services\ReportPdfLayoutService::labFirmasBool($hgLogo, 'show_label_logo', true);
        $inlineLogo  = (($hgLogo['label_logo_line_mode'] ?? 'stacked') === 'inline');
        $showLblLogo = $showLogoL && $lblLogo !== '';
        $stLogoLbl   = \App\Services\ReportPdfLayoutService::headerGridLabelPieceStyleAttr($hgLogo, 'logo');
        ?>
                <div class="header-piece header-piece-logo">
                    <?php if ($inlineLogo && $showLblLogo): ?>
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                        <span style="<?= esc($stLogoLbl, 'attr') ?>"><?= esc($lblLogo) ?></span>
                        <?php if ($logoDataUri !== ''): ?>
                        <img src="<?= $logoDataUri ?>" alt="Logo">
                        <?php else: ?>
                        <strong style="<?= esc($stInst, 'attr') ?>"><?= esc($lab['company'] ?? 'Laboratorio') ?></strong>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <?php if ($showLblLogo): ?>
                    <div style="margin-bottom:6px;"><span style="<?= esc($stLogoLbl, 'attr') ?>"><?= esc($lblLogo) ?></span></div>
                    <?php endif; ?>
                    <?php if ($logoDataUri !== ''): ?>
                    <img src="<?= $logoDataUri ?>" alt="Logo">
                    <?php else: ?>
                    <strong style="<?= esc($stInst, 'attr') ?>"><?= esc($lab['company'] ?? 'Laboratorio') ?></strong>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
        <?php
        break;

    case 'lab_company':
        $hgCo = is_array($pdf_header_grid_style ?? null)
            ? $pdf_header_grid_style
            : \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle([]);
        $lblCo     = trim((string) ($hgCo['label_lab_company'] ?? ''));
        $showCoL   = \App\Services\ReportPdfLayoutService::labFirmasBool($hgCo, 'show_label_lab_company', true);
        $inlineCo  = (($hgCo['label_lab_company_line_mode'] ?? 'stacked') === 'inline');
        $showLblCo = $showCoL && $lblCo !== '';
        $stCoLbl   = \App\Services\ReportPdfLayoutService::headerGridLabelPieceStyleAttr($hgCo, 'lab_company');
        $h1CoStyle = esc($stInst, 'attr') . ';margin:0;';
        ?>
                <div class="header-piece header-piece-company">
                    <?php if ($inlineCo && $showLblCo): ?>
                    <div style="display:flex;flex-wrap:wrap;align-items:baseline;gap:8px;">
                        <span style="<?= esc($stCoLbl, 'attr') ?>"><?= esc($lblCo) ?></span>
                        <h1 style="<?= $h1CoStyle ?>"><?= esc($lab['company'] ?? 'Laboratorio') ?></h1>
                    </div>
                    <?php elseif ($showLblCo): ?>
                    <div style="margin-bottom:6px;"><span style="<?= esc($stCoLbl, 'attr') ?>"><?= esc($lblCo) ?></span></div>
                    <h1 style="<?= $h1CoStyle ?>"><?= esc($lab['company'] ?? 'Laboratorio') ?></h1>
                    <?php else: ?>
                    <h1 style="<?= $h1CoStyle ?>"><?= esc($lab['company'] ?? 'Laboratorio') ?></h1>
                    <?php endif; ?>
                </div>
        <?php
        break;

    case 'lab_address':
        if (! empty($lab['address'])) {
            $hgAd = is_array($pdf_header_grid_style ?? null)
                ? $pdf_header_grid_style
                : \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle([]);
            $lblAd     = trim((string) ($hgAd['label_lab_address'] ?? ''));
            $showAdL   = \App\Services\ReportPdfLayoutService::labFirmasBool($hgAd, 'show_label_lab_address', true);
            $inlineAd  = (($hgAd['label_lab_address_line_mode'] ?? 'stacked') === 'inline');
            $showLblAd = $showAdL && $lblAd !== '';
            $stAdLbl   = \App\Services\ReportPdfLayoutService::headerGridLabelPieceStyleAttr($hgAd, 'lab_address');
            $valAd     = (string) $lab['address'];
            ?>
                <div class="header-piece">
                    <?php if ($inlineAd && $showLblAd): ?>
                    <p style="margin:0;"><span style="<?= esc($stAdLbl, 'attr') ?>"><?= esc($lblAd) ?></span> <span style="<?= esc($stInst, 'attr') ?>"><?= esc($valAd) ?></span></p>
                    <?php elseif ($showLblAd): ?>
                    <p style="margin:0;"><span style="<?= esc($stAdLbl, 'attr') ?>"><?= esc($lblAd) ?></span></p>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= esc($valAd) ?></span></p>
                    <?php else: ?>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= esc($valAd) ?></span></p>
                    <?php endif; ?>
                </div>
            <?php
        }
        break;

    case 'lab_phone':
        if (! empty($lab['phone'])) {
            $hgPh = is_array($pdf_header_grid_style ?? null)
                ? $pdf_header_grid_style
                : \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle([]);
            $lblPh     = trim((string) ($hgPh['label_lab_phone'] ?? ''));
            $showPhL   = \App\Services\ReportPdfLayoutService::labFirmasBool($hgPh, 'show_label_lab_phone', true);
            $inlinePh  = (($hgPh['label_lab_phone_line_mode'] ?? 'stacked') === 'inline');
            $showLblPh = $showPhL && $lblPh !== '';
            $stPhLbl   = \App\Services\ReportPdfLayoutService::headerGridLabelPieceStyleAttr($hgPh, 'lab_phone');
            $valPh     = (string) $lab['phone'];
            ?>
                <div class="header-piece">
                    <?php if ($inlinePh && $showLblPh): ?>
                    <p style="margin:0;"><span style="<?= esc($stPhLbl, 'attr') ?>"><?= esc($lblPh) ?></span> <span style="<?= esc($stInst, 'attr') ?>"><?= esc($valPh) ?></span></p>
                    <?php elseif ($showLblPh): ?>
                    <p style="margin:0;"><span style="<?= esc($stPhLbl, 'attr') ?>"><?= esc($lblPh) ?></span></p>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= esc($valPh) ?></span></p>
                    <?php else: ?>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= esc($valPh) ?></span></p>
                    <?php endif; ?>
                </div>
            <?php
        }
        break;

    case 'lab_email':
        if (! empty($lab['email'])) {
            $hgEm = is_array($pdf_header_grid_style ?? null)
                ? $pdf_header_grid_style
                : \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle([]);
            $lblEm     = trim((string) ($hgEm['label_lab_email'] ?? ''));
            $showEmL   = \App\Services\ReportPdfLayoutService::labFirmasBool($hgEm, 'show_label_lab_email', true);
            $inlineEm  = (($hgEm['label_lab_email_line_mode'] ?? 'stacked') === 'inline');
            $showLblEm = $showEmL && $lblEm !== '';
            $stEmLbl   = \App\Services\ReportPdfLayoutService::headerGridLabelPieceStyleAttr($hgEm, 'lab_email');
            $valEm     = (string) $lab['email'];
            ?>
                <div class="header-piece">
                    <?php if ($inlineEm && $showLblEm): ?>
                    <p style="margin:0;"><span style="<?= esc($stEmLbl, 'attr') ?>"><?= esc($lblEm) ?></span> <span style="<?= esc($stInst, 'attr') ?>"><?= esc($valEm) ?></span></p>
                    <?php elseif ($showLblEm): ?>
                    <p style="margin:0;"><span style="<?= esc($stEmLbl, 'attr') ?>"><?= esc($lblEm) ?></span></p>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= esc($valEm) ?></span></p>
                    <?php else: ?>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= esc($valEm) ?></span></p>
                    <?php endif; ?>
                </div>
            <?php
        }
        break;

    case 'lab_website':
        if (! empty($lab['website'])) {
            $hgWs = is_array($pdf_header_grid_style ?? null)
                ? $pdf_header_grid_style
                : \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle([]);
            $lblWs     = trim((string) ($hgWs['label_lab_website'] ?? ''));
            $showWsL   = \App\Services\ReportPdfLayoutService::labFirmasBool($hgWs, 'show_label_lab_website', true);
            $inlineWs  = (($hgWs['label_lab_website_line_mode'] ?? 'stacked') === 'inline');
            $showLblWs = $showWsL && $lblWs !== '';
            $stWsLbl   = \App\Services\ReportPdfLayoutService::headerGridLabelPieceStyleAttr($hgWs, 'lab_website');
            $valWs     = (string) $lab['website'];
            ?>
                <div class="header-piece">
                    <?php if ($inlineWs && $showLblWs): ?>
                    <p style="margin:0;"><span style="<?= esc($stWsLbl, 'attr') ?>"><?= esc($lblWs) ?></span> <span style="<?= esc($stInst, 'attr') ?>"><?= esc($valWs) ?></span></p>
                    <?php elseif ($showLblWs): ?>
                    <p style="margin:0;"><span style="<?= esc($stWsLbl, 'attr') ?>"><?= esc($lblWs) ?></span></p>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= esc($valWs) ?></span></p>
                    <?php else: ?>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= esc($valWs) ?></span></p>
                    <?php endif; ?>
                </div>
            <?php
        }
        break;

    case 'qr':
        if (! empty($report_url) && ! empty($qr_data_uri)) {
            $hgS = is_array($pdf_header_grid_style ?? null)
                ? $pdf_header_grid_style
                : \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle([]);
            $hint     = trim((string) ($hgS['label_qr_hint'] ?? ''));
            $showHint = \App\Services\ReportPdfLayoutService::labFirmasBool($hgS, 'show_label_qr_hint', true) && $hint !== '';
            $inlineH  = (($hgS['label_qr_hint_line_mode'] ?? 'stacked') === 'inline');
            $stQrHint = \App\Services\ReportPdfLayoutService::headerGridLabelPieceStyleAttr($hgS, 'qr_hint');
            ?>
                <div class="header-piece header-piece-qr">
                    <?php if ($inlineH && $showHint): ?>
                    <div class="qr-inline-wrap" style="text-align:center;">
                        <span style="display:inline-block;vertical-align:middle;max-width:58%;margin-right:6px;<?= esc($stQrHint, 'attr') ?>"><?= esc($hint) ?></span><img src="<?= $qr_data_uri ?>" alt="QR" class="qr-img" style="vertical-align:middle;">
                    </div>
                    <?php elseif ($inlineH): ?>
                    <img src="<?= $qr_data_uri ?>" alt="QR" class="qr-img">
                    <?php else: ?>
                    <img src="<?= $qr_data_uri ?>" alt="QR" class="qr-img">
                    <?php if ($showHint): ?>
                    <p class="qr-label" style="<?= esc($stQrHint, 'attr') ?>"><?= esc($hint) ?></p>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php
        }
        break;

    case 'footer_company':
        // Tipografía: estilo por instancia (Elementos del PDF); el card «Pie» solo define textos/leyendas.
        $stCo = \App\Services\ReportPdfLayoutService::textStyleNormalizedToInlineCss($ts);
        ?>
                <div class="footer-piece footer-piece-company" style="<?= esc($stCo, 'attr') ?>"><?= esc($lab['company'] ?? '') ?></div>
        <?php
        break;

    case 'footer_generated':
        $ftS = is_array($pdf_footer_grid_style ?? null)
            ? $pdf_footer_grid_style
            : \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle([]);
        $pref   = trim((string) ($ftS['label_footer_generated'] ?? 'Resultados generados el'));
        $showP  = \App\Services\ReportPdfLayoutService::labFirmasBool($ftS, 'show_label_footer_generated', true);
        $inlineF = (($ftS['label_footer_generated_line_mode'] ?? 'stacked') === 'inline');
        $showPref = $showP && $pref !== '';
        $stInst = \App\Services\ReportPdfLayoutService::textStyleNormalizedToInlineCss($ts);
        ?>
                <div class="footer-piece footer-piece-generated"><?php if ($inlineF): ?><?php if ($showPref): ?><span class="footer-generated-label" style="<?= esc($stInst, 'attr') ?>"><?= esc($pref) ?></span> <?php endif; ?><span class="footer-generated-datetime" style="<?= esc($stInst, 'attr') ?>"><?= esc($reportEmitidoEl) ?></span><?php else: ?><?php if ($showPref): ?><div class="footer-generated-label" style="<?= esc($stInst, 'attr') ?>"><?= esc($pref) ?></div><?php endif; ?><div class="footer-generated-datetime" style="<?= esc($stInst, 'attr') ?>"><?= esc($reportEmitidoEl) ?></div><?php endif; ?></div>
        <?php
        break;

    case 'footer_policy':
        if (! empty($lab['return_policy'])) {
            $stPo = \App\Services\ReportPdfLayoutService::textStyleNormalizedToInlineCss($ts);
            ?>
                <div class="footer-piece footer-piece-policy"><small class="footer-policy-text" style="<?= esc($stPo, 'attr') ?>"><?= esc($lab['return_policy']) ?></small></div>
            <?php
        }
        break;

    case 'lab_firmas_title':
        $lfS = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        if (! \App\Services\ReportPdfLayoutService::labFirmasBool($lfS, 'show_section_title', true)) {
            break;
        }
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
        $showL = \App\Services\ReportPdfLayoutService::labFirmasBool($lfS, 'show_label_validator', true);
        $inline = (($lfS['validator_line_mode'] ?? 'stacked') === 'inline');
        $lblValidator = trim((string) ($lfS['label_validator'] ?? 'Verificado por:'));
        $showLblText = $showL && $lblValidator !== '';
        ?>
                <?php if ($inline): ?>
                <div>
                    <?php if ($showLblText): ?><span class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;"><?= esc($lblValidator) ?></span> <?php endif; ?><span><?= esc($nm !== '' ? $nm : '—') ?></span>
                </div>
                <?php else: ?>
                <?php if ($showLblText): ?>
                <div class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;margin-bottom:4px;"><?= esc($lblValidator) ?></div>
                <?php endif; ?>
                <div><?= esc($nm !== '' ? $nm : '—') ?></div>
                <?php endif; ?>
        <?php
        break;

    case 'lab_firmas_seal':
        $lfS    = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        $fr     = is_array($pdf_firma_row ?? null) ? $pdf_firma_row : [];
        $seal   = trim((string) ($fr['approver_seal'] ?? ''));
        $sealUri = ($seal !== '') ? report_image_data_uri($seal) : '';
        $showL = \App\Services\ReportPdfLayoutService::labFirmasBool($lfS, 'show_label_seal', true);
        $inline = (($lfS['label_seal_line_mode'] ?? 'stacked') === 'inline');
        $lblSeal = trim((string) ($lfS['label_seal'] ?? ''));
        $showLblText = $showL && $lblSeal !== '';
        ?>
                <?php if ($inline): ?>
                <div style="display:flex;align-items:flex-end;flex-wrap:wrap;gap:6px;">
                    <?php if ($showLblText): ?>
                    <span class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;"><?= esc($lblSeal) ?></span>
                    <?php endif; ?>
                    <?php if ($sealUri !== ''): ?>
                    <img src="<?= esc($sealUri, 'attr') ?>" alt="" class="pdf-lab-f-img" style="max-height:110px;max-width:100%;">
                    <?php else: ?>
                    <div style="opacity:0.6;">—</div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <?php if ($showLblText): ?>
                <div class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;margin-bottom:6px;"><?= esc($lblSeal) ?></div>
                <?php endif; ?>
                <?php if ($sealUri !== ''): ?>
                    <img src="<?= esc($sealUri, 'attr') ?>" alt="" class="pdf-lab-f-img" style="max-height:110px;max-width:100%;">
                <?php else: ?>
                    <div style="opacity:0.6;">—</div>
                <?php endif; ?>
                <?php endif; ?>
        <?php
        break;

    case 'lab_firmas_approver_signature':
        $lfS    = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        $fr     = is_array($pdf_firma_row ?? null) ? $pdf_firma_row : [];
        $sig    = trim((string) ($fr['approver_signature'] ?? ''));
        $sigUri = ($sig !== '') ? report_image_data_uri($sig) : '';
        $showL = \App\Services\ReportPdfLayoutService::labFirmasBool($lfS, 'show_label_firma', true);
        $inline = (($lfS['label_firma_line_mode'] ?? 'stacked') === 'inline');
        $lblFirma = trim((string) ($lfS['label_firma'] ?? ''));
        $showLblText = $showL && $lblFirma !== '';
        ?>
                <?php if ($inline): ?>
                <div style="display:flex;align-items:flex-end;flex-wrap:wrap;gap:6px;">
                    <?php if ($showLblText): ?>
                    <span class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;"><?= esc($lblFirma) ?></span>
                    <?php endif; ?>
                    <?php if ($sigUri !== ''): ?>
                    <img src="<?= esc($sigUri, 'attr') ?>" alt="" class="pdf-lab-f-img" style="max-height:72px;max-width:220px;">
                    <?php else: ?>
                    <div style="opacity:0.6;">—</div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <?php if ($showLblText): ?>
                <div class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;margin-bottom:6px;"><?= esc($lblFirma) ?></div>
                <?php endif; ?>
                <?php if ($sigUri !== ''): ?>
                    <div style="margin-bottom:4px;">
                        <img src="<?= esc($sigUri, 'attr') ?>" alt="" class="pdf-lab-f-img" style="max-height:72px;max-width:220px;">
                    </div>
                <?php else: ?>
                    <div style="opacity:0.6;">—</div>
                <?php endif; ?>
                <?php endif; ?>
        <?php
        break;

    case 'lab_firmas_approver_name':
        $lfS  = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        $fr   = is_array($pdf_firma_row ?? null) ? $pdf_firma_row : [];
        $apNm = trim((string) ($fr['approver_name'] ?? ''));
        $showL = \App\Services\ReportPdfLayoutService::labFirmasBool($lfS, 'show_label_approver', true);
        $inline = (($lfS['label_approver_line_mode'] ?? 'stacked') === 'inline');
        $lblAp = trim((string) ($lfS['label_approver'] ?? ''));
        $showLblText = $showL && $lblAp !== '';
        ?>
                <?php if ($inline): ?>
                <div>
                    <?php if ($showLblText): ?><span class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;"><?= esc($lblAp) ?></span> <?php endif; ?><span style="font-weight:600;"><?= esc($apNm !== '' ? $apNm : '—') ?></span>
                </div>
                <?php else: ?>
                <?php if ($showLblText): ?>
                <div class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;margin-bottom:4px;"><?= esc($lblAp) ?></div>
                <?php endif; ?>
                <div style="font-weight:600;"><?= esc($apNm !== '' ? $apNm : '—') ?></div>
                <?php endif; ?>
        <?php
        break;

    case 'lab_firmas_approver_cargo':
        $lfS   = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        $fr    = is_array($pdf_firma_row ?? null) ? $pdf_firma_row : [];
        $cargo = trim((string) ($fr['approver_cargo'] ?? ''));
        $showL = \App\Services\ReportPdfLayoutService::labFirmasBool($lfS, 'show_label_cargo', true);
        $inline = (($lfS['label_cargo_line_mode'] ?? 'stacked') === 'inline');
        $lblCargo = trim((string) ($lfS['label_cargo'] ?? ''));
        $showLblText = $showL && $lblCargo !== '';
        ?>
                <?php if ($inline): ?>
                <div>
                    <?php if ($showLblText): ?><span class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;"><?= esc($lblCargo) ?></span> <?php endif; ?><span style="opacity:0.95;"><?= esc($cargo !== '' ? $cargo : '—') ?></span>
                </div>
                <?php else: ?>
                <?php if ($showLblText): ?>
                <div class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;margin-bottom:4px;"><?= esc($lblCargo) ?></div>
                <?php endif; ?>
                <div style="opacity:0.95;"><?= esc($cargo !== '' ? $cargo : '—') ?></div>
                <?php endif; ?>
        <?php
        break;

    case 'lab_firmas_matricula':
        $lfS       = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        $fr        = is_array($pdf_firma_row ?? null) ? $pdf_firma_row : [];
        $matricula = trim((string) ($fr['approver_matricula'] ?? ''));
        $showL = \App\Services\ReportPdfLayoutService::labFirmasBool($lfS, 'show_label_matricula', true);
        $inline = (($lfS['label_matricula_line_mode'] ?? 'stacked') === 'inline');
        $lblMat = trim((string) ($lfS['label_matricula'] ?? 'Matrícula:'));
        $showLblText = $showL && $lblMat !== '';
        ?>
                <?php if ($inline): ?>
                <div>
                    <?php if ($showLblText): ?><span class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;"><?= esc($lblMat) ?></span> <?php endif; ?><span style="opacity:0.95;"><?= esc($matricula !== '' ? $matricula : '—') ?></span>
                </div>
                <?php else: ?>
                <?php if ($showLblText): ?>
                <div class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;margin-bottom:6px;"><?= esc($lblMat) ?></div>
                <?php endif; ?>
                <div style="opacity:0.95;"><?= esc($matricula !== '' ? $matricula : '—') ?></div>
                <?php endif; ?>
        <?php
        break;
}
