<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<script src="<?= base_url('js/vendor/jquery.validate.min.js') ?>"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

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
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><strong>Lotes - <?= esc($reactivo['nombre'] ?? '') ?></strong> <span class="badge bg-secondary"><?= esc($reactivo['tipo_nombre'] ?? '') ?></span></div>
            <div class="card-body">
                <p class="small text-muted">
                    Unidad base: <strong><?= esc($reactivo['unidad_base'] ?? $reactivo['unidad'] ?? '-') ?></strong>
                    <?php if (!empty($reactivo['contenido_por_presentacion']) && (int)$reactivo['contenido_por_presentacion'] > 1): ?>
                    | Contenido por presentación: <?= (int)$reactivo['contenido_por_presentacion'] ?>
                    <?php endif; ?>
                </p>
                <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Lote</th><th>Stock (unidad base)</th><th>Vencimiento</th><th>Ingreso</th></tr></thead>
                    <tbody>
                        <?php foreach ($lotes ?? [] as $l): ?>
                        <tr class="<?= (!empty($l['fecha_vencimiento']) && $l['fecha_vencimiento'] <= date('Y-m-d') ? 'table-danger' : '') ?>">
                            <td><?= esc($l['codigo_lote'] ?? '') ?></td>
                            <td><?= (int)($l['cantidad'] ?? 0) ?></td>
                            <td><?= !empty($l['fecha_vencimiento']) ? date('d/m/Y', strtotime($l['fecha_vencimiento'])) : '-' ?></td>
                            <td><?= !empty($l['fecha_ingreso']) ? date('d/m/Y', strtotime($l['fecha_ingreso'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <hr>
                <h6>Nuevo lote (entrada)</h6>
                <?= form_open('reactivos/savelote', ['id' => 'form_lote']) ?>
                <input type="hidden" name="reactivo_id" value="<?= (int)($reactivo['reactivo_id'] ?? 0) ?>">
                <div class="row g-2 mb-2 align-items-end">
                    <div class="col-md-2"><label class="form-label small mb-0">Código lote</label><input type="text" name="codigo_lote" id="codigo_lote" class="form-control form-control-sm" placeholder="Ej. L2026001"></div>
                    <div class="col-md-2"><label class="form-label small mb-0">Cantidad</label><input type="number" name="cantidad" id="cantidad_lote" class="form-control form-control-sm" placeholder="Unidad base" min="1"></div>
                    <div class="col-md-2"><label class="form-label small mb-0">Vencimiento</label><input type="text" id="fecha_vencimiento" name="fecha_vencimiento" class="form-control form-control-sm" placeholder="Seleccionar"></div>
                    <div class="col-md-2"><label class="form-label small mb-0">Fecha ingreso</label><input type="text" id="fecha_ingreso" name="fecha_ingreso" class="form-control form-control-sm" placeholder="Seleccionar" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm">Agregar lote</button></div>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Registrar consumo (salida)</strong>
            </div>
            <div class="card-body">
                <?php $nLotesStock = count($lotes_con_stock ?? []); ?>
                <?= form_open('reactivos/registrarsalida', ['id' => 'form_salida']) ?>
                <input type="hidden" name="reactivo_id" value="<?= (int)($reactivo['reactivo_id'] ?? 0) ?>">
                <div class="row g-2 mb-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Lote</label>
                        <select name="lote_id" id="lote_id_salida" class="form-select form-select-sm" <?= $nLotesStock === 0 ? 'disabled' : '' ?> <?= $nLotesStock > 1 ? 'required' : '' ?>>
                            <?php if ($nLotesStock > 1): ?>
                                <option value="">— Elegir lote —</option>
                            <?php endif; ?>
                            <?php foreach ($lotes_con_stock ?? [] as $l): ?>
                                <option value="<?= (int) ($l['lote_id'] ?? 0) ?>" <?= $nLotesStock === 1 ? 'selected' : '' ?>>
                                    <?= esc($l['codigo_lote'] ?? '') ?> (disp. <?= (int) ($l['cantidad'] ?? 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label small">Cantidad</label><input type="number" name="cantidad" id="cantidad_salida" class="form-control form-control-sm" placeholder="Cant." min="1" <?= $nLotesStock === 0 ? 'disabled' : '' ?>></div>
                    <div class="col-md-2"><label class="form-label small">Nº orden (opc.)</label><input type="text" name="numero_orden" class="form-control form-control-sm" placeholder="Folio o ID" autocomplete="off" <?= $nLotesStock === 0 ? 'disabled' : '' ?>></div>
                    <div class="col-md-2"><label class="form-label small">Observaciones</label><input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Opcional" <?= $nLotesStock === 0 ? 'disabled' : '' ?>></div>
                    <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-warning btn-sm" <?= $nLotesStock === 0 ? 'disabled' : '' ?>>Registrar consumo</button></div>
                </div>
                <?php if ($nLotesStock === 0): ?>
                    <p class="small text-danger mb-0">No hay stock en ningún lote; agregue entrada antes de registrar consumo.</p>
                <?php elseif ($nLotesStock === 1): ?>
                    <small class="text-muted">Único lote con stock: se usará por defecto. Sin orden = consumo sin prueba. Con folio = orden existente.</small>
                <?php else: ?>
                    <small class="text-muted">Elija el lote del que se descuenta. Sin orden = consumo sin prueba. Con folio = orden existente.</small>
                <?php endif; ?>
                <?= form_close() ?>
            </div>
        </div>
        <?php if (!empty($movimientos)): ?>
        <div class="card mt-3">
            <div class="card-header"><strong>Últimos movimientos</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Cant.</th><th>Lote</th><th>Orden</th><th>Responsable</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td><?= esc($m['fecha'] ?? '') ?></td>
                            <td><span class="badge <?= ($m['tipo'] ?? '') === 'entrada' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= esc($m['tipo'] ?? '') ?></span></td>
                            <td><?= (int)($m['cantidad'] ?? 0) ?></td>
                            <td><?= esc(trim((string) ($m['codigo_lote'] ?? '')) !== '' ? $m['codigo_lote'] : '—') ?></td>
                            <td><?php
                                $rid = (int) ($m['registro_id'] ?? 0);
                                if ($rid > 0) {
                                    $ordenTxt = trim((string) ($m['numero_orden'] ?? ''));
                                    $etiqueta = $ordenTxt !== '' ? $ordenTxt : ('#' . $rid);
                                    echo '<a href="' . site_url('registers/insumos/' . $rid) . '">' . esc($etiqueta) . '</a>';
                                } else {
                                    echo '<span class="text-muted">Sin prueba</span>';
                                }
                            ?></td>
                            <td><?= esc(trim(($m['first_name'] ?? '') . ' ' . ($m['last_name_fa'] ?? '')) ?: '-') ?></td>
                            <td class="text-nowrap">
                                <?php if (($m['tipo'] ?? '') === 'salida' && !empty($m['lote_id'])): ?>
                                    <?= form_open('reactivos/revertirSalida', ['class' => 'd-inline', 'onsubmit' => "return confirm('¿Anular este consumo y devolver la cantidad al lote?');"]) ?>
                                    <input type="hidden" name="movimiento_id" value="<?= (int) ($m['movimiento_id'] ?? 0) ?>">
                                    <input type="hidden" name="reactivo_id" value="<?= (int) ($reactivo['reactivo_id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0">Anular</button>
                                    <?= form_close() ?>
                                <?php elseif (($m['tipo'] ?? '') === 'salida'): ?>
                                    <span class="text-muted small" title="Registro antiguo sin lote">—</span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<p class="mt-3"><a href="<?= site_url('reactivos') ?>" class="btn btn-secondary">Volver</a></p>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.validate && window.VALIDATE_COMMON_OPTIONS) {
        $('#form_lote').validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
            rules: {
                codigo_lote: { required: true, minlength: 2 },
                cantidad: { required: true, min: 1 }
            },
            messages: {
                codigo_lote: { required: "El código de lote es obligatorio", minlength: "Al menos 2 caracteres" },
                cantidad: { required: "La cantidad es obligatoria", min: "Debe ser al menos 1" }
            }
        }));
        $('#form_salida').validate($.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, {
            rules: {
                cantidad: { required: true, min: 1 },
                lote_id: <?= $nLotesStock > 1 ? '{ required: true }' : '{}' ?>
            },
            messages: {
                cantidad: { required: "La cantidad es obligatoria", min: "Debe ser al menos 1" },
                lote_id: { required: "Seleccione el lote" }
            }
        }));
    }

    flatpickr('#fecha_vencimiento', {
        locale: 'es',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: false,
        disableMobile: true,
        onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); }
    });
    flatpickr('#fecha_ingreso', {
        locale: 'es',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        defaultDate: '<?= date('Y-m-d') ?>',
        allowInput: false,
        disableMobile: true,
        onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); }
    });
});
</script>

<?= $this->endSection() ?>
