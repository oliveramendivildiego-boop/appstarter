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
    <h1 class="h2"><i class="fa-solid fa-money-check me-2"></i>Pagar Comisión</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= site_url('doctor_commissions') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver a Comisiones
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Detalles de la Comisión</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?= site_url('doctor_commissions/processPayment') ?>" id="paymentForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="commission_id" value="<?= $commission->commission_id ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">ID Comisión:</label>
                            <p class="form-control-plaintext fw-bold">#<?= $commission->commission_id ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha Creación:</label>
                            <p class="form-control-plaintext"><?= lab_dt_short($commission->created_date ?? null) ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Doctor:</label>
                            <p class="form-control-plaintext"><?= esc($doctor->name ?? 'N/A') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"># Registro:</label>
                            <p class="form-control-plaintext">
                                <a href="<?= site_url('registers/viewreport/' . $commission->registro_id) ?>" target="_blank">
                                    #<?= $commission->registro_id ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Monto Prueba:</label>
                            <p class="form-control-plaintext"><?= $currencyIsRight
                                ? (number_format($commission->total_amount, 2) . ' ' . esc($currencySym))
                                : (esc($currencySym) . ' ' . number_format($commission->total_amount, 2)) ?></p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">% Comisión:</label>
                            <p class="form-control-plaintext"><?= number_format($commission->commission_percent, 2) ?>%</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monto Comisión:</label>
                            <p class="form-control-plaintext fw-bold text-success"><?= $currencyIsRight
                                ? (number_format($commission->commission_amount, 2) . ' ' . esc($currencySym))
                                : (esc($currencySym) . ' ' . number_format($commission->commission_amount, 2)) ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas del Pago (opcional):</label>
                        <textarea name="notes" id="notes" class="form-control" rows="4" 
                                  placeholder="Ingrese cualquier nota o referencia sobre este pago..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-exclamation-triangle"></i> 
                        <strong>Confirmación:</strong> Está a punto de marcar esta comisión como pagada. 
                        Esta acción registrará la fecha de pago y no podrá deshacerse.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= site_url('doctor_commissions') ?>" class="btn btn-secondary">
                            <i class="fa-solid fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-money-check"></i> Confirmar Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Resumen</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Doctor</small>
                    <p class="mb-1 fw-bold"><?= esc($doctor->name ?? 'N/A') ?></p>
                    <?php if (isset($doctor->speciality)): ?>
                    <small class="text-muted"><?= esc($doctor->speciality) ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Monto a Pagar</small>
                    <h3 class="text-success mb-0"><?= $currencyIsRight
                        ? (number_format($commission->commission_amount, 2) . ' ' . esc($currencySym))
                        : (esc($currencySym) . ' ' . number_format($commission->commission_amount, 2)) ?></h3>
                </div>
                
                <div class="d-grid gap-2">
                    <div class="text-center">
                        <i class="fa-solid fa-money-check-wave fa-3x text-success mb-2"></i>
                        <p class="text-muted small">Comisión Pendiente de Pago</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($commission->notes): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Notas Existentes</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= esc($commission->notes) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- Modal confirmación de pago -->
<div class="modal fade" id="modalConfirmPagoComision" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Confirmar pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de confirmar el pago de esta comisión?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnConfirmarPagoComisionAceptar">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal mensaje -->
<div class="modal fade" id="modalMensajePagoComision" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMensajePagoComisionTitulo">Mensaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="modalMensajePagoComisionTexto"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnModalMensajePagoComisionOk" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('#paymentForm').submit(function(e) {
        e.preventDefault();

        var form = $(this);

        var modalConfirmPago = document.getElementById('modalConfirmPagoComision');
        var btnAceptar = document.getElementById('btnConfirmarPagoComisionAceptar');
        var modalMensaje = document.getElementById('modalMensajePagoComision');
        var modalMensajeTexto = document.getElementById('modalMensajePagoComisionTexto');
        var btnOk = document.getElementById('btnModalMensajePagoComisionOk');

        function ejecutarAjax() {
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json'
            }).done(function(response) {
                var texto = response && response.success
                    ? 'Comisión pagada correctamente'
                    : (response && response.message ? response.message : 'Error al procesar el pago');

                if (modalMensaje && modalMensajeTexto && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    modalMensajeTexto.textContent = texto;
                    var m = new bootstrap.Modal(modalMensaje);
                    m.show();
                    if (response && response.success && response.redirect_url && btnOk) {
                        btnOk.onclick = function() {
                            window.location.href = response.redirect_url;
                        };
                    }
                } else {
                    uiAlert(texto, 'Resultado');
                    if (response && response.success && response.redirect_url) window.location.href = response.redirect_url;
                }
            }).fail(function() {
                if (modalMensaje && modalMensajeTexto && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    modalMensajeTexto.textContent = 'Error al procesar el pago';
                    new bootstrap.Modal(modalMensaje).show();
                } else {
                    uiAlert('Error al procesar el pago', 'Error');
                }
            });
        }

        if (modalConfirmPago && btnAceptar && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            btnAceptar.onclick = function() {
                var m = bootstrap.Modal.getInstance(modalConfirmPago) || new bootstrap.Modal(modalConfirmPago);
                m.hide();
                ejecutarAjax();
            };
            new bootstrap.Modal(modalConfirmPago).show();
        } else {
            uiConfirm('¿Está seguro de confirmar el pago de esta comisión?', 'Confirmar pago')
                .then(function(ok) { if (ok) ejecutarAjax(); });
        }
    });
});
</script>
<?= $this->endSection() ?>
