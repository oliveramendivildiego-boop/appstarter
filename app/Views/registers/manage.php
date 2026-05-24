<?= $this->extend('layouts/main') ?>
<?php $pageTitle = !empty($edit_registro) ? 'Editar orden' : 'Nuevo registro'; ?>
<?= $this->section('title') ?><?= esc($pageTitle) ?><?= $this->endSection() ?>
<?= $this->section('head_extra') ?>
<script src="<?= base_url('js/vendor/jquery.validate.min.js') ?>"></script>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<style>
    #modalSeleccionPruebas .modal-dialog {
        max-width: 96vw;
    }
    #modalSeleccionPruebas .modal-content {
        min-height: 90vh;
    }
    #modalSeleccionPruebas .modal-body {
        max-height: calc(90vh - 140px);
        overflow-y: auto;
    }
    #modal_pruebas_lista {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: .5rem;
    }
    #modal_pruebas_lista .list-group-item {
        border-width: 1px;
        border-radius: .35rem;
        font-size: .875rem;
        padding: .5rem .6rem;
    }
    #modal_pruebas_lista .modal-prueba-categoria-header {
        background: #0d6efd;
        border: 1px solid #0a58ca;
        border-radius: .35rem;
        color: #fff;
        padding: .35rem .5rem;
    }
    #modal_pruebas_lista .modal-prueba-categoria-header .form-check-label {
        font-weight: 700;
    }
    #modal_pruebas_lista .modal-prueba-categoria-header .form-check-input {
        border-color: #111;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, .65);
    }
    #modal_pruebas_lista .form-check {
        min-height: 1.25rem;
    }
    #modal_pruebas_lista .form-check-input {
        border: 2px solid #198754;
        box-shadow: 0 0 0 1px rgba(25, 135, 84, .15);
        height: 1.05rem;
        margin-top: .15rem;
        width: 1.05rem;
    }
    #modal_pruebas_lista .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }
    #modal_pruebas_lista .modal-prueba-categoria-header .form-check-input {
        border-color: #111;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, .65);
    }
    #modal_pruebas_lista .modal-prueba-item-row .form-check-label::before {
        background: #20c997;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(32, 201, 151, .16);
        content: "";
        display: inline-block;
        height: .48rem;
        margin: 0 .45rem .05rem 0;
        width: .48rem;
    }
    #modal_pruebas_lista .badge {
        font-size: .7rem;
        white-space: nowrap;
    }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$pruebasLookup = [];
foreach ($categories ?? [] as $cat) {
    $padreName = $cat['name'] ?? '';
    foreach ($cat['items'] ?? [] as $item) {
        $pruebasLookup[(string)($item['id'] ?? '')] = [
            'name' => $item['name'] ?? '',
            'padre' => $padreName,
            'cost' => (float)($item['cost'] ?? 0)
        ];
    }
}
?>
<script>window.PRUEBAS_LOOKUP = <?= json_encode($pruebasLookup, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
<script>window.PRUEBAS_CATEGORIES = <?= json_encode($categories ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
<?php
$editPayload = null;
if (!empty($edit_registro)) {
    $editPayload = [
        'registro_id' => (int)($edit_registro->registro_id ?? 0),
        'person_id'   => (int)($edit_registro->person_id ?? 0),
        'doctor_id'   => (int)($edit_registro->doctor_id ?? 0),
        'paciente'    => trim((string)(($edit_registro->first_name ?? '') . ' ' . ($edit_registro->last_name_fa ?? ''))),
        'doctor'      => trim((string)($edit_registro->doctor_name ?? '')),
        'prioridad'   => (int)($edit_registro->prioridad ?? 0),
        'diagnostico_presuntivo' => (string)($edit_registro->diagnostico_presuntivo ?? ''),
        'motivo_estudio' => (string)($edit_registro->motivo_estudio ?? ''),
        'pruebas'     => (string)($edit_registro->pruebas ?? ''),
        'regvalues_count' => (int)($edit_regvalues_count ?? 0),
        'pago'        => [
            'total_reco'  => $edit_pago->total_reco ?? '',
            'total'       => $edit_pago->total ?? '',
            'monto_pagar' => $edit_pago->monto_pagar ?? '',
            'tipopago'    => $edit_pago->tipopago ?? '',
            'saldo'       => $edit_pago->saldo ?? '',
            'comentarios' => $edit_pago->comentarios ?? '',
        ],
        'institucion' => trim((string) (($edit_discount_info['institucion'] ?? '') ?: ($edit_registro->customer_institucion ?? ''))),
        'descuento'   => (float) ($edit_discount_info['descuento'] ?? 0),
    ];
}
?>
<script>window.EDIT_REGISTRO = <?= json_encode($editPayload) ?>;</script>
<?= view('partial/breadcrumb_nav', [
    'items' => [['label' => lang('Module.module_registers'), 'url' => site_url('registers')]],
    'right' => '<a href="' . site_url('registers/lista') . '" class="btn btn-outline-primary">Ver lista</a>' .
        '<a href="' . site_url('expediente') . '" class="btn btn-outline-info">Historial paciente</a>' .
        '<button type="button" id="btn_open_modal_paciente" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalCrearPaciente"><i class="fa-solid fa-user-plus me-1"></i>Paciente</button>' .
        '<button type="button" id="btn_open_modal_doctor" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCrearDoctor"><i class="fa-solid fa-user-doctor me-1"></i>Doctor</button>',
]) ?>

<div id="registers_form_error" class="alert alert-danger" style="display:none;"></div>
<div class="row">
    <div class="col-md-8 mb-3">
        <?= view('registers/form_basic_info') ?>
        <div id="pruebas_error" class="text-danger small mb-2" style="display:none;"></div>
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <label class="form-label mb-0">Pruebas seleccionadas:</label>
                <button type="button" id="btn_open_modal_pruebas" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSeleccionPruebas">
                    <i class="fa-solid fa-list-check me-1"></i>Seleccionar pruebas
                </button>
            </div>
            <div id="pruebas_lista" class="border rounded p-2 bg-light" style="min-height:60px;">
                <p class="text-muted small mb-0">Use el buscador para agregar pruebas. La lista aparecerá aquí.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <?= view('registers/form_pagos') ?>
    </div>
</div>

<div class="modal fade" id="modalSeleccionPruebas" tabindex="-1" aria-labelledby="modalSeleccionPruebasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSeleccionPruebasLabel"><i class="fa-solid fa-flask-vial me-2"></i>Seleccionar pruebas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modal_pruebas_search" class="form-label">Buscar análisis</label>
                    <input type="text" class="form-control" id="modal_pruebas_search" placeholder="Escriba para filtrar por padre o prueba">
                </div>
                <div id="modal_pruebas_lista">
                    <p class="text-muted small mb-0">No hay análisis configurados.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn_agregar_pruebas_modal" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i>Agregar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCrearPaciente" tabindex="-1" aria-labelledby="modalCrearPacienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCrearPacienteLabel"><i class="fa-solid fa-user-plus me-2"></i>Nuevo paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="crear_paciente_alert" class="alert" style="display:none;"></div>
                <form id="form_crear_paciente" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="np_ci" class="form-label">CI</label>
                            <input type="text" class="form-control" id="np_ci" name="ci">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="np_first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="np_first_name" name="first_name" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="np_last_name_fa" class="form-label">Apellido paterno <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="np_last_name_fa" name="last_name_fa" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="np_last_name_mom" class="form-label">Apellido materno</label>
                            <input type="text" class="form-control" id="np_last_name_mom" name="last_name_mom">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="np_phone_number" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="np_phone_number" name="phone_number">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="np_email" class="form-label">Correo</label>
                            <input type="email" class="form-control" id="np_email" name="email">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="np_birthday" class="form-label">Fecha nacimiento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="np_birthday" name="birthday" placeholder="AAAA-MM-DD" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="np_gender" class="form-label">Género <span class="text-danger">*</span></label>
                            <select class="form-select" id="np_gender" name="gender" required>
                                <option value="">Seleccione...</option>
                                <option value="1">Masculino</option>
                                <option value="2">Femenino</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="np_institucion" class="form-label">Institución</label>
                            <input type="text" class="form-control" id="np_institucion" name="institucion">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="np_seguro" class="form-label">Seguro</label>
                            <input type="text" class="form-control" id="np_seguro" name="seguro">
                        </div>
                        <div class="col-12">
                            <label for="np_comments" class="form-label">Comentarios</label>
                            <textarea class="form-control" id="np_comments" name="comments" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn_guardar_paciente_modal" class="btn btn-success">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Guardar paciente
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCrearDoctor" tabindex="-1" aria-labelledby="modalCrearDoctorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCrearDoctorLabel"><i class="fa-solid fa-user-doctor me-2"></i>Nuevo doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="crear_doctor_alert" class="alert" style="display:none;"></div>
                <form id="form_crear_doctor" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="nd_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nd_name" name="name" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="nd_phone_number" class="form-label">Teléfono <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nd_phone_number" name="phone_number" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="nd_gender" class="form-label">Género <span class="text-danger">*</span></label>
                            <select class="form-select" id="nd_gender" name="gender" required>
                                <option value="">Seleccione...</option>
                                <option value="1">Masculino</option>
                                <option value="2">Femenino</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="nd_speciality" class="form-label">Especialidad <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nd_speciality" name="speciality" required>
                        </div>
                        <div class="col-12">
                            <label for="nd_address" class="form-label">Dirección <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nd_address" name="address" required>
                        </div>
                        <div class="col-12">
                            <label for="nd_comments" class="form-label">Comentarios</label>
                            <textarea class="form-control" id="nd_comments" name="comments" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn_guardar_doctor_modal" class="btn btn-secondary">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Guardar doctor
                </button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('search_prueba_input');
    var pruebaListDropdown = document.getElementById('prueba_list');
    var pruebaListaContainer = document.getElementById('pruebas_lista');
    var guardarBtn = document.getElementById('guardar');
    var pruebasModalEl = document.getElementById('modalSeleccionPruebas');
    var pruebasModal = (typeof bootstrap !== 'undefined' && pruebasModalEl) ? bootstrap.Modal.getOrCreateInstance(pruebasModalEl) : null;
    var modalPruebasLista = document.getElementById('modal_pruebas_lista');
    var modalPruebasSearch = document.getElementById('modal_pruebas_search');
    var btnAgregarPruebasModal = document.getElementById('btn_agregar_pruebas_modal');
    var modalPruebasSeleccionadas = {};
    var pacienteModalEl = document.getElementById('modalCrearPaciente');
    var doctorModalEl = document.getElementById('modalCrearDoctor');
    // focus: false -> flatpickr ancla el calendario en <body> y el "focus trap" de Bootstrap
    // impedía teclear el año; mismo comportamiento que en customers (sin modal).
    var pacienteModal = (typeof bootstrap !== 'undefined' && pacienteModalEl) ? bootstrap.Modal.getOrCreateInstance(pacienteModalEl, { focus: false }) : null;
    var doctorModal = (typeof bootstrap !== 'undefined' && doctorModalEl) ? bootstrap.Modal.getOrCreateInstance(doctorModalEl) : null;
    var pruebasSeleccionadas = []; // {id, name, padre, cost}
    var editInfo = (typeof window.EDIT_REGISTRO !== 'undefined') ? window.EDIT_REGISTRO : null;

    function getCsrfPair() {
        if (typeof window.CI_CSRF_TOKEN_NAME === 'undefined' || typeof window.CI_CSRF_TOKEN === 'undefined') {
            return null;
        }
        return { name: window.CI_CSRF_TOKEN_NAME, value: window.CI_CSRF_TOKEN };
    }

    function updateCsrfFromResponse(res) {
        if (!res || typeof res !== 'object') return;
        if (res.csrf_name && res.csrf_token) {
            window.CI_CSRF_TOKEN_NAME = res.csrf_name;
            window.CI_CSRF_TOKEN = res.csrf_token;
        }
    }

    function showInlineAlert(alertEl, isSuccess, message) {
        if (!alertEl) return;
        alertEl.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-danger');
        alertEl.textContent = message || '';
        alertEl.style.display = 'block';
    }

    function hideInlineAlert(alertEl) {
        if (!alertEl) return;
        alertEl.style.display = 'none';
        alertEl.textContent = '';
    }

    function toBodyString(formEl) {
        var fd = new FormData(formEl);
        var params = new URLSearchParams();
        fd.forEach(function(value, key) {
            params.append(key, value == null ? '' : String(value));
        });
        var csrf = getCsrfPair();
        if (csrf) params.append(csrf.name, csrf.value);
        return params.toString();
    }

    function recalcular() {
        var totalCost = 0;
        pruebasSeleccionadas.forEach(function(p) { totalCost += p.cost; });
        var totalReco = totalCost;
        var pct = parseFloat((document.getElementById('customer_descuento_pct') || {}).value || 0);
        if (isNaN(pct) || pct < 0) pct = 0;
        if (pct > 100) pct = 100;
        var totalConDescuento = totalReco * (1 - (pct / 100));
        var valReco = totalReco.toFixed(2);
        var valTotal = totalConDescuento.toFixed(2);
        var reco = document.getElementById('total_reco');
        var tot = document.getElementById('total');
        if (reco) reco.value = valReco;
        if (tot) {
            tot.value = valTotal;
            try { tot.dispatchEvent(new Event('input', { bubbles: true })); } catch (e) {}
        }
        if (typeof window.updateInstitutionDiscountInfo === 'function') {
            window.updateInstitutionDiscountInfo();
        }
    }
    window.recalcularTotalesRegistro = recalcular;

    window.updateInstitutionDiscountInfo = function() {
        var infoEl = document.getElementById('institucion_descuento_info');
        var inst = ((document.getElementById('customer_institucion') || {}).value || '').trim();
        var pct = parseFloat((document.getElementById('customer_descuento_pct') || {}).value || 0);
        if (!infoEl) return;
        if (!inst || isNaN(pct) || pct <= 0) {
            infoEl.textContent = '';
            return;
        }
        var totalBruto = 0;
        pruebasSeleccionadas.forEach(function(p) { totalBruto += parseFloat(p.cost || 0); });
        var totalVal = parseFloat((document.getElementById('total') || {}).value || 0);
        var ahorro = (!isNaN(totalVal)) ? Math.max(0, totalBruto - totalVal) : 0;
        if (ahorro > 0) {
            infoEl.textContent = 'Institución: ' + inst + ' — descuento aplicado: ' + pct.toFixed(2) + '% (ahorro: ' + formatCurrencyAmount(ahorro, 2) + ')';
        } else {
            infoEl.textContent = 'Institución: ' + inst + ' — descuento configurado: ' + pct.toFixed(2) + '% (sin ahorro aplicado)';
        }
    };

    function renderPruebasLista() {
        if (!pruebaListaContainer) return;
        pruebaListaContainer.innerHTML = '';
        if (pruebasSeleccionadas.length === 0) {
            pruebaListaContainer.innerHTML = '<p class="text-muted small mb-0">Use el buscador para agregar pruebas. La lista aparecerá aquí.</p>';
        } else {
            pruebasSeleccionadas.forEach(function(p, idx) {
                var row = document.createElement('div');
                row.className = 'd-flex align-items-center justify-content-between py-2 border-bottom pruebaitem';
                row.dataset.id = p.id;
                var displayName = (p.name || '');
                if (p.padre) displayName += ' <span class="text-muted small">(' + p.padre + ')</span>';
                var removeButton = p.locked
                    ? '<button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Esta prueba ya tiene resultados"><i class="fa-solid fa-lock"></i></button>'
                    : '<button type="button" class="btn btn-outline-danger btn-sm quitar-prueba" data-idx="' + idx + '" title="Eliminar"><i class="fa-solid fa-times"></i></button>';
                row.innerHTML = '<span class="flex-grow-1">' + displayName + '</span>' +
                    '<span class="badge bg-secondary me-2">' + formatCurrencyAmount(p.cost || 0, 0) + '</span>' +
                    removeButton;
                pruebaListaContainer.appendChild(row);
            });
        }
        recalcular();
    }

    function agregarPrueba(item) {
        if (!item || !item.data) return;
        if (pruebasSeleccionadas.some(function(p) { return String(p.id) === String(item.data); })) return;
        pruebasSeleccionadas.push({
            id: item.data,
            name: item.value || '',
            padre: item.padre || '',
            cost: parseFloat(item.cost || 0),
            locked: !!item.locked
        });
        renderPruebasLista();
    }

    function quitarPrueba(idx) {
        if (pruebasSeleccionadas[idx] && pruebasSeleccionadas[idx].locked) return;
        pruebasSeleccionadas.splice(idx, 1);
        renderPruebasLista();
    }

    function getPruebasSeleccionadasIds() {
        var ids = {};
        pruebasSeleccionadas.forEach(function(p) {
            ids[String(p.id)] = true;
        });
        return ids;
    }

    function updateCategoriaCheckboxState(categoryBlock) {
        if (!categoryBlock) return;
        var parentCheck = categoryBlock.querySelector('.modal-prueba-categoria');
        var childChecks = categoryBlock.querySelectorAll('.modal-prueba-item');
        if (!parentCheck || !childChecks.length) return;
        var checkedCount = 0;
        childChecks.forEach(function(chk) {
            if (chk.checked) checkedCount++;
        });
        parentCheck.checked = checkedCount === childChecks.length;
        parentCheck.indeterminate = checkedCount > 0 && checkedCount < childChecks.length;
    }

    function syncModalPruebasSeleccionadas() {
        if (!modalPruebasLista) return;
        modalPruebasLista.querySelectorAll('.modal-prueba-item').forEach(function(chk) {
            chk.checked = !!modalPruebasSeleccionadas[String(chk.value)];
        });
        modalPruebasLista.querySelectorAll('.modal-prueba-categoria-block').forEach(updateCategoriaCheckboxState);
    }

    function renderModalPruebasLista(filterText) {
        if (!modalPruebasLista) return;
        var categories = Array.isArray(window.PRUEBAS_CATEGORIES) ? window.PRUEBAS_CATEGORIES : [];
        var filter = (filterText || '').toLowerCase().trim();
        modalPruebasLista.innerHTML = '';
        var rendered = 0;

        categories.forEach(function(cat, catIdx) {
            var catName = String(cat.name || '');
            var items = Array.isArray(cat.items) ? cat.items : [];
            var visibleItems = items.filter(function(item) {
                if (!filter) return true;
                return catName.toLowerCase().indexOf(filter) !== -1 || String(item.name || '').toLowerCase().indexOf(filter) !== -1;
            });
            if (!visibleItems.length) return;

            var block = document.createElement('div');
            block.className = 'list-group-item modal-prueba-categoria-block';

            var header = document.createElement('div');
            header.className = 'form-check modal-prueba-categoria-header mb-2';
            var parentId = 'modal_prueba_cat_' + catIdx;
            var parentCheck = document.createElement('input');
            parentCheck.type = 'checkbox';
            parentCheck.className = 'form-check-input modal-prueba-categoria';
            parentCheck.id = parentId;
            var parentLabel = document.createElement('label');
            parentLabel.className = 'form-check-label';
            parentLabel.htmlFor = parentId;
            parentLabel.textContent = catName || 'Sin categoría';
            header.appendChild(parentCheck);
            header.appendChild(parentLabel);
            block.appendChild(header);

            var children = document.createElement('div');
            children.className = 'ps-2';
            visibleItems.forEach(function(item) {
                var itemId = String(item.id || '');
                if (!itemId) return;
                var row = document.createElement('div');
                row.className = 'form-check modal-prueba-item-row d-flex align-items-start gap-1 mb-1';
                var checkId = 'modal_prueba_' + itemId;
                var chk = document.createElement('input');
                chk.type = 'checkbox';
                chk.className = 'form-check-input modal-prueba-item';
                chk.id = checkId;
                chk.value = itemId;
                chk.dataset.name = item.name || '';
                chk.dataset.padre = catName;
                chk.dataset.cost = item.cost || 0;
                var label = document.createElement('label');
                label.className = 'form-check-label flex-grow-1 lh-sm';
                label.htmlFor = checkId;
                label.textContent = item.name || ('Prueba #' + itemId);
                var badge = document.createElement('span');
                badge.className = 'badge bg-secondary';
                badge.textContent = formatCurrencyAmount(item.cost || 0, 0);
                row.appendChild(chk);
                row.appendChild(label);
                row.appendChild(badge);
                children.appendChild(row);
            });
            block.appendChild(children);
            modalPruebasLista.appendChild(block);
            rendered++;
        });

        if (!rendered) {
            modalPruebasLista.innerHTML = '<p class="text-muted small mb-0">No se encontraron análisis.</p>';
            return;
        }
        syncModalPruebasSeleccionadas();
    }

    function agregarPruebasDesdeModal() {
        Object.keys(modalPruebasSeleccionadas).forEach(function(id) {
            if (!modalPruebasSeleccionadas[id]) return;
            var info = (window.PRUEBAS_LOOKUP || {})[id];
            if (info) {
                agregarPrueba({
                    data: id,
                    value: info.name || '',
                    padre: info.padre || '',
                    cost: info.cost || 0
                });
            }
        });
        if (pruebasModal) pruebasModal.hide();
    }


    function quitarInvalid() {
        document.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
        document.querySelectorAll('[id$="_error"]').forEach(function(el) { el.textContent = ''; });
        var pe = document.getElementById('pruebas_error');
        if (pe) { pe.textContent = ''; pe.style.display = 'none'; }
        var rfe = document.getElementById('registers_form_error');
        if (rfe) { rfe.textContent = ''; rfe.style.display = 'none'; }
    }

    function mostrarError(el, msg) {
        if (!el) return;
        el.classList.add('is-invalid');
        var err = document.getElementById(el.id + '_error');
        if (err) { err.textContent = msg; }
    }

    if (pruebasModalEl) {
        pruebasModalEl.addEventListener('show.bs.modal', function() {
            modalPruebasSeleccionadas = getPruebasSeleccionadasIds();
            if (modalPruebasSearch) modalPruebasSearch.value = '';
            renderModalPruebasLista('');
        });
    }
    if (modalPruebasSearch) {
        modalPruebasSearch.addEventListener('input', function() {
            renderModalPruebasLista(this.value);
        });
    }
    if (modalPruebasLista) {
        modalPruebasLista.addEventListener('change', function(e) {
            var parent = e.target.closest('.modal-prueba-categoria');
            if (parent) {
                var block = parent.closest('.modal-prueba-categoria-block');
                if (block) {
                    block.querySelectorAll('.modal-prueba-item').forEach(function(chk) {
                        chk.checked = parent.checked;
                        modalPruebasSeleccionadas[String(chk.value)] = parent.checked;
                    });
                    updateCategoriaCheckboxState(block);
                }
                return;
            }
            var child = e.target.closest('.modal-prueba-item');
            if (child) {
                modalPruebasSeleccionadas[String(child.value)] = child.checked;
                updateCategoriaCheckboxState(child.closest('.modal-prueba-categoria-block'));
            }
        });
    }
    if (btnAgregarPruebasModal) {
        btnAgregarPruebasModal.addEventListener('click', agregarPruebasDesdeModal);
    }

    if (guardarBtn) {
        guardarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            quitarInvalid();
            var pruebas = pruebasSeleccionadas.map(function(p) { return p.id; });
            var pruebasStr = pruebas.join(',');
            var prioridad = (document.getElementById('prioridad') && document.getElementById('prioridad').value) || '0';
            var registroData = {
                person_id: (document.getElementById('person_id') || {}).value || '',
                doctor_id: (document.getElementById('doctor_id') || {}).value || '',
                pruebas: pruebasStr,
                prioridad: prioridad,
                diagnostico_presuntivo: (document.getElementById('diagnostico_presuntivo') || {}).value || '',
                motivo_estudio: (document.getElementById('motivo_estudio') || {}).value || ''
            };
            var tipopagoVal = (document.getElementById('tipopago') || {}).value || '';
            var esPendiente = tipopagoVal === '4';
            var montoPagarRaw = ((document.getElementById('monto_pagar') || {}).value || '').trim();
            var totalNum = parseFloat((document.getElementById('total') || {}).value || 0);
            var saldoEl = document.getElementById('saldo');
            if (esPendiente && montoPagarRaw === '') {
                if (saldoEl) saldoEl.value = (!isNaN(totalNum) ? (totalNum - 0).toFixed(2) : '');
            }
            var pagosData = {
                total_reco: (document.getElementById('total_reco') || {}).value || '',
                total: (document.getElementById('total') || {}).value || '',
                monto_pagar: esPendiente && montoPagarRaw === '' ? '0' : (document.getElementById('monto_pagar') || {}).value || '',
                tipopago: tipopagoVal,
                saldo: (document.getElementById('saldo') || {}).value || '',
                comentarios: (document.getElementById('comentarios') || {}).value || ''
            };
            var primero = null;
            if (!registroData.person_id || registroData.person_id === '0') {
                var p = document.getElementById('paciente');
                mostrarError(p, 'Seleccione un paciente.'); primero = primero || p;
            }
            if (pruebasStr === '') {
                var pe = document.getElementById('pruebas_error');
                if (pe) { pe.textContent = 'Seleccione al menos una prueba.'; pe.style.display = 'block'; pe.className = 'text-danger small'; if (!primero) primero = pe; }
            }
            if (!pagosData.total_reco || parseFloat(pagosData.total_reco) <= 0) {
                var tr = document.getElementById('total_reco');
                mostrarError(tr, 'Total Recomendado es obligatorio.'); primero = primero || tr;
            }
            if (!pagosData.total || isNaN(parseFloat(pagosData.total))) {
                var tot = document.getElementById('total');
                mostrarError(tot, 'Total es obligatorio.'); primero = primero || tot;
            }
            if (!esPendiente) {
                if (!pagosData.monto_pagar || isNaN(parseFloat(pagosData.monto_pagar))) {
                    var mp = document.getElementById('monto_pagar');
                    mostrarError(mp, 'Monto a pagar es obligatorio.'); primero = primero || mp;
                }
            }
            if (!pagosData.tipopago) {
                var tp = document.getElementById('tipopago');
                mostrarError(tp, 'Seleccione tipo de pago.'); primero = primero || tp;
            }
            if (!pagosData.saldo || isNaN(parseFloat(pagosData.saldo))) {
                var s = document.getElementById('saldo');
                mostrarError(s, 'Saldo es obligatorio.'); primero = primero || s;
            }
            if (primero) {
                primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            var csrf = (typeof CI_CSRF_TOKEN !== 'undefined' && typeof CI_CSRF_TOKEN_NAME !== 'undefined')
                ? '&' + CI_CSRF_TOKEN_NAME + '=' + encodeURIComponent(CI_CSRF_TOKEN) : '';
            var urlGuardar = editInfo && editInfo.registro_id ? ('<?= site_url('registers/update') ?>/' + editInfo.registro_id) : '<?= site_url('registers/save') ?>';
            fetch(urlGuardar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'registro[person_id]=' + encodeURIComponent(registroData.person_id) +
                    '&registro[doctor_id]=' + encodeURIComponent(registroData.doctor_id) +
                    '&registro[pruebas]=' + encodeURIComponent(registroData.pruebas) +
                    '&registro[prioridad]=' + encodeURIComponent(registroData.prioridad) +
                    '&registro[diagnostico_presuntivo]=' + encodeURIComponent(registroData.diagnostico_presuntivo) +
                    '&registro[motivo_estudio]=' + encodeURIComponent(registroData.motivo_estudio) +
                    '&pagos[total_reco]=' + encodeURIComponent(pagosData.total_reco) +
                    '&pagos[total]=' + encodeURIComponent(pagosData.total) +
                    '&pagos[monto_pagar]=' + encodeURIComponent(pagosData.monto_pagar) +
                    '&pagos[tipopago]=' + encodeURIComponent(pagosData.tipopago) +
                    '&pagos[saldo]=' + encodeURIComponent(pagosData.saldo) +
                    '&pagos[comentarios]=' + encodeURIComponent(pagosData.comentarios) + csrf
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    window.location.href = '<?= site_url('registers/view') ?>/' + res.id;
                } else {
                    var errDiv = document.getElementById('registers_form_error');
                    if (errDiv) { errDiv.textContent = (res.message || 'Error al guardar.'); errDiv.className = 'alert alert-danger'; errDiv.style.display = 'block'; errDiv.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                }
            })
            .catch(function() {
                var errDiv = document.getElementById('registers_form_error');
                if (errDiv) { errDiv.textContent = 'Error en la petición.'; errDiv.className = 'alert alert-danger'; errDiv.style.display = 'block'; }
            });
        });
    }

    var formCrearPaciente = document.getElementById('form_crear_paciente');
    var formCrearDoctor = document.getElementById('form_crear_doctor');
    var btnGuardarPacienteModal = document.getElementById('btn_guardar_paciente_modal');
    var btnGuardarDoctorModal = document.getElementById('btn_guardar_doctor_modal');
    var pacienteAlert = document.getElementById('crear_paciente_alert');
    var doctorAlert = document.getElementById('crear_doctor_alert');
    var pacienteValidator = null;
    var doctorValidator = null;

    if (typeof flatpickr !== 'undefined') {
        var npBirthdayInp = document.getElementById('np_birthday');
        if (npBirthdayInp) {
            flatpickr(npBirthdayInp, {
                dateFormat: 'Y-m-d',
                maxDate: 'today',
                locale: 'es',
                allowInput: true,
                onOpen: function(selectedDates, dateStr, instance) {
                    if (typeof flatpickrPositionArrowTopLeft === 'function') {
                        flatpickrPositionArrowTopLeft(instance);
                    }
                }
            });
        }
    }

    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.validate === 'function') {
        pacienteValidator = window.jQuery('#form_crear_paciente').validate(window.jQuery.extend(true, {}, window.VALIDATE_COMMON_OPTIONS || {}, {
            rules: {
                first_name: { required: true, minlength: 2 },
                last_name_fa: { required: true, minlength: 2 },
                email: { email: true },
                birthday: { required: true, date: true },
                gender: { required: true }
            },
            messages: {
                first_name: { required: 'Por favor ingrese su nombre(s)', minlength: 'El nombre debe tener al menos 2 caracteres' },
                last_name_fa: { required: 'Por favor ingrese su apellido(s)', minlength: 'Debe tener al menos 2 caracteres' },
                email: { email: 'Ingrese un correo válido' },
                birthday: { required: 'Seleccione su fecha de nacimiento', date: 'Ingrese una fecha válida' },
                gender: { required: 'Seleccione su género' }
            }
        }));

        doctorValidator = window.jQuery('#form_crear_doctor').validate(window.jQuery.extend(true, {}, window.VALIDATE_COMMON_OPTIONS || {}, {
            rules: {
                name: { required: true, minlength: 2 },
                phone_number: { required: true, maxlength: 50 },
                gender: { required: true },
                speciality: { required: true, maxlength: 255 },
                address: { required: true, maxlength: 255 }
            },
            messages: {
                name: { required: 'Por favor ingrese nombre(s) y apellido(s)', minlength: 'El nombre debe tener al menos 2 caracteres' },
                phone_number: { required: 'El teléfono es obligatorio' },
                gender: { required: 'Seleccione su género' },
                speciality: { required: 'La especialidad es obligatoria' },
                address: { required: 'La dirección es obligatoria' }
            }
        }));
    }

    if (btnGuardarPacienteModal && formCrearPaciente) {
        btnGuardarPacienteModal.addEventListener('click', function() {
            hideInlineAlert(pacienteAlert);
            if (pacienteValidator) {
                if (!pacienteValidator.form()) return;
            } else if (!formCrearPaciente.checkValidity()) {
                formCrearPaciente.reportValidity();
                return;
            }
            btnGuardarPacienteModal.disabled = true;
            fetch('<?= site_url('customers/save') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: toBodyString(formCrearPaciente)
            })
            .then(function(r) { return r.json().catch(function() { return {}; }); })
            .then(function(res) {
                updateCsrfFromResponse(res);
                if (!res.success || !res.person_id || parseInt(res.person_id, 10) < 1) {
                    showInlineAlert(pacienteAlert, false, res.message || 'No se pudo crear el paciente.');
                    return;
                }
                var firstName = (document.getElementById('np_first_name') || {}).value || '';
                var apPat = (document.getElementById('np_last_name_fa') || {}).value || '';
                var apMat = (document.getElementById('np_last_name_mom') || {}).value || '';
                var nombrePaciente = [firstName, apPat, apMat].join(' ').replace(/\s+/g, ' ').trim();
                var institucion = ((document.getElementById('np_institucion') || {}).value || '').trim();
                var pacienteInput = document.getElementById('paciente');
                var personIdInput = document.getElementById('person_id');
                var instInput = document.getElementById('customer_institucion');
                var descInput = document.getElementById('customer_descuento_pct');
                if (pacienteInput) pacienteInput.value = nombrePaciente;
                if (personIdInput) personIdInput.value = String(res.person_id);
                if (instInput) instInput.value = institucion;
                if (descInput) descInput.value = '0';
                if (typeof window.updateInstitutionDiscountInfo === 'function') {
                    window.updateInstitutionDiscountInfo();
                }
                if (typeof window.recalcularTotalesRegistro === 'function') {
                    window.recalcularTotalesRegistro();
                }
                formCrearPaciente.reset();
                hideInlineAlert(pacienteAlert);
                if (pacienteModal) pacienteModal.hide();
            })
            .catch(function() {
                showInlineAlert(pacienteAlert, false, 'Error de red al guardar el paciente.');
            })
            .finally(function() {
                btnGuardarPacienteModal.disabled = false;
            });
        });
    }

    if (btnGuardarDoctorModal && formCrearDoctor) {
        btnGuardarDoctorModal.addEventListener('click', function() {
            hideInlineAlert(doctorAlert);
            if (doctorValidator) {
                if (!doctorValidator.form()) return;
            } else if (!formCrearDoctor.checkValidity()) {
                formCrearDoctor.reportValidity();
                return;
            }
            btnGuardarDoctorModal.disabled = true;
            fetch('<?= site_url('doctors/save') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: toBodyString(formCrearDoctor)
            })
            .then(function(r) { return r.json().catch(function() { return {}; }); })
            .then(function(res) {
                updateCsrfFromResponse(res);
                if (!res.success || !res.doctor_id || parseInt(res.doctor_id, 10) < 1) {
                    showInlineAlert(doctorAlert, false, res.message || 'No se pudo crear el doctor.');
                    return;
                }
                var nombreDoctor = (((document.getElementById('nd_name') || {}).value) || '').trim();
                var doctorInput = document.getElementById('doctor');
                var doctorIdInput = document.getElementById('doctor_id');
                if (doctorInput) doctorInput.value = nombreDoctor;
                if (doctorIdInput) doctorIdInput.value = String(res.doctor_id);
                formCrearDoctor.reset();
                hideInlineAlert(doctorAlert);
                if (doctorModal) doctorModal.hide();
            })
            .catch(function() {
                showInlineAlert(doctorAlert, false, 'Error de red al guardar el doctor.');
            })
            .finally(function() {
                btnGuardarDoctorModal.disabled = false;
            });
        });
    }

    // Prefill al editar orden
    if (editInfo && editInfo.registro_id) {
        try {
            var personIdEl = document.getElementById('person_id');
            var doctorIdEl = document.getElementById('doctor_id');
            var pacienteEl = document.getElementById('paciente');
            var doctorEl = document.getElementById('doctor');
            var prioridadEl = document.getElementById('prioridad');
            var diagnosticoEl = document.getElementById('diagnostico_presuntivo');
            var motivoEl = document.getElementById('motivo_estudio');
            if (personIdEl) personIdEl.value = String(editInfo.person_id || '');
            if (doctorIdEl) doctorIdEl.value = String(editInfo.doctor_id || '');
            if (pacienteEl) pacienteEl.value = String(editInfo.paciente || '');
            if (doctorEl) doctorEl.value = String(editInfo.doctor || '');
            if (prioridadEl) prioridadEl.value = String(editInfo.prioridad || '0');
            if (diagnosticoEl) diagnosticoEl.value = String(editInfo.diagnostico_presuntivo || '');
            if (motivoEl) motivoEl.value = String(editInfo.motivo_estudio || '');
            var institucionEl = document.getElementById('customer_institucion');
            var descuentoEl = document.getElementById('customer_descuento_pct');
            if (institucionEl) institucionEl.value = String(editInfo.institucion || '');
            if (descuentoEl) descuentoEl.value = String(editInfo.descuento || 0);

            // Pago
            var p = editInfo.pago || {};
            var totalEl = document.getElementById('total');
            var totalRecoEl = document.getElementById('total_reco');
            var montoEl = document.getElementById('monto_pagar');
            var tipopagoEl = document.getElementById('tipopago');
            var saldoEl = document.getElementById('saldo');
            var comentariosEl = document.getElementById('comentarios');
            if (tipopagoEl) tipopagoEl.value = String(p.tipopago || '');
            if (montoEl) montoEl.value = String(p.monto_pagar ?? '');
            if (comentariosEl) comentariosEl.value = String(p.comentarios ?? '');
            if (totalEl) totalEl.value = String(p.total ?? '');
            if (saldoEl) saldoEl.value = String(p.saldo ?? '');

            // Pruebas
            var pruebasStr = String(editInfo.pruebas || '').trim();
            if (pruebasStr) {
                var hasResults = (parseInt(editInfo.regvalues_count || 0, 10) > 0);
                pruebasStr.split(',').map(function(x) { return String(parseInt(x, 10)); })
                    .filter(function(x) { return x !== 'NaN'; })
                    .forEach(function(id) {
                        var info = (window.PRUEBAS_LOOKUP || {})[id];
                        if (info) {
                            agregarPrueba({
                                value: info.name,
                                padre: info.padre,
                                data: id,
                                cost: info.cost,
                                locked: hasResults
                            });
                            return;
                        }

                        // Conserva IDs históricos (fuera de catálogo) para no perderlos al guardar.
                        agregarPrueba({
                            value: 'Prueba #' + id + ' (no disponible en catálogo)',
                            padre: 'Histórico',
                            data: id,
                            cost: 0,
                            locked: hasResults
                        });
                    });
            }

            // total_reco se recalcula por pruebas (readonly), pero si viene algo y no hay lookup completo, lo mostramos.
            if (totalRecoEl && (totalRecoEl.value === '' || totalRecoEl.value === '0.00')) {
                totalRecoEl.value = String(p.total_reco ?? totalRecoEl.value);
            }
            renderPruebasLista();
            if (typeof window.updateInstitutionDiscountInfo === 'function') {
                window.updateInstitutionDiscountInfo();
            }
        } catch (e) {}
    }

    // Perfil rápido: agregar pruebas del perfil a la lista
    var perfilSel = document.getElementById('perfil_rapido');
    if (perfilSel && typeof window.PRUEBAS_LOOKUP !== 'undefined') {
        perfilSel.addEventListener('change', function() {
            var pruebasStr = this.value;
            if (!pruebasStr) return;
            var ids = pruebasStr.split(',').map(function(x) { return String(parseInt(x, 10)); }).filter(function(x) { return x !== 'NaN'; });
            ids.forEach(function(id) {
                var info = window.PRUEBAS_LOOKUP[id];
                if (info) agregarPrueba({ value: info.name, padre: info.padre, data: id, cost: info.cost });
            });
        });
    }

    // Delegación para botones eliminar
    if (pruebaListaContainer) {
        pruebaListaContainer.addEventListener('click', function(e) {
            var btn = e.target.closest('.quitar-prueba');
            if (btn) quitarPrueba(parseInt(btn.dataset.idx, 10));
        });
    }

    // Autocomplete para búsqueda de pruebas
    var pruebaSearchTimeout;
    if (searchInput) {
        searchInput.addEventListener('focus', function() { this.select(); });
        searchInput.addEventListener('blur', function() {
            setTimeout(function() { if (pruebaListDropdown) pruebaListDropdown.style.display = 'none'; }, 200);
        });
        searchInput.addEventListener('input', function() {
            clearTimeout(pruebaSearchTimeout);
            var q = this.value.trim();
            if (q.length < 2) {
                if (pruebaListDropdown) { pruebaListDropdown.innerHTML = ''; pruebaListDropdown.style.display = 'none'; }
                return;
            }
            pruebaSearchTimeout = setTimeout(function() {
                fetch('<?= site_url('registers/search_prueba') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: 'prueba=' + encodeURIComponent(q)
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!pruebaListDropdown) return;
                    pruebaListDropdown.innerHTML = '';
                    if (!data || !data.length) {
                        pruebaListDropdown.style.display = 'none';
                        return;
                    }
                    data.forEach(function(item) {
                        var li = document.createElement('div');
                        li.className = 'list-group-item list-group-item-action';
                        li.style.cursor = 'pointer';
                        var label = item.value;
                        if (item.padre) label += ' <span class="text-muted small">(' + item.padre + ')</span>';
                        li.innerHTML = label + ' <span class="badge bg-secondary float-end">' + formatCurrencyAmount(item.cost || 0, 0) + '</span>';
                        li.addEventListener('click', function() {
                            agregarPrueba(item);
                            searchInput.value = '';
                            pruebaListDropdown.style.display = 'none';
                        });
                        pruebaListDropdown.appendChild(li);
                    });
                    pruebaListDropdown.style.display = 'block';
                })
                .catch(function() { if (pruebaListDropdown) pruebaListDropdown.style.display = 'none'; });
            }, 300);
        });
    }
    document.addEventListener('click', function(e) {
        var c = document.getElementById('prueba_search_container');
        if (pruebaListDropdown && c && !c.contains(e.target)) pruebaListDropdown.style.display = 'none';
    });
});
</script>
<?= $this->endSection() ?>
