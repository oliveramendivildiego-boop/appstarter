<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_leyendas'), 'url' => site_url('leyendas')],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header"><strong>Listado de leyendas</strong></div>
            <div class="card-body">
                <?php if (empty($leyendas ?? [])): ?>
                    <p class="text-muted mb-0">No hay leyendas registradas.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Estado</th>
                                    <th class="text-center" style="width:130px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($leyendas ?? []) as $l): ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($l['titulo'] ?? '') ?></strong><br>
                                            <small class="text-muted"><?= esc(mb_strimwidth((string)($l['mensaje'] ?? ''), 0, 120, '...')) ?></small>
                                        </td>
                                        <td><?= ((int)($l['activo'] ?? 0) === 1) ? 'Activo' : 'Inactivo' ?></td>
                                        <td class="text-center">
                                            <a href="<?= site_url('leyendas?editar=' . (int)($l['leyenda_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                            <a href="<?= site_url('leyendas/delete/' . (int)($l['leyenda_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return uiConfirmLink(this, '¿Eliminar esta leyenda?');"><i class="fa-solid fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><strong><?= !empty($leyenda_editar) ? 'Editar leyenda' : 'Nueva leyenda' ?></strong></div>
            <div class="card-body">
                <?= form_open(site_url('leyendas/save')) ?>
                <input type="hidden" name="leyenda_id" value="<?= (int)($leyenda_editar['leyenda_id'] ?? 0) ?>">
                <div class="mb-3">
                    <label class="form-label">Título</label>
                    <input type="text" name="titulo" class="form-control" required value="<?= esc($leyenda_editar['titulo'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensaje / Leyenda</label>
                    <textarea name="mensaje" class="form-control" rows="6" required><?= esc($leyenda_editar['mensaje'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select name="activo" class="form-select">
                        <option value="1" <?= ((int)($leyenda_editar['activo'] ?? 1) === 1) ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= ((int)($leyenda_editar['activo'] ?? 1) === 0) ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="<?= site_url('leyendas') ?>" class="btn btn-secondary">Cancelar</a>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

