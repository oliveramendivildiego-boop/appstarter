<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Orden de trabajo<?= $this->endSection() ?>
<?php
$nombrePacienteOrden = trim(implode(' ', array_filter([
    $register_info->first_name ?? '',
    $register_info->last_name_fa ?? '',
    $register_info->last_name_mom ?? '',
])));
?>
<?= $this->section('content') ?>
<style>
@media print {
    /*
     * No usar visibility:hidden en body *: en Chrome/Edge los SVG del código de barras
     * dejan de pintarse al imprimir. Se oculta el chrome vía dom_print.css + d-print-none.
     */
    body:not(.print-barcode-labels) #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    body:not(.print-barcode-labels) #print-area .print-meta-row {
        display: flex !important;
        flex-wrap: nowrap !important;
    }
    body:not(.print-barcode-labels) #print-area .print-meta-col {
        width: 50% !important;
        max-width: 50% !important;
        flex: 0 0 50% !important;
    }

    body:not(.print-barcode-labels) #barcode-labels-root {
        display: none !important;
    }

    body:not(.print-barcode-labels) #print-area svg,
    body.print-barcode-labels #barcode-labels-root svg {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Solo códigos de barras (N copias): ocultar la orden completa */
    body.print-barcode-labels #print-area {
        display: none !important;
    }
    body.print-barcode-labels #barcode-labels-root {
        display: block !important;
        position: static !important;
        left: auto !important;
        top: auto !important;
        width: 100% !important;
        height: auto !important;
        overflow: visible !important;
        pointer-events: none;
    }
    body.print-barcode-labels .barcode-label-item {
        page-break-inside: avoid;
        break-inside: avoid;
    }
}

#barcode-labels-root {
    position: absolute;
    left: -9999px;
    top: 0;
    width: 1px;
    height: 1px;
    overflow: hidden;
    pointer-events: none;
}
</style>

<div class="d-print-none">
    <?= view('partial/breadcrumb_nav', ['items' => [
        ['label' => lang('Module.module_registers'), 'url' => site_url('registers/lista')],
        ['label' => 'Orden ' . registro_orden_display($register_info), 'url' => null],
    ]]) ?>
</div>

<div class="d-print-none mb-3">
    <a href="<?= site_url('registers/lista') ?>" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver
    </a>
    <button type="button" class="btn btn-primary" onclick="window.print()">
        <i class="fa-solid fa-print me-1"></i> Imprimir
    </button>
    <?php if (($show_order_barcode ?? true)): ?>
        <span class="ms-2 d-inline-flex align-items-center flex-wrap gap-2">
            <label for="barcode-copies-input" class="small text-muted mb-0">Copias (solo código de barras)</label>
            <input type="number" id="barcode-copies-input" class="form-control form-control-sm d-inline-block" style="width: 4.5rem;" min="1" max="99" value="1" />
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-print-barcode-labels" title="Imprime únicamente el código de barras, tantas veces como indique el número">
                <i class="fa-solid fa-barcode me-1"></i> Imprimir solo código de barras
            </button>
        </span>
    <?php endif; ?>
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
                <div><strong>Paciente:</strong> <?= esc($nombrePacienteOrden) ?></div>
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
                <?php if ($nombrePacienteOrden !== ''): ?>
                    <div class="fw-semibold small mb-0"><?= esc($nombrePacienteOrden) ?></div>
                <?php endif; ?>
                <svg id="orden-barcode"></svg>
                <div class="small text-muted mt-1">Orden <?= esc(registro_orden_display($register_info)) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (($show_order_barcode ?? true)): ?>
    <div id="barcode-labels-root" aria-hidden="true"></div>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var orderId = <?= json_encode(registro_orden_display($register_info), JSON_UNESCAPED_UNICODE) ?>;
        var patientName = <?= json_encode($nombrePacienteOrden, JSON_UNESCAPED_UNICODE) ?>;
        var barcodeOpts = {
            format: 'CODE128',
            displayValue: true,
            fontSize: 14,
            height: 55,
            margin: 6
        };
        var svg = document.getElementById('orden-barcode');
        if (!svg || !orderId) return;
        if (typeof JsBarcode === 'undefined') {
            svg.outerHTML = '<div class="text-muted small">Orden #' + orderId + '</div>';
            return;
        }
        JsBarcode(svg, orderId, barcodeOpts);

        var labelsRoot = document.getElementById('barcode-labels-root');
        var copiesInput = document.getElementById('barcode-copies-input');
        var btnLabels = document.getElementById('btn-print-barcode-labels');
        if (labelsRoot && copiesInput && btnLabels) {
            btnLabels.addEventListener('click', function() {
                var n = parseInt(copiesInput.value, 10);
                if (isNaN(n) || n < 1) n = 1;
                if (n > 99) n = 99;
                copiesInput.value = String(n);
                labelsRoot.innerHTML = '';
                for (var i = 0; i < n; i++) {
                    var wrap = document.createElement('div');
                    wrap.className = 'text-center barcode-label-item mb-3';
                    if (patientName) {
                        var nameEl = document.createElement('div');
                        nameEl.className = 'fw-semibold small mb-0';
                        nameEl.textContent = patientName;
                        wrap.appendChild(nameEl);
                    }
                    var el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    wrap.appendChild(el);
                    labelsRoot.appendChild(wrap);
                    JsBarcode(el, orderId, barcodeOpts);
                }
                document.body.classList.add('print-barcode-labels');
                window.print();
            });
            window.addEventListener('afterprint', function() {
                document.body.classList.remove('print-barcode-labels');
                if (labelsRoot) labelsRoot.innerHTML = '';
            });
        }
    });
    </script>
<?php endif; ?>

<?= $this->endSection() ?>

