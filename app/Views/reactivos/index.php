<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'reactivos']) ?>

<?= view('partial/breadcrumb_nav', ['items' => [['label' => 'Inventario de insumos', 'url' => site_url('reactivos')]]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?php if (!empty($alertas)): ?>
<div class="alert alert-warning">
    <strong><i class="fa-solid fa-triangle-exclamation me-1"></i> Alertas:</strong>
    <?php foreach (array_slice($alertas, 0, 8) as $a): ?>
        <?php if ($a['tipo'] === 'stock'): ?>
            <span class="badge bg-danger me-1"><?= esc($a['reactivo']['nombre'] ?? '') ?>: stock <?= $a['stock'] ?> (mín <?= $a['minimo'] ?>)</span>
        <?php elseif ($a['tipo'] === 'vencido'): ?>
            <span class="badge bg-dark me-1"><?= esc($a['reactivo']['nombre'] ?? '') ?>: lote vencido</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark me-1"><?= esc($a['reactivo']['nombre'] ?? '') ?>: vence pronto</span>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Insumos (presentación vs unidad base)</strong>
                <?php if (!empty($grupos)): ?>
                <div class="btn-group btn-group-sm">
                    <a href="<?= site_url('reactivos') ?>" class="btn btn-outline-secondary <?= !$grupo_filtro ? 'active' : '' ?>">Todos</a>
                    <?php foreach ($grupos as $g): ?>
                    <a href="<?= site_url('reactivos?grupo=' . urlencode($g['grupo'] ?? '')) ?>" class="btn btn-outline-secondary <?= ($grupo_filtro ?? '') === ($g['grupo'] ?? '') ? 'active' : '' ?>"><?= esc($g['grupo'] ?? '') ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Tipo</th>
                            <th>Grupo</th>
                            <th>Unidad base</th>
                            <th>Cont./pres.</th>
                            <th>Stock mín</th>
                            <th>Stock</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reactivos ?? [] as $r): ?>
                        <tr class="<?= (($r['stock_actual'] ?? 0) < ($r['stock_minimo'] ?? 0) && ($r['stock_minimo'] ?? 0) > 0 ? 'table-danger' : '') ?>">
                            <td><?= esc($r['nombre'] ?? '') ?></td>
                            <td><span class="badge bg-secondary"><?= esc($r['tipo_nombre'] ?? '') ?></span></td>
                            <td><?= esc($r['grupo'] ?? '-') ?></td>
                            <td><?= esc($r['unidad_base'] ?? $r['unidad'] ?? '-') ?></td>
                            <td><?= (int)($r['contenido_por_presentacion'] ?? 1) ?></td>
                            <td><?= (int)($r['stock_minimo'] ?? 0) ?></td>
                            <td><?= (int)($r['stock_actual'] ?? 0) ?></td>
                            <td>
                                <a href="<?= site_url('reactivos/lotes/' . (int)$r['reactivo_id']) ?>" class="btn btn-sm btn-outline-primary">Lotes</a>
                                <a href="<?= site_url('reactivos/editar/' . (int)$r['reactivo_id']) ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <?= form_open('reactivos/eliminar/' . (int)$r['reactivo_id'], ['class' => 'd-inline', 'onsubmit' => "return confirm('¿Eliminar este insumo?');"]) ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                <?= form_close() ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <hr>
                <h6 class="mb-2">Nuevo insumo</h6>
                <?= form_open('reactivos/savereactivo') ?>
                <input type="hidden" name="reactivo_id" value="0">
                <div class="row g-2 mb-2">
                    <div class="col-md-3"><input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre" required></div>
                    <div class="col-md-2">
                        <select name="tipo" class="form-select form-select-sm">
                            <option value="1">Reactivo</option>
                            <option value="2">Kit</option>
                            <option value="3">Insumo</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="text" name="grupo" class="form-control form-control-sm" placeholder="Grupo"></div>
                    <div class="col-md-1"><input type="text" name="unidad_base" class="form-control form-control-sm" placeholder="ml/prueba/unidad"></div>
                    <div class="col-md-1"><input type="number" name="contenido_por_presentacion" class="form-control form-control-sm" placeholder="1" value="1" min="1"></div>
                    <div class="col-md-1"><input type="number" name="stock_minimo" class="form-control form-control-sm" placeholder="Mín" value="0"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm">Agregar</button></div>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= view('partial/footer') ?>
