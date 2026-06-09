<?php
/**
 * Matriz configurable para pruebas de tipo cultivo (encabezado, cuerpo, pie).
 *
 * @var object $labotests_info
 * @var array{version?: int, bloques?: list<array<string, mixed>>} $cultivo_matriz
 * @var array<int, string> $opciones
 * @var array<int, array<string, mixed>> $leyendas_cultivo
 */
$secciones = \App\Models\LabotestModel::CULTIVO_SECCION_LABELS;
$bloquesMatriz = \App\Models\LabotestModel::resolveCultivoMatrizBloques($cultivo_matriz ?? []);
$matriz = $cultivo_matriz ?? [];
$opcionesList = $opciones ?? [];
$leyendasCultivoList = $leyendas_cultivo ?? [];
$leyendasJsMap = [];
$leyendasPorCategoriaRender = [];
foreach ($leyendasCultivoList as $lcRow) {
    $lid = (int) ($lcRow['leyenda_cultivo_id'] ?? 0);
    if ($lid < 1) {
        continue;
    }
    $catId = (int) ($lcRow['leyenda_cultivo_categoria_id'] ?? 0);
    $catNombre = trim((string) ($lcRow['categoria_nombre'] ?? ''));
    if ($catNombre === '' && $catId > 0) {
        $catNombre = 'Categoría ' . $catId;
    }
    if ($catId < 1) {
        $catId = 0;
        $catNombre = 'Sin categoría';
    }
    $leyendasJsMap[$lid] = [
        'titulo'           => (string) ($lcRow['titulo'] ?? ''),
        'mensaje'          => (string) ($lcRow['mensaje'] ?? ''),
        'categoria_id'     => $catId,
        'categoria_nombre' => $catNombre,
    ];
    if (! isset($leyendasPorCategoriaRender[$catId])) {
        $leyendasPorCategoriaRender[$catId] = [
            'categoria_id'     => $catId,
            'categoria_nombre' => $catNombre,
            'leyendas'         => [],
        ];
    }
    $leyendasPorCategoriaRender[$catId]['leyendas'][] = $lcRow;
}
$leyendasAgrupadasJs = array_values($leyendasPorCategoriaRender);
usort($leyendasAgrupadasJs, static function ($a, $b) {
    return strcmp((string) ($a['categoria_nombre'] ?? ''), (string) ($b['categoria_nombre'] ?? ''));
});
$resolveCategoriaCeldaCfg = static function (array $celdaRaw) use ($leyendasJsMap): int {
    $catId = (int) ($celdaRaw['leyenda_cultivo_categoria_id'] ?? 0);
    if ($catId < 1) {
        $leyendaId = (int) ($celdaRaw['leyenda_cultivo_id'] ?? 0);
        if ($leyendaId > 0 && isset($leyendasJsMap[$leyendaId])) {
            $catId = (int) ($leyendasJsMap[$leyendaId]['categoria_id'] ?? 0);
        }
    }

    return $catId;
};
?>
<script src="<?= base_url('js/vendor/sortable.min.js') ?>"></script>
<style>
.cultivo-columnas-grid {
    display: grid;
    grid-template-columns: repeat(var(--cultivo-cols, 1), 1fr);
    gap: 0.75rem;
    list-style: none;
    padding: 0;
    margin: 0;
    min-height: 3rem;
}
.cultivo-titulos-preview-grid {
    display: grid;
    grid-template-columns: repeat(var(--cultivo-cols, 1), 1fr);
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.cultivo-preview-titulo-cell {
    border: 1px solid #0d6efd;
    background: #e7f1ff;
    border-radius: 0.375rem;
    padding: 0.35rem 0.5rem;
    text-align: center;
    font-size: 0.8rem;
    min-height: 2.35rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    line-height: 1.2;
}
.cultivo-preview-titulo-cell.is-span-multi {
    background: #cfe2ff;
    border-width: 2px;
}
.cultivo-preview-titulo-cell.is-vacio {
    border-style: dashed;
    border-color: #dee2e6;
    background: #f8f9fa;
    color: #adb5bd;
    font-size: 0.75rem;
}
.cultivo-preview-col-hint {
    font-size: 0.65rem;
    color: #6c757d;
    margin-top: 0.15rem;
}
.cultivo-col-stack.cultivo-col-en-union {
    border-style: dashed;
    border-color: #0d6efd;
    background: #f0f6ff;
}
.cultivo-col-stack.cultivo-col-en-union .cultivo-col-header::after {
    content: 'Unida';
    margin-left: auto;
    font-size: 0.65rem;
    color: #0d6efd;
    background: #e7f1ff;
    border-radius: 0.25rem;
    padding: 0 0.3rem;
}
.cultivo-titulo-span-badge {
    font-size: 0.65rem;
    flex-shrink: 0;
}
.cultivo-bloques-toolbar {
    border: 1px dashed #ced4da;
    border-radius: 0.375rem;
    background: #f8f9fa;
}
.cultivo-orden-panel {
    border: 1px solid #0d6efd;
    border-radius: 0.5rem;
    background: #f0f6ff;
    padding: 0.75rem 1rem;
    margin-bottom: 1.25rem;
}
.cultivo-orden-panel-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #0d6efd;
    margin-bottom: 0.5rem;
}
.cultivo-orden-list {
    list-style: none;
    padding: 0;
    margin: 0 0 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.cultivo-orden-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.6rem;
    background: #fff;
    border: 1px solid #b6d4fe;
    border-radius: 0.375rem;
    font-size: 0.85rem;
}
.cultivo-orden-item.sortable-ghost { opacity: 0.45; }
.cultivo-orden-item.sortable-chosen {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15);
}
.cultivo-orden-drag {
    cursor: grab;
    color: #0d6efd;
    user-select: none;
    flex-shrink: 0;
}
.cultivo-orden-drag:active { cursor: grabbing; }
.cultivo-orden-pos {
    flex-shrink: 0;
    width: 1.4rem;
    height: 1.4rem;
    border-radius: 50%;
    background: #e7f1ff;
    color: #0d6efd;
    font-size: 0.7rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.cultivo-orden-label {
    font-weight: 600;
    min-width: 0;
}
.cultivo-bloques-config-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.cultivo-bloque-item {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 0;
    margin-bottom: 1.25rem;
    background: #fff;
}
.cultivo-bloque-body { padding: 1rem; }
.cultivo-col-stack {
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.45rem;
    background: #f8f9fa;
}
.cultivo-col-stack.sortable-ghost { opacity: 0.45; }
.cultivo-col-header {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 0.35rem;
    font-size: 0.75rem;
    color: #6c757d;
}
.cultivo-col-drag-handle,
.cultivo-titulo-drag-handle {
    cursor: grab;
    color: #6c757d;
    padding: 0.1rem 0.2rem;
    user-select: none;
    flex-shrink: 0;
}
.cultivo-col-drag-handle:active,
.cultivo-titulo-drag-handle:active { cursor: grabbing; }
.cultivo-titulos-col-list {
    list-style: none;
    padding: 0;
    margin: 0 0 0.35rem;
    min-height: 2rem;
}
.cultivo-titulo-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 0.35rem;
    flex-wrap: wrap;
}
.cultivo-titulo-colspan {
    width: 3.25rem;
    flex-shrink: 0;
    padding-left: 0.25rem;
    padding-right: 0.25rem;
}
.cultivo-titulo-item.sortable-ghost { opacity: 0.45; }
.cultivo-titulo-palette ul { min-height: 0; }
.cultivo-titulo-plantilla {
    cursor: grab;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border: 1px dashed #0d6efd;
    border-radius: 0.375rem;
    padding: 0.25rem 0.5rem;
    background: #f0f6ff;
    color: #0d6efd;
    font-size: 0.8rem;
}
.cultivo-titulo-plantilla:active { cursor: grabbing; }
.cultivo-trash-zone {
    border: 2px dashed #dc3545;
    border-radius: 0.375rem;
    color: #dc3545;
    background: #fff;
    min-height: 5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: background 0.15s ease;
}
.cultivo-celda-leyenda-preview {
    font-size: 0.75rem;
    max-height: 4.5rem;
    overflow: auto;
    border: 1px dashed #dee2e6;
    border-radius: 0.25rem;
    padding: 0.35rem;
    background: #fff;
}
.cultivo-celda-texto-wrap .form-control[readonly] { background: #fff; cursor: default; }
.cultivo-trash-zone.sortable-ghost,
.cultivo-trash-zone.sortable-chosen {
    background: #fff5f5;
}
.cultivo-celda-opcion-wrap.cultivo-con-valor {
    display: flex;
    gap: 0.35rem;
    align-items: flex-start;
}
.cultivo-celda-opcion-wrap.cultivo-con-valor .cultivo-opcion-input {
    flex: 1 1 auto;
    min-width: 0;
}
.cultivo-celda-valor-input {
    flex: 0 0 6.5rem;
    max-width: 8rem;
}
.cultivo-celda-valor-wrap .cultivo-celda-valor-input {
    flex: 1 1 auto;
    max-width: none;
}
.cultivo-cuerpo-unidad-wrap {
    min-width: 9rem;
}
.cultivo-bulk-celda-panel {
    border: 1px dashed #ced4da;
    border-radius: 0.375rem;
    background: #f8f9fa;
}
.cultivo-bulk-celda-panel .cultivo-bulk-celda-extra {
    min-width: 12rem;
}
</style>
<div class="card card-tabla-sub-items mt-3">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <strong>Valores de sub-clases (prueba cultivo)</strong>
        <span class="badge bg-info text-dark">Matriz</span>
        <span class="text-muted small">Apile títulos por columna; use el panel «Orden de secciones» arriba para reordenar, duplicar o agregar bloques.</span>
        <a href="<?= site_url('labotests/opciones') ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
            <i class="fa-solid fa-list-check me-1"></i>Tipos de resultado
        </a>
        <a href="<?= site_url('config?tab=leyendas_cultivo') ?>" class="btn btn-sm btn-outline-secondary ms-auto" target="_blank" rel="noopener">
            <i class="fa-solid fa-quote-right me-1"></i>Leyendas cultivo
        </a>
    </div>
    <div class="card-body">
        <?= form_open('labotests/savecultivomatriz', ['id' => 'form_cultivo_matriz', 'novalidate' => 'novalidate']) ?>
        <input type="hidden" name="prianacategoria_id" value="<?= (int) ($labotests_info->prianacategoria_id ?? 0) ?>">
        <input type="hidden" name="cultivo_matriz_json" id="cultivo_matriz_json" value="">

        <input type="hidden" name="cultivo_matriz_json" id="cultivo_matriz_json" value="">

        <div class="cultivo-orden-panel">
            <div class="cultivo-orden-panel-title">
                <i class="fa-solid fa-arrow-down-wide-short me-1"></i>Orden de secciones
            </div>
            <p class="small text-muted mb-2 mb-md-2">Arrastre aquí para cambiar el orden (encabezado, cuerpo, pie). La configuración de cada bloque está abajo.</p>
            <ul class="cultivo-orden-list" id="cultivo_orden_list">
            <?php $ordenIdx = 0; foreach ($bloquesMatriz as $bloqueOrden):
                $bloqueIdOrden = (string) ($bloqueOrden['id'] ?? '');
                $bloqueTipoOrden = (string) ($bloqueOrden['tipo'] ?? 'encabezado');
                if ($bloqueIdOrden === '') continue;
                $ordenIdx++;
                $labelOrden = \App\Models\LabotestModel::cultivoBloqueDisplayLabel($bloqueOrden, $bloquesMatriz);
            ?>
                <li class="cultivo-orden-item" data-bloque-id="<?= esc($bloqueIdOrden, 'attr') ?>" data-tipo="<?= esc($bloqueTipoOrden, 'attr') ?>">
                    <span class="cultivo-orden-drag" title="Arrastrar para reordenar"><i class="fa-solid fa-grip-vertical"></i></span>
                    <span class="cultivo-orden-pos cultivo-orden-pos-num"><?= (int) $ordenIdx ?></span>
                    <span class="cultivo-orden-label"><?= esc($labelOrden) ?></span>
                    <span class="badge bg-secondary"><?= esc($secciones[$bloqueTipoOrden] ?? $bloqueTipoOrden) ?></span>
                    <div class="ms-auto d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-cultivo-dup-bloque py-0 px-1" data-bloque-id="<?= esc($bloqueIdOrden, 'attr') ?>" title="Duplicar">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-cultivo-del-bloque py-0 px-1" data-bloque-id="<?= esc($bloqueIdOrden, 'attr') ?>" title="Eliminar">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
            <div class="cultivo-bloques-toolbar p-2">
                <span class="small text-muted me-2">Agregar bloque:</span>
                <?php foreach ($secciones as $tipoAdd => $labelAdd): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-cultivo-add-bloque" data-tipo="<?= esc($tipoAdd, 'attr') ?>">
                    <i class="fa-solid fa-plus me-1"></i><?= esc($labelAdd) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <ul class="cultivo-bloques-config-list" id="cultivo_bloques_list">
        <?php foreach ($bloquesMatriz as $bloque):
            $bloqueId = (string) ($bloque['id'] ?? '');
            $bloqueTipo = (string) ($bloque['tipo'] ?? 'encabezado');
            if ($bloqueId === '') {
                continue;
            }
            $secLabel = \App\Models\LabotestModel::cultivoBloqueDisplayLabel($bloque, $bloquesMatriz);
            $sec = $bloque;
            $secId = $bloqueId;
            $filas = max(0, (int) ($sec['filas'] ?? 1));
            $columnas = max(1, (int) ($sec['columnas'] ?? 1));
            $titulosRaw = $sec['titulos'] ?? [];
            $celdas = $sec['celdas'] ?? [];
            $titulosPorCol = \App\Models\LabotestModel::parseCultivoTitulosPorColumna(
                is_array($titulosRaw) ? $titulosRaw : [],
                $columnas
            );
            $valoresHabilitado = ($bloqueTipo === 'cuerpo') && ! empty($sec['valores_habilitado']);
            $unidadesHabilitado = ($bloqueTipo === 'cuerpo') && ! empty($sec['unidades_habilitado']);
            $unidadGlobal = ($bloqueTipo === 'cuerpo') ? trim((string) ($sec['unidad'] ?? '')) : '';
            $alineacionFilas = ($bloqueTipo === 'cuerpo') ? trim((string) ($sec['alineacion_filas'] ?? 'centro')) : 'centro';
            if ($alineacionFilas === 'cuerpo') {
                $alineacionFilas = 'centro';
            }
            if (! in_array($alineacionFilas, ['centro', 'bordes'], true)) {
                $alineacionFilas = 'centro';
            }
            $bloqueIdSafe = preg_replace('/[^a-z0-9_]/', '_', $bloqueId);
        ?>
        <li class="cultivo-bloque-item" data-bloque-id="<?= esc($bloqueId, 'attr') ?>" data-tipo="<?= esc($bloqueTipo, 'attr') ?>">
            <div class="cultivo-bloque-body">
        <div class="border rounded p-3 mb-0 cultivo-matriz-seccion" data-bloque-id="<?= esc($bloqueId, 'attr') ?>" data-tipo="<?= esc($bloqueTipo, 'attr') ?>"
             data-filas="<?= $filas ?>" data-columnas="<?= $columnas ?>"
             <?= $bloqueTipo === 'cuerpo' ? 'data-valores-habilitado="' . ($valoresHabilitado ? '1' : '0') . '" data-unidades-habilitado="' . ($unidadesHabilitado ? '1' : '0') . '" data-unidad-global="' . esc($unidadGlobal, 'attr') . '" data-alineacion-filas="' . esc($alineacionFilas, 'attr') . '" data-celdas-json="' . esc(json_encode($celdas, JSON_UNESCAPED_UNICODE), 'attr') . '"' : '' ?>>
            <div class="d-flex flex-wrap align-items-end gap-3 mb-3">
                <div>
                    <h6 class="mb-1"><?= esc($secLabel) ?></h6>
                    <small class="text-muted">Varios títulos por columna; cada título puede ocupar más de una columna.</small>
                </div>
                <?php if ($bloqueTipo === 'cuerpo'): ?>
                <div class="form-check mb-0">
                    <input type="checkbox" class="form-check-input cultivo-valores-habilitado-check"
                           id="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_valores_habilitado" data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                           <?= $valoresHabilitado ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_valores_habilitado">Valores</label>
                    <span class="small text-muted">(campo Valor en cada celda del cuerpo)</span>
                </div>
                <div class="form-check mb-0">
                    <input type="checkbox" class="form-check-input cultivo-unidades-habilitado-check"
                           id="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_unidades_habilitado" data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                           <?= $unidadesHabilitado ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_unidades_habilitado">Unidades</label>
                    <span class="small text-muted">(una unidad de medida para todo el cuerpo)</span>
                </div>
                <div class="cultivo-cuerpo-unidad-wrap<?= $unidadesHabilitado ? '' : ' d-none' ?>">
                    <label class="form-label small mb-1" for="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_unidad">Unidad de medida</label>
                    <input type="text"
                           class="form-control form-control-sm cultivo-cuerpo-unidad-input"
                           id="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_unidad"
                           data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                           value="<?= esc($unidadGlobal) ?>"
                           placeholder="ej. UFC/mL, mg/dl"
                           style="min-width: 9rem;">
                </div>
                <div>
                    <label class="form-label small mb-1" for="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_alineacion_filas">Orden en filas</label>
                    <select class="form-select form-select-sm cultivo-alineacion-filas-select"
                            id="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_alineacion_filas"
                            data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                            style="min-width: 9rem;">
                        <option value="centro" <?= $alineacionFilas === 'centro' ? 'selected' : '' ?>>Centro</option>
                        <option value="bordes" <?= $alineacionFilas === 'bordes' ? 'selected' : '' ?>>Bordes</option>
                    </select>
                    <span class="small text-muted d-block">Bordes: resultado izq., valor+unidad der.</span>
                </div>
                <?php endif; ?>
                <div class="ms-auto d-flex flex-wrap align-items-end gap-2">
                    <div>
                        <label class="form-label small mb-1" for="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_filas">Filas de datos</label>
                        <input type="number" class="form-control form-control-sm cultivo-dim-input" id="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_filas"
                               data-bloque-id="<?= esc($bloqueId, 'attr') ?>" data-dim="filas"
                               value="<?= $filas ?>" min="0" max="50" step="1" style="width: 5rem;">
                    </div>
                    <div>
                        <label class="form-label small mb-1" for="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_columnas">Columnas</label>
                        <input type="number" class="form-control form-control-sm cultivo-dim-input" id="cultivo_<?= esc($bloqueIdSafe, 'attr') ?>_columnas"
                               data-bloque-id="<?= esc($bloqueId, 'attr') ?>" data-dim="columnas"
                               value="<?= $columnas ?>" min="1" max="20" step="1" style="width: 5rem;">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-cultivo-add-col" data-bloque-id="<?= esc($bloqueId, 'attr') ?>">
                        <i class="fa-solid fa-plus me-1"></i>Agregar columna
                    </button>
                </div>
            </div>

            <div class="cultivo-titulo-palette mb-2" data-bloque-id="<?= esc($bloqueId, 'attr') ?>">
                <span class="small text-muted me-2">Arrastre a una columna para agregar título debajo:</span>
                <ul class="list-unstyled d-inline-block mb-0">
                    <li class="cultivo-titulo-plantilla" title="Arrastrar a una columna">
                        <i class="fa-solid fa-grip-vertical"></i> Nuevo título
                    </li>
                </ul>
            </div>

            <div class="row g-2 mb-2 align-items-stretch">
                <div class="col">
                    <div class="small text-muted mb-1">Vista previa (columnas unidas según colspan):</div>
                    <div class="cultivo-titulos-preview-grid" data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                         style="--cultivo-cols: <?= (int) $columnas ?>;"></div>
                    <ul class="cultivo-columnas-grid" data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                        style="--cultivo-cols: <?= (int) $columnas ?>;">
                        <?php for ($c = 0; $c < $columnas; $c++): ?>
                        <li class="cultivo-col-stack">
                            <div class="cultivo-col-header">
                                <span class="cultivo-col-drag-handle" title="Arrastrar columna"><i class="fa-solid fa-grip-vertical"></i></span>
                                <span>Col. <?= $c + 1 ?></span>
                            </div>
                            <ul class="cultivo-titulos-col-list" data-bloque-id="<?= esc($bloqueId, 'attr') ?>" data-columna="<?= $c ?>">
                                <?php foreach ($titulosPorCol[$c] as $ti => $tituloCell):
                                    $tituloTexto = (string) ($tituloCell['texto'] ?? '');
                                    $tituloColspan = max(1, min($columnas - $c, (int) ($tituloCell['colspan'] ?? 1)));
                                ?>
                                <li class="cultivo-titulo-item">
                                    <span class="cultivo-titulo-drag-handle" title="Arrastrar título"><i class="fa-solid fa-grip-vertical"></i></span>
                                    <input type="text"
                                           class="form-control form-control-sm cultivo-titulo-input flex-grow-1"
                                           data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                                           data-columna="<?= $c ?>"
                                           value="<?= esc($tituloTexto) ?>"
                                           placeholder="Título <?= $ti + 1 ?>">
                                    <select class="form-select form-select-sm cultivo-titulo-colspan"
                                            data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                                            data-columna="<?= $c ?>"
                                            title="Columnas que ocupa">
                                        <?php for ($sp = 1; $sp <= ($columnas - $c); $sp++): ?>
                                        <option value="<?= $sp ?>" <?= $tituloColspan === $sp ? 'selected' : '' ?>><?= $sp ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <span class="badge bg-primary cultivo-titulo-span-badge<?= $tituloColspan > 1 ? '' : ' d-none' ?>"
                                          title="Columnas que ocupa">×<?= (int) $tituloColspan ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn btn-sm btn-link btn-cultivo-add-titulo-in-col p-0 small text-start"
                                    data-bloque-id="<?= esc($bloqueId, 'attr') ?>" data-columna="<?= $c ?>">
                                <i class="fa-solid fa-plus me-1"></i>Título
                            </button>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </div>
                <div class="col-auto" style="min-width: 11rem;">
                    <div class="cultivo-trash-zone px-2 text-center" data-bloque-id="<?= esc($bloqueId, 'attr') ?>" title="Arrastre un título o columna aquí para eliminar">
                        <i class="fa-solid fa-trash-can me-1"></i>Eliminar
                    </div>
                </div>
            </div>

            <?php if ($bloqueTipo === 'cuerpo'): ?>
            <div class="cultivo-bulk-celda-panel p-2 mb-2">
                <div class="d-flex flex-wrap align-items-end gap-2">
                    <div>
                        <label class="form-label small mb-1">Aplicar a todas las columnas</label>
                        <select class="form-select form-select-sm cultivo-bulk-celda-modo" data-bloque-id="<?= esc($bloqueId, 'attr') ?>">
                            <option value="texto">Texto libre</option>
                            <option value="opcion">Tipo de resultado</option>
                            <option value="leyenda">Leyenda de cultivo</option>
                        </select>
                    </div>
                    <div class="cultivo-bulk-celda-extra cultivo-bulk-opcion-wrap d-none">
                        <label class="form-label small mb-1">Tipo de resultado</label>
                        <select class="form-select form-select-sm cultivo-bulk-celda-opcion" data-bloque-id="<?= esc($bloqueId, 'attr') ?>">
                            <option value="0">— Seleccione tipo —</option>
                            <?php foreach ($opcionesList as $oid => $oname): ?>
                            <option value="<?= (int) $oid ?>"><?= esc($oname) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="cultivo-bulk-celda-extra cultivo-bulk-leyenda-wrap d-none">
                        <label class="form-label small mb-1">Categoría de leyenda</label>
                        <select class="form-select form-select-sm cultivo-bulk-celda-leyenda" data-bloque-id="<?= esc($bloqueId, 'attr') ?>">
                            <option value="0">— Seleccione categoría —</option>
                            <?php foreach ($leyendasAgrupadasJs as $grupoLc):
                                $grpCatId = (int) ($grupoLc['categoria_id'] ?? 0);
                                if ($grpCatId < 1 || empty($grupoLc['leyendas'])) continue;
                            ?>
                            <option value="<?= $grpCatId ?>"><?= esc((string) ($grupoLc['categoria_nombre'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-cultivo-bulk-aplicar" data-bloque-id="<?= esc($bloqueId, 'attr') ?>">
                        <i class="fa-solid fa-arrows-left-right-to-line me-1"></i>Aplicar a todas las columnas
                    </button>
                </div>
                <p class="small text-muted mb-0 mt-1">Define el tipo de celda del cuerpo y lo copia a todas las filas y columnas de datos.</p>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 cultivo-matriz-grid" data-bloque-id="<?= esc($bloqueId, 'attr') ?>">
                    <thead class="table-light">
                        <tr class="cultivo-fila-ref">
                            <?php for ($c = 0; $c < $columnas; $c++): ?>
                            <th class="p-1 small text-muted text-center"><?= $c + 1 ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($filas < 1): ?>
                        <tr class="cultivo-sin-filas">
                            <td colspan="<?= $columnas ?>" class="text-muted small text-center py-2">Sin filas de datos (solo títulos de columna)</td>
                        </tr>
                        <?php else: ?>
                        <?php for ($r = 0; $r < $filas; $r++): ?>
                        <tr>
                            <?php for ($c = 0; $c < $columnas; $c++):
                                $celdaRaw = $celdas[$r][$c] ?? ['modo' => 'texto'];
                                $celdaModo = 'texto';
                                $celdaOpcion = 0;
                                $celdaCategoria = 0;
                                $celdaValor = '';
                                if (is_array($celdaRaw)) {
                                    $celdaValor = trim((string) ($celdaRaw['valor'] ?? ''));
                                    $modoRaw = (string) ($celdaRaw['modo'] ?? 'texto');
                                    if ($modoRaw === 'opcion') {
                                        $celdaModo = 'opcion';
                                        $celdaOpcion = (int) ($celdaRaw['opcion_id'] ?? 0);
                                    } elseif ($modoRaw === 'leyenda') {
                                        $celdaModo = 'leyenda';
                                        $celdaCategoria = $resolveCategoriaCeldaCfg($celdaRaw);
                                    }
                                } elseif (is_numeric($celdaRaw)) {
                                    $celdaOpcion = (int) $celdaRaw;
                                    $celdaModo = $celdaOpcion > 0 ? 'opcion' : 'texto';
                                }
                            ?>
                            <td class="p-1">
                                <div class="cultivo-celda-config">
                                    <select class="form-select form-select-sm cultivo-celda-modo mb-1"
                                            data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                                            data-fila="<?= $r ?>"
                                            data-columna="<?= $c ?>">
                                        <option value="texto" <?= $celdaModo === 'texto' ? 'selected' : '' ?>>Texto libre</option>
                                        <option value="opcion" <?= $celdaModo === 'opcion' ? 'selected' : '' ?>>Tipo de resultado</option>
                                        <option value="leyenda" <?= $celdaModo === 'leyenda' ? 'selected' : '' ?>>Leyenda de cultivo</option>
                                    </select>
                                    <div class="cultivo-celda-texto-wrap<?= $celdaModo !== 'texto' ? ' d-none' : '' ?>">
                                        <input type="text" class="form-control form-control-sm" value="" readonly
                                               placeholder="Campo de texto al capturar" tabindex="-1">
                                    </div>
                                    <div class="cultivo-celda-opcion-wrap<?= $celdaModo !== 'opcion' ? ' d-none' : '' ?><?= ($bloqueTipo === 'cuerpo' && $valoresHabilitado) ? ' cultivo-con-valor' : '' ?>">
                                        <select class="form-select form-select-sm cultivo-opcion-input"
                                                data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                                                data-fila="<?= $r ?>"
                                                data-columna="<?= $c ?>">
                                            <option value="0" <?= $celdaOpcion === 0 ? 'selected' : '' ?>>— Seleccione tipo —</option>
                                            <?php foreach ($opcionesList as $oid => $oname): ?>
                                            <option value="<?= (int) $oid ?>" <?= $celdaOpcion === (int) $oid ? 'selected' : '' ?>><?= esc($oname) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($bloqueTipo === 'cuerpo' && $valoresHabilitado): ?>
                                        <input type="text"
                                               class="form-control form-control-sm cultivo-celda-valor-input"
                                               data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                                               data-fila="<?= $r ?>"
                                               data-columna="<?= $c ?>"
                                               value="<?= esc($celdaValor) ?>"
                                               placeholder="Valor">
                                        <?php endif; ?>
                                    </div>
                                    <div class="cultivo-celda-leyenda-wrap<?= $celdaModo !== 'leyenda' ? ' d-none' : '' ?>">
                                        <select class="form-select form-select-sm cultivo-leyenda-categoria-input"
                                                data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                                                data-fila="<?= $r ?>"
                                                data-columna="<?= $c ?>">
                                            <option value="0" <?= $celdaCategoria === 0 ? 'selected' : '' ?>>— Seleccione categoría —</option>
                                            <?php foreach ($leyendasAgrupadasJs as $grupoLc):
                                                $grpCatId = (int) ($grupoLc['categoria_id'] ?? 0);
                                                if ($grpCatId < 1 || empty($grupoLc['leyendas'])) continue;
                                            ?>
                                            <option value="<?= $grpCatId ?>" <?= $celdaCategoria === $grpCatId ? 'selected' : '' ?>>
                                                <?= esc((string) ($grupoLc['categoria_nombre'] ?? '')) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="small text-muted mb-0 mt-1">Al llenar la prueba se elige el título/mensaje.</p>
                                    </div>
                                    <?php if ($bloqueTipo === 'cuerpo' && $valoresHabilitado && $celdaModo !== 'opcion'): ?>
                                    <div class="cultivo-celda-valor-wrap mt-1">
                                        <input type="text"
                                               class="form-control form-control-sm cultivo-celda-valor-input"
                                               data-bloque-id="<?= esc($bloqueId, 'attr') ?>"
                                               data-fila="<?= $r ?>"
                                               data-columna="<?= $c ?>"
                                               value="<?= esc($celdaValor) ?>"
                                               placeholder="Valor">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
            </div>
        </li>
        <?php endforeach; ?>
        </ul>

        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i>Guardar matriz
        </button>
        <?= form_close() ?>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('form_cultivo_matriz');
    if (!form) return;

    var opcionesMap = <?= json_encode($opcionesList, JSON_UNESCAPED_UNICODE) ?>;
    var leyendasMap = <?= json_encode($leyendasJsMap, JSON_UNESCAPED_UNICODE) ?>;
    var leyendasAgrupadas = <?= json_encode($leyendasAgrupadasJs, JSON_UNESCAPED_UNICODE) ?>;
    var seccionLabels = <?= json_encode($secciones, JSON_UNESCAPED_UNICODE) ?>;
    var sortableInstances = {};
    var bloquesSortable = null;
    var bloqueTemplates = {};
    var ordenTemplates = {};

    function cacheBloqueTemplates() {
        form.querySelectorAll('#cultivo_bloques_list .cultivo-bloque-item').forEach(function(item) {
            var t = item.getAttribute('data-tipo');
            if (t && !bloqueTemplates[t]) {
                bloqueTemplates[t] = item.cloneNode(true);
            }
        });
        form.querySelectorAll('#cultivo_orden_list .cultivo-orden-item').forEach(function(item) {
            var t = item.getAttribute('data-tipo');
            if (t && !ordenTemplates[t]) {
                ordenTemplates[t] = item.cloneNode(true);
            }
        });
    }

    function getAllBloqueIds() {
        var ids = [];
        var list = document.getElementById('cultivo_orden_list');
        if (!list) return ids;
        list.querySelectorAll('.cultivo-orden-item[data-bloque-id]').forEach(function(item) {
            var id = item.getAttribute('data-bloque-id');
            if (id) ids.push(id);
        });
        return ids;
    }

    function getConfigItem(bloqueId) {
        var list = document.getElementById('cultivo_bloques_list');
        return list ? list.querySelector('.cultivo-bloque-item[data-bloque-id="' + bloqueId + '"]') : null;
    }

    function getOrdenItem(bloqueId) {
        var list = document.getElementById('cultivo_orden_list');
        return list ? list.querySelector('.cultivo-orden-item[data-bloque-id="' + bloqueId + '"]') : null;
    }

    function syncConfigOrderFromOrden() {
        var configList = document.getElementById('cultivo_bloques_list');
        if (!configList) return;
        getAllBloqueIds().forEach(function(id) {
            var item = getConfigItem(id);
            if (item) configList.appendChild(item);
        });
        refreshOrdenPosNumbers();
    }

    function refreshOrdenPosNumbers() {
        var list = document.getElementById('cultivo_orden_list');
        if (!list) return;
        list.querySelectorAll('.cultivo-orden-item').forEach(function(item, idx) {
            var pos = item.querySelector('.cultivo-orden-pos-num');
            if (pos) pos.textContent = String(idx + 1);
        });
    }

    function getBloqueTipo(bloqueId) {
        var wrap = getWrap(bloqueId);
        return wrap ? (wrap.getAttribute('data-tipo') || 'encabezado') : 'encabezado';
    }

    function isCuerpoBloque(bloqueId) {
        return getBloqueTipo(bloqueId) === 'cuerpo';
    }

    function generateBloqueId(tipo, existingIds) {
        if (existingIds.indexOf(tipo) < 0) return tipo;
        var n = 2;
        while (existingIds.indexOf(tipo + '_' + n) >= 0) n++;
        return tipo + '_' + n;
    }

    function bloqueDisplayLabel(bloque, allBloques) {
        var tipo = bloque.tipo || 'encabezado';
        var base = seccionLabels[tipo] || tipo;
        var id = bloque.id || tipo;
        if (id === tipo) {
            var same = 0;
            (allBloques || []).forEach(function(b) {
                if ((b.tipo || '') === tipo) same++;
            });
            if (same <= 1) return base;
        }
        var m = String(id).match(new RegExp('^' + tipo + '_(\\d+)$'));
        if (m) return base + ' ' + m[1];
        return base + ' (' + id + ')';
    }

    function defaultBloqueData(tipo, id) {
        var b = {
            id: id,
            tipo: tipo,
            filas: 1,
            columnas: 1,
            titulos: [[]],
            celdas: [[{ modo: 'texto' }]]
        };
        if (tipo === 'cuerpo') {
            b.valores_habilitado = false;
            b.unidades_habilitado = false;
            b.unidad = '';
            b.alineacion_filas = 'centro';
        }
        return b;
    }

    function deepCloneBloque(src) {
        return JSON.parse(JSON.stringify(src));
    }

    function capturarTodosBloques() {
        var bloques = [];
        getAllBloqueIds().forEach(function(bloqueId) {
            var data = capturarSeccion(bloqueId);
            if (data) bloques.push(data);
        });
        return bloques;
    }
    var MAX_TITULOS_POR_COL = 20;

    var debounceTimers = {};

    function clampFilas(n) {
        n = parseInt(n, 10);
        if (isNaN(n)) return 0;
        return Math.max(0, Math.min(50, n));
    }

    function clampColumnas(n) {
        n = parseInt(n, 10);
        if (isNaN(n)) return 1;
        return Math.max(1, Math.min(20, n));
    }

    function escAttr(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function celdaDefault() {
        return { modo: 'texto' };
    }

    function normalizeCelda(raw) {
        function withExtras(out) {
            if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
                if (raw.valor !== undefined) {
                    var v = String(raw.valor || '');
                    if (v !== '') out.valor = v;
                }
            }
            return out;
        }
        if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
            if (raw.modo === 'opcion') {
                var id = parseInt(raw.opcion_id, 10);
                return withExtras({ modo: 'opcion', opcion_id: isNaN(id) ? 0 : Math.max(0, id) });
            }
            if (raw.modo === 'leyenda') {
                var catId = parseInt(raw.leyenda_cultivo_categoria_id, 10);
                var celda = {
                    modo: 'leyenda',
                    leyenda_cultivo_categoria_id: isNaN(catId) ? 0 : Math.max(0, catId)
                };
                if (celda.leyenda_cultivo_categoria_id < 1) {
                    var lid = parseInt(raw.leyenda_cultivo_id, 10);
                    if (!isNaN(lid) && lid > 0 && leyendasMap[lid]) {
                        celda.leyenda_cultivo_categoria_id = parseInt(leyendasMap[lid].categoria_id, 10) || 0;
                    }
                }
                return withExtras(celda);
            }
            return withExtras({ modo: 'texto' });
        }
        if (typeof raw === 'number' || (typeof raw === 'string' && raw !== '' && !isNaN(parseInt(raw, 10)))) {
            var n = parseInt(raw, 10);
            if (n > 0) return { modo: 'opcion', opcion_id: n };
        }
        return { modo: 'texto' };
    }

    function buildCategoriaLeyendaOptions(selected) {
        var sel = selected !== undefined && selected !== null ? parseInt(selected, 10) : 0;
        if (isNaN(sel)) sel = 0;
        var html = '<option value="0"' + (sel === 0 ? ' selected' : '') + '>— Seleccione categoría —</option>';
        (leyendasAgrupadas || []).forEach(function(grupo) {
            var cid = parseInt(grupo.categoria_id, 10);
            var leyendas = grupo.leyendas || [];
            if (isNaN(cid) || cid < 1 || !leyendas.length) return;
            var catLabel = String(grupo.categoria_nombre || ('Categoría ' + cid)).replace(/</g, '&lt;');
            html += '<option value="' + cid + '"' + (sel === cid ? ' selected' : '') + '>' + catLabel + '</option>';
        });
        return html;
    }

    function buildLeyendaCeldaInner(secId, fila, col, celda) {
        var catId = celda.leyenda_cultivo_categoria_id || 0;
        if (catId < 1) {
            var legacyId = parseInt(celda.leyenda_cultivo_id, 10);
            if (!isNaN(legacyId) && legacyId > 0 && leyendasMap[legacyId]) {
                catId = parseInt(leyendasMap[legacyId].categoria_id, 10) || 0;
            }
        }
        var html = '<select class="form-select form-select-sm cultivo-leyenda-categoria-input"'
            + ' data-bloque-id="' + secId + '" data-fila="' + fila + '" data-columna="' + col + '">';
        html += buildCategoriaLeyendaOptions(catId);
        html += '</select>';
        html += '<p class="small text-muted mb-0 mt-1">Al llenar la prueba se elige el título/mensaje.</p>';
        return html;
    }

    function toggleCeldaModoPanels(config, modo) {
        if (!config) return;
        var textoWrap = config.querySelector('.cultivo-celda-texto-wrap');
        var opcionWrap = config.querySelector('.cultivo-celda-opcion-wrap');
        var leyendaWrap = config.querySelector('.cultivo-celda-leyenda-wrap');
        var valorWrap = config.querySelector('.cultivo-celda-valor-wrap');
        if (textoWrap) textoWrap.classList.toggle('d-none', modo !== 'texto');
        if (opcionWrap) opcionWrap.classList.toggle('d-none', modo !== 'opcion');
        if (leyendaWrap) leyendaWrap.classList.toggle('d-none', modo !== 'leyenda');
        if (valorWrap) valorWrap.classList.toggle('d-none', modo === 'opcion');
    }

    function toggleBulkCeldaPanels(wrap, modo) {
        if (!wrap) return;
        var opcionWrap = wrap.querySelector('.cultivo-bulk-opcion-wrap');
        var leyendaWrap = wrap.querySelector('.cultivo-bulk-leyenda-wrap');
        if (opcionWrap) opcionWrap.classList.toggle('d-none', modo !== 'opcion');
        if (leyendaWrap) leyendaWrap.classList.toggle('d-none', modo !== 'leyenda');
    }

    function buildCeldaFromBulk(modo, opcionId, categoriaId) {
        if (modo === 'opcion') {
            var oid = parseInt(opcionId, 10);
            return { modo: 'opcion', opcion_id: isNaN(oid) ? 0 : Math.max(0, oid) };
        }
        if (modo === 'leyenda') {
            var cid = parseInt(categoriaId, 10);
            return {
                modo: 'leyenda',
                leyenda_cultivo_categoria_id: isNaN(cid) ? 0 : Math.max(0, cid)
            };
        }
        return { modo: 'texto' };
    }

    function aplicarBulkCeldaATodasColumnas(secId) {
        if (!isCuerpoBloque(secId)) return;
        var wrap = getWrap(secId);
        if (!wrap) return;
        var modoSel = wrap.querySelector('.cultivo-bulk-celda-modo');
        var modo = modoSel ? modoSel.value : 'texto';
        var opcionSel = wrap.querySelector('.cultivo-bulk-celda-opcion');
        var leyendaSel = wrap.querySelector('.cultivo-bulk-celda-leyenda');
        var opcionId = opcionSel ? opcionSel.value : '0';
        var categoriaId = leyendaSel ? leyendaSel.value : '0';

        if (modo === 'opcion' && (!opcionId || parseInt(opcionId, 10) < 1)) {
            if (typeof showToast === 'function') {
                showToast('Seleccione un tipo de resultado', 'error');
            }
            return;
        }
        if (modo === 'leyenda' && (!categoriaId || parseInt(categoriaId, 10) < 1)) {
            if (typeof showToast === 'function') {
                showToast('Seleccione una categoría de leyenda', 'error');
            }
            return;
        }

        var data = capturarSeccion(secId);
        if (!data || data.filas < 1) {
            if (typeof showToast === 'function') {
                showToast('Agregue al menos una fila de datos', 'error');
            }
            return;
        }

        var plantilla = buildCeldaFromBulk(modo, opcionId, categoriaId);
        for (var r = 0; r < data.filas; r++) {
            if (!Array.isArray(data.celdas[r])) data.celdas[r] = [];
            for (var c = 0; c < data.columnas; c++) {
                data.celdas[r][c] = normalizeCelda(plantilla);
            }
        }
        renderSeccion(secId, data);
        if (typeof showToast === 'function') {
            showToast('Tipo de celda aplicado a todas las columnas', 'success');
        }
    }

    function buildCeldaValorInputHtml(secId, fila, col, valor) {
        return '<input type="text" class="form-control form-control-sm cultivo-celda-valor-input"'
            + ' data-bloque-id="' + secId + '" data-fila="' + fila + '" data-columna="' + col + '"'
            + ' value="' + escAttr(String(valor || '')) + '" placeholder="Valor">';
    }

    function buildCeldaCell(secId, fila, col, celdaRaw, mostrarValor) {
        var celda = normalizeCelda(celdaRaw);
        var modo = celda.modo || 'texto';
        var html = '<div class="cultivo-celda-config">';
        html += '<select class="form-select form-select-sm cultivo-celda-modo mb-1"'
            + ' data-bloque-id="' + secId + '" data-fila="' + fila + '" data-columna="' + col + '">';
        html += '<option value="texto"' + (modo === 'texto' ? ' selected' : '') + '>Texto libre</option>';
        html += '<option value="opcion"' + (modo === 'opcion' ? ' selected' : '') + '>Tipo de resultado</option>';
        html += '<option value="leyenda"' + (modo === 'leyenda' ? ' selected' : '') + '>Leyenda de cultivo</option>';
        html += '</select>';
        html += '<div class="cultivo-celda-texto-wrap' + (modo === 'texto' ? '' : ' d-none') + '">';
        html += '<input type="text" class="form-control form-control-sm" value="" readonly'
            + ' placeholder="Campo de texto al capturar" tabindex="-1">';
        html += '</div>';
        var opcionClass = 'cultivo-celda-opcion-wrap' + (modo === 'opcion' ? '' : ' d-none');
        if (mostrarValor) opcionClass += ' cultivo-con-valor';
        html += '<div class="' + opcionClass + '">';
        html += '<select class="form-select form-select-sm cultivo-opcion-input"'
            + ' data-bloque-id="' + secId + '" data-fila="' + fila + '" data-columna="' + col + '">';
        html += buildOpcionesSelectOptions(modo === 'opcion' ? celda.opcion_id : 0);
        html += '</select>';
        if (mostrarValor) {
            html += buildCeldaValorInputHtml(secId, fila, col, celda.valor || '');
        }
        html += '</div>';
        html += '<div class="cultivo-celda-leyenda-wrap' + (modo === 'leyenda' ? '' : ' d-none') + '">';
        html += buildLeyendaCeldaInner(secId, fila, col, modo === 'leyenda' ? celda : { leyenda_cultivo_categoria_id: 0 });
        html += '</div>';
        if (mostrarValor && isCuerpoBloque(secId) && modo !== 'opcion') {
            html += '<div class="cultivo-celda-valor-wrap mt-1">';
            html += buildCeldaValorInputHtml(secId, fila, col, celda.valor || '');
            html += '</div>';
        }
        html += '</div>';
        return html;
    }

    function buildOpcionesSelectOptions(selected) {
        var sel = selected !== undefined && selected !== null ? parseInt(selected, 10) : 0;
        if (isNaN(sel)) sel = 0;
        var html = '<option value="0"' + (sel === 0 ? ' selected' : '') + '>— Seleccione tipo —</option>';
        for (var oid in opcionesMap) {
            if (!Object.prototype.hasOwnProperty.call(opcionesMap, oid)) continue;
            var id = parseInt(oid, 10);
            html += '<option value="' + id + '"' + (sel === id ? ' selected' : '') + '>'
                + String(opcionesMap[oid]).replace(/</g, '&lt;') + '</option>';
        }
        return html;
    }

    function normalizeTituloCelda(raw) {
        if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
            var texto = String(raw.texto !== undefined ? raw.texto : (raw.text || ''));
            var colspan = parseInt(raw.colspan, 10);
            if (isNaN(colspan) || colspan < 1) colspan = 1;
            return { texto: texto, colspan: colspan };
        }
        return { texto: String(raw || ''), colspan: 1 };
    }

    function serializeTituloCelda(cell) {
        var c = normalizeTituloCelda(cell);
        if (c.colspan > 1) {
            return { texto: c.texto, colspan: c.colspan };
        }
        return c.texto;
    }

    function buildTituloItem(secId, colIdx, tituloRaw, tituloIdx, totalCols) {
        var cell = normalizeTituloCelda(tituloRaw);
        var maxSpan = Math.max(1, (totalCols || 1) - colIdx);
        if (cell.colspan > maxSpan) cell.colspan = maxSpan;
        var ph = 'Título ' + (tituloIdx + 1);
        var spanOpts = '';
        for (var s = 1; s <= maxSpan; s++) {
            spanOpts += '<option value="' + s + '"' + (cell.colspan === s ? ' selected' : '') + '>' + s + '</option>';
        }
        return '<li class="cultivo-titulo-item">'
            + '<span class="cultivo-titulo-drag-handle" title="Arrastrar título"><i class="fa-solid fa-grip-vertical"></i></span>'
            + '<input type="text" class="form-control form-control-sm cultivo-titulo-input flex-grow-1"'
            + ' data-bloque-id="' + secId + '" data-columna="' + colIdx + '"'
            + ' value="' + escAttr(cell.texto || '') + '"'
            + ' placeholder="' + escAttr(ph) + '">'
            + '<select class="form-select form-select-sm cultivo-titulo-colspan"'
            + ' data-bloque-id="' + secId + '" data-columna="' + colIdx + '"'
            + ' title="Columnas que ocupa">' + spanOpts + '</select>'
            + '<span class="badge bg-primary cultivo-titulo-span-badge' + (cell.colspan > 1 ? '' : ' d-none') + '"'
            + ' title="Columnas que ocupa">×' + cell.colspan + '</span>'
            + '</li>';
    }

    function tituloCeldaEstaCubiertaJs(titulosPorCol, columnas, col, tr) {
        var cubiertasHasta = 0;
        for (var c = 0; c < columnas; c++) {
            if (c < cubiertasHasta) {
                if (c === col) return true;
                continue;
            }
            var colArr = titulosPorCol[c] || [];
            var cell = normalizeTituloCelda(colArr[tr]);
            var colspan = Math.max(1, Math.min(cell.colspan, columnas - c));
            if (c !== col && c <= col && col < c + colspan) return true;
            cubiertasHasta = c + colspan;
        }
        return false;
    }

    function buildTitulosPreviewRows(titulos, columnas) {
        var maxFilas = 0;
        for (var c = 0; c < columnas; c++) {
            var col = titulos[c] || [];
            if (Array.isArray(col)) maxFilas = Math.max(maxFilas, col.length);
        }
        var rows = [];
        for (var tr = 0; tr < maxFilas; tr++) {
            var fila = [];
            var cubiertasHasta = 0;
            for (var c = 0; c < columnas; c++) {
                if (c < cubiertasHasta) continue;
                var cell = normalizeTituloCelda((titulos[c] || [])[tr]);
                var colspan = Math.max(1, Math.min(cell.colspan, columnas - c));
                fila.push({
                    texto: cell.texto || '',
                    colspan: colspan,
                    colInicio: c
                });
                cubiertasHasta = c + colspan;
            }
            if (fila.length > 0) rows.push(fila);
        }
        return rows;
    }

    function leerTitulosDesdeDom(wrap, columnas) {
        var titulos = [];
        var stacks = wrap.querySelectorAll('.cultivo-columnas-grid .cultivo-col-stack');
        stacks.forEach(function(stack, colIdx) {
            var colTitulos = [];
            stack.querySelectorAll('.cultivo-titulos-col-list .cultivo-titulo-item').forEach(function(item) {
                var inp = item.querySelector('.cultivo-titulo-input');
                var spanSel = item.querySelector('.cultivo-titulo-colspan');
                var colspan = spanSel ? parseInt(spanSel.value, 10) : 1;
                if (isNaN(colspan) || colspan < 1) colspan = 1;
                colTitulos.push({
                    texto: inp ? (inp.value || '') : '',
                    colspan: colspan
                });
            });
            titulos[colIdx] = colTitulos;
        });
        while (titulos.length < columnas) titulos.push([]);
        return titulos;
    }

    function actualizarBadgesTituloColspan(wrap) {
        wrap.querySelectorAll('.cultivo-titulo-item').forEach(function(item) {
            var spanSel = item.querySelector('.cultivo-titulo-colspan');
            var badge = item.querySelector('.cultivo-titulo-span-badge');
            if (!spanSel || !badge) return;
            var colspan = parseInt(spanSel.value, 10);
            if (isNaN(colspan) || colspan < 1) colspan = 1;
            if (colspan > 1) {
                badge.textContent = '×' + colspan;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        });
    }

    function actualizarPreviewTitulos(secId, titulos, columnas) {
        var wrap = getWrap(secId);
        if (!wrap) return;
        columnas = clampColumnas(columnas || 1);
        var preview = wrap.querySelector('.cultivo-titulos-preview-grid');
        var colGrid = wrap.querySelector('.cultivo-columnas-grid');
        var colsVar = String(columnas);
        if (preview) preview.style.setProperty('--cultivo-cols', colsVar);
        if (colGrid) colGrid.style.setProperty('--cultivo-cols', colsVar);

        var rows = buildTitulosPreviewRows(titulos, columnas);
        if (preview) {
            var html = '';
            if (rows.length === 0) {
                html = '<div class="cultivo-preview-titulo-cell is-vacio" style="grid-column: 1 / -1;">Sin títulos configurados</div>';
            } else {
                rows.forEach(function(fila) {
                    fila.forEach(function(cell) {
                        var colFin = cell.colInicio + cell.colspan;
                        var hint = 'Col. ' + (cell.colInicio + 1);
                        if (cell.colspan > 1) hint += '–' + colFin;
                        var texto = String(cell.texto || '').trim();
                        var cls = 'cultivo-preview-titulo-cell';
                        if (cell.colspan > 1) cls += ' is-span-multi';
                        if (texto === '') cls += ' is-vacio';
                        html += '<div class="' + cls + '" style="grid-column: span ' + cell.colspan + ';">'
                            + (texto !== '' ? escAttr(texto) : '(sin texto)')
                            + '<span class="cultivo-preview-col-hint">' + hint + ' · ' + cell.colspan + ' col.</span>'
                            + '</div>';
                    });
                });
            }
            preview.innerHTML = html;
        }

        var colsEnUnion = {};
        for (var tr = 0; tr < 20; tr++) {
            for (var c = 0; c < columnas; c++) {
                if (tituloCeldaEstaCubiertaJs(titulos, columnas, c, tr)) {
                    colsEnUnion[c] = true;
                }
            }
        }
        wrap.querySelectorAll('.cultivo-columnas-grid .cultivo-col-stack').forEach(function(stack, idx) {
            stack.classList.toggle('cultivo-col-en-union', !!colsEnUnion[idx]);
        });
        actualizarBadgesTituloColspan(wrap);
    }

    function refrescarPreviewDesdeDom(secId) {
        var wrap = getWrap(secId);
        if (!wrap) return;
        var dims = leerDimensiones(secId);
        if (!dims) return;
        var titulos = leerTitulosDesdeDom(wrap, dims.columnas);
        actualizarPreviewTitulos(secId, titulos, dims.columnas);
    }

    function buildColStack(secId, colIdx, colTitulos, totalCols) {
        var titles = Array.isArray(colTitulos) ? colTitulos : [];
        var listHtml = '';
        for (var t = 0; t < titles.length; t++) {
            listHtml += buildTituloItem(secId, colIdx, titles[t], t, totalCols);
        }
        return '<li class="cultivo-col-stack">'
            + '<div class="cultivo-col-header">'
            + '<span class="cultivo-col-drag-handle" title="Arrastrar columna"><i class="fa-solid fa-grip-vertical"></i></span>'
            + '<span>Col. ' + (colIdx + 1) + '</span>'
            + '</div>'
            + '<ul class="cultivo-titulos-col-list" data-bloque-id="' + secId + '" data-columna="' + colIdx + '">'
            + listHtml
            + '</ul>'
            + '<button type="button" class="btn btn-sm btn-link btn-cultivo-add-titulo-in-col p-0 small text-start"'
            + ' data-bloque-id="' + secId + '" data-columna="' + colIdx + '">'
            + '<i class="fa-solid fa-plus me-1"></i>Título</button>'
            + '</li>';
    }

    function getWrap(secId) {
        return form.querySelector('.cultivo-matriz-seccion[data-bloque-id="' + secId + '"]');
    }

    function leerDimensiones(secId) {
        var wrap = getWrap(secId);
        if (!wrap) return null;

        var filasInp = wrap.querySelector('.cultivo-dim-input[data-dim="filas"]');
        var colsInp = wrap.querySelector('.cultivo-dim-input[data-dim="columnas"]');
        var filasFallback = parseInt(wrap.getAttribute('data-filas') || '0', 10);
        var colsFallback = parseInt(wrap.getAttribute('data-columnas') || '1', 10);
        if (isNaN(filasFallback)) filasFallback = 0;
        if (isNaN(colsFallback) || colsFallback < 1) colsFallback = 1;

        var filasRaw = filasInp ? String(filasInp.value).trim() : '';
        var colsRaw = colsInp ? String(colsInp.value).trim() : '';
        var filas = filasRaw === '' ? filasFallback : clampFilas(filasRaw);
        var columnas = colsRaw === '' ? colsFallback : clampColumnas(colsRaw);

        var listCount = wrap.querySelectorAll('.cultivo-columnas-grid .cultivo-col-stack').length;
        if (listCount > 0) {
            columnas = clampColumnas(listCount);
        }

        return { wrap: wrap, filas: filas, columnas: columnas, filasInp: filasInp, colsInp: colsInp };
    }

    function guardarDimensionesEnWrap(wrap, filas, columnas) {
        wrap.setAttribute('data-filas', String(filas));
        wrap.setAttribute('data-columnas', String(columnas));
        var filasInp = wrap.querySelector('.cultivo-dim-input[data-dim="filas"]');
        var colsInp = wrap.querySelector('.cultivo-dim-input[data-dim="columnas"]');
        if (filasInp) filasInp.value = String(filas);
        if (colsInp) colsInp.value = String(columnas);
    }

    function capturarSeccion(secId) {
        var dims = leerDimensiones(secId);
        if (!dims) return null;

        var wrap = dims.wrap;
        var filas = dims.filas;
        var stacks = wrap.querySelectorAll('.cultivo-columnas-grid .cultivo-col-stack');
        var columnas = clampColumnas(stacks.length || dims.columnas);

        var titulos = [];
        stacks.forEach(function(stack, colIdx) {
            var colTitulos = [];
            stack.querySelectorAll('.cultivo-titulos-col-list .cultivo-titulo-item').forEach(function(item) {
                var inp = item.querySelector('.cultivo-titulo-input');
                var spanSel = item.querySelector('.cultivo-titulo-colspan');
                var colspan = spanSel ? parseInt(spanSel.value, 10) : 1;
                if (isNaN(colspan) || colspan < 1) colspan = 1;
                colTitulos.push(serializeTituloCelda({
                    texto: inp ? (inp.value || '') : '',
                    colspan: colspan
                }));
            });
            titulos[colIdx] = colTitulos;
        });
        while (titulos.length < columnas) titulos.push([]);

        var celdas = [];
        var fallbackCeldas = [];
        if (isCuerpoBloque(secId)) {
            var storedCells = wrap.getAttribute('data-celdas-json');
            if (storedCells) {
                try {
                    fallbackCeldas = JSON.parse(storedCells);
                } catch (errCells) {
                    fallbackCeldas = [];
                }
            }
            if (!Array.isArray(fallbackCeldas)) fallbackCeldas = [];
        }
        for (var r = 0; r < filas; r++) {
            celdas[r] = [];
            for (var c = 0; c < columnas; c++) {
                var modoSel = wrap.querySelector('.cultivo-celda-modo[data-fila="' + r + '"][data-columna="' + c + '"]');
                var modo = modoSel ? modoSel.value : 'texto';
                if (modo === 'opcion') {
                    var opcSel = wrap.querySelector('.cultivo-opcion-input[data-fila="' + r + '"][data-columna="' + c + '"]');
                    var val = opcSel ? parseInt(opcSel.value, 10) : 0;
                    celdas[r][c] = { modo: 'opcion', opcion_id: isNaN(val) ? 0 : Math.max(0, val) };
                } else if (modo === 'leyenda') {
                    var catSel = wrap.querySelector('.cultivo-leyenda-categoria-input[data-fila="' + r + '"][data-columna="' + c + '"]');
                    var cval = catSel ? parseInt(catSel.value, 10) : 0;
                    celdas[r][c] = {
                        modo: 'leyenda',
                        leyenda_cultivo_categoria_id: isNaN(cval) ? 0 : Math.max(0, cval)
                    };
                } else {
                    celdas[r][c] = { modo: 'texto' };
                }
                if (isCuerpoBloque(secId)) {
                    var vInp = wrap.querySelector('.cultivo-celda-valor-input[data-fila="' + r + '"][data-columna="' + c + '"]');
                    if (vInp) {
                        var vStr = vInp.value || '';
                        if (vStr !== '') celdas[r][c].valor = vStr;
                    } else {
                        var fbCell = fallbackCeldas[r] && fallbackCeldas[r][c];
                        if (fbCell && fbCell.valor !== undefined && String(fbCell.valor || '') !== '') {
                            celdas[r][c].valor = String(fbCell.valor);
                        }
                    }
                }
            }
        }

        guardarDimensionesEnWrap(wrap, filas, columnas);
        var result = { id: secId, tipo: getBloqueTipo(secId), filas: filas, columnas: columnas, titulos: titulos, celdas: celdas };
        if (isCuerpoBloque(secId)) {
            var chkValores = wrap.querySelector('.cultivo-valores-habilitado-check');
            result.valores_habilitado = chkValores ? chkValores.checked : false;
            var chkUnidades = wrap.querySelector('.cultivo-unidades-habilitado-check');
            result.unidades_habilitado = chkUnidades ? chkUnidades.checked : false;
            var unidadGlobalInp = wrap.querySelector('.cultivo-cuerpo-unidad-input');
            if (unidadGlobalInp) {
                result.unidad = unidadGlobalInp.value || '';
            } else {
                result.unidad = wrap.getAttribute('data-unidad-global') || '';
            }
            var aliSel = wrap.querySelector('.cultivo-alineacion-filas-select');
            result.alineacion_filas = aliSel ? aliSel.value : (wrap.getAttribute('data-alineacion-filas') || 'centro');
        }
        return result;
    }

    function destruirSortable(secId) {
        ['cols', 'trash', 'palette'].forEach(function(key) {
            var inst = sortableInstances[secId + '-' + key];
            if (inst && typeof inst.destroy === 'function') {
                inst.destroy();
            }
            delete sortableInstances[secId + '-' + key];
        });
        var titleInsts = sortableInstances[secId + '-titles'];
        if (Array.isArray(titleInsts)) {
            titleInsts.forEach(function(inst) {
                if (inst && typeof inst.destroy === 'function') inst.destroy();
            });
        }
        delete sortableInstances[secId + '-titles'];
    }

    function convertirPlantillaEnTitulo(evt, secId) {
        if (!evt.item.classList.contains('cultivo-titulo-plantilla')) return;
        var list = evt.to;
        if (!list || !list.classList.contains('cultivo-titulos-col-list')) {
            evt.item.remove();
            return;
        }
        var stack = list.closest('.cultivo-col-stack');
        var stacks = getWrap(secId).querySelectorAll('.cultivo-columnas-grid .cultivo-col-stack');
        var colIdx = Array.prototype.indexOf.call(stacks, stack);
        if (colIdx < 0) colIdx = 0;
        var temp = document.createElement('div');
        temp.innerHTML = buildTituloItem(secId, colIdx, '', evt.newIndex >= 0 ? evt.newIndex : 0, stacks.length);
        var newItem = temp.firstElementChild;
        if (newItem) {
            evt.item.replaceWith(newItem);
        } else {
            evt.item.remove();
        }
    }

    function initSortableSeccion(secId) {
        if (typeof Sortable === 'undefined') return;

        destruirSortable(secId);
        var wrap = getWrap(secId);
        if (!wrap) return;

        var colGrid = wrap.querySelector('.cultivo-columnas-grid');
        var trash = wrap.querySelector('.cultivo-trash-zone');
        var palette = wrap.querySelector('.cultivo-titulo-palette ul');
        if (!colGrid || !trash) return;

        var groupCols = 'cultivo-cols-' + secId;
        var groupTitles = 'cultivo-titles-' + secId;

        sortableInstances[secId + '-cols'] = new Sortable(colGrid, {
            group: { name: groupCols, pull: true, put: true },
            handle: '.cultivo-col-drag-handle',
            animation: 150,
            draggable: '.cultivo-col-stack',
            onEnd: function() {
                renderSeccion(secId);
            }
        });

        var titleInsts = [];
        wrap.querySelectorAll('.cultivo-titulos-col-list').forEach(function(list) {
            titleInsts.push(new Sortable(list, {
                group: { name: groupTitles, pull: true, put: true },
                handle: '.cultivo-titulo-drag-handle',
                animation: 150,
                draggable: '.cultivo-titulo-item',
                onAdd: function(evt) {
                    convertirPlantillaEnTitulo(evt, secId);
                    setTimeout(function() { renderSeccion(secId); }, 0);
                },
                onEnd: function() {
                    renderSeccion(secId);
                }
            }));
        });
        sortableInstances[secId + '-titles'] = titleInsts;

        if (palette) {
            sortableInstances[secId + '-palette'] = new Sortable(palette, {
                group: { name: groupTitles, pull: 'clone', put: false },
                sort: false,
                draggable: '.cultivo-titulo-plantilla'
            });
        }

        sortableInstances[secId + '-trash'] = new Sortable(trash, {
            group: {
                name: 'cultivo-trash-' + secId,
                pull: false,
                put: [groupTitles, groupCols]
            },
            sort: false,
            onAdd: function(evt) {
                var data = capturarSeccion(secId);
                if (!data) {
                    evt.item.remove();
                    return;
                }

                if (evt.item.classList.contains('cultivo-titulo-plantilla')) {
                    evt.item.remove();
                    return;
                }

                if (evt.item.classList.contains('cultivo-col-stack')) {
                    if (data.columnas <= 1) {
                        evt.item.remove();
                        if (typeof showToast === 'function') {
                            showToast('Debe quedar al menos una columna', 'error');
                        }
                        renderSeccion(secId, data);
                        return;
                    }
                    var colIdx = evt.oldIndex;
                    if (colIdx < 0 || colIdx >= data.titulos.length) {
                        evt.item.remove();
                        renderSeccion(secId, data);
                        return;
                    }
                    data.titulos.splice(colIdx, 1);
                    data.celdas.forEach(function(row) {
                        if (row && row.length > colIdx) row.splice(colIdx, 1);
                    });
                    data.columnas = data.titulos.length;
                    evt.item.remove();
                    renderSeccion(secId, data);
                    return;
                }

                if (evt.item.classList.contains('cultivo-titulo-item')) {
                    var fromList = evt.from;
                    var fromStack = fromList ? fromList.closest('.cultivo-col-stack') : null;
                    var allStacks = wrap.querySelectorAll('.cultivo-columnas-grid .cultivo-col-stack');
                    var fromColIdx = fromStack ? Array.prototype.indexOf.call(allStacks, fromStack) : -1;
                    var titleIdx = evt.oldIndex;

                    evt.item.remove();

                    if (fromColIdx < 0 || fromColIdx >= data.titulos.length) {
                        renderSeccion(secId, data);
                        return;
                    }
                    if (titleIdx >= 0 && titleIdx < data.titulos[fromColIdx].length) {
                        data.titulos[fromColIdx].splice(titleIdx, 1);
                    }
                    renderSeccion(secId, data);
                }
            }
        });
    }

    function renderSeccion(secId, dataOpt) {
        var actual = dataOpt || capturarSeccion(secId);
        if (!actual) return;

        var wrap = getWrap(secId);
        if (!wrap) return;

        actual.columnas = clampColumnas(actual.columnas);
        actual.filas = clampFilas(actual.filas);

        if (isCuerpoBloque(secId)) {
            if (typeof actual.valores_habilitado !== 'boolean') {
                actual.valores_habilitado = wrap.getAttribute('data-valores-habilitado') === '1';
            }
            wrap.setAttribute('data-valores-habilitado', actual.valores_habilitado ? '1' : '0');
            var chkValoresRender = wrap.querySelector('.cultivo-valores-habilitado-check');
            if (chkValoresRender) chkValoresRender.checked = actual.valores_habilitado;
            if (typeof actual.unidades_habilitado !== 'boolean') {
                actual.unidades_habilitado = wrap.getAttribute('data-unidades-habilitado') === '1';
            }
            wrap.setAttribute('data-unidades-habilitado', actual.unidades_habilitado ? '1' : '0');
            var chkUnidadesRender = wrap.querySelector('.cultivo-unidades-habilitado-check');
            if (chkUnidadesRender) chkUnidadesRender.checked = actual.unidades_habilitado;
            if (typeof actual.unidad !== 'string') {
                actual.unidad = wrap.getAttribute('data-unidad-global') || '';
            }
            wrap.setAttribute('data-unidad-global', actual.unidad || '');
            var unidadWrap = wrap.querySelector('.cultivo-cuerpo-unidad-wrap');
            if (unidadWrap) unidadWrap.classList.toggle('d-none', !actual.unidades_habilitado);
            var unidadGlobalRender = wrap.querySelector('.cultivo-cuerpo-unidad-input');
            if (unidadGlobalRender) unidadGlobalRender.value = actual.unidad || '';
            var aliRender = wrap.querySelector('.cultivo-alineacion-filas-select');
            if (aliRender) aliRender.value = actual.alineacion_filas || 'centro';
            wrap.setAttribute('data-alineacion-filas', actual.alineacion_filas || 'centro');
        }

        if (!Array.isArray(actual.titulos)) actual.titulos = [[]];
        while (actual.titulos.length < actual.columnas) {
            actual.titulos.push([]);
        }
        while (actual.titulos.length > actual.columnas) {
            actual.titulos.pop();
        }
        actual.titulos = actual.titulos.map(function(col, colIdx) {
            var arr = Array.isArray(col) ? col.slice() : (col ? [col] : []);
            var maxSpan = Math.max(1, actual.columnas - colIdx);
            return arr.slice(0, MAX_TITULOS_POR_COL).map(function(item) {
                var cell = normalizeTituloCelda(item);
                if (cell.colspan > maxSpan) {
                    cell.colspan = maxSpan;
                }
                return cell;
            });
        });

        if (actual.filas > 0) {
            if (!Array.isArray(actual.celdas)) actual.celdas = [];
            for (var ri = 0; ri < actual.filas; ri++) {
                if (!Array.isArray(actual.celdas[ri])) actual.celdas[ri] = [];
                for (var ci = 0; ci < actual.columnas; ci++) {
                    actual.celdas[ri][ci] = normalizeCelda(actual.celdas[ri][ci]);
                }
            }
        }

        guardarDimensionesEnWrap(wrap, actual.filas, actual.columnas);
        wrap.setAttribute('data-celdas-json', JSON.stringify(actual.celdas || []));

        var colGrid = wrap.querySelector('.cultivo-columnas-grid');
        if (colGrid) {
            var gridHtml = '';
            for (var c = 0; c < actual.columnas; c++) {
                gridHtml += buildColStack(secId, c, actual.titulos[c], actual.columnas);
            }
            colGrid.innerHTML = gridHtml;
        }

        var table = wrap.querySelector('.cultivo-matriz-grid');
        if (table) {
            var refHtml = '<tr class="cultivo-fila-ref">';
            for (var rc = 0; rc < actual.columnas; rc++) {
                refHtml += '<th class="p-1 small text-muted text-center">' + (rc + 1) + '</th>';
            }
            refHtml += '</tr>';
            var thead = table.querySelector('thead');
            if (thead) thead.innerHTML = refHtml;

            var tbody = table.querySelector('tbody');
            if (!tbody) {
                tbody = document.createElement('tbody');
                table.appendChild(tbody);
            }
            var bodyHtml = '';
            if (actual.filas < 1) {
                bodyHtml = '<tr class="cultivo-sin-filas"><td colspan="' + actual.columnas
                    + '" class="text-muted small text-center py-2">Sin filas de datos (solo títulos de columna)</td></tr>';
            } else {
                var mostrarValorCelda = isCuerpoBloque(secId) && actual.valores_habilitado;
                for (var r = 0; r < actual.filas; r++) {
                    bodyHtml += '<tr>';
                    for (var c2 = 0; c2 < actual.columnas; c2++) {
                        var val = (actual.celdas[r] && actual.celdas[r][c2] !== undefined) ? actual.celdas[r][c2] : celdaDefault();
                        bodyHtml += '<td class="p-1">' + buildCeldaCell(secId, r, c2, val, mostrarValorCelda) + '</td>';
                    }
                    bodyHtml += '</tr>';
                }
            }
            tbody.innerHTML = bodyHtml;
        }

        actualizarPreviewTitulos(secId, actual.titulos, actual.columnas);
        initSortableSeccion(secId);
    }

    function agregarColumna(secId) {
        var data = capturarSeccion(secId);
        if (!data) return;
        if (data.columnas >= 20) {
            if (typeof showToast === 'function') {
                showToast('Máximo 20 columnas', 'error');
            }
            return;
        }
        data.titulos.push([]);
        data.celdas.forEach(function(row) {
            row.push(celdaDefault());
        });
        data.columnas = data.titulos.length;
        renderSeccion(secId, data);
    }

    function agregarTituloEnColumna(secId, colIdx) {
        var data = capturarSeccion(secId);
        if (!data) return;
        if (colIdx < 0 || colIdx >= data.titulos.length) return;
        if (!Array.isArray(data.titulos[colIdx])) data.titulos[colIdx] = [];
        if (data.titulos[colIdx].length >= MAX_TITULOS_POR_COL) {
            if (typeof showToast === 'function') {
                showToast('Máximo ' + MAX_TITULOS_POR_COL + ' títulos por columna', 'error');
            }
            return;
        }
        data.titulos[colIdx].push({ texto: '', colspan: 1 });
        renderSeccion(secId, data);
    }

    function programarRender(secId) {
        var dims = leerDimensiones(secId);
        if (!dims) return;
        var filasRaw = dims.filasInp ? String(dims.filasInp.value).trim() : '';
        var colsRaw = dims.colsInp ? String(dims.colsInp.value).trim() : '';
        if (filasRaw === '' || colsRaw === '') return;

        if (debounceTimers[secId]) clearTimeout(debounceTimers[secId]);
        debounceTimers[secId] = setTimeout(function() {
            var data = capturarSeccion(secId);
            if (!data) return;
            var targetCols = clampColumnas(colsRaw);
            var targetFilas = clampFilas(filasRaw);
            while (data.titulos.length < targetCols) {
                data.titulos.push([]);
                data.celdas.forEach(function(row) { row.push(celdaDefault()); });
            }
            while (data.titulos.length > targetCols) {
                data.titulos.pop();
                data.celdas.forEach(function(row) { row.pop(); });
            }
            while (data.celdas.length < targetFilas) {
                var row = [];
                for (var i = 0; i < data.columnas; i++) row.push(celdaDefault());
                data.celdas.push(row);
            }
            while (data.celdas.length > targetFilas) {
                data.celdas.pop();
            }
            data.filas = targetFilas;
            data.columnas = targetCols;
            renderSeccion(secId, data);
        }, 150);
    }

    function reassignBloqueDomIds(root, oldId, newId, tipo) {
        root.setAttribute('data-bloque-id', newId);
        if (tipo) root.setAttribute('data-tipo', tipo);
        root.querySelectorAll('[data-bloque-id]').forEach(function(el) {
            if (el.getAttribute('data-bloque-id') === oldId) {
                el.setAttribute('data-bloque-id', newId);
            }
        });
        var wrap = root.querySelector('.cultivo-matriz-seccion');
        if (wrap) {
            wrap.setAttribute('data-bloque-id', newId);
            if (tipo) wrap.setAttribute('data-tipo', tipo);
        }
    }

    function updateBloqueLabels(bloque, allBloques) {
        var labelText = bloqueDisplayLabel(bloque, allBloques);
        var ordenItem = getOrdenItem(bloque.id);
        if (ordenItem) {
            var ordenLabel = ordenItem.querySelector('.cultivo-orden-label');
            if (ordenLabel) ordenLabel.textContent = labelText;
            var badge = ordenItem.querySelector('.badge');
            if (badge && seccionLabels[bloque.tipo]) badge.textContent = seccionLabels[bloque.tipo];
            var dupBtn = ordenItem.querySelector('.btn-cultivo-dup-bloque');
            var delBtn = ordenItem.querySelector('.btn-cultivo-del-bloque');
            if (dupBtn) dupBtn.setAttribute('data-bloque-id', bloque.id);
            if (delBtn) delBtn.setAttribute('data-bloque-id', bloque.id);
        }
        var configItem = getConfigItem(bloque.id);
        if (configItem) {
            var h6 = configItem.querySelector('.cultivo-matriz-seccion h6');
            if (h6) h6.textContent = labelText;
        }
    }

    function initBloquesSortable() {
        if (typeof Sortable === 'undefined') return;
        var list = document.getElementById('cultivo_orden_list');
        if (!list) return;
        if (bloquesSortable && typeof bloquesSortable.destroy === 'function') {
            bloquesSortable.destroy();
        }
        bloquesSortable = new Sortable(list, {
            handle: '.cultivo-orden-drag',
            animation: 150,
            draggable: '.cultivo-orden-item',
            onEnd: function() {
                syncConfigOrderFromOrden();
            }
        });
    }

    function duplicarBloque(bloqueId) {
        var data = capturarSeccion(bloqueId);
        if (!data) return;
        var bloques = capturarTodosBloques();
        var ids = bloques.map(function(b) { return b.id; });
        data.id = generateBloqueId(data.tipo, ids);

        var ordenItem = getOrdenItem(bloqueId);
        var configItem = getConfigItem(bloqueId);
        if (!ordenItem || !configItem || !ordenItem.parentNode || !configItem.parentNode) return;

        var ordenClone = ordenItem.cloneNode(true);
        var configClone = configItem.cloneNode(true);
        reassignBloqueDomIds(ordenClone, bloqueId, data.id, data.tipo);
        reassignBloqueDomIds(configClone, bloqueId, data.id, data.tipo);
        ordenItem.parentNode.insertBefore(ordenClone, ordenItem.nextSibling);
        configItem.parentNode.insertBefore(configClone, configItem.nextSibling);

        var allWithNew = capturarTodosBloques();
        allWithNew.push(data);
        updateBloqueLabels(data, allWithNew);
        renderSeccion(data.id, data);
        initSortableSeccion(data.id);
        refreshOrdenPosNumbers();
        initBloquesSortable();
    }

    function eliminarBloque(bloqueId) {
        var bloques = capturarTodosBloques();
        if (bloques.length <= 1) {
            if (typeof showToast === 'function') {
                showToast('Debe quedar al menos un bloque', 'error');
            }
            return;
        }
        destruirSortable(bloqueId);
        var ordenItem = getOrdenItem(bloqueId);
        var configItem = getConfigItem(bloqueId);
        if (ordenItem) ordenItem.remove();
        if (configItem) configItem.remove();
        refreshOrdenPosNumbers();
        initBloquesSortable();
    }

    function agregarBloque(tipo) {
        if (!seccionLabels[tipo]) return;
        var refId = null;
        getAllBloqueIds().forEach(function(id) {
            if (!refId && getBloqueTipo(id) === tipo) refId = id;
        });
        if (!refId) {
            getAllBloqueIds().forEach(function(id) {
                if (!refId) refId = id;
            });
        }
        var bloques = capturarTodosBloques();
        var ids = bloques.map(function(b) { return b.id; });
        var newId = generateBloqueId(tipo, ids);
        var newData = defaultBloqueData(tipo, newId);

        var ordenList = document.getElementById('cultivo_orden_list');
        var configList = document.getElementById('cultivo_bloques_list');
        if (!ordenList || !configList) return;
        if (!refId && !bloqueTemplates[tipo] && !ordenTemplates[tipo]) return;

        var ordenRef = null;
        if (ordenTemplates[tipo]) {
            ordenRef = ordenTemplates[tipo].cloneNode(true);
        } else if (refId) {
            var liveOrden = getOrdenItem(refId);
            if (liveOrden) ordenRef = liveOrden.cloneNode(true);
        }
        var configRef = null;
        if (bloqueTemplates[tipo]) {
            configRef = bloqueTemplates[tipo].cloneNode(true);
        } else if (refId) {
            var liveConfig = getConfigItem(refId);
            if (liveConfig) configRef = liveConfig.cloneNode(true);
        }
        if (!ordenRef || !configRef) return;

        var oldOrdenId = ordenRef.getAttribute('data-bloque-id') || refId || newId;
        var oldConfigId = configRef.getAttribute('data-bloque-id') || refId || newId;
        reassignBloqueDomIds(ordenRef, oldOrdenId, newId, tipo);
        reassignBloqueDomIds(configRef, oldConfigId, newId, tipo);
        ordenList.appendChild(ordenRef);
        configList.appendChild(configRef);

        var allBloques = capturarTodosBloques();
        allBloques.push(newData);
        updateBloqueLabels(newData, allBloques);
        renderSeccion(newId, newData);
        initSortableSeccion(newId);
        refreshOrdenPosNumbers();
        initBloquesSortable();
    }

    function attrBloqueId(el) {
        return el ? (el.getAttribute('data-bloque-id') || el.getAttribute('data-seccion')) : '';
    }

    cacheBloqueTemplates();
    getAllBloqueIds().forEach(function(secId) {
        var wrap = getWrap(secId);
        if (!wrap) return;
        var dims = leerDimensiones(secId);
        if (dims) guardarDimensionesEnWrap(wrap, dims.filas, dims.columnas);
        if (isCuerpoBloque(secId)) {
            var bulkModo = wrap.querySelector('.cultivo-bulk-celda-modo');
            if (bulkModo) toggleBulkCeldaPanels(wrap, bulkModo.value);
        }
        initSortableSeccion(secId);
        refrescarPreviewDesdeDom(secId);
    });
    initBloquesSortable();

    form.addEventListener('input', function(e) {
        if (e.target.classList.contains('cultivo-titulo-input')) {
            var wrapInp = e.target.closest('.cultivo-matriz-seccion');
            if (wrapInp) {
                var secInp = attrBloqueId(wrapInp);
                if (secInp) refrescarPreviewDesdeDom(secInp);
            }
        }
    });

    form.querySelectorAll('.btn-cultivo-add-col').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var secId = attrBloqueId(btn);
            if (secId) agregarColumna(secId);
        });
    });

    form.querySelectorAll('.btn-cultivo-add-bloque').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tipo = btn.getAttribute('data-tipo');
            if (tipo) agregarBloque(tipo);
        });
    });

    form.addEventListener('change', function(e) {
        if (e.target.classList.contains('cultivo-titulo-colspan')) {
            var wrapSpan = e.target.closest('.cultivo-matriz-seccion');
            if (wrapSpan) {
                var secSpan = attrBloqueId(wrapSpan);
                if (secSpan) refrescarPreviewDesdeDom(secSpan);
            }
            return;
        }
        if (e.target.classList.contains('cultivo-valores-habilitado-check')) {
            var secValores = attrBloqueId(e.target);
            if (secValores) {
                var dataValores = capturarSeccion(secValores);
                if (dataValores) {
                    dataValores.valores_habilitado = e.target.checked;
                    if (!dataValores.valores_habilitado) {
                        dataValores.unidades_habilitado = false;
                    }
                    renderSeccion(secValores, dataValores);
                }
            }
            return;
        }
        if (e.target.classList.contains('cultivo-unidades-habilitado-check')) {
            var secUnidades = attrBloqueId(e.target);
            if (secUnidades) {
                var wrapUnidades = getWrap(secUnidades);
                var dataUnidades = capturarSeccion(secUnidades);
                if (dataUnidades) {
                    dataUnidades.unidades_habilitado = e.target.checked;
                    if (wrapUnidades) {
                        var unidadWrap = wrapUnidades.querySelector('.cultivo-cuerpo-unidad-wrap');
                        if (unidadWrap) unidadWrap.classList.toggle('d-none', !e.target.checked);
                    }
                    renderSeccion(secUnidades, dataUnidades);
                }
            }
            return;
        }
        if (e.target.classList.contains('cultivo-celda-modo')) {
            var config = e.target.closest('.cultivo-celda-config');
            toggleCeldaModoPanels(config, e.target.value);
            return;
        }
        if (e.target.classList.contains('cultivo-bulk-celda-modo')) {
            var wrapBulk = e.target.closest('.cultivo-matriz-seccion');
            if (wrapBulk) toggleBulkCeldaPanels(wrapBulk, e.target.value);
            return;
        }
    });

    form.addEventListener('click', function(e) {
        var btnDup = e.target.closest('.btn-cultivo-dup-bloque');
        if (btnDup) {
            var dupId = btnDup.getAttribute('data-bloque-id');
            if (dupId) duplicarBloque(dupId);
            return;
        }
        var btnDel = e.target.closest('.btn-cultivo-del-bloque');
        if (btnDel) {
            var delId = btnDel.getAttribute('data-bloque-id');
            if (delId) eliminarBloque(delId);
            return;
        }
        var btnBulk = e.target.closest('.btn-cultivo-bulk-aplicar');
        if (btnBulk) {
            var secBulk = attrBloqueId(btnBulk);
            if (secBulk) aplicarBulkCeldaATodasColumnas(secBulk);
            return;
        }
        var btn = e.target.closest('.btn-cultivo-add-titulo-in-col');
        if (!btn) return;
        var secId = attrBloqueId(btn);
        var colIdx = parseInt(btn.getAttribute('data-columna'), 10);
        if (secId && !isNaN(colIdx)) {
            agregarTituloEnColumna(secId, colIdx);
        }
    });

    form.querySelectorAll('.cultivo-dim-input').forEach(function(inp) {
        var secId = attrBloqueId(inp);
        if (!secId) return;
        inp.addEventListener('input', function() {
            var wrap = getWrap(secId);
            if (!wrap) return;
            var filasInp = wrap.querySelector('.cultivo-dim-input[data-dim="filas"]');
            var colsInp = wrap.querySelector('.cultivo-dim-input[data-dim="columnas"]');
            var fr = filasInp ? String(filasInp.value).trim() : '';
            var cr = colsInp ? String(colsInp.value).trim() : '';
            if (fr !== '' && !isNaN(parseInt(fr, 10))) {
                wrap.setAttribute('data-filas', String(clampFilas(fr)));
            }
            if (cr !== '' && !isNaN(parseInt(cr, 10))) {
                wrap.setAttribute('data-columnas', String(clampColumnas(cr)));
            }
            programarRender(secId);
        });
        inp.addEventListener('change', function() {
            if (debounceTimers[secId]) {
                clearTimeout(debounceTimers[secId]);
                delete debounceTimers[secId];
            }
            programarRender(secId);
            setTimeout(function() {
                var data = capturarSeccion(secId);
                if (data) renderSeccion(secId, data);
            }, 160);
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        getAllBloqueIds().forEach(function(secId) {
            if (debounceTimers[secId]) {
                clearTimeout(debounceTimers[secId]);
                delete debounceTimers[secId];
            }
            var data = capturarSeccion(secId);
            if (data) renderSeccion(secId, data);
        });

        var bloquesPayload = capturarTodosBloques();
        if (!bloquesPayload.length) {
            if (typeof showToast === 'function') {
                showToast('No se pudo leer la configuración de la matriz', 'error');
            }
            return;
        }

        var payload = { version: 2, bloques: bloquesPayload };
        var jsonStr = JSON.stringify(payload);
        if (!jsonStr) {
            if (typeof showToast === 'function') {
                showToast('No se pudo serializar la matriz', 'error');
            }
            return;
        }

        var hidden = document.getElementById('cultivo_matriz_json');
        if (hidden) hidden.value = jsonStr;

        var submitBtn = form.querySelector('button[type="submit"]');
        var originalHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
        }

        var priaInp = form.querySelector('input[name="prianacategoria_id"]');
        var priaId = priaInp ? priaInp.value : '';
        var csrfInp = form.querySelector('input[name*="csrf"]');
        var fd = new FormData();
        fd.append('prianacategoria_id', priaId);
        fd.append('cultivo_matriz_json', jsonStr);
        if (csrfInp) {
            fd.append(csrfInp.name, csrfInp.value);
        }
        fetch(form.action, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(r) {
            return r.json().then(function(d) {
                return { ok: r.ok, data: d || {} };
            }).catch(function() {
                return { ok: false, data: { success: false, message: 'Respuesta inválida del servidor' } };
            });
        }).then(function(result) {
            var d = result.data || {};
            if (d.csrf_token && d.csrf_name) {
                var csrfInp = form.querySelector('input[name="' + d.csrf_name + '"]') || form.querySelector('input[name*="csrf"]');
                if (csrfInp) {
                    csrfInp.name = d.csrf_name;
                    csrfInp.value = d.csrf_token;
                }
            }
            if (typeof showToast === 'function') {
                showToast(d.message || (result.ok ? 'Guardado' : 'Error al guardar'), d.success ? 'success' : 'error');
            }
            if (d.success && d.reload) {
                window.location.reload();
            }
        }).catch(function() {
            if (typeof showToast === 'function') {
                showToast('Error al guardar la matriz', 'error');
            }
        }).finally(function() {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }
        });
    });
})();
</script>
