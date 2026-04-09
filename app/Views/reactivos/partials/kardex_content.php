<form method="get" action="<?= esc($form_action) ?>" class="row g-2 align-items-end mb-4">
<div class="col-md-2"><label class="form-label small mb-0">Desde</label><input type="date" name="start" class="form-control form-control-sm" value="<?= esc($startDate) ?>"></div>
<div class="col-md-2"><label class="form-label small mb-0">Hasta</label><input type="date" name="end" class="form-control form-control-sm" value="<?= esc($endDate) ?>"></div>
<div class="col-md-3"><label class="form-label small mb-0">Usuario</label><select name="person_id" class="form-select form-select-sm"><option value="0">Todos</option><?php foreach (($empleados ?? []) as $em): ?><option value="<?= (int)$em['person_id'] ?>" <?= ((int)($person_id ?? 0)===(int)$em['person_id'])?'selected':'' ?>><?= esc($em['nombre']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label small mb-0">Insumo</label><select name="reactivo_id" class="form-select form-select-sm"><option value="0">Todos</option><?php foreach (($insumos ?? []) as $ins): ?><option value="<?= (int)$ins['reactivo_id'] ?>" <?= ((int)($reactivo_id ?? 0)===(int)$ins['reactivo_id'])?'selected':'' ?>><?= esc($ins['nombre']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><label class="form-label small mb-0">Tipo</label><select name="tipo" class="form-select form-select-sm"><option value="">Entrada y salida</option><option value="entrada" <?= ($tipo??'')==='entrada'?'selected':'' ?>>Entrada</option><option value="salida" <?= ($tipo??'')==='salida'?'selected':'' ?>>Salida</option></select></div>
<div class="col-12"><button type="submit" class="btn btn-primary btn-sm">Filtrar</button></div>
</form>
<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Fecha</th><th>Insumo</th><th>Tipo</th><th>Cantidad</th><th>Lote</th><th>Orden</th><th>Usuario</th><th>Obs.</th></tr></thead><tbody><?php foreach(($rows??[]) as $m): ?><tr><td><?= esc($m['fecha']??'') ?></td><td><?= esc($m['reactivo_nombre']??'') ?></td><td><?= esc($m['tipo']??'') ?></td><td><?= esc($m['cantidad']??'') ?></td><td><?= esc($m['codigo_lote']??'') ?></td><td><?php $rid=(int)($m['registro_id']??0); if($rid>0): ?><a href="<?= site_url('registers/insumos/'.$rid) ?>"><?= esc(($m['numero_orden']??'')?:('#'.$rid)) ?></a><?php endif; ?></td><td><?= esc(trim($m['usuario_nombre']??'')?:'-') ?></td><td><?= esc($m['observaciones']??'') ?></td></tr><?php endforeach; if(empty($rows)): ?><tr><td colspan="8" class="text-center text-muted">Sin movimientos</td></tr><?php endif; ?></tbody></table></div>

<?php if (!empty($resumen_lotes_por_insumo)): ?>
<hr class="my-4">
<h5 class="mb-3"><i class="fa-solid fa-boxes-stacked me-2"></i>Resumen de lotes (insumos de esta vista)</h5>
<p class="text-muted small mb-3">Por lote: cantidad ingresada (según movimientos de entrada o estimada), saldo actual en unidad base, vencimiento e ingreso del lote. Unidad: según cada insumo.</p>
<?php foreach ($resumen_lotes_por_insumo as $bloque): ?>
    <div class="card border shadow-sm mb-3">
        <div class="card-header py-2 fw-semibold"><?= esc($bloque['nombre'] ?? '') ?> <span class="text-muted small fw-normal">(<?= esc($bloque['unidad'] ?? '') ?>)</span></div>
        <div class="card-body p-0">
            <?php if (empty($bloque['lotes'])): ?>
                <p class="text-muted small mb-0 p-3">Sin lotes registrados para este insumo.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Lote</th>
                            <th class="text-end">Ingreso</th>
                            <th class="text-end">Queda</th>
                            <th>Vencimiento</th>
                            <th>Ingreso al sistema</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bloque['lotes'] as $lt): ?>
                            <?php
                            $venc = $lt['fecha_vencimiento'] ?? '';
                            $vencClass = '';
                            if ($venc !== '' && $venc !== null) {
                                if ($venc <= date('Y-m-d')) {
                                    $vencClass = 'table-danger';
                                }
                            }
                            ?>
                            <tr class="<?= $vencClass ?>">
                                <td><?= esc($lt['codigo_lote'] ?? '') ?></td>
                                <td class="text-end"><?= (int) ($lt['ingreso'] ?? 0) ?></td>
                                <td class="text-end fw-semibold"><?= (int) ($lt['queda'] ?? 0) ?></td>
                                <td><?= !empty($lt['fecha_vencimiento']) ? esc(date('d/m/Y', strtotime($lt['fecha_vencimiento']))) : '—' ?></td>
                                <td><?= !empty($lt['fecha_ingreso']) ? esc(date('d/m/Y', strtotime($lt['fecha_ingreso']))) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
<?php elseif ((int)($reactivo_id ?? 0) === 0 && empty($rows)): ?>
<p class="text-muted small mt-3 mb-0">Filtré el kardex y no hay movimientos; no hay resumen de lotes. Elija un insumo o amplíe fechas para ver movimientos y el detalle de lotes.</p>
<?php endif; ?>
