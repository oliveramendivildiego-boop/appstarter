<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mb-4">
    <h3 class="mb-1"><?= lang('Module.module_reports') ?></h3>
    <p class="text-muted mb-0">Seleccione el tipo de reporte que desea generar. Los reportes operativos conservan su comportamiento habitual; los <strong>reportes analíticos avanzados</strong> son módulos independientes de solo lectura.</p>
</div>

<?php
$analyticsSeen = $analytics_seen ?? [];
/** @var callable(string): string $badgeNuevo */
$badgeNuevo = static fn (string $reportKey): string => in_array($reportKey, $analyticsSeen, true)
    ? ''
    : '<span class="badge bg-success ms-1">Nuevo</span>';
?>
<div class="row">
    <!-- Columna 1: Operaciones diarias -->
    <div class="col-md-6 col-lg-4 mb-4 d-flex flex-column gap-3">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-flask me-2"></i>Operaciones de laboratorio</h5>
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
                <a href="<?= site_url('reports/pruebasPorGrupoAnalisis') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-layer-group me-2"></i> Pruebas por grupo de análisis (padre)
                </a>
                <p class="list-group-item small text-muted mb-0">Por fechas y grupo clínico (p. ej. Hematología): total y detalle de cada análisis solicitado.</p>
                <a href="<?= site_url('reports/pruebasIncompletasFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-hourglass-half me-2"></i> Pruebas incompletas por fecha
                </a>
                <p class="list-group-item small text-muted mb-0">Órdenes ingresadas sin resultados registrados aún.</p>
                <a href="<?= site_url('reports/pruebasAnuladasFecha') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-ban me-2"></i> Pruebas anuladas por fecha
                </a>
                <p class="list-group-item small text-muted mb-0">Órdenes marcadas como anuladas dentro del rango de fechas.</p>
                <a href="<?= site_url('reports/estadisticasLaboratorio') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-line me-2 text-primary"></i> Tablero SNIS — Estadísticas de laboratorio
                </a>
                <p class="list-group-item small text-muted mb-0">Indicadores de producción por período orientados al SNIS Bolivia: KPIs, gráficos y exportación PDF/Excel/CSV.</p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-wallet me-2"></i>Pagos y cobranza</h5>
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
                <a href="<?= site_url('reports/ingresosPorTipoProcesamiento') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-pie me-2"></i> Ingresos por tipo de procesamiento
                </a>
                <p class="list-group-item small text-muted mb-0">Ingresos de pruebas internas vs derivadas a laboratorios externos; gráficos y exportación.</p>
                <a href="<?= site_url('reports/pagosCierres') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-file-invoice me-2"></i> Cierres de pagos
                </a>
                <p class="list-group-item small text-muted mb-0">Historial de cierres registrados; imprimir o PDF desde el listado.</p>
            </div>
        </div>
    </div>

    <!-- Columna 2: Catálogo e insumos -->
    <div class="col-md-6 col-lg-4 mb-4 d-flex flex-column gap-3">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i>Catálogo y referencias</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= site_url('reports/pruebasDetallado') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-search-plus me-2"></i> Reporte detallado de pruebas
                </a>
                <p class="list-group-item small text-muted mb-0">Código de recepción, paciente, usuario de recepción y trazabilidad; incluye órdenes nuevas aunque aún no tengan resultados.</p>
                <a href="<?= site_url('reports/costosPruebas') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-dollar-sign me-2"></i> Costos de todas las pruebas
                </a>
                <p class="list-group-item small text-muted mb-0">Precio y precio derivado de todas las pruebas del sistema.</p>
                <a href="<?= site_url('reports/editarCostosPruebas') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-edit me-2"></i> Editar costos de pruebas
                </a>
                <p class="list-group-item small text-muted mb-0">Lista editable para actualizar precios manual o masivamente.</p>
                <a href="<?= site_url('reports/valoresReferencia') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-line me-2"></i> Valores de referencia
                </a>
                <p class="list-group-item small text-muted mb-0">Valores de referencia con buscador; exportar e importar CSV con IDs (tipos de análisis simple, tabla y cultivo).</p>
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
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Insumos e inventario</h5>
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
                <a href="<?= site_url('reports/consumoInsumos') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-flask me-2"></i> Consumo de insumos por prueba <?= $badgeNuevo('consumo_insumos') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Reactivos consumidos por prueba, por día y por mes; pruebas que consumen más recursos.</p>
                <a href="<?= site_url('reports/proyeccionInsumos') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-hourglass-end me-2"></i> Proyección de agotamiento de insumos <?= $badgeNuevo('proyeccion_insumos') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Stock actual, consumo promedio, días restantes y fecha estimada de agotamiento (normal/advertencia/crítico).</p>
            </div>
        </div>
    </div>

    <!-- Columna 3: Analítica avanzada (10 reportes nuevos) -->
    <div class="col-md-6 col-lg-4 mb-4 d-flex flex-column gap-3">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Análisis clínico avanzado</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= site_url('reports/tendenciaPaciente') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-area me-2"></i> Tendencia histórica por paciente <?= $badgeNuevo('tendencia_paciente') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Evolución cronológica de resultados con gráfico histórico (glucosa, HbA1c, perfil lipídico, PSA, TSH, hemograma, etc.).</p>
                <a href="<?= site_url('reports/valoresCriticos') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-exclamation-triangle me-2"></i> Valores críticos o fuera de rango <?= $badgeNuevo('valores_criticos') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Resultados bajo/alto y críticos según referencias del catálogo, con indicadores por prueba y grupo.</p>
                <a href="<?= site_url('reports/historialPrueba') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-search me-2"></i> Historial por prueba analítica <?= $badgeNuevo('historial_prueba') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Busque una prueba (ej. triglicéridos) y liste todos los análisis realizados con paciente, resultado y referencias.</p>
            </div>
        </div>

        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Indicadores estratégicos</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= site_url('reports/pruebasMasSolicitadas') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-trophy me-2"></i> Pruebas más solicitadas <?= $badgeNuevo('pruebas_mas_solicitadas') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Ranking con porcentaje, ingreso estimado y gráficos Top 10/Top 20; filtros por grupo y médico.</p>
                <a href="<?= site_url('reports/comparativoMensual') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-calendar-alt me-2"></i> Comparativo mensual de ingresos y pacientes <?= $badgeNuevo('comparativo_mensual') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Pacientes, órdenes, pruebas y facturación por mes; variación vs mes/año anterior y crecimiento acumulado.</p>
            </div>
        </div>

        <div class="card shadow-sm border-secondary">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-user-clock me-2"></i>Productividad y calidad</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= site_url('reports/tiempoEntrega') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-stopwatch me-2"></i> Tiempo de entrega (TAT) <?= $badgeNuevo('tiempo_entrega') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Horas entre recepción, primer resultado y validación; cumplimiento de SLA y retrasos.</p>
                <a href="<?= site_url('reports/productividadUsuarios') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-users-cog me-2"></i> Productividad por usuario <?= $badgeNuevo('productividad_usuarios') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Recepciones, resultados cargados, modificaciones, validaciones e impresiones por usuario, con ranking.</p>
                <a href="<?= site_url('reports/notificacionesEntrega') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-bell me-2"></i> Notificaciones de entrega <?= $badgeNuevo('notificaciones_entrega') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Entregas confirmadas con «Notificar»: volumen por usuario y recepción, tiempos desde validación e indicadores de calidad.</p>
                <a href="<?= site_url('reports/resultadosCorregidos') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-history me-2"></i> Resultados corregidos (auditoría) <?= $badgeNuevo('resultados_corregidos') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Historial de correcciones con comparación antes/después, usuario y fecha (bitácora de auditoría).</p>
                <a href="<?= site_url('reports/pendientesValidacion') ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-clipboard-check me-2"></i> Resultados pendientes de validar <?= $badgeNuevo('pendientes_validacion') ?>
                </a>
                <p class="list-group-item small text-muted mb-0">Órdenes con resultados sin validación técnica/médica, tiempo pendiente e indicadores por usuario y área.</p>
            </div>
        </div>

        <div class="card shadow-sm border-success bg-light">
            <div class="card-body small text-muted py-3">
                <i class="fas fa-info-circle me-1 text-success"></i>
                Los 10 reportes analíticos avanzados son de <strong>solo lectura</strong>, con exportación PDF/Excel, impresión, buscador, ordenamiento y paginación.
                Documentación técnica en <code>docs/reports-analytics/</code>.
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
