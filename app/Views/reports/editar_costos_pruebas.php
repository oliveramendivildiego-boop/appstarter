<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
helper('layout');
$currencySym = currency_symbol();
$tenantKey   = (string) ($tenant_scope['tenant_key'] ?? '');
$tenantDb    = (string) ($tenant_scope['database'] ?? '');
?>
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="mb-1"><?= esc($title) ?></h3>
            <p class="text-muted mb-0"><?= esc($subtitle) ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('reports/costosPruebas') ?><?= ! empty($busqueda) ? '?busqueda=' . urlencode($busqueda) : '' ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-list me-1"></i> Ver reporte
            </a>
            <a href="<?= site_url('reports/exportCostosPruebasImport') ?>" class="btn btn-outline-success btn-sm btn-export-costos-csv" data-export-base="<?= esc(site_url('reports/exportCostosPruebasImport'), 'attr') ?>">
                <i class="fas fa-file-download me-1"></i> Exportar CSV
            </a>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-4 border-success">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Importar / Exportar precios (CSV)</h5>
        <?php if ($tenantKey !== ''): ?>
        <span class="badge bg-light text-dark" title="Base de datos: <?= esc($tenantDb) ?>">
            Tenant: <strong><?= esc($tenantKey) ?></strong>
        </span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            La exportación e importación solo afectan al catálogo del laboratorio actual
            <?php if ($tenantKey !== ''): ?>(<strong><?= esc($tenantKey) ?></strong><?= $tenantDb !== '' ? ' · BD ' . esc($tenantDb) : '' ?>)<?php endif; ?>.
            No modifica otros tenants ni la base central de administración.
        </p>
        <p class="text-muted small mb-3">
            Formato del archivo (separado por comas): <code>GRUPO,PRUEBA,ID,PRECIO,PRECIO_DERIVADO</code>.
            Ejemplo: <code>Hematologia,Hemograma completo,19,60,50</code>
            (<code>PRECIO</code> = columna «Precio»; <code>PRECIO_DERIVADO</code> = columna «Precio derivado» de la tabla).
            La referencia es el <strong>ID</strong>: al importar se actualizan nombre, precio y precio derivado según el CSV.
            GRUPO es solo informativa.
            Exportar descarga <strong>todas</strong> las pruebas tal como están guardadas en base de datos.
            <strong>Guarde los precios</strong> antes de exportar si editó la tabla de abajo.
        </p>
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <a href="<?= site_url('reports/exportCostosPruebasImport') ?>" class="btn btn-success w-100 btn-export-costos-csv" data-export-base="<?= esc(site_url('reports/exportCostosPruebasImport'), 'attr') ?>">
                    <i class="fas fa-file-download me-1"></i> Exportar todos los precios
                </a>
            </div>
            <div class="col-md-6">
                <?= form_open_multipart('reports/importCostosPruebas', ['id' => 'form_import_costos_csv', 'class' => 'd-flex flex-wrap gap-2 align-items-end']) ?>
                <input type="hidden" name="busqueda" value="<?= esc($busqueda) ?>">
                <input type="hidden" name="tenant_key" value="<?= esc($tenantKey) ?>">
                <div class="flex-grow-1">
                    <label for="costos_csv" class="form-label small mb-1">Archivo CSV</label>
                    <input type="file" name="costos_csv" id="costos_csv" class="form-control form-control-sm" accept=".csv,.txt,text/csv" required>
                </div>
                <button type="button" class="btn btn-primary" id="btn_open_import_costos_modal">
                    <i class="fas fa-file-upload me-1"></i> Importar
                </button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImportCostosCsv" tabindex="-1" aria-labelledby="modalImportCostosCsvLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalImportCostosCsvLabel">
                    <i class="fas fa-file-upload text-primary me-2"></i>Confirmar importación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">¿Importar precios desde el archivo CSV seleccionado?</p>
                <p class="text-muted small mb-0">
                    Se actualizarán el <strong>nombre</strong>, el <strong>precio</strong> y el <strong>precio derivado</strong>
                    (si viene en el archivo) de cada prueba según su <strong>ID</strong>, solo en el laboratorio actual
                    <?php if ($tenantKey !== ''): ?>(<strong><?= esc($tenantKey) ?></strong>)<?php endif; ?>.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn_confirm_import_costos_csv">
                    <i class="fas fa-file-upload me-1"></i> Importar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= site_url('reports/editarCostosPruebas') ?>" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="busqueda" class="form-control"
                       placeholder="Buscar por categoría o nombre de prueba..."
                       value="<?= esc($busqueda) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Buscar
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($data)): ?>
<div class="card shadow-sm">
    <div class="card-body text-center text-muted py-5">
        <i class="fas fa-search fa-3x mb-3"></i>
        <p class="mb-0">No se encontraron pruebas con los criterios de búsqueda.</p>
    </div>
</div>
<?php else: ?>
<?= form_open('reports/saveCostosPruebas', ['id' => 'form_costos_pruebas']) ?>
<input type="hidden" name="busqueda" value="<?= esc($busqueda) ?>">

<div class="card shadow-sm mb-2 border-warning">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-2 costos-mass-toolbar">
            <span class="small text-muted text-nowrap"><i class="fas fa-bolt me-1"></i>Masivo:</span>
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted mb-0 text-nowrap" for="mass_precio">Precio</label>
                <input type="number" id="mass_precio" class="form-control form-control-sm costos-mass-input" min="0" step="1" placeholder="<?= esc($currencySym) ?>">
            </div>
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted mb-0 text-nowrap" for="mass_derivado">Deriv.</label>
                <input type="number" id="mass_derivado" class="form-control form-control-sm costos-mass-input" min="0" step="1" placeholder="<?= esc($currencySym) ?>">
            </div>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary" id="btn_mass_both" title="Aplicar a todas las filas">Todas</button>
                <button type="button" class="btn btn-outline-primary" id="btn_mass_checked" title="Aplicar a filas marcadas">Selección</button>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm py-0" id="btn_copy_precio_derivado" title="Copiar precio a derivado en todas">Precio→deriv.</button>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" id="btn_select_all" title="Marcar todas">✓ Todas</button>
                <button type="button" class="btn btn-outline-secondary" id="btn_select_none" title="Quitar marcas">✕</button>
            </div>
            <span class="small text-muted ms-auto d-none d-md-inline">Campo vacío = no cambia. Guarde al final.</span>
        </div>
    </div>
</div>
<?= view('reports/partials/costos_pruebas_table_styles') ?>
<style>
.costos-mass-toolbar .costos-mass-input { width: 4.5rem; }
.costos-mass-toolbar .btn-group-sm > .btn { font-size: 0.75rem; padding: 0.15rem 0.45rem; }
.costos-mass-toolbar .btn-sm.py-0 { font-size: 0.75rem; padding: 0.15rem 0.4rem; }
</style>

<div class="card shadow-sm">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2 sticky-top" style="top: 0; z-index: 1020;">
        <h5 class="mb-0">Precios editables</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark"><?= count($data) ?> pruebas</span>
            <button type="submit" class="btn btn-light btn-sm fw-semibold text-success">
                <i class="fas fa-save me-1"></i> Guardar todos los precios
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 70vh;">
            <table class="table table-hover table-sm mb-0 align-middle table-costos-pruebas" id="tabla_costos_edit">
                <thead class="table-dark sticky-top" style="top: 0; z-index: 1010;">
                    <tr>
                        <th style="width: 2.5rem;" class="text-center">
                            <input type="checkbox" class="form-check-input" id="check_all" title="Seleccionar todas">
                        </th>
                        <th>Categoría</th>
                        <th>Prueba</th>
                        <th style="width: 8rem;" class="text-end">Precio (<?= esc($currencySym) ?>)</th>
                        <th style="width: 8rem;" class="text-end">Precio derivado (<?= esc($currencySym) ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $categoriaHeaderKey = null;
                    foreach ($data as $item):
                        $id = (int) ($item['prianacategoria_id'] ?? 0);
                        if ($id < 1) {
                            continue;
                        }
                        $catNombre = (string) ($item['categoria'] ?? '');
                        $catHeaderKey = mb_strtolower($catNombre, 'UTF-8');
                        $precio = (int) ($item['precio'] ?? 0);
                        $derivado = (int) ($item['precio_derivado'] ?? 0);
                        if ($categoriaHeaderKey !== $catHeaderKey):
                            $categoriaHeaderKey = $catHeaderKey;
                            ?>
                    <tr class="costos-grupo-header">
                        <td colspan="5" class="py-2">
                            <i class="fas fa-folder me-2"></i><?= esc($catNombre) ?>
                        </td>
                    </tr>
                        <?php endif; ?>
                    <tr data-row-id="<?= $id ?>">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input row-check" value="<?= $id ?>">
                        </td>
                        <td></td>
                        <td><?= esc($item['prueba']) ?></td>
                        <td>
                            <input type="number" name="precio[<?= $id ?>]" class="form-control form-control-sm text-end input-precio"
                                   min="0" step="1" value="<?= $precio ?>" data-id="<?= $id ?>">
                        </td>
                        <td>
                            <input type="number" name="precio_derivado[<?= $id ?>]" class="form-control form-control-sm text-end input-derivado"
                                   min="0" step="1" value="<?= $derivado ?>" data-id="<?= $id ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="<?= site_url('reports') ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a reportes
        </a>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Guardar todos los precios
        </button>
    </div>
</div>
<?= form_close() ?>
<?php endif; ?>

<script>
(function () {
    const formImport = document.getElementById('form_import_costos_csv');
    const btnOpenImport = document.getElementById('btn_open_import_costos_modal');
    const btnConfirmImport = document.getElementById('btn_confirm_import_costos_csv');
    const modalImportEl = document.getElementById('modalImportCostosCsv');

    if (formImport && btnOpenImport && btnConfirmImport && modalImportEl && typeof bootstrap !== 'undefined') {
        btnOpenImport.addEventListener('click', function () {
            if (!formImport.reportValidity()) {
                return;
            }
            bootstrap.Modal.getOrCreateInstance(modalImportEl).show();
        });

        btnConfirmImport.addEventListener('click', function () {
            const modal = bootstrap.Modal.getInstance(modalImportEl);
            if (modal) {
                modal.hide();
            }
            formImport.submit();
        });
    }

    const form = document.getElementById('form_costos_pruebas');
    if (!form) return;

    const checkAll = document.getElementById('check_all');
    const rowChecks = () => Array.from(document.querySelectorAll('.row-check'));
    const precioInputs = () => Array.from(document.querySelectorAll('.input-precio'));
    const derivInputs = () => Array.from(document.querySelectorAll('.input-derivado'));

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rowChecks().forEach(cb => { cb.checked = checkAll.checked; });
        });
    }

    document.getElementById('btn_select_all')?.addEventListener('click', function () {
        rowChecks().forEach(cb => { cb.checked = true; });
        if (checkAll) checkAll.checked = true;
    });

    document.getElementById('btn_select_none')?.addEventListener('click', function () {
        rowChecks().forEach(cb => { cb.checked = false; });
        if (checkAll) checkAll.checked = false;
    });

    function applyMass(onlyChecked) {
        const p = document.getElementById('mass_precio')?.value;
        const d = document.getElementById('mass_derivado')?.value;
        const setP = p !== '' && p !== null;
        const setD = d !== '' && d !== null;
        if (!setP && !setD) {
            alert('Indique al menos un precio o precio derivado para aplicar.');
            return;
        }

        const targets = onlyChecked
            ? rowChecks().filter(cb => cb.checked).map(cb => parseInt(cb.value, 10))
            : null;

        precioInputs().forEach(inp => {
            const id = parseInt(inp.dataset.id, 10);
            if (targets && targets.indexOf(id) === -1) return;
            if (setP) inp.value = Math.max(0, parseInt(p, 10) || 0);
        });
        derivInputs().forEach(inp => {
            const id = parseInt(inp.dataset.id, 10);
            if (targets && targets.indexOf(id) === -1) return;
            if (setD) inp.value = Math.max(0, parseInt(d, 10) || 0);
        });

        if (onlyChecked && targets && targets.length === 0) {
            alert('Seleccione al menos una prueba.');
        }
    }

    document.getElementById('btn_mass_both')?.addEventListener('click', () => applyMass(false));
    document.getElementById('btn_mass_checked')?.addEventListener('click', () => applyMass(true));

    document.getElementById('btn_copy_precio_derivado')?.addEventListener('click', function () {
        precioInputs().forEach(inp => {
            const id = inp.dataset.id;
            const der = document.querySelector('.input-derivado[data-id="' + id + '"]');
            if (der) der.value = inp.value;
        });
    });

    form.addEventListener('submit', function (e) {
        if (!confirm('¿Guardar todos los precios de esta lista?')) {
            e.preventDefault();
        }
    });

    function formHasUnsavedCostChanges() {
        let dirty = false;
        precioInputs().forEach(inp => {
            const orig = inp.dataset.savedValue;
            if (orig !== undefined && String(inp.value) !== String(orig)) {
                dirty = true;
            }
        });
        derivInputs().forEach(inp => {
            const orig = inp.dataset.savedValue;
            if (orig !== undefined && String(inp.value) !== String(orig)) {
                dirty = true;
            }
        });
        return dirty;
    }

    precioInputs().concat(derivInputs()).forEach(inp => {
        inp.dataset.savedValue = inp.value;
    });

    document.querySelectorAll('.btn-export-costos-csv').forEach(link => {
        link.addEventListener('click', function (e) {
            const base = this.dataset.exportBase || this.getAttribute('href') || '';
            const sep = base.indexOf('?') >= 0 ? '&' : '?';
            this.href = base + sep + '_=' + Date.now();

            if (form && formHasUnsavedCostChanges()) {
                const ok = confirm(
                    'Hay cambios sin guardar en la tabla. El CSV solo incluye precios ya guardados en la base de datos.\n\n' +
                    '¿Desea exportar de todos modos? (Use «Guardar todos los precios» primero para incluir sus cambios.)'
                );
                if (!ok) {
                    e.preventDefault();
                }
            }
        });
    });
})();
</script>
<?= $this->endSection() ?>
