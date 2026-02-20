<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'expediente']) ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_home'), 'url' => site_url('home')],
    ['label' => 'Expediente', 'url' => site_url('expediente')],
    ['label' => $pacienteNombre ?? '', 'url' => null],
]]) ?>

<div class="card shadow-sm mb-4">
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
                <p class="mb-1"><strong>Dirección:</strong> <?= esc($paciente->address_1 ?? '-') ?></p>
                <p class="mb-0"><strong>Total de estudios:</strong> <?= count($registros) ?></p>
            </div>
        </div>
        <div class="mt-3">
            <a href="<?= site_url('expediente') ?>" class="btn btn-secondary"><i class="fa-solid fa-search me-1"></i> Buscar otro paciente</a>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-book-medical me-2"></i>Historial de estudios</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($registros)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fa-solid fa-clipboard-list fa-3x mb-3"></i>
                <p>No hay registros de análisis para este paciente.</p>
                <a href="<?= site_url('registers') ?>" class="btn btn-primary">Crear nuevo registro</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Orden</th>
                            <th>Fecha</th>
                            <th>Médico</th>
                            <th>Total</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                            <tr>
                                <td><?= esc($r->registro_id ?? '') ?></td>
                                <td><?= esc($r->ingreso ? date('d/m/Y H:i', strtotime($r->ingreso)) : '-') ?></td>
                                <td><?= esc($r->doctor ?? '-') ?></td>
                                <td><?= esc($r->total ?? '-') ?></td>
                                <td class="text-center">
                                    <a href="<?= site_url('registers/viewreport/' . ($r->registro_id ?? '')) ?>" class="btn btn-sm btn-primary" target="_blank" title="Ver reporte">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </a>
                                    <a href="<?= site_url('registers/pdf/' . ($r->registro_id ?? '')) ?>" class="btn btn-sm btn-success" target="_blank" title="Descargar PDF">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                    <a href="<?= site_url('registers/view/' . ($r->registro_id ?? '')) ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
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

<?php if (!empty($antecedentes)): ?>
<div class="card shadow-sm mt-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-history me-2"></i>Antecedentes (estudios previos)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small">Registros anteriores del mismo paciente para comparar resultados.</p>
        <ul class="list-group list-group-flush">
            <?php foreach ($antecedentes as $ant): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        Orden #<?= esc($ant->registro_id) ?> - <?= esc(date('d/m/Y', strtotime($ant->ingreso ?? 'now'))) ?>
                    </span>
                    <span>
                        <a href="<?= site_url('registers/viewreport/' . ($ant->registro_id ?? '')) ?>" class="btn btn-sm btn-outline-primary" target="_blank">Ver</a>
                        <a href="<?= site_url('registers/pdf/' . ($ant->registro_id ?? '')) ?>" class="btn btn-sm btn-outline-success" target="_blank">PDF</a>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?= view('partial/footer') ?>
