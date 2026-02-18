<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'reports']) ?>

<div class="mb-4">
    <h3 class="mb-1"><?= lang('Module.module_reports') ?></h3>
    <p class="text-muted">Seleccione el tipo de reporte que desea generar.</p>
</div>

<div class="row">
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Reportes de laboratorio</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= site_url('reports/registrosFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-clipboard-list me-2"></i> Registros por fecha
                </a>
                <a href="<?= site_url('reports/ingresosFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-money-bill-wave me-2"></i> Ingresos por fecha
                </a>
                <a href="<?= site_url('reports/porDoctor') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-md me-2"></i> Registros por doctor
                </a>
            </div>
        </div>
    </div>
</div>

<?= view('partial/footer') ?>
