<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Orden de trabajo<?= $this->endSection() ?>
<?= $this->section('content') ?>
<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #print-area,
    #print-area * {
        visibility: visible !important;
    }
    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    /* Mantener datos de cabecera en 2 columnas al imprimir */
    #print-area .print-meta-row {
        display: flex !important;
        flex-wrap: nowrap !important;
    }
    #print-area .print-meta-col {
        width: 50% !important;
        max-width: 50% !important;
        flex: 0 0 50% !important;
    }
}
</style>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_registers'), 'url' => site_url('registers/lista')],
    ['label' => 'Orden #' . (int)($labotests_namecate ?? 0), 'url' => null],
]]) ?>

<div class="d-print-none mb-3">
    <a href="<?= site_url('registers/lista') ?>" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver
    </a>
    <button type="button" class="btn btn-primary" onclick="window.print()">
        <i class="fa-solid fa-print me-1"></i> Imprimir
    </button>
    <a href="<?= site_url('registers/ordenPdf/' . (int)($labotests_namecate ?? 0)) ?>" class="btn btn-success" target="_blank">
        <i class="fa-solid fa-file-pdf me-1"></i> Descargar PDF
    </a>
    <a href="<?= site_url('registers/edit/' . (int)($labotests_namecate ?? 0)) ?>" class="btn btn-outline-success">
        <i class="fa-solid fa-flask me-1"></i> Editar orden
    </a>
</div>

<div class="card" id="print-area">
    <div class="card-body">
        <div class="row mb-2 print-meta-row">
            <div class="col-md-6 print-meta-col">
                <div><strong>Orden:</strong> #<?= esc($register_info->registro_id ?? '') ?></div>
                <div><strong>Fecha:</strong> <?= esc($fecha ?? '') ?></div>
            </div>
            <div class="col-md-6 print-meta-col">
                <div><strong>Paciente:</strong> <?= esc(($register_info->first_name ?? '') . ' ' . ($register_info->last_name_fa ?? '') . ' ' . ($register_info->last_name_mom ?? '')) ?></div>
                <div><strong>Doctor:</strong> <?= esc($register_info->doctor_name ?? $register_info->doctor ?? '') ?></div>
            </div>
        </div>

        <hr class="my-3" />

        <?php if (empty($grupos_pruebas ?? [])): ?>
            <div class="alert alert-warning mb-0">
                No hay pruebas para esta orden.
            </div>
        <?php else: ?>
            <?php foreach (($grupos_pruebas ?? []) as $padre => $items): ?>
                <div class="mb-3">
                    <div class="fw-bold text-uppercase border-bottom pb-1 mb-2"><?= esc($padre) ?></div>
                    <ul class="mb-0">
                        <?php foreach (($items ?? []) as $it): ?>
                            <li>
                                <?= esc($it['hijo'] ?? '') ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (($show_order_barcode ?? true)): ?>
            <hr class="my-3" />
            <div class="text-center mt-2">
                <svg id="orden-barcode"></svg>
                <div class="small text-muted mt-1">Orden #<?= (int)($register_info->registro_id ?? 0) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (($show_order_barcode ?? true)): ?>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var orderId = '<?= (int)($register_info->registro_id ?? 0) ?>';
        var svg = document.getElementById('orden-barcode');
        if (!svg || !orderId) return;
        if (typeof JsBarcode === 'undefined') {
            svg.outerHTML = '<div class="text-muted small">Orden #' + orderId + '</div>';
            return;
        }
        JsBarcode(svg, orderId, {
            format: 'CODE128',
            displayValue: true,
            fontSize: 14,
            height: 55,
            margin: 6
        });
    });
    </script>
<?php endif; ?>

<?= $this->endSection() ?>
