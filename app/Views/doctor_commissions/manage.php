<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fa-solid fa-dollar-sign me-2"></i>Gestión de Comisiones de Doctores</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="<?= site_url('doctor_commissions?status=0') ?>" class="btn <?= ($status == '0' || $status == '') ? 'btn-warning' : 'btn-outline-warning' ?>">
                <i class="fa-solid fa-clock"></i> Pendientes
            </a>
            <a href="<?= site_url('doctor_commissions?status=1') ?>" class="btn <?= ($status == '1') ? 'btn-success' : 'btn-outline-success' ?>">
                <i class="fa-solid fa-check"></i> Pagadas
            </a>
            <a href="<?= site_url('doctor_commissions') ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-list"></i> Todas
            </a>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lista de Comisiones</h5>
            <?php if ($status == '0' || $status == ''): ?>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkPayModal">
                <i class="fa-solid fa-money-check"></i> Pagar Seleccionadas
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($commissions)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Doctor</th>
                        <th>Especialidad</th>
                        <th>Total Comisiones</th>
                        <th>Monto Pendiente</th>
                        <th>Monto Pagado</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commissions as $comm): ?>
                    <tr>
                        <td>
                            <strong><?= esc($comm->doctor_name) ?></strong>
                            <br><small class="text-muted">ID: <?= $comm->doctor_id ?></small>
                        </td>
                        <td><?= esc($comm->speciality ?? 'N/A') ?></td>
                        <td>
                            <strong><?= number_format($comm->total_amount, 2) ?></strong>
                            <br><small class="text-muted"><?= (int) $comm->total_commissions ?> comisiones</small>
                        </td>
                        <td>
                            <span class="text-warning fw-bold">$<?= number_format($comm->total_pending, 2) ?></span>
                            <br><small class="text-muted"><?= (int) $comm->pending_count ?> pendientes</small>
                        </td>
                        <td>
                            <span class="text-success fw-bold">$<?= number_format($comm->total_paid, 2) ?></span>
                            <br><small class="text-muted"><?= (int) $comm->paid_count ?> pagadas</small>
                        </td>
                        <td>
                            <?php if ($comm->pending_count > 0): ?>
                                <span class="badge bg-warning">Con pendientes</span>
                            <?php else: ?>
                                <span class="badge bg-success">Todas pagadas</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= site_url('doctor_commissions/details/' . $comm->doctor_id) ?>" 
                               class="btn btn-sm btn-info" title="Ver detalles">
                                <i class="fa-solid fa-eye"></i> Detalles
                            </a>
                            <?php if ($comm->pending_count > 0): ?>
                            <a href="<?= site_url('doctor_commissions/pay/bulk/' . $comm->doctor_id) ?>" 
                               class="btn btn-sm btn-success" title="Pagar pendientes">
                                <i class="fa-solid fa-money-check"></i> Pagar
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">
                Mostrando <?= (($page - 1) * $perPage) + 1 ?> - 
                <?= min($page * $perPage, $total) ?> de <?= $total ?> comisiones
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= site_url('doctor_commissions?page=' . max(1, $page - 1) . '&status=' . $status) ?>">Anterior</a>
                    </li>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= site_url('doctor_commissions?page=' . $p . '&status=' . $status) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= site_url('doctor_commissions?page=' . min($totalPages, $page + 1) . '&status=' . $status) ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="text-center py-4">
            <i class="fa-solid fa-receipt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay comisiones registradas</h5>
            <p class="text-muted">No se encontraron comisiones con los filtros seleccionados.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para pago individual -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pagar Comisión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="payForm" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Notas (opcional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Ingrese notas sobre el pago..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-money-check"></i> Confirmar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para pago masivo -->
<div class="modal fade" id="bulkPayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pagar Comisiones Seleccionadas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkPayFormModal" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Notas (opcional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Ingrese notas sobre el pago masivo..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fa-solid fa-info-circle"></i> Se pagarán todas las comisiones seleccionadas.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-money-check"></i> Pagar Seleccionadas
                    </button>
                </div>
            </form>
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
    // Seleccionar/deseleccionar todos
    $('#selectAll').change(function() {
        $('.commission-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    // Pago individual
    $('.pay-commission').click(function() {
        var id = $(this).data('id');
        $('#payForm').attr('action', '<?= site_url('doctor_commissions/processPayment') ?>');
        $('#payForm').append('<input type="hidden" name="commission_id" value="' + id + '">');
        $('#payModal').modal('show');
    });
    
    // Ver notas
    $('.view-notes').click(function() {
        var notes = $(this).data('notes');
        $('#notesContent').text(notes || 'No hay notas');
        $('#notesModal').modal('show');
    });
    
    // Eliminar comisión
    $('.delete-commission').click(function() {
        var id = $(this).data('id');
        if (confirm('¿Está seguro de eliminar esta comisión?')) {
            $.post('<?= site_url('doctor_commissions/delete/') ?>' + id, {<?= csrf_token() ?>: '<?= csrf_hash() ?>'})
                .done(function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                })
                .fail(function() {
                    alert('Error al eliminar la comisión');
                });
        }
    });
    
    // Formulario de pago individual
    $('#payForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                $('#payModal').modal('hide');
                location.href = response.redirect_url;
            } else {
                alert(response.message);
            }
        }).fail(function() {
            alert('Error al procesar el pago');
        });
    });
    
    // Formulario de pago masivo
    $('#bulkPayFormModal').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var selectedIds = [];
        $('.commission-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            alert('Seleccione al menos una comisión para pagar');
            return;
        }
        
        // Agregar IDs seleccionados al formulario
        form.find('input[name="commission_ids[]"]').remove();
        selectedIds.forEach(function(id) {
            form.append('<input type="hidden" name="commission_ids[]" value="' + id + '">');
        });
        
        $.ajax({
            url: '<?= site_url('doctor_commissions/bulkPay') ?>',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                $('#bulkPayModal').modal('hide');
                location.href = response.redirect_url;
            } else {
                alert(response.message);
            }
        }).fail(function() {
            alert('Error al procesar el pago masivo');
        });
    });
});
</script>
<?= $this->endSection() ?>
