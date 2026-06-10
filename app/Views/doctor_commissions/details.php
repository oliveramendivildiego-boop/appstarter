<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
helper('layout');
$layoutCfg = layout_config();
$currencySym = $layoutCfg['currency_symbol'] ?? '$';
$currencySide = isset($layoutCfg['currency_side']) ? (string)$layoutCfg['currency_side'] : 'left';
$currencyIsRight = strtolower(trim($currencySide)) === 'right';
?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_doctor_commissions'), 'url' => site_url('doctor_commissions')],
    ['label' => $doctor->name, 'url' => null],
]]) ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fa-solid fa-user-doctor me-2"></i>Comisiones de <?= esc($doctor->name) ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= site_url('doctor_commissions') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver a Comisiones
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detalles de Comisiones</h5>
                    <div class="btn-group">
                        <a href="<?= site_url('doctor_commissions/details/' . $doctor->doctor_id . '?status=0') ?>" 
                           class="btn <?= ($status == '0' || $status == '') ? 'btn-warning' : 'btn-outline-warning' ?>">
                            <i class="fa-solid fa-clock"></i> Pendientes
                        </a>
                        <a href="<?= site_url('doctor_commissions/details/' . $doctor->doctor_id . '?status=1') ?>" 
                           class="btn <?= ($status == '1') ? 'btn-success' : 'btn-outline-success' ?>">
                            <i class="fa-solid fa-check"></i> Pagadas
                        </a>
                        <a href="<?= site_url('doctor_commissions/details/' . $doctor->doctor_id) ?>" 
                           class="btn btn-outline-secondary">
                            <i class="fa-solid fa-list"></i> Todas
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($commissions)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th># Registro</th>
                                <th>Paciente</th>
                                <th>Monto Prueba</th>
                                <th>% Comisión</th>
                                <th>Monto Comisión</th>
                                <th>Fecha Creación</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commissions as $comm): ?>
                            <tr>
                                <td>
                                    <a href="<?= site_url('registers/viewreport/' . $comm->registro_id) ?>" target="_blank">
                                        #<?= $comm->registro_id ?>
                                    </a>
                                </td>
                                <td><?= esc($comm->paciente_nombre ?? 'N/A') ?></td>
                                <td><?= $currencyIsRight
                                    ? (number_format($comm->total_amount, 2) . ' ' . esc($currencySym))
                                    : (esc($currencySym) . ' ' . number_format($comm->total_amount, 2)) ?></td>
                                <td><?= number_format($comm->commission_percent, 2) ?>%</td>
                                <td><strong><?= $currencyIsRight
                                    ? (number_format($comm->commission_amount, 2) . ' ' . esc($currencySym))
                                    : (esc($currencySym) . ' ' . number_format($comm->commission_amount, 2)) ?></strong></td>
                                <td><?= lab_dt_short($comm->created_date ?? null) ?></td>
                                <td>
                                    <?php if ($comm->status == 0): ?>
                                        <span class="badge bg-warning">Pendiente</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Pagada</span>
                                        <?php if ($comm->paid_date): ?>
                                            <br><small class="text-muted"><?= lab_date($comm->paid_date ?? null) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($comm->status == 0): ?>
                                        <a href="<?= site_url('doctor_commissions/pay/' . $comm->commission_id) ?>" 
                                           class="btn btn-sm btn-success" title="Pagar Comisión">
                                            <i class="fa-solid fa-money-check"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-commission" 
                                                data-id="<?= $comm->commission_id ?>" title="Eliminar">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-info view-notes" 
                                                data-notes="<?= esc($comm->notes ?? '') ?>" title="Ver Notas">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fa-solid fa-receipt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay comisiones registradas</h5>
                    <p class="text-muted">Este doctor no tiene comisiones con los filtros seleccionados.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver notas -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notas del Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="notesContent"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Ver notas
    $('.view-notes').click(function() {
        var notes = $(this).data('notes');
        $('#notesContent').text(notes || 'No hay notas');
        $('#notesModal').modal('show');
    });
    
    // Eliminar comisión
    $('.delete-commission').click(function() {
        var id = $(this).data('id');
        uiConfirm('¿Está seguro de eliminar esta comisión?', 'Confirmar eliminación')
            .then(function(ok) {
                if (!ok) return;
                $.post('<?= site_url('doctor_commissions/delete/') ?>' + id, {<?= csrf_token() ?>: '<?= csrf_hash() ?>'})
                    .done(function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            uiAlert(response.message, 'Error');
                        }
                    })
                    .fail(function() {
                        uiAlert('Error al eliminar la comisión', 'Error');
                    });
            });
    });
});
</script>
<?= $this->endSection() ?>
