<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Suscripción y comprobantes<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => 'Inicio', 'url' => site_url('home')],
    ['label' => 'Suscripción y comprobantes', 'url' => site_url('tenant-subscription')],
]]) ?>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Suscripción y comprobantes de pago</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small">Aquí puede consultar la vigencia registrada por administración y descargar los comprobantes en PDF. No puede modificar estos datos desde su laboratorio.</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Período desde</th>
                        <th>Período hasta</th>
                        <th>Monto</th>
                        <th>Notas</th>
                        <th>Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($payments ?? []) as $p): ?>
                    <tr>
                        <td><?= esc($p['period_start'] ?? '') ?></td>
                        <td><?= esc($p['period_end'] ?? '') ?></td>
                        <td><?= esc(number_format((float) ($p['amount'] ?? 0), 2, ',', '.')) ?> <?= esc($p['currency'] ?? '') ?></td>
                        <td><?= esc($p['notes'] ?? '') ?></td>
                        <td>
                            <?php if (! empty($p['voucher_filename'])): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('tenant-subscription/voucher/' . (int) ($p['id'] ?? 0)) ?>"><i class="fa-solid fa-download me-1"></i> PDF</a>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($payments)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No hay comprobantes registrados todavía.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
