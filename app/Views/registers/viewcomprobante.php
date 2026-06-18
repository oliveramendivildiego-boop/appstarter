<?= $this->extend('layouts/main') ?>
<?php
helper('registro');
$rid = (int) ($registro_id ?? 0);
$factura = ! empty($sin_billing_enabled);
$lblComp = $factura ? 'Descargar factura (PDF)' : 'Descargar recibo (PDF)';
$codigoOrden = registro_orden_display($register_info ?? null);
$nombrePaciente = trim(implode(' ', array_filter([
    $register_info->first_name ?? '',
    $register_info->last_name_fa ?? '',
    $register_info->last_name_mom ?? '',
])));
?>
<?= $this->section('title') ?>Comprobante de pago<?= $this->endSection() ?>

<?= $this->section('content') ?>
<style>
.viewcomprobante-shell {
    background: #e9ecef;
    border-radius: 8px;
    padding: 16px;
    overflow-x: auto;
}
.viewcomprobante-frame {
    display: block;
    width: 210mm;
    max-width: 100%;
    min-height: 297mm;
    margin: 0 auto;
    border: 0;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.14);
}
@media print {
    .d-print-none { display: none !important; }
    .viewcomprobante-shell {
        background: transparent;
        padding: 0;
    }
    .viewcomprobante-frame {
        width: 100%;
        min-height: auto;
        box-shadow: none;
    }
}
</style>

<div class="d-print-none">
    <?= view('partial/breadcrumb_nav', ['items' => [
        ['label' => lang('Module.module_registers'), 'url' => site_url('registers/lista')],
        ['label' => 'Comprobante — Orden ' . $codigoOrden, 'url' => null],
    ]]) ?>
</div>

<?php if (! empty($recien_creado)): ?>
<div class="alert alert-success d-print-none">
    <i class="fa-solid fa-circle-check me-2"></i>
    Orden registrada correctamente. Puede imprimir o descargar el comprobante de pago.
</div>
<?php endif; ?>

<div class="d-print-none mb-3 d-flex flex-wrap gap-2 align-items-center">
    <a href="<?= site_url('registers') ?>" class="btn btn-outline-success">
        <i class="fa-solid fa-plus me-1"></i> Nuevo registro
    </a>
    <a href="<?= site_url('registers/lista') ?>" class="btn btn-outline-secondary">
        <i class="fa-solid fa-list me-1"></i> Lista de registros
    </a>
    <button type="button" class="btn btn-primary" id="btn_print_comprobante">
        <i class="fa-solid fa-print me-1"></i> Imprimir
    </button>
    <a href="<?= site_url('registers/comprobantePdf/' . $rid) ?>" class="btn btn-success" target="_blank" rel="noopener">
        <i class="fa-solid fa-file-pdf me-1"></i> <?= esc($lblComp) ?>
    </a>
    <a href="<?= site_url('registers/orden/' . $rid) ?>" class="btn btn-outline-primary">
        <i class="fa-solid fa-clipboard-list me-1"></i> Hoja de trabajo
    </a>
    <a href="<?= site_url('registers/view/' . $rid) ?>" class="btn btn-outline-secondary">
        <i class="fa-solid fa-flask me-1"></i> Cargar resultados
    </a>
</div>

<?php if (empty($pago_completo)): ?>
<p class="small text-muted d-print-none mb-3">
    <i class="fa-solid fa-circle-info me-1"></i>
    El comprobante refleja el saldo pendiente si la orden aún no está saldada por completo.
</p>
<?php endif; ?>

<div class="viewcomprobante-shell">
    <iframe
        id="comprobante_preview_frame"
        class="viewcomprobante-frame"
        title="Vista previa del comprobante"
        src="<?= esc(site_url('registers/printcomprobante/' . $rid), 'attr') ?>"
    ></iframe>
</div>

<script>
(function () {
    var frame = document.getElementById('comprobante_preview_frame');
    var btnPrint = document.getElementById('btn_print_comprobante');
    if (!frame || !btnPrint) return;

    function printComprobante() {
        try {
            if (frame.contentWindow) {
                frame.contentWindow.focus();
                frame.contentWindow.print();
                return;
            }
        } catch (e) { /* ignore */ }
        window.open('<?= esc(site_url('registers/printcomprobante/' . $rid), 'js') ?>', '_blank', 'noopener');
    }

    btnPrint.addEventListener('click', printComprobante);

    var params = new URLSearchParams(window.location.search);
    if (params.get('auto') === '1') {
        frame.addEventListener('load', function () {
            setTimeout(printComprobante, 200);
        }, { once: true });
    }
})();
</script>
<?= $this->endSection() ?>
