<?php
/**
 * Banda fija Paciente / No. Orden encima del pie en cada hoja (impresión navegador, 2+ páginas).
 *
 * @var bool   $order_sheet_header_enabled
 * @var float  $margin_bottom_mm
 * @var float  $margin_left_mm
 * @var float  $margin_right_mm
 * @var float  $gap_above_footer_mm
 */
if (empty($order_sheet_header_enabled)) {
    return;
}
$marginBottomMm = (float) ($margin_bottom_mm ?? 15);
$marginLeftMm   = (float) ($margin_left_mm ?? 15);
$marginRightMm  = (float) ($margin_right_mm ?? 15);
$gapAboveFooterMm = (float) ($gap_above_footer_mm ?? \App\Services\ReportPdfLayoutService::ORDER_SHEET_HEADER_GAP_ABOVE_FOOTER_MM);
?>
<script>
(function() {
    var cfg = {
        marginBottomMm: <?= json_encode($marginBottomMm) ?>,
        marginLeftMm: <?= json_encode($marginLeftMm) ?>,
        marginRightMm: <?= json_encode($marginRightMm) ?>,
        gapAboveFooterMm: <?= json_encode($gapAboveFooterMm) ?>
    };

    function readCssMm(varName, fallback) {
        var raw = getComputedStyle(document.documentElement).getPropertyValue(varName);
        var parsed = parseFloat(raw);
        return (isFinite(parsed) && parsed >= 0) ? parsed : fallback;
    }

    function resolveFooterReserveMm() {
        if (typeof window.measureReportPrintFooterReserveMm === 'function') {
            return window.measureReportPrintFooterReserveMm();
        }
        return readCssMm('--print-footer-reserve-mm', 0);
    }

    function deactivateOrderSheetHeaderBand() {
        document.body.classList.remove('js-order-sheet-header-active');
        var band = document.getElementById('pdf-order-sheet-header-band');
        if (!band) {
            return;
        }
        band.classList.remove('pdf-order-sheet-header-band--active');
        band.setAttribute('aria-hidden', 'true');
        band.style.removeProperty('position');
        band.style.removeProperty('left');
        band.style.removeProperty('right');
        band.style.removeProperty('bottom');
        band.style.removeProperty('z-index');
        band.style.removeProperty('background');
        band.style.removeProperty('display');
    }

    function activateOrderSheetHeaderBand() {
        deactivateOrderSheetHeaderBand();

        if (!window.reportPrintPagination) {
            return;
        }

        var container = window.reportPrintPagination.getPrintContainer();
        if (!container) {
            return;
        }

        var metrics = window.reportPrintPagination.buildMetrics(container);
        if (!metrics || !isFinite(metrics.estimatedPages) || metrics.estimatedPages < 2) {
            return;
        }

        var band = document.getElementById('pdf-order-sheet-header-band');
        if (!band) {
            return;
        }

        if (band.parentNode !== document.body) {
            document.body.appendChild(band);
        }

        var marginBottomMm = readCssMm('--print-margin-bottom-mm', cfg.marginBottomMm);
        var marginLeftMm = readCssMm('--print-margin-left-mm', cfg.marginLeftMm);
        var marginRightMm = readCssMm('--print-margin-right-mm', cfg.marginRightMm);
        var footerReserveMm = resolveFooterReserveMm();
        var bottomMm = marginBottomMm + footerReserveMm + cfg.gapAboveFooterMm;

        document.documentElement.style.setProperty('--print-order-sheet-bottom-mm', String(bottomMm));

        band.classList.add('pdf-order-sheet-header-band--active');
        band.setAttribute('aria-hidden', 'false');
        band.style.position = 'fixed';
        band.style.left = marginLeftMm + 'mm';
        band.style.right = marginRightMm + 'mm';
        band.style.bottom = bottomMm + 'mm';
        band.style.zIndex = '3';
        band.style.background = '#ffffff';
        band.style.margin = '0';
        band.style.padding = '0';
        band.style.boxSizing = 'border-box';

        document.body.classList.add('js-order-sheet-header-active');
    }

    window.injectOrderSheetHeadersFromPageTwo = activateOrderSheetHeaderBand;
})();
</script>
