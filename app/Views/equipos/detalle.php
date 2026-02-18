<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'equipos']) ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => 'Equipos', 'url' => site_url('equipos')],
    ['label' => esc($equipo['nombre'] ?? ''), 'url' => null],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header"><strong><?= esc($equipo['nombre'] ?? '') ?></strong> - <?= esc($equipo['codigo'] ?? '') ?> / <?= esc($equipo['ubicacion'] ?? '-') ?></div>
</div>

<div class="card">
    <div class="card-header"><strong>Historial de mantenimientos</strong></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Descripción</th></tr></thead>
            <tbody>
                <?php foreach ($mantenimientos ?? [] as $m): ?>
                <tr>
                    <td><?= esc($m['fecha'] ?? '') ?></td>
                    <td><?= (int)($m['tipo'] ?? 1) === 1 ? 'Preventivo' : 'Correctivo' ?></td>
                    <td><?= esc($m['descripcion'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <hr>
        <?= form_open('equipos/savemantenimiento') ?>
        <input type="hidden" name="equipo_id" value="<?= (int)($equipo['equipo_id'] ?? 0) ?>">
        <div class="row g-2 mb-2">
            <div class="col-md-2"><input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-2"><select name="tipo" class="form-select form-select-sm"><option value="1">Preventivo</option><option value="2">Correctivo</option></select></div>
            <div class="col-md-4"><input type="text" name="descripcion" class="form-control form-control-sm" placeholder="Descripción"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm">Registrar</button></div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<p class="mt-3"><a href="<?= site_url('equipos') ?>" class="btn btn-secondary">Volver</a></p>

<?= view('partial/footer') ?>
