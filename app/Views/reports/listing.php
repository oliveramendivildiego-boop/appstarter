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
                <p class="list-group-item small text-muted mb-0">Listado de cotizaciones archivadas con detalle y opción de reimprimir PDF.</p>
                <a href="<?= site_url('reports/registrosFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-clipboard-list me-2"></i> Registros por fecha
                </a>
                <p class="list-group-item small text-muted mb-0">Órdenes de análisis registradas en un rango de fechas (ingreso).</p>
                <a href="<?= site_url('reports/ingresosFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-money-bill-wave me-2"></i> Ingresos por fecha
                </a>
                <p class="list-group-item small text-muted mb-0">Facturación y cobros por día; incluye referencia de órdenes anuladas.</p>
                <a href="<?= site_url('reports/porDoctor') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-md me-2"></i> Registros por doctor
                </a>
                <p class="list-group-item small text-muted mb-0">Cantidad de órdenes y monto total agrupados por médico solicitante.</p>
                <a href="<?= site_url('reports/pruebasFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-vial me-2"></i> Pruebas realizadas por fecha
                </a>
                <p class="list-group-item small text-muted mb-0">Pruebas completas con resultados cargados en el período seleccionado.</p>
                <a href="<?= site_url('reports/pruebasIncompletasFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-hourglass-half me-2"></i> Pruebas incompletas por fecha
                </a>
                <p class="list-group-item small text-muted mb-0">Órdenes ingresadas sin resultados registrados aún.</p>
                <a href="<?= site_url('reports/pruebasAnuladasFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-ban me-2"></i> Pruebas anuladas por fecha
                </a>
                <p class="list-group-item small text-muted mb-0">Órdenes marcadas como anuladas dentro del rango de fechas.</p>
                <a href="<?= site_url('reports/estadisticasLaboratorio') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-pie me-2"></i> Estadísticas por período (pruebas, pacientes, población, género)
                </a>
                <p class="list-group-item small text-muted mb-0">Resumen agregado: volumen de pruebas, pacientes y distribución demográfica.</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4 mb-4 d-flex flex-column gap-3">
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
                <a href="<?= site_url('reports/catalogoPruebas') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-sitemap me-2"></i> Catálogo de pruebas (imprimir)
                </a>
                <p class="list-group-item small text-muted mb-0">Listado de todos los grupos con sus análisis; abre una página lista para imprimir.</p>
                <a href="<?= site_url('reports/catalogoPruebasPdf') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-file-pdf me-2"></i> Catálogo de pruebas (PDF)
                </a>
                <p class="list-group-item small text-muted mb-0">Descarga el mismo catálogo (padres e hijos) en PDF.</p>
                <a href="<?= site_url('reports/catalogoPruebasExcel') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-file-excel me-2"></i> Catálogo de pruebas (Excel)
                </a>
                <p class="list-group-item small text-muted mb-0">Archivo CSV con UTF-8 (compatible con Excel): grupo y análisis por fila.</p>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Reportes de pagos</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= site_url('reports/pagos') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-wallet me-2"></i> Reporte de pagos
                </a>
                <p class="list-group-item small text-muted mb-0">Todos los pagos, pendientes de pago y resumen del período.</p>
                <a href="<?= site_url('reports/pagosPendientes') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-clock me-2"></i> Pendientes de pago
                </a>
                <p class="list-group-item small text-muted mb-0">Solo órdenes con saldo por cobrar en el rango; total pendiente resumido.</p>
                <a href="<?= site_url('reports/pagosCierres') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-file-invoice me-2"></i> Cierres de pagos
                </a>
                <p class="list-group-item small text-muted mb-0">Historial de cierres registrados; imprimir o PDF desde el listado.</p>
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
                <a href="<?= site_url('reports/inventarioKardex') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-warehouse me-2"></i> Kardex de inventario por usuario
                </a>
                <p class="list-group-item small text-muted mb-0">Entradas, salidas, responsable, lote, orden de laboratorio y saldo acumulado por insumo.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
