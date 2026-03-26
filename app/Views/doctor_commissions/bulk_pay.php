<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
helper('layout');
$layoutCfg = layout_config();
$currencySym = $layoutCfg['currency_symbol'] ?? '$';
$currencySide = isset($layoutCfg['currency_side']) ? (string)$layoutCfg['currency_side'] : 'left';
$currencyIsRight = strtolower(trim($currencySide)) === 'right';
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fa-solid fa-money-check-wave me-2"></i>Pago Masivo de Comisiones - <?= esc($doctor->name) ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= site_url('doctor_commissions/details/' . $doctor->doctor_id) ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver a Detalles
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Comisiones Pendientes</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?= site_url('doctor_commissions/bulkPay') ?>" id="bulkPayForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="doctor_id" value="<?= $doctor->doctor_id ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll" class="form-check-input" checked>
                                    </th>
                                    <th># Registro</th>
                                    <th>Paciente</th>
                                    <th>Monto</th>
                                    <th>Comisión</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalAmount = 0;
                                foreach ($commissions as $comm): 
                                    $totalAmount += $comm->commission_amount;
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="commission_ids[]" value="<?= $comm->commission_id ?>" 
                                               class="form-check-input commission-checkbox" checked>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('registers/viewreport/' . $comm->registro_id) ?>" target="_blank">
                                            #<?= $comm->registro_id ?>
                                        </a>
                                    </td>
                                    <td><?= esc($comm->paciente_nombre ?? 'N/A') ?></td>
                                    <td>
                                        <?php if ($currencyIsRight): ?>
                                            <strong><?= number_format($comm->total_amount, 2) ?></strong> <?= esc($currencySym) ?>
                                        <?php else: ?>
                                            <?= esc($currencySym) ?> <strong><?= number_format($comm->total_amount, 2) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($currencyIsRight): ?>
                                            <strong><?= number_format($comm->commission_amount, 2) ?></strong> <?= esc($currencySym) ?>
                                        <?php else: ?>
                                            <?= esc($currencySym) ?> <strong><?= number_format($comm->commission_amount, 2) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($comm->created_date)) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-warning">
                                    <th colspan="4">Total a Pagar:</th>
                                    <th colspan="2" class="text-success fw-bold">
                                        <?php if ($currencyIsRight): ?>
                                            <span id="totalAmount"><?= number_format($totalAmount, 2) ?></span> <?= esc($currencySym) ?>
                                        <?php else: ?>
                                            <?= esc($currencySym) ?> <span id="totalAmount"><?= number_format($totalAmount, 2) ?></span>
                                        <?php endif; ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas del Pago (opcional):</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" 
                                  placeholder="Ingrese notas sobre este pago masivo..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-exclamation-triangle"></i> 
                        <strong>Confirmación:</strong> Está a punto de pagar las comisiones seleccionadas. 
                        Esta acción registrará la fecha de pago y no podrá deshacerse.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= site_url('doctor_commissions/details/' . $doctor->doctor_id) ?>" class="btn btn-secondary">
                            <i class="fa-solid fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-money-check"></i> Pagar Comisiones Seleccionadas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Resumen del Doctor</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Doctor</small>
                    <p class="mb-1 fw-bold"><?= esc($doctor->name) ?></p>
                    <?php if (isset($doctor->speciality)): ?>
                    <small class="text-muted"><?= esc($doctor->speciality) ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Comisiones Pendientes</small>
                    <h4 class="text-warning mb-0"><?= count($commissions) ?></h4>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Monto Total a Pagar</small>
                    <h3 class="text-success mb-0">
                        <?php if ($currencyIsRight): ?>
                            <span id="totalAmountSidebar"><?= number_format($totalAmount, 2) ?></span> <?= esc($currencySym) ?>
                        <?php else: ?>
                            <?= esc($currencySym) ?> <span id="totalAmountSidebar"><?= number_format($totalAmount, 2) ?></span>
                        <?php endif; ?>
                    </h3>
                </div>
                
                <div class="d-grid gap-2">
                    <div class="text-center">
                        <i class="fa-solid fa-money-check-wave fa-3x text-success mb-2"></i>
                        <p class="text-muted small">Pago Masivo de Comisiones</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Seleccionar/deseleccionar todos
    $('#selectAll').change(function() {
        $('.commission-checkbox').prop('checked', $(this).is(':checked'));
        updateTotal();
    });
    
    // Actualizar total cuando se cambia un checkbox
    $('.commission-checkbox').change(function() {
        updateTotal();
    });
    
    function updateTotal() {
        let total = 0;
        $('.commission-checkbox:checked').each(function() {
            let row = $(this).closest('tr');
            // El strong contiene solo el número (sin símbolo), pero puede traer separador de miles.
            let amountText = row.find('td:eq(4) strong').text();
            let amount = parseFloat(amountText.replace(/,/g, ''));
            total += amount;
        });
        $('#totalAmount, #totalAmountSidebar').text(total.toFixed(2));
    }
    
    // Enviar formulario
    $('#bulkPayForm').submit(function(e) {
        e.preventDefault();
        
        var selectedIds = [];
        $('.commission-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            uiAlert('Seleccione al menos una comisión para pagar', 'Validación');
            return false;
        }
        
        uiConfirm('¿Está seguro de pagar las ' + selectedIds.length + ' comisiones seleccionadas?', 'Confirmar pago')
            .then(function(ok) {
                if (!ok) return;
                var form = $('#bulkPayForm');
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json'
                }).done(function(response) {
                    if (response.success) {
                        uiAlert(response.message, 'OK');
                        window.location.href = response.redirect_url;
                    } else {
                        uiAlert(response.message, 'Error');
                    }
                }).fail(function() {
                    uiAlert('Error al procesar el pago masivo', 'Error');
                });
            });

        return false;
    });
});
</script>
<?= $this->endSection() ?>
