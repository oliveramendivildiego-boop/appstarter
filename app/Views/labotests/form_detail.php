<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'labotests']) ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_labotests'), 'url' => site_url('labotests')],
    ['label' => $labotests_master->name ?? '', 'url' => site_url('labotests/view/' . $labotests_namecate)],
    ['label' => ($labotests_info->name ?? '') . ' - ' . lang('Labotests.labotests_config'), 'url' => null],
]]) ?>

<?= form_open('labotests/savesub', ['id' => 'detail_form']) ?>
<input type="hidden" id="prianacategoria_id" name="prianacategoria_id" value="<?= (int)($labotests_info->prianacategoria_id ?? 0) ?>">
<input type="hidden" id="anacategoria_id" name="anacategoria_id" value="<?= (int)($labotests_info->anacategoria_id ?? 0) ?>">

<div class="card">
    <div class="card-header"><strong><?= lang('Labotests.labotests_config') ?></strong></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label"><?= lang('Labotests.labotests_name_sub') ?> <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="<?= esc($labotests_info->name ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="order" class="form-label"><?= lang('Labotests.labotests_order') ?></label>
                <input type="number" name="order" id="order" class="form-control" value="<?= esc($labotests_info->order ?? 0) ?>" min="0">
            </div>
            <div class="col-md-6 mb-3">
                <label for="cost" class="form-label"><?= lang('Labotests.labotests_cost_sub') ?> (Bs)</label>
                <input type="number" name="cost" id="cost" class="form-control" value="<?= esc($labotests_info->cost ?? 0) ?>" min="0">
            </div>
            <div class="col-md-6 mb-3">
                <label for="cost_deriv" class="form-label"><?= lang('Labotests.labotests_costderiv_sub') ?> (Bs)</label>
                <input type="number" name="cost_deriv" id="cost_deriv" class="form-control" value="<?= esc($labotests_info->cost_deriv ?? 0) ?>" min="0">
            </div>
            <div class="col-md-6 mb-3">
                <label for="compleja" class="form-label"><?= lang('Labotests.labotests_compuesta') ?></label>
                <select name="compleja" id="compleja" class="form-control">
                    <option value="0" <?= (($labotests_info->compleja ?? 0) == 0 ? 'selected' : '') ?>><?= lang('Labotests.labotests_no') ?></option>
                    <option value="1" <?= (($labotests_info->compleja ?? 0) == 1 ? 'selected' : '') ?>><?= lang('Labotests.labotests_yes') ?></option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= lang('Common.common_submit') ?></button>
        <a href="<?= site_url('labotests') ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</div>

<?= form_close() ?>

<?php if ($compleja ?? 0): ?>
<?php
$refsPorNombre = [];   // nombre -> c_id o constante (para guardado)
$refsCidToNombre = []; // c_id -> nombre (para carga)
foreach ($sub_items ?? [] as $s) {
    if (($editar_sec ?? 0) && (int)($s['secanacategoria_id'] ?? 0) === $editar_sec) continue;
    $nombre = trim($s['nombre'] ?? '');
    $cid = 'c_' . ($s['secanacategoria_id'] ?? '');
    if ($nombre !== '' && !isset($refsPorNombre[$nombre])) {
        $refsPorNombre[$nombre] = $cid;
        $refsCidToNombre[$cid] = $nombre;
    }
}
$refsPorNombre['valor'] = '1';
$formulaExpresionDesdeFormulas = '';
if (($editar_sec ?? 0) && ((int)($editar_sec_data['formulas_id'] ?? 0)) > 1) {
    $fidEditar = (int) $editar_sec_data['formulas_id'];
    foreach ($formulas_con_expresion ?? [] as $f) {
        if ((int)($f['formulas_id'] ?? 0) === $fidEditar) {
            $formulaExpresionDesdeFormulas = trim($f['formula_expresion'] ?? '');
            break;
        }
    }
}
$formulaParaTextarea = $formulaExpresionDesdeFormulas !== '' ? $formulaExpresionDesdeFormulas : ($editar_sec_data['formula_expresion'] ?? '');
if ($formulaExpresionDesdeFormulas === '') {
    foreach ($refsCidToNombre as $cid => $nombre) {
        $formulaParaTextarea = str_replace($cid, '[' . $nombre . ']', $formulaParaTextarea);
    }
    $formulaParaTextarea = preg_replace('/\b1\b/', '[valor]', $formulaParaTextarea);
}
$formulaNombreInicial = '';
$feRaw = trim($editar_sec_data['formula_expresion'] ?? '');
if ($feRaw === '' && $formulaExpresionDesdeFormulas !== '') $feRaw = $formulaExpresionDesdeFormulas;
if ($feRaw !== '' && !empty($formulas_con_expresion ?? [])) {
    foreach ($formulas_con_expresion as $f) {
        if (trim($f['formula_expresion'] ?? '') === $feRaw) {
            $formulaNombreInicial = $f['nombre'] ?? '';
            break;
        }
    }
}
?>
<div class="card mt-3">
    <div class="card-header"><strong>Valores de sub-clases (prueba compuesta)</strong></div>
    <div class="card-body">
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <style>
            #tabla_sub_items .sec-drag-handle { cursor: grab; padding: 0.25rem; user-select: none; color: #6c757d; }
            #tabla_sub_items .sec-drag-handle:active { cursor: grabbing; }
        </style>
        <table class="table table-sm table-bordered" id="tabla_sub_items">
            <thead>
                <tr>
                    <th class="text-center" style="width: 6rem;">Orden</th>
                    <th>Sub-clase</th>
                    <th>Población</th>
                    <th>Sexo</th>
                    <th>Valor mín</th>
                    <th>Valor máx</th>
                    <th>U. medida</th>
                    <th>Fórmula</th>
                    <th>Tipo</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pobMap = [];
                foreach ($poblaciones ?? [] as $p) {
                    $pobMap[(int)$p['id_poblacion']] = $p['name'] ?? '';
                }
                foreach ($sub_items ?? [] as $s):
                    $rowFormulaExpr = '';
                    if ((int)($s['formulas_id'] ?? 0) > 1) {
                        foreach ($formulas_con_expresion ?? [] as $f) {
                            if ((int)($f['formulas_id'] ?? 0) === (int)($s['formulas_id'])) {
                                $rowFormulaExpr = trim($f['formula_expresion'] ?? '');
                                break;
                            }
                        }
                    }
                    $rowFormulaTextarea = $rowFormulaExpr;
                    if ($rowFormulaTextarea !== '') {
                        foreach ($refsCidToNombre as $cid => $nombre) {
                            $rowFormulaTextarea = str_replace($cid, '[' . $nombre . ']', $rowFormulaTextarea);
                        }
                        $rowFormulaTextarea = preg_replace('/\b1\b/', '[valor]', $rowFormulaTextarea);
                    }
                    $rowFormulaNombre = '';
                    if ($rowFormulaExpr !== '' && !empty($formulas_con_expresion ?? [])) {
                        foreach ($formulas_con_expresion as $f) {
                            if (trim($f['formula_expresion'] ?? '') === $rowFormulaExpr) {
                                $rowFormulaNombre = $f['nombre'] ?? '';
                                break;
                            }
                        }
                    }
                    $rowDataSec = [
                        'secanacategoria_id' => (int)($s['secanacategoria_id'] ?? 0),
                        'nombre' => $s['nombre'] ?? '',
                        'paciente_id' => (int)($s['paciente_id'] ?? 3),
                        'sexo' => $s['sexo'] ?? 'ambos',
                        'valor_min' => $s['valor_min'] ?? '',
                        'valor_max' => $s['valor_max'] ?? '',
                        'umedida' => $s['umedida'] ?? '',
                        'formulas_id' => (int)($s['formulas_id'] ?? 1),
                        'opcion_id' => (int)($s['opcion_id'] ?? 3),
                        'formula_para_textarea' => $rowFormulaTextarea,
                        'formula_nombre' => $rowFormulaNombre,
                    ];
                ?>
                <tr data-sec="<?= htmlspecialchars(json_encode($rowDataSec), ENT_QUOTES, 'UTF-8') ?>" data-secanacategoria-id="<?= (int)($s['secanacategoria_id'] ?? 0) ?>">
                    <td class="text-center">
                        <span class="sec-drag-handle" title="Arrastrar para reordenar"><i class="fa-solid fa-grip-vertical"></i></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sec-subir" title="Subir"><i class="fa-solid fa-arrow-up"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sec-bajar" title="Bajar"><i class="fa-solid fa-arrow-down"></i></button>
                    </td>
                    <td><?= esc($s['nombre'] ?? '') ?></td>
                    <td><?= esc($pobMap[(int)($s['paciente_id'] ?? 0)] ?? $s['paciente_id'] ?? '') ?></td>
                    <td><?= esc(match($s['sexo'] ?? '') { 'masculino' => 'Masculino', 'femenino' => 'Femenino', default => 'Ambos' }) ?></td>
                    <td><?= esc($s['valor_min'] ?? '') ?></td>
                    <td><?= esc($s['valor_max'] ?? '') ?></td>
                    <td><?= esc($s['umedida'] ?? '') ?></td>
                    <td><?php
$fe = $s['formula_expresion'] ?? '';
if ($fe !== '') {
    $fid = (int)($s['formulas_id'] ?? 0);
    $fname = ($formulas ?? [])[$fid] ?? '';
    echo esc($fname !== '' ? $fname : 'Calculada');
} else {
    echo esc(($formulas ?? [])[(int)($s['formulas_id'] ?? 0)] ?? 'Ninguno');
} ?></td>
                    <td><?= esc($opciones[(int)($s['opcion_id'] ?? 0)] ?? '') ?></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar-sec" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <a href="<?= site_url("labotests/duplicatesecitem/" . (int)($s['secanacategoria_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary" title="Duplicar"><i class="fa-solid fa-copy"></i></a>
                        <a href="<?= site_url("labotests/deletesecitem/" . (int)($s['secanacategoria_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Eliminar esta sub-clase?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <hr>
        <button type="button" class="btn btn-primary btn-sm mb-3" id="btn_agregar_sec"><?= empty($sub_items) ? 'Agregar primera sub-clase' : 'Agregar sub-clase' ?></button>

        <div class="modal fade" id="modalEditarSec" tabindex="-1" aria-labelledby="modalEditarSecTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarSecTitle">Editar sub-clase</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
        <?= form_open('labotests/savesecitem', ['class' => 'border p-3 rounded', 'id' => 'form_secitem']) ?>
        <input type="hidden" name="prianacategoria_id" value="<?= (int)($labotests_info->prianacategoria_id ?? 0) ?>">
        <input type="hidden" name="secanacategoria_id" id="secanacategoria_id_input" value="<?= (int)($editar_sec ?? 0) ?>">
        <div class="row">
            <div class="col-md-3 mb-2">
                <label class="form-label">Nombre sub-clase <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control form-control-sm" value="<?= esc($editar_sec_data['nombre'] ?? '') ?>" required>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Población</label>
                <select name="paciente_id" class="form-control form-control-sm">
                    <?php foreach ($poblaciones ?? [] as $p): ?>
                    <option value="<?= (int)$p['id_poblacion'] ?>" <?= ((int)($editar_sec_data['paciente_id'] ?? 3) === (int)$p['id_poblacion']) ? 'selected' : '' ?>><?= esc($p['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Sexo</label>
                <select name="sexo" class="form-control form-control-sm">
                    <option value="ambos" <?= (($editar_sec_data['sexo'] ?? 'ambos') === 'ambos') ? 'selected' : '' ?>>Ambos</option>
                    <option value="masculino" <?= (($editar_sec_data['sexo'] ?? '') === 'masculino') ? 'selected' : '' ?>>Masculino</option>
                    <option value="femenino" <?= (($editar_sec_data['sexo'] ?? '') === 'femenino') ? 'selected' : '' ?>>Femenino</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Valor mín</label>
                <input type="text" name="valor_min" class="form-control form-control-sm" value="<?= esc($editar_sec_data['valor_min'] ?? '') ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Valor máx</label>
                <input type="text" name="valor_max" class="form-control form-control-sm" value="<?= esc($editar_sec_data['valor_max'] ?? '') ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">U. medida</label>
                <input type="text" name="umedida" class="form-control form-control-sm" value="<?= esc($editar_sec_data['umedida'] ?? '') ?>">
            </div>
        </div>
        <div class="row">
            <?php $esCalculada = ((int)($editar_sec_data['formulas_id'] ?? 1) > 1); ?>
            <div class="col-md-2 mb-2">
                <label class="form-label">¿Calculada?</label>
                <div class="form-check mt-1">
                    <input type="checkbox" name="es_calculada" id="es_calculada" class="form-check-input" <?= $esCalculada ? 'checked' : '' ?>>
                    <label class="form-check-label" for="es_calculada">Sí</label>
                </div>
            </div>
            <input type="hidden" name="formulas_id" id="formulas_id_hidden" value="<?= (int)($editar_sec_data['formulas_id'] ?? 1) ?>">
            <div class="col-md-3 mb-2" id="wrap_formulas_id" style="<?= $esCalculada ? 'display:none;' : '' ?>">
                <label class="form-label">Fórmula predefinida</label>
                <select id="formulas_id" class="form-control form-control-sm">
                    <?php
                    $secFormulasId = (int)($editar_sec_data['formulas_id'] ?? 1);
                    $secFormulasIdSel = ($formulas_id_canonical ?? [])[$secFormulasId] ?? $secFormulasId;
                    foreach ($formulas_creadas ?? $formulas ?? [] as $fid => $fname):
                    ?>
                    <option value="<?= $fid ?>" <?= (!$esCalculada && $secFormulasIdSel === $fid) ? 'selected' : '' ?>><?= esc($fname) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Fórmulas creadas (sin duplicados)</small>
            </div>
            <div class="col-md-4 mb-2" id="wrap_formula_predefinida_creadas" style="<?= !$esCalculada ? 'display:none;' : '' ?>">
                <label class="form-label">Fórmula predefinida (todas las calculadas creadas)</label>
                <select id="formula_predefinida_select" class="form-control form-control-sm">
                    <option value="">— Ninguno —</option>
                    <option value="__NUEVA__">— Nueva fórmula —</option>
                    <?php foreach ($formulas_con_expresion_deduped ?? $formulas_con_expresion ?? [] as $f): ?>
                    <option value="<?= esc($f['formula_expresion'] ?? '') ?>" data-formulas-id="<?= (int)($f['formulas_id'] ?? 0) ?>" data-nombre="<?= esc($f['nombre'] ?? '') ?>"><?= esc($f['nombre'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Ninguno=sin fórmula calculada; Nueva=crear; o seleccione para editar</small>
            </div>
            <div class="col-12 mb-2" id="wrap_formula_expresion" style="<?= !$esCalculada ? 'display:none;' : '' ?>">
                <label class="form-label">Constructor de fórmula</label>
                <input type="hidden" name="formula_expresion" id="formula_expresion" value="<?= esc($editar_sec_data['formula_expresion'] ?? '') ?>">
                <div class="border rounded p-2 mb-2 bg-light" style="min-height:50px">
                    <small class="text-muted d-block mb-1">Referencias (arrastre o clic para insertar en la fórmula):</small>
                    <div id="formula_refs" class="d-flex flex-wrap gap-1 mb-2">
                        <?php foreach ($refsPorNombre ?? [] as $nombre => $cid): ?>
                        <span class="badge <?= $nombre === 'valor' ? 'bg-secondary' : 'bg-primary' ?> formula-ref" draggable="true" data-nombre="<?= esc($nombre) ?>" data-cid="<?= esc($cid) ?>" style="cursor:grab;font-size:0.85rem"><?= esc($nombre) ?></span>
                        <?php endforeach; ?>
                        <?php if (empty($refsPorNombre) || count($refsPorNombre) <= 1): ?>
                        <small class="text-muted align-self-center">Agregue sub-clases en la tabla superior para que aparezcan aquí.</small>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted d-block mb-1">Fórmula actual (nombre de la fórmula o con qué nombre guardará):</small>
                    <div class="d-flex gap-2 align-items-center mb-2">
                        <input type="text" id="formula_nombre_input" class="form-control form-control-sm" placeholder="Ej: Formula Eritrocitos" style="max-width:220px" value="<?= esc($formulaNombreInicial ?? '') ?>">
                        <span id="formula_valor_refs" class="border rounded px-2 py-1 bg-white flex-grow-1" style="min-height:2em;font-size:0.95rem;font-weight:500">—</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
                    <button type="button" id="btn_guardar_formula" class="btn btn-outline-success btn-sm">Guardar / Actualizar fórmula</button>
                    <button type="button" id="btn_eliminar_formula" class="btn btn-outline-danger btn-sm" title="Eliminar la fórmula seleccionada (solo si no está en uso)">Eliminar fórmula</button>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-2 align-items-center">
                    <span class="text-muted" style="font-size:0.9rem">Operadores:</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op="+">+</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op="-">−</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op="*">×</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op="/">/</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op="(">(</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op=")">)</button>
                    <span class="text-muted ms-2" style="font-size:0.85rem">(Puede escribir números directamente)</span>
                </div>
                <div class="border rounded p-2 bg-white mb-2" style="min-height:70px">
                    <small class="text-muted d-block mb-1">Fórmula (use referencias por nombre y operadores):</small>
                    <textarea id="formula_area" class="form-control" rows="2" style="font-family:monospace" placeholder="Ej: [Glucosa] + [Colesterol] * 100 / [Glucosa]"><?= esc($formulaParaTextarea ?? '') ?></textarea>
                </div>
                <div class="border rounded p-3 bg-light" id="formula_math_preview" style="min-height:60px">
                    <small class="text-muted d-block mb-2">Vista previa matemática:</small>
                    <div id="formula_math_display" class="fs-4 text-center py-2" style="font-family:serif;color:#333">—</div>
                </div>
                <style>.formula-math-error{color:#999;font-size:0.9em;}</style>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css"/>
                <script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label">Tipo resultado</label>
                <select name="opcion_id" class="form-control form-control-sm">
                    <?php foreach ($opciones ?? [] as $oid => $oname): ?>
                    <option value="<?= $oid ?>" <?= ((int)($editar_sec_data['opcion_id'] ?? 3) === $oid) ? 'selected' : '' ?>><?= esc($oname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <button type="submit" class="btn btn-primary btn-sm mt-4" id="btn_submit_sec">Actualizar</button>
                <button type="button" class="btn btn-secondary btn-sm mt-4" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
        <script>
        (function() {
            var refsNombreToCid = <?= json_encode($refsPorNombre ?? []) ?>;
            var refsCidToNombre = <?= json_encode($refsCidToNombre ?? []) ?>;
            var wForm = document.getElementById('wrap_formulas_id');
            var wExp = document.getElementById('wrap_formula_expresion');
            var formulaArea = document.getElementById('formula_area');
            var formulaInput = document.getElementById('formula_expresion');
            var mathDisplay = document.getElementById('formula_math_display');
            var formulaValorRefs = document.getElementById('formula_valor_refs');
            var wrapPredefCreadas = document.getElementById('wrap_formula_predefinida_creadas');
            var formulaPredefSelect = document.getElementById('formula_predefinida_select');
            var formulasIdHidden = document.getElementById('formulas_id_hidden');
            var formulasIdSelect = document.getElementById('formulas_id');
            document.getElementById('es_calculada')?.addEventListener('change', function() {
                if (this.checked) {
                    if (wForm) wForm.style.display = 'none';
                    if (wrapPredefCreadas) wrapPredefCreadas.style.display = 'block';
                    if (wExp) wExp.style.display = 'block';
                    if (formulaPredefSelect) {
                        var opt = formulaPredefSelect.options[formulaPredefSelect.selectedIndex];
                        var nomInp = document.getElementById('formula_nombre_input');
                        var v = opt ? opt.value : '';
                        if (v && v !== '__NUEVA__') {
                            formulaArea.value = textoInternoANombres(v);
                            if (nomInp) nomInp.value = opt.dataset.nombre || '';
                        } else {
                            formulaArea.value = '';
                            if (nomInp) nomInp.value = '';
                        }
                        syncFormula();
                        var fid = (opt && opt.dataset.formulasId) ? parseInt(opt.dataset.formulasId, 10) : 1;
                        if (formulasIdHidden) formulasIdHidden.value = fid;
                    }
                } else {
                    if (wForm) wForm.style.display = 'block';
                    if (wrapPredefCreadas) wrapPredefCreadas.style.display = 'none';
                    if (wExp) wExp.style.display = 'none';
                    if (formulasIdHidden) formulasIdHidden.value = '1';
                    if (formulasIdSelect) formulasIdSelect.value = '1';
                    if (formulaPredefSelect) formulaPredefSelect.selectedIndex = 0;
                }
            });
            if (formulaPredefSelect) {
                formulaPredefSelect.addEventListener('change', function() {
                    var opt = this.options[this.selectedIndex];
                    var nomInp = document.getElementById('formula_nombre_input');
                    var v = opt ? opt.value : '';
                    if (v && v !== '__NUEVA__') {
                        formulaArea.value = textoInternoANombres(v);
                        if (nomInp) nomInp.value = opt.dataset.nombre || '';
                        if (formulasIdHidden) formulasIdHidden.value = opt.dataset.formulasId || '1';
                    } else {
                        formulaArea.value = '';
                        if (nomInp) nomInp.value = '';
                        if (formulasIdHidden) formulasIdHidden.value = '1';
                    }
                    syncFormula();
                });
            }
            if (formulasIdSelect) {
                formulasIdSelect.addEventListener('change', function() {
                    if (formulasIdHidden && !document.getElementById('es_calculada').checked) {
                        formulasIdHidden.value = this.value;
                    }
                });
            }
            function textoConNombresAInterno(texto) {
                var t = (texto || '').trim().replace(/\s+/g, ' ');
                for (var nom in refsNombreToCid) {
                    if (!refsNombreToCid.hasOwnProperty(nom)) continue;
                    var re = new RegExp('\\[' + nom.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\]', 'g');
                    t = t.replace(re, refsNombreToCid[nom]);
                }
                return t;
            }
            function textoInternoANombres(texto) {
                var t = texto || '';
                for (var cid in refsCidToNombre) {
                    if (!refsCidToNombre.hasOwnProperty(cid)) continue;
                    t = t.split(cid).join('[' + refsCidToNombre[cid] + ']');
                }
                t = t.replace(/\b1\b/g, '[valor]');
                return t;
            }
            function actualizarNombreFormula() {
                if (!formulaValorRefs) return;
                var sel = document.getElementById('formula_predefinida_select');
                var nomInput = document.getElementById('formula_nombre_input');
                var nombre = '';
                var opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
                var esNueva = opt && opt.value === '__NUEVA__';
                if (sel && sel.value && sel.selectedIndex > 0 && !esNueva) {
                    nombre = (opt && opt.dataset.nombre) ? opt.dataset.nombre : (opt ? opt.text : '');
                    if (nomInput) nomInput.value = nombre;
                } else if (nomInput) {
                    nombre = (nomInput.value || '').trim();
                }
                formulaValorRefs.textContent = nombre || '—';
            }
            function syncFormula() {
                if (formulaInput && formulaArea) {
                    formulaInput.value = textoConNombresAInterno(formulaArea.value);
                }
                actualizarNombreFormula();
                actualizarMathPreview();
            }
            function actualizarMathPreview() {
                if (!mathDisplay || !formulaArea) return;
                var expr = (formulaArea.value || '').trim();
                if (!expr) { mathDisplay.innerHTML = '—'; mathDisplay.classList.remove('formula-math-error'); return; }
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
                        if (op === '*') left = left + ' \\times ' + right;
                        else left = '\\frac{' + left + '}{' + right + '}';
                        skipSp();
                    }
                    return left;
                }
                function parseFactor() {
                    skipSp();
                    if (i >= s.length) return '';
                    if (s[i] === '(') {
                        i++; var r = parseExpr(); if (s[i] === ')') i++;
                        return '(' + r + ')';
                    }
                    var start = i;
                    if (/[a-zA-Z0-9_]/.test(s[i]) || s[i] === '[') {
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
                    return '';
                }
                return parseExpr();
            }
            function insertAtCursor(text) {
                if (!formulaArea) return;
                var start = formulaArea.selectionStart, end = formulaArea.selectionEnd, val = formulaArea.value;
                var prefix = (start > 0 && val[start-1] && /[0-9a-zA-Z_\]]/.test(val[start-1])) ? ' ' : '';
                var newVal = val.substring(0, start) + prefix + text + val.substring(end);
                formulaArea.value = newVal;
                formulaArea.selectionStart = formulaArea.selectionEnd = start + prefix.length + text.length;
                formulaArea.focus();
                syncFormula();
            }
            if (formulaArea) {
                formulaArea.addEventListener('input', syncFormula);
            }
            function normalizarExpr(s) {
                return (s || '').trim().replace(/\s+/g, ' ');
            }
            function aplicarFormulaInicial() {
                setTimeout(function() {
                    var calc = document.getElementById('es_calculada');
                    if (calc && calc.checked && formulaPredefSelect && formulaArea) {
                        var expr = normalizarExpr(textoConNombresAInterno(formulaArea.value));
                        var nomInp = document.getElementById('formula_nombre_input');
                        var found = false;
                        for (var i = 2; i < formulaPredefSelect.options.length; i++) {
                            var opt = formulaPredefSelect.options[i];
                            if (opt.value && opt.value !== '__NUEVA__' && normalizarExpr(opt.value) === expr) {
                                formulaPredefSelect.selectedIndex = i;
                                if (nomInp) nomInp.value = opt.dataset.nombre || '';
                                found = true;
                                break;
                            }
                        }
                        if (!found && formulaArea.value && expr) {
                            formulaPredefSelect.selectedIndex = 1;
                        }
                    }
                    if (formulasIdHidden && formulasIdSelect && document.getElementById('es_calculada')) {
                        if (!document.getElementById('es_calculada').checked) {
                            formulasIdHidden.value = formulasIdSelect.value;
                        }
                    }
                    actualizarNombreFormula();
                    actualizarMathPreview();
                }, 100);
            }
            aplicarFormulaInicial();
            document.addEventListener('secModalFormFilled', function() {
                syncFormula();
                aplicarFormulaInicial();
            });
            document.querySelectorAll('.formula-ref').forEach(function(el) {
                var insertar = '[' + (el.dataset.nombre || '') + ']';
                el.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('text/plain', insertar);
                    e.dataTransfer.effectAllowed = 'copy';
                });
                el.addEventListener('click', function() {
                    insertAtCursor(insertar);
                });
            });
            if (formulaArea) {
                formulaArea.addEventListener('dragover', function(e) { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; });
                formulaArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    var text = e.dataTransfer.getData('text/plain');
                    if (text) insertAtCursor(text);
                });
            }
            document.querySelectorAll('.formula-op').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    insertAtCursor(btn.dataset.op || '');
                });
            });
            (document.getElementById('form_secitem') || formulaInput?.closest('form'))?.addEventListener('submit', function(e) {
                e.preventDefault();
                var f = this;
                var calc = document.getElementById('es_calculada');
                var predefVal = formulaPredefSelect ? (formulaPredefSelect.options[formulaPredefSelect.selectedIndex]?.value || '') : '';
                if (calc && calc.checked && predefVal === '') {
                    if (formulaInput) formulaInput.value = '';
                    if (formulaArea) formulaArea.value = '';
                } else if (calc && calc.checked && formulaInput && formulaArea) {
                    // Enviar expresión con nombres [Hematocrito], [Eritrocitos] para guardar en BD, no c_8, c_2
                    formulaInput.value = (formulaArea.value || '').trim();
                } else {
                    syncFormula();
                }
                if (calc && calc.checked && formulaPredefSelect && formulasIdHidden) {
                    var opt = formulaPredefSelect.options[formulaPredefSelect.selectedIndex];
                    formulasIdHidden.value = (opt && opt.dataset.formulasId) ? opt.dataset.formulasId : '1';
                } else if (calc && !calc.checked && formulasIdSelect && formulasIdHidden) {
                    formulasIdHidden.value = formulasIdSelect.value;
                }
                if (calc && !calc.checked && formulaInput) {
                    formulaInput.value = '';
                }
                f.submit();
            });
            var formulaNombreInput = document.getElementById('formula_nombre_input');
            if (formulaNombreInput) {
                formulaNombreInput.addEventListener('input', actualizarNombreFormula);
                formulaNombreInput.addEventListener('change', actualizarNombreFormula);
            }
            var btnGuardarFormula = document.getElementById('btn_guardar_formula');
            if (btnGuardarFormula && formulaArea && formulaInput) {
                btnGuardarFormula.addEventListener('click', function() {
                    var exprConNombres = (formulaArea.value || '').trim();
                    if (!exprConNombres) { if (typeof showToast === 'function') showToast('Escriba una fórmula primero', 'error'); return; }
                    var nomInput = document.getElementById('formula_nombre_input');
                    var nombre = nomInput ? (nomInput.value || '').trim() : '';
                    if (!nombre) { if (typeof showToast === 'function') showToast('Ingrese el nombre de la fórmula en el campo indicado', 'error'); if (nomInput) nomInput.focus(); return; }
                    var formData = new FormData();
                    formData.append('nombre_formula', nombre);
                    formData.append('formula_expresion', exprConNombres);
                    var predefOpt = formulaPredefSelect && formulaPredefSelect.selectedIndex > 0 ? formulaPredefSelect.options[formulaPredefSelect.selectedIndex] : null;
                    var fid = (predefOpt && predefOpt.dataset.formulasId) ? predefOpt.dataset.formulasId : '0';
                    formData.append('formulas_id', fid);
                    var csrfInput = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
                    var csrfName = (csrfInput && csrfInput.name) ? csrfInput.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
                    var csrfVal = (csrfInput && csrfInput.value) ? csrfInput.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
                    if (csrfVal) formData.append(csrfName, csrfVal);
                    btnGuardarFormula.disabled = true;
                    var fetchHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
                    if (csrfVal) fetchHeaders['X-CSRF-TOKEN'] = csrfVal;
                    fetch('<?= site_url('labotests/saveformula') ?>', {
                        method: 'POST',
                        body: formData,
                        headers: fetchHeaders
                    }).then(function(r) { return r.json(); }).then(function(d) {
                        btnGuardarFormula.disabled = false;
                        if (d.success) {
                            if (d.csrf_token && d.csrf_name) {
                                window.CI_CSRF_TOKEN = d.csrf_token;
                                window.CI_CSRF_TOKEN_NAME = d.csrf_name;
                                document.querySelectorAll('input[name="' + d.csrf_name + '"], input[name="csrf_test_name"]').forEach(function(inp) {
                                    inp.name = d.csrf_name;
                                    inp.value = d.csrf_token;
                                });
                                var formSec = document.getElementById('form_secitem');
                                if (formSec) {
                                    var inp = formSec.querySelector('input[name="' + d.csrf_name + '"]') || formSec.querySelector('input[name*="csrf"]');
                                    if (inp) { inp.value = d.csrf_token; }
                                }
                            }
                            var predefSel = document.getElementById('formula_predefinida_select');
                            if (predefSel) {
                                var exprVal = (formulaArea && formulaArea.value) ? formulaArea.value.trim() : '';
                                var opt = predefSel.selectedIndex > 0 ? predefSel.options[predefSel.selectedIndex] : null;
                                if (opt && fid !== '0') {
                                    opt.value = exprVal;
                                    opt.textContent = nombre;
                                    opt.dataset.nombre = nombre;
                                    opt.dataset.formulasId = (d.formulas_id || fid);
                                } else {
                                    opt = document.createElement('option');
                                    opt.value = exprVal;
                                    opt.textContent = nombre;
                                    opt.dataset.nombre = nombre;
                                    opt.dataset.formulasId = (d.formulas_id || 1);
                                    predefSel.appendChild(opt);
                                    predefSel.selectedIndex = predefSel.options.length - 1;
                                }
                            }
                            if (formulaNombreInput) formulaNombreInput.value = nombre;
                            actualizarNombreFormula();
                            if (typeof showToast === 'function') showToast(fid !== '0' ? 'Fórmula actualizada correctamente' : 'Fórmula guardada correctamente', 'success');
                        } else {
                            if (typeof showToast === 'function') showToast(d.message || 'Error al guardar', 'error');
                        }
                    }).catch(function() {
                        btnGuardarFormula.disabled = false;
                        if (typeof showToast === 'function') showToast('Error al guardar', 'error');
                    });
                });
            }
            var btnEliminarFormula = document.getElementById('btn_eliminar_formula');
            if (btnEliminarFormula && formulaPredefSelect) {
                btnEliminarFormula.addEventListener('click', function() {
                    var opt = formulaPredefSelect.options[formulaPredefSelect.selectedIndex];
                    if (!opt || formulaPredefSelect.selectedIndex < 2 || !opt.value || opt.value === '__NUEVA__') {
                        if (typeof showToast === 'function') showToast('Seleccione una fórmula creada para eliminar', 'error');
                        return;
                    }
                    var fid = opt.dataset.formulasId ? parseInt(opt.dataset.formulasId, 10) : 0;
                    if (!fid || fid < 2) {
                        if (typeof showToast === 'function') showToast('No se puede eliminar esta fórmula', 'error');
                        return;
                    }
                    if (!confirm('¿Eliminar la fórmula "' + (opt.textContent || '') + '"? Solo es posible si no está en uso.')) return;
                    var formData = new FormData();
                    var csrfInput = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
                    var csrfName = (csrfInput && csrfInput.name) ? csrfInput.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
                    var csrfVal = (csrfInput && csrfInput.value) ? csrfInput.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
                    if (csrfVal) formData.append(csrfName, csrfVal);
                    var fetchHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
                    if (csrfVal) fetchHeaders['X-CSRF-TOKEN'] = csrfVal;
                    btnEliminarFormula.disabled = true;
                    fetch('<?= site_url('labotests/deleteformula/') ?>' + fid, {
                        method: 'POST',
                        body: formData,
                        headers: fetchHeaders
                    }).then(function(r) { return r.json(); }).then(function(d) {
                        btnEliminarFormula.disabled = false;
                        if (d.success) {
                            opt.remove();
                            var formulasIdSelect = document.getElementById('formulas_id');
                            if (formulasIdSelect) {
                                var opts = formulasIdSelect.querySelectorAll('option[value="' + fid + '"]');
                                opts.forEach(function(o) { o.remove(); });
                            }
                            if (formulaPredefSelect.selectedIndex >= formulaPredefSelect.options.length) {
                                formulaPredefSelect.selectedIndex = Math.max(0, formulaPredefSelect.options.length - 1);
                            }
                            syncFormula();
                            if (typeof showToast === 'function') showToast(d.message || 'Fórmula eliminada', 'success');
                            if (d.csrf_token && d.csrf_name) {
                                window.CI_CSRF_TOKEN = d.csrf_token;
                                window.CI_CSRF_TOKEN_NAME = d.csrf_name;
                                document.querySelectorAll('input[name="csrf_test_name"]').forEach(function(inp) {
                                    inp.name = d.csrf_name;
                                    inp.value = d.csrf_token;
                                });
                            }
                        } else {
                            if (typeof showToast === 'function') showToast(d.message || 'No se pudo eliminar', 'error');
                        }
                    }).catch(function() {
                        btnEliminarFormula.disabled = false;
                        if (typeof showToast === 'function') showToast('Error al eliminar', 'error');
                    });
                });
            }
        })();
        </script>
        <?= form_close() ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function() {
            var prianacategoriaId = <?= (int)($labotests_info->prianacategoria_id ?? 0) ?>;
            var modalEl = document.getElementById('modalEditarSec');
            var modalTitle = document.getElementById('modalEditarSecTitle');
            var btnSubmit = document.getElementById('btn_submit_sec');
            if (!modalEl) return;
            var modal = typeof bootstrap !== 'undefined' ? new bootstrap.Modal(modalEl) : null;
            function openModal() {
                if (modal) modal.show();
            }
            function fillFormSec(data) {
                data = data || {};
                var id = (data.secanacategoria_id || 0) | 0;
                document.getElementById('secanacategoria_id_input').value = id;
                var byName = function(n) { return document.querySelector('#form_secitem [name="' + n + '"]'); };
                var set = function(n, v) { var el = byName(n); if (el) el.value = (v !== undefined && v !== null) ? String(v) : ''; };
                set('nombre', data.nombre);
                set('paciente_id', data.paciente_id);
                set('sexo', data.sexo);
                set('valor_min', data.valor_min);
                set('valor_max', data.valor_max);
                set('umedida', data.umedida);
                set('opcion_id', data.opcion_id);
                var calc = document.getElementById('es_calculada');
                var fid = (data.formulas_id || 1) | 0;
                if (calc) calc.checked = fid > 1;
                document.getElementById('formulas_id_hidden').value = fid;
                var formulasIdSelect = document.getElementById('formulas_id');
                if (formulasIdSelect && fid <= 1) formulasIdSelect.value = '1';
                var wrapForm = document.getElementById('wrap_formulas_id');
                var wrapExp = document.getElementById('wrap_formula_expresion');
                var wrapPredef = document.getElementById('wrap_formula_predefinida_creadas');
                if (wrapForm) wrapForm.style.display = fid > 1 ? 'none' : 'block';
                if (wrapExp) wrapExp.style.display = fid > 1 ? 'block' : 'none';
                if (wrapPredef) wrapPredef.style.display = fid > 1 ? 'block' : 'none';
                var formulaArea = document.getElementById('formula_area');
                var formulaInput = document.getElementById('formula_expresion');
                var formulaPredefSelect = document.getElementById('formula_predefinida_select');
                if (formulaArea) formulaArea.value = data.formula_para_textarea || '';
                if (formulaInput) formulaInput.value = '';
                var nomInp = document.getElementById('formula_nombre_input');
                if (nomInp) nomInp.value = data.formula_nombre || '';
                if (formulaPredefSelect) formulaPredefSelect.selectedIndex = 0;
                document.dispatchEvent(new CustomEvent('secModalFormFilled'));
            }
            function clearFormSec() {
                fillFormSec({
                    secanacategoria_id: 0,
                    nombre: '',
                    paciente_id: 3,
                    sexo: 'ambos',
                    valor_min: '',
                    valor_max: '',
                    umedida: '',
                    formulas_id: 1,
                    opcion_id: 3,
                    formula_para_textarea: '',
                    formula_nombre: ''
                });
            }
            document.getElementById('btn_agregar_sec').addEventListener('click', function() {
                if (modalTitle) modalTitle.textContent = 'Agregar sub-clase';
                if (btnSubmit) btnSubmit.textContent = 'Agregar';
                clearFormSec();
                openModal();
            });
            document.querySelectorAll('.btn-editar-sec').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var tr = btn.closest('tr');
                    var dataStr = tr && tr.getAttribute('data-sec');
                    if (!dataStr) return;
                    var data = {};
                    try { data = JSON.parse(dataStr); } catch (e) { return; }
                    if (modalTitle) modalTitle.textContent = 'Editar sub-clase';
                    if (btnSubmit) btnSubmit.textContent = 'Actualizar';
                    fillFormSec(data);
                    openModal();
                });
            });
            var tablaSub = document.getElementById('tabla_sub_items');
            if (tablaSub) {
                function getOrderIds() {
                    var tbody = tablaSub.querySelector('tbody');
                    if (!tbody) return [];
                    var ids = [];
                    tbody.querySelectorAll('tr[data-secanacategoria-id]').forEach(function(tr) {
                        var id = parseInt(tr.getAttribute('data-secanacategoria-id'), 10);
                        if (id > 0) ids.push(id);
                    });
                    return ids;
                }
                function guardarOrden() {
                    var order = getOrderIds();
                    if (order.length === 0) return;
                    var fd = new FormData();
                    fd.append('prianacategoria_id', prianacategoriaId);
                    for (var i = 0; i < order.length; i++) fd.append('order[]', order[i]);
                    var csrf = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
                    var csrfName = (csrf && csrf.name) ? csrf.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
                    var csrfVal = (csrf && csrf.value) ? csrf.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
                    if (csrfVal) fd.append(csrfName, csrfVal);
                    var headers = { 'X-Requested-With': 'XMLHttpRequest' };
                    if (csrfVal) headers['X-CSRF-TOKEN'] = csrfVal;
                    fetch('<?= site_url('labotests/orderSecItems') ?>', {
                        method: 'POST',
                        body: fd,
                        headers: headers
                    }).then(function(r) { return r.json(); }).then(function(d) {
                        if (d.success) {
                            if (typeof showToast === 'function') showToast('Orden guardado', 'success');
                            if (d.csrf_token && d.csrf_name) {
                                window.CI_CSRF_TOKEN = d.csrf_token;
                                window.CI_CSRF_TOKEN_NAME = d.csrf_name;
                                document.querySelectorAll('input[name="csrf_test_name"], input[name*="csrf"]').forEach(function(inp) {
                                    inp.name = d.csrf_name;
                                    inp.value = d.csrf_token;
                                });
                            }
                        } else if (typeof showToast === 'function') showToast(d.message || 'Error al guardar orden', 'error');
                    }).catch(function() {
                        if (typeof showToast === 'function') showToast('Error al guardar orden', 'error');
                    });
                }
                function moverFila(tr, direccion) {
                    var tbody = tablaSub.querySelector('tbody');
                    if (!tbody) return;
                    var rows = [].slice.call(tbody.querySelectorAll('tr[data-secanacategoria-id]'));
                    var idx = rows.indexOf(tr);
                    if (idx < 0) return;
                    var otroIdx = direccion === -1 ? idx - 1 : idx + 1;
                    if (otroIdx < 0 || otroIdx >= rows.length) return;
                    if (direccion === -1) {
                        tbody.insertBefore(tr, rows[otroIdx]);
                    } else {
                        tbody.insertBefore(tr, rows[otroIdx].nextSibling);
                    }
                    guardarOrden();
                }
                tablaSub.addEventListener('click', function(e) {
                    var subir = e.target.closest('.btn-sec-subir');
                    var bajar = e.target.closest('.btn-sec-bajar');
                    var tr = (subir || bajar) && (subir || bajar).closest('tr');
                    if (!tr) return;
                    if (subir) { e.preventDefault(); moverFila(tr, -1); }
                    if (bajar) { e.preventDefault(); moverFila(tr, 1); }
                });
                var tbody = tablaSub.querySelector('tbody');
                if (tbody && typeof Sortable !== 'undefined') {
                    new Sortable(tbody, {
                        handle: '.sec-drag-handle',
                        animation: 150,
                        ghostClass: 'table-secondary',
                        onEnd: function() { guardarOrden(); }
                    });
                }
            }
            <?php if ($editar_sec ?? 0): ?>
            var editarSecDataInicial = <?= json_encode([
                'secanacategoria_id' => (int)($editar_sec ?? 0),
                'nombre' => $editar_sec_data['nombre'] ?? '',
                'paciente_id' => (int)($editar_sec_data['paciente_id'] ?? 3),
                'sexo' => $editar_sec_data['sexo'] ?? 'ambos',
                'valor_min' => $editar_sec_data['valor_min'] ?? '',
                'valor_max' => $editar_sec_data['valor_max'] ?? '',
                'umedida' => $editar_sec_data['umedida'] ?? '',
                'formulas_id' => (int)($editar_sec_data['formulas_id'] ?? 1),
                'opcion_id' => (int)($editar_sec_data['opcion_id'] ?? 3),
                'formula_para_textarea' => $formulaParaTextarea ?? '',
                'formula_nombre' => $formulaNombreInicial ?? '',
            ]) ?>;
            if (modalTitle) modalTitle.textContent = 'Editar sub-clase';
            if (btnSubmit) btnSubmit.textContent = 'Actualizar';
            fillFormSec(editarSecDataInicial);
            openModal();
            <?php endif; ?>
        })();
        </script>
    </div>
</div>
<?php else: ?>
<div class="card mt-3">
    <div class="card-header"><strong>Valores de referencia (prueba no compuesta)</strong></div>
    <div class="card-body">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Población</th>
                    <th>Sexo</th>
                    <th>Valor mín</th>
                    <th>Valor máx</th>
                    <th>U. medida</th>
                    <th>Fórmula</th>
                    <th>Tipo</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pobMap = [];
                foreach ($poblaciones ?? [] as $p) {
                    $pobMap[(int)$p['id_poblacion']] = $p['name'] ?? '';
                }
                $sexoMap = ['ambos' => 'Ambos', 'masculino' => 'Masculino', 'femenino' => 'Femenino'];
                foreach ($priresultados ?? [] as $pr): ?>
                <tr>
                    <td><?= esc($pobMap[(int)($pr['id_poblacion'] ?? 0)] ?? $pr['id_poblacion'] ?? '') ?></td>
                    <td><?= esc($sexoMap[$pr['sexo'] ?? 'ambos'] ?? 'Ambos') ?></td>
                    <td><?= esc($pr['valor_min'] ?? '') ?></td>
                    <td><?= esc($pr['valor_max'] ?? '') ?></td>
                    <td><?= esc($pr['umedida'] ?? '') ?></td>
                    <td><?= esc($formulas[(int)($pr['formulas_id'] ?? 0)] ?? '') ?></td>
                    <td><?= esc($opciones[(int)($pr['opcion_id'] ?? 0)] ?? '') ?></td>
                    <td class="text-center">
                        <a href="<?= site_url("labotests/detail/{$labotests_info->prianacategoria_id}") ?>?editarpri=<?= (int)($pr['priresultados_id'] ?? 0) ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></a>
                        <a href="<?= site_url("labotests/deletepriresultado/" . (int)($pr['priresultados_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Eliminar estos valores?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <hr>
        <h6 class="mb-3"><?= empty($priresultados) ? 'Agregar valores de referencia' : 'Agregar por población' ?></h6>
        <?= form_open('labotests/savepriresultado', ['class' => 'border p-3 rounded']) ?>
        <input type="hidden" name="prianacategoria_id" value="<?= (int)($labotests_info->prianacategoria_id ?? 0) ?>">
        <input type="hidden" name="priresultados_id" value="<?= (int)($editar_pri ?? 0) ?>">
        <div class="row">
            <div class="col-md-2 mb-2">
                <label class="form-label">Población</label>
                <select name="id_poblacion" class="form-control form-control-sm">
                    <?php foreach ($poblaciones ?? [] as $p): ?>
                    <option value="<?= (int)$p['id_poblacion'] ?>" <?= ((int)($editar_pri_data['id_poblacion'] ?? 3) === (int)$p['id_poblacion']) ? 'selected' : '' ?>><?= esc($p['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Sexo</label>
                <select name="sexo" class="form-control form-control-sm">
                    <option value="ambos" <?= (($editar_pri_data['sexo'] ?? 'ambos') === 'ambos') ? 'selected' : '' ?>>Ambos</option>
                    <option value="masculino" <?= (($editar_pri_data['sexo'] ?? '') === 'masculino') ? 'selected' : '' ?>>Masculino</option>
                    <option value="femenino" <?= (($editar_pri_data['sexo'] ?? '') === 'femenino') ? 'selected' : '' ?>>Femenino</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Valor mín</label>
                <input type="text" name="valor_min" class="form-control form-control-sm" value="<?= esc($editar_pri_data['valor_min'] ?? '') ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Valor máx</label>
                <input type="text" name="valor_max" class="form-control form-control-sm" value="<?= esc($editar_pri_data['valor_max'] ?? '') ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">U. medida</label>
                <input type="text" name="umedida" class="form-control form-control-sm" value="<?= esc($editar_pri_data['umedida'] ?? '') ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Fórmula</label>
                <select name="formulas_id" class="form-control form-control-sm">
                    <?php
                    $priFormulasId = (int)($editar_pri_data['formulas_id'] ?? 1);
                    $priFormulasIdSel = ($formulas_id_canonical ?? [])[$priFormulasId] ?? $priFormulasId;
                    foreach ($formulas_creadas ?? $formulas ?? [] as $fid => $fname):
                    ?>
                    <option value="<?= $fid ?>" <?= $priFormulasIdSel === $fid ? 'selected' : '' ?>><?= esc($fname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Tipo resultado</label>
                <select name="opcion_id" class="form-control form-control-sm">
                    <?php foreach ($opciones ?? [] as $oid => $oname): ?>
                    <option value="<?= $oid ?>" <?= ((int)($editar_pri_data['opcion_id'] ?? 3) === $oid) ? 'selected' : '' ?>><?= esc($oname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-2">
            <button type="submit" class="btn btn-primary btn-sm"><?= ($editar_pri ?? 0) ? 'Actualizar' : 'Agregar' ?></button>
            <?php if ($editar_pri ?? 0): ?>
            <a href="<?= site_url("labotests/detail/{$labotests_info->prianacategoria_id}") ?>" class="btn btn-secondary btn-sm">Cancelar</a>
            <?php endif; ?>
        </div>
        <?= form_close() ?>
    </div>
</div>
<?php endif; ?>

<?= view('partial/footer') ?>
