<?= $this->extend('layouts/doctor') ?>

<?= $this->section('title') ?>Mi panel<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
helper('layout');
$layoutCfg = layout_config();
$currencySym = $layoutCfg['currency_symbol'] ?? '$';
$currencySide = isset($layoutCfg['currency_side']) ? (string)$layoutCfg['currency_side'] : 'left';
$currencyIsRight = strtolower(trim($currencySide)) === 'right';
$hideCommissionDetails = !empty($hide_commission_details);
$doctorSummary = $doctor_summary ?? [];
$clinicalFilters = $clinical_filters ?? [];
$clinicalSummaries = $clinical_summaries ?? [];
$summaryStatusClass = $doctorSummary['status_class'] ?? 'success';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <?php $tituloDoctor = ((int) ($doctor_info->gender ?? 0) === 2) ? 'Dra.' : 'Dr.'; ?>
            <h1 class="h2 mb-4 fw-bold text-center"><i class="fa-solid fa-user-doctor me-2"></i>Mi panel - <?= $tituloDoctor ?> <?= esc($doctor_info->name ?? '') ?></h1>

            <div class="row mb-4 g-3">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fa-solid fa-folder-open me-2"></i>Buscar historial de paciente</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Busque un paciente para ver su historial de estudios (solo pacientes atendidos por usted).</p>
                            <form action="<?= site_url('doctor/expediente/0') ?>" method="get" id="expediente_search_form">
                                <div class="input-group">
                                    <input type="text" id="paciente_search" name="q" class="form-control" placeholder="Nombre o apellido del paciente..." autocomplete="off">
                                    <input type="hidden" id="person_id" name="person_id" value="">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Ver historial</button>
                                </div>
                                <div id="suggestions_box" class="list-group position-absolute mt-1 shadow" style="z-index:1050;display:none;max-height:200px;overflow-y:auto;max-width:400px"></div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100 ynex-stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="ynex-stat-icon primary me-3">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div>
                                <div class="ynex-stat-label">Mis pacientes</div>
                                <div class="ynex-stat-value"><?= count($pacientes ?? []) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 ynex-stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="ynex-stat-icon <?= esc($summaryStatusClass) ?> me-3">
                                <i class="fa-solid fa-heart-pulse"></i>
                            </div>
                            <div>
                                <div class="ynex-stat-label">Estado general</div>
                                <div class="ynex-stat-value"><?= esc($doctorSummary['status'] ?? 'Normal') ?></div>
                                <small class="text-muted">Últimos <?= (int) ($doctorSummary['sample_size'] ?? 0) ?> reportes</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 ynex-stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="ynex-stat-icon warning me-3">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <div class="ynex-stat-label">Valores alterados</div>
                                <div class="ynex-stat-value"><?= (int) ($doctorSummary['altered_count'] ?? 0) ?></div>
                                <small class="text-muted">Acumulado reciente</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 ynex-stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="ynex-stat-icon danger me-3">
                                <i class="fa-solid fa-bell"></i>
                            </div>
                            <div>
                                <div class="ynex-stat-label">Críticos</div>
                                <div class="ynex-stat-value"><?= (int) ($doctorSummary['critical_count'] ?? 0) ?></div>
                                <small class="text-muted">Priorización inmediata</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="fw-semibold mb-1">Interpretación global</div>
                            <p class="small text-muted mb-0"><?= esc($doctorSummary['interpretation'] ?? 'Sin datos recientes para interpretar.') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3">
                    <h5 class="card-title mb-0 fw-semibold"><i class="fa-solid fa-magnifying-glass-chart me-2"></i>Buscador avanzado</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="<?= site_url('doctor/home') ?>" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Nombre, CI u orden</label>
                            <input type="text" name="q" class="form-control" value="<?= esc($clinicalFilters['q'] ?? '') ?>" placeholder="Paciente, CI, orden">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Desde</label>
                            <input type="date" name="date_from" class="form-control" value="<?= esc($clinicalFilters['date_from'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="date_to" class="form-control" value="<?= esc($clinicalFilters['date_to'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Query clínica</label>
                            <input type="text" name="clinical_query" class="form-control" value="<?= esc($clinicalFilters['clinical_query'] ?? '') ?>" placeholder="Ej: glucosa > 126">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="only_altered" value="1" id="only_altered" <?= !empty($clinicalFilters['only_altered']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="only_altered">Solo alterados</label>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                                <a href="<?= site_url('doctor/home') ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($doctorSummary['top_alterations'])): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3">
                    <h5 class="card-title mb-0 fw-semibold"><i class="fa-solid fa-list-check me-2"></i>Top 5 alteraciones recientes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Paciente</th>
                                    <th>Parámetro</th>
                                    <th>Valor</th>
                                    <th>Clasificación</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctorSummary['top_alterations'] as $alt): ?>
                                <tr>
                                    <td><?= esc($alt['paciente'] ?? '-') ?></td>
                                    <td><?= esc($alt['nombre'] ?? '-') ?></td>
                                    <td><?= esc(trim(($alt['valor'] ?? '-') . ' ' . ($alt['unidad'] ?? ''))) ?></td>
                                    <td>
                                        <span class="badge bg-<?= !empty($alt['critico']) ? 'danger' : 'warning text-dark' ?>">
                                            <?= !empty($alt['critico']) ? 'Crítico' : esc($alt['direccion'] ?? 'Alterado') ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= site_url('doctor/viewreport/' . (int) ($alt['registro_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary" target="_blank">Ver</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Sección de Comisiones -->
            <?php if (isset($comision_summary) && $comision_summary !== null): ?>
            <div class="row mb-4 g-3">
                <?php if ($hideCommissionDetails): ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 ynex-stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="ynex-stat-icon info me-3">
                                <i class="fa-solid fa-money-check"></i>
                            </div>
                            <div>
                                <div class="ynex-stat-label">Monto pagado</div>
                                <div class="ynex-stat-value"><?= $currencyIsRight
                                    ? (number_format($comision_summary->total_paid ?? 0, 2) . ' ' . esc($currencySym))
                                    : (esc($currencySym) . ' ' . number_format($comision_summary->total_paid ?? 0, 2)) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 ynex-stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="ynex-stat-icon success me-3">
                                <i class="fa-solid fa-dollar-sign"></i>
                            </div>
                            <div>
                                <div class="ynex-stat-label">Comisiones Pendientes</div>
                                <div class="ynex-stat-value"><?= $currencyIsRight
                                    ? (number_format($comision_summary->total_pending ?? 0, 2) . ' ' . esc($currencySym))
                                    : (esc($currencySym) . ' ' . number_format($comision_summary->total_pending ?? 0, 2)) ?></div>
                                <small class="text-muted"><?= (int)($comision_summary->pending_count ?? 0) ?> pendientes</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 ynex-stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="ynex-stat-icon info me-3">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div>
                                <div class="ynex-stat-label">Comisiones Pagadas</div>
                                <div class="ynex-stat-value"><?= $currencyIsRight
                                    ? (number_format($comision_summary->total_paid ?? 0, 2) . ' ' . esc($currencySym))
                                    : (esc($currencySym) . ' ' . number_format($comision_summary->total_paid ?? 0, 2)) ?></div>
                                <small class="text-muted"><?= (int)($comision_summary->paid_count ?? 0) ?> pagadas</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 ynex-stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="ynex-stat-icon warning me-3">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div>
                                <div class="ynex-stat-label">Total Comisiones</div>
                                <div class="ynex-stat-value"><?= $currencyIsRight
                                    ? (number_format($comision_summary->total_commissions ?? 0, 2) . ' ' . esc($currencySym))
                                    : (esc($currencySym) . ' ' . number_format($comision_summary->total_commissions ?? 0, 2)) ?></div>
                                <small class="text-muted">Acumulado total</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-xl-5">
                    <!-- Mis pacientes -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-transparent py-3">
                            <h5 class="card-title mb-0 fw-semibold"><i class="fa-solid fa-users me-2"></i>Mis pacientes</h5>
                        </div>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <?php if (!empty($pacientes)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($pacientes as $p): ?>
                                <a href="<?= site_url('doctor/expediente/' . ($p->person_id ?? 0)) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <?= esc($p->nombre ?? '-') ?>
                                    <span class="badge bg-secondary rounded-pill"><?= (int)($p->total_registros ?? 0) ?> estudios</span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-muted text-center py-4 mb-0">Aún no tiene pacientes registrados</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$hideCommissionDetails): ?>
                    <!-- Comisiones Pagadas Recientes -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent py-3">
                            <h5 class="card-title mb-0 fw-semibold"><i class="fa-solid fa-dollar-sign me-2"></i>Comisiones Pagadas Recientes</h5>
                        </div>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <?php if (isset($comisiones_recientes) && !empty($comisiones_recientes)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th># Registro</th>
                                            <th>Paciente</th>
                                            <th>Comisión</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($comisiones_recientes as $com): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= site_url('doctor/viewreport/' . $com->registro_id) ?>" 
                                                   class="text-decoration-none" target="_blank">
                                                    #<?= $com->registro_id ?>
                                                </a>
                                            </td>
                                            <td><?= esc($com->paciente_nombre ?? 'N/A') ?></td>
                                            <td>
                                                <span class="fw-bold text-success"><?= $currencyIsRight
                                                    ? (number_format($com->commission_amount, 2) . ' ' . esc($currencySym))
                                                    : (esc($currencySym) . ' ' . number_format($com->commission_amount, 2)) ?></span>
                                                <br><small class="text-muted"><?= number_format($com->commission_percent, 2) ?>%</small>
                                            </td>
                                            <td>
                                                <i class="fa-solid fa-calendar-check text-success"></i>
                                                <?= !empty($com->paid_date) ? date('d/m/Y', strtotime($com->paid_date)) : '-' ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($comisiones_recientes) >= 5): ?>
                            <div class="text-center p-2 border-top">
                                <small class="text-muted">Mostrando las 5 comisiones más recientes</small>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                            <p class="text-muted text-center py-4 mb-0">No hay comisiones pagadas recientes</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-xl-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent py-3">
                            <h5 class="card-title mb-0 fw-semibold"><i class="fa-solid fa-clock-rotate-left me-2"></i>Registros recientes</h5>
                        </div>
                        <div class="card-body p-0" style="max-height: 620px; overflow-y: auto;">
                            <?php if (!empty($registros_recientes)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>Paciente</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registros_recientes as $reg): ?>
                                        <tr>
                                            <td><?= (int)($reg->registro_id ?? 0) ?></td>
                                            <td><?= esc($reg->paciente ?? '-') ?></td>
                                            <td><?= !empty($reg->ingreso) ? date('d/m/Y H:i', strtotime($reg->ingreso)) : '-' ?></td>
                                            <td>
                                                <?php
                                                $rid = (int) ($reg->registro_id ?? 0);
                                                $sum = $clinicalSummaries[$rid] ?? null;
                                                $badge = $sum['status_class'] ?? 'secondary';
                                                ?>
                                                <?php if ($sum): ?>
                                                    <span class="badge bg-<?= esc($badge) ?>"><?= esc($sum['status'] ?? 'Normal') ?></span>
                                                    <small class="text-muted d-block"><?= (int) ($sum['altered_count'] ?? 0) ?> alt. / <?= (int) ($sum['critical_count'] ?? 0) ?> crit.</small>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Sin resumen</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?= site_url('doctor/viewreport/' . ($reg->registro_id ?? 0)) ?>" class="btn btn-sm btn-outline-primary" target="_blank">Ver reporte</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (($totalPages ?? 1) > 1): ?>
                            <div class="p-3 d-flex justify-content-between align-items-center border-top">
                                <small class="text-muted">
                                    Mostrando <?= (int)(($page - 1) * $perPage + 1) ?> - <?= (int) min($page * $perPage, $totalRegistros) ?> de <?= (int) $totalRegistros ?>
                                </small>
                                <nav aria-label="Paginación registros doctor">
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= site_url('doctor/home?page=' . max(1, $page - 1)) ?>">Anterior</a>
                                        </li>
                                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                            <li class="page-item <?= ($p === (int)$page) ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= site_url('doctor/home?page=' . $p) ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= site_url('doctor/home?page=' . min($totalPages, $page + 1)) ?>">Siguiente</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                            <p class="text-muted text-center py-4 mb-0">No hay registros recientes</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('expediente_search_form');
    var input = document.getElementById('paciente_search');
    var personInput = document.getElementById('person_id');
    var suggestionsBox = document.getElementById('suggestions_box');
    var debounceTimer;

    form.addEventListener('submit', function(e) {
        if (!personInput.value) {
            e.preventDefault();
            uiAlert('Seleccione un paciente de la lista de sugerencias.', 'Validación');
            input.focus();
            return;
        }
        form.action = '<?= site_url('doctor/expediente/') ?>' + personInput.value;
    });

    input.addEventListener('input', function() {
        var q = this.value.trim();
        clearTimeout(debounceTimer);
        personInput.value = '';
        suggestionsBox.style.display = 'none';
        if (q.length < 2) return;

        debounceTimer = setTimeout(function() {
            var body = 'q=' + encodeURIComponent(q);
            if (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' && window.CI_CSRF_TOKEN) {
                body = (window.CI_CSRF_TOKEN_NAME || 'csrf_test_name') + '=' + encodeURIComponent(window.CI_CSRF_TOKEN) + '&' + body;
            }
            fetch('<?= site_url('doctor/search') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
            .then(function(r) { return r.ok ? r.json() : []; })
            .then(function(data) {
                suggestionsBox.innerHTML = '';
                if (!Array.isArray(data) || data.length === 0) {
                    suggestionsBox.innerHTML = '<div class="list-group-item text-muted">No se encontraron pacientes</div>';
                } else {
                    data.forEach(function(item) {
                        var a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action';
                        a.textContent = item.value;
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
                            input.value = item.value;
                            personInput.value = item.data;
                            suggestionsBox.style.display = 'none';
                        });
                        suggestionsBox.appendChild(a);
                    });
                }
                suggestionsBox.style.display = 'block';
                suggestionsBox.style.width = input.offsetWidth + 'px';
            });
        }, 300);
    });

    input.addEventListener('blur', function() { setTimeout(function() { suggestionsBox.style.display = 'none'; }, 200); });
});
</script>
<?= $this->endSection() ?>
