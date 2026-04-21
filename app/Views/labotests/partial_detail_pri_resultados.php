<?php
/**
 * Partial: Valores de referencia (prueba no compuesta).
 * Variables: labotests_info, poblaciones, priresultados, formulas, opciones,
 *            editar_pri, editar_pri_data, formulas_id_canonical, formulas_creadas
 */
$pobMap = [];
foreach ($poblaciones ?? [] as $p) {
    $pobMap[(int) $p['id_poblacion']] = $p['name'] ?? '';
}
$sexoMap = ['ambos' => 'Ambos', 'masculino' => 'Masculino', 'femenino' => 'Femenino'];
?>
<div class="card mt-3">
    <div class="card-header"><strong>Valores de referencia (prueba no compuesta)</strong></div>
    <div class="card-body">
        <div class="table-responsive">
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
                <?php foreach ($priresultados ?? [] as $pr): ?>
                <?php
                $rowDataPri = [
                    'priresultados_id' => (int) ($pr['priresultados_id'] ?? 0),
                    'id_poblacion' => (int) ($pr['id_poblacion'] ?? 15),
                    'sexo' => $pr['sexo'] ?? 'ambos',
                    'valor_min' => $pr['valor_min'] ?? '',
                    'valor_max' => $pr['valor_max'] ?? '',
                    'umedida' => $pr['umedida'] ?? '',
                    'formulas_id' => (int) ($pr['formulas_id'] ?? 1),
                    'opcion_id' => (int) ($pr['opcion_id'] ?? 3),
                ];
                ?>
                <tr>
                    <td><?= esc($pobMap[(int) ($pr['id_poblacion'] ?? 0)] ?? $pr['id_poblacion'] ?? '') ?></td>
                    <td><?= esc($sexoMap[$pr['sexo'] ?? 'ambos'] ?? 'Ambos') ?></td>
                    <td><?= esc($pr['valor_min'] ?? '') ?></td>
                    <td><?= esc($pr['valor_max'] ?? '') ?></td>
                    <td><?= esc($pr['umedida'] ?? '') ?></td>
                    <td><?= esc($formulas[(int) ($pr['formulas_id'] ?? 0)] ?? '') ?></td>
                    <td><?= esc($opciones[(int) ($pr['opcion_id'] ?? 0)] ?? '') ?></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar-pri" data-pri="<?= htmlspecialchars(json_encode($rowDataPri), ENT_QUOTES, 'UTF-8') ?>" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <a href="<?= site_url("labotests/deletepriresultado/" . (int) ($pr['priresultados_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return uiConfirmLink(this, '¿Eliminar estos valores?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
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
                                    <option value="ambos">Ambos</option>
                                    <option value="masculino">Masculino</option>
                                    <option value="femenino">Femenino</option>
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
                                <select name="opcion_id" class="form-control form-control-sm">
                                    <?php foreach ($opciones ?? [] as $oid => $oname): ?>
                                    <option value="<?= $oid ?>"><?= esc($oname) ?></option>
                                    <?php endforeach; ?>
                                </select>
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
<script>
(function() {
    var modalEl = document.getElementById('modalEditarPri');
    if (!modalEl) return;
    var modal = (typeof bootstrap !== 'undefined') ? new bootstrap.Modal(modalEl) : null;
    var modalTitle = document.getElementById('modalEditarPriTitle');
    var btnSubmit = document.getElementById('btn_submit_pri');
    var form = document.getElementById('form_priresultado');
    var calcCb = document.getElementById('pri_es_calculada');
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

    function fillFormPri(data) {
        data = data || {};
        if (inputId) inputId.value = String((data.priresultados_id || 0) | 0);
        setField('id_poblacion', (data.id_poblacion || 15) | 0);
        setField('sexo', data.sexo || 'ambos');
        setField('valor_min', data.valor_min || '');
        setField('valor_max', data.valor_max || '');
        setField('umedida', data.umedida || '');
        setField('opcion_id', (data.opcion_id || 3) | 0);

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
            formulas_id: 1,
            opcion_id: 3
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
