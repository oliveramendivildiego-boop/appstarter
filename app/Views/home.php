<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'home']) ?>

<h1 class="h2 mb-4">Dashboard</h1>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                    <i class="fa-solid fa-hospital-user fa-lg text-primary"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase">Pacientes</h6>
                    <h3 class="mb-0 fw-bold"><?= (int)($dashboard_stats['customers'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 py-2">
                <a href="<?= site_url('customers') ?>" class="small text-primary text-decoration-none">Ver pacientes <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 bg-success bg-opacity-10 rounded-circle p-3 me-3">
                    <i class="fa-solid fa-user-doctor fa-lg text-success"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase">Doctores</h6>
                    <h3 class="mb-0 fw-bold"><?= (int)($dashboard_stats['doctors'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 py-2">
                <a href="<?= site_url('doctors') ?>" class="small text-success text-decoration-none">Ver doctores <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 bg-info bg-opacity-10 rounded-circle p-3 me-3">
                    <i class="fa-solid fa-book-medical fa-lg text-info"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase">Registros hoy</h6>
                    <h3 class="mb-0 fw-bold"><?= (int)($dashboard_stats['registers_today'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 py-2">
                <a href="<?= site_url('registers') ?>" class="small text-info text-decoration-none">Nuevo registro <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 bg-warning bg-opacity-10 rounded-circle p-3 me-3">
                    <i class="fa-solid fa-user-tie fa-lg text-warning"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-0 small text-uppercase">Empleados</h6>
                    <h3 class="mb-0 fw-bold"><?= (int)($dashboard_stats['employees'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0"><i class="fa-solid fa-chart-simple me-2"></i>Registros por período</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td>Hoy</td>
                            <td class="text-end fw-semibold"><?= (int)($dashboard_stats['registers_today'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Esta semana</td>
                            <td class="text-end fw-semibold"><?= (int)($dashboard_stats['registers_week'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Este mes</td>
                            <td class="text-end fw-semibold"><?= (int)($dashboard_stats['registers_month'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td>Total</td>
                            <td class="text-end fw-semibold"><?= (int)($dashboard_stats['registers_total'] ?? 0) ?></td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Registros recientes</h5>
                <a href="<?= site_url('registers') ?>" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recent_registers)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Paciente</th>
                                <th>Doctor</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_registers as $reg): ?>
                            <tr>
                                <td><?= (int)($reg->registro_id ?? 0) ?></td>
                                <td><?= esc($reg->paciente ?? '-') ?></td>
                                <td><?= esc($reg->doctor ?? '-') ?></td>
                                <td><?= !empty($reg->ingreso) ? date('d/m/Y H:i', strtotime($reg->ingreso)) : '-' ?></td>
                                <td>
                                    <a href="<?= site_url('registers/viewreport/' . ($reg->registro_id ?? 0)) ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-5 mb-0">No hay registros recientes</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= view('partial/footer') ?>
