<?= $this->extend('layouts/doctor') ?>

<?= $this->section('title') ?>Mi panel<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $tituloDoctor = ((int) ($doctor_info->gender ?? 0) === 2) ? 'Dra.' : 'Dr.'; ?>
<h1 class="h2 mb-4 fw-bold"><i class="fa-solid fa-user-doctor me-2"></i>Mi panel - <?= $tituloDoctor ?> <?= esc($doctor_info->name ?? '') ?></h1>

<div class="row mb-4 g-3">
    <div class="col-md-6">
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
    <div class="col-md-6">
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

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0 fw-semibold"><i class="fa-solid fa-users me-2"></i>Mis pacientes</h5>
            </div>
            <div class="card-body p-0">
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
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0 fw-semibold"><i class="fa-solid fa-clock-rotate-left me-2"></i>Registros recientes</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($registros_recientes)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Paciente</th>
                                <th>Fecha</th>
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
            alert('Seleccione un paciente de la lista de sugerencias.');
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
