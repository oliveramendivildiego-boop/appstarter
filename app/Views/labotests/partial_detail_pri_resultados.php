<?php
/**
 * Partial: Valores de referencia (prueba no compuesta).
 * Variables: labotests_info, poblaciones, priresultados, formulas, opciones,
 *            editar_pri, editar_pri_data, formulas_id_canonical, formulas_creadas
 */
$pobMap = [];
foreach ($poblaciones ?? [] as $p) {
    $pobMap[(int) $p['id_poblacion']] = $p['name'] ?? '';
}
$sexoMap = ['ambos' => 'Ambos', 'masculino' => 'Masculino', 'femenino' => 'Femenino'];
?>
<div class="card mt-3">
    <div class="card-header"><strong>Valores de referencia (prueba no compuesta)</strong></div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
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
                <?php foreach ($priresultados ?? [] as $pr): ?>
                <tr>
                    <td><?= esc($pobMap[(int) ($pr['id_poblacion'] ?? 0)] ?? $pr['id_poblacion'] ?? '') ?></td>
                    <td><?= esc($sexoMap[$pr['sexo'] ?? 'ambos'] ?? 'Ambos') ?></td>
                    <td><?= esc($pr['valor_min'] ?? '') ?></td>
                    <td><?= esc($pr['valor_max'] ?? '') ?></td>
                    <td><?= esc($pr['umedida'] ?? '') ?></td>
                    <td><?= esc($formulas[(int) ($pr['formulas_id'] ?? 0)] ?? '') ?></td>
                    <td><?= esc($opciones[(int) ($pr['opcion_id'] ?? 0)] ?? '') ?></td>
                    <td class="text-center">
                        <a href="<?= site_url("labotests/detail/{$labotests_info->prianacategoria_id}") ?>?editarpri=<?= (int) ($pr['priresultados_id'] ?? 0) ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></a>
                        <a href="<?= site_url("labotests/deletepriresultado/" . (int) ($pr['priresultados_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Eliminar estos valores?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <hr>
        <h6 class="mb-3"><?= empty($priresultados) ? 'Agregar valores de referencia' : 'Agregar por población' ?></h6>
        <?= form_open('labotests/savepriresultado', ['class' => 'border p-3 rounded']) ?>
        <input type="hidden" name="prianacategoria_id" value="<?= (int) ($labotests_info->prianacategoria_id ?? 0) ?>">
        <input type="hidden" name="priresultados_id" value="<?= (int) ($editar_pri ?? 0) ?>">
        <div class="row">
            <div class="col-md-2 mb-2">
                <label class="form-label">Población</label>
                <select name="id_poblacion" class="form-control form-control-sm">
                    <?php foreach ($poblaciones ?? [] as $p): ?>
                    <option value="<?= (int) $p['id_poblacion'] ?>" <?= ((int) ($editar_pri_data['id_poblacion'] ?? 3) === (int) $p['id_poblacion']) ? 'selected' : '' ?>><?= esc($p['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Sexo</label>
                <select name="sexo" class="form-control form-control-sm">
                    <option value="ambos" <?= (($editar_pri_data['sexo'] ?? 'ambos') === 'ambos') ? 'selected' : '' ?>>Ambos</option>
                    <option value="masculino" <?= (($editar_pri_data['sexo'] ?? '') === 'masculino') ? 'selected' : '' ?>>Masculino</option>
                    <option value="femenino" <?= (($editar_pri_data['sexo'] ?? '') === 'femenino') ? 'selected' : '' ?>>Femenino</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Valor mín</label>
                <input type="text" name="valor_min" class="form-control form-control-sm" value="<?= esc($editar_pri_data['valor_min'] ?? '') ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Valor máx</label>
                <input type="text" name="valor_max" class="form-control form-control-sm" value="<?= esc($editar_pri_data['valor_max'] ?? '') ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">U. medida</label>
                <input type="text" name="umedida" class="form-control form-control-sm" value="<?= esc($editar_pri_data['umedida'] ?? '') ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Fórmula</label>
                <select name="formulas_id" class="form-control form-control-sm">
                    <?php
                    $priFormulasId = (int) ($editar_pri_data['formulas_id'] ?? 1);
                    $priFormulasIdSel = ($formulas_id_canonical ?? [])[$priFormulasId] ?? $priFormulasId;
                    foreach ($formulas_creadas ?? $formulas ?? [] as $fid => $fname):
                    ?>
                    <option value="<?= $fid ?>" <?= $priFormulasIdSel === $fid ? 'selected' : '' ?>><?= esc($fname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Tipo resultado</label>
                <select name="opcion_id" class="form-control form-control-sm">
                    <?php foreach ($opciones ?? [] as $oid => $oname): ?>
                    <option value="<?= $oid ?>" <?= ((int) ($editar_pri_data['opcion_id'] ?? 3) === $oid) ? 'selected' : '' ?>><?= esc($oname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-2">
            <button type="submit" class="btn btn-primary btn-sm"><?= ($editar_pri ?? 0) ? 'Actualizar' : 'Agregar' ?></button>
            <?php if ($editar_pri ?? 0): ?>
            <a href="<?= site_url("labotests/detail/{$labotests_info->prianacategoria_id}") ?>" class="btn btn-secondary btn-sm">Cancelar</a>
            <?php endif; ?>
        </div>
        <?= form_close() ?>
    </div>
</div>
