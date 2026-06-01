<?= $this->extend('layouts/doctor') ?>

<?= $this->section('title') ?><?= esc($pacienteNombre ?? 'Paciente') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= site_url('doctor/home') ?>">Mi panel</a></li>
        <li class="breadcrumb-item active"><?= esc($pacienteNombre ?? 'Paciente') ?></li>
    </ol>
</nav>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-user-doctor me-2"></i>Expediente del paciente</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Paciente:</strong> <?= esc($pacienteNombre) ?></p>
                <p class="mb-1"><strong>Edad:</strong> <?= esc($paciente->edad_texto ?? '-') ?></p>
                <p class="mb-1"><strong>Teléfono:</strong> <?= esc($paciente->phone_number ?? '-') ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Correo:</strong> <?= esc($paciente->email ?? '-') ?></p>
                <p class="mb-0"><strong>Total de estudios (con usted):</strong> <?= count($registros) ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="<?= site_url('doctor/home') ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Volver</a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-book-medical me-2"></i>Historial de estudios</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($registros)): ?>
            <div class="p-4 text-center text-muted">
                <p>No hay estudios con resultados para este paciente en sus registros.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Orden</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= esc($r->registro_id ?? '') ?></td>
                            <td><?= esc($r->ingreso ? lab_dt_short($r->ingreso ?? null) : '-') ?></td>
                            <td><?= esc($r->total ?? '-') ?></td>
                            <td class="text-center">
                                <a href="<?= site_url('doctor/viewreport/' . ($r->registro_id ?? '')) ?>" class="btn btn-sm btn-primary" target="_blank" title="Ver reporte">
                                    <i class="fa-solid fa-file-lines"></i>
                                </a>
                                <a href="<?= site_url('doctor/pdf/' . ($r->registro_id ?? '')) ?>" class="btn btn-sm btn-success" target="_blank" title="Descargar PDF">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
