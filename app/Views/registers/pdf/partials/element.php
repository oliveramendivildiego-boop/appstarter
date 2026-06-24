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
        case 'diagnostico_presuntivo':
            return trim((string) ($register_info->diagnostico_presuntivo ?? ''));
        case 'medico':
            $nombreMed = trim((string) ($doctor->name ?? ''));
            if (! empty($doctor->report_sin_prefijo_medico ?? false)) {
                return $nombreMed !== '' ? $nombreMed : '—';
            }
            $tit = ((int) ($doctor->gender ?? 0) === 1) ? 'Dr.' : 'Dra.';

            return $tit . ' ' . ($nombreMed !== '' ? $nombreMed : '-');
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
$pdfVariant  = (string) ($pdf_analisis_variant ?? 'pdf');
if ($logoDataUri === '' && $type === 'logo' && $pdfVariant !== 'pdf') {
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
    $gapPx = isset($pdf_label_value_gap_px)
        ? (int) $pdf_label_value_gap_px
        : (array_key_exists('label_' . $type . '_value_gap_px', $pdS) ? (int) $pdS['label_' . $type . '_value_gap_px'] : 0);
    $mtPx  = isset($pdf_label_space_above_px)
        ? (int) $pdf_label_space_above_px
        : (array_key_exists('label_' . $type . '_space_above_px', $pdS) ? (int) $pdS['label_' . $type . '_space_above_px'] : 0);
    $mbPx  = isset($pdf_label_space_below_px)
        ? (int) $pdf_label_space_below_px
        : (array_key_exists('label_' . $type . '_space_below_px', $pdS) ? (int) $pdS['label_' . $type . '_space_below_px'] : 0);
    $val   = $valueOf($type);
    if ($type === 'diagnostico_presuntivo' && trim($val) === '') {
        return;
    }
    $showLblText = $showL && $lbl !== '';
    $stPdLbl = \App\Services\ReportPdfLayoutService::patientDoctorGridLabelStyleAttr($pdS, $type);
    if ($inline) {
        ?>
                <div class="patient-line" style="padding-top:<?= (int) max(0, $mtPx) ?>px;padding-bottom:<?= (int) max(0, $mbPx) ?>px;">
                    <?php if ($showLblText): ?>
                        <span class="label" style="margin-right:<?= (int) max(0, $gapPx) ?>px;<?= esc($stPdLbl, 'attr') ?>"><?= esc($lbl) ?></span>
                    <?php endif; ?>
                    <span style="<?= $showLblText ? '' : 'margin-left:' . (int) max(0, $gapPx) . 'px;' ?>"><?= esc($val) ?></span>
                </div>
        <?php
    } else {
        if ($showLblText) {
            ?>
                <div class="patient-line" style="padding-top:<?= (int) max(0, $mtPx) ?>px;padding-bottom:<?= (int) max(0, $gapPx) ?>px;">
                    <span class="label" style="<?= esc($stPdLbl, 'attr') ?>"><?= esc($lbl) ?></span>
                </div>
            <?php
        }
        $valMt = $showLblText ? 0 : ((int) max(0, $mtPx) + (int) max(0, $gapPx));
        ?>
                <div class="patient-line" style="padding-top:<?= (int) max(0, $valMt) ?>px;padding-bottom:<?= (int) max(0, $mbPx) ?>px;"><?= esc($val) ?></div>
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
        $logoRel     = $lab['logo'] ?? 'images/logo-john.png';
        $logoSrc     = $logoDataUri !== '' ? $logoDataUri : report_image_src_for_variant($logoRel, $pdfVariant);
        ?>
                <div class="header-piece header-piece-logo">
                    <?php if ($inlineLogo && $showLblLogo): ?>
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                        <span style="<?= esc($stLogoLbl, 'attr') ?>"><?= esc($lblLogo) ?></span>
                        <?php if ($logoSrc !== ''): ?>
                        <img src="<?= report_pdf_img_src_attr($logoSrc) ?>" alt="Logo">
                        <?php else: ?>
                        <strong style="<?= esc($stInst, 'attr') ?>"><?= esc($lab['company'] ?? 'Laboratorio') ?></strong>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <?php if ($showLblLogo): ?>
                    <div style="margin-bottom:6px;"><span style="<?= esc($stLogoLbl, 'attr') ?>"><?= esc($lblLogo) ?></span></div>
                    <?php endif; ?>
                    <?php if ($logoSrc !== ''): ?>
                    <img src="<?= report_pdf_img_src_attr($logoSrc) ?>" alt="Logo">
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

    case 'paciente_institucion':
        $hgInst = is_array($pdf_header_grid_style ?? null)
            ? $pdf_header_grid_style
            : \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle([]);
        $lblInst     = trim((string) ($hgInst['label_paciente_institucion'] ?? ''));
        $showInstL   = \App\Services\ReportPdfLayoutService::labFirmasBool($hgInst, 'show_label_paciente_institucion', true);
        $inlineInst  = (($hgInst['label_paciente_institucion_line_mode'] ?? 'stacked') === 'inline');
        $showLblInst = $showInstL && $lblInst !== '';
        $stInstLbl   = \App\Services\ReportPdfLayoutService::headerGridLabelPieceStyleAttr($hgInst, 'paciente_institucion');
        $valInstRaw  = trim((string) ($paciente->paciente_institucion ?? ''));
        $valInst     = $valInstRaw !== '' ? $valInstRaw : '—';
        ?>
                <div class="header-piece">
                    <?php if ($inlineInst && $showLblInst): ?>
                    <p style="margin:0;"><span style="<?= esc($stInstLbl, 'attr') ?>"><?= esc($lblInst) ?></span> <span style="<?= esc($stInst, 'attr') ?>"><?= esc($valInst) ?></span></p>
                    <?php elseif ($showLblInst): ?>
                    <p style="margin:0;"><span style="<?= esc($stInstLbl, 'attr') ?>"><?= esc($lblInst) ?></span></p>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= esc($valInst) ?></span></p>
                    <?php else: ?>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= esc($valInst) ?></span></p>
                    <?php endif; ?>
                </div>
        <?php
        break;

    case 'pdf_pages_total':
        $hgPg = is_array($pdf_header_grid_style ?? null)
            ? $pdf_header_grid_style
            : \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle([]);
        $lblPg     = trim((string) ($hgPg['label_pdf_pages_total'] ?? ''));
        $showPgL   = \App\Services\ReportPdfLayoutService::labFirmasBool($hgPg, 'show_label_pdf_pages_total', true);
        $inlinePg  = (($hgPg['label_pdf_pages_total_line_mode'] ?? 'stacked') === 'inline');
        $showLblPg = $showPgL && $lblPg !== '';
        $stPgLbl   = \App\Services\ReportPdfLayoutService::headerGridLabelPieceStyleAttr($hgPg, 'pdf_pages_total');
        ?>
                <div class="header-piece">
                    <?php if ($inlinePg && $showLblPg): ?>
                    <p style="margin:0;"><span style="<?= esc($stPgLbl, 'attr') ?>"><?= esc($lblPg) ?></span> <span style="<?= esc($stInst, 'attr') ?>"><?= '__PDF_TOTAL_PAGES__' ?></span></p>
                    <?php elseif ($showLblPg): ?>
                    <p style="margin:0;"><span style="<?= esc($stPgLbl, 'attr') ?>"><?= esc($lblPg) ?></span></p>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= '__PDF_TOTAL_PAGES__' ?></span></p>
                    <?php else: ?>
                    <p style="margin:0;"><span style="<?= esc($stInst, 'attr') ?>"><?= '__PDF_TOTAL_PAGES__' ?></span></p>
                    <?php endif; ?>
                </div>
        <?php
        break;

    case 'pdf_pagination':
        $hgPag = is_array($pdf_header_grid_style ?? null)
            ? $pdf_header_grid_style
            : \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle([]);
        $lblPag       = trim((string) ($hgPag['label_pdf_pagination'] ?? ''));
        $showPagL     = \App\Services\ReportPdfLayoutService::labFirmasBool($hgPag, 'show_label_pdf_pagination', true);
        $inlinePag    = (($hgPag['label_pdf_pagination_line_mode'] ?? 'inline') === 'inline');
        $showLblPag   = $showPagL && $lblPag !== '';
        $stPagLbl     = \App\Services\ReportPdfLayoutService::headerGridLabelPieceStyleAttr($hgPag, 'pdf_pagination');
        $canvasPrefix = $showLblPag ? $lblPag . ' ' : '';
        $variant      = (string) ($pdf_analisis_variant ?? 'pdf');
        $isDompdf     = ($variant === 'pdf');
        $instUid      = trim((string) ($pdf_instance_uid ?? ''));
        $pagUid       = 'pdf-pag-' . substr(sha1($instUid . '|' . $canvasPrefix . '|' . $stInst), 0, 10);
        $pageToken    = \App\Services\RegisterService::TOTAL_PAGES_TOKEN;
        $sectionKey   = (string) ($pdf_section_key ?? 'header');
        $inFooter     = ($sectionKey === 'footer');
        $ftGrid       = is_array($pdf_footer_grid_style ?? null)
            ? $pdf_footer_grid_style
            : \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle([]);
        $footerLh     = max(1.0, (float) ($ftGrid['line_height'] ?? 1.35));
        if ($isDompdf) {
            $mm = is_array($pdf_margins_mm ?? null)
                ? $pdf_margins_mm
                : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
            $pagConfig  = [
                'prefix'            => $canvasPrefix,
                'zone'              => $inFooter ? 'footer' : 'header',
                'align'             => (string) ($pdf_cell_align ?? 'left'),
                'fontSize'          => (float) ($ts['font_size_pt'] ?? 10),
                'fontFamily'        => (string) ($ts['font_family'] ?? 'DejaVu Sans'),
                'color'             => (string) ($ts['font_color'] ?? '#333333'),
                'mt'                => (float) ($mm['top'] ?? 15),
                'mr'                => (float) ($mm['right'] ?? 15),
                'mb'                => (float) ($mm['bottom'] ?? 15),
                'ml'                => (float) ($mm['left'] ?? 15),
                'footerReserveMm'   => $inFooter
                    ? max(0.0, (float) ($pdf_footer_reserve_mm ?? 0))
                    : 0.0,
                'gridColumn'        => (int) ($pdf_grid_column ?? 0),
                'gridColumnSpan'    => max(1, (int) ($pdf_grid_column_span ?? 1)),
                'gridRow'           => (int) ($pdf_grid_row ?? 0),
                'gridStack'         => (int) ($pdf_grid_stack ?? 0),
                'footerColumns'     => max(1, (int) ($pdf_footer_columns ?? 1)),
                'footerRows'        => max(1, (int) ($pdf_footer_rows ?? 1)),
                'footerRowGapPx'    => max(0.0, (float) ($pdf_footer_row_gap_px ?? 0)),
                'lineHeight'        => $inFooter ? $footerLh : max(1.0, (float) ($ts['line_height'] ?? 1.35)),
            ];
            echo '<!-- pdf-pagination:' . base64_encode(json_encode($pagConfig, JSON_UNESCAPED_UNICODE)) . ' -->';
        }
        $dataTotalAttr     = $pageToken;
        $hideForCanvas     = $isDompdf && ! $inFooter;
        $footerDompdfClass = ($isDompdf && $inFooter) ? ' pdf-pagination-line--dompdf-footer' : '';
        $pagPieceClass     = $inFooter ? 'header-piece' : 'header-piece header-piece-pagination';
        ?>
                <div class="<?= esc($pagPieceClass, 'attr') ?>"<?= $hideForCanvas ? ' style="visibility:hidden;height:0;overflow:hidden;margin:0;padding:0;"' : '' ?>>
                    <?php if ($inlinePag && $showLblPag): ?>
                    <p style="margin:0;"><span style="<?= esc($stPagLbl, 'attr') ?>"><?= esc($lblPag) ?></span> <span id="<?= esc($pagUid, 'attr') ?>" class="pdf-pagination-line<?= esc($footerDompdfClass, 'attr') ?>" style="<?= esc($stInst, 'attr') ?>" data-prefix="" data-total="<?= esc($dataTotalAttr, 'attr') ?>"></span></p>
                    <?php elseif ($showLblPag): ?>
                    <p style="margin:0;"><span style="<?= esc($stPagLbl, 'attr') ?>"><?= esc($lblPag) ?></span></p>
                    <p style="margin:0;"><span id="<?= esc($pagUid, 'attr') ?>" class="pdf-pagination-line<?= esc($footerDompdfClass, 'attr') ?>" style="<?= esc($stInst, 'attr') ?>" data-prefix="" data-total="<?= esc($dataTotalAttr, 'attr') ?>"></span></p>
                    <?php else: ?>
                    <p style="margin:0;"><span id="<?= esc($pagUid, 'attr') ?>" class="pdf-pagination-line<?= esc($footerDompdfClass, 'attr') ?>" style="<?= esc($stInst, 'attr') ?>" data-prefix="" data-total="<?= esc($dataTotalAttr, 'attr') ?>"></span></p>
                    <?php endif; ?>
                </div>
        <?php
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
        $ftS = is_array($pdf_footer_grid_style ?? null)
            ? $pdf_footer_grid_style
            : \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle([]);
        $stCo = \App\Services\ReportPdfLayoutService::footerGridPieceStyleAttr($ftS, 'company');
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
        $stPref = \App\Services\ReportPdfLayoutService::footerGridPieceStyleAttr($ftS, 'label_generated');
        $stDt   = \App\Services\ReportPdfLayoutService::footerGridPieceStyleAttr($ftS, 'datetime');
        ?>
                <div class="footer-piece footer-piece-generated"><?php if ($inlineF): ?><?php if ($showPref): ?><span class="footer-generated-label" style="<?= esc($stPref, 'attr') ?>"><?= esc($pref) ?></span> <?php endif; ?><span class="footer-generated-datetime" style="<?= esc($stDt, 'attr') ?>"><?= esc($reportEmitidoEl) ?></span><?php else: ?><?php if ($showPref): ?><div class="footer-generated-label" style="<?= esc($stPref, 'attr') ?>"><?= esc($pref) ?></div><?php endif; ?><div class="footer-generated-datetime" style="<?= esc($stDt, 'attr') ?>"><?= esc($reportEmitidoEl) ?></div><?php endif; ?></div>
        <?php
        break;

    case 'footer_policy':
        if (! empty($lab['return_policy'])) {
            $ftS = is_array($pdf_footer_grid_style ?? null)
                ? $pdf_footer_grid_style
                : \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle([]);
            $stPo = \App\Services\ReportPdfLayoutService::footerGridPieceStyleAttr($ftS, 'policy');
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
        $sealMaxH = max(40, min(200, (int) ($lfS['seal_max_height_px'] ?? 110)));
        $seal   = trim((string) ($fr['approver_seal'] ?? ''));
        $sealUri = ($seal !== '') ? report_image_src_for_variant($seal, $pdfVariant) : '';
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
                    <img src="<?= report_pdf_img_src_attr($sealUri) ?>" alt="" class="pdf-lab-f-img" style="max-height:<?= (int) $sealMaxH ?>px;max-width:100%;">
                    <?php else: ?>
                    <div style="opacity:0.6;">—</div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <?php if ($showLblText): ?>
                <div class="pdf-lab-f-sublabel" style="opacity:0.75;font-size:0.92em;margin-bottom:6px;"><?= esc($lblSeal) ?></div>
                <?php endif; ?>
                <?php if ($sealUri !== ''): ?>
                    <img src="<?= report_pdf_img_src_attr($sealUri) ?>" alt="" class="pdf-lab-f-img" style="max-height:<?= (int) $sealMaxH ?>px;max-width:100%;">
                <?php else: ?>
                    <div style="opacity:0.6;">—</div>
                <?php endif; ?>
                <?php endif; ?>
        <?php
        break;

    case 'lab_firmas_approver_signature':
        $lfS    = is_array($lab_firmas_style ?? null) ? $lab_firmas_style : [];
        $fr     = is_array($pdf_firma_row ?? null) ? $pdf_firma_row : [];
        $sigMaxH = max(30, min(160, (int) ($lfS['signature_max_height_px'] ?? 72)));
        $sigMaxW = max(80, min(400, (int) ($lfS['signature_max_width_px'] ?? 220)));
        $sig    = trim((string) ($fr['approver_signature'] ?? ''));
        $sigUri = ($sig !== '') ? report_image_src_for_variant($sig, $pdfVariant) : '';
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
                    <img src="<?= report_pdf_img_src_attr($sigUri) ?>" alt="" class="pdf-lab-f-img" style="max-height:<?= (int) $sigMaxH ?>px;max-width:<?= (int) $sigMaxW ?>px;">
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
                        <img src="<?= report_pdf_img_src_attr($sigUri) ?>" alt="" class="pdf-lab-f-img" style="max-height:<?= (int) $sigMaxH ?>px;max-width:<?= (int) $sigMaxW ?>px;">
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
