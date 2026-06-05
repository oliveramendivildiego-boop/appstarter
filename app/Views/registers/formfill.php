<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Editar registro<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_registers'), 'url' => site_url('registers')],
    ['label' => ($register_info->first_name ?? '') . ' ' . ($register_info->last_name_fa ?? ''), 'url' => site_url('registers/view/' . ($register_info->registro_id ?? ''))],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php
$estadosMuestra = [0 => 'Tomada', 1 => 'Recibida', 2 => 'Procesada', 3 => 'Validada'];
if (!empty($muestra)): ?>
<div class="card mb-3 border-info border-0 shadow-sm">
    <div class="card-header bg-info text-white py-2"><i class="fa-solid fa-vial-circle-check me-2"></i> Muestra</div>
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-4"><strong>Código:</strong> <?= esc($muestra['codigo_barras'] ?? '') ?></div>
            <div class="col-md-3"><strong>Estado:</strong> <?= esc($estadosMuestra[(int)($muestra['estado'] ?? 0)] ?? '-') ?></div>
            <div class="col-md-5">
                <?php if ((int)($muestra['estado'] ?? 0) < 3): ?>
                <a href="<?= site_url('registers/cambiarestadomuestra/' . (int)($muestra['muestra_id'] ?? 0) . '/' . ((int)($muestra['estado'] ?? 0) + 1)) ?>" class="btn btn-sm btn-primary">â†’ <?= esc($estadosMuestra[(int)($muestra['estado'] ?? 0) + 1] ?? 'Siguiente') ?></a>
                <?php endif; ?>
                <a href="<?= base_url('qr/generate?data=' . urlencode($muestra['codigo_barras'] ?? '') . '&size=150') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Ver QR</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<fieldset id="customer_basic_info">
<?= csrf_field() ?>
<input type="hidden" name="registro_id" id="registro_id" value="<?= (int)($labotests_namecate ?? 0) ?>">
<?php
$registerModel = $registerModel ?? null;
$last_padre = '';
$pobMap = [];
foreach (($poblaciones_catalogo ?? []) as $pobRow) {
    $pobMap[(int) ($pobRow['id_poblacion'] ?? 0)] = (string) ($pobRow['name'] ?? '');
}
$existentes = [];
foreach ($analisis ?? [] as $row) {
    $n = $row['name'] ?? null;
    if ($n !== null && $n !== '') {
        $existentes[$n] = $row['regvalues'] ?? '';
    }
}
if (empty($pruebas_info)):
    $pruebas_info = $pruebas_info_fallback ?? [];
endif;
if (empty($pruebas_info)):
?>
<div class="alert alert-warning">No hay pruebas para completar en este registro. <a href="<?= site_url('registers') ?>">Volver a registros</a></div>
<?php
else:
$labGruposFirma = [];
$labFirmaLvList = $lab_validators ?? [];
$labFirmaLaList = $lab_approvers ?? [];
foreach ($pruebas_info ?? [] as $pruebaFirmaScan):
    $mostrarFirma = true;
    if (($pruebaFirmaScan['compleja'] ?? 0) == 1) {
        $pidScan = (int) ($pruebaFirmaScan['prianacategoria_id'] ?? 0);
        $valoresFirmaScan = $registerModel
            ? $registerModel->getValoresComplejaSiempre(
                $pidScan,
                $matching_poblacion_ids ?? [],
                isset($register_info->gender) ? (int) $register_info->gender : null
            )
            : [];
        if ($valoresFirmaScan === []) {
            $mostrarFirma = false;
        }
    }
    if (! $mostrarFirma) {
        continue;
    }
    $padreFirma = trim((string) ($pruebaFirmaScan['padre'] ?? ''));
    $grpKeyFirma = \App\Services\RegisterService::labGrupoFirmaKey($padreFirma);
    if ($grpKeyFirma === '') {
        continue;
    }
    if (! isset($labGruposFirma[$grpKeyFirma])) {
        $labGruposFirma[$grpKeyFirma] = ['padre' => $padreFirma, 'pria_ids' => []];
    }
    $pidF = (int) ($pruebaFirmaScan['prianacategoria_id'] ?? 0);
    if ($pidF > 0 && ! in_array($pidF, $labGruposFirma[$grpKeyFirma]['pria_ids'], true)) {
        $labGruposFirma[$grpKeyFirma]['pria_ids'][] = $pidF;
    }
endforeach;
$renderLabFirmaGrupoCerrado = static function (string $padreCerrado) use ($labGruposFirma, $existentes, $labFirmaLvList, $labFirmaLaList): void {
    $padreCerrado = trim($padreCerrado);
    if ($padreCerrado === '') {
        return;
    }
    $grpKey = \App\Services\RegisterService::labGrupoFirmaKey($padreCerrado);
    if ($grpKey === '' || ! isset($labGruposFirma[$grpKey])) {
        return;
    }
    echo view('registers/partials/lab_firma_grupo', [
        'padre_label'      => $labGruposFirma[$grpKey]['padre'],
        'grp_key'          => $grpKey,
        'existentes'       => $existentes,
        'lv_list'          => $labFirmaLvList,
        'la_list'          => $labFirmaLaList,
        'pria_ids_legacy'  => $labGruposFirma[$grpKey]['pria_ids'],
    ]);
};
foreach ($pruebas_info ?? [] as $prueba):
    if (($prueba['padre'] ?? '') != $last_padre):
        if ($last_padre !== '') {
            $renderLabFirmaGrupoCerrado($last_padre);
            echo '</div>';
        }
        echo '<div class="row mb-3"><div class="col-12"><strong class="text-uppercase">' . esc($prueba['padre'] ?? '') . '</strong></div>';
        $last_padre = $prueba['padre'] ?? '';
    endif;

    $mostrarPrueba = true;
    if (($prueba['compleja'] ?? 0) == 1) {
        $prianacategoriaIdTmp = (int)($prueba['prianacategoria_id'] ?? 0);
        $valoresTmp = $registerModel
            ? $registerModel->getValoresComplejaSiempre(
                $prianacategoriaIdTmp,
                $matching_poblacion_ids ?? [],
                isset($register_info->gender) ? (int) $register_info->gender : null
            )
            : [];
        if (empty($valoresTmp)) {
            $mostrarPrueba = false;
            $refsTabla = $registerModel ? $registerModel->getSecReferenciasConsolidadasSinColapsar($prianacategoriaIdTmp) : [];
            echo '<div class="col-12 mb-3">';
            echo '<div class="alert alert-warning mb-2">';
            echo '<i class="fa-solid fa-triangle-exclamation me-2"></i>';
            echo 'La prueba <strong>' . esc($prueba['hijo'] ?? ('ID ' . $prianacategoriaIdTmp)) . '</strong> no tiene valores de referencia para la población/edad del paciente.';
            echo '</div>';
            if (!empty($refsTabla)) {
                echo '<div class="table-responsive border rounded bg-white">';
                echo '<table class="table table-sm table-striped mb-0">';
                echo '<thead><tr><th>Parámetro</th><th>Población</th><th>Género</th><th>Valor mín.</th><th>Valor máx.</th><th>Unidad</th></tr></thead><tbody>';
                foreach ($refsTabla as $refRow) {
                    $sexoRef = trim((string) ($refRow['sexo'] ?? ''));
                    if ($sexoRef === '') {
                        $sexoRef = 'ambos';
                    }
                    echo '<tr>';
                    echo '<td>' . esc($refRow['nombre'] ?? '') . '</td>';
                    echo '<td>' . esc($refRow['poblacion_nombre'] ?? '') . '</td>';
                    echo '<td>' . esc(ucfirst($sexoRef)) . '</td>';
                    echo '<td>' . esc((string) ($refRow['valor_min'] ?? '')) . '</td>';
                    echo '<td>' . esc((string) ($refRow['valor_max'] ?? '')) . '</td>';
                    echo '<td>' . esc((string) ($refRow['umedida'] ?? '')) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            } else {
                echo '<div class="small text-muted">No hay filas de referencia configuradas para esta prueba.</div>';
            }
            echo '</div>';
        }
    }
    if (!$mostrarPrueba) continue;

    $sinReferenciaSimple = (($prueba['compleja'] ?? 0) == 0) && ((int) ($prueba['priresultados_id'] ?? 0) < 1);
    if ($sinReferenciaSimple):
        $prianacategoriaIdTmp = (int) ($prueba['prianacategoria_id'] ?? 0);
        $refsSimple = $registerModel ? $registerModel->getAllPriResultadosByPrianacategoriaForReport($prianacategoriaIdTmp) : [];
        echo '<div class="col-12 mb-3">';
        echo '<div class="alert alert-warning mb-2">';
        echo '<i class="fa-solid fa-triangle-exclamation me-2"></i>';
        echo 'La prueba <strong>' . esc($prueba['hijo'] ?? ('ID ' . $prianacategoriaIdTmp)) . '</strong> no tiene valores de referencia para la población/edad del paciente.';
        echo '</div>';
        if (!empty($refsSimple)) {
            echo '<div class="table-responsive border rounded bg-white">';
            echo '<table class="table table-sm table-striped mb-0">';
            echo '<thead><tr><th>Parámetro</th><th>Población</th><th>Género</th><th>Valor mín.</th><th>Valor máx.</th><th>Unidad</th></tr></thead><tbody>';
            foreach ($refsSimple as $refRow) {
                $idPob = (int) ($refRow['id_poblacion'] ?? 0);
                $nomPob = trim((string) ($pobMap[$idPob] ?? ''));
                if ($nomPob === '') {
                    $nomPob = (string) $idPob;
                }
                $sexoRef = trim((string) ($refRow['sexo'] ?? ''));
                if ($sexoRef === '') {
                    $sexoRef = 'ambos';
                }
                echo '<tr>';
                echo '<td>' . esc($refRow['nombre'] ?? '') . '</td>';
                echo '<td>' . esc($nomPob) . '</td>';
                echo '<td>' . esc(ucfirst($sexoRef)) . '</td>';
                echo '<td>' . esc((string) ($refRow['valor_min'] ?? '')) . '</td>';
                echo '<td>' . esc((string) ($refRow['valor_max'] ?? '')) . '</td>';
                echo '<td>' . esc((string) ($refRow['umedida'] ?? '')) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="small text-muted">No hay filas de referencia configuradas para esta prueba.</div>';
        }
        echo '</div>';
        continue;
    endif;

    if (($prueba['compleja'] ?? 0) == 0):
        $formulaExprNoc = trim((string) ($prueba['formula_expresion'] ?? ''));
        $formulaExprNoc = preg_replace('/\b1\b/', '[valor]', $formulaExprNoc);
        $formulasIdNoc = (int) ($prueba['formulas_id'] ?? 1);
        $esCalculadaNoc = $formulaExprNoc !== '' && $formulasIdNoc > 1;
        $idsEnFormulaNoc = $esCalculadaNoc && preg_match_all('/c_\d+/', $formulaExprNoc, $mNoc) ? array_unique($mNoc[0]) : [];
        $esFormulaValorNoc = $esCalculadaNoc && count($idsEnFormulaNoc) === 0;
        if (($prueba['opcion_id'] ?? '') != 3 && ($prueba['opcion_id'] ?? '') != '' && (int)($prueba['opcion_id'] ?? 0) > 0):
            $pMin = trim($prueba['valor_min'] ?? ''); $pMax = trim($prueba['valor_max'] ?? ''); $pUmed = trim($prueba['umedida'] ?? '');
            $pRef = ($pMin !== '' || $pMax !== '') ? ' <small class="text-muted">(Ref: ' . ($pMin ?: 'â€¦') . ' - ' . ($pMax ?: 'â€¦') . ($pUmed ? ' ' . $pUmed : '') . ')</small>' : '';
            $valores = $registerModel ? $registerModel->getOpciones((int)$prueba['opcion_id']) : [];
            echo '<div class="col-md-6 mb-3"><div class="mb-3">';
            $nocId = 'noc_' . ($prueba['priresultados_id'] ?? '');
            echo '<label for="' . esc($nocId) . '" class="form-label">' . esc($prueba['hijo'] ?? '') . $pRef . ':</label>';
            $extra = 'id="' . esc($nocId) . '" class="form-control input-con-ref"';
            if ($pMin !== '') $extra .= ' data-min="' . esc($pMin) . '"'; if ($pMax !== '') $extra .= ' data-max="' . esc($pMax) . '"';
            echo build_select($nocId, $valores, $existentes[$nocId] ?? '', $extra);
            echo '<span class="invalid-feedback d-block" data-msg-for="' . esc($nocId) . '"></span></div></div>';
        elseif (($prueba['opcion_id'] ?? '') == 3):
            $rid = $prueba['priresultados_id'] ?? $prueba['prianacategoria_id'] ?? '';
            $pMin = trim($prueba['valor_min'] ?? ''); $pMax = trim($prueba['valor_max'] ?? ''); $pUmed = trim($prueba['umedida'] ?? '');
            $pRef = ($pMin !== '' || $pMax !== '') ? ' <small class="text-muted">(Ref: ' . ($pMin ?: 'â€¦') . ' - ' . ($pMax ?: 'â€¦') . ($pUmed ? ' ' . $pUmed : '') . ')</small>' : '';
            echo '<div class="col-md-6 mb-3"><div class="mb-3">';
            $nocRid = 'noc_' . esc($rid);
            echo '<label for="' . $nocRid . '" class="form-label">' . esc($prueba['hijo'] ?? '') . $pRef . ($esCalculadaNoc ? ' <span class="badge badge-calculada">' . ($esFormulaValorNoc ? 'FÃ³rmula (valor Ã— expresiÃ³n)' : 'Calculada') . '</span>' : '') . ':</label>';
            $valRid = $existentes['noc_' . $rid] ?? '';
            $classes = $esCalculadaNoc ? 'form-control formula-calculada input-con-ref' : 'form-control input-con-ref';
            $attrs = 'name="noc_' . esc($rid) . '" id="noc_' . esc($rid) . '" class="' . $classes . '" value="' . esc($valRid) . '"';
            if ($pMin !== '') $attrs .= ' data-min="' . esc($pMin) . '"'; if ($pMax !== '') $attrs .= ' data-max="' . esc($pMax) . '"';
            if ($pUmed !== '') $attrs .= ' data-umedida="' . esc($pUmed) . '"';
            if ($esCalculadaNoc) {
                $attrs .= ' data-formula="' . esc($formulaExprNoc) . '"';
                if ($formulasIdNoc > 1) $attrs .= ' data-formula-id="' . $formulasIdNoc . '"';
                $attrs .= $esFormulaValorNoc ? ' placeholder="Escriba el valor (ej. 50)"' : ' placeholder="Escriba o use la sugerencia"';
            }
            echo '<input type="text" ' . $attrs . '><span class="invalid-feedback d-block" data-msg-for="noc_' . esc($rid) . '"></span>';
            if ($esCalculadaNoc) {
                echo '<span class="sugerencia-calculada small text-muted mt-1 d-block" data-sugerencia-for="noc_' . esc($rid) . '" role="button" tabindex="0" title="Clic para usar este valor">Sugerencia: â€”</span>';
            }
            echo '</div></div>';
        else:
            $rid = $prueba['priresultados_id'] ?? $prueba['prianacategoria_id'] ?? '';
            $pMin = trim($prueba['valor_min'] ?? ''); $pMax = trim($prueba['valor_max'] ?? ''); $pUmed = trim($prueba['umedida'] ?? '');
            $pRef = ($pMin !== '' || $pMax !== '') ? ' <small class="text-muted">(Ref: ' . ($pMin ?: 'â€¦') . ' - ' . ($pMax ?: 'â€¦') . ($pUmed ? ' ' . $pUmed : '') . ')</small>' : ' <small class="text-muted">(Por favor revise los valores de referencia en AnÃ¡lisis clÃ­nico)</small>';
            echo '<div class="col-md-6 mb-3"><div class="mb-3">';
            echo '<label for="noc_' . esc($rid) . '" class="form-label">' . esc($prueba['hijo'] ?? '') . $pRef . ':</label>';
            $valRid = $existentes['noc_' . $rid] ?? '';
            $attrs = 'name="noc_' . esc($rid) . '" id="noc_' . esc($rid) . '" class="form-control input-con-ref" value="' . esc($valRid) . '"';
            if ($pMin !== '') $attrs .= ' data-min="' . esc($pMin) . '"'; if ($pMax !== '') $attrs .= ' data-max="' . esc($pMax) . '"';
            echo '<input type="text" ' . $attrs . '><span class="invalid-feedback d-block" data-msg-for="noc_' . esc($rid) . '"></span>';
            echo '</div></div>';
        endif;
    elseif (($prueba['compleja'] ?? 0) == 2):
        $prianacategoriaIdCultivo = (int) ($prueba['prianacategoria_id'] ?? 0);
        echo view('registers/partial_cultivo_fill', [
            'prianacategoria_id' => $prianacategoriaIdCultivo,
            'titulo_prueba'      => (string) ($prueba['hijo'] ?? ''),
            'existentes'         => $existentes,
            'registerModel'      => $registerModel,
        ]);
    else:
        $prianacategoriaId = (int)($prueba['prianacategoria_id'] ?? 0);
        $valores = $valoresTmp ?? [];
        if ($valores === []) {
            $valores = $registerModel
                ? $registerModel->getValoresComplejaSiempre(
                    $prianacategoriaId,
                    $matching_poblacion_ids ?? [],
                    isset($register_info->gender) ? (int) $register_info->gender : null
                )
                : [];
        }
        $nombreToCid = [];
        foreach ($valores as $vv) {
            if (! empty($vv['es_separador'])) {
                continue;
            }
            $nom = trim($vv['nombre'] ?? '');
            if ($nom !== '') {
                $nombreToCid[$nom] = 'c_' . ($vv['secanacategoria_id'] ?? '');
            }
        }
        $cidToNombre = array_flip($nombreToCid);
        foreach ($valores as $v):
            if (! empty($v['es_separador'])) {
                echo '<div class="col-12"><div class="register-form-seccion-separador">' . esc($v['nombre'] ?? '') . '</div></div>';
                continue;
            }
            $vMin = trim($v['valor_min'] ?? '');
            $vMax = trim($v['valor_max'] ?? '');
            $umedida = trim($v['umedida'] ?? '');
            $refText = ($vMin !== '' || $vMax !== '') ? ' <small class="text-muted">(Ref: ' . ($vMin !== '' ? $vMin : 'â€¦') . ' - ' . ($vMax !== '' ? $vMax : 'â€¦') . ($umedida !== '' ? ' ' . $umedida : '') . ')</small>' : '';
            $cId = 'c_' . ($v['secanacategoria_id'] ?? '');
            $nombrePrueba = trim($v['nombre'] ?? '');
            $valorExiste = $existentes[$cId] ?? ($prianacategoriaId > 0 && $nombrePrueba !== '' ? ($existentes[$prianacategoriaId . '|' . $nombrePrueba] ?? '') : '');
            if (($v['opcion_id'] ?? 0) != 3):
                $opts = $registerModel ? $registerModel->getOpciones((int)($v['opcion_id'] ?? 0)) : [];
                echo '<div class="col-md-6 mb-3"><div class="mb-3">';
                echo '<label for="' . esc($cId) . '" class="form-label">' . esc($v['nombre'] ?? '') . $refText . ':</label>';
                $extra = 'id="' . esc($cId) . '" class="form-control input-con-ref"';
                if ($prianacategoriaId > 0) $extra .= ' data-prianacategoria-id="' . $prianacategoriaId . '"';
                if ($nombrePrueba !== '') $extra .= ' data-prueba="' . esc($nombrePrueba) . '"';
                if ($vMin !== '') $extra .= ' data-min="' . esc($vMin) . '"';
                if ($vMax !== '') $extra .= ' data-max="' . esc($vMax) . '"';
                echo build_select($cId, $opts, $valorExiste, $extra);
                echo '<span class="invalid-feedback d-block" data-msg-for="' . esc($cId) . '"></span></div></div>';
            else:
                $cId = 'c_' . ($v['secanacategoria_id'] ?? '');
                $expresion = trim($v['formula_expresion'] ?? '');
                if ($expresion !== '' && !empty($nombreToCid)) {
                    uksort($nombreToCid, function ($a, $b) { return strlen($b) - strlen($a); });
                    foreach ($nombreToCid as $nom => $cid) {
                        $expresion = str_replace('[' . $nom . ']', $cid, $expresion);
                    }
                }
                $formulaConNombres = '';
                if ($expresion !== '' && !empty($cidToNombre)) {
                    $formulaConNombres = preg_replace('/\b1\b/', '[valor]', $expresion);
                    $cids = array_keys($cidToNombre);
                    usort($cids, function ($a, $b) { return strlen($b) - strlen($a); });
                    foreach ($cids as $cid) {
                        $formulaConNombres = str_replace($cid, '[' . $cidToNombre[$cid] . ']', $formulaConNombres);
                    }
                } else {
                    $formulaConNombres = $expresion;
                }
                $formulasId = (int)($v['formulas_id'] ?? 1);
                $esCalculada = $expresion !== '' && $formulasId !== 1;
                $idsEnFormula = $esCalculada && preg_match_all('/c_\d+/', $expresion, $m) ? array_unique($m[0]) : [];
                $esFormulaValor = $esCalculada && count($idsEnFormula) === 1 && in_array($cId, $idsEnFormula, true);
                echo '<div class="col-md-6 mb-3"><div class="mb-3">';
                echo '<label for="' . esc($cId) . '" class="form-label">' . esc($v['nombre'] ?? '') . $refText . ($esCalculada ? ' <span class="badge badge-calculada">' . ($esFormulaValor ? 'FÃ³rmula (valor Ã— expresiÃ³n)' : 'Calculada') . '</span>' : '') . ':</label>';
                if ($esCalculada) {
                    if ($esFormulaValor) {
                        $attrs = 'name="' . esc($cId) . '" id="' . esc($cId) . '" class="form-control formula-calculada input-con-ref" value="' . esc($valorExiste) . '" data-formula="' . esc($formulaConNombres) . '" placeholder="Escriba el valor (ej. 50)"';
                        if ($formulasId > 1) $attrs .= ' data-formula-id="' . (int)$formulasId . '"';
                        if ($prianacategoriaId > 0) $attrs .= ' data-prianacategoria-id="' . $prianacategoriaId . '"';
                        if ($nombrePrueba !== '') $attrs .= ' data-prueba="' . esc($nombrePrueba) . '"';
                        if ($vMin !== '') $attrs .= ' data-min="' . esc($vMin) . '"'; if ($vMax !== '') $attrs .= ' data-max="' . esc($vMax) . '"';
                        if ($umedida !== '') $attrs .= ' data-umedida="' . esc($umedida) . '"';
                        echo '<input type="text" ' . $attrs . '>';
                        echo '<span class="sugerencia-calculada small text-muted mt-1 d-block" data-sugerencia-for="' . esc($cId) . '" role="button" tabindex="0" title="Clic para usar este valor">Sugerencia: â€”</span>';
                        echo '<span class="invalid-feedback d-block" data-msg-for="' . esc($cId) . '"></span>';
                    } else {
                        $attrs = 'id="' . esc($cId) . '" class="form-control formula-calculada input-con-ref" value="' . esc($valorExiste) . '" data-formula="' . esc($formulaConNombres) . '" placeholder="Escriba o use la sugerencia"';
                        if ($formulasId > 1) $attrs .= ' data-formula-id="' . (int)$formulasId . '"';
                        if ($prianacategoriaId > 0) $attrs .= ' data-prianacategoria-id="' . $prianacategoriaId . '"';
                        if ($nombrePrueba !== '') $attrs .= ' data-prueba="' . esc($nombrePrueba) . '"';
                        if ($vMin !== '') $attrs .= ' data-min="' . esc($vMin) . '"'; if ($vMax !== '') $attrs .= ' data-max="' . esc($vMax) . '"';
                        if ($umedida !== '') $attrs .= ' data-umedida="' . esc($umedida) . '"';
                        echo '<input type="text" ' . $attrs . '>';
                        echo '<span class="sugerencia-calculada small text-muted mt-1 d-block" data-sugerencia-for="' . esc($cId) . '" role="button" tabindex="0" title="Clic para usar este valor">Sugerencia: â€”</span>';
                        echo '<span class="invalid-feedback d-block" data-msg-for="' . esc($cId) . '"></span>';
                    }
                } else {
                    $attrs = 'name="' . esc($cId) . '" id="' . esc($cId) . '" class="form-control input-con-ref" value="' . esc($valorExiste) . '"';
                    if ($prianacategoriaId > 0) $attrs .= ' data-prianacategoria-id="' . $prianacategoriaId . '"';
                    if ($nombrePrueba !== '') $attrs .= ' data-prueba="' . esc($nombrePrueba) . '"';
                    if ($vMin !== '') $attrs .= ' data-min="' . esc($vMin) . '"'; if ($vMax !== '') $attrs .= ' data-max="' . esc($vMax) . '"';
                    if ($umedida !== '') $attrs .= ' data-umedida="' . esc($umedida) . '"';
                    echo '<input type="text" ' . $attrs . '><span class="invalid-feedback d-block" data-msg-for="' . esc($cId) . '"></span>';
                }
                echo '</div></div>';
            endif;
        endforeach;
    endif;
endforeach;
if ($last_padre !== '') {
    $renderLabFirmaGrupoCerrado($last_padre);
    echo '</div>';
}
endif;
?>
<?php if (!empty($pruebas_info)): ?>
<?php if (!empty($leyendas_enabled)): ?>
<div class="row mt-2">
    <div class="col-12">
        <?php if (!empty($leyendas_activas)): ?>
        <label for="leyenda_sugerida" class="form-label">Sugerencias de leyendas</label>
        <select id="leyenda_sugerida" class="form-select mb-2">
            <option value="">-- Seleccionar leyenda --</option>
            <?php foreach (($leyendas_activas ?? []) as $leyenda): ?>
                <?php $txtLey = trim((string)($leyenda['mensaje'] ?? '')); if ($txtLey === '') continue; ?>
                <option value="<?= esc($txtLey) ?>"><?= esc($leyenda['titulo'] ?? 'Leyenda') ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <label for="comentario_resultado" class="form-label fw-bold">Comentarios / Nota</label>
        <textarea id="comentario_resultado" class="form-control" rows="3" placeholder="Escriba una nota o seleccione una leyenda sugerida..."><?= esc((string)($register_info->comentario_resultado ?? '')) ?></textarea>
        <small class="text-muted">Puede usar una leyenda sugerida y luego editar el texto manualmente.</small>
    </div>
</div>
<?php endif; ?>
<div class="mt-3">
    <button type="button" id="submit" name="btn_submit" class="btn btn-primary"><?= !empty($existentes) ? ucfirst(lang('Common.common_edit')) : lang('Common.common_submit') ?></button>
</div>
<?php endif; ?>
<!-- Modal confirmación de envío (reemplaza window.confirm) -->
<div class="modal fade" id="modalConfirmEnviar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Confirmar envío</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="modalConfirmEnviarTexto" class="text-muted" style="white-space: pre-wrap;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="modalConfirmEnviarAceptar">Enviar de todos modos</button>
            </div>
        </div>
    </div>
</div>
</fieldset>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var decimalesSugerencia = <?= json_encode(max(0, min(10, (int)($decimales_sugerencia ?? 2)))) ?>;
    function formatearSugerencia(val) {
        if (val === '' || val == null) return '';
        var n = parseFloat(String(val).replace(',', '.'));
        if (isNaN(n)) return val;
        if (n === Math.round(n)) return String(Math.round(n));
        return decimalesSugerencia === 0 ? String(Math.round(n)) : n.toFixed(decimalesSugerencia);
    }
    function buscarInputPorPrueba(nombre, formOrDoc) {
        var root = formOrDoc && formOrDoc.nodeType === 9 ? formOrDoc : (formOrDoc || document);
        var key = (nombre || '').trim().toLowerCase();
        if (key === '') return null;
        var els = root.querySelectorAll('input[data-prueba], select[data-prueba]');
        for (var j = 0; j < els.length; j++) {
            var attr = (els[j].getAttribute('data-prueba') || '').trim().toLowerCase();
            if (attr === key) return els[j];
        }
        return null;
    }
    function evaluarFormulaPorNombres(expr, inputActual) {
        if (!expr || typeof expr !== 'string') return '';
        expr = expr.replace(/\s+/g, ' ').replace(/\u00d7/g, '*').replace(/\u00f7/g, '/');
        var nombres = (expr.match(/\[([^\]]+)\]/g) || []).map(function(m) { return m.slice(1, -1).trim(); });
        var unicos = nombres.filter(function(v, i, a) { return a.indexOf(v) === i; });
        var resultExpr = expr;
        var root = inputActual && inputActual.closest('form') ? inputActual.closest('form') : document;
        for (var i = 0; i < unicos.length; i++) {
            var nom = unicos[i];
            var val = '';
            if (nom.toLowerCase() === 'valor' && inputActual) {
                val = (inputActual.value || '').trim().replace(',', '.');
            } else {
                var inp = buscarInputPorPrueba(nom, root);
                val = inp ? (inp.value || '').trim().replace(',', '.') : '';
            }
            if (val === '' || isNaN(parseFloat(val))) return '';
            var numVal = parseFloat(val);
            var re = new RegExp('\\[' + nom.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\]', 'gi');
            resultExpr = resultExpr.replace(re, String(numVal));
        }
        resultExpr = resultExpr.replace(/\s+/g, ' ');
        if (!/^[\d\s\+\-\*\/\(\)\.]+$/.test(resultExpr)) return '';
        try {
            var r = eval(resultExpr);
            return (typeof r === 'number' && !isNaN(r)) ? String(r) : '';
        } catch (e) { return ''; }
    }
    function evaluarFormula(expr) {
        if (!expr || typeof expr !== 'string') return '';
        expr = expr.replace(/\s+/g, ' ').replace(/\u00d7/g, '*').replace(/\u00f7/g, '/');
        var ids = (expr.match(/c_\d+/g) || []).filter(function(v,i,a){ return a.indexOf(v)===i; });
        var reemplazos = {};
        for (var i = 0; i < ids.length; i++) {
            var el = document.getElementById(ids[i]);
            var v = el ? (el.value || '').trim() : '';
            if (v === '' || isNaN(parseFloat(v))) return '';
            reemplazos[ids[i]] = parseFloat(v);
        }
        var resultExpr = expr;
        ids.sort(function(a,b){ return b.length - a.length; });
        for (var i = 0; i < ids.length; i++) {
            resultExpr = resultExpr.split(ids[i]).join(reemplazos[ids[i]]);
        }
        if (!/^[\d\s\+\-\*\/\(\)\.]+$/.test(resultExpr)) return '';
        try {
            var r = eval(resultExpr);
            return (typeof r === 'number' && !isNaN(r)) ? String(r) : '';
        } catch (e) { return ''; }
    }
    function actualizarCalculadas() {
        document.querySelectorAll('.formula-calculada').forEach(function(el) {
            var f = el.getAttribute('data-formula');
            var valorSugerido = '';
            if (f) valorSugerido = evaluarFormulaPorNombres(f, el);
            if (valorSugerido === '' && f) valorSugerido = evaluarFormula(f);
            var valorMostrar = valorSugerido !== '' ? formatearSugerencia(valorSugerido) : '';
            var spanSug = document.querySelector('[data-sugerencia-for="' + el.id + '"]');
            if (spanSug) {
                spanSug.textContent = valorMostrar !== '' ? 'Sugerencia: ' + valorMostrar : 'Sugerencia: \u2014';
                spanSug.setAttribute('data-valor', valorMostrar);
            }
            validarInputAlEscribir(el);
        });
    }
    function validarInputAlEscribir(el) {
        var min = el.getAttribute('data-min');
        var max = el.getAttribute('data-max');
        var msgEl = document.querySelector('[data-msg-for="' + el.id + '"]');
        if (!msgEl) return;
        var val = (el.value || '').trim().replace(',', '.');
        var num = val !== '' && !isNaN(parseFloat(val)) ? parseFloat(val) : NaN;
        var valorMostrar = val !== '' ? val : '';
        msgEl.textContent = '';
        msgEl.innerHTML = '';
        el.classList.remove('is-invalid');
        if ((min === null || min === '') && (max === null || max === '')) return;
        if (isNaN(num)) return;
        var minNum = (min !== null && min !== '') ? parseFloat(String(min).replace(',', '.')) : null;
        var maxNum = (max !== null && max !== '') ? parseFloat(String(max).replace(',', '.')) : null;
        if (minNum !== null && !isNaN(minNum) && num < minNum) {
            msgEl.innerHTML = 'Valor: <strong>' + valorMostrar + '</strong>. Por debajo del rango de referencia (min. ' + min + ').';
            el.classList.add('is-invalid');
        } else if (maxNum !== null && !isNaN(maxNum) && num > maxNum) {
            msgEl.innerHTML = 'Valor: <strong>' + valorMostrar + '</strong>. Por encima del rango de referencia (max. ' + max + ').';
            el.classList.add('is-invalid');
        }
    }
    document.addEventListener('click', function(e) {
        var t = e.target.closest('.aplicar-valor');
        if (t && t.dataset.valor !== undefined && t.dataset.inputId) {
            var inp = document.getElementById(t.dataset.inputId);
            if (inp) {
                inp.value = t.dataset.valor;
                inp.classList.remove('is-invalid');
                var msgEl = document.querySelector('[data-msg-for="' + inp.id + '"]');
                if (msgEl) { msgEl.textContent = ''; msgEl.innerHTML = ''; }
                actualizarCalculadas();
                validarInputAlEscribir(inp);
            }
            return;
        }
        var sug = e.target.closest('.sugerencia-calculada');
        if (sug && sug.dataset.sugerenciaFor && sug.dataset.valor !== undefined && sug.dataset.valor !== '') {
            var inp = document.getElementById(sug.dataset.sugerenciaFor);
            if (inp) {
                inp.value = sug.dataset.valor;
                inp.classList.remove('is-invalid');
                var msgEl = document.querySelector('[data-msg-for="' + inp.id + '"]');
                if (msgEl) { msgEl.textContent = ''; msgEl.innerHTML = ''; }
                actualizarCalculadas();
                validarInputAlEscribir(inp);
                sug.textContent = 'Sugerencia: \u2014';
                sug.removeAttribute('data-valor');
                inp.classList.add('input-sugerencia-aplicada');
            }
        }
    });
    document.querySelectorAll('.sugerencia-calculada').forEach(function(el) {
        el.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); } });
    });
    document.querySelectorAll('.input-con-ref:not(.formula-calculada)').forEach(function(el) {
        el.addEventListener('input', function() { actualizarCalculadas(); validarInputAlEscribir(this); });
        el.addEventListener('change', function() { actualizarCalculadas(); validarInputAlEscribir(this); });
    });
    document.querySelectorAll('.formula-calculada').forEach(function(el) {
        el.addEventListener('input', function() { actualizarCalculadas(); validarInputAlEscribir(this); });
        el.addEventListener('change', function() { actualizarCalculadas(); validarInputAlEscribir(this); });
    });
    document.querySelectorAll('.input-con-ref').forEach(function(el) {
        el.addEventListener('input', function() { validarInputAlEscribir(this); });
        el.addEventListener('change', function() { validarInputAlEscribir(this); });
    });
    function validarAntesDeEnviar() {
        var faltantes = [];
        var fueraRango = [];
        document.querySelectorAll('.input-con-ref').forEach(function(el) {
            var min = el.getAttribute('data-min');
            var max = el.getAttribute('data-max');
            var labelEl = document.querySelector('label[for="' + el.id + '"]');
            var label = labelEl ? (labelEl.textContent || el.id).trim() : el.id;
            var val = (el.value || '').trim();
            if (min !== null && min !== '' || max !== null && max !== '') {
                if (val === '') {
                    faltantes.push(label);
                    return;
                }
                var num = parseFloat(val.replace(',', '.'));
                if (!isNaN(num)) {
                    var minNum = (min !== null && min !== '') ? parseFloat(String(min).replace(',', '.')) : null;
                    var maxNum = (max !== null && max !== '') ? parseFloat(String(max).replace(',', '.')) : null;
                    if (minNum !== null && !isNaN(minNum) && num < minNum) fueraRango.push(label + ' (valor ' + val + ' < ' + min + ')');
                    else if (maxNum !== null && !isNaN(maxNum) && num > maxNum) fueraRango.push(label + ' (valor ' + val + ' > ' + max + ')');
                }
            }
        });
        if (faltantes.length || fueraRango.length) {
            var msg = '';
            if (faltantes.length) msg += 'Complete los siguientes campos: ' + faltantes.join(', ') + '.\n';
            if (fueraRango.length) msg += 'Valores fuera del rango de referencia: ' + fueraRango.join('; ') + '.\n';
            msg += '¿Desea enviar igualmente?';
            return { needsConfirm: true, msg: msg + '\n\nCancelar = corregir datos. Aceptar = enviar de todos modos.' };
        }
        return { needsConfirm: false, msg: '' };
    }
    var submitBtn = document.getElementById('submit');
    if (!submitBtn) return;
    var leyendaSel = document.getElementById('leyenda_sugerida');
    if (leyendaSel) {
        leyendaSel.addEventListener('change', function() {
            if (!this.value) return;
            var txt = document.getElementById('comentario_resultado');
            if (!txt) return;
            txt.value = this.value;
            txt.focus();
        });
    }
    function syncCsrfToken(csrfName, csrfToken) {
        if (!csrfName || !csrfToken) return;
        window.CI_CSRF_TOKEN_NAME = csrfName;
        window.CI_CSRF_TOKEN = csrfToken;
        document.querySelectorAll('input[name="' + csrfName + '"], input[name*="csrf"]').forEach(function(inp) {
            inp.name = csrfName;
            inp.value = csrfToken;
        });
    }
    function getCsrfPair() {
        var inp = document.querySelector('#customer_basic_info input[name*="csrf"]')
            || document.querySelector('input[name*="csrf"]');
        if (inp && inp.name && inp.value) {
            return { name: inp.name, value: inp.value };
        }
        if (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' && window.CI_CSRF_TOKEN) {
            return { name: window.CI_CSRF_TOKEN_NAME, value: window.CI_CSRF_TOKEN };
        }
        var metaName = document.querySelector('meta[name="csrf-token-name"]');
        var metaTok = document.querySelector('meta[name="csrf-token"]');
        if (metaName && metaTok) {
            var n = metaName.getAttribute('content');
            var v = metaTok.getAttribute('content');
            if (n && v) return { name: n, value: v };
        }
        return null;
    }
    function ejecutarEnvio() {
        if (submitBtn) submitBtn.disabled = true;
        var datos = [];
        document.querySelectorAll('.input-con-ref').forEach(function(el) {
            var valor = (el.value || '').trim();
            var registroId = document.getElementById('registro_id').value;
            if (!registroId || valor === '') return;
            // c_* y noc_* son únicos por fila (secanacategoria_id / priresultados_id).
            // No usar priId|nombre aquí: varias sub-pruebas pueden llamarse igual ("Sensible")
            // y colisionarían en regvalues.name.
            var id = el.id || '';
            if (id.indexOf('c_') !== 0 && id.indexOf('noc_') !== 0) {
                var priId = el.getAttribute('data-prianacategoria-id');
                var nombrePrueba = el.getAttribute('data-prueba');
                if (priId && nombrePrueba) {
                    id = priId + '|' + nombrePrueba;
                }
            }
            if (!id) return;
            datos.push({ id: id, valor: valor, registro_id: registroId });
        });

        var cultivoInputs = document.querySelectorAll('.cultivo-celda-input');
        cultivoInputs.forEach(function(el) {
            var priId = el.getAttribute('data-prianacategoria-id');
            var sec = el.getAttribute('data-seccion');
            var fila = parseInt(el.getAttribute('data-fila'), 10);
            var col = parseInt(el.getAttribute('data-columna'), 10);
            if (!priId || !sec || isNaN(fila) || isNaN(col)) return;
            var valor = (el.value || '').trim();
            if (valor === '') return;
            var registroIdCv = document.getElementById('registro_id').value;
            if (!registroIdCv) return;
            datos.push({
                id: 'cv_' + priId + '_' + sec + '_' + fila + '_' + col,
                valor: valor,
                registro_id: registroIdCv
            });
        });

        function pushCultivoAux(selector, prefix) {
            document.querySelectorAll(selector).forEach(function(el) {
                var priId = el.getAttribute('data-prianacategoria-id');
                var sec = el.getAttribute('data-seccion');
                var fila = parseInt(el.getAttribute('data-fila'), 10);
                var col = parseInt(el.getAttribute('data-columna'), 10);
                if (!priId || !sec || isNaN(fila) || isNaN(col)) return;
                var valor = (el.value || '').trim();
                if (valor === '') return;
                var registroIdAux = document.getElementById('registro_id').value;
                if (!registroIdAux) return;
                datos.push({
                    id: prefix + priId + '_' + sec + '_' + fila + '_' + col,
                    valor: valor,
                    registro_id: registroIdAux
                });
            });
        }
        pushCultivoAux('.cultivo-celda-valor-fill', 'cvn_');

        if (datos.length === 0) {
            uiAlert('No hay datos para guardar. Complete al menos un campo de resultado.', 'Aviso');
            if (submitBtn) submitBtn.disabled = false;
            return;
        }

        var registroIdF = document.getElementById('registro_id').value;
        document.querySelectorAll('.lab-registro-firmas-grupo').forEach(function(wrap) {
            var grpKey = (wrap.getAttribute('data-grp-key') || '').trim();
            if (!grpKey || !registroIdF) return;
            var vSel = wrap.querySelector('.lab-grp-val-validator');
            var aSel = wrap.querySelector('.lab-grp-val-approver');
            if (!vSel || !aSel) return;
            var vVal = (vSel.value || '').trim();
            var aVal = (aSel.value || '').trim();
            if (vVal === '' && aVal === '') return;
            datos.push({ id: 'lab_val_grp_' + grpKey, valor: vVal, registro_id: registroIdF });
            datos.push({ id: 'lab_app_grp_' + grpKey, valor: aVal, registro_id: registroIdF });
        });
        var csrf = getCsrfPair();
        if (!csrf) {
            uiAlert('Sesión de seguridad no disponible. Recargue la página (F5) e intente de nuevo.', 'Error');
            if (submitBtn) submitBtn.disabled = false;
            return;
        }
        var body = 'data=' + encodeURIComponent(JSON.stringify(datos));
        body += '&registro_id=' + encodeURIComponent(document.getElementById('registro_id').value || '');
        body += '&comentario_resultado=' + encodeURIComponent((document.getElementById('comentario_resultado') || {}).value || '');
        body += '&' + encodeURIComponent(csrf.name) + '=' + encodeURIComponent(csrf.value);
        var headers = {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf.value
        };
        fetch('<?= site_url('registers/saveregvalues') ?>', {
            method: 'POST',
            headers: headers,
            credentials: 'same-origin',
            body: body
        })
        .then(function(r) {
            if (r.status === 403) {
                return r.text().then(function(t) {
                    var msg = 'La sesión de seguridad expiró o no es válida. Recargue la página (F5) e intente de nuevo.';
                    if (t && t.indexOf('anulada') !== -1) {
                        msg = 'Esta orden fue anulada y no puede modificarse.';
                    }
                    throw new Error(msg);
                });
            }
            return r.json();
        })
        .then(function(res) {
            if (res && res.csrf_name && res.csrf_token) {
                syncCsrfToken(res.csrf_name, res.csrf_token);
            }
            if (res && res.success) {
                var rid = document.getElementById('registro_id').value;
                window.location.href = '<?= site_url('registers/view') ?>/' + rid;
            } else {
                uiAlert(res && res.message ? res.message : 'Error al guardar', 'Error');
                if (submitBtn) submitBtn.disabled = false;
            }
        })
        .catch(function(err) {
            uiAlert(err && err.message ? err.message : 'Error al guardar', 'Error');
            if (submitBtn) submitBtn.disabled = false;
        });
    }

    submitBtn.addEventListener('click', function() {
        var validacion = validarAntesDeEnviar();
        if (validacion && validacion.needsConfirm) {
            var modalEl = document.getElementById('modalConfirmEnviar');
            var textoEl = document.getElementById('modalConfirmEnviarTexto');
            var btnAceptar = document.getElementById('modalConfirmEnviarAceptar');

            textoEl.textContent = validacion.msg || '';
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
            btnAceptar.onclick = function() {
                modal.hide();
                ejecutarEnvio();
            };
            return;
        }

        ejecutarEnvio();
    });
    actualizarCalculadas();
});
</script>
<?= $this->endSection() ?>
