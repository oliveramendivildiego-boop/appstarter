<?= $this->extend('layouts/main') ?>
<?php
$plViewHead = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$mmViewHead = is_array($plViewHead['margins_mm'] ?? null)
    ? $plViewHead['margins_mm']
    : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
$mtViewHead = (float) ($mmViewHead['top'] ?? 15);
$mbViewHead = (float) ($mmViewHead['bottom'] ?? 15);
$pageSizeViewHead = \App\Services\ReportPdfLayoutService::resolveGlobalPageSizeMm(is_array($lab_config ?? null) ? $lab_config : []);
$printPageHeightMmHead = (float) $pageSizeViewHead['height_mm'];
$printPageWidthMmHead = (float) $pageSizeViewHead['width_mm'];
$printPageCssSizeView = (string) $pageSizeViewHead['css_size'];
$pdfFooterEnabledView = false;
foreach (is_array($plViewHead['blocks'] ?? null) ? $plViewHead['blocks'] : [] as $fbView) {
    if (! empty($fbView['enabled']) && (string) ($fbView['id'] ?? '') === 'footer') {
        $pdfFooterEnabledView = true;
        break;
    }
}
$pdfFooterReserveMmView = $pdfFooterEnabledView
    ? \App\Services\ReportPdfLayoutService::estimatePdfFooterReserveMm($plViewHead)
    : 22.0;
$gpbBodyClassView = \App\Services\ReportPdfLayoutService::grupoPruebaPageBreakBodyClass($plViewHead);
$orderSheetHeaderEnabledView = \App\Services\ReportPdfLayoutService::isOrderSheetHeaderEnabledForLayout($plViewHead);
?>
<?= $this->section('title') ?>Reporte<?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<?= view('registers/partials/report_pdf_theme_styles', [
    'pdf_layout'                     => $pdf_layout ?? [],
    'use_sheet_padding_for_margins' => true,
]) ?>
<style>
.viewreport-actions-bar {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 12px 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.viewreport-actions-bar--sticky {
    position: sticky;
    top: 0;
    z-index: 100;
    margin-bottom: 16px;
}
.viewreport-actions-bar--bottom {
    margin-top: 16px;
}
.viewreport-pdf-shell {
    width: 100%;
    overflow-x: auto;
    padding: 8px 0 24px;
    background: #e9ecef;
}
.viewreport-pdf-sheet {
    position: relative;
    width: <?= esc((string) $printPageWidthMmHead) ?>mm;
    max-width: 100%;
    margin-left: auto;
    margin-right: auto;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.14);
    box-sizing: border-box;
}
.viewreport-pdf-sheet .pdf-watermark-layer {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    max-height: none;
    z-index: 0;
    pointer-events: none;
}
.viewreport-pdf-sheet .pdf-watermark-inner,
.viewreport-pdf-sheet .pdf-watermark-table,
.viewreport-pdf-sheet .pdf-watermark-td {
    height: 100% !important;
    min-height: <?= esc((string) $printPageHeightMmHead) ?>mm;
}
.viewreport-pdf-sheet .pdf-main-stack {
    position: relative;
    z-index: 1;
}
.viewreport-pdf-sheet .pdf-ft-block.footer-grid {
    position: relative;
    z-index: 2;
}
table.results td.resultado-texto-rico-cell .resultado-texto-rico strong,
table.results td.resultado-texto-rico-cell .resultado-texto-rico b {
    font-weight: 700 !important;
}
table.results td.resultado-texto-rico-cell .resultado-texto-rico em,
table.results td.resultado-texto-rico-cell .resultado-texto-rico i,
table.results td.resultado-texto-rico-cell .resultado-texto-rico span[style*="italic"],
table.results td.resultado-texto-rico-cell .resultado-texto-rico span[style*="oblique"] {
    font-style: italic !important;
}
table.results td.resultado-texto-rico-cell .resultado-texto-rico u {
    text-decoration: underline !important;
}
table.results td.report-interpretacion-alto {
    color: #dc3545 !important;
    font-weight: 700;
}
table.results td.report-interpretacion-bajo {
    color: #0d6efd !important;
    font-weight: 700;
}
<?php
// Ajustes visuales según modo de reporte del doctor (por defecto 'clinico')
$report_display_mode = 'clinico';
if (! empty($doctor)) {
    if (is_object($doctor)) {
        $report_display_mode = trim((string) ($doctor->display_mode ?? $report_display_mode));
    } elseif (is_array($doctor)) {
        $report_display_mode = trim((string) ($doctor['display_mode'] ?? $report_display_mode));
    }
}
// Determina modo de reporte: preferencia del doctor; si el registro fue creado sin doctor usar config "sin doctor"
$doctorIsSynthetic = false;
if (! empty($doctor)) {
    if (is_object($doctor)) {
        $report_display_mode = trim((string) ($doctor->display_mode ?? $report_display_mode));
        if (property_exists($doctor, 'report_sin_prefijo_medico') && $doctor->report_sin_prefijo_medico) {
            $doctorIsSynthetic = true;
        }
    } elseif (is_array($doctor)) {
        $report_display_mode = trim((string) ($doctor['display_mode'] ?? $report_display_mode));
        if (! empty($doctor['report_sin_prefijo_medico'])) {
            $doctorIsSynthetic = true;
        }
    }
}
if ($doctorIsSynthetic) {
    // usar configuración del laboratorio para órdenes sin doctor
    $cfgMode = is_array($lab_config ?? null) ? trim((string) ($lab_config['sin_doctor_report_mode'] ?? '')) : '';
    if ($cfgMode !== '' && in_array($cfgMode, ['clinico', 'neutral', 'semaforo'], true)) {
        $report_display_mode = $cfgMode;
    }
}
if ($report_display_mode === '') {
    $report_display_mode = 'clinico';
}
?>
<?php if ($report_display_mode === 'neutral'): ?>
/* Modo Neutral: quitar colores y peso fuerte */
table.results td.report-interpretacion-alto,
table.results td.report-interpretacion-bajo,
.results td.text-danger,
.results td.out-range {
    color: inherit !important;
    font-weight: normal !important;
}
<?php elseif ($report_display_mode === 'clinico'): ?>
/* Modo Clínico: colores clásicos por interpretación */
table.results td.report-interpretacion-alto { color: #dc3545 !important; font-weight: 700; }
table.results td.report-interpretacion-bajo { color: #0d6efd !important; font-weight: 700; }
/* Asegurar iconos siguen el mismo color */
.report-interpretacion-icon.text-danger { color: #dc3545 !important; }
.report-interpretacion-icon.text-primary { color: #0d6efd !important; }

<?php elseif ($report_display_mode === 'semaforo'): ?>
/* Modo Semáforo suave: colores más suaves e iconos (iconos se inyectan desde la plantilla) */
table.results td.report-interpretacion-alto { color: #c44b4b !important; font-weight: 600; }
table.results td.report-interpretacion-bajo { color: #3b82f6 !important; font-weight: 600; }
.report-interpretacion-icon { margin-right: 6px; opacity: 0.95; }
/* Forzar color en iconos incluso si el td padre tiene otra clase */
.report-interpretacion-icon.text-danger { color: #c44b4b !important; }
.report-interpretacion-icon.text-primary { color: #0d6efd !important; }
.report-interpretacion-icon.text-dark { color: #212529 !important; }
<?php endif; ?>
body.js-total-pages-ready .pdf-counter-pages::before {
    content: '' !important;
}
</style>
<?php if ($gpbBodyClassView !== ''): ?>
<?= view('registers/partials/report_browser_print_styles', [
    'mt'                          => (float) ($mmViewHead['top'] ?? 15),
    'mr'                          => (float) ($mmViewHead['right'] ?? 15),
    'mb'                          => (float) ($mmViewHead['bottom'] ?? 15),
    'ml'                          => (float) ($mmViewHead['left'] ?? 15),
    'printPageCssSize'            => $printPageCssSizeView,
    'pdfFooterEnabled'            => $pdfFooterEnabledView,
    'pdfFooterReserveMm'          => (float) $pdfFooterReserveMmView,
    'printSegmentBreakInside'     => 'auto',
    'printPagLabelCssPos'         => '',
    'printPagValueCssPos'         => '',
    'order_sheet_header_enabled'  => $orderSheetHeaderEnabledView,
]) ?>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_registers'), 'url' => site_url('registers')],
    ['label' => ($register_info->first_name ?? '') . ' ' . ($register_info->last_name_fa ?? ''), 'url' => site_url('registers/view/' . ($register_info->registro_id ?? ''))],
]]) ?>

<fieldset id="customer_basic_info">
<input type="hidden" name="registro_id" id="registro_id" value="<?= (int)($labotests_namecate ?? 0) ?>">

<?php
$grupos = $grupos ?? [];
$ridPdf = (int) ($labotests_namecate ?? 0);
$compOk = ! empty($comprobante_pdf_disponible ?? false);
$lblComp = ! empty($sin_billing_enabled ?? false) ? 'Factura (PDF)' : 'Recibo (PDF)';
?>

<?= view('registers/partials/report_viewreport_actions_bar', [
    'ridPdf'                   => $ridPdf,
    'compOk'                   => $compOk,
    'lblComp'                  => $lblComp,
    'envelope_print_available' => $envelope_print_available ?? false,
    'sticky'                   => true,
]) ?>

<?php if (empty($grupos)): ?>
<div class="alert alert-info mt-3">
    <i class="fa-solid fa-info-circle me-2"></i>No hay resultados cargados para esta orden. Complete los resultados en <a href="<?= site_url('registers/view/' . $ridPdf) ?>">Editar registro</a>.
</div>
<?php else:
helper(['qr', 'registro']);
$reportUrl = ! empty($public_resultados_token)
    ? site_url('resultados/' . $public_resultados_token)
    : site_url('registers/viewreport/' . $ridPdf);
$qrPx = \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout(is_array($pdf_layout ?? null) ? $pdf_layout : []);
$qr_data_uri = qr_base64($reportUrl, $qrPx);
?>
<div class="viewreport-pdf-shell">
    <div class="viewreport-pdf-sheet">
        <?php
        ob_start();
        echo view('registers/pdf/report_document', [
            'pdf_layout'                      => $pdf_layout ?? [],
            'register_info'                   => $register_info,
            'paciente'                        => $paciente,
            'doctor'                          => $doctor,
            'grupos'                          => $grupos,
            'lab_config'                      => $lab_config ?? [],
            'report_url'                      => $reportUrl,
            'qr_data_uri'                     => $qr_data_uri,
            'report_emitido_en'               => $report_emitido_en ?? \App\Services\RegisterService::formatNowForReport(),
            'pdf_watermark_uri'               => null,
            'pdf_logo_data_uri'               => null,
            'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
            'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
            'report_lab_firmas'               => $report_lab_firmas ?? [],
            'report_pria_refs_consolidada'    => $report_pria_refs_consolidada ?? [],
            'analisis_variant'                => 'screen_pdf',
            'report_layout_plan'              => $report_layout_plan ?? null,
            'report_layout_applier'           => $report_layout_applier ?? null,
        ]);
        echo \App\Services\RegisterService::replaceTotalPagesTokenForBrowser(ob_get_clean());
        ?>
    </div>
</div>
<?= view('registers/partials/report_viewreport_actions_bar', [
    'ridPdf'                   => $ridPdf,
    'compOk'                   => $compOk,
    'lblComp'                  => $lblComp,
    'envelope_print_available' => $envelope_print_available ?? false,
    'sticky'                   => false,
]) ?>
<?php endif; ?>

<?php if (! $compOk && ! empty($grupos)): ?>
<div class="small text-muted text-center mt-2 w-100">
    <?php if (! empty($comprobante_pdf_sin_registro_pago ?? false)): ?>
        <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i>Sin registro de pago en base de datos: cree o vincule el pago desde la lista de registros.
    <?php elseif (! empty($comprobante_pdf_pendiente_pago ?? false)): ?>
        <i class="fa-solid fa-circle-info me-1"></i>La descarga del comprobante queda disponible cuando el saldo sea 0 o el monto pagado cubra el total (revise en lista de registros → historial de pagos).
    <?php else: ?>
        <i class="fa-solid fa-circle-info me-1"></i>Si no puede descargar el comprobante, verifique el pago de la orden.
    <?php endif; ?>
</div>
<?php endif; ?>
</fieldset>
<?= view('partial/page_scroll_nav', ['scroll_nav_id' => 'viewreport-scroll-nav']) ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (! empty($grupos)): ?>
<?php
$mmViewScripts = is_array($plViewHead['margins_mm'] ?? null)
    ? $plViewHead['margins_mm']
    : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
?>
<?= view('registers/partials/report_print_pagination_metrics', [
    'page_height_mm'              => $printPageHeightMmHead,
    'margin_top_mm'               => (float) ($mmViewScripts['top'] ?? 15),
    'margin_bottom_mm'            => (float) ($mmViewScripts['bottom'] ?? 15),
    'footer_reserve_mm'           => (float) $pdfFooterReserveMmView,
    'footer_enabled'              => $pdfFooterEnabledView,
    'order_sheet_header_enabled'  => $orderSheetHeaderEnabledView,
    'order_sheet_band_default_mm' => \App\Services\ReportPdfLayoutService::orderSheetHeaderPaginationReserveMm(),
]) ?>
<?= view('registers/partials/report_layout_plan_apply_script', [
    'report_layout_applier' => $report_layout_applier ?? null,
]) ?>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var gpbBodyClasses = <?= json_encode(array_values(array_filter(explode(' ', (string) ($gpbBodyClassView ?? ''))))) ?>;
    if (gpbBodyClasses.length) {
        document.body.classList.add('viewreport-pdf-pagination', 'report-browser-print');
        gpbBodyClasses.forEach(function(cls) {
            document.body.classList.add(cls);
        });
    }
    if (typeof window.applyReportLayoutPlan === 'function') {
        window.applyReportLayoutPlan();
    } else if (window.reportPrintPagination && typeof window.reportPrintPagination.applyPaginationLineTotals === 'function') {
        window.reportPrintPagination.applyPaginationLineTotals();
    }
    window.dispatchEvent(new Event('page-scroll-nav-refresh'));

    function bindPrintWindow(selector, windowName) {
        document.querySelectorAll(selector).forEach(function(btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var url = btn.getAttribute('href');
                if (url.indexOf('auto=1') === -1) {
                    url += (url.indexOf('?') >= 0 ? '&' : '?') + 'auto=1';
                }
                var w = window.open(url, windowName, 'width=960,height=900');
                if (!w) {
                    window.location.href = url;
                }
            });
        });
    }
    bindPrintWindow('.js-viewreport-print', 'reportPrint');
    bindPrintWindow('.js-viewreport-envelope', 'envelopePrint');

    document.querySelectorAll('.js-viewreport-save').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var datos = [];
            document.querySelectorAll('.analisis').forEach(function(el) {
                datos.push({
                    padre: el.getAttribute('padre'),
                    hijo: el.getAttribute('hijo'),
                    analisis: el.getAttribute('analisis'),
                    valor: el.getAttribute('value') || el.value,
                    unidad: el.getAttribute('unidad'),
                    minimo: el.getAttribute('min'),
                    maximo: el.getAttribute('max'),
                    registro_id: document.getElementById('registro_id').value
                });
            });
            fetch('<?= site_url('registers/saveanalisiss') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'data=' + encodeURIComponent(JSON.stringify(datos))
            })
            .then(function(r) { return r.json(); })
            .then(function() {
                window.location.href = '<?= site_url('registers') ?>';
            })
            .catch(function() { uiAlert('Error al guardar', 'Error'); });
        });
    });
});
</script>
<?= $this->endSection() ?>
