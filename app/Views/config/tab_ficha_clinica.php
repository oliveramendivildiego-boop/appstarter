<?php
/**
 * @var array<int, array<string, mixed>> $fichas_clinicas
 * @var int $editar_ficha_clinica
 * @var array<string, mixed> $editar_ficha_clinica_data
 * @var array<int, string> $opciones_map
 * @var array<int, array<string, mixed>> $leyendas_cultivo
 * @var array<int, int> $ficha_clinica_pruebas_counts
 * @var array<int, array<string, mixed>> $ficha_clinica_pruebas_catalog
 * @var array<int, array<string, mixed>> $ficha_clinica_pruebas_linked
 */
$fichasClinicas = $fichas_clinicas ?? [];
$editarId = (int) ($editar_ficha_clinica ?? 0);
$editarData = is_array($editar_ficha_clinica_data ?? null) ? $editar_ficha_clinica_data : [];
$opcionesMap = $opciones_map ?? [];
$leyendasCultivo = $leyendas_cultivo ?? [];
$pruebasCounts = $ficha_clinica_pruebas_counts ?? [];
?>
<div class="tab-pane fade <?= ($activeTab ?? '') === 'ficha_clinica' ? 'show active' : '' ?>" id="tab-ficha_clinica" role="tabpanel">
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center gap-2">
            <h5 class="mb-0"><i class="fa-solid fa-file-medical me-2"></i>Fichas clínicas</h5>
            <span class="badge bg-light text-dark"><?= count($fichasClinicas) ?></span>
        </div>
        <div class="card-body">
            <?= view('config/partials/config_section_guide', [
                'guide_key' => 'ficha_clinica',
                'title' => 'Plantillas de ficha clínica',
                'body' => 'Matrices personalizadas reutilizables en recepción o consulta. Puede vincular pruebas de laboratorio a cada ficha.',
                'steps' => [
                    'Cree una ficha con nombre y diseño de matriz.',
                    'Exporte/importe JSON para copiar entre entornos.',
                    'Al editar una ficha, asocie las pruebas que debe incluir.',
                ],
            ]) ?>

            <div class="border rounded p-3 mb-3 bg-light-subtle">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <strong class="small"><i class="fa-solid fa-file-import me-1"></i>Importar ficha clínica</strong>
                    <span class="badge bg-secondary">JSON completo</span>
                </div>
                <p class="text-muted small mb-2">
                    Importa una ficha exportada desde aquí o desde otro sistema. Se creará una <strong>nueva</strong> ficha con nombre y matriz del archivo.
                </p>
                <?= form_open_multipart('config/importfichaclinica', ['class' => 'row g-2 align-items-end', 'id' => 'form_import_ficha_clinica']) ?>
                <div class="col-md-8">
                    <input type="file" name="config_file" class="form-control form-control-sm" accept=".json,application/json" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="fa-solid fa-upload me-1"></i>Importar ficha
                    </button>
                </div>
                <?= form_close() ?>
            </div>

            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="config-paginated-list" data-page-size="10">
                    <?= view('config/partials/config_list_toolbar', ['search_placeholder' => 'Buscar ficha clínica…']) ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th class="text-center" style="width: 90px;">Pruebas</th>
                                    <th class="text-center" style="width: 90px;">Estado</th>
                                    <th class="text-center" style="width: 170px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($fichasClinicas === []): ?>
                                <tr>
                                    <td colspan="4" class="text-muted text-center py-3">No hay fichas clínicas. Cree la primera con el formulario.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($fichasClinicas as $fc):
                                    $fid = (int) ($fc['ficha_clinica_id'] ?? 0);
                                    $numPruebas = (int) ($pruebasCounts[$fid] ?? 0);
                                ?>
                                <tr class="<?= $editarId === $fid ? 'table-primary' : '' ?>">
                                    <td><strong><?= esc((string) ($fc['nombre'] ?? '')) ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark"><?= $numPruebas ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?= ((int) ($fc['activo'] ?? 0) === 1)
                                            ? '<span class="badge bg-success">Activo</span>'
                                            : '<span class="badge bg-secondary">Inactivo</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="<?= site_url('config/exportfichaclinica/' . $fid) ?>"
                                               class="btn btn-sm btn-outline-secondary" title="Exportar JSON" download>
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <a href="<?= site_url('config?tab=ficha_clinica&editar_ficha_clinica=' . $fid) ?>"
                                               class="btn btn-sm btn-outline-primary" title="Editar matriz">
                                                <i class="fa-solid fa-table-cells"></i>
                                            </a>
                                            <a href="<?= site_url('config/deletefichaclinica/' . $fid) ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               title="Eliminar"
                                               onclick="return confirm('¿Eliminar esta ficha clínica?');">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
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

                <div class="col-lg-7">
                    <div class="card border">
                        <div class="card-header py-2">
                            <strong><?= $editarId > 0 ? 'Editar ficha clínica' : 'Nueva ficha clínica' ?></strong>
                        </div>
                        <div class="card-body">
                            <?= form_open('config/savefichaclinica') ?>
                            <input type="hidden" name="ficha_clinica_id" value="<?= $editarId ?>">
                            <div class="mb-3">
                                <label class="form-label" for="fc_nombre">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="fc_nombre" name="nombre" required maxlength="255"
                                       value="<?= esc((string) ($editarData['nombre'] ?? '')) ?>"
                                       placeholder="Ej. Ficha ginecológica, Historia clínica pediátrica">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="fc_activo">Estado</label>
                                <select class="form-select" id="fc_activo" name="activo">
                                    <option value="1" <?= (int) ($editarData['activo'] ?? 1) === 1 ? 'selected' : '' ?>>Activo</option>
                                    <option value="0" <?= (int) ($editarData['activo'] ?? 1) === 0 ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-floppy-disk me-1"></i><?= $editarId > 0 ? 'Guardar cambios' : 'Crear ficha' ?>
                                </button>
                                <?php if ($editarId > 0): ?>
                                <a href="<?= site_url('config?tab=ficha_clinica') ?>" class="btn btn-outline-secondary">Cancelar edición</a>
                                <?php endif; ?>
                            </div>
                            <?= form_close() ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($editarId > 0 && $editarData !== []):
        $matrizConfig = model(\App\Models\FichaClinicaModel::class)->getMatrizConfig($editarId);
        $fichaInfo = (object) [
            'prianacategoria_id' => $editarId,
            'name'               => (string) ($editarData['nombre'] ?? ''),
        ];
    ?>
    <?= view('labotests/partial_detail_personalizado_matriz', [
        'labotests_info'            => $fichaInfo,
        'personalizado_matriz'      => $matrizConfig,
        'opciones'                  => $opcionesMap,
        'leyendas_cultivo'          => $leyendasCultivo,
        'matriz_save_url'           => 'config/savefichaclinicamatriz',
        'matriz_entity_field'       => 'ficha_clinica_id',
        'matriz_entity_id'          => $editarId,
        'matriz_show_export_import' => true,
        'matriz_export_url'         => site_url('config/exportfichaclinicamatriz/' . $editarId),
        'matriz_import_url'         => site_url('config/importfichaclinicamatriz/' . $editarId),
        'matriz_import_confirm_message' => 'Esto reemplazará la matriz actual de esta ficha clínica. ¿Continuar?',
        'matriz_export_import_description' => 'Exporta o importa solo la matriz de esta ficha. También puede importar una matriz exportada desde una prueba tipo Personalizado o Cultivo.',
        'matriz_export_import_badge' => 'Matriz ficha clínica',
        'matriz_context_label'      => 'Matriz de ficha clínica: ' . (string) ($editarData['nombre'] ?? ''),
    ]) ?>

    <?= view('config/partial_ficha_clinica_pruebas', [
        'ficha_clinica_id'   => $editarId,
        'pruebas_catalog'    => $ficha_clinica_pruebas_catalog ?? [],
        'pruebas_linked'     => $ficha_clinica_pruebas_linked ?? [],
    ]) ?>
    <?php endif; ?>
</div>
<script>
(function() {
    var importForm = document.getElementById('form_import_ficha_clinica');
    if (!importForm || importForm.dataset.confirmBound === '1') return;
    importForm.dataset.confirmBound = '1';
    importForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var msg = 'Se creará una nueva ficha clínica con los datos del archivo. ¿Continuar?';
        if (typeof uiConfirm === 'function') {
            uiConfirm(msg, 'Confirmar importación').then(function(ok) {
                if (ok) importForm.submit();
            });
            return;
        }
        if (window.confirm(msg)) {
            importForm.submit();
        }
    });
})();
</script>
