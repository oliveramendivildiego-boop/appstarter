<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Reporte<?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<?= view('registers/partials/report_pdf_theme_styles', [
    'pdf_layout'                     => $pdf_layout ?? [],
    'use_sheet_padding_for_margins' => true,
]) ?>
<style>
.viewreport-pdf-shell { width: 100%; overflow-x: auto; }
.viewreport-pdf-shell .pdf-watermark-layer { z-index: 0; }
.viewreport-pdf-shell .pdf-main-stack { position: relative; z-index: 1; }
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
body.js-total-pages-ready .pdf-counter-pages::before {
    content: '' !important;
}
</style>
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
if (empty($grupos)): ?>
<div class="alert alert-info mt-3">
    <i class="fa-solid fa-info-circle me-2"></i>No hay resultados cargados para esta orden. Complete los resultados en <a href="<?= site_url('registers/view/' . (int)($labotests_namecate ?? 0)) ?>">Editar registro</a>.
</div>
<?php else:
helper(['qr', 'registro']);
$rid = (int) ($labotests_namecate ?? 0);
$reportUrl = ! empty($public_resultados_token)
    ? site_url('resultados/' . $public_resultados_token)
    : site_url('registers/viewreport/' . $rid);
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
        ]);
        echo \App\Services\RegisterService::replaceTotalPagesTokenForBrowser(ob_get_clean());
        ?>
    </div>
</div>
<?php endif; ?>

<div class="text-center mt-3">
    <button id="guardaranalisis" name="guardaranalisis" class="btn btn-primary">Guardar</button>
    <a href="<?= site_url('registers/printreport/' . (int) ($labotests_namecate ?? 0)) ?>" class="btn btn-outline-primary" id="btn_print_report">Imprimir</a>
    <?php if (! empty($envelope_print_available)): ?>
    <a href="<?= site_url('registers/printEnvelope/' . (int) ($labotests_namecate ?? 0)) ?>"
       class="btn btn-outline-secondary"
       id="btn_print_envelope"
       title="Imprimir sobre con la plantilla de impresión en Configuración → Sobres">
        <i class="fa-solid fa-envelope me-1"></i> Imprimir sobre
    </a>
    <?php endif; ?>
    <a href="<?= site_url('registers/pdf/' . ($labotests_namecate ?? 0)) ?>" class="btn btn-success" target="_blank">
        <i class="fa-solid fa-file-pdf me-1"></i> Descargar PDF
    </a>
    <?php
    $ridPdf = (int) ($labotests_namecate ?? 0);
    $compOk = !empty($comprobante_pdf_disponible ?? false);
    $lblComp  = !empty($sin_billing_enabled ?? false) ? 'Factura (PDF)' : 'Recibo (PDF)';
    ?>
    <a href="<?= site_url('registers/comprobantePdf/' . $ridPdf) ?>"
       class="btn btn-outline-dark"
       target="_blank"
       title="<?= $compOk ? 'Descargar comprobante de pago' : 'Si la orden no está saldada, se mostrará un aviso al intentar descargar' ?>">
        <i class="fa-solid fa-file-invoice-dollar me-1"></i> <?= esc($lblComp) ?>
    </a>
    <?php if (!$compOk): ?>
    <div class="small text-muted mt-2 w-100">
        <?php if (!empty($comprobante_pdf_sin_registro_pago ?? false)): ?>
            <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i>Sin registro de pago en base de datos: cree o vincule el pago desde la lista de registros.
        <?php elseif (!empty($comprobante_pdf_pendiente_pago ?? false)): ?>
            <i class="fa-solid fa-circle-info me-1"></i>La descarga del comprobante queda disponible cuando el saldo sea 0 o el monto pagado cubra el total (revise en lista de registros → historial de pagos).
        <?php else: ?>
            <i class="fa-solid fa-circle-info me-1"></i>Si no puede descargar el comprobante, verifique el pago de la orden.
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</fieldset>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php
$plView = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$mmView = is_array($plView['margins_mm'] ?? null)
    ? $plView['margins_mm']
    : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
$mtView = (float) ($mmView['top'] ?? 15);
$mbView = (float) ($mmView['bottom'] ?? 15);
$printPaperView = strtolower((string) (($lab_config ?? [])['print_paper_size'] ?? 'letter'));
if (! in_array($printPaperView, ['letter', 'a4', 'legal', 'custom'], true)) {
    $printPaperView = 'letter';
}
$printPaperCustomHView = max(50.0, min(999.0, (float) (($lab_config ?? [])['print_paper_height_mm'] ?? 297)));
$printPageHeightMmView = $printPaperView === 'a4'
    ? 297.0
    : ($printPaperView === 'legal' ? 355.6 : ($printPaperView === 'custom' ? $printPaperCustomHView : 279.4));
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var MM_TO_PX = 96 / 25.4;
    var PAGE_HEIGHT_MM = <?= json_encode($printPageHeightMmView) ?>;
    var marginTopMm = <?= json_encode($mtView) ?>;
    var marginBottomMm = <?= json_encode($mbView) ?>;

    function estimateTotalPagesForView() {
        var content = document.querySelector('.viewreport-pdf-sheet .pdf-main-stack') || document.querySelector('.pdf-main-stack');
        if (!content) {
            return 1;
        }
        var printableHeightMm = PAGE_HEIGHT_MM - marginTopMm - marginBottomMm;
        if (!isFinite(printableHeightMm) || printableHeightMm <= 0) printableHeightMm = 240;
        var printablePx = printableHeightMm * MM_TO_PX;
        if (!isFinite(printablePx) || printablePx <= 0) printablePx = 900;
        var total = Math.ceil(content.scrollHeight / printablePx);
        if (!isFinite(total) || total < 1) total = 1;
        return total;
    }

    function applyBrowserTotalPages() {
        var total = estimateTotalPagesForView();
        document.querySelectorAll('.pdf-counter-pages').forEach(function(el) {
            el.textContent = String(total);
        });
        document.body.classList.add('js-total-pages-ready');
    }

    applyBrowserTotalPages();
    window.addEventListener('resize', applyBrowserTotalPages);

    function bindPrintWindow(btnId, windowName) {
        var btn = document.getElementById(btnId);
        if (!btn) {
            return;
        }
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
    }
    bindPrintWindow('btn_print_report', 'reportPrint');
    bindPrintWindow('btn_print_envelope', 'envelopePrint');
    var btn = document.getElementById('guardaranalisis');
    if (btn) {
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
            .then(function(res) {
                window.location.href = '<?= site_url('registers') ?>';
            })
            .catch(function() { uiAlert('Error al guardar', 'Error'); });
        });
    }
});
</script>
<?= $this->endSection() ?>
