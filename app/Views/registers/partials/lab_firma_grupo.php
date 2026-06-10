<?php
/**
 * Validación del laboratorio por área (grupo de pruebas / padre)
 * o global por análisis (un solo panel para todas las áreas).
 *
 * @var string $padre_label
 * @var string $grp_key
 * @var array<string, string> $existentes
 * @var list<array{id:string,name:string}> $lv_list
 * @var list<array{id:string,name:string}> $la_list
 * @var list<int> $pria_ids_legacy
 * @var bool $es_global
 * @var list<string> $grp_keys_all Claves de todas las áreas (solo modo global)
 */
$padre_label = trim((string) ($padre_label ?? ''));
$grp_key = trim((string) ($grp_key ?? ''));
$es_global = ! empty($es_global);
$grp_keys_all = is_array($grp_keys_all ?? null) ? array_values(array_filter(array_map('strval', $grp_keys_all))) : [];
if ($grp_key === '' || (! $es_global && $padre_label === '')) {
    return;
}
$existentes = is_array($existentes ?? null) ? $existentes : [];
$lv_list = is_array($lv_list ?? null) ? $lv_list : [];
$la_list = is_array($la_list ?? null) ? $la_list : [];
$pria_ids_legacy = is_array($pria_ids_legacy ?? null) ? $pria_ids_legacy : [];

$curV = trim((string) ($existentes['lab_val_grp_' . $grp_key] ?? ''));
$curA = trim((string) ($existentes['lab_app_grp_' . $grp_key] ?? ''));
if ($es_global && ($curV === '' || $curA === '')) {
    foreach ($grp_keys_all as $gkAlt) {
        if ($curV === '') {
            $tV = trim((string) ($existentes['lab_val_grp_' . $gkAlt] ?? ''));
            if ($tV !== '') {
                $curV = $tV;
            }
        }
        if ($curA === '') {
            $tA = trim((string) ($existentes['lab_app_grp_' . $gkAlt] ?? ''));
            if ($tA !== '') {
                $curA = $tA;
            }
        }
    }
}
if ($curV === '' || $curA === '') {
    foreach ($pria_ids_legacy as $pidFirma) {
        $pidFirma = (int) $pidFirma;
        if ($pidFirma < 1) {
            continue;
        }
        if ($curV === '') {
            $tV = trim((string) ($existentes['lab_val_pri_' . $pidFirma] ?? ''));
            if ($tV !== '') {
                $curV = $tV;
            }
        }
        if ($curA === '') {
            $tA = trim((string) ($existentes['lab_app_pri_' . $pidFirma] ?? ''));
            if ($tA !== '') {
                $curA = $tA;
            }
        }
    }
}
?>
<div class="col-12 mb-3 lab-registro-firmas-grupo" data-grp-key="<?= esc($grp_key, 'attr') ?>"<?= $es_global ? ' data-grp-keys="' . esc(json_encode($grp_keys_all), 'attr') . '"' : '' ?>>
    <div class="register-form-validacion-panel">
        <div class="register-form-validacion-titulo">
            <i class="fa-solid fa-user-check me-2" aria-hidden="true"></i>Validación del laboratorio<?= $es_global ? '' : ' — ' . esc($padre_label) ?>
        </div>
        <div class="register-form-validacion-cuerpo">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small mb-0">Verificado por:</label>
                <select class="form-select form-select-sm lab-grp-val-validator">
                    <option value="">—</option>
                    <?php foreach ($lv_list as $lv): ?>
                        <?php $lid = (string) ($lv['id'] ?? ''); ?>
                        <option value="<?= esc($lid) ?>" <?= ($curV !== '' && $curV === $lid) ? 'selected' : '' ?>><?= esc($lv['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-0">ATENTAMENTE</label>
                <select class="form-select form-select-sm lab-grp-val-approver">
                    <option value="">—</option>
                    <?php foreach ($la_list as $la): ?>
                        <?php $aid = (string) ($la['id'] ?? ''); ?>
                        <option value="<?= esc($aid) ?>" <?= ($curA !== '' && $curA === $aid) ? 'selected' : '' ?>><?= esc($la['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        </div>
    </div>
</div>
