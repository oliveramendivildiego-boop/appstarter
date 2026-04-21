<?= $this->extend('layouts/main') ?>
<?= $this->section('head_extra') ?>
<script src="<?= base_url('js/vendor/jquery.validate.min.js') ?>"></script>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_labotests'), 'url' => site_url('labotests')],
        ['label' => $labotests_master->name ?? '', 'url' => site_url('labotests/view/' . $labotests_namecate)],
        ['label' => ($labotests_info->name ?? '') . ' - ' . lang('Labotests.labotests_config'), 'url' => null],
    ],
    'right' => '<a href="' . site_url('labotests/opciones') . '" class="btn btn-outline-primary btn-sm" title="Administrar tipos de resultado"><i class="fa-solid fa-list-check me-1"></i>Tipos resultado</a>'
]) ?>

<?= form_open('labotests/savesub', ['id' => 'detail_form']) ?>
<input type="hidden" id="prianacategoria_id" name="prianacategoria_id" value="<?= (int)($labotests_info->prianacategoria_id ?? 0) ?>">
<input type="hidden" id="anacategoria_id" name="anacategoria_id" value="<?= (int)($labotests_info->anacategoria_id ?? 0) ?>">

<?= view('labotests/partial_detail_config', ['labotests_info' => $labotests_info, 'tipos_muestra' => $tipos_muestra ?? [], 'metodos_prueba' => $metodos_prueba ?? []]) ?>

<?= form_close() ?>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fa-solid fa-file-arrow-up me-1"></i>Exportar / Importar configuración</strong>
        <span class="badge bg-secondary"><?= ($compleja ?? 0) ? 'Prueba compuesta' : 'Prueba no compuesta' ?></span>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            Exporta la configuración actual de esta prueba en JSON o importa un archivo para reemplazar sus filas de detalle.
        </p>
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <a href="<?= site_url('labotests/exportdetailconfig/' . (int)($labotests_info->prianacategoria_id ?? 0)) ?>" class="btn btn-outline-primary w-100">
                    <i class="fa-solid fa-download me-1"></i>Exportar JSON
                </a>
            </div>
            <div class="col-md-8">
                <?= form_open_multipart('labotests/importdetailconfig/' . (int)($labotests_info->prianacategoria_id ?? 0), ['class' => 'row g-2', 'id' => 'form_import_detail_config']) ?>
                <div class="col-md-8">
                    <input type="file" name="config_file" class="form-control" accept=".json,application/json" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fa-solid fa-upload me-1"></i>Importar JSON
                    </button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var importForm = document.getElementById('form_import_detail_config');
    if (!importForm) return;
    importForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var msg = 'Esto reemplazará la configuración actual de esta prueba. ¿Continuar?';
        if (typeof uiConfirm === 'function') {
            uiConfirm(msg, 'Confirmar importación').then(function(ok) {
                if (ok) importForm.submit();
            });
            return;
        }
        if (window.confirm(msg)) {
            importForm.submit();
        }
    });
})();
</script>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fa-solid fa-vial me-1"></i>Consumo automatico de reactivos</strong>
        <span class="badge bg-info text-dark">Por analisis</span>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            Configura que reactivo se descuenta al guardar resultados de este analisis y el consumo por defecto.
        </p>

        <?= form_open('labotests/savereactivoconsumo', ['class' => 'row g-2 align-items-end mb-3']) ?>
        <input type="hidden" name="prianacategoria_id" value="<?= (int) ($labotests_info->prianacategoria_id ?? 0) ?>">
        <div class="col-md-5">
            <label class="form-label">Reactivo</label>
            <select name="reactivo_id" class="form-select" required>
                <option value="">Seleccionar...</option>
                <?php foreach (($reactivos_catalogo ?? []) as $rx): ?>
                    <option value="<?= (int) ($rx['reactivo_id'] ?? 0) ?>">
                        <?= esc($rx['nombre'] ?? '') ?> (Stock: <?= (int) ($rx['stock_actual'] ?? 0) ?> <?= esc($rx['unidad_base'] ?? $rx['unidad'] ?? '') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Consumo</label>
            <input type="number" min="1" step="1" class="form-control" name="consumo_default" value="1" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Politica de lote</label>
            <select name="lote_policy" class="form-select" required>
                <?php foreach (($reactivo_lote_policies ?? []) as $key => $label): ?>
                    <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success w-100">
                <i class="fa-solid fa-plus me-1"></i>Agregar
            </button>
        </div>
        <?= form_close() ?>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reactivo</th>
                        <th class="text-center">Consumo</th>
                        <th>Politica lote</th>
                        <th class="text-center">Accion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($reactivos_consumo_config ?? [])): ?>
                        <?php foreach (($reactivos_consumo_config ?? []) as $cfg): ?>
                            <tr>
                                <td><?= esc($cfg['reactivo_nombre'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?= (int) ($cfg['consumo_default'] ?? 1) ?> <?= esc($cfg['unidad_base'] ?? '') ?>
                                </td>
                                <td><?= esc(strtoupper((string) ($cfg['lote_policy'] ?? 'fefo'))) ?></td>
                                <td class="text-center">
                                    <?= form_open('labotests/deletereactivoconsumo/' . (int) ($cfg['config_id'] ?? 0), ['class' => 'd-inline']) ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminar esta configuracion?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <?= form_close() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">Sin configuraciones de consumo automatico.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($compleja ?? 0): ?>
<?php
$refsPorNombre = [];   // nombre -> c_id o constante (para guardado)
$refsCidToNombre = []; // c_id -> nombre (para carga)
foreach ($sub_items ?? [] as $s) {
    if (($editar_sec ?? 0) && (int)($s['secanacategoria_id'] ?? 0) === $editar_sec) continue;
    if (! empty($s['es_separador'])) {
        continue;
    }
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
<div class="card card-tabla-sub-items mt-3">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <strong>Valores de sub-clases (prueba compuesta)</strong>
        <?php if (! empty($sub_items)): ?>
        <span class="badge bg-secondary"><?= count($sub_items) ?> filas</span>
        <span class="text-muted small">Agrupadas por población; cada analito puede tener una fila por grupo y sexo.</span>
        <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="btn_abrir_modal_orden_sec" title="Lista compacta para reordenar más rápido">
            <i class="fa-solid fa-list-ol me-1"></i> Orden rápido
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger" id="btn_eliminar_sec_seleccionadas" disabled>
            <i class="fa-solid fa-trash me-1"></i>Eliminar seleccionadas
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <div class="wrapper-tabla-sub-items">
        <table class="table table-bordered" id="tabla_sub_items">
            <thead>
                <tr>
                    <th class="text-center">
                        <input type="checkbox" id="sec_check_all" title="Seleccionar todas">
                    </th>
                    <th class="text-center col-orden">Orden</th>
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
                        'es_separador' => ! empty($s['es_separador']) ? 1 : 0,
                    ];
                    $esSepRow = ! empty($s['es_separador']);
                    $sexoEtq = match ($s['sexo'] ?? '') {
                        'masculino' => 'M',
                        'femenino'  => 'F',
                        default     => 'Ambos',
                    };
                    $pobEtq = $pobMap[(int) ($s['paciente_id'] ?? 0)] ?? (string) ($s['paciente_id'] ?? '');
                    $etiquetaOrden = $esSepRow
                        ? ('Título · ' . trim((string) ($s['nombre'] ?? '')))
                        : (trim((string) ($s['nombre'] ?? '')) . ' · ' . $pobEtq . ' · ' . $sexoEtq);
                ?>
                <tr data-sec="<?= htmlspecialchars(json_encode($rowDataSec), ENT_QUOTES, 'UTF-8') ?>" data-secanacategoria-id="<?= (int)($s['secanacategoria_id'] ?? 0) ?>" data-orden-etiqueta="<?= esc($etiquetaOrden, 'attr') ?>">
                    <?php if ($esSepRow): ?>
                    <td class="text-center">
                        <input type="checkbox" class="sec-check-item" value="<?= (int)($s['secanacategoria_id'] ?? 0) ?>">
                    </td>
                    <td class="text-center text-nowrap">
                        <span class="sec-drag-handle" title="Arrastrar para reordenar"><i class="fa-solid fa-grip-vertical"></i></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sec-primero" title="Ir al inicio de la lista"><i class="fa-solid fa-angles-up"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sec-subir" title="Subir una fila"><i class="fa-solid fa-arrow-up"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sec-bajar" title="Bajar una fila"><i class="fa-solid fa-arrow-down"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sec-ultimo" title="Ir al final de la lista"><i class="fa-solid fa-angles-down"></i></button>
                    </td>
                    <td colspan="8" class="table-secondary"><span class="badge bg-secondary me-2">Título</span><strong><?= esc($s['nombre'] ?? '') ?></strong></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar-sec" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <a href="<?= site_url("labotests/duplicatesecitem/" . (int)($s['secanacategoria_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary btn-sec-duplicar" title="Duplicar"><i class="fa-solid fa-copy"></i></a>
                        <a href="<?= site_url("labotests/deletesecitem/" . (int)($s['secanacategoria_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger btn-sec-eliminar" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                    </td>
                    <?php else: ?>
                    <td class="text-center">
                        <input type="checkbox" class="sec-check-item" value="<?= (int)($s['secanacategoria_id'] ?? 0) ?>">
                    </td>
                    <td class="text-center text-nowrap">
                        <span class="sec-drag-handle" title="Arrastrar para reordenar"><i class="fa-solid fa-grip-vertical"></i></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sec-primero" title="Ir al inicio de la lista"><i class="fa-solid fa-angles-up"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sec-subir" title="Subir una fila"><i class="fa-solid fa-arrow-up"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sec-bajar" title="Bajar una fila"><i class="fa-solid fa-arrow-down"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sec-ultimo" title="Ir al final de la lista"><i class="fa-solid fa-angles-down"></i></button>
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
                        <a href="<?= site_url("labotests/duplicatesecitem/" . (int)($s['secanacategoria_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary btn-sec-duplicar" title="Duplicar"><i class="fa-solid fa-copy"></i></a>
                        <a href="<?= site_url("labotests/deletesecitem/" . (int)($s['secanacategoria_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger btn-sec-eliminar" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <?php if (! empty($sub_items)): ?>
        <div class="modal fade" id="modalOrdenSecItems" tabindex="-1" aria-labelledby="modalOrdenSecItemsTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalOrdenSecItemsTitle">Orden rápido</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2">Lista compacta: arrastra el asa <i class="fa-solid fa-grip-vertical text-secondary"></i> para mover varias filas de golpe. <strong>Aplicar y guardar</strong> actualiza la tabla y el servidor.</p>
                        <ul class="list-group mt-3 lista-orden-sec-modal" id="listaOrdenSecItems" style="max-height: 62vh; overflow-y: auto;"></ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btn_aplicar_orden_sec_modal">Aplicar y guardar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="modal fade" id="modalDuplicarSecItem" tabindex="-1" aria-labelledby="modalDuplicarSecItemTitle" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalDuplicarSecItemTitle">Duplicar sub-clase</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Indique cuántas copias desea crear.</p>
                        <input type="hidden" id="duplicar_sec_url" value="">
                        <label for="duplicar_sec_copias" class="form-label">Cantidad de copias</label>
                        <input type="number" id="duplicar_sec_copias" class="form-control" min="1" max="100" step="1" value="1" required>
                        <small class="text-muted">Valor por defecto: 1</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btn_confirmar_duplicar_sec">Duplicar</button>
                    </div>
                </div>
            </div>
        </div>

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
            <div class="col-md-6 mb-2">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control form-control-sm" value="<?= esc($editar_sec_data['nombre'] ?? '') ?>" required>
                <small class="text-muted">En modo título solo se usa como texto del separador en el reporte.</small>
            </div>
            <div class="col-md-6 mb-2 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="es_separador" value="1" class="form-check-input" id="es_separador_cb" <?= ! empty($editar_sec_data['es_separador']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="es_separador_cb">Título de sección (fila separadora en reporte; sin valor de resultado)</label>
                </div>
            </div>
        </div>
        <div id="wrap_campos_analito_subclase" class="<?= ! empty($editar_sec_data['es_separador']) ? 'd-none' : '' ?>">
        <div class="row">
            <div class="col-md-2 mb-2">
                <label class="form-label">Población <span class="text-danger">*</span></label>
                <select name="paciente_id" class="form-control form-control-sm" required>
                    <?php foreach ($poblaciones ?? [] as $p): ?>
                    <option value="<?= (int)$p['id_poblacion'] ?>" <?= ((int)($editar_sec_data['paciente_id'] ?? 15) === (int)$p['id_poblacion']) ? 'selected' : '' ?>><?= esc($p['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Sexo <span class="text-danger">*</span></label>
                <select name="sexo" class="form-control form-control-sm" required>
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
            <div class="col-md-3 mb-2" id="wrap_formulas_id" <?= $esCalculada ? 'class="d-none"' : '' ?>>
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
            <div class="col-md-4 mb-2" id="wrap_formula_predefinida_creadas" <?= !$esCalculada ? 'class="d-none"' : '' ?>>
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
            <div class="col-12 mb-2" id="wrap_formula_expresion" <?= !$esCalculada ? 'class="d-none"' : '' ?>>
                <label class="form-label">Constructor de fórmula</label>
                <input type="hidden" name="formula_expresion" id="formula_expresion" value="<?= esc($editar_sec_data['formula_expresion'] ?? '') ?>">
                <div class="border rounded p-2 mb-2 bg-light formula-constructor-box">
                    <small class="text-muted d-block mb-1">Referencias (arrastre o clic para insertar en la fórmula):</small>
                    <div id="formula_refs" class="d-flex flex-wrap gap-1 mb-2">
                        <?php foreach ($refsPorNombre ?? [] as $nombre => $cid): ?>
                        <span class="badge <?= $nombre === 'valor' ? 'bg-secondary' : 'bg-primary' ?> formula-ref" draggable="true" data-nombre="<?= esc($nombre) ?>" data-cid="<?= esc($cid) ?>"><?= esc($nombre) ?></span>
                        <?php endforeach; ?>
                        <?php if (empty($refsPorNombre) || count($refsPorNombre) <= 1): ?>
                        <small class="text-muted align-self-center">Agregue sub-clases en la tabla superior para que aparezcan aquí.</small>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted d-block mb-1">Fórmula actual (nombre de la fórmula o con qué nombre guardará):</small>
                    <div class="d-flex gap-2 align-items-center mb-2">
                        <input type="text" id="formula_nombre_input" class="form-control form-control-sm formula-nombre-input" placeholder="Ej: Formula Eritrocitos" value="<?= esc($formulaNombreInicial ?? '') ?>">
                        <span id="formula_valor_refs" class="border rounded px-2 py-1 bg-white flex-grow-1 formula-valor-refs">—</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
                    <button type="button" id="btn_guardar_formula" class="btn btn-outline-success btn-sm">Guardar / Actualizar fórmula</button>
                    <button type="button" id="btn_eliminar_formula" class="btn btn-outline-danger btn-sm" title="Eliminar la fórmula seleccionada (solo si no está en uso)">Eliminar fórmula</button>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-2 align-items-center">
                    <span class="text-muted formula-op-label">Operadores:</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op="+">+</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op="-">−</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op="*">×</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op="/">/</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op="(">(</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm formula-op" data-op=")">)</button>
                    <span class="text-muted ms-2 formula-op-hint">(Puede escribir números directamente)</span>
                </div>
                <div class="border rounded p-2 bg-white mb-2 formula-area-box">
                    <small class="text-muted d-block mb-1">Fórmula (use referencias por nombre y operadores):</small>
                    <textarea id="formula_area" class="form-control formula-area" rows="2" placeholder="Ej: [Glucosa] + [Colesterol] * 100 / [Glucosa]"><?= esc($formulaParaTextarea ?? '') ?></textarea>
                </div>
                <div class="border rounded p-3 bg-light formula-math-preview" id="formula_math_preview">
                    <small class="text-muted d-block mb-2">Vista previa matemática:</small>
                    <div id="formula_math_display" class="fs-4 text-center py-2 formula-math-display">—</div>
                </div>
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
        </div>
        </div>
        <div class="row">
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
                    if (wForm) { wForm.classList.add('d-none'); }
                    if (wrapPredefCreadas) { wrapPredefCreadas.classList.remove('d-none'); }
                    if (wExp) { wExp.classList.remove('d-none'); }
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
                    if (wForm) { wForm.classList.remove('d-none'); }
                    if (wrapPredefCreadas) { wrapPredefCreadas.classList.add('d-none'); }
                    if (wExp) { wExp.classList.add('d-none'); }
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
            var formSecItem = document.getElementById('form_secitem');
            if (formSecItem && typeof $ !== 'undefined' && $.fn.validate) {
                $(formSecItem).validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
                    rules: {
                        nombre: { required: true },
                        paciente_id: { required: { depends: function() { var cb = document.getElementById('es_separador_cb'); return !cb || !cb.checked; } } },
                        sexo: { required: { depends: function() { var cb = document.getElementById('es_separador_cb'); return !cb || !cb.checked; } } }
                    },
                    messages: { nombre: { required: "El nombre de la sub-clase es obligatorio" }, paciente_id: { required: "La población es obligatoria" }, sexo: { required: "El sexo es obligatorio" } }
                }));
            }
            (formSecItem || formulaInput?.closest('form'))?.addEventListener('submit', function(e) {
                e.preventDefault();
                var f = this;
                if (typeof $ !== 'undefined' && $(f).data('validator') && !$(f).validate().form()) return;
                var sepOn = document.getElementById('es_separador_cb') && document.getElementById('es_separador_cb').checked;
                if (sepOn) {
                    var c0 = document.getElementById('es_calculada');
                    if (c0) c0.checked = false;
                    if (formulasIdHidden) formulasIdHidden.value = '1';
                    if (formulaInput) formulaInput.value = '';
                    if (formulaArea) formulaArea.value = '';
                    f.submit();
                    return;
                }
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
                    var confirmMsg = '¿Eliminar la fórmula "' + (opt.textContent || '') + '"? Solo es posible si no está en uso.';
                    uiConfirm(confirmMsg, 'Confirmar').then(function(ok) {
                        if (!ok) return;
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
            function toggleCamposSeparador(on) {
                var w = document.getElementById('wrap_campos_analito_subclase');
                if (w) w.classList.toggle('d-none', !!on);
            }
            document.getElementById('es_separador_cb')?.addEventListener('change', function() {
                toggleCamposSeparador(this.checked);
            });
            function fillFormSec(data) {
                data = data || {};
                var esSep = !!(data.es_separador && (parseInt(String(data.es_separador), 10) === 1));
                var sepCb = document.getElementById('es_separador_cb');
                if (sepCb) sepCb.checked = esSep;
                toggleCamposSeparador(esSep);
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
                var fid = esSep ? 1 : ((data.formulas_id || 1) | 0);
                if (calc) calc.checked = !esSep && fid > 1;
                document.getElementById('formulas_id_hidden').value = fid;
                var formulasIdSelect = document.getElementById('formulas_id');
                if (formulasIdSelect && fid <= 1) formulasIdSelect.value = '1';
                var wrapForm = document.getElementById('wrap_formulas_id');
                var wrapExp = document.getElementById('wrap_formula_expresion');
                var wrapPredef = document.getElementById('wrap_formula_predefinida_creadas');
                if (wrapForm) {
                    if (fid > 1) {
                        wrapForm.classList.add('d-none');
                    } else {
                        wrapForm.classList.remove('d-none');
                    }
                }
                if (wrapExp) {
                    if (fid > 1) {
                        wrapExp.classList.remove('d-none');
                    } else {
                        wrapExp.classList.add('d-none');
                    }
                }
                if (wrapPredef) {
                    if (fid > 1) {
                        wrapPredef.classList.remove('d-none');
                    } else {
                        wrapPredef.classList.add('d-none');
                    }
                }
                var formulaArea = document.getElementById('formula_area');
                var formulaInput = document.getElementById('formula_expresion');
                var formulaPredefSelect = document.getElementById('formula_predefinida_select');
                if (formulaArea) formulaArea.value = esSep ? '' : (data.formula_para_textarea || '');
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
                    formula_nombre: '',
                    es_separador: 0
                });
            }
            document.getElementById('btn_agregar_sec').addEventListener('click', function() {
                if (modalTitle) modalTitle.textContent = 'Agregar sub-clase';
                if (btnSubmit) btnSubmit.textContent = 'Agregar';
                clearFormSec();
                openModal();
            });
            var tablaSub = document.getElementById('tabla_sub_items');
            var sortableModalOrden = null;
            if (tablaSub) {
                var secCheckAll = document.getElementById('sec_check_all');
                var btnEliminarSeleccionadas = document.getElementById('btn_eliminar_sec_seleccionadas');
                var modalDuplicarSecEl = document.getElementById('modalDuplicarSecItem');
                var modalDuplicarSecInst = (modalDuplicarSecEl && typeof bootstrap !== 'undefined')
                    ? (bootstrap.Modal.getInstance(modalDuplicarSecEl) || new bootstrap.Modal(modalDuplicarSecEl))
                    : null;
                var duplicarSecUrlInput = document.getElementById('duplicar_sec_url');
                var duplicarSecCopiasInput = document.getElementById('duplicar_sec_copias');
                var btnConfirmarDuplicarSec = document.getElementById('btn_confirmar_duplicar_sec');

                function actualizarBadgeFilas() {
                    var badge = document.querySelector('.card-tabla-sub-items .card-header .badge.bg-secondary');
                    if (!badge) return;
                    var total = tablaSub.querySelectorAll('tbody tr[data-secanacategoria-id]').length;
                    badge.textContent = String(total) + ' filas';
                }
                function getCsrfData() {
                    var csrf = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
                    var csrfName = (csrf && csrf.name) ? csrf.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
                    var csrfVal = (csrf && csrf.value) ? csrf.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
                    return { name: csrfName, value: csrfVal };
                }
                function applyCsrfFromJson(d) {
                    if (!d || !d.csrf_token || !d.csrf_name) return;
                    window.CI_CSRF_TOKEN = d.csrf_token;
                    window.CI_CSRF_TOKEN_NAME = d.csrf_name;
                    document.querySelectorAll('input[name="csrf_test_name"], input[name*="csrf"]').forEach(function(inp) {
                        inp.name = d.csrf_name;
                        inp.value = d.csrf_token;
                    });
                }
                function getSelectedSecIds() {
                    var ids = [];
                    tablaSub.querySelectorAll('tbody .sec-check-item:checked').forEach(function(chk) {
                        var id = parseInt(chk.value, 10);
                        if (id > 0) ids.push(id);
                    });
                    return ids;
                }
                function syncSecCheckAllState() {
                    if (!secCheckAll) return;
                    var all = tablaSub.querySelectorAll('tbody .sec-check-item');
                    var checked = tablaSub.querySelectorAll('tbody .sec-check-item:checked');
                    secCheckAll.checked = all.length > 0 && checked.length === all.length;
                    secCheckAll.indeterminate = checked.length > 0 && checked.length < all.length;
                }
                function actualizarEstadoSeleccion() {
                    var selected = getSelectedSecIds();
                    if (btnEliminarSeleccionadas) {
                        btnEliminarSeleccionadas.disabled = selected.length === 0;
                        btnEliminarSeleccionadas.innerHTML = selected.length > 0
                            ? '<i class="fa-solid fa-trash me-1"></i>Eliminar seleccionadas (' + selected.length + ')'
                            : '<i class="fa-solid fa-trash me-1"></i>Eliminar seleccionadas';
                    }
                    syncSecCheckAllState();
                }
                function refrescarTablaDesdeRespuesta(htmlText) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(htmlText || '', 'text/html');
                    var nuevoBody = doc.querySelector('#tabla_sub_items tbody');
                    var tbodyActual = tablaSub.querySelector('tbody');
                    if (!nuevoBody || !tbodyActual) return false;
                    tbodyActual.innerHTML = nuevoBody.innerHTML;
                    actualizarBadgeFilas();
                    actualizarEstadoSeleccion();
                    return true;
                }
                function ejecutarAccionFila(url, mensajeExito) {
                    if (!url) return;
                    fetch(url, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function(r) { return r.text(); }).then(function(html) {
                        if (!refrescarTablaDesdeRespuesta(html)) {
                            throw new Error('No se pudo actualizar la tabla');
                        }
                        if (typeof showToast === 'function' && mensajeExito) {
                            showToast(mensajeExito, 'success');
                        }
                    }).catch(function() {
                        if (typeof showToast === 'function') {
                            showToast('No se pudo completar la acción', 'error');
                        }
                    });
                }
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
                function moverFilaExtremo(tr, alInicio) {
                    var tbody = tablaSub.querySelector('tbody');
                    if (!tbody) return;
                    var rows = [].slice.call(tbody.querySelectorAll('tr[data-secanacategoria-id]'));
                    if (rows.length < 2) return;
                    var idx = rows.indexOf(tr);
                    if (idx < 0) return;
                    if (alInicio) {
                        if (idx === 0) return;
                        tbody.insertBefore(tr, rows[0]);
                    } else {
                        if (idx === rows.length - 1) return;
                        tbody.appendChild(tr);
                    }
                    guardarOrden();
                }
                function aplicarOrdenDesdeModal() {
                    var tbody = tablaSub.querySelector('tbody');
                    var list = document.getElementById('listaOrdenSecItems');
                    if (!tbody || !list) return;
                    var items = list.querySelectorAll('li[data-secanacategoria-id]');
                    if (items.length === 0) return;
                    var frag = document.createDocumentFragment();
                    for (var i = 0; i < items.length; i++) {
                        var id = parseInt(items[i].getAttribute('data-secanacategoria-id'), 10);
                        if (id < 1) continue;
                        var tr = tablaSub.querySelector('tr[data-secanacategoria-id="' + id + '"]');
                        if (tr) frag.appendChild(tr);
                    }
                    tbody.appendChild(frag);
                    guardarOrden();
                    var modalOrdenEl = document.getElementById('modalOrdenSecItems');
                    if (modalOrdenEl && typeof bootstrap !== 'undefined') {
                        var inst = bootstrap.Modal.getInstance(modalOrdenEl);
                        if (inst) inst.hide();
                    }
                }
                function poblarListaOrdenModal() {
                    var list = document.getElementById('listaOrdenSecItems');
                    if (!list) return;
                    list.innerHTML = '';
                    getOrderIds().forEach(function(id) {
                        var tr = tablaSub.querySelector('tr[data-secanacategoria-id="' + id + '"]');
                        if (!tr) return;
                        var label = tr.getAttribute('data-orden-etiqueta') || ('#' + id);
                        var li = document.createElement('li');
                        li.className = 'list-group-item d-flex align-items-center gap-2 py-2';
                        li.setAttribute('data-secanacategoria-id', String(id));
                        var h = document.createElement('span');
                        h.className = 'sec-orden-modal-handle text-muted flex-shrink-0';
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
                var modalOrdenSecEl = document.getElementById('modalOrdenSecItems');
                var modalOrdenSecInst = null;
                if (modalOrdenSecEl && typeof bootstrap !== 'undefined') {
                    modalOrdenSecInst = bootstrap.Modal.getInstance(modalOrdenSecEl) || new bootstrap.Modal(modalOrdenSecEl);
                    modalOrdenSecEl.addEventListener('show.bs.modal', function() {
                        if (sortableModalOrden) {
                            sortableModalOrden.destroy();
                            sortableModalOrden = null;
                        }
                        poblarListaOrdenModal();
                    });
                    modalOrdenSecEl.addEventListener('shown.bs.modal', function() {
                        var list = document.getElementById('listaOrdenSecItems');
                        if (list && typeof Sortable !== 'undefined' && list.children.length) {
                            sortableModalOrden = new Sortable(list, {
                                handle: '.sec-orden-modal-handle',
                                animation: 150,
                                ghostClass: 'list-group-item-secondary'
                            });
                        }
                    });
                    modalOrdenSecEl.addEventListener('hidden.bs.modal', function() {
                        if (sortableModalOrden) {
                            sortableModalOrden.destroy();
                            sortableModalOrden = null;
                        }
                    });
                }
                var btnAbrirOrden = document.getElementById('btn_abrir_modal_orden_sec');
                if (btnAbrirOrden && modalOrdenSecInst) {
                    btnAbrirOrden.addEventListener('click', function() { modalOrdenSecInst.show(); });
                }
                var btnAplicarOrden = document.getElementById('btn_aplicar_orden_sec_modal');
                if (btnAplicarOrden) {
                    btnAplicarOrden.addEventListener('click', aplicarOrdenDesdeModal);
                }
                if (secCheckAll) {
                    secCheckAll.addEventListener('change', function() {
                        tablaSub.querySelectorAll('tbody .sec-check-item').forEach(function(chk) {
                            chk.checked = !!secCheckAll.checked;
                        });
                        actualizarEstadoSeleccion();
                    });
                }
                tablaSub.addEventListener('change', function(e) {
                    if (e.target && e.target.classList.contains('sec-check-item')) {
                        actualizarEstadoSeleccion();
                    }
                });
                if (btnEliminarSeleccionadas) {
                    btnEliminarSeleccionadas.addEventListener('click', function() {
                        var ids = getSelectedSecIds();
                        if (ids.length < 1) return;
                        var msg = '¿Eliminar ' + ids.length + ' sub-clase(s) seleccionada(s)?';
                        var confirmar = (typeof uiConfirm === 'function')
                            ? uiConfirm(msg, 'Confirmar eliminación masiva')
                            : Promise.resolve(window.confirm(msg));
                        confirmar.then(function(ok) {
                            if (!ok) return;
                            var fd = new FormData();
                            fd.append('prianacategoria_id', String(prianacategoriaId));
                            ids.forEach(function(id) { fd.append('secanacategoria_ids[]', String(id)); });
                            var csrfData = getCsrfData();
                            if (csrfData.value) fd.append(csrfData.name, csrfData.value);
                            var headers = { 'X-Requested-With': 'XMLHttpRequest' };
                            if (csrfData.value) headers['X-CSRF-TOKEN'] = csrfData.value;
                            fetch('<?= site_url('labotests/deletesecitemsbulk') ?>', {
                                method: 'POST',
                                body: fd,
                                headers: headers
                            }).then(function(r) { return r.json(); }).then(function(d) {
                                applyCsrfFromJson(d);
                                if (d.success) {
                                    if (typeof showToast === 'function') showToast(d.message || 'Eliminación completada', 'success');
                                    window.location.reload();
                                } else if (typeof showToast === 'function') {
                                    showToast(d.message || 'No se pudo eliminar', 'error');
                                }
                            }).catch(function() {
                                if (typeof showToast === 'function') showToast('No se pudo eliminar en lote', 'error');
                            });
                        });
                    });
                }
                if (btnConfirmarDuplicarSec) {
                    btnConfirmarDuplicarSec.addEventListener('click', function() {
                        var url = duplicarSecUrlInput ? duplicarSecUrlInput.value : '';
                        var copies = parseInt((duplicarSecCopiasInput && duplicarSecCopiasInput.value) ? duplicarSecCopiasInput.value : '1', 10);
                        if (!url) return;
                        copies = isNaN(copies) ? 1 : Math.max(1, Math.min(100, copies));
                        var fd = new FormData();
                        fd.append('copies', String(copies));
                        var csrfData = getCsrfData();
                        if (csrfData.value) fd.append(csrfData.name, csrfData.value);
                        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (csrfData.value) headers['X-CSRF-TOKEN'] = csrfData.value;
                        btnConfirmarDuplicarSec.disabled = true;
                        fetch(url, {
                            method: 'POST',
                            body: fd,
                            headers: headers
                        }).then(function(r) { return r.json(); }).then(function(d) {
                            btnConfirmarDuplicarSec.disabled = false;
                            applyCsrfFromJson(d);
                            if (d.success) {
                                if (typeof showToast === 'function') showToast(d.message || 'Sub-clase duplicada', 'success');
                                if (modalDuplicarSecInst) modalDuplicarSecInst.hide();
                                window.location.reload();
                            } else if (typeof showToast === 'function') {
                                showToast(d.message || 'No se pudo duplicar', 'error');
                            }
                        }).catch(function() {
                            btnConfirmarDuplicarSec.disabled = false;
                            if (typeof showToast === 'function') showToast('No se pudo duplicar la sub-clase', 'error');
                        });
                    });
                }
                tablaSub.addEventListener('click', function(e) {
                    var editar = e.target.closest('.btn-editar-sec');
                    if (editar) {
                        e.preventDefault();
                        var trEdit = editar.closest('tr');
                        var dataStr = trEdit && trEdit.getAttribute('data-sec');
                        if (!dataStr) return;
                        var data = {};
                        try { data = JSON.parse(dataStr); } catch (err) { return; }
                        if (modalTitle) modalTitle.textContent = 'Editar sub-clase';
                        if (btnSubmit) btnSubmit.textContent = 'Actualizar';
                        fillFormSec(data);
                        openModal();
                        return;
                    }
                    var duplicar = e.target.closest('.btn-sec-duplicar');
                    if (duplicar) {
                        e.preventDefault();
                        if (duplicarSecUrlInput) duplicarSecUrlInput.value = duplicar.getAttribute('href') || '';
                        if (duplicarSecCopiasInput) duplicarSecCopiasInput.value = '1';
                        if (modalDuplicarSecInst) {
                            modalDuplicarSecInst.show();
                            setTimeout(function() { if (duplicarSecCopiasInput) duplicarSecCopiasInput.focus(); }, 200);
                        } else {
                            ejecutarAccionFila(duplicar.getAttribute('href'), 'Sub-clase duplicada correctamente');
                        }
                        return;
                    }
                    var eliminar = e.target.closest('.btn-sec-eliminar');
                    if (eliminar) {
                        e.preventDefault();
                        var confirmar = (typeof uiConfirm === 'function')
                            ? uiConfirm('¿Eliminar esta sub-clase?', 'Confirmar')
                            : Promise.resolve(window.confirm('¿Eliminar esta sub-clase?'));
                        confirmar.then(function(ok) {
                            if (!ok) return;
                            ejecutarAccionFila(eliminar.getAttribute('href'), 'Sub-clase eliminada');
                        });
                        return;
                    }
                    var subir = e.target.closest('.btn-sec-subir');
                    var bajar = e.target.closest('.btn-sec-bajar');
                    var primero = e.target.closest('.btn-sec-primero');
                    var ultimo = e.target.closest('.btn-sec-ultimo');
                    var btn = subir || bajar || primero || ultimo;
                    var tr = btn && btn.closest('tr');
                    if (!tr) return;
                    if (subir) { e.preventDefault(); moverFila(tr, -1); }
                    if (bajar) { e.preventDefault(); moverFila(tr, 1); }
                    if (primero) { e.preventDefault(); moverFilaExtremo(tr, true); }
                    if (ultimo) { e.preventDefault(); moverFilaExtremo(tr, false); }
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
                actualizarEstadoSeleccion();
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
                'es_separador' => ! empty($editar_sec_data['es_separador']) ? 1 : 0,
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
<?= view('labotests/partial_detail_pri_resultados', get_defined_vars()) ?>
<?php endif; ?>
<?= $this->endSection() ?>
