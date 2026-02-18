<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'reactivos']) ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => 'Insumos', 'url' => site_url('reactivos')],
    ['label' => esc($reactivo['nombre'] ?? 'Lotes'), 'url' => null],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Lotes - <?= esc($reactivo['nombre'] ?? '') ?></strong> <span class="badge bg-secondary"><?= esc($reactivo['tipo_nombre'] ?? '') ?></span></div>
            <div class="card-body">
                <p class="small text-muted">
                    Unidad base: <strong><?= esc($reactivo['unidad_base'] ?? $reactivo['unidad'] ?? '-') ?></strong>
                    <?php if (!empty($reactivo['contenido_por_presentacion']) && (int)$reactivo['contenido_por_presentacion'] > 1): ?>
                    | Contenido por presentación: <?= (int)$reactivo['contenido_por_presentacion'] ?>
                    <?php endif; ?>
                </p>
                <table class="table table-sm">
                    <thead><tr><th>Lote</th><th>Stock (unidad base)</th><th>Vencimiento</th><th>Ingreso</th></tr></thead>
                    <tbody>
                        <?php foreach ($lotes ?? [] as $l): ?>
                        <tr class="<?= (!empty($l['fecha_vencimiento']) && $l['fecha_vencimiento'] <= date('Y-m-d') ? 'table-danger' : '') ?>">
                            <td><?= esc($l['codigo_lote'] ?? '') ?></td>
                            <td><?= (int)($l['cantidad'] ?? 0) ?></td>
                            <td><?= esc($l['fecha_vencimiento'] ?? '-') ?></td>
                            <td><?= esc($l['fecha_ingreso'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <hr>
                <h6>Nuevo lote (entrada)</h6>
                <?= form_open('reactivos/savelote') ?>
                <input type="hidden" name="reactivo_id" value="<?= (int)($reactivo['reactivo_id'] ?? 0) ?>">
                <div class="row g-2 mb-2">
                    <div class="col-md-2"><input type="text" name="codigo_lote" class="form-control form-control-sm" placeholder="Código lote" required></div>
                    <div class="col-md-2"><input type="number" name="cantidad" class="form-control form-control-sm" placeholder="Cantidad (unidad base)" required min="1"></div>
                    <div class="col-md-2"><input type="date" name="fecha_vencimiento" class="form-control form-control-sm" placeholder="Vencimiento"></div>
                    <div class="col-md-2"><input type="date" name="fecha_ingreso" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm">Agregar lote</button></div>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Registrar consumo (salida)</strong>
            </div>
            <div class="card-body">
                <?= form_open('reactivos/registrarsalida') ?>
                <input type="hidden" name="reactivo_id" value="<?= (int)($reactivo['reactivo_id'] ?? 0) ?>">
                <div class="row g-2 mb-2">
                    <div class="col-md-2"><label class="form-label small">Cantidad</label><input type="number" name="cantidad" class="form-control form-control-sm" placeholder="Cant." required min="1"></div>
                    <div class="col-md-2"><label class="form-label small">Nº orden (opcional)</label><input type="number" name="registro_id" class="form-control form-control-sm" placeholder="Sin prueba" min="1"></div>
                    <div class="col-md-3"><label class="form-label small">Observaciones</label><input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Opcional"></div>
                    <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-warning btn-sm">Registrar consumo</button></div>
                </div>
                <small class="text-muted">Sin orden = consumo sin prueba asociada. Con Nº orden = vinculado a esa orden. FIFO por vencimiento. Se registra quién consumió.</small>
                <?= form_close() ?>
            </div>
        </div>
        <?php if (!empty($movimientos)): ?>
        <div class="card mt-3">
            <div class="card-header"><strong>Últimos movimientos</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Cant.</th><th>Orden</th><th>Responsable</th></tr></thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td><?= esc($m['fecha'] ?? '') ?></td>
                            <td><span class="badge <?= ($m['tipo'] ?? '') === 'entrada' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= esc($m['tipo'] ?? '') ?></span></td>
                            <td><?= (int)($m['cantidad'] ?? 0) ?></td>
                            <td><?php $rid = (int)($m['registro_id'] ?? 0); echo $rid > 0 ? '<a href="' . site_url('registers/view/' . $rid) . '" target="_blank">#' . $rid . '</a>' : '<span class="text-muted">Sin prueba</span>'; ?></td>
                            <td><?= esc(trim(($m['first_name'] ?? '') . ' ' . ($m['last_name_fa'] ?? '')) ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<p class="mt-3"><a href="<?= site_url('reactivos') ?>" class="btn btn-secondary">Volver</a></p>

<?= view('partial/footer') ?>
