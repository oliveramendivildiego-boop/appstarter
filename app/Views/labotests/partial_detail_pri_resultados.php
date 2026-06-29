<?php
/**
 * Partial: Valores de referencia (tipo de análisis simple).
 * Variables: labotests_info, poblaciones, priresultados, formulas, opciones,
 *            editar_pri, editar_pri_data, formulas_id_canonical, formulas_creadas
 */
$pobMap = [];
foreach ($poblaciones ?? [] as $p) {
    $pobMap[(int) $p['id_poblacion']] = $p['name'] ?? '';
}
if (! function_exists('referencia_sexo_dropdown_options')) {
    helper('config');
}
$referencia_sexo_options = $referencia_sexo_options ?? referencia_sexo_dropdown_options();
?>
<div class="card mt-3" id="card_pri_resultados">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <strong>Valores de referencia (<?= lang('Labotests.labotests_tipo_analisis_simple') ?>)</strong>
        <?php if (! empty($priresultados)): ?>
        <span class="badge bg-secondary" id="badge_pri_filas_count"><?= count($priresultados) ?> filas</span>
        <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="btn_abrir_modal_orden_pri" title="Lista compacta para reordenar más rápido">
            <i class="fa-solid fa-list-ol me-1"></i> Orden rápido
        </button>
        <?= view('labotests/partial_modal_orden_criterios', [
            'modal_id'           => 'modalOrdenCriteriosPri',
            'btn_open_id'        => 'btn_abrir_modal_orden_criterios_pri',
            'btn_apply_id'       => 'btn_aplicar_orden_criterios_pri',
            'include_nombre'     => false,
            'sort_url'           => site_url('labotests/sortpriresultadosbycriteria'),
            'prianacategoria_id' => (int) ($labotests_info->prianacategoria_id ?? 0),
        ]) ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_duplicar_pri_seleccionadas" disabled>
            <i class="fa-solid fa-copy me-1"></i>Duplicar seleccionadas
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_pri_por_generos" disabled title="Crea una fila por cada género del catálogo a partir de las filas seleccionadas">
            <i class="fa-solid fa-venus-mars me-1"></i>Pruebas por géneros
        </button>
        <div class="dropdown">
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="btn_formato_nombres_pri">
                <i class="fa-solid fa-font me-1"></i> Formato nombres
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Aplicar a todas las unidades de medida de esta prueba</h6></li>
                <li>
                    <button type="button" class="dropdown-item pri-transform-umedida-action" data-mode="uppercase">
                        <i class="fa-solid fa-text-height me-2 text-muted"></i> TODO EN MAYÚSCULAS
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item pri-transform-umedida-action" data-mode="lowercase">
                        <i class="fa-solid fa-text-height me-2 text-muted"></i> todo en minúsculas
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item pri-transform-umedida-action" data-mode="sentence">
                        <i class="fa-solid fa-a me-2 text-muted"></i> Solo la primera letra en mayúscula
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item pri-transform-umedida-action" data-mode="title">
                        <i class="fa-solid fa-heading me-2 text-muted"></i> Primera letra de cada palabra
                    </button>
                </li>
            </ul>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" id="btn_eliminar_pri_seleccionadas" disabled>
            <i class="fa-solid fa-trash me-1"></i>Eliminar seleccionadas
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (! empty($priresultados)): ?>
        <script src="<?= base_url('js/vendor/sortable.min.js') ?>"></script>
        <?php endif; ?>
        <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped table-hover align-middle mb-0" id="tabla_pri_resultados">
            <thead class="table-light">
                <tr>
                    <?php if (! empty($priresultados)): ?>
                    <th class="text-center">
                        <input type="checkbox" id="pri_check_all" title="Seleccionar todas">
                    </th>
                    <?php endif; ?>
                    <th>Población</th>
                    <th>Sexo</th>
                    <th>Valor mín</th>
                    <th>Valor máx</th>
                    <th>U. medida</th>
                    <th class="text-center">No mostrar Medida</th>
                    <th>Fórmula</th>
                    <th>Tipo</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($priresultados ?? [] as $pr): ?>
                <?php
                $rowDataPri = [
                    'priresultados_id' => (int) ($pr['priresultados_id'] ?? 0),
                    'id_poblacion' => (int) ($pr['id_poblacion'] ?? 15),
                    'sexo' => $pr['sexo'] ?? 'ambos',
                    'valor_min' => $pr['valor_min'] ?? '',
                    'valor_max' => $pr['valor_max'] ?? '',
                    'umedida' => $pr['umedida'] ?? '',
                    'mostrar_medida' => (int) ($pr['mostrar_medida'] ?? 0),
                    'formulas_id' => (int) ($pr['formulas_id'] ?? 1),
                    'opcion_id' => (int) ($pr['opcion_id'] ?? 3),
                    'texto_fijo' => (string) ($pr['texto_fijo'] ?? ''),
                ];
                $etiquetaOrden = trim(($pobMap[(int) ($pr['id_poblacion'] ?? 0)] ?? '') . ' · ' . referencia_sexo_label($pr['sexo'] ?? 'ambos'));
                ?>
                <tr data-priresultados-id="<?= (int) ($pr['priresultados_id'] ?? 0) ?>" data-orden-etiqueta="<?= esc($etiquetaOrden, 'attr') ?>" data-sexo="<?= esc(referencia_sexo_css_slug($pr['sexo'] ?? 'ambos'), 'attr') ?>">
                    <?php if (! empty($priresultados)): ?>
                    <td class="text-center">
                        <input type="checkbox" class="pri-check-item" value="<?= (int) ($pr['priresultados_id'] ?? 0) ?>">
                    </td>
                    <?php endif; ?>
                    <td><?= esc($pobMap[(int) ($pr['id_poblacion'] ?? 0)] ?? $pr['id_poblacion'] ?? '') ?></td>
                    <td><span class="<?= esc(referencia_sexo_badge_class($pr['sexo'] ?? 'ambos'), 'attr') ?>"><?= esc(referencia_sexo_label($pr['sexo'] ?? 'ambos')) ?></span></td>
                    <td><?= esc($pr['valor_min'] ?? '') ?></td>
                    <td><?= esc($pr['valor_max'] ?? '') ?></td>
                    <td><?= esc($pr['umedida'] ?? '') ?></td>
                    <td class="text-center"><?= ((int) ($pr['mostrar_medida'] ?? 0) === 1) ? 'Sí' : 'No' ?></td>
                    <td><?= esc($formulas[(int) ($pr['formulas_id'] ?? 0)] ?? '') ?></td>
                    <td><?= esc($opciones[(int) ($pr['opcion_id'] ?? 0)] ?? '') ?></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar-pri" data-pri="<?= htmlspecialchars(json_encode($rowDataPri), ENT_QUOTES, 'UTF-8') ?>" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-pri" data-pri-id="<?= (int) ($pr['priresultados_id'] ?? 0) ?>" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <?php if (! empty($priresultados)): ?>
        <div class="modal fade" id="modalOrdenPriResultados" tabindex="-1" aria-labelledby="modalOrdenPriResultadosTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalOrdenPriResultadosTitle">Orden rápido</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2">Lista compacta: arrastra el asa <i class="fa-solid fa-grip-vertical text-secondary"></i> para mover filas. <strong>Aplicar y guardar</strong> actualiza la tabla y el servidor.</p>
                        <ul class="list-group mt-3 lista-orden-pri-modal" id="listaOrdenPriResultados" style="max-height: 62vh; overflow-y: auto;"></ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btn_aplicar_orden_pri_modal">Aplicar y guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalDuplicarPriResultado" tabindex="-1" aria-labelledby="modalDuplicarPriResultadoTitle" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalDuplicarPriResultadoTitle">Duplicar filas seleccionadas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Indique cuántas copias desea crear por cada fila seleccionada.</p>
                        <label for="duplicar_pri_copias" class="form-label">Cantidad de copias</label>
                        <input type="number" id="duplicar_pri_copias" class="form-control" min="1" max="100" step="1" value="1" required>
                        <small class="text-muted">Valor por defecto: 1</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btn_confirmar_duplicar_pri">Duplicar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <hr>
        <button type="button" class="btn btn-primary btn-sm" id="btn_agregar_pri">
            <?= empty($priresultados) ? 'Agregar valores de referencia' : 'Agregar por población' ?>
        </button>

        <div class="modal fade" id="modalEditarPri" tabindex="-1" aria-labelledby="modalEditarPriTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarPriTitle">Agregar valores de referencia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <?= form_open('labotests/savepriresultado', ['id' => 'form_priresultado', 'class' => 'border p-3 rounded']) ?>
                        <input type="hidden" name="prianacategoria_id" value="<?= (int) ($labotests_info->prianacategoria_id ?? 0) ?>">
                        <input type="hidden" name="priresultados_id" id="priresultados_id_input" value="0">
                        <input type="hidden" name="formulas_id" id="pri_formulas_id_hidden" value="1">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Población</label>
                                <select name="id_poblacion" class="form-control form-control-sm">
                                    <?php foreach ($poblaciones ?? [] as $p): ?>
                                    <option value="<?= (int) $p['id_poblacion'] ?>"><?= esc($p['name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Sexo</label>
                                <select name="sexo" class="form-control form-control-sm">
                                    <?php foreach ($referencia_sexo_options as $sexoVal => $sexoLbl): ?>
                                    <option value="<?= esc($sexoVal) ?>"><?= esc($sexoLbl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Valor mín</label>
                                <input type="text" name="valor_min" class="form-control form-control-sm" value="">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Valor máx</label>
                                <input type="text" name="valor_max" class="form-control form-control-sm" value="">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">U. medida</label>
                                <input type="text" name="umedida" class="form-control form-control-sm" value="">
                            </div>
                            <div class="col-md-3 mb-2 d-flex align-items-end">
                                <input type="hidden" name="mostrar_medida" value="0">
                                <div class="form-check">
                                    <input type="checkbox" name="mostrar_medida" value="1" id="pri_mostrar_medida" class="form-check-input">
                                    <label class="form-check-label" for="pri_mostrar_medida">No mostrar Medida</label>
                                </div>
                                <small class="text-muted ms-2">Sin unidad en resultado; solo en rango referencial</small>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label">¿Calculada?</label>
                                <div class="form-check mt-1">
                                    <input type="checkbox" id="pri_es_calculada" class="form-check-input">
                                    <label class="form-check-label" for="pri_es_calculada">Sí</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2" id="wrap_pri_formulas_id">
                                <label class="form-label">Fórmula predefinida</label>
                                <select id="pri_formulas_id" class="form-control form-control-sm">
                                    <?php foreach ($formulas_creadas ?? $formulas ?? [] as $fid => $fname): ?>
                                    <option value="<?= (int) $fid ?>"><?= esc($fname) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Fórmulas creadas (sin duplicados)</small>
                            </div>
                            <div class="col-md-4 mb-2 d-none" id="wrap_pri_formula_predefinida_creadas">
                                <label class="form-label">Fórmula predefinida (calculadas)</label>
                                <select id="pri_formula_predefinida_select" class="form-control form-control-sm">
                                    <option value="">— Ninguno —</option>
                                    <option value="__NUEVA__">— Nueva fórmula —</option>
                                    <?php foreach ($formulas_con_expresion_deduped ?? $formulas_con_expresion ?? [] as $f): ?>
                                    <option value="<?= esc($f['formula_expresion'] ?? '') ?>" data-formulas-id="<?= (int) ($f['formulas_id'] ?? 0) ?>" data-nombre="<?= esc($f['nombre'] ?? '') ?>">
                                        <?= esc($f['nombre'] ?? '') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Seleccione una fórmula calculada ya creada</small>
                            </div>
                            <div class="col-12 mb-2 d-none" id="wrap_pri_formula_expresion">
                                <label class="form-label">Constructor de fórmula</label>
                                <input type="hidden" id="pri_formula_expresion" value="">
                                <div class="border rounded p-2 mb-2 bg-light formula-constructor-box">
                                    <small class="text-muted d-block mb-1">Referencias (clic para insertar):</small>
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        <span class="badge bg-secondary pri-formula-ref" data-nombre="valor">valor</span>
                                    </div>
                                    <small class="text-muted d-block mb-1">Nombre de la fórmula:</small>
                                    <input type="text" id="pri_formula_nombre_input" class="form-control form-control-sm mb-2" placeholder="Ej: Formula calculada general">
                                </div>
                                <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
                                    <button type="button" id="btn_guardar_formula_pri" class="btn btn-outline-success btn-sm">Guardar / Actualizar fórmula</button>
                                    <button type="button" id="btn_eliminar_formula_pri" class="btn btn-outline-danger btn-sm">Eliminar fórmula</button>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mb-2 align-items-center">
                                    <span class="text-muted formula-op-label">Operadores:</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm pri-formula-op" data-op="+">+</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm pri-formula-op" data-op="-">−</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm pri-formula-op" data-op="*">×</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm pri-formula-op" data-op="/">/</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm pri-formula-op" data-op="(">(</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm pri-formula-op" data-op=")">)</button>
                                </div>
                                <div class="border rounded p-2 bg-white mb-2 formula-area-box">
                                    <small class="text-muted d-block mb-1">Fórmula (use `[valor]` y operadores):</small>
                                    <textarea id="pri_formula_area" class="form-control formula-area" rows="2" placeholder="Ej: ([valor] * 100) / 2"></textarea>
                                </div>
                                <div class="border rounded p-3 bg-light formula-math-preview">
                                    <small class="text-muted d-block mb-2">Vista previa matemática:</small>
                                    <div id="pri_formula_math_display" class="fs-4 text-center py-2 formula-math-display">—</div>
                                </div>
                                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css"/>
                                <script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Tipo resultado</label>
                                <select name="opcion_id" id="pri_opcion_id" class="form-control form-control-sm">
                                    <?php foreach ($opciones ?? [] as $oid => $oname): ?>
                                    <option value="<?= $oid ?>"><?= esc($oname) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mb-2 d-none" id="wrap_pri_texto_fijo">
                                <label class="form-label">Texto fijo</label>
                                <textarea name="texto_fijo" id="pri_texto_fijo" class="form-control form-control-sm input-texto-fijo-config" rows="4" placeholder="Escriba el texto con negrita, cursiva, listas…"></textarea>
                                <small class="text-muted">Use la barra de herramientas para negrita, cursiva, subrayado y listas. Este texto aparecerá predefinido al capturar resultados y en el reporte.</small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary btn-sm" id="btn_submit_pri">Agregar</button>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                        <?= form_close() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
#tabla_pri_resultados tbody tr.pri-fila-eliminandose {
    opacity: 0;
    transition: opacity 0.42s ease;
    pointer-events: none;
}
</style>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
(function() {
    var textoFijoOpcionId = <?= (int) (model(\App\Models\OpcionModel::class)->getTextoFijoOpcionId()) ?>;
    var modalEl = document.getElementById('modalEditarPri');
    if (!modalEl) return;
    var modal = (typeof bootstrap !== 'undefined') ? new bootstrap.Modal(modalEl) : null;
    var modalTitle = document.getElementById('modalEditarPriTitle');
    var btnSubmit = document.getElementById('btn_submit_pri');
    var form = document.getElementById('form_priresultado');
    var calcCb = document.getElementById('pri_es_calculada');
    var mostrarMedidaCb = document.getElementById('pri_mostrar_medida');
    var formulasHidden = document.getElementById('pri_formulas_id_hidden');
    var formulasSelect = document.getElementById('pri_formulas_id');
    var predefSelect = document.getElementById('pri_formula_predefinida_select');
    var wrapNormal = document.getElementById('wrap_pri_formulas_id');
    var wrapPredef = document.getElementById('wrap_pri_formula_predefinida_creadas');
    var wrapFormula = document.getElementById('wrap_pri_formula_expresion');
    var formulaArea = document.getElementById('pri_formula_area');
    var formulaInput = document.getElementById('pri_formula_expresion');
    var formulaNombreInput = document.getElementById('pri_formula_nombre_input');
    var mathDisplay = document.getElementById('pri_formula_math_display');
    var inputId = document.getElementById('priresultados_id_input');
    var prianacategoriaId = <?= (int) ($labotests_info->prianacategoria_id ?? 0) ?>;
    var tablaPri = document.getElementById('tabla_pri_resultados');
    var priCheckAll = document.getElementById('pri_check_all');
    var btnPriPorGeneros = document.getElementById('btn_pri_por_generos');
    var btnDuplicarPriSeleccionadas = document.getElementById('btn_duplicar_pri_seleccionadas');
    var btnEliminarPriSeleccionadas = document.getElementById('btn_eliminar_pri_seleccionadas');
    var btnFormatoNombresPri = document.getElementById('btn_formato_nombres_pri');
    var modalDuplicarPriEl = document.getElementById('modalDuplicarPriResultado');
    var modalDuplicarPriInst = (modalDuplicarPriEl && typeof bootstrap !== 'undefined')
        ? (bootstrap.Modal.getInstance(modalDuplicarPriEl) || new bootstrap.Modal(modalDuplicarPriEl))
        : null;
    var duplicarPriCopiasInput = document.getElementById('duplicar_pri_copias');
    var btnConfirmarDuplicarPri = document.getElementById('btn_confirmar_duplicar_pri');
    var sortableModalOrdenPri = null;

    function openModal() {
        if (modal) modal.show();
    }

    function byName(name) {
        return document.querySelector('#form_priresultado [name="' + name + '"]');
    }

    function setField(name, value) {
        var el = byName(name);
        if (el) el.value = (value === undefined || value === null) ? '' : String(value);
    }

    function syncFormulaHidden() {
        if (!formulasHidden) return;
        if (calcCb && calcCb.checked) {
            var opt = predefSelect && predefSelect.selectedIndex >= 0 ? predefSelect.options[predefSelect.selectedIndex] : null;
            formulasHidden.value = (opt && opt.dataset.formulasId) ? String(opt.dataset.formulasId) : '1';
            return;
        }
        formulasHidden.value = formulasSelect ? String(formulasSelect.value || '1') : '1';
    }

    function toggleFormulaWrappers() {
        var isCalc = !!(calcCb && calcCb.checked);
        if (wrapNormal) wrapNormal.classList.toggle('d-none', isCalc);
        if (wrapPredef) wrapPredef.classList.toggle('d-none', !isCalc);
        if (wrapFormula) wrapFormula.classList.toggle('d-none', !isCalc);
        syncFormulaHidden();
        syncFormulaExpr();
    }

    function syncTextoFijoConfigEditor(el) {
        if (!el || typeof jQuery === 'undefined' || !jQuery(el).data('summernote')) return;
        el.value = jQuery(el).summernote('code');
    }
    function destroyTextoFijoConfigEditor(el) {
        if (!el || typeof jQuery === 'undefined' || !jQuery(el).data('summernote')) return;
        syncTextoFijoConfigEditor(el);
        jQuery(el).summernote('destroy');
    }
    function initTextoFijoConfigEditor(el) {
        if (!el || typeof jQuery === 'undefined' || !jQuery.fn.summernote) return;
        if (jQuery(el).data('summernote')) return;
        jQuery(el).summernote({
            height: 180,
            toolbar: [
                ['style', ['bold', 'italic', 'underline']],
                ['para', ['ul', 'ol']],
                ['view', ['codeview']]
            ],
            callbacks: {
                onChange: function() {
                    syncTextoFijoConfigEditor(el);
                }
            }
        });
    }
    function setTextoFijoConfigValue(el, html) {
        if (!el) return;
        var val = (html !== undefined && html !== null) ? String(html) : '';
        if (typeof jQuery !== 'undefined' && jQuery(el).data('summernote')) {
            jQuery(el).summernote('code', val);
        } else {
            el.value = val;
        }
    }
    modalEl?.addEventListener('hidden.bs.modal', function() {
        destroyTextoFijoConfigEditor(document.getElementById('pri_texto_fijo'));
    });

    function toggleTextoFijoPri() {
        var wrap = document.getElementById('wrap_pri_texto_fijo');
        var sel = document.getElementById('pri_opcion_id');
        var textoFijoEl = document.getElementById('pri_texto_fijo');
        if (!wrap || !sel) return;
        var esTextoFijo = (parseInt(String(sel.value || '0'), 10) === textoFijoOpcionId);
        wrap.classList.toggle('d-none', !esTextoFijo);
        if (esTextoFijo) {
            initTextoFijoConfigEditor(textoFijoEl);
        } else {
            destroyTextoFijoConfigEditor(textoFijoEl);
        }
    }

    function fillFormPri(data) {
        data = data || {};
        if (inputId) inputId.value = String((data.priresultados_id || 0) | 0);
        setField('id_poblacion', (data.id_poblacion || 15) | 0);
        setField('sexo', data.sexo || 'ambos');
        setField('valor_min', data.valor_min || '');
        setField('valor_max', data.valor_max || '');
        setField('umedida', data.umedida || '');
        if (mostrarMedidaCb) mostrarMedidaCb.checked = !!(parseInt(String(data.mostrar_medida || 0), 10) === 1);
        setField('opcion_id', (data.opcion_id || 3) | 0);
        toggleTextoFijoPri();
        setTextoFijoConfigValue(document.getElementById('pri_texto_fijo'), data.texto_fijo || '');

        var fid = (data.formulas_id || 1) | 0;
        if (calcCb) calcCb.checked = fid > 1;
        if (formulasSelect) formulasSelect.value = String(fid > 1 ? 1 : fid);
        if (formulaArea) formulaArea.value = '';
        if (formulaNombreInput) formulaNombreInput.value = '';
        if (predefSelect) {
            predefSelect.selectedIndex = 0;
            if (fid > 1) {
                for (var i = 2; i < predefSelect.options.length; i++) {
                    var opt = predefSelect.options[i];
                    if (String(opt.dataset.formulasId || '') === String(fid)) {
                        predefSelect.selectedIndex = i;
                        if (formulaArea) formulaArea.value = internoANombres(opt.value || '');
                        if (formulaNombreInput) formulaNombreInput.value = opt.dataset.nombre || '';
                        break;
                    }
                }
            }
        }
        toggleFormulaWrappers();
    }

    function clearFormPri() {
        fillFormPri({
            priresultados_id: 0,
            id_poblacion: 15,
            sexo: 'ambos',
            valor_min: '',
            valor_max: '',
            umedida: '',
            mostrar_medida: 0,
            formulas_id: 1,
            opcion_id: 3,
            texto_fijo: ''
        });
    }

    function nombresAInterno(texto) {
        return (texto || '').replace(/\[valor\]/gi, '1');
    }

    function internoANombres(texto) {
        return (texto || '').replace(/\b1\b/g, '[valor]');
    }

    function syncFormulaExpr() {
        if (formulaInput && formulaArea) {
            formulaInput.value = nombresAInterno(formulaArea.value);
        }
        updateMathPreview();
    }

    function insertAtCursor(text) {
        if (!formulaArea) return;
        var start = formulaArea.selectionStart;
        var end = formulaArea.selectionEnd;
        var val = formulaArea.value || '';
        var prefix = (start > 0 && /[0-9a-zA-Z_\]]/.test(val[start - 1] || '')) ? ' ' : '';
        formulaArea.value = val.substring(0, start) + prefix + text + val.substring(end);
        formulaArea.selectionStart = formulaArea.selectionEnd = start + prefix.length + text.length;
        formulaArea.focus();
        syncFormulaExpr();
    }

    function exprAMatematico(s) {
        s = (s || '').replace(/\s+/g, ' ').trim();
        if (!s) return '';
        var i = 0;
        function skipSp() { while (i < s.length && s[i] === ' ') i++; }
        function parseExpr() {
            var left = parseTerm();
            skipSp();
            while (i < s.length && (s[i] === '+' || s[i] === '-')) {
                var op = s[i++];
                skipSp();
                var right = parseTerm();
                left = left + (op === '+' ? ' + ' : ' - ') + right;
                skipSp();
            }
            return left;
        }
        function parseTerm() {
            var left = parseFactor();
            skipSp();
            while (i < s.length && (s[i] === '*' || s[i] === '/')) {
                var op = s[i++];
                skipSp();
                var right = parseFactor();
                left = (op === '*') ? (left + ' \\times ' + right) : ('\\frac{' + left + '}{' + right + '}');
                skipSp();
            }
            return left;
        }
        function parseFactor() {
            skipSp();
            if (i >= s.length) return '';
            if (s[i] === '(') {
                i++;
                var r = parseExpr();
                if (s[i] === ')') i++;
                return '(' + r + ')';
            }
            var start = i;
            if (s[i] === '[') {
                i++;
                var n = '';
                while (i < s.length && s[i] !== ']') n += s[i++];
                if (s[i] === ']') i++;
                return '\\text{' + n.replace(/\\/g, '\\\\').replace(/{/g, '\\{').replace(/}/g, '\\}') + '}';
            }
            while (i < s.length && /[a-zA-Z0-9_.]/.test(s[i])) i++;
            var tok = s.substring(start, i);
            if (/^\d+\.?\d*$/.test(tok)) return tok;
            return '\\text{' + tok.replace(/\\/g, '\\\\').replace(/{/g, '\\{').replace(/}/g, '\\}') + '}';
        }
        return parseExpr();
    }

    function updateMathPreview() {
        if (!mathDisplay || !formulaArea) return;
        var expr = (formulaArea.value || '').trim();
        if (!expr) {
            mathDisplay.innerHTML = '—';
            mathDisplay.classList.remove('formula-math-error');
            return;
        }
        try {
            var latex = exprAMatematico(expr);
            if (window.katex) {
                katex.render(latex, mathDisplay, { throwOnError: false, displayMode: true });
                mathDisplay.classList.remove('formula-math-error');
            } else {
                mathDisplay.textContent = expr;
            }
        } catch (e) {
            mathDisplay.textContent = expr;
            mathDisplay.classList.add('formula-math-error');
        }
    }

    document.getElementById('btn_agregar_pri')?.addEventListener('click', function() {
        if (modalTitle) modalTitle.textContent = 'Agregar valores de referencia';
        if (btnSubmit) btnSubmit.textContent = 'Agregar';
        clearFormPri();
        openModal();
    });

    function getPriCsrfData() {
        var csrf = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
        var csrfName = (csrf && csrf.name) ? csrf.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
        var csrfVal = (csrf && csrf.value) ? csrf.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
        return { name: csrfName, value: csrfVal };
    }
    function applyPriCsrfFromJson(d) {
        if (!d || !d.csrf_token || !d.csrf_name) return;
        window.CI_CSRF_TOKEN = d.csrf_token;
        window.CI_CSRF_TOKEN_NAME = d.csrf_name;
        document.querySelectorAll('input[name="csrf_test_name"], input[name*="csrf"]').forEach(function(inp) {
            inp.name = d.csrf_name;
            inp.value = d.csrf_token;
        });
    }
    function desvanecerYQuitarFilaPri(tr, onDone) {
        if (!tr) {
            if (onDone) onDone();
            return;
        }
        tr.classList.add('pri-fila-eliminandose');
        window.setTimeout(function() {
            tr.remove();
            if (onDone) onDone();
        }, 420);
    }
    function actualizarContadorFilasPri() {
        var count = tablaPri ? tablaPri.querySelectorAll('tbody tr[data-priresultados-id]').length : 0;
        var badge = document.getElementById('badge_pri_filas_count');
        if (badge) {
            badge.textContent = count + (count === 1 ? ' fila' : ' filas');
        }
        if (priCheckAll) {
            priCheckAll.checked = false;
            priCheckAll.indeterminate = false;
        }
        actualizarEstadoSeleccionPri();
    }
    function eliminarFilasPriEnCliente(rows) {
        if (!rows || !rows.length) {
            actualizarContadorFilasPri();
            return;
        }
        var pendientes = rows.length;
        rows.forEach(function(tr) {
            desvanecerYQuitarFilaPri(tr, function() {
                pendientes--;
                if (pendientes < 1) {
                    actualizarContadorFilasPri();
                }
            });
        });
    }
    function eliminarPriResultadoAjax(priId, tr) {
        if (!priId || priId < 1) return;
        var fd = new FormData();
        var csrfData = getPriCsrfData();
        if (csrfData.value) fd.append(csrfData.name, csrfData.value);
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (csrfData.value) headers['X-CSRF-TOKEN'] = csrfData.value;
        var btn = tr ? tr.querySelector('.btn-eliminar-pri') : null;
        if (btn) btn.disabled = true;
        fetch('<?= site_url('labotests/deletepriresultado/') ?>' + priId, {
            method: 'POST',
            body: fd,
            headers: headers
        }).then(function(r) { return r.json(); }).then(function(d) {
            applyPriCsrfFromJson(d);
            if (d.success) {
                if (typeof showToast === 'function') showToast(d.message || 'Valores eliminados', 'success');
                eliminarFilasPriEnCliente(tr ? [tr] : []);
            } else {
                if (btn) btn.disabled = false;
                if (typeof showToast === 'function') showToast(d.message || 'No se pudo eliminar', 'error');
            }
        }).catch(function() {
            if (btn) btn.disabled = false;
            if (typeof showToast === 'function') showToast('No se pudo eliminar', 'error');
        });
    }
    function getSelectedPriIds() {
        var ids = [];
        if (!tablaPri) return ids;
        tablaPri.querySelectorAll('tbody .pri-check-item:checked').forEach(function(chk) {
            var id = parseInt(chk.value, 10);
            if (id > 0) ids.push(id);
        });
        return ids;
    }
    function syncPriCheckAllState() {
        if (!priCheckAll || !tablaPri) return;
        var all = tablaPri.querySelectorAll('tbody .pri-check-item');
        var checked = tablaPri.querySelectorAll('tbody .pri-check-item:checked');
        priCheckAll.checked = all.length > 0 && checked.length === all.length;
        priCheckAll.indeterminate = checked.length > 0 && checked.length < all.length;
    }
    function actualizarEstadoSeleccionPri() {
        var selected = getSelectedPriIds();
        if (btnPriPorGeneros) {
            btnPriPorGeneros.disabled = selected.length === 0;
        }
        if (btnDuplicarPriSeleccionadas) {
            btnDuplicarPriSeleccionadas.disabled = selected.length === 0;
        }
        if (btnEliminarPriSeleccionadas) {
            btnEliminarPriSeleccionadas.disabled = selected.length === 0;
        }
        syncPriCheckAllState();
    }
    function getOrderPriIds() {
        if (!tablaPri) return [];
        var ids = [];
        tablaPri.querySelectorAll('tbody tr[data-priresultados-id]').forEach(function(tr) {
            var id = parseInt(tr.getAttribute('data-priresultados-id'), 10);
            if (id > 0) ids.push(id);
        });
        return ids;
    }
    function guardarOrdenPri() {
        var order = getOrderPriIds();
        if (order.length === 0) return;
        var fd = new FormData();
        fd.append('prianacategoria_id', String(prianacategoriaId));
        for (var i = 0; i < order.length; i++) fd.append('order[]', String(order[i]));
        var csrfData = getPriCsrfData();
        if (csrfData.value) fd.append(csrfData.name, csrfData.value);
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (csrfData.value) headers['X-CSRF-TOKEN'] = csrfData.value;
        fetch('<?= site_url('labotests/orderPriResultados') ?>', {
            method: 'POST',
            body: fd,
            headers: headers
        }).then(function(r) { return r.json(); }).then(function(d) {
            applyPriCsrfFromJson(d);
            if (d.success) {
                if (typeof showToast === 'function') showToast(d.message || 'Orden guardado', 'success');
            } else if (typeof showToast === 'function') {
                showToast(d.message || 'Error al guardar orden', 'error');
            }
        }).catch(function() {
            if (typeof showToast === 'function') showToast('Error al guardar orden', 'error');
        });
    }
    function poblarListaOrdenPriModal() {
        var list = document.getElementById('listaOrdenPriResultados');
        if (!list) return;
        list.innerHTML = '';
        getOrderPriIds().forEach(function(id) {
            var tr = tablaPri.querySelector('tr[data-priresultados-id="' + id + '"]');
            if (!tr) return;
            var label = tr.getAttribute('data-orden-etiqueta') || ('#' + id);
            var li = document.createElement('li');
            li.className = 'list-group-item d-flex align-items-center gap-2 py-2';
            li.setAttribute('data-priresultados-id', String(id));
            var h = document.createElement('span');
            h.className = 'pri-orden-modal-handle text-muted flex-shrink-0';
            h.style.cursor = 'grab';
            h.title = 'Arrastrar';
            h.innerHTML = '<i class="fa-solid fa-grip-vertical"></i>';
            var t = document.createElement('span');
            t.className = 'flex-grow-1 small text-break';
            t.textContent = label;
            li.appendChild(h);
            li.appendChild(t);
            list.appendChild(li);
        });
    }
    function aplicarOrdenDesdeModalPri() {
        var tbody = tablaPri ? tablaPri.querySelector('tbody') : null;
        var list = document.getElementById('listaOrdenPriResultados');
        if (!tbody || !list) return;
        var frag = document.createDocumentFragment();
        list.querySelectorAll('li[data-priresultados-id]').forEach(function(li) {
            var id = parseInt(li.getAttribute('data-priresultados-id'), 10);
            if (id < 1) return;
            var tr = tablaPri.querySelector('tr[data-priresultados-id="' + id + '"]');
            if (tr) frag.appendChild(tr);
        });
        tbody.appendChild(frag);
        guardarOrdenPri();
        var modalOrdenEl = document.getElementById('modalOrdenPriResultados');
        if (modalOrdenEl && typeof bootstrap !== 'undefined') {
            var inst = bootstrap.Modal.getInstance(modalOrdenEl);
            if (inst) inst.hide();
        }
    }
    var modalOrdenPriEl = document.getElementById('modalOrdenPriResultados');
    var modalOrdenPriInst = null;
    if (modalOrdenPriEl && typeof bootstrap !== 'undefined') {
        modalOrdenPriInst = bootstrap.Modal.getInstance(modalOrdenPriEl) || new bootstrap.Modal(modalOrdenPriEl);
        modalOrdenPriEl.addEventListener('show.bs.modal', function() {
            if (sortableModalOrdenPri) {
                sortableModalOrdenPri.destroy();
                sortableModalOrdenPri = null;
            }
            poblarListaOrdenPriModal();
        });
        modalOrdenPriEl.addEventListener('shown.bs.modal', function() {
            var list = document.getElementById('listaOrdenPriResultados');
            if (list && typeof Sortable !== 'undefined' && list.children.length) {
                sortableModalOrdenPri = new Sortable(list, {
                    handle: '.pri-orden-modal-handle',
                    animation: 150,
                    ghostClass: 'list-group-item-secondary'
                });
            }
        });
        modalOrdenPriEl.addEventListener('hidden.bs.modal', function() {
            if (sortableModalOrdenPri) {
                sortableModalOrdenPri.destroy();
                sortableModalOrdenPri = null;
            }
        });
    }
    var btnAbrirOrdenPri = document.getElementById('btn_abrir_modal_orden_pri');
    if (btnAbrirOrdenPri && modalOrdenPriInst) {
        btnAbrirOrdenPri.addEventListener('click', function() { modalOrdenPriInst.show(); });
    }
    var btnAplicarOrdenPri = document.getElementById('btn_aplicar_orden_pri_modal');
    if (btnAplicarOrdenPri) {
        btnAplicarOrdenPri.addEventListener('click', aplicarOrdenDesdeModalPri);
    }
    if (priCheckAll && tablaPri) {
        priCheckAll.addEventListener('change', function() {
            var checked = !!priCheckAll.checked;
            tablaPri.querySelectorAll('tbody .pri-check-item').forEach(function(chk) {
                chk.checked = checked;
            });
            actualizarEstadoSeleccionPri();
        });
        tablaPri.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('pri-check-item')) {
                actualizarEstadoSeleccionPri();
            }
        });
        actualizarEstadoSeleccionPri();
    }
    if (btnPriPorGeneros) {
        btnPriPorGeneros.addEventListener('click', function() {
            var ids = getSelectedPriIds();
            if (ids.length < 1) return;
            var msg = '¿Generar filas por cada género del catálogo a partir de ' + ids.length + ' fila(s) seleccionada(s)? Las filas con sexo «Todos» serán reemplazadas.';
            var confirmar = (typeof uiConfirm === 'function')
                ? uiConfirm(msg, 'Pruebas por géneros')
                : Promise.resolve(window.confirm(msg));
            confirmar.then(function(ok) {
                if (!ok) return;
                var fd = new FormData();
                fd.append('prianacategoria_id', String(prianacategoriaId));
                ids.forEach(function(id) { fd.append('priresultados_ids[]', String(id)); });
                var csrfData = getPriCsrfData();
                if (csrfData.value) fd.append(csrfData.name, csrfData.value);
                var headers = { 'X-Requested-With': 'XMLHttpRequest' };
                if (csrfData.value) headers['X-CSRF-TOKEN'] = csrfData.value;
                btnPriPorGeneros.disabled = true;
                fetch('<?= site_url('labotests/expandreferenciasbygenerospri') ?>', {
                    method: 'POST',
                    body: fd,
                    headers: headers
                }).then(function(r) { return r.json(); }).then(function(d) {
                    applyPriCsrfFromJson(d);
                    if (d.success) {
                        if (typeof showToast === 'function') showToast(d.message || 'Operación completada', 'success');
                        window.location.reload();
                    } else if (typeof showToast === 'function') {
                        showToast(d.message || 'No se pudo completar la operación', 'error');
                        actualizarEstadoSeleccionPri();
                    }
                }).catch(function() {
                    if (typeof showToast === 'function') showToast('No se pudo completar la operación', 'error');
                    actualizarEstadoSeleccionPri();
                });
            });
        });
    }
    if (btnDuplicarPriSeleccionadas) {
        btnDuplicarPriSeleccionadas.addEventListener('click', function() {
            if (getSelectedPriIds().length < 1) return;
            if (duplicarPriCopiasInput) duplicarPriCopiasInput.value = '1';
            if (modalDuplicarPriInst) modalDuplicarPriInst.show();
        });
    }
    if (btnConfirmarDuplicarPri) {
        btnConfirmarDuplicarPri.addEventListener('click', function() {
            var ids = getSelectedPriIds();
            if (ids.length < 1) return;
            var copies = parseInt((duplicarPriCopiasInput && duplicarPriCopiasInput.value) ? duplicarPriCopiasInput.value : '1', 10);
            copies = isNaN(copies) ? 1 : Math.max(1, Math.min(100, copies));
            var fd = new FormData();
            fd.append('prianacategoria_id', String(prianacategoriaId));
            fd.append('copies', String(copies));
            ids.forEach(function(id) { fd.append('priresultados_ids[]', String(id)); });
            var csrfData = getPriCsrfData();
            if (csrfData.value) fd.append(csrfData.name, csrfData.value);
            var headers = { 'X-Requested-With': 'XMLHttpRequest' };
            if (csrfData.value) headers['X-CSRF-TOKEN'] = csrfData.value;
            btnConfirmarDuplicarPri.disabled = true;
            fetch('<?= site_url('labotests/duplicatepriresultadosbulk') ?>', {
                method: 'POST',
                body: fd,
                headers: headers
            }).then(function(r) { return r.json(); }).then(function(d) {
                btnConfirmarDuplicarPri.disabled = false;
                applyPriCsrfFromJson(d);
                if (d.success) {
                    if (typeof showToast === 'function') showToast(d.message || 'Filas duplicadas', 'success');
                    if (modalDuplicarPriInst) modalDuplicarPriInst.hide();
                    window.location.reload();
                } else if (typeof showToast === 'function') {
                    showToast(d.message || 'No se pudo duplicar', 'error');
                }
            }).catch(function() {
                btnConfirmarDuplicarPri.disabled = false;
                if (typeof showToast === 'function') showToast('No se pudo duplicar las filas seleccionadas', 'error');
            });
        });
    }
    if (btnEliminarPriSeleccionadas) {
        btnEliminarPriSeleccionadas.addEventListener('click', function() {
            var ids = getSelectedPriIds();
            if (ids.length < 1) return;
            var msg = '¿Eliminar ' + ids.length + ' fila(s) seleccionada(s)?';
            var confirmar = (typeof uiConfirm === 'function')
                ? uiConfirm(msg, 'Confirmar eliminación masiva')
                : Promise.resolve(window.confirm(msg));
            confirmar.then(function(ok) {
                if (!ok) return;
                var fd = new FormData();
                fd.append('prianacategoria_id', String(prianacategoriaId));
                ids.forEach(function(id) { fd.append('priresultados_ids[]', String(id)); });
                var csrfData = getPriCsrfData();
                if (csrfData.value) fd.append(csrfData.name, csrfData.value);
                var headers = { 'X-Requested-With': 'XMLHttpRequest' };
                if (csrfData.value) headers['X-CSRF-TOKEN'] = csrfData.value;
                var filasAEliminar = [];
                ids.forEach(function(id) {
                    var tr = tablaPri ? tablaPri.querySelector('tr[data-priresultados-id="' + id + '"]') : null;
                    if (tr) filasAEliminar.push(tr);
                });
                btnEliminarPriSeleccionadas.disabled = true;
                fetch('<?= site_url('labotests/deletepriresultadosbulk') ?>', {
                    method: 'POST',
                    body: fd,
                    headers: headers
                }).then(function(r) { return r.json(); }).then(function(d) {
                    applyPriCsrfFromJson(d);
                    if (d.success) {
                        if (typeof showToast === 'function') showToast(d.message || 'Eliminación completada', 'success');
                        eliminarFilasPriEnCliente(filasAEliminar);
                    } else if (typeof showToast === 'function') {
                        showToast(d.message || 'No se pudo eliminar', 'error');
                        actualizarEstadoSeleccionPri();
                    }
                }).catch(function() {
                    if (typeof showToast === 'function') showToast('No se pudo eliminar', 'error');
                    actualizarEstadoSeleccionPri();
                }).finally(function() {
                    if (btnEliminarPriSeleccionadas) btnEliminarPriSeleccionadas.disabled = false;
                });
            });
        });
    }
    var priUmedidaTransformBusy = false;
    var priUmedidaModeLabels = {
        uppercase: 'convertir todas las unidades de medida a MAYÚSCULAS',
        lowercase: 'convertir todas las unidades de medida a minúsculas',
        sentence: 'poner solo la primera letra de cada unidad en mayúscula',
        title: 'poner la primera letra de cada palabra en mayúscula'
    };
    document.querySelectorAll('.pri-transform-umedida-action').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (priUmedidaTransformBusy) return;
            var mode = btn.getAttribute('data-mode') || '';
            if (!mode) return;
            var msg = '¿Confirma ' + (priUmedidaModeLabels[mode] || 'transformar las unidades de medida') + ' en esta prueba?';
            var confirmar = (typeof uiConfirm === 'function')
                ? uiConfirm(msg, 'Formato de nombres')
                : Promise.resolve(window.confirm(msg));
            confirmar.then(function(ok) {
                if (!ok) return;
                priUmedidaTransformBusy = true;
                if (btnFormatoNombresPri) btnFormatoNombresPri.disabled = true;
                var fd = new FormData();
                fd.append('prianacategoria_id', String(prianacategoriaId));
                fd.append('mode', mode);
                var csrfData = getPriCsrfData();
                if (csrfData.value) fd.append(csrfData.name, csrfData.value);
                var headers = { 'X-Requested-With': 'XMLHttpRequest' };
                if (csrfData.value) headers['X-CSRF-TOKEN'] = csrfData.value;
                fetch('<?= site_url('labotests/transformpriresultadosumedida') ?>', {
                    method: 'POST',
                    body: fd,
                    headers: headers
                }).then(function(r) { return r.json(); }).then(function(d) {
                    applyPriCsrfFromJson(d);
                    if (d.success) {
                        if (typeof showToast === 'function') showToast(d.message || 'Unidades actualizadas', 'success');
                        window.location.reload();
                    } else if (typeof showToast === 'function') {
                        showToast(d.message || 'No se pudo aplicar el formato', 'error');
                    }
                }).catch(function() {
                    if (typeof showToast === 'function') showToast('No se pudo aplicar el formato', 'error');
                }).finally(function() {
                    priUmedidaTransformBusy = false;
                    if (btnFormatoNombresPri) btnFormatoNombresPri.disabled = false;
                });
            });
        });
    });

    if (tablaPri) {
        tablaPri.addEventListener('click', function(e) {
            var eliminarBtn = e.target.closest('.btn-eliminar-pri');
            if (!eliminarBtn) return;
            e.preventDefault();
            var tr = eliminarBtn.closest('tr[data-priresultados-id]');
            var priId = parseInt(eliminarBtn.getAttribute('data-pri-id') || (tr ? tr.getAttribute('data-priresultados-id') : '0'), 10);
            if (priId < 1) return;
            var confirmar = (typeof uiConfirm === 'function')
                ? uiConfirm('¿Eliminar estos valores?', 'Confirmar')
                : Promise.resolve(window.confirm('¿Eliminar estos valores?'));
            confirmar.then(function(ok) {
                if (!ok) return;
                eliminarPriResultadoAjax(priId, tr);
            });
        });
    }

    document.querySelectorAll('.btn-editar-pri').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var dataStr = btn.getAttribute('data-pri');
            if (!dataStr) return;
            var data = {};
            try { data = JSON.parse(dataStr); } catch (e) { return; }
            if (modalTitle) modalTitle.textContent = 'Editar valores de referencia';
            if (btnSubmit) btnSubmit.textContent = 'Actualizar';
            fillFormPri(data);
            openModal();
        });
    });

    calcCb?.addEventListener('change', toggleFormulaWrappers);
    document.getElementById('pri_opcion_id')?.addEventListener('change', toggleTextoFijoPri);
    formulasSelect?.addEventListener('change', syncFormulaHidden);
    predefSelect?.addEventListener('change', function() {
        var opt = predefSelect.options[predefSelect.selectedIndex];
        var val = opt ? opt.value : '';
        if (val && val !== '__NUEVA__') {
            if (formulaArea) formulaArea.value = internoANombres(val);
            if (formulaNombreInput) formulaNombreInput.value = opt.dataset.nombre || '';
        } else {
            if (formulaArea) formulaArea.value = '';
            if (formulaNombreInput) formulaNombreInput.value = '';
        }
        syncFormulaHidden();
        syncFormulaExpr();
    });
    form?.addEventListener('submit', function() {
        syncFormulaHidden();
        syncTextoFijoConfigEditor(document.getElementById('pri_texto_fijo'));
    });
    formulaArea?.addEventListener('input', syncFormulaExpr);
    document.querySelectorAll('.pri-formula-ref').forEach(function(el) {
        el.addEventListener('click', function() {
            insertAtCursor('[' + (el.dataset.nombre || 'valor') + ']');
        });
    });
    document.querySelectorAll('.pri-formula-op').forEach(function(btn) {
        btn.addEventListener('click', function() {
            insertAtCursor(btn.dataset.op || '');
        });
    });

    var btnGuardarFormula = document.getElementById('btn_guardar_formula_pri');
    if (btnGuardarFormula && formulaArea) {
        btnGuardarFormula.addEventListener('click', function() {
            var exprConNombres = (formulaArea.value || '').trim();
            var nombre = formulaNombreInput ? (formulaNombreInput.value || '').trim() : '';
            if (!exprConNombres) {
                if (typeof showToast === 'function') showToast('Escriba una fórmula primero', 'error');
                return;
            }
            if (!nombre) {
                if (typeof showToast === 'function') showToast('Ingrese nombre de la fórmula', 'error');
                formulaNombreInput?.focus();
                return;
            }
            var selOpt = predefSelect && predefSelect.selectedIndex > 0 ? predefSelect.options[predefSelect.selectedIndex] : null;
            var fid = (selOpt && selOpt.dataset.formulasId) ? String(selOpt.dataset.formulasId) : '0';
            if (selOpt && selOpt.value === '__NUEVA__') {
                fid = '0';
            }
            var formData = new FormData();
            formData.append('nombre_formula', nombre);
            formData.append('formula_expresion', exprConNombres);
            formData.append('formulas_id', fid);
            var csrfInput = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
            var csrfName = (csrfInput && csrfInput.name) ? csrfInput.name : 'csrf_test_name';
            var csrfVal = (csrfInput && csrfInput.value) ? csrfInput.value : '';
            if (csrfVal) formData.append(csrfName, csrfVal);
            btnGuardarFormula.disabled = true;
            fetch('<?= site_url('labotests/saveformula') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(r) { return r.json(); }).then(function(d) {
                btnGuardarFormula.disabled = false;
                if (!d.success) {
                    if (typeof showToast === 'function') showToast(d.message || 'Error al guardar', 'error');
                    return;
                }
                if (d.csrf_token && d.csrf_name) {
                    document.querySelectorAll('input[name="' + d.csrf_name + '"], input[name="csrf_test_name"], input[name*="csrf"]').forEach(function(inp) {
                        inp.name = d.csrf_name;
                        inp.value = d.csrf_token;
                    });
                }
                if (predefSelect) {
                    var exprVal = exprConNombres;
                    var opt = (predefSelect.selectedIndex > 0) ? predefSelect.options[predefSelect.selectedIndex] : null;
                    if (opt && opt.value !== '__NUEVA__' && fid !== '0') {
                        opt.value = exprVal;
                        opt.textContent = nombre;
                        opt.dataset.nombre = nombre;
                        opt.dataset.formulasId = String(d.formulas_id || fid);
                    } else {
                        opt = document.createElement('option');
                        opt.value = exprVal;
                        opt.textContent = nombre;
                        opt.dataset.nombre = nombre;
                        opt.dataset.formulasId = String(d.formulas_id || 1);
                        predefSelect.appendChild(opt);
                        predefSelect.selectedIndex = predefSelect.options.length - 1;
                    }
                }
                syncFormulaHidden();
                if (typeof showToast === 'function') showToast(fid !== '0' ? 'Fórmula actualizada' : 'Fórmula guardada', 'success');
            }).catch(function() {
                btnGuardarFormula.disabled = false;
                if (typeof showToast === 'function') showToast('Error al guardar', 'error');
            });
        });
    }

    var btnEliminarFormula = document.getElementById('btn_eliminar_formula_pri');
    if (btnEliminarFormula && predefSelect) {
        btnEliminarFormula.addEventListener('click', function() {
            var opt = predefSelect.options[predefSelect.selectedIndex];
            if (!opt || predefSelect.selectedIndex < 2 || !opt.dataset.formulasId) {
                if (typeof showToast === 'function') showToast('Seleccione una fórmula creada para eliminar', 'error');
                return;
            }
            var fid = parseInt(opt.dataset.formulasId, 10);
            if (!fid || fid < 2) {
                if (typeof showToast === 'function') showToast('No se puede eliminar esta fórmula', 'error');
                return;
            }
            uiConfirm('¿Eliminar la fórmula "' + (opt.textContent || '') + '"?', 'Confirmar').then(function(ok) {
                if (!ok) return;
                var formData = new FormData();
                var csrfInput = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
                var csrfName = (csrfInput && csrfInput.name) ? csrfInput.name : 'csrf_test_name';
                var csrfVal = (csrfInput && csrfInput.value) ? csrfInput.value : '';
                if (csrfVal) formData.append(csrfName, csrfVal);
                btnEliminarFormula.disabled = true;
                fetch('<?= site_url('labotests/deleteformula/') ?>' + fid, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function(r) { return r.json(); }).then(function(d) {
                    btnEliminarFormula.disabled = false;
                    if (!d.success) {
                        if (typeof showToast === 'function') showToast(d.message || 'No se pudo eliminar', 'error');
                        return;
                    }
                    opt.remove();
                    predefSelect.selectedIndex = 0;
                    if (formulaArea) formulaArea.value = '';
                    if (formulaNombreInput) formulaNombreInput.value = '';
                    syncFormulaHidden();
                    syncFormulaExpr();
                    if (typeof showToast === 'function') showToast(d.message || 'Fórmula eliminada', 'success');
                }).catch(function() {
                    btnEliminarFormula.disabled = false;
                    if (typeof showToast === 'function') showToast('Error al eliminar', 'error');
                });
            });
        });
    }

    <?php if ($editar_pri ?? 0): ?>
    fillFormPri(<?= json_encode([
        'priresultados_id' => (int) ($editar_pri ?? 0),
        'id_poblacion' => (int) ($editar_pri_data['id_poblacion'] ?? 15),
        'sexo' => $editar_pri_data['sexo'] ?? 'ambos',
        'valor_min' => $editar_pri_data['valor_min'] ?? '',
        'valor_max' => $editar_pri_data['valor_max'] ?? '',
        'umedida' => $editar_pri_data['umedida'] ?? '',
        'mostrar_medida' => (int) ($editar_pri_data['mostrar_medida'] ?? 0),
        'formulas_id' => (int) ($editar_pri_data['formulas_id'] ?? 1),
        'opcion_id' => (int) ($editar_pri_data['opcion_id'] ?? 3),
    ]) ?>);
    if (modalTitle) modalTitle.textContent = 'Editar valores de referencia';
    if (btnSubmit) btnSubmit.textContent = 'Actualizar';
    openModal();
    <?php else: ?>
    clearFormPri();
    <?php endif; ?>
    syncFormulaExpr();
})();
</script>
