<?php
/**
 * Partial: tabla y formulario de Tipos de resultado (para pestaña en config)
 */
$opciones = isset($opciones) ? $opciones : [];
$opcionesPagination = isset($opciones_pagination) && is_array($opciones_pagination) ? $opciones_pagination : [];
$opcionesCurrentPage = max(1, (int) ($opcionesPagination['page'] ?? 1));
$opcionesTotalPages = max(1, (int) ($opcionesPagination['pages'] ?? 1));
?>
<p class="text-muted mb-4">
    Estos tipos definen las opciones del select "Tipo resultado" al agregar sub-clases en análisis compuestos.
    Puede crear tipos personalizados (ej: Color, Consistencia, Presencia de moco) y definir sus valores.
</p>

<div class="border rounded p-3 mb-4 bg-light">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= site_url('config/exportOpciones') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-file-export me-1"></i> Exportar JSON
        </a>
        <?= form_open_multipart(site_url('config/importOpciones'), ['class' => 'd-flex flex-wrap align-items-center gap-2']) ?>
        <input type="hidden" name="opciones_page" value="<?= $opcionesCurrentPage ?>">
        <input type="file" name="opciones_file" class="form-control form-control-sm" accept="application/json,.json" required style="max-width: 320px;">
        <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-file-import me-1"></i> Importar JSON
        </button>
        <?= form_close() ?>
    </div>
    <small class="text-muted d-block mt-2">La importación agrega/actualiza tipos y valores del archivo sin borrar los existentes.</small>
</div>

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
                    <input type="hidden" name="opciones_page" value="<?= $opcionesCurrentPage ?>">
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
                            <input type="hidden" name="opciones_page" value="<?= $opcionesCurrentPage ?>">
                            <input type="hidden" name="opcion_valor_id" value="<?= (int)($v['opcion_valor_id'] ?? 0) ?>">
                            <input type="hidden" name="orden" value="<?= (int)($v['orden'] ?? 0) ?>">
                            <input type="text" name="valor" class="form-control form-control-sm" style="width:180px" value="<?= esc($v['valor'] ?? '') ?>" required>
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar"><i class="fa-solid fa-save"></i></button>
                            <?= form_close() ?>
                            <a href="<?= site_url('config/deleteopcionvalor/' . (int)($v['opcion_valor_id'] ?? 0) . '?opciones_page=' . $opcionesCurrentPage) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return uiConfirmLink(this, '¿Eliminar este valor?');"><i class="fa-solid fa-trash"></i></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-2">
                        <?= form_open('config/saveopcionvalor', ['class' => 'd-flex align-items-center gap-2']) ?>
                        <input type="hidden" name="opciones_id" value="<?= (int)($o['opciones_id'] ?? 0) ?>">
                        <input type="hidden" name="opciones_page" value="<?= $opcionesCurrentPage ?>">
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
                            <input type="hidden" name="opciones_page" value="<?= $opcionesCurrentPage ?>">
                            <input type="hidden" name="valor_id" value="<?= (int)($v[$idCol] ?? 0) ?>">
                            <input type="text" name="valor" class="form-control form-control-sm" style="width:180px" value="<?= esc($v[$valCol] ?? '') ?>" required>
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar"><i class="fa-solid fa-save"></i></button>
                            <?= form_close() ?>
                            <a href="<?= site_url('config/deletevalortabla/' . $ts . '/' . (int)($v[$idCol] ?? 0) . '?opciones_page=' . $opcionesCurrentPage) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return uiConfirmLink(this, '¿Eliminar este valor?');"><i class="fa-solid fa-trash"></i></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-2">
                        <?= form_open('config/savevalortabla', ['class' => 'd-flex align-items-center gap-2']) ?>
                        <input type="hidden" name="tabla" value="<?= esc($ts) ?>">
                        <input type="hidden" name="opciones_page" value="<?= $opcionesCurrentPage ?>">
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
                    <a href="<?= site_url('config/deleteopcion/' . (int)($o['opciones_id'] ?? 0) . '?opciones_page=' . $opcionesCurrentPage) ?>" class="btn btn-sm btn-outline-danger" onclick="return uiConfirmLink(this, '¿Eliminar este tipo de resultado?');"><i class="fa-solid fa-trash"></i> Eliminar</a>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($opcionesTotalPages > 1): ?>
<nav aria-label="Paginación tipos de resultado" class="mb-4">
    <ul class="pagination pagination-sm mb-0">
        <?php $prevPage = max(1, $opcionesCurrentPage - 1); ?>
        <li class="page-item <?= $opcionesCurrentPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= site_url('config?tab=opciones&opciones_page=' . $prevPage) ?>">Anterior</a>
        </li>
        <?php for ($p = 1; $p <= $opcionesTotalPages; $p++): ?>
        <li class="page-item <?= $p === $opcionesCurrentPage ? 'active' : '' ?>">
            <a class="page-link" href="<?= site_url('config?tab=opciones&opciones_page=' . $p) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <?php $nextPage = min($opcionesTotalPages, $opcionesCurrentPage + 1); ?>
        <li class="page-item <?= $opcionesCurrentPage >= $opcionesTotalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= site_url('config?tab=opciones&opciones_page=' . $nextPage) ?>">Siguiente</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<div class="border rounded p-3 bg-light">
    <h6 class="mb-3"><i class="fa-solid fa-plus me-2"></i>Agregar tipo de resultado</h6>
    <?= form_open('config/saveopcion', ['id' => 'form_nueva_opcion']) ?>
    <input type="hidden" name="opciones_id" value="0">
    <input type="hidden" name="opciones_page" value="<?= $opcionesCurrentPage ?>">
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
