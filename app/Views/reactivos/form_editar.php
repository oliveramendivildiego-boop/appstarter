<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'reactivos']) ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => 'Insumos', 'url' => site_url('reactivos')],
    ['label' => 'Editar: ' . esc($reactivo['nombre'] ?? ''), 'url' => null],
]]) ?>

<?= form_open('reactivos/savereactivo') ?>
<input type="hidden" name="reactivo_id" value="<?= (int)($reactivo['reactivo_id'] ?? 0) ?>">
<div class="card">
    <div class="card-header"><strong>Editar insumo</strong></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" value="<?= esc($reactivo['nombre'] ?? '') ?>" required></div>
            <div class="col-md-2"><label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="1" <?= ((int)($reactivo['tipo'] ?? 1)) === 1 ? 'selected' : '' ?>>Reactivo</option>
                    <option value="2" <?= ((int)($reactivo['tipo'] ?? 0)) === 2 ? 'selected' : '' ?>>Kit</option>
                    <option value="3" <?= ((int)($reactivo['tipo'] ?? 0)) === 3 ? 'selected' : '' ?>>Insumo</option>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Grupo</label><input type="text" name="grupo" class="form-control" value="<?= esc($reactivo['grupo'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label">Subgrupo</label><input type="text" name="subgrupo" class="form-control" value="<?= esc($reactivo['subgrupo'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label">Unidad base</label><input type="text" name="unidad_base" class="form-control" value="<?= esc($reactivo['unidad_base'] ?? $reactivo['unidad'] ?? '') ?>" placeholder="ml, prueba, unidad"></div>
            <div class="col-md-2"><label class="form-label">Cont. por presentación</label><input type="number" name="contenido_por_presentacion" class="form-control" value="<?= (int)($reactivo['contenido_por_presentacion'] ?? 1) ?>" min="1"></div>
            <div class="col-md-2"><label class="form-label">Stock mínimo</label><input type="number" name="stock_minimo" class="form-control" value="<?= (int)($reactivo['stock_minimo'] ?? 0) ?>"></div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= site_url('reactivos') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </div>
</div>
<?= form_close() ?>

<?= view('partial/footer') ?>
