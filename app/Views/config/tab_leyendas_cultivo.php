<?php
/**
 * @var array<int, array<string, mixed>> $leyendas_cultivo
 * @var array<int, array<string, mixed>> $leyendas_cultivo_categorias
 * @var int $editar_leyenda_cultivo
 * @var array<string, mixed> $editar_leyenda_cultivo_data
 * @var int $editar_leyenda_cultivo_categoria
 * @var array<string, mixed> $editar_leyenda_cultivo_categoria_data
 */
$leyendasCultivo = $leyendas_cultivo ?? [];
$categorias = $leyendas_cultivo_categorias ?? [];
$editarId = (int) ($editar_leyenda_cultivo ?? 0);
$editarData = is_array($editar_leyenda_cultivo_data ?? null) ? $editar_leyenda_cultivo_data : [];
$editarCatId = (int) ($editar_leyenda_cultivo_categoria ?? 0);
$editarCatData = is_array($editar_leyenda_cultivo_categoria_data ?? null) ? $editar_leyenda_cultivo_categoria_data : [];
$mensajeEdit = (string) ($editarData['mensaje'] ?? '');
$categoriasActivas = array_values(array_filter($categorias, static fn($c) => (int) ($c['activo'] ?? 0) === 1));

$leyendasPorCategoria = [];
$leyendasSinCategoria = [];
foreach ($leyendasCultivo as $lc) {
    $catId = (int) ($lc['leyenda_cultivo_categoria_id'] ?? 0);
    if ($catId > 0) {
        $leyendasPorCategoria[$catId][] = $lc;
    } else {
        $leyendasSinCategoria[] = $lc;
    }
}

$categoriasMap = [];
foreach ($categorias as $cat) {
    $cid = (int) ($cat['leyenda_cultivo_categoria_id'] ?? 0);
    if ($cid > 0) {
        $categoriasMap[$cid] = (string) ($cat['nombre'] ?? '');
    }
}
?>
<div class="tab-pane fade <?= ($activeTab ?? '') === 'leyendas_cultivo' ? 'show active' : '' ?>" id="tab-leyendas_cultivo" role="tabpanel">
    <?= view('config/partials/config_section_guide', [
        'guide_key' => 'leyendas_cultivo',
        'title' => 'Leyendas para cultivos microbiológicos',
        'body' => 'Primero cree <strong>categorías</strong> (bacterias, hongos, etc.) y luego los textos reutilizables al registrar resultados de pruebas tipo cultivo.',
        'steps' => [
            'Cree o edite categorías en el primer bloque.',
            'Agregue leyendas con título y mensaje enriquecido en el segundo bloque.',
            'Use buscar y paginación si tiene muchas leyendas registradas.',
        ],
    ]) ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fa-solid fa-folder-tree me-2"></i>Categorías de leyendas</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Agrupe las leyendas por categoría (ej. <em>Bacterias</em>, <em>Hongos</em>, <em>Interpretación general</em>).
                Cada categoría puede contener muchas leyendas.
            </p>
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="config-paginated-list" data-page-size="10">
                    <?= view('config/partials/config_list_toolbar', ['search_placeholder' => 'Buscar categoría…']) ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Categoría</th>
                                    <th class="text-center" style="width: 90px;">Leyendas</th>
                                    <th class="text-center" style="width: 90px;">Estado</th>
                                    <th class="text-center" style="width: 120px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($categorias === []): ?>
                                <tr>
                                    <td colspan="4" class="text-muted text-center py-3">No hay categorías. Cree una antes de agregar leyendas.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($categorias as $cat):
                                    $cid = (int) ($cat['leyenda_cultivo_categoria_id'] ?? 0);
                                    $countLc = count($leyendasPorCategoria[$cid] ?? []);
                                ?>
                                <tr>
                                    <td><strong><?= esc((string) ($cat['nombre'] ?? '')) ?></strong></td>
                                    <td class="text-center"><span class="badge bg-light text-dark"><?= $countLc ?></span></td>
                                    <td class="text-center">
                                        <?= ((int) ($cat['activo'] ?? 0) === 1) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="<?= site_url('config?tab=leyendas_cultivo&editar_leyenda_cultivo_categoria=' . $cid) ?>"
                                               class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                            <a href="<?= site_url('config/deleteleyendacultivocategoria/' . $cid) ?>"
                                               class="btn btn-sm btn-outline-danger" title="Eliminar"
                                               onclick="return uiConfirmLink(this, '¿Eliminar esta categoría?');"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="config-list-pagination">
                        <span class="text-muted small"></span>
                        <div class="config-list-pagination-nav"></div>
                    </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="border rounded p-3 bg-light">
                        <h6 class="mb-3"><?= $editarCatId > 0 ? 'Editar categoría' : 'Nueva categoría' ?></h6>
                        <?= form_open(site_url('config/saveleyendacultivocategoria'), ['id' => 'form_leyenda_cultivo_categoria']) ?>
                        <input type="hidden" name="leyenda_cultivo_categoria_id" value="<?= $editarCatId > 0 ? $editarCatId : 0 ?>">
                        <div class="mb-3">
                            <label class="form-label" for="leyenda_cultivo_cat_nombre">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="leyenda_cultivo_cat_nombre" class="form-control form-control-sm" required maxlength="255"
                                   value="<?= esc((string) ($editarCatData['nombre'] ?? '')) ?>"
                                   placeholder="Ej: Bacterias gram positivas">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="leyenda_cultivo_cat_activo">Estado</label>
                            <select name="activo" id="leyenda_cultivo_cat_activo" class="form-select form-select-sm">
                                <option value="1" <?= ((int) ($editarCatData['activo'] ?? 1) === 1) ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= ((int) ($editarCatData['activo'] ?? 1) === 0) ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar categoría</button>
                        <?php if ($editarCatId > 0): ?>
                        <a href="<?= site_url('config?tab=leyendas_cultivo') ?>" class="btn btn-secondary btn-sm">Cancelar</a>
                        <?php endif; ?>
                        <?= form_close() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fa-solid fa-microscope me-2"></i>Leyendas de cultivo</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Mensajes predeterminados para pruebas de tipo <strong>cultivo</strong>. Puede usar negrilla, cursiva, listas y saltos de línea.
                Estos textos estarán disponibles al registrar resultados de cultivos.
            </p>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="config-paginated-list" data-page-size="12">
                    <?= view('config/partials/config_list_toolbar', ['search_placeholder' => 'Buscar leyenda por título o categoría…']) ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Categoría</th>
                                    <th>Título</th>
                                    <th>Vista previa</th>
                                    <th class="text-center" style="width: 90px;">Estado</th>
                                    <th class="text-center" style="width: 120px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($leyendasCultivo === []): ?>
                                <tr>
                                    <td colspan="5" class="text-muted text-center py-3">No hay leyendas de cultivo registradas.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($leyendasCultivo as $lc):
                                    $preview = trim(strip_tags((string) ($lc['mensaje'] ?? '')));
                                    if (mb_strlen($preview) > 80) {
                                        $preview = mb_substr($preview, 0, 80) . '…';
                                    }
                                    $catNombre = (string) ($lc['categoria_nombre'] ?? '');
                                    if ($catNombre === '') {
                                        $catIdTmp = (int) ($lc['leyenda_cultivo_categoria_id'] ?? 0);
                                        $catNombre = $categoriasMap[$catIdTmp] ?? '— Sin categoría —';
                                    }
                                ?>
                                <tr>
                                    <td class="small"><?= esc($catNombre) ?></td>
                                    <td><strong><?= esc((string) ($lc['titulo'] ?? '')) ?></strong></td>
                                    <td class="small text-muted"><?= esc($preview !== '' ? $preview : '—') ?></td>
                                    <td class="text-center">
                                        <?= ((int) ($lc['activo'] ?? 0) === 1) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="<?= site_url('config?tab=leyendas_cultivo&editar_leyenda_cultivo=' . (int) ($lc['leyenda_cultivo_id'] ?? 0)) ?>"
                                               class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                            <a href="<?= site_url('config/deleteleyendacultivo/' . (int) ($lc['leyenda_cultivo_id'] ?? 0)) ?>"
                                               class="btn btn-sm btn-outline-danger" title="Eliminar"
                                               onclick="return uiConfirmLink(this, '¿Eliminar esta leyenda de cultivo?');"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="config-list-pagination">
                        <span class="text-muted small"></span>
                        <div class="config-list-pagination-nav"></div>
                    </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="border rounded p-3 bg-light">
                        <h6 class="mb-3"><?= $editarId > 0 ? 'Editar leyenda' : 'Nueva leyenda' ?></h6>
                        <?php if ($categoriasActivas === [] && $editarId === 0): ?>
                        <div class="alert alert-warning small mb-0">
                            Debe crear al menos una categoría activa antes de registrar leyendas.
                        </div>
                        <?php else: ?>
                        <?= form_open(site_url('config/saveleyendacultivo'), ['id' => 'form_leyenda_cultivo']) ?>
                        <input type="hidden" name="leyenda_cultivo_id" value="<?= $editarId > 0 ? $editarId : 0 ?>">
                        <div class="mb-3">
                            <label class="form-label" for="leyenda_cultivo_categoria_id">Categoría <span class="text-danger">*</span></label>
                            <select name="leyenda_cultivo_categoria_id" id="leyenda_cultivo_categoria_id" class="form-select form-select-sm" required>
                                <option value="">— Seleccione categoría —</option>
                                <?php foreach ($categorias as $cat):
                                    $cid = (int) ($cat['leyenda_cultivo_categoria_id'] ?? 0);
                                    if ($cid < 1) continue;
                                    $selCat = (int) ($editarData['leyenda_cultivo_categoria_id'] ?? 0) === $cid;
                                ?>
                                <option value="<?= $cid ?>" <?= $selCat ? 'selected' : '' ?>>
                                    <?= esc((string) ($cat['nombre'] ?? '')) ?><?= ((int) ($cat['activo'] ?? 1) === 0) ? ' (inactiva)' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="leyenda_cultivo_titulo">Título del mensaje <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" id="leyenda_cultivo_titulo" class="form-control form-control-sm" required maxlength="255"
                                   value="<?= esc((string) ($editarData['titulo'] ?? '')) ?>"
                                   placeholder="Ej: Interpretación negativa">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="leyenda_cultivo_mensaje">Mensaje</label>
                            <textarea name="mensaje" id="leyenda_cultivo_mensaje" class="form-control"><?= htmlspecialchars($mensajeEdit, ENT_QUOTES, 'UTF-8') ?></textarea>
                            <small class="text-muted">Use la barra de herramientas para formato, listas y saltos de línea.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="leyenda_cultivo_activo">Estado</label>
                            <select name="activo" id="leyenda_cultivo_activo" class="form-select form-select-sm">
                                <option value="1" <?= ((int) ($editarData['activo'] ?? 1) === 1) ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= ((int) ($editarData['activo'] ?? 1) === 0) ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                        <?php if ($editarId > 0): ?>
                        <a href="<?= site_url('config?tab=leyendas_cultivo') ?>" class="btn btn-secondary btn-sm">Cancelar</a>
                        <?php endif; ?>
                        <?= form_close() ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
