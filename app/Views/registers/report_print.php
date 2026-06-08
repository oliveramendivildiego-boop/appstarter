<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprimir resultados - <?= esc($paciente->first_name ?? '') ?> <?= esc($paciente->last_name_fa ?? '') ?></title>
    <base href="<?= base_url() ?>" />
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

    $rid = (int) ($registro_id ?? 0);
    $pdfFooterEnabled = false;
    foreach (is_array($pl['blocks'] ?? null) ? $pl['blocks'] : [] as $fb) {
        if (! empty($fb['enabled']) && (string) ($fb['id'] ?? '') === 'footer') {
            $pdfFooterEnabled = true;
            break;
        }
    }
    $pdfFooterReserveMm = 22.0;

    $printPaper = strtolower((string) ($lab_config['print_paper_size'] ?? 'letter'));
    if (! in_array($printPaper, ['letter', 'a4', 'legal', 'custom'], true)) {
        $printPaper = 'letter';
    }
    $printPaperCustomW = max(50.0, min(999.0, (float) ($lab_config['print_paper_width_mm'] ?? 210)));
    $printPaperCustomH = max(50.0, min(999.0, (float) ($lab_config['print_paper_height_mm'] ?? 297)));
    if ($printPaperCustomW <= 0) {
        $printPaperCustomW = 210.0;
    }
    if ($printPaperCustomH <= 0) {
        $printPaperCustomH = 297.0;
    }
    if ($printPaper === 'custom') {
        $printPageCssSize = (string) $printPaperCustomW . 'mm ' . (string) $printPaperCustomH . 'mm';
    } else {
        $printPageCssSize = ($printPaper === 'a4') ? 'A4 portrait' : (($printPaper === 'legal') ? 'legal portrait' : 'letter portrait');
    }
    $printPageHeightMm = $printPaper === 'a4' ? 297.0 : ($printPaper === 'legal' ? 355.6 : ($printPaper === 'custom' ? $printPaperCustomH : 279.4));
    $printPageWidthMm  = $printPaper === 'a4' ? 210.0 : ($printPaper === 'custom' ? $printPaperCustomW : 215.9);
    $printPaperLabels  = [
        'letter' => 'Carta (Letter)',
        'a4'     => 'A4',
        'legal'  => 'Oficio (Legal)',
        'custom' => 'Personalizado',
    ];
    $printPaperLabel = $printPaperLabels[$printPaper] ?? 'Carta (Letter)';
    $layoutReportMode = ! empty($layout_report_mode);

    $gpbCfg = \App\Services\ReportPdfLayoutService::normalizeGrupoPruebaPageBreakStyle($ps['grupo_prueba_page_break'] ?? []);
    $gpbBodyClass = \App\Services\ReportPdfLayoutService::grupoPruebaPageBreakBodyClass($pl);
    $printSegmentBreakInside = ($gpbCfg['mode'] ?? '') === 'keep_together_if_fits'
        ? 'auto'
        : \App\Services\ReportPdfLayoutService::grupoPruebaPrintSegmentBreakInside($gpbCfg);
    $pp = \App\Services\ReportPdfLayoutService::normalizePrintPaginationStyle($ps['print_pagination'] ?? []);
    $printPaginationEnabled = ! empty($pp['enabled']);
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
    $osh = \App\Services\ReportPdfLayoutService::normalizeOrderSheetHeaderStyle($ps['order_sheet_header'] ?? []);
    $orderSheetHeaderEnabled = ! empty($osh['enabled']);
    $orderSheetHeaderReserveMm = 6.0;
    ?>
    <?= view('registers/partials/report_pdf_theme_styles', [
        'pdf_layout'                     => $pdf_layout ?? [],
        'use_sheet_padding_for_margins' => false,
        'browser_print_mode'            => true,
    ]) ?>
    <?= view('registers/partials/report_browser_print_styles', [
        'mt'                      => $mt,
        'mr'                      => $mr,
        'mb'                      => $mb,
        'ml'                      => $ml,
        'printPageCssSize'        => $printPageCssSize,
        'pdfFooterEnabled'        => $pdfFooterEnabled,
        'pdfFooterReserveMm'      => $pdfFooterReserveMm,
        'printSegmentBreakInside' => $printSegmentBreakInside,
        'printPagLabelCssPos'          => $printPagLabelCssPos,
        'printPagValueCssPos'          => $printPagValueCssPos,
        'orderSheetHeaderEnabled'      => $orderSheetHeaderEnabled,
        'orderSheetHeaderReserveMm'    => $orderSheetHeaderReserveMm,
    ]) ?>
</head>
<body class="report-browser-print<?= $gpbBodyClass !== '' ? ' ' . esc($gpbBodyClass, 'attr') : '' ?><?= $layoutReportMode ? ' report-print-layout-report-mode' : '' ?>">
<?php if ($layoutReportMode): ?>
<?= view('registers/partials/report_print_layout_report', [
    'registro_id'        => $rid,
    'layout_report_mode' => true,
]) ?>
<?php endif; ?>
<div class="report-print-toolbar">
    <button type="button" class="report-print-btn-primary" onclick="window.print()">Imprimir de nuevo</button>
    <?php if ($rid > 0): ?>
    <a href="<?= site_url('registers/printreport/' . $rid . '?layout_report=1') ?>" class="report-print-btn-secondary">Informe de maquetación</a>
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
    'analisis_variant'                => 'browser_print',
]) ?>
<?= view('registers/partials/report_order_sheet_header_print_script', [
    'order_sheet_header_enabled' => $orderSheetHeaderEnabled,
]) ?>
<?= view('registers/partials/report_print_pagination_metrics', [
    'page_height_mm'    => $printPageHeightMm,
    'margin_top_mm'     => (float) $mt,
    'margin_bottom_mm'  => (float) $mb,
    'footer_reserve_mm' => (float) $pdfFooterReserveMm,
    'footer_enabled'    => $pdfFooterEnabled,
]) ?>
<?= view('registers/partials/report_pdf_grupo_page_break_script', [
    'pdf_layout'         => $pdf_layout ?? [],
    'page_height_mm'     => $printPageHeightMm,
    'margin_top_mm'      => (float) $mt,
    'margin_bottom_mm'   => (float) $mb,
    'footer_reserve_mm'  => (float) $pdfFooterReserveMm,
    'footer_enabled'     => $pdfFooterEnabled,
]) ?>
<script>
(function() {
    var MM_TO_PX = 96 / 25.4;
    var PAGE_HEIGHT_MM = <?= json_encode($printPageHeightMm) ?>;
    var PAGE_CSS_SIZE = <?= json_encode($printPageCssSize) ?>;
    var marginTopMm = <?= json_encode((float) $mt) ?>;
    var marginRightMm = <?= json_encode((float) $mr) ?>;
    var marginBottomMm = <?= json_encode((float) $mb) ?>;
    var marginLeftMm = <?= json_encode((float) $ml) ?>;
    var defaultFooterReserveMm = <?= json_encode((float) $pdfFooterReserveMm) ?>;
    var footerEnabled = <?= $pdfFooterEnabled ? 'true' : 'false' ?>;
    var layoutReportMode = <?= $layoutReportMode ? 'true' : 'false' ?>;

    function pxToMm(px) {
        return px / MM_TO_PX;
    }
    window.reportPrintPxToMm = pxToMm;

    function measureFooterHeightMm() {
        if (!footerEnabled) {
            return 0;
        }
        var footer = document.querySelector('.pdf-ft-block.footer-grid');
        if (!footer) {
            return defaultFooterReserveMm;
        }
        var heightMm = pxToMm(footer.offsetHeight || footer.scrollHeight || 0);
        if (!isFinite(heightMm) || heightMm <= 0) {
            return defaultFooterReserveMm;
        }
        return Math.max(8, Math.min(80, Math.round(heightMm * 100) / 100));
    }
    window.measureReportPrintFooterHeightMm = measureFooterHeightMm;

    function measureFooterReserveMm() {
        if (!footerEnabled) {
            return 0;
        }
        var heightMm = measureFooterHeightMm();
        if (!isFinite(heightMm) || heightMm <= 0) {
            return defaultFooterReserveMm;
        }
        return Math.max(10, Math.min(90, Math.ceil((heightMm + 4) * 10) / 10));
    }
    window.measureReportPrintFooterReserveMm = measureFooterReserveMm;

    function applyPageMarginsStyle(footerHeightMm) {
        var styleEl = document.getElementById('report-print-page-margins');
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = 'report-print-page-margins';
            styleEl.media = 'print';
            document.head.appendChild(styleEl);
        }
        styleEl.textContent = '@page { size: ' + PAGE_CSS_SIZE + '; '
            + 'margin-top: ' + marginTopMm + 'mm; '
            + 'margin-right: ' + marginRightMm + 'mm; '
            + 'margin-bottom: ' + marginBottomMm + 'mm; '
            + 'margin-left: ' + marginLeftMm + 'mm; }';
        console.log('[report-print-margins]', {
            marginTopMM: marginTopMm,
            marginRightMM: marginRightMm,
            marginBottomMM: marginBottomMm,
            marginLeftMM: marginLeftMm,
            source: 'plantilla margins_mm + #report-print-page-margins'
        });
    }

    function syncReportPrintLayoutMetrics() {
        var footerHeightMm = measureFooterHeightMm();
        var footerReserveMm = measureFooterReserveMm();
        document.documentElement.style.setProperty('--print-footer-height-mm', String(footerHeightMm));
        document.documentElement.style.setProperty('--print-footer-reserve-mm', String(footerReserveMm));
        applyPageMarginsStyle(footerHeightMm);
        if (typeof window.updateReportPrintPageBreakMetrics === 'function') {
            window.updateReportPrintPageBreakMetrics({
                footerReserveMm: footerReserveMm
            });
        }
        return footerReserveMm;
    }

    function estimateTotalPagesForPrint() {
        if (window.reportPrintPagination && typeof window.reportPrintPagination.buildMetrics === 'function') {
            var container = window.reportPrintPagination.getPrintContainer();
            var metrics = window.reportPrintPagination.buildMetrics(container);
            if (metrics && isFinite(metrics.estimatedPages) && metrics.estimatedPages >= 1) {
                return metrics.estimatedPages;
            }
        }
        return 1;
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

    function preparePrintLayout() {
        syncReportPrintLayoutMetrics();
        if (typeof window.injectOrderSheetHeadersFromPageTwo === 'function') {
            window.injectOrderSheetHeadersFromPageTwo();
        }
        if (typeof window.applyReportPdfGrupoPageBreaks === 'function') {
            window.applyReportPdfGrupoPageBreaks();
        }
        applyBrowserTotalPages();
    }

    function openPrintDialog() {
        preparePrintLayout();
        window.print();
    }

    window.syncReportPrintLayoutMetrics = syncReportPrintLayoutMetrics;
    window.addEventListener('beforeprint', function() {
        preparePrintLayout();
        if (typeof window.renderReportPrintLayoutReport === 'function') {
            window.renderReportPrintLayoutReport();
        }
    });
    if (!layoutReportMode) {
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
    }
})();
</script>
<?php if ($layoutReportMode): ?>
<?= view('registers/partials/report_print_layout_report_script', [
    'print_page_height_mm' => $printPageHeightMm,
    'print_page_width_mm'  => $printPageWidthMm,
    'print_page_css_size'  => $printPageCssSize,
    'print_paper_label'    => $printPaperLabel,
    'mt'                   => $mt,
    'mr'                   => $mr,
    'mb'                   => $mb,
    'ml'                   => $ml,
    'pdf_footer_enabled'   => $pdfFooterEnabled,
    'layout_report_mode'   => true,
]) ?>
<?php endif; ?>
</body>
</html>
