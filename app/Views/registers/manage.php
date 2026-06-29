<?= $this->extend('layouts/main') ?>
<?php
helper('config');
$pageTitle = !empty($edit_registro) ? 'Editar orden' : 'Nuevo registro';
?>
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
    #pruebas_lista .pruebaitem {
        gap: .5rem;
    }
    #pruebas_lista .prueba-drag-handle {
        color: #6c757d;
        cursor: grab;
        flex: 0 0 auto;
        padding: 0 .15rem;
        user-select: none;
    }
    #pruebas_lista .prueba-drag-handle:active {
        cursor: grabbing;
    }
    #pruebas_lista .pruebaitem.prueba-sortable-chosen {
        background: #e7f1ff;
    }
    #pruebas_lista .prueba-sortable-ghost {
        opacity: 0.45;
        background: #cfe2ff;
    }
    #pruebas_lista .btn-ficha-clinica.ficha-con-datos {
        border-color: #198754;
        color: #198754;
    }
    #pruebas_lista .pruebaitem.prueba-ficha-pendiente {
        background: #fff3cd;
        border-left: 3px solid #ffc107;
    }
    #modalFichaClinica .modal-dialog {
        max-width: 96vw;
    }
    #modalFichaClinica .modal-body {
        max-height: calc(90vh - 160px);
        overflow-y: auto;
        overflow-x: auto;
    }
    #modalFichaClinica .note-editor.note-frame {
        width: 100% !important;
        max-width: 100%;
    }
    #modalFichaClinica .note-editing-area,
    #modalFichaClinica .note-editable {
        min-height: 120px;
    }
    .note-popover,
    .note-dropdown-menu,
    .note-modal {
        z-index: 1085 !important;
    }
    .cultivo-fill-texto-fijo p:last-child {
        margin-bottom: 0;
    }
    .cultivo-fill-texto-fijo ul,
    .cultivo-fill-texto-fijo ol {
        margin-bottom: 0.35rem;
        padding-left: 1.25rem;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="<?= base_url('js/vendor/sortable.min.js') ?>"></script>
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
            'cost' => (float)($item['cost'] ?? 0),
            'cost_deriv' => (float)($item['cost_deriv'] ?? 0),
        ];
    }
}
?>
<script>window.PRUEBAS_LOOKUP = <?= json_encode($pruebasLookup, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
<script>window.PRUEBAS_CATEGORIES = <?= json_encode($categories ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
<script>window.FICHA_CLINICA_MAP = <?= json_encode($ficha_clinica_map ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
<script>window.FICHAS_CLINICAS_FILLED = <?= json_encode($fichas_clinicas_filled ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
<script>window.FICHAS_CLINICAS_DATA = <?= json_encode($fichas_clinicas_data ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
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
        'origen_prueba' => (int)($edit_registro->origen_prueba ?? 0),
        'diagnostico_presuntivo' => (string)($edit_registro->diagnostico_presuntivo ?? ''),
        'motivo_estudio' => (string)($edit_registro->motivo_estudio ?? ''),
        'notificar_entrega' => (int)($edit_registro->notificar_entrega ?? 0),
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
        <?= view('registers/form_basic_info', [
            'show_notificar_entrega_checkbox' => ! empty($show_notificar_entrega_checkbox),
            'perfiles' => $perfiles ?? [],
        ]) ?>
        <div id="pruebas_error" class="text-danger small mb-2" style="display:none;"></div>
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <label class="form-label mb-0">Pruebas seleccionadas <span class="text-muted fw-normal small">(arrastre para ordenar)</span>:</label>
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

<div class="modal fade" id="modalFichaClinica" tabindex="-1" aria-labelledby="modalFichaClinicaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFichaClinicaLabel"><i class="fa-solid fa-file-medical me-2"></i>Ficha clínica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="ficha_clinica_alert" class="alert" style="display:none;"></div>
                <div id="ficha_clinica_meta" class="mb-2 small text-muted"></div>
                <div id="ficha_clinica_selector_wrap" class="mb-3" style="display:none;">
                    <label for="ficha_clinica_selector" class="form-label">Ficha clínica</label>
                    <select id="ficha_clinica_selector" class="form-select form-select-sm"></select>
                </div>
                <div id="ficha_clinica_loading" class="text-muted py-3" style="display:none;">Cargando formulario...</div>
                <div id="ficha_clinica_form_wrap"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="btn_guardar_ficha_clinica" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Guardar ficha
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
                                <?php foreach (genero_dropdown_options('Seleccione...') as $generoVal => $generoLabel): ?>
                                    <option value="<?= esc($generoVal) ?>"><?= esc($generoLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="np_institucion" class="form-label">Institución</label>
                            <input type="text" class="form-control" id="np_institucion" name="institucion">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="np_seguro" class="form-label">Seguro</label>
                            <input type="text" class="form-control" id="np_seguro" name="seguro">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="np_address" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="np_address" name="address_1" placeholder="Ej: Av. Principal #123, Zona Centro">
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
                            <label for="nd_phone_number" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="nd_phone_number" name="phone_number">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="nd_gender" class="form-label">Género <span class="text-danger">*</span></label>
                            <select class="form-select" id="nd_gender" name="gender" required>
                                <?php foreach (genero_dropdown_options('Seleccione...') as $generoVal => $generoLabel): ?>
                                    <option value="<?= esc($generoVal) ?>"><?= esc($generoLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="nd_speciality" class="form-label">Especialidad</label>
                            <input type="text" class="form-control" id="nd_speciality" name="speciality">
                        </div>
                        <div class="col-12">
                            <label for="nd_address" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="nd_address" name="address">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="nd_display_mode" class="form-label">Modo de reporte</label>
                            <select id="nd_display_mode" name="display_mode" class="form-select">
                                <option value="clinico">Clínico (texto Bajo/Alto)</option>
                                <option value="neutral">Neutral (sin color)</option>
                                <option value="semaforo">Semáforo suave (colores + iconos)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Mostrar interpretación</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="nd_interpretacion_enabled" name="interpretacion_enabled" value="1" checked>
                                <label class="form-check-label" for="nd_interpretacion_enabled">Mostrar interpretación en reportes</label>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="nd_has_commission" name="has_commission" value="1">
                                <label class="form-check-label" for="nd_has_commission">¿Este doctor usa comisiones?</label>
                            </div>
                        </div>

                        <div id="nd_commission_field" style="display:none;" class="col-12">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nd_commission_percent" class="form-label">Comisión (%)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="100" class="form-control" id="nd_commission_percent" name="commission_percent" placeholder="0.00">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="nd_hide_commission_details" name="hide_commission_details" value="1">
                                        <label class="form-check-label" for="nd_hide_commission_details">Ocultar detalle de comisiones</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-2">
                        <div class="col-12 col-md-4">
                            <label for="nd_username" class="form-label">Usuario (opcional)</label>
                            <input type="text" class="form-control" id="nd_username" name="username" placeholder="Ej: dr.garcia">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="nd_password" class="form-label">Contraseña (opcional)</label>
                            <input type="password" class="form-control" id="nd_password" name="password" placeholder="Dejar vacío para no cambiar">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="nd_email" class="form-label">Correo</label>
                            <input type="email" class="form-control" id="nd_email" name="email" placeholder="Para login con correo">
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
    var guardandoOrden = false;
    function renewSubmitToken() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            submitToken = window.crypto.randomUUID();
        } else {
            submitToken = 'st_' + Date.now() + '_' + Math.random().toString(36).slice(2, 12);
        }
    }
    var submitToken = (function() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'st_' + Date.now() + '_' + Math.random().toString(36).slice(2, 12);
    })();
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
    var pruebasSortable = null;
    var editInfo = (typeof window.EDIT_REGISTRO !== 'undefined') ? window.EDIT_REGISTRO : null;
    if (!editInfo || !editInfo.registro_id) {
        window.addEventListener('pageshow', function(ev) {
            if (ev.persisted) {
                renewSubmitToken();
            }
        });
    }
    var fichaClinicaMap = (typeof window.FICHA_CLINICA_MAP === 'object' && window.FICHA_CLINICA_MAP) ? window.FICHA_CLINICA_MAP : {};
    var fichasClinicasFilledState = (typeof window.FICHAS_CLINICAS_FILLED === 'object' && window.FICHAS_CLINICAS_FILLED) ? window.FICHAS_CLINICAS_FILLED : {};
    var fichasClinicasDraft = {};
    var fichaClinicaModalEl = document.getElementById('modalFichaClinica');
    var fichaClinicaModal = (typeof bootstrap !== 'undefined' && fichaClinicaModalEl) ? bootstrap.Modal.getOrCreateInstance(fichaClinicaModalEl) : null;
    var fichaClinicaFormWrap = document.getElementById('ficha_clinica_form_wrap');
    var fichaClinicaLoading = document.getElementById('ficha_clinica_loading');
    var fichaClinicaSelectorWrap = document.getElementById('ficha_clinica_selector_wrap');
    var fichaClinicaSelector = document.getElementById('ficha_clinica_selector');
    var fichaClinicaMeta = document.getElementById('ficha_clinica_meta');
    var fichaClinicaAlert = document.getElementById('ficha_clinica_alert');
    var btnGuardarFichaClinica = document.getElementById('btn_guardar_ficha_clinica');
    var fichaClinicaContext = { prianacategoriaId: 0, pruebaNombre: '', fichaClinicaId: 0, fichas: [] };

    if (typeof window.FICHAS_CLINICAS_DATA === 'object' && window.FICHAS_CLINICAS_DATA) {
        Object.keys(window.FICHAS_CLINICAS_DATA).forEach(function(priaKey) {
            var row = window.FICHAS_CLINICAS_DATA[priaKey];
            if (!row || !row.has_data) return;
            fichasClinicasDraft[String(priaKey)] = {
                prianacategoria_id: parseInt(priaKey, 10) || parseInt(row.prianacategoria_id, 10) || 0,
                ficha_clinica_id: parseInt(row.ficha_clinica_id, 10) || 0,
                valores: row.valores || {},
                has_data: true
            };
        });
    }

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

    var csrfRefreshUrl = <?= json_encode(site_url('registers/csrfRefresh')) ?>;

    function refreshCsrfToken() {
        return fetch(csrfRefreshUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function(r) {
            return r.json().then(function(data) {
                updateCsrfFromResponse(data);
                if (!r.ok || !data.success) {
                    throw new Error('csrf_refresh_failed');
                }
                return data;
            });
        });
    }

    // Renovar CSRF en segundo plano (cookie expira ~2 h; sesión de trabajo larga en recepción).
    setInterval(function() {
        refreshCsrfToken().catch(function() {});
    }, 45 * 60 * 1000);

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            refreshCsrfToken().catch(function() {});
        }
    });

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

    function esProcesamientoDerivacion() {
        var el = document.getElementById('prioridad');
        return el && String(el.value) === '2';
    }

    function costoParaPrueba(info) {
        if (!info) return 0;
        if (esProcesamientoDerivacion()) {
            return parseFloat(info.cost_deriv != null ? info.cost_deriv : (info.refe || 0));
        }
        return parseFloat(info.cost != null ? info.cost : 0);
    }

    function actualizarPreciosSegunProcesamiento() {
        pruebasSeleccionadas.forEach(function(p) {
            var info = (window.PRUEBAS_LOOKUP || {})[String(p.id)];
            if (info) {
                p.cost = costoParaPrueba(info);
            }
        });
        renderPruebasLista();
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

    function syncPruebasSeleccionadasFromDom() {
        if (!pruebaListaContainer) return;
        var byId = {};
        pruebasSeleccionadas.forEach(function(p) { byId[String(p.id)] = p; });
        var next = [];
        pruebaListaContainer.querySelectorAll('.pruebaitem').forEach(function(row) {
            var id = row.dataset.id;
            if (id && byId[id]) next.push(byId[id]);
        });
        if (next.length === pruebasSeleccionadas.length) {
            pruebasSeleccionadas = next;
        }
    }

    function initPruebasSortable() {
        if (!pruebaListaContainer || typeof Sortable === 'undefined') return;
        if (pruebasSortable) {
            pruebasSortable.destroy();
            pruebasSortable = null;
        }
        if (pruebasSeleccionadas.length < 2) return;
        pruebasSortable = new Sortable(pruebaListaContainer, {
            animation: 150,
            handle: '.prueba-drag-handle',
            ghostClass: 'prueba-sortable-ghost',
            chosenClass: 'prueba-sortable-chosen',
            onEnd: function() {
                syncPruebasSeleccionadasFromDom();
            }
        });
    }

    function pruebaTieneFichaClinica(pruebaId) {
        var key = String(pruebaId);
        var fichas = fichaClinicaMap[key] || fichaClinicaMap[parseInt(pruebaId, 10)];
        return Array.isArray(fichas) && fichas.length > 0;
    }

    function fichaClinicaTieneDatos(pruebaId) {
        var key = String(pruebaId);
        var draft = fichasClinicasDraft[key] || fichasClinicasDraft[parseInt(pruebaId, 10)];
        if (draft && draft.has_data) return true;
        var filled = fichasClinicasFilledState[key] || fichasClinicasFilledState[parseInt(pruebaId, 10)];
        return !!(filled && filled.has_data);
    }

    function getFichaClinicaIdParaPrueba(pruebaId) {
        var key = String(pruebaId);
        var draft = fichasClinicasDraft[key] || fichasClinicasDraft[parseInt(pruebaId, 10)];
        if (draft && draft.ficha_clinica_id) return parseInt(draft.ficha_clinica_id, 10);
        var filled = fichasClinicasFilledState[key] || fichasClinicasFilledState[parseInt(pruebaId, 10)];
        if (filled && filled.ficha_clinica_id) return parseInt(filled.ficha_clinica_id, 10);
        var fichas = fichaClinicaMap[key] || fichaClinicaMap[parseInt(pruebaId, 10)];
        if (Array.isArray(fichas) && fichas[0]) return parseInt(fichas[0].ficha_clinica_id, 10) || 0;
        return 0;
    }

    function destroyFichaClinicaEditors() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.summernote || !fichaClinicaFormWrap) return;
        fichaClinicaFormWrap.querySelectorAll('.input-texto-rico, .input-texto-fijo').forEach(function(el) {
            if (jQuery(el).data('summernote')) {
                jQuery(el).summernote('destroy');
            }
        });
    }

    function initFichaClinicaEditors() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.summernote || !fichaClinicaFormWrap) return;
        fichaClinicaFormWrap.querySelectorAll('.input-texto-rico').forEach(function(el) {
            if (jQuery(el).data('summernote')) return;
            var initialHtml = el.value || el.textContent || '';
            var enPersonalizado = !!el.closest('.cultivo-fill-personalizado');
            jQuery(el).summernote({
                height: enPersonalizado ? 200 : 160,
                width: '100%',
                tooltip: false,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link']],
                    ['view', ['codeview']]
                ],
                callbacks: {
                    onInit: function() {
                        if (initialHtml && initialHtml.trim() !== '') {
                            jQuery(el).summernote('code', initialHtml);
                        }
                    }
                }
            });
        });
        fichaClinicaFormWrap.querySelectorAll('.input-texto-fijo').forEach(function(el) {
            if (jQuery(el).data('summernote')) return;
            var initialHtml = el.value || el.textContent || '';
            jQuery(el).summernote({
                height: 120,
                toolbar: false
            });
            jQuery(el).summernote('disable');
            if (initialHtml && initialHtml.trim() !== '') {
                jQuery(el).summernote('code', initialHtml);
            }
        });
    }

    function refreshFichaClinicaRichEditors() {
        if (!fichaClinicaFormWrap) return;
        initFichaClinicaEditors();
        if (typeof jQuery === 'undefined' || !jQuery.fn.summernote) return;
        fichaClinicaFormWrap.querySelectorAll('.input-texto-rico, .input-texto-fijo').forEach(function(el) {
            if (!jQuery(el).data('summernote')) return;
            var html = el.getAttribute('data-initial-html');
            if (!html) {
                html = el.value || '';
            }
            if (html && html.trim() !== '') {
                jQuery(el).summernote('code', html);
            }
        });
    }

    function syncFichaClinicaEditors() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.summernote || !fichaClinicaFormWrap) return;
        fichaClinicaFormWrap.querySelectorAll('.input-texto-rico, .input-texto-fijo').forEach(function(el) {
            if (jQuery(el).data('summernote')) {
                el.value = jQuery(el).summernote('code');
            }
        });
    }

    function collectFichaClinicaValores() {
        var valores = {};
        if (!fichaClinicaFormWrap) return valores;
        syncFichaClinicaEditors();
        fichaClinicaFormWrap.querySelectorAll('.cultivo-celda-input, .cultivo-celda-valor-fill').forEach(function(el) {
            var id = el.id || '';
            if (!id) return;
            var valor = '';
            if (typeof jQuery !== 'undefined' && jQuery(el).data('summernote')) {
                valor = (jQuery(el).summernote('code') || '').trim();
            } else {
                valor = (el.value || '').trim();
            }
            if (el.classList.contains('input-texto-rico') || el.classList.contains('input-texto-fijo')) {
                valor = valor.replace(/^<p><br><\/p>$/i, '').replace(/^<p><\/p>$/i, '').trim();
            }
            if (valor !== '') valores[id] = valor;
        });
        return valores;
    }

    function applyFichaClinicaDraftValores(valores) {
        if (!fichaClinicaFormWrap || !valores || typeof valores !== 'object') return;
        Object.keys(valores).forEach(function(fieldId) {
            var el = fichaClinicaFormWrap.querySelector('#' + CSS.escape(fieldId));
            if (!el) return;
            var val = valores[fieldId];
            if (typeof jQuery !== 'undefined' && jQuery(el).data('summernote')) {
                jQuery(el).summernote('code', val);
            } else {
                el.value = val;
            }
        });
        fichaClinicaFormWrap.querySelectorAll('.cultivo-leyenda-fill-select').forEach(function(sel) {
            sel.dispatchEvent(new Event('change'));
        });
    }

    function hideFichaClinicaAlert() {
        if (!fichaClinicaAlert) return;
        fichaClinicaAlert.style.display = 'none';
        fichaClinicaAlert.textContent = '';
    }

    function showFichaClinicaAlert(ok, msg) {
        if (!fichaClinicaAlert) return;
        fichaClinicaAlert.className = 'alert alert-' + (ok ? 'success' : 'danger');
        fichaClinicaAlert.textContent = msg || '';
        fichaClinicaAlert.style.display = msg ? 'block' : 'none';
    }

    function loadFichaClinicaForm(priaId, fichaId) {
        if (!fichaClinicaFormWrap || !fichaClinicaLoading) return;
        hideFichaClinicaAlert();
        destroyFichaClinicaEditors();
        fichaClinicaFormWrap.innerHTML = '';
        fichaClinicaLoading.style.display = 'block';
        var registroId = (editInfo && editInfo.registro_id) ? parseInt(editInfo.registro_id, 10) : 0;
        var qs = 'prianacategoria_id=' + encodeURIComponent(String(priaId)) +
            '&ficha_clinica_id=' + encodeURIComponent(String(fichaId || 0)) +
            '&registro_id=' + encodeURIComponent(String(registroId)) +
            '&titulo_prueba=' + encodeURIComponent(String(fichaClinicaContext.pruebaNombre || ''));
        fetch('<?= site_url('registers/fichaClinicaForm') ?>?' + qs, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(res) {
            fichaClinicaLoading.style.display = 'none';
            if (!res.success) {
                showFichaClinicaAlert(false, res.message || 'No se pudo cargar la ficha.');
                return;
            }
            fichaClinicaContext.fichaClinicaId = parseInt(res.ficha_clinica_id, 10) || 0;
            fichaClinicaContext.fichas = Array.isArray(res.fichas) ? res.fichas : [];
            if (fichaClinicaSelector && fichaClinicaSelectorWrap) {
                var fichas = fichaClinicaContext.fichas;
                if (fichas.length > 1) {
                    fichaClinicaSelector.innerHTML = '';
                    fichas.forEach(function(f) {
                        var opt = document.createElement('option');
                        opt.value = String(f.ficha_clinica_id);
                        opt.textContent = f.nombre || ('Ficha #' + f.ficha_clinica_id);
                        if (parseInt(f.ficha_clinica_id, 10) === fichaClinicaContext.fichaClinicaId) opt.selected = true;
                        fichaClinicaSelector.appendChild(opt);
                    });
                    fichaClinicaSelectorWrap.style.display = 'block';
                } else {
                    fichaClinicaSelectorWrap.style.display = 'none';
                }
            }
            fichaClinicaFormWrap.innerHTML = res.html || '';
            requestAnimationFrame(function() {
                refreshFichaClinicaRichEditors();
                var draft = fichasClinicasDraft[String(priaId)] || fichasClinicasDraft[parseInt(priaId, 10)];
                if (draft && draft.valores && Object.keys(draft.valores).length) {
                    applyFichaClinicaDraftValores(draft.valores);
                }
            });
        })
        .catch(function() {
            fichaClinicaLoading.style.display = 'none';
            showFichaClinicaAlert(false, 'Error de red al cargar la ficha.');
        });
    }

    function abrirFichaClinicaModal(pruebaId, pruebaNombre) {
        if (!pruebaTieneFichaClinica(pruebaId)) return;
        fichaClinicaContext.prianacategoriaId = parseInt(pruebaId, 10);
        fichaClinicaContext.pruebaNombre = pruebaNombre || '';
        var fichaId = getFichaClinicaIdParaPrueba(pruebaId);
        if (fichaClinicaMeta) {
            fichaClinicaMeta.textContent = 'Prueba: ' + (pruebaNombre || ('#' + pruebaId));
        }
        hideFichaClinicaAlert();
        loadFichaClinicaForm(pruebaId, fichaId);
        if (fichaClinicaModal) fichaClinicaModal.show();
    }

    function syncFichaModalToDraft() {
        var priaId = fichaClinicaContext.prianacategoriaId;
        var fichaId = fichaClinicaContext.fichaClinicaId;
        if (priaId < 1 || fichaId < 1 || !fichaClinicaFormWrap) return false;
        if (!fichaClinicaFormWrap.querySelector('.cultivo-celda-input, .cultivo-celda-valor-fill')) return false;
        var valores = collectFichaClinicaValores();
        var hasData = Object.keys(valores).length > 0;
        if (!hasData) return false;
        fichasClinicasDraft[String(priaId)] = {
            prianacategoria_id: priaId,
            ficha_clinica_id: fichaId,
            valores: valores,
            has_data: true
        };
        fichasClinicasFilledState[String(priaId)] = { ficha_clinica_id: fichaId, has_data: true };
        return true;
    }

    function guardarFichaClinicaModal() {
        var priaId = fichaClinicaContext.prianacategoriaId;
        var fichaId = fichaClinicaContext.fichaClinicaId;
        if (priaId < 1 || fichaId < 1) return;
        if (!syncFichaModalToDraft()) {
            fichasClinicasDraft[String(priaId)] = {
                prianacategoria_id: priaId,
                ficha_clinica_id: fichaId,
                valores: {},
                has_data: false
            };
            delete fichasClinicasFilledState[String(priaId)];
            renderPruebasLista();
            showFichaClinicaAlert(true, 'Borrador actualizado.');
            return;
        }
        renderPruebasLista();
        var valores = fichasClinicasDraft[String(priaId)].valores || {};
        var hasData = Object.keys(valores).length > 0;
        var registroId = (editInfo && editInfo.registro_id) ? parseInt(editInfo.registro_id, 10) : 0;
        if (registroId < 1) {
            showFichaClinicaAlert(true, hasData ? 'Datos guardados en borrador (se guardarán con la orden).' : 'Borrador actualizado.');
            return;
        }
        var csrf = getCsrfPair();
        var body = 'registro_id=' + encodeURIComponent(String(registroId)) +
            '&prianacategoria_id=' + encodeURIComponent(String(priaId)) +
            '&ficha_clinica_id=' + encodeURIComponent(String(fichaId)) +
            '&valores=' + encodeURIComponent(JSON.stringify(valores));
        if (csrf) body += '&' + encodeURIComponent(csrf.name) + '=' + encodeURIComponent(csrf.value);
        if (btnGuardarFichaClinica) btnGuardarFichaClinica.disabled = true;
        fetch('<?= site_url('registers/saveFichaClinica') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body
        })
        .then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(res) {
            updateCsrfFromResponse(res);
            if (!res.success) {
                showFichaClinicaAlert(false, res.message || 'No se pudo guardar.');
                return;
            }
            showFichaClinicaAlert(true, res.message || 'Ficha clínica guardada.');
        })
        .catch(function() {
            showFichaClinicaAlert(false, 'Error de red al guardar.');
        })
        .finally(function() {
            if (btnGuardarFichaClinica) btnGuardarFichaClinica.disabled = false;
        });
    }

    function buildFichasClinicasPostPayload() {
        var items = [];
        Object.keys(fichasClinicasDraft).forEach(function(key) {
            var d = fichasClinicasDraft[key];
            if (!d || !d.has_data) return;
            var valores = d.valores || {};
            if (!Object.keys(valores).length) return;
            items.push({
                prianacategoria_id: parseInt(d.prianacategoria_id || key, 10),
                ficha_clinica_id: parseInt(d.ficha_clinica_id, 10),
                valores: d.valores || {}
            });
        });
        return items.length ? JSON.stringify(items) : '';
    }

    function getPruebasConFichaSinDatos() {
        var pendientes = [];
        pruebasSeleccionadas.forEach(function(p) {
            if (!pruebaTieneFichaClinica(p.id)) return;
            if (fichaClinicaTieneDatos(p.id)) return;
            var nombre = (p.name || '').trim() || ('Prueba #' + p.id);
            if (p.padre) nombre += ' (' + p.padre + ')';
            pendientes.push({ id: String(p.id), nombre: nombre });
        });
        return pendientes;
    }

    function marcarPruebasFichaPendiente(pendientes) {
        if (!pruebaListaContainer) return;
        pruebaListaContainer.querySelectorAll('.pruebaitem').forEach(function(row) {
            row.classList.remove('prueba-ficha-pendiente');
        });
        var ids = {};
        (pendientes || []).forEach(function(p) { ids[String(p.id)] = true; });
        pruebaListaContainer.querySelectorAll('.pruebaitem').forEach(function(row) {
            if (ids[String(row.dataset.id || '')]) {
                row.classList.add('prueba-ficha-pendiente');
            }
        });
        var pe = document.getElementById('pruebas_error');
        if (pe && pendientes && pendientes.length) {
            pe.textContent = 'Complete la ficha clínica de las pruebas marcadas (icono de ficha) antes de guardar, o confirme para continuar sin esos datos.';
            pe.className = 'text-warning small mb-2';
            pe.style.display = 'block';
            pe.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function limpiarAvisoFichasPendientes() {
        if (!pruebaListaContainer) return;
        pruebaListaContainer.querySelectorAll('.prueba-ficha-pendiente').forEach(function(row) {
            row.classList.remove('prueba-ficha-pendiente');
        });
        if (getPruebasConFichaSinDatos().length === 0) {
            var pe = document.getElementById('pruebas_error');
            if (pe && pe.classList.contains('text-warning')) {
                pe.textContent = '';
                pe.style.display = 'none';
            }
        }
    }

    function confirmarGuardarConFichasPendientes(pendientes) {
        if (!pendientes || pendientes.length === 0) {
            return Promise.resolve(true);
        }
        var lista = pendientes.map(function(p) { return '• ' + p.nombre; }).join('\n');
        var msg = 'Las siguientes pruebas tienen ficha clínica sin completar:\n\n' + lista +
            '\n\n¿Desea guardar la orden de todos modos?';
        if (typeof uiConfirm === 'function') {
            return uiConfirm(msg, 'Fichas clínicas pendientes');
        }
        return Promise.resolve(window.confirm(msg));
    }

    function ejecutarGuardarOrden(registroData, pagosData, esNuevaOrden) {
        var urlGuardar = editInfo && editInfo.registro_id ? ('<?= site_url('registers/update') ?>/' + editInfo.registro_id) : '<?= site_url('registers/save') ?>';
        guardandoOrden = true;
        guardarBtn.disabled = true;
        var guardarBtnLabel = guardarBtn.textContent;
        guardarBtn.textContent = 'Guardando...';

        function buildSaveBody() {
            var csrf = getCsrfPair();
            var csrfSuffix = csrf ? ('&' + encodeURIComponent(csrf.name) + '=' + encodeURIComponent(csrf.value)) : '';
            return 'registro[person_id]=' + encodeURIComponent(registroData.person_id) +
                '&registro[doctor_id]=' + encodeURIComponent(registroData.doctor_id) +
                '&registro[pruebas]=' + encodeURIComponent(registroData.pruebas) +
                '&registro[prioridad]=' + encodeURIComponent(registroData.prioridad) +
                '&registro[origen_prueba]=' + encodeURIComponent(registroData.origen_prueba) +
                '&registro[diagnostico_presuntivo]=' + encodeURIComponent(registroData.diagnostico_presuntivo) +
                '&registro[motivo_estudio]=' + encodeURIComponent(registroData.motivo_estudio) +
                '&registro[notificar_entrega]=' + (registroData.notificar_entrega === '1' ? '1' : '0') +
                '&pagos[total_reco]=' + encodeURIComponent(pagosData.total_reco) +
                '&pagos[total]=' + encodeURIComponent(pagosData.total) +
                '&pagos[monto_pagar]=' + encodeURIComponent(pagosData.monto_pagar) +
                '&pagos[tipopago]=' + encodeURIComponent(pagosData.tipopago) +
                '&pagos[saldo]=' + encodeURIComponent(pagosData.saldo) +
                '&pagos[comentarios]=' + encodeURIComponent(pagosData.comentarios) +
                (function() {
                    var fc = buildFichasClinicasPostPayload();
                    return fc ? ('&fichas_clinicas=' + encodeURIComponent(fc)) : '';
                })() +
                (esNuevaOrden ? ('&submit_token=' + encodeURIComponent(submitToken)) : '') + csrfSuffix;
        }

        function resetGuardarBtn() {
            guardandoOrden = false;
            guardarBtn.disabled = false;
            guardarBtn.textContent = guardarBtnLabel;
        }

        function showGuardarError(message) {
            var errDiv = document.getElementById('registers_form_error');
            if (errDiv) {
                errDiv.textContent = message || 'Error al guardar.';
                errDiv.className = 'alert alert-danger';
                errDiv.style.display = 'block';
                errDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        refreshCsrfToken()
        .then(function() {
            return fetch(urlGuardar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: buildSaveBody()
            });
        })
        .then(function(r) {
            return r.text().then(function(text) {
                var res = null;
                try {
                    res = text ? JSON.parse(text) : null;
                } catch (e) {
                    if (r.status === 403) {
                        throw new Error('csrf_expired');
                    }
                    throw new Error('invalid_response');
                }
                updateCsrfFromResponse(res);
                if (!r.ok || !res || !res.success) {
                    if (r.status === 403) {
                        throw new Error('csrf_expired');
                    }
                    var msg = (res && res.message) ? res.message : 'Error al guardar.';
                    throw new Error(msg);
                }
                return res;
            });
        })
        .then(function(res) {
            if (esNuevaOrden) {
                window.location.href = '<?= site_url('registers/viewcomprobante') ?>/' + res.id + '?nuevo=1';
            } else {
                window.location.href = '<?= site_url('registers/view') ?>/' + res.id;
            }
        })
        .catch(function(err) {
            resetGuardarBtn();
            if (err && err.message === 'csrf_expired') {
                showGuardarError('La sesión de seguridad expiró. Espere un momento y pulse Guardar de nuevo, o recargue la página (F5).');
                refreshCsrfToken().catch(function() {});
                return;
            }
            showGuardarError(err && err.message ? err.message : 'Error en la petición.');
        });
    }

    function renderPruebasLista() {
        if (!pruebaListaContainer) return;
        if (pruebasSortable) {
            pruebasSortable.destroy();
            pruebasSortable = null;
        }
        pruebaListaContainer.innerHTML = '';
        if (pruebasSeleccionadas.length === 0) {
            pruebaListaContainer.innerHTML = '<p class="text-muted small mb-0">Use el buscador para agregar pruebas. La lista aparecerá aquí.</p>';
        } else {
            pruebasSeleccionadas.forEach(function(p) {
                var row = document.createElement('div');
                row.className = 'd-flex align-items-center justify-content-between py-2 border-bottom pruebaitem';
                row.dataset.id = String(p.id);
                var displayName = (p.name || '');
                if (p.padre) displayName += ' <span class="text-muted small">(' + p.padre + ')</span>';
                var fichaBtn = '';
                if (pruebaTieneFichaClinica(p.id)) {
                    var fichaFilled = fichaClinicaTieneDatos(p.id);
                    var fichaClass = 'btn-outline-info btn-ficha-clinica' + (fichaFilled ? ' ficha-con-datos' : '');
                    var fichaTitle = fichaFilled ? 'Ficha clínica (con datos)' : 'Completar ficha clínica';
                    fichaBtn = '<button type="button" class="btn ' + fichaClass + ' btn-sm me-1" data-ficha-id="' + String(p.id) + '" data-ficha-name="' + String(p.name || '').replace(/"/g, '&quot;') + '" title="' + fichaTitle + '"><i class="fa-solid fa-file-medical"></i></button>';
                }
                var removeButton = '<button type="button" class="btn btn-outline-danger btn-sm quitar-prueba" data-id="' + String(p.id) + '" title="Eliminar"><i class="fa-solid fa-times"></i></button>';
                row.innerHTML = '<span class="prueba-drag-handle" title="Arrastrar para reordenar"><i class="fa-solid fa-grip-vertical"></i></span>' +
                    '<span class="flex-grow-1">' + displayName + '</span>' +
                    '<span class="badge bg-secondary me-2">' + formatCurrencyAmount(p.cost || 0, 0) + '</span>' +
                    fichaBtn +
                    removeButton;
                pruebaListaContainer.appendChild(row);
            });
            initPruebasSortable();
        }
        limpiarAvisoFichasPendientes();
        recalcular();
    }

    function agregarPrueba(item) {
        if (!item || !item.data) return;
        if (pruebasSeleccionadas.some(function(p) { return String(p.id) === String(item.data); })) return;
        pruebasSeleccionadas.push({
            id: item.data,
            name: item.value || '',
            padre: item.padre || '',
            cost: costoParaPrueba({
                cost: item.cost,
                cost_deriv: item.cost_deriv != null ? item.cost_deriv : item.refe
            })
        });
        renderPruebasLista();
    }

    function quitarPrueba(idx) {
        var removed = pruebasSeleccionadas[idx];
        if (removed && removed.id) {
            delete fichasClinicasDraft[String(removed.id)];
            delete fichasClinicasDraft[parseInt(removed.id, 10)];
            delete fichasClinicasFilledState[String(removed.id)];
            delete fichasClinicasFilledState[parseInt(removed.id, 10)];
        }
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
                chk.dataset.costDeriv = item.cost_deriv || 0;
                var label = document.createElement('label');
                label.className = 'form-check-label flex-grow-1 lh-sm';
                label.htmlFor = checkId;
                label.textContent = item.name || ('Prueba #' + itemId);
                var badge = document.createElement('span');
                badge.className = 'badge bg-secondary';
                badge.textContent = formatCurrencyAmount(costoParaPrueba(item), 0);
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
                    cost: info.cost || 0,
                    cost_deriv: info.cost_deriv || 0
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
            if (guardandoOrden) {
                return;
            }
            quitarInvalid();
            var pruebas = pruebasSeleccionadas.map(function(p) { return p.id; });
            var pruebasStr = pruebas.join(',');
            var procVal = (document.getElementById('prioridad') && document.getElementById('prioridad').value) || '0';
            var registroData = {
                person_id: (document.getElementById('person_id') || {}).value || '',
                doctor_id: (document.getElementById('doctor_id') || {}).value || '',
                pruebas: pruebasStr,
                prioridad: procVal === '1' ? '1' : '0',
                origen_prueba: procVal === '2' ? '1' : '0',
                diagnostico_presuntivo: (document.getElementById('diagnostico_presuntivo') || {}).value || '',
                motivo_estudio: (document.getElementById('motivo_estudio') || {}).value || '',
                notificar_entrega: (function () {
                    var el = document.getElementById('notificar_entrega');
                    return el && el.checked ? '1' : '0';
                })()
            };
            var tipopagoVal = (document.getElementById('tipopago') || {}).value || '';
            var esPendiente = tipopagoVal === '4';
            var totalNum = parseFloat((document.getElementById('total') || {}).value || 0);
            var saldoEl = document.getElementById('saldo');
            if (esPendiente) {
                if (saldoEl) saldoEl.value = (!isNaN(totalNum) ? (totalNum - 0).toFixed(2) : '');
            }
            var pagosData = {
                total_reco: (document.getElementById('total_reco') || {}).value || '',
                total: (document.getElementById('total') || {}).value || '',
                monto_pagar: esPendiente ? '' : ((document.getElementById('monto_pagar') || {}).value || ''),
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
            } else if (parseFloat(pagosData.total) <= 0 && parseFloat(pagosData.total_reco) > 0) {
                var totZero = document.getElementById('total');
                mostrarError(totZero, 'El total debe ser mayor a 0 cuando hay pruebas con costo.'); primero = primero || totZero;
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
            syncFichaModalToDraft();
            var esNuevaOrden = !(editInfo && editInfo.registro_id);
            var pendientesFicha = getPruebasConFichaSinDatos();
            confirmarGuardarConFichasPendientes(pendientesFicha).then(function(ok) {
                if (!ok) {
                    marcarPruebasFichaPendiente(pendientesFicha);
                    return;
                }
                limpiarAvisoFichasPendientes();
                ejecutarGuardarOrden(registroData, pagosData, esNuevaOrden);
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

        // Toggle comisión field en modal de crear doctor
        (function() {
            var ndHas = document.getElementById('nd_has_commission');
            var ndField = document.getElementById('nd_commission_field');
            if (ndHas && ndField) {
                ndHas.addEventListener('change', function() {
                    if (this.checked) {
                        ndField.style.display = '';
                    } else {
                        ndField.style.display = 'none';
                        var pct = document.getElementById('nd_commission_percent');
                        var hide = document.getElementById('nd_hide_commission_details');
                        if (pct) pct.value = '';
                        if (hide) hide.checked = false;
                    }
                });
            }
        })();

        doctorValidator = window.jQuery('#form_crear_doctor').validate(window.jQuery.extend(true, {}, window.VALIDATE_COMMON_OPTIONS || {}, {
            rules: {
                name: { required: true, minlength: 2 },
                phone_number: { maxlength: 50 },
                gender: { required: true },
                speciality: { maxlength: 255 },
                address: { maxlength: 255 },
                commission_percent: { number: true, min: 0, max: 100 }
            },
            messages: {
                name: { required: 'Por favor ingrese nombre(s) y apellido(s)', minlength: 'El nombre debe tener al menos 2 caracteres' },
                gender: { required: 'Seleccione su género' },
                commission_percent: { number: 'Ingrese un número válido', min: 'La comisión no puede ser negativa', max: 'La comisión no puede ser mayor a 100%' }
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
            if (prioridadEl) {
                var procEdit = '0';
                if (parseInt(editInfo.origen_prueba, 10) === 1) {
                    procEdit = '2';
                } else if (parseInt(editInfo.prioridad, 10) === 1) {
                    procEdit = '1';
                }
                prioridadEl.value = procEdit;
            }
            if (diagnosticoEl) diagnosticoEl.value = String(editInfo.diagnostico_presuntivo || '');
            if (motivoEl) motivoEl.value = String(editInfo.motivo_estudio || '');
            var notificarEntregaEl = document.getElementById('notificar_entrega');
            if (notificarEntregaEl) {
                notificarEntregaEl.checked = parseInt(editInfo.notificar_entrega, 10) === 1;
            }
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
                                cost_deriv: info.cost_deriv
                            });
                            return;
                        }

                        // Conserva IDs históricos (fuera de catálogo) para no perderlos al guardar.
                        agregarPrueba({
                            value: 'Prueba #' + id + ' (no disponible en catálogo)',
                            padre: 'Histórico',
                            data: id,
                            cost: 0
                        });
                    });
            }

            // total_reco se recalcula por pruebas (readonly), pero si viene algo y no hay lookup completo, lo mostramos.
            if (totalRecoEl && (totalRecoEl.value === '' || totalRecoEl.value === '0.00')) {
                totalRecoEl.value = String(p.total_reco ?? totalRecoEl.value);
            }
            renderPruebasLista();
            if (tipopagoEl) tipopagoEl.dispatchEvent(new Event('change'));
            if (typeof window.updateInstitutionDiscountInfo === 'function') {
                window.updateInstitutionDiscountInfo();
            }
        } catch (e) {}
    }

    var prioridadSelect = document.getElementById('prioridad');
    if (prioridadSelect) {
        prioridadSelect.addEventListener('change', function() {
            actualizarPreciosSegunProcesamiento();
            if (modalPruebasLista && modalPruebasLista.children.length) {
                renderModalPruebasLista(modalPruebasSearch ? modalPruebasSearch.value : '');
            }
        });
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
                if (info) agregarPrueba({ value: info.name, padre: info.padre, data: id, cost: info.cost, cost_deriv: info.cost_deriv });
            });
        });
    }

    // Delegación para botones eliminar y ficha clínica
    if (pruebaListaContainer) {
        pruebaListaContainer.addEventListener('click', function(e) {
            var fichaBtn = e.target.closest('.btn-ficha-clinica');
            if (fichaBtn) {
                abrirFichaClinicaModal(fichaBtn.getAttribute('data-ficha-id'), fichaBtn.getAttribute('data-ficha-name'));
                return;
            }
            var btn = e.target.closest('.quitar-prueba');
            if (!btn) return;
            var id = btn.getAttribute('data-id');
            var idx = pruebasSeleccionadas.findIndex(function(p) { return String(p.id) === String(id); });
            if (idx >= 0) quitarPrueba(idx);
        });
    }

    if (fichaClinicaSelector) {
        fichaClinicaSelector.addEventListener('change', function() {
            var fichaId = parseInt(fichaClinicaSelector.value, 10);
            if (fichaId > 0 && fichaClinicaContext.prianacategoriaId > 0) {
                loadFichaClinicaForm(fichaClinicaContext.prianacategoriaId, fichaId);
            }
        });
    }

    if (btnGuardarFichaClinica) {
        btnGuardarFichaClinica.addEventListener('click', guardarFichaClinicaModal);
    }

    if (fichaClinicaModalEl) {
        fichaClinicaModalEl.addEventListener('shown.bs.modal', function() {
            refreshFichaClinicaRichEditors();
        });
        fichaClinicaModalEl.addEventListener('hidden.bs.modal', function() {
            syncFichaModalToDraft();
            renderPruebasLista();
            destroyFichaClinicaEditors();
            if (fichaClinicaFormWrap) fichaClinicaFormWrap.innerHTML = '';
            hideFichaClinicaAlert();
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
                        var precioItem = costoParaPrueba({ cost: item.cost, cost_deriv: item.refe });
                        li.innerHTML = label + ' <span class="badge bg-secondary float-end">' + formatCurrencyAmount(precioItem, 0) + '</span>';
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
