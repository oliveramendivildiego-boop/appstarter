<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprimir resultados - <?= esc($paciente->first_name ?? '') ?> <?= esc($paciente->last_name_fa ?? '') ?></title>
    <base href="<?= base_url() ?>" />
    <?php
    $reportPdfCssRel = 'assets/css/report_pdf.css';
    $reportPdfCssFs = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $reportPdfCssRel);
    $reportPdfCssVer = is_file($reportPdfCssFs) ? (int) filemtime($reportPdfCssFs) : (int) time();
    ?>
    <link rel="stylesheet" href="<?= base_url($reportPdfCssRel) ?>?v=<?= $reportPdfCssVer ?>" />
    <link rel="stylesheet" href="<?= base_url('css/dom_print.css') ?>" media="print" />
    <?php
    $pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
    $mm = is_array($pl['margins_mm'] ?? null)
        ? $pl['margins_mm']
        : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
    $mt = (float) ($mm['top'] ?? 15);
    $mr = (float) ($mm['right'] ?? 15);
    $mb = (float) ($mm['bottom'] ?? 15);
    $ml = (float) ($mm['left'] ?? 15);
    $ps = is_array($pl['page_style'] ?? null) ? $pl['page_style'] : \App\Services\ReportPdfLayoutService::defaultPageStyleStatic();
    $ch = \App\Services\ReportPdfLayoutService::normalizeCardHeaderStyle($ps['card_header'] ?? []);
    $hs = \App\Services\ReportPdfLayoutService::normalizeHeaderSectionStyle($ps['header_section'] ?? []);
    $ns = \App\Services\ReportPdfLayoutService::normalizeNotesStyle($ps['notes'] ?? []);
    $lf = \App\Services\ReportPdfLayoutService::normalizeLabFirmasStyle($ps['lab_firmas'] ?? []);
    $lfBodyBg = ! empty($lf['body_transparent']) ? 'transparent' : (string) $lf['body_bg_color'];
    $lfColBorderW = max(0, min(4, (int) ($lf['column_border_width_px'] ?? 1)));
    $lfColBorderColor = (string) ($lf['column_border_color'] ?? '#DDDDDD');
    $hg = \App\Services\ReportPdfLayoutService::normalizeHeaderGridStyle($ps['header_grid'] ?? []);
    $pd = \App\Services\ReportPdfLayoutService::normalizePatientDoctorGridStyle($ps['patient_doctor_grid'] ?? []);
    $ft = \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle($ps['footer_grid'] ?? []);
    $hgBodyBg = ! empty($hg['body_transparent']) ? 'transparent' : (string) $hg['body_bg_color'];
    $pdBodyBg = ! empty($pd['body_transparent']) ? 'transparent' : (string) $pd['body_bg_color'];
    $ftBodyBg = ! empty($ft['body_transparent']) ? 'transparent' : (string) $ft['body_bg_color'];
    $hgColW = max(0, min(4, (int) ($hg['column_border_width_px'] ?? 0)));
    $pdColW = max(0, min(4, (int) ($pd['column_border_width_px'] ?? 0)));
    $ftColW = max(0, min(4, (int) ($ft['column_border_width_px'] ?? 0)));
    $nsTitleBg = ! empty($ns['title_transparent']) ? 'transparent' : (string) $ns['title_bg_color'];
    $nsBodyBg = ! empty($ns['body_transparent']) ? 'transparent' : (string) $ns['body_bg_color'];
    $nsColW = max(0, min(4, (int) ($ns['column_border_width_px'] ?? 1)));
    $nsColColor = (string) ($ns['column_border_color'] ?? '#DDDDDD');
    $chCardBg = ! empty($ch['bg_transparent']) ? 'transparent' : (string) $ch['bg_color'];
    $rs = \App\Services\ReportPdfLayoutService::normalizeResultsTableStyle($ps['results_table'] ?? []);
    $rsBodyBg = ! empty($rs['body_transparent']) ? 'transparent' : (string) $rs['body_bg_color'];
    $rsSegBg = ! empty($rs['segment_transparent']) ? 'transparent' : (string) $rs['segment_bg_color'];
    $segShadowMap = [
        'none' => 'none',
        'soft' => '0 1px 2px rgba(0,0,0,0.18)',
        'medium' => '0 1.5px 3px rgba(0,0,0,0.26)',
        'strong' => '0 2px 5px rgba(0,0,0,0.34)',
    ];
    $rsSegShadow = $segShadowMap[$rs['segment_shadow']] ?? 'none';
    $rid = (int) ($registro_id ?? 0);
    $pdfFooterEnabled = false;
    foreach (is_array($pl['blocks'] ?? null) ? $pl['blocks'] : [] as $fb) {
        if (! empty($fb['enabled']) && (string) ($fb['id'] ?? '') === 'footer') {
            $pdfFooterEnabled = true;
            break;
        }
    }
    $pdfFooterReserveMm = 22.0;
    $printBottomMarginMm = $mb + ($pdfFooterEnabled ? $pdfFooterReserveMm : 0.0);
    $pdfFooterStripBg   = ($ftBodyBg !== 'transparent') ? $ftBodyBg : '#ffffff';
    $ftTopOn            = ! empty($ft['section_top_border_enabled']);
    $ftTopW             = max(0, min(6, (int) ($ft['section_top_border_width_px'] ?? 1)));
    $ftTopColor         = (string) ($ft['section_top_border_color'] ?? '#DDDDDD');
    $ftTopStyleCss      = ($ftTopOn && $ftTopW > 0) ? 'solid' : 'none';
    $ftTopWpx           = ($ftTopOn && $ftTopW > 0) ? $ftTopW : 0;
    $printPaper         = strtolower((string) ($lab_config['print_paper_size'] ?? 'letter'));
    if (! in_array($printPaper, ['letter', 'a4', 'legal'], true)) {
        $printPaper = 'letter';
    }
    // Calibración fina para impresión física: algunos drivers dejan el footer visualmente alto.
    $printFooterNudgeMm = ($pdfFooterEnabled && $printPaper === 'a4') ? -2.0 : (($pdfFooterEnabled && in_array($printPaper, ['letter', 'legal'], true)) ? -2.5 : 0.0);
    $printPageCssSize = ($printPaper === 'a4') ? 'A4 portrait' : (($printPaper === 'legal') ? 'legal portrait' : 'letter portrait');
    $pp = \App\Services\ReportPdfLayoutService::normalizePrintPaginationStyle($ps['print_pagination'] ?? []);
    $printPaginationEnabled = ! empty($pp['enabled']);
    // Si la plantilla ya define pie de página, no superponer paginación fija del navegador.
    $renderFixedPrintPagination = $printPaginationEnabled && ! $pdfFooterEnabled;
    $printPaginationLabelText = (string) ($pp['label_text'] ?? 'Página');
    $ppToCssPos = static function (string $pos, float $mt, float $mr, float $mb, float $ml): string {
        $parts = explode('-', strtolower(trim($pos)), 2);
        $v = $parts[0] ?? 'bottom';
        $h = $parts[1] ?? 'right';
        $css = ($v === 'top')
            ? ('top:' . max(1, $mt) . 'mm;')
            : ('bottom:' . max(1, $mb) . 'mm;');
        if ($h === 'left') {
            $css .= 'left:' . max(1, $ml) . 'mm;';
        } elseif ($h === 'center') {
            $css .= 'left:50%;transform:translateX(-50%);';
        } else {
            $css .= 'right:' . max(1, $mr) . 'mm;';
        }
        return $css;
    };
    $printPagLabelCssPos = $ppToCssPos((string) ($pp['label_position'] ?? 'bottom-left'), $mt, $mr, $mb, $ml);
    $printPagValueCssPos = $ppToCssPos((string) ($pp['value_position'] ?? 'bottom-right'), $mt, $mr, $mb, $ml);
    ?>
    <style>
        @page {
            size: <?= esc($printPageCssSize) ?>;
            margin: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) $printBottomMarginMm) ?>mm <?= esc((string) $ml) ?>mm;
        }
        body { margin: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) $mb) ?>mm <?= esc((string) $ml) ?>mm !important; position: relative; background: #fff; }
        <?php if ($pdfFooterEnabled): ?>
        .pdf-ft-block.footer-grid {
            position: fixed;
            left: 0;
            right: 0;
            bottom: <?= esc((string) $printFooterNudgeMm) ?>mm;
            z-index: 2;
            margin-top: 0 !important;
            padding-top: 6px;
            padding-left: <?= esc((string) $ml) ?>mm;
            padding-right: <?= esc((string) $mr) ?>mm;
            padding-bottom: <?= esc((string) $mb) ?>mm;
            background: <?= esc($pdfFooterStripBg) ?>;
            box-sizing: border-box;
        }
        <?php endif; ?>
        :root {
            --pdf-card-header-bg: <?= esc($chCardBg) ?>;
            --pdf-card-header-color: <?= esc($ch['text_color']) ?>;
            --pdf-card-header-font-family: "<?= esc($ch['font_family']) ?>";
            --pdf-card-header-font-size: <?= esc((string) $ch['font_size_pt']) ?>pt;
            --pdf-card-header-font-weight: <?= esc($ch['font_weight']) ?>;
            --pdf-card-header-font-style: <?= esc($ch['font_style']) ?>;
            --pdf-card-header-transform: <?= esc($ch['text_transform']) ?>;
            --pdf-header-separator-color: <?= esc($hs['separator_color']) ?>;
            --pdf-notes-title-bg: <?= esc($nsTitleBg) ?>;
            --pdf-notes-title-color: <?= esc($ns['title_text_color']) ?>;
            --pdf-notes-body-bg: <?= esc($nsBodyBg) ?>;
            --pdf-notes-body-color: <?= esc($ns['body_text_color']) ?>;
            --pdf-notes-column-border-width: <?= esc((string) $nsColW) ?>px;
            --pdf-notes-column-border-color: <?= esc($nsColColor) ?>;
            --pdf-notes-font-family: "<?= esc($ns['font_family']) ?>";
            --pdf-notes-font-size: <?= esc((string) $ns['font_size_pt']) ?>pt;
            --pdf-notes-font-weight: <?= esc($ns['font_weight']) ?>;
            --pdf-notes-font-style: <?= esc($ns['font_style']) ?>;
            --pdf-notes-transform: <?= esc($ns['text_transform']) ?>;
            --pdf-notes-line-height: <?= esc((string) $ns['line_height']) ?>;
            --pdf-lf-title-bg: <?= esc($lf['title_bg_color']) ?>;
            --pdf-lf-title-color: <?= esc($lf['title_text_color']) ?>;
            --pdf-lf-body-bg: <?= esc($lfBodyBg) ?>;
            --pdf-lf-body-color: <?= esc($lf['body_text_color']) ?>;
            --pdf-lf-column-border-width: <?= esc((string) $lfColBorderW) ?>px;
            --pdf-lf-column-border-color: <?= esc($lfColBorderColor) ?>;
            --pdf-lf-font-family: "<?= esc($lf['font_family']) ?>";
            --pdf-lf-font-size: <?= esc((string) $lf['font_size_pt']) ?>pt;
            --pdf-lf-font-weight: <?= esc($lf['font_weight']) ?>;
            --pdf-lf-font-style: <?= esc($lf['font_style']) ?>;
            --pdf-lf-transform: <?= esc($lf['text_transform']) ?>;
            --pdf-lf-line-height: <?= esc((string) $lf['line_height']) ?>;
            --pdf-hg-body-bg: <?= esc($hgBodyBg) ?>;
            --pdf-hg-body-color: <?= esc($hg['body_text_color']) ?>;
            --pdf-hg-font-family: "<?= esc($hg['font_family']) ?>";
            --pdf-hg-font-size: <?= esc((string) $hg['font_size_pt']) ?>pt;
            --pdf-hg-font-weight: <?= esc($hg['font_weight']) ?>;
            --pdf-hg-font-style: <?= esc($hg['font_style']) ?>;
            --pdf-hg-transform: <?= esc($hg['text_transform']) ?>;
            --pdf-hg-line-height: <?= esc((string) $hg['line_height']) ?>;
            --pdf-hg-column-border-width: <?= esc((string) $hgColW) ?>px;
            --pdf-hg-column-border-color: <?= esc((string) ($hg['column_border_color'] ?? '#DDDDDD')) ?>;
            --pdf-qr-size-percent: <?= (int) ($hg['qr_size_percent'] ?? 100) ?>;
            --pdf-pd-body-bg: <?= esc($pdBodyBg) ?>;
            --pdf-pd-body-color: <?= esc($pd['body_text_color']) ?>;
            --pdf-pd-font-family: "<?= esc($pd['font_family']) ?>";
            --pdf-pd-font-size: <?= esc((string) $pd['font_size_pt']) ?>pt;
            --pdf-pd-font-weight: <?= esc($pd['font_weight']) ?>;
            --pdf-pd-font-style: <?= esc($pd['font_style']) ?>;
            --pdf-pd-transform: <?= esc($pd['text_transform']) ?>;
            --pdf-pd-line-height: <?= esc((string) $pd['line_height']) ?>;
            --pdf-pd-column-border-width: <?= esc((string) $pdColW) ?>px;
            --pdf-pd-column-border-color: <?= esc((string) ($pd['column_border_color'] ?? '#DDDDDD')) ?>;
            --pdf-ft-body-bg: <?= esc($ftBodyBg) ?>;
            --pdf-ft-body-color: <?= esc($ft['body_text_color']) ?>;
            --pdf-ft-font-family: "<?= esc($ft['font_family']) ?>";
            --pdf-ft-font-size: <?= esc((string) $ft['font_size_pt']) ?>pt;
            --pdf-ft-font-weight: <?= esc($ft['font_weight']) ?>;
            --pdf-ft-font-style: <?= esc($ft['font_style']) ?>;
            --pdf-ft-transform: <?= esc($ft['text_transform']) ?>;
            --pdf-ft-line-height: <?= esc((string) $ft['line_height']) ?>;
            --pdf-ft-column-border-width: <?= esc((string) $ftColW) ?>px;
            --pdf-ft-column-border-color: <?= esc((string) ($ft['column_border_color'] ?? '#DDDDDD')) ?>;
            --pdf-ft-company-color: <?= esc((string) ($ft['footer_company_text_color'] ?? $ft['body_text_color'])) ?>;
            --pdf-ft-label-generated-color: <?= esc((string) ($ft['label_footer_generated_color'] ?? $ft['body_text_color'])) ?>;
            --pdf-ft-datetime-color: <?= esc((string) ($ft['label_footer_datetime_color'] ?? $ft['body_text_color'])) ?>;
            --pdf-ft-policy-color: <?= esc((string) ($ft['footer_policy_text_color'] ?? $ft['body_text_color'])) ?>;
            --pdf-ft-section-top-border-width: <?= esc((string) $ftTopWpx) ?>px;
            --pdf-ft-section-top-border-style: <?= esc($ftTopStyleCss) ?>;
            --pdf-ft-section-top-border-color: <?= esc($ftTopColor) ?>;
            --pdf-results-header-bg: <?= esc($rs['header_bg_color']) ?>;
            --pdf-results-header-color: <?= esc($rs['header_text_color']) ?>;
            --pdf-results-body-bg: <?= esc($rsBodyBg) ?>;
            --pdf-results-body-color: <?= esc($rs['body_text_color']) ?>;
            --pdf-results-border-color: <?= esc($rs['border_color']) ?>;
            --pdf-results-segment-bg: <?= esc($rsSegBg) ?>;
            --pdf-results-segment-border: <?= esc($rs['segment_border_color']) ?>;
            --pdf-results-segment-border-width: <?= esc((string) $rs['segment_border_width_px']) ?>px;
            --pdf-results-segment-shadow: <?= esc($rsSegShadow) ?>;
            --pdf-results-font-family: "<?= esc($rs['font_family']) ?>";
            --pdf-results-font-size: <?= esc((string) $rs['font_size_pt']) ?>pt;
            --pdf-results-font-weight: <?= esc($rs['font_weight']) ?>;
            --pdf-results-font-style: <?= esc($rs['font_style']) ?>;
            --pdf-results-transform: <?= esc($rs['text_transform']) ?>;
            --pdf-results-line-height: <?= esc((string) $rs['line_height']) ?>;
            --pdf-results-cell-padding-v: <?= (int) ($rs['cell_padding_v_px'] ?? 6) ?>px;
            --pdf-results-matrix-align: <?= esc((string) ($rs['matrix_text_align'] ?? 'center')) ?>;
            --pdf-results-matrix-vertical-align: <?= esc((string) ($rs['matrix_vertical_align'] ?? 'middle')) ?>;
            --pdf-results-matrix-color: <?= esc((string) ($rs['matrix_text_color'] ?? $rs['body_text_color'])) ?>;
            --pdf-results-matrix-font-size: <?= esc((string) ($rs['matrix_font_size_pt'] ?? $rs['font_size_pt'])) ?>pt;
            --pdf-results-matrix-font-weight: <?= esc((string) ($rs['matrix_font_weight'] ?? $rs['font_weight'])) ?>;
            --pdf-results-matrix-font-style: <?= esc((string) ($rs['matrix_font_style'] ?? $rs['font_style'])) ?>;
            --pdf-results-matrix-transform: <?= esc((string) ($rs['matrix_text_transform'] ?? $rs['text_transform'])) ?>;
            --pdf-results-matrix-header-color: <?= esc((string) ($rs['matrix_header_text_color'] ?? $rs['header_text_color'])) ?>;
            --pdf-results-matrix-header-font-family: "<?= esc((string) ($rs['matrix_header_font_family'] ?? $rs['font_family'])) ?>";
            --pdf-results-matrix-header-font-size: <?= esc((string) ($rs['matrix_header_font_size_pt'] ?? $rs['font_size_pt'])) ?>pt;
            --pdf-results-matrix-header-font-weight: <?= esc((string) ($rs['matrix_header_font_weight'] ?? $rs['font_weight'])) ?>;
            --pdf-results-matrix-header-font-style: <?= esc((string) ($rs['matrix_header_font_style'] ?? $rs['font_style'])) ?>;
            --pdf-results-matrix-header-transform: <?= esc((string) ($rs['matrix_header_text_transform'] ?? $rs['text_transform'])) ?>;
            --pdf-results-matrix-col-population-align: <?= esc((string) ($rs['matrix_col_population_align'] ?? 'left')) ?>;
            --pdf-results-matrix-col-parameter-align: <?= esc((string) ($rs['matrix_col_parameter_align'] ?? 'left')) ?>;
            --pdf-results-matrix-col-sex-align: <?= esc((string) ($rs['matrix_col_sex_align'] ?? 'center')) ?>;
            --pdf-results-matrix-col-reference-align: <?= esc((string) ($rs['matrix_col_reference_align'] ?? 'center')) ?>;
            --pdf-results-matrix-hdr-population-align: <?= esc((string) ($rs['matrix_hdr_population_align'] ?? 'left')) ?>;
            --pdf-results-matrix-hdr-parameter-align: <?= esc((string) ($rs['matrix_hdr_parameter_align'] ?? 'left')) ?>;
            --pdf-results-matrix-hdr-sex-align: <?= esc((string) ($rs['matrix_hdr_sex_align'] ?? 'center')) ?>;
            --pdf-results-matrix-hdr-reference-align: <?= esc((string) ($rs['matrix_hdr_reference_align'] ?? 'center')) ?>;
        }
        table.results th {
            background: <?= esc($rs['header_bg_color']) ?> !important;
            color: <?= esc($rs['header_text_color']) ?> !important;
        }
        table.results td {
            background: <?= esc($rsBodyBg) ?> !important;
            color: <?= esc($rs['body_text_color']) ?> !important;
        }
        table.results th,
        table.results td {
            border-color: <?= esc($rs['border_color']) ?> !important;
            font-family: "<?= esc($rs['font_family']) ?>", sans-serif !important;
            font-size: <?= esc((string) $rs['font_size_pt']) ?>pt !important;
            font-weight: <?= esc($rs['font_weight']) ?> !important;
            font-style: <?= esc($rs['font_style']) ?> !important;
            text-transform: <?= esc($rs['text_transform']) ?> !important;
            line-height: <?= esc((string) $rs['line_height']) ?> !important;
        }
        table.results:not(.pdf-notes-table) th,
        table.results:not(.pdf-notes-table) td {
            padding: <?= (int) ($rs['cell_padding_v_px'] ?? 6) ?>px 8px !important;
        }
        table.results td.out-range,
        table.results .out-range {
            color: #c00 !important;
            font-weight: 700 !important;
        }
        .report-refs-matrix th,
        .report-refs-matrix td {
            vertical-align: var(--pdf-results-matrix-vertical-align) !important;
            color: var(--pdf-results-matrix-color) !important;
        }
        .report-refs-matrix td {
            font-size: var(--pdf-results-matrix-font-size) !important;
            font-weight: var(--pdf-results-matrix-font-weight) !important;
            font-style: var(--pdf-results-matrix-font-style) !important;
            text-transform: var(--pdf-results-matrix-transform) !important;
        }
        .report-refs-matrix th {
            color: var(--pdf-results-matrix-header-color) !important;
            font-family: var(--pdf-results-matrix-header-font-family), sans-serif !important;
            font-size: var(--pdf-results-matrix-header-font-size) !important;
            font-weight: var(--pdf-results-matrix-header-font-weight) !important;
            font-style: var(--pdf-results-matrix-header-font-style) !important;
            text-transform: var(--pdf-results-matrix-header-transform) !important;
        }
        .report-refs-matrix th.matrix-col-population { text-align: var(--pdf-results-matrix-hdr-population-align) !important; }
        .report-refs-matrix th.matrix-col-parameter { text-align: var(--pdf-results-matrix-hdr-parameter-align) !important; }
        .report-refs-matrix th.matrix-col-sex { text-align: var(--pdf-results-matrix-hdr-sex-align) !important; }
        .report-refs-matrix th.matrix-col-reference { text-align: var(--pdf-results-matrix-hdr-reference-align) !important; }
        .report-refs-matrix td.matrix-col-population { text-align: var(--pdf-results-matrix-col-population-align) !important; }
        .report-refs-matrix td.matrix-col-parameter { text-align: var(--pdf-results-matrix-col-parameter-align) !important; }
        .report-refs-matrix td.matrix-col-sex { text-align: var(--pdf-results-matrix-col-sex-align) !important; }
        .report-refs-matrix td.matrix-col-reference { text-align: var(--pdf-results-matrix-col-reference-align) !important; }
        table.results.pdf-notes-table td.pdf-notes-cell {
            background: <?= esc($nsBodyBg) ?> !important;
            color: <?= esc($ns['body_text_color']) ?> !important;
            border-color: <?= esc($nsColColor) ?> !important;
            font-family: "<?= esc($ns['font_family']) ?>", sans-serif !important;
            font-size: <?= esc((string) $ns['font_size_pt']) ?>pt !important;
            font-weight: <?= esc($ns['font_weight']) ?> !important;
            font-style: <?= esc($ns['font_style']) ?> !important;
            text-transform: <?= esc($ns['text_transform']) ?> !important;
            line-height: <?= esc((string) $ns['line_height']) ?> !important;
        }
        .header-grid {
            border-bottom-color: <?= esc($hs['separator_color']) ?> !important;
        }
        .report-segment-title {
            background: <?= esc($rsSegBg) ?> !important;
            border-color: <?= esc($rs['segment_border_color']) ?> !important;
            border-width: <?= esc((string) $rs['segment_border_width_px']) ?>px !important;
            box-shadow: <?= esc($rsSegShadow) ?> !important;
        }
        .report-segment-title.pdf-card-header {
            background: <?= esc($rsSegBg) ?> !important;
            color: <?= esc($ch['text_color']) ?> !important;
            font-family: "<?= esc($ch['font_family']) ?>", sans-serif !important;
            font-size: <?= esc((string) $ch['font_size_pt']) ?>pt !important;
            font-weight: <?= esc($ch['font_weight']) ?> !important;
            font-style: <?= esc($ch['font_style']) ?> !important;
            text-transform: <?= esc($ch['text_transform']) ?> !important;
        }
        .report-print-toolbar { padding: 10px 12px; margin: -8px -8px 16px -8px; background: #f1f3f5; border-bottom: 1px solid #dee2e6; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .report-print-btn-primary { padding: 8px 16px; background: #0d6efd; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .report-print-btn-secondary { padding: 8px 16px; background: #fff; color: #333; border: 1px solid #ced4da; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        @media print {
            body {
                margin: 0 !important;
                padding: 0 !important;
            }
            .report-print-toolbar { display: none !important; }
            /* Fuerza a los navegadores a conservar colores de fondo en impresión. */
            body,
            table.results th,
            table.results td,
            .report-segment-title,
            .pdf-card-header,
            .pdf-notes-title,
            .pdf-notes-cell,
            .pdf-lab-f-title,
            .pdf-lab-f-cell {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
        /* En impresión directa (navegador), el total de páginas lo inyecta JS
         * para evitar valores 0 en motores sin soporte confiable de counter(pages). */
        body.js-total-pages-ready .pdf-counter-pages::before {
            content: '' !important;
        }
        .print-pagination-fixed {
            position: fixed;
            z-index: 20;
            font-size: 9pt;
            color: #333;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #d6d6d6;
            border-radius: 4px;
            padding: 2px 6px;
            line-height: 1.2;
        }
        .print-pagination-label-fixed {
            <?= esc($printPagLabelCssPos, 'css') ?>
        }
        .print-pagination-value-fixed {
            <?= esc($printPagValueCssPos, 'css') ?>
        }
    </style>
</head>
<body>
<div class="report-print-toolbar">
    <button type="button" class="report-print-btn-primary" onclick="window.print()">Imprimir de nuevo</button>
    <?php if ($rid > 0): ?>
    <a href="<?= site_url('registers/viewreport/' . $rid) ?>" class="report-print-btn-secondary">Volver al reporte</a>
    <?php endif; ?>
</div>
<?php if ($renderFixedPrintPagination): ?>
<div class="print-pagination-fixed print-pagination-label-fixed" aria-hidden="true">
    <?= esc($printPaginationLabelText) ?>
</div>
<div class="print-pagination-fixed print-pagination-value-fixed" aria-hidden="true">
    <span class="pdf-counter-page"></span> / <span class="print-total-pages">1</span>
</div>
<?php endif; ?>
<?= view('registers/pdf/report_document', [
    'pdf_layout'        => $pdf_layout ?? [],
    'register_info'     => $register_info,
    'paciente'          => $paciente,
    'doctor'            => $doctor,
    'grupos'            => $grupos,
    'lab_config'        => $lab_config,
    'report_url'        => $report_url ?? '',
    'qr_data_uri'       => $qr_data_uri ?? '',
    'report_emitido_en' => $report_emitido_en ?? \App\Services\RegisterService::formatNowForReport(),
    'pdf_watermark_uri' => null,
    'pdf_logo_data_uri' => null,
    'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
    'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
    'report_lab_firmas'               => $report_lab_firmas ?? [],
    'report_pria_refs_consolidada'    => $report_pria_refs_consolidada ?? [],
]) ?>
<script>
(function() {
    var MM_TO_PX = 96 / 25.4;
    var PAGE_HEIGHT_MM = <?= json_encode($printPaper === 'a4' ? 297.0 : ($printPaper === 'legal' ? 355.6 : 279.4)) ?>;
    var marginTopMm = <?= json_encode((float) $mt) ?>;
    var marginBottomMm = <?= json_encode((float) $printBottomMarginMm) ?>;

    function estimateTotalPagesForPrint() {
        var content = document.querySelector('.pdf-main-stack') || document.body;
        var printableHeightMm = PAGE_HEIGHT_MM - marginTopMm - marginBottomMm;
        if (!isFinite(printableHeightMm) || printableHeightMm <= 0) printableHeightMm = 240;
        var printablePx = printableHeightMm * MM_TO_PX;
        if (!isFinite(printablePx) || printablePx <= 0) printablePx = 900;
        var total = Math.ceil(content.scrollHeight / printablePx);
        if (!isFinite(total) || total < 1) total = 1;
        return total;
    }

    function applyBrowserTotalPages() {
        var total = estimateTotalPagesForPrint();
        document.querySelectorAll('.pdf-counter-pages').forEach(function(el) {
            el.textContent = String(total);
        });
        document.querySelectorAll('.print-total-pages').forEach(function(el) {
            el.textContent = String(total);
        });
        document.body.classList.add('js-total-pages-ready');
    }

    function openPrintDialog() {
        applyBrowserTotalPages();
        window.print();
    }
    window.addEventListener('beforeprint', function() {
        applyBrowserTotalPages();
    });
    window.addEventListener('afterprint', function() {
        setTimeout(function() {
            window.close();
        }, 150);
    });
    if (document.readyState === 'complete') {
        setTimeout(openPrintDialog, 350);
    } else {
        window.addEventListener('load', function() {
            setTimeout(openPrintDialog, 350);
        });
    }
})();
</script>
</body>
</html>
