<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Hoja de trabajo<?= $this->endSection() ?>
<?php
helper('registro');
$labCfg = is_array($lab_config ?? null) ? $lab_config : [];
$labCompany = trim((string) ($labCfg['company'] ?? ''));
if ($labCompany === '') {
    $layoutCfg = layout_config();
    $labCompany = trim((string) ($layoutCfg['company'] ?? 'Laboratorio'));
}
$labAddress = trim((string) ($labCfg['address'] ?? ''));
$labPhone = trim((string) ($labCfg['phone'] ?? ''));
$codigoOrden = registro_orden_display($register_info);
$telefonoPaciente = trim((string) ($register_info->phone_number ?? ''));
$direccionPaciente = trim((string) ($register_info->address_1 ?? ''));
$nombrePacienteOrden = trim(implode(' ', array_filter([
    $register_info->last_name_fa ?? '',
    $register_info->last_name_mom ?? '',
    $register_info->first_name ?? '',
])));
$doctorOrdenDisplay = trim((string) ($register_info->doctor_name ?? $register_info->doctor ?? ''));
if ($doctorOrdenDisplay === '') {
    $doctorOrdenDisplay = isset($label_sin_doctor) ? trim((string) $label_sin_doctor) : 'Sin doctor';
    if ($doctorOrdenDisplay === '') {
        $doctorOrdenDisplay = 'Sin doctor';
    }
}
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

    body:not(.print-barcode-labels) #print-area #orden-barcode {
        width: 236px !important;
        height: 86px !important;
        max-width: 236px !important;
        max-height: 86px !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    body.print-barcode-labels #barcode-labels-root .orden-barcode-label-svg {
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

    /* Config: horizontal = 3 etiquetas por fila al imprimir solo códigos */
    body.print-barcode-labels #barcode-labels-root.barcode-layout-horizontal {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: flex-start !important;
        justify-content: flex-start !important;
    }
    body.print-barcode-labels #barcode-labels-root.barcode-layout-horizontal .barcode-label-item {
        flex: 0 0 33.333% !important;
        width: 33.333% !important;
        max-width: 33.333% !important;
        box-sizing: border-box !important;
        padding: 0.2rem 0.35rem !important;
        margin-bottom: 0.5rem !important;
    }
    body.print-barcode-labels #barcode-labels-root.barcode-layout-horizontal .barcode-label-item .orden-barcode-label-svg {
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
    }

    #print-area .orden-print-header {
        display: grid !important;
        grid-template-columns: 1fr auto 1fr;
        align-items: start;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    #print-area .orden-print-title {
        text-align: center;
        align-self: center;
    }
    #print-area .orden-print-title h2 {
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
}

.orden-print-header {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: start;
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.orden-lab-info {
    font-size: 0.9rem;
    line-height: 1.35;
}
.orden-lab-name {
    font-weight: 700;
    font-size: 1rem;
}
.orden-print-title {
    text-align: center;
    align-self: center;
}
.orden-print-title h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.orden-print-header-spacer {
    min-height: 1px;
}

.orden-barcode-box {
    display: inline-block;
    line-height: 0;
    width: 236px;
    height: 86px;
}
#orden-barcode {
    display: block;
    box-sizing: border-box;
    width: 236px !important;
    height: 86px !important;
    max-width: 236px !important;
    max-height: 86px !important;
}
.orden-barcode-label-svg {
    display: block;
    box-sizing: border-box;
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

.orden-barcode-patient-name {
    font-weight: 600;
    font-size: 1.05rem;
    line-height: 1.2;
    margin-bottom: 0.15rem;
}
@media print {
    body:not(.print-barcode-labels) #print-area .orden-barcode-patient-name {
        font-size: 1.2rem;
    }
    body.print-barcode-labels .orden-barcode-patient-name {
        font-size: 1.15rem;
    }
    body.print-barcode-labels #barcode-labels-root.barcode-layout-horizontal .orden-barcode-patient-name {
        font-size: 1rem;
    }
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
        <div class="orden-print-header">
            <div class="orden-lab-info">
                <div class="orden-lab-name"><?= esc($labCompany) ?></div>
                <?php if ($labAddress !== ''): ?>
                    <div><?= esc($labAddress) ?></div>
                <?php endif; ?>
                <?php if ($labPhone !== ''): ?>
                    <div><strong>Tel.</strong> <?= esc($labPhone) ?></div>
                <?php endif; ?>
            </div>
            <div class="orden-print-title">
                <h2>Hoja de trabajo</h2>
            </div>
            <div class="orden-print-header-spacer" aria-hidden="true"></div>
        </div>

        <div class="row mb-2 print-meta-row">
            <div class="col-md-6 print-meta-col">
                <div><strong>Orden:</strong> <?= esc($codigoOrden) ?></div>
                <div><strong>Fecha:</strong> <?= esc($fecha ?? '') ?></div>
                <div><strong>Dirección:</strong> <?= esc($direccionPaciente !== '' ? $direccionPaciente : '-') ?></div>
            </div>
            <div class="col-md-6 print-meta-col">
                <div><strong>Paciente:</strong> <?= esc($nombrePacienteOrden) ?></div>
                <div><strong>Edad:</strong> <?= esc($edad_paciente_orden ?? '-') ?></div>
                <?php if ($telefonoPaciente !== ''): ?>
                    <div><strong>Tel. paciente:</strong> <?= esc($telefonoPaciente) ?></div>
                <?php endif; ?>
                <div><strong>Doctor:</strong> <?= esc($doctorOrdenDisplay) ?></div>
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
                    <div class="orden-barcode-patient-name"><?= esc($nombrePacienteOrden) ?></div>
                <?php endif; ?>
                <div class="orden-barcode-box">
                    <svg id="orden-barcode"></svg>
                </div>
                <div class="small text-muted mt-1">Orden <?= esc($codigoOrden) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (($show_order_barcode ?? true)): ?>
    <div id="barcode-labels-root" aria-hidden="true"></div>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var orderId = <?= json_encode($codigoOrden, JSON_UNESCAPED_UNICODE) ?>;
        var patientName = <?= json_encode($nombrePacienteOrden, JSON_UNESCAPED_UNICODE) ?>;
        var printLayout = <?= json_encode(($order_barcode_print_layout ?? 'vertical') === 'horizontal' ? 'horizontal' : 'vertical', JSON_UNESCAPED_UNICODE) ?>;
        var sizePct = <?= (int) ($order_barcode_print_size_percent ?? 100) ?>;
        if (sizePct < 30) sizePct = 100;
        if (sizePct > 250) sizePct = 100;
        var sz = sizePct / 100;
        var ordenBarcodeW = 236;
        var ordenBarcodeH = 86;
        var labelBarcodeW = Math.round(236 * sz);
        var labelBarcodeH = Math.round(86 * sz);
        function applyBarcodeDimensions(svgEl, w, h, fitCell) {
            if (!svgEl || !svgEl.getBBox) return;
            var box = svgEl.getBBox();
            if (!box.width || !box.height) return;
            svgEl.setAttribute('viewBox', box.x + ' ' + box.y + ' ' + box.width + ' ' + box.height);
            svgEl.setAttribute('preserveAspectRatio', 'xMidYMid meet');
            if (fitCell) {
                svgEl.removeAttribute('width');
                svgEl.removeAttribute('height');
                svgEl.style.width = '';
                svgEl.style.height = '';
                return;
            }
            svgEl.setAttribute('width', String(w));
            svgEl.setAttribute('height', String(h));
            svgEl.style.width = w + 'px';
            svgEl.style.height = h + 'px';
        }
        function scaleBarcodeOpts(base) {
            var o = {
                format: base.format,
                displayValue: base.displayValue,
                fontSize: Math.max(8, Math.round(base.fontSize * sz)),
                height: Math.max(20, Math.round(base.height * sz)),
                margin: Math.max(0, Math.round(base.margin * sz))
            };
            if (base.width != null) {
                o.width = Math.max(0.5, Math.round(base.width * sz * 10) / 10);
            }
            return o;
        }
        var barcodeOptsMain = {
            format: 'CODE128',
            displayValue: true,
            fontSize: 14,
            height: 55,
            margin: 6
        };
        var barcodeOptsLabelsVertical = scaleBarcodeOpts({
            format: 'CODE128',
            displayValue: true,
            fontSize: 14,
            height: 55,
            margin: 6
        });
        var barcodeOptsLabelsHorizontal = scaleBarcodeOpts({
            format: 'CODE128',
            displayValue: true,
            fontSize: 10,
            height: 42,
            width: 1.2,
            margin: 2
        });
        var svg = document.getElementById('orden-barcode');
        if (!svg || !orderId) return;
        if (typeof JsBarcode === 'undefined') {
            svg.outerHTML = '<div class="text-muted small">Orden #' + orderId + '</div>';
            return;
        }
        JsBarcode(svg, orderId, barcodeOptsMain);
        applyBarcodeDimensions(svg, ordenBarcodeW, ordenBarcodeH, false);

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
                labelsRoot.classList.remove('barcode-layout-horizontal');
                if (printLayout === 'horizontal') {
                    labelsRoot.classList.add('barcode-layout-horizontal');
                }
                var optsLabels = printLayout === 'horizontal' ? barcodeOptsLabelsHorizontal : barcodeOptsLabelsVertical;
                for (var i = 0; i < n; i++) {
                    var wrap = document.createElement('div');
                    wrap.className = 'text-center barcode-label-item' + (printLayout === 'horizontal' ? '' : ' mb-3');
                    if (patientName) {
                        var nameEl = document.createElement('div');
                        nameEl.className = 'orden-barcode-patient-name';
                        nameEl.textContent = patientName;
                        wrap.appendChild(nameEl);
                    }
                    var el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    el.setAttribute('class', 'orden-barcode-label-svg');
                    wrap.appendChild(el);
                    labelsRoot.appendChild(wrap);
                    JsBarcode(el, orderId, optsLabels);
                    applyBarcodeDimensions(
                        el,
                        labelBarcodeW,
                        labelBarcodeH,
                        printLayout === 'horizontal'
                    );
                }
                document.body.classList.add('print-barcode-labels');
                window.print();
            });
            window.addEventListener('afterprint', function() {
                document.body.classList.remove('print-barcode-labels');
                if (labelsRoot) {
                    labelsRoot.innerHTML = '';
                    labelsRoot.classList.remove('barcode-layout-horizontal');
                }
            });
        }
    });
    </script>
<?php endif; ?>

<?= $this->endSection() ?>

