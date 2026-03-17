<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
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
                <a href="<?= site_url('reports/cotizacionesGuardadas') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Todas las cotizaciones guardadas
                </a>
                <a href="<?= site_url('reports/registrosFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-clipboard-list me-2"></i> Registros por fecha
                </a>
                <a href="<?= site_url('reports/ingresosFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-money-bill-wave me-2"></i> Ingresos por fecha
                </a>
                <a href="<?= site_url('reports/porDoctor') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-md me-2"></i> Registros por doctor
                </a>
                <a href="<?= site_url('reports/pruebasFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-vial me-2"></i> Pruebas realizadas por fecha
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Reportes de pruebas</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= site_url('reports/costosPruebas') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-dollar-sign me-2"></i> Costos de todas las pruebas
                </a>
                <p class="list-group-item small text-muted mb-0">Precio y precio derivado de todas las pruebas del sistema.</p>
                <a href="<?= site_url('reports/valoresReferencia') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-line me-2"></i> Valores de referencia
                </a>
                <p class="list-group-item small text-muted mb-0">Valores de referencia de todas las pruebas con buscador.</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Reportes de insumos</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= site_url('reports/insumosVencimiento') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-box-open me-2"></i> Insumos por ingreso y vencimiento
                </a>
                <p class="list-group-item small text-muted mb-0">Fechas de ingreso, vencimiento y alertas según configuración.</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Reportes de pagos</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= site_url('reports/pagos') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-wallet me-2"></i> Reporte de pagos
                </a>
                <p class="list-group-item small text-muted mb-0">Todos los pagos, pendientes de pago y resumen del período.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
