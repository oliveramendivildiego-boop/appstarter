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
$qr_data_uri = qr_base64($reportUrl, 120);
?>
<div class="viewreport-pdf-shell">
    <div class="viewreport-pdf-sheet">
        <?= view('registers/pdf/report_document', [
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
            'analisis_variant'                => 'screen_pdf',
        ]) ?>
    </div>
</div>
<?php endif; ?>

<div class="text-center mt-3">
    <button id="guardaranalisis" name="guardaranalisis" class="btn btn-primary">Guardar</button>
    <a href="<?= site_url('registers/printreport/' . (int) ($labotests_namecate ?? 0)) ?>" class="btn btn-outline-primary" id="btn_print_report">Imprimir</a>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    var printBtn = document.getElementById('btn_print_report');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var url = printBtn.getAttribute('href');
            var w = window.open(url, 'reportPrint', 'width=960,height=900');
            if (!w) {
                window.location.href = url;
            }
        });
    }
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
