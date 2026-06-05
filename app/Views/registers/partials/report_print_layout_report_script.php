<?php
/**
 * Recolección y presentación del informe de maquetación de impresión directa.
 *
 * @var float  $print_page_height_mm
 * @var float  $print_page_width_mm
 * @var string $print_page_css_size
 * @var string $print_paper_label
 * @var float  $mt
 * @var float  $mr
 * @var float  $mb
 * @var float  $ml
 * @var bool   $pdf_footer_enabled
 * @var bool   $layout_report_mode
 */
?>
<script>
(function() {
    var cfg = {
        pageHeightMm: <?= json_encode((float) ($print_page_height_mm ?? 279.4)) ?>,
        pageWidthMm: <?= json_encode((float) ($print_page_width_mm ?? 215.9)) ?>,
        pageCssSize: <?= json_encode((string) ($print_page_css_size ?? 'letter portrait')) ?>,
        paperLabel: <?= json_encode((string) ($print_paper_label ?? 'Letter')) ?>,
        marginTopMm: <?= json_encode((float) ($mt ?? 15)) ?>,
        marginRightMm: <?= json_encode((float) ($mr ?? 15)) ?>,
        marginBottomMm: <?= json_encode((float) ($mb ?? 15)) ?>,
        marginLeftMm: <?= json_encode((float) ($ml ?? 15)) ?>,
        footerEnabled: <?= ! empty($pdf_footer_enabled) ? 'true' : 'false' ?>,
        layoutReportMode: <?= ! empty($layout_report_mode) ? 'true' : 'false' ?>
    };

    function mmFmt(value) {
        if (!isFinite(value)) {
            return '—';
        }
        return (Math.round(value * 100) / 100) + ' mm';
    }

    function pxFmt(value) {
        if (!isFinite(value)) {
            return '—';
        }
        return Math.round(value) + ' px';
    }

    function setText(id, text) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = text;
        }
    }

    function measureResultsHeightPx(container) {
        var firstGrupo = container.querySelector('.report-pdf-grupo-prueba');
        if (!firstGrupo) {
            return 0;
        }
        var footer = container.querySelector('.pdf-ft-block.footer-grid');
        var bottom = footer ? footer.offsetTop : container.scrollHeight;
        return Math.max(0, bottom - firstGrupo.offsetTop);
    }

    function collectReportPrintLayoutReport() {
        var container = document.querySelector('.pdf-main-stack') || document.body;

        if (typeof window.syncReportPrintLayoutMetrics === 'function') {
            window.syncReportPrintLayoutMetrics();
        }

        var metrics = null;
        if (window.reportPrintPagination && typeof window.reportPrintPagination.buildMetrics === 'function') {
            metrics = window.reportPrintPagination.buildMetrics(container);
        }

        var pxToMm = (window.reportPrintPagination && window.reportPrintPagination.pxToMm)
            ? window.reportPrintPagination.pxToMm
            : function(px) { return px / (96 / 25.4); };

        var footerReserveMm = metrics ? metrics.footerReserveMM : 0;
        var footerHeightMm = metrics ? metrics.footerHeightMM : 0;
        var headerHeightMm = metrics ? metrics.headerHeightMM : 0;
        var headerHeightPx = metrics ? metrics.headerHeightPx : 0;
        var firstPageContentMm = metrics ? metrics.firstPageContentMM : 0;
        var nextPageContentMm = metrics ? metrics.nextPageContentMM : 0;
        var totalHeightMm = metrics ? metrics.totalContentMM : pxToMm(container.scrollHeight || 0);
        var totalHeightPx = container.scrollHeight || 0;
        var pages = metrics ? metrics.estimatedPages : 1;

        var resultsHeightPx = measureResultsHeightPx(container);
        var resultsHeightMm = pxToMm(resultsHeightPx);

        var pageMarginBottomMm = cfg.marginBottomMm + (cfg.footerEnabled ? footerReserveMm : 0);
        var sheetSizeText = cfg.paperLabel + ' (' + cfg.pageWidthMm + ' × ' + cfg.pageHeightMm + ' mm)';
        var now = new Date();

        return {
            sheetSizeText: sheetSizeText,
            sheetSizeDetail: '@page size: ' + cfg.pageCssSize,
            marginTopMm: cfg.marginTopMm,
            marginBottomMm: cfg.marginBottomMm,
            marginLeftMm: cfg.marginLeftMm,
            marginRightMm: cfg.marginRightMm,
            headerHeightMm: headerHeightMm,
            headerHeightPx: headerHeightPx,
            footerHeightMm: footerHeightMm,
            firstPageContentMm: firstPageContentMm,
            nextPageContentMm: nextPageContentMm,
            totalHeightMm: totalHeightMm,
            totalHeightPx: totalHeightPx,
            pageMarginBottomMm: pageMarginBottomMm,
            footerReserveMm: footerReserveMm,
            resultsHeightMm: resultsHeightMm,
            pagesEstimated: pages,
            measuredAt: now.toLocaleString('es-HN')
        };
    }

    function renderReportPrintLayoutReport() {
        var data = collectReportPrintLayoutReport();

        setText('lr_sheet_size', data.sheetSizeText);
        setText('lr_sheet_size_detail', data.sheetSizeDetail);
        setText('lr_margin_top', mmFmt(data.marginTopMm));
        setText('lr_margin_bottom', mmFmt(data.marginBottomMm));
        setText('lr_margin_left', mmFmt(data.marginLeftMm));
        setText('lr_margin_right', mmFmt(data.marginRightMm));
        setText('lr_header_height', mmFmt(data.headerHeightMm) + ' (' + pxFmt(data.headerHeightPx) + ')');
        setText('lr_footer_height', cfg.footerEnabled ? mmFmt(data.footerHeightMm) : 'N/A (pie deshabilitado)');
        setText('lr_footer_height_detail', cfg.footerEnabled
            ? 'Bloque pie fijo (.pdf-ft-block.footer-grid)'
            : 'El bloque pie no está habilitado en la plantilla');
        setText('lr_first_page_content', mmFmt(data.firstPageContentMm));
        setText('lr_first_page_content_detail', cfg.pageHeightMm + ' − ' + data.marginTopMm + ' − '
            + data.marginBottomMm + (cfg.footerEnabled ? ' − ' + data.footerReserveMm : '')
            + ' − ' + data.headerHeightMm + ' mm');
        setText('lr_next_page_content', mmFmt(data.nextPageContentMm));
        setText('lr_next_page_content_detail', cfg.pageHeightMm + ' − ' + data.marginTopMm + ' − '
            + data.marginBottomMm + (cfg.footerEnabled ? ' − ' + data.footerReserveMm : '') + ' mm');
        setText('lr_total_height', mmFmt(data.totalHeightMm) + ' (' + pxFmt(data.totalHeightPx) + ')');
        setText('lr_total_height_detail', 'scrollHeight de .pdf-main-stack');
        setText('lr_page_margin_bottom', mmFmt(data.pageMarginBottomMm));
        setText('lr_footer_reserve', cfg.footerEnabled ? mmFmt(data.footerReserveMm) : '0 mm');
        setText('lr_results_height', mmFmt(data.resultsHeightMm));
        setText('lr_pages_estimated', String(data.pagesEstimated));
        setText('lr_pages_estimated_detail', 'Hoja 1: ' + mmFmt(data.firstPageContentMm)
            + ' · siguientes: ' + mmFmt(data.nextPageContentMm));
        setText('lr_measured_at', data.measuredAt);

        return data;
    }

    window.collectReportPrintLayoutReport = collectReportPrintLayoutReport;
    window.renderReportPrintLayoutReport = renderReportPrintLayoutReport;

    function bindLayoutReportUi() {
        var panel = document.getElementById('report-print-layout-report');
        if (!panel) {
            return;
        }
        var btnRefresh = document.getElementById('btn_layout_report_refresh');
        var btnPrint = document.getElementById('btn_layout_report_print');
        if (btnRefresh) {
            btnRefresh.addEventListener('click', function() {
                renderReportPrintLayoutReport();
            });
        }
        if (btnPrint) {
            btnPrint.addEventListener('click', function() {
                renderReportPrintLayoutReport();
                window.print();
            });
        }
    }

    function initLayoutReport() {
        bindLayoutReportUi();
        renderReportPrintLayoutReport();
    }

    if (document.readyState === 'complete') {
        initLayoutReport();
    } else {
        window.addEventListener('load', initLayoutReport);
    }
})();
</script>
