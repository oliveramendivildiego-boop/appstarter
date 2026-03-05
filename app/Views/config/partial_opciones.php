<?php
/**
 * Partial: tabla y formulario de Tipos de resultado (para pestaña en config)
 */
$opciones = isset($opciones) ? $opciones : [];
?>
<p class="text-muted mb-4">
    Estos tipos definen las opciones del select "Tipo resultado" al agregar sub-clases en análisis compuestos.
    Puede crear tipos personalizados (ej: Color, Consistencia, Presencia de moco) y definir sus valores.
</p>

<div class="table-responsive mb-4">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Valores del select</th>
                <th class="text-center" style="width:140px">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($opciones as $o): ?>
            <tr id="opcion-<?= (int)($o['opciones_id'] ?? 0) ?>">
                <td>
                    <?php if (($o['editable'] ?? false)): ?>
                    <?= form_open('config/saveopcion', ['class' => 'd-inline']) ?>
                    <input type="hidden" name="opciones_id" value="<?= (int)($o['opciones_id'] ?? 0) ?>">
                    <input type="text" name="opciones" class="form-control form-control-sm d-inline-block" style="width:200px" value="<?= esc($o['opciones'] ?? '') ?>" required>
                    <button type="submit" class="btn btn-sm btn-outline-primary ms-1"><i class="fa-solid fa-save"></i></button>
                    <?= form_close() ?>
                    <?php else: ?>
                    <strong><?= esc($o['opciones'] ?? '') ?></strong>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($o['usa_valores_genericos'] ?? false): ?>
                    <ul class="list-unstyled mb-0 small">
                        <?php foreach ($o['valores'] ?? [] as $v): ?>
                        <li class="d-flex align-items-center gap-2 py-1">
                            <?= form_open('config/saveopcionvalor', ['class' => 'd-flex align-items-center gap-1 flex-grow-1']) ?>
                            <input type="hidden" name="opciones_id" value="<?= (int)($o['opciones_id'] ?? 0) ?>">
                            <input type="hidden" name="opcion_valor_id" value="<?= (int)($v['opcion_valor_id'] ?? 0) ?>">
                            <input type="hidden" name="orden" value="<?= (int)($v['orden'] ?? 0) ?>">
                            <input type="text" name="valor" class="form-control form-control-sm" style="width:180px" value="<?= esc($v['valor'] ?? '') ?>" required>
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar"><i class="fa-solid fa-save"></i></button>
                            <?= form_close() ?>
                            <a href="<?= site_url('config/deleteopcionvalor/' . (int)($v['opcion_valor_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Eliminar este valor?');"><i class="fa-solid fa-trash"></i></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-2">
                        <?= form_open('config/saveopcionvalor', ['class' => 'd-flex align-items-center gap-2']) ?>
                        <input type="hidden" name="opciones_id" value="<?= (int)($o['opciones_id'] ?? 0) ?>">
                        <input type="hidden" name="opcion_valor_id" value="0">
                        <input type="text" name="valor" class="form-control form-control-sm" style="width:200px" placeholder="Nuevo valor..." required>
                        <input type="hidden" name="orden" value="0">
                        <button type="submit" class="btn btn-success btn-sm"><i class="fa-solid fa-plus me-1"></i> Agregar</button>
                        <?= form_close() ?>
                    </div>
                    <?php elseif (($o['usa_tabla_sistema'] ?? false) && !empty($o['valores'])): ?>
                    <?php $ts = $o['tabla_sistema'] ?? ''; $idCol = $ts . '_id'; $valCol = $ts; ?>
                    <ul class="list-unstyled mb-0 small">
                        <?php foreach ($o['valores'] as $v): ?>
                        <li class="d-flex align-items-center gap-2 py-1">
                            <?= form_open('config/savevalortabla', ['class' => 'd-flex align-items-center gap-1 flex-grow-1']) ?>
                            <input type="hidden" name="tabla" value="<?= esc($ts) ?>">
                            <input type="hidden" name="valor_id" value="<?= (int)($v[$idCol] ?? 0) ?>">
                            <input type="text" name="valor" class="form-control form-control-sm" style="width:180px" value="<?= esc($v[$valCol] ?? '') ?>" required>
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar"><i class="fa-solid fa-save"></i></button>
                            <?= form_close() ?>
                            <a href="<?= site_url('config/deletevalortabla/' . $ts . '/' . (int)($v[$idCol] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Eliminar este valor?');"><i class="fa-solid fa-trash"></i></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-2">
                        <?= form_open('config/savevalortabla', ['class' => 'd-flex align-items-center gap-2']) ?>
                        <input type="hidden" name="tabla" value="<?= esc($ts) ?>">
                        <input type="hidden" name="valor_id" value="0">
                        <input type="text" name="valor" class="form-control form-control-sm" style="width:200px" placeholder="Nuevo valor..." required>
                        <button type="submit" class="btn btn-success btn-sm"><i class="fa-solid fa-plus me-1"></i> Agregar</button>
                        <?= form_close() ?>
                    </div>
                    <?php elseif (!empty($o['valores'])): ?>
                    <span class="text-muted"><?= implode(', ', array_map(function ($v) {
                        if (isset($v['valor'])) return esc($v['valor']);
                        if (isset($v['opcpositivo'])) return esc($v['opcpositivo']);
                        if (isset($v['opcreactivo'])) return esc($v['opcreactivo']);
                        return esc($v[array_key_first($v)] ?? '');
                    }, $o['valores'])) ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($o['editable'] ?? false): ?>
                    <a href="<?= site_url('config/deleteopcion/' . (int)($o['opciones_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar este tipo de resultado?');"><i class="fa-solid fa-trash"></i> Eliminar</a>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="border rounded p-3 bg-light">
    <h6 class="mb-3"><i class="fa-solid fa-plus me-2"></i>Agregar tipo de resultado</h6>
    <?= form_open('config/saveopcion', ['id' => 'form_nueva_opcion']) ?>
    <input type="hidden" name="opciones_id" value="0">
    <div class="row align-items-end">
        <div class="col-md-5 mb-2">
            <label class="form-label">Nombre (ej: Color, Consistencia, Presencia de moco)</label>
            <input type="text" name="opciones" class="form-control" placeholder="Ej: Color (marrón normal, verdoso, negro...)" required>
        </div>
        <div class="col-md-4 mb-2">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Crear tipo</button>
        </div>
    </div>
    <small class="text-muted">Después de crear, agregue los valores del select en la tabla superior.</small>
    <?= form_close() ?>
</div>
