<?php
/**
 * Exportar / importar configuración de matriz (cultivo o personalizado).
 *
 * @var object $labotests_info
 * @var string $matriz_tipo cultivo|personalizado
 */
$priaId = (int) ($labotests_info->prianacategoria_id ?? 0);
$matrizTipo = ($matriz_tipo ?? 'cultivo') === 'personalizado' ? 'personalizado' : 'cultivo';
$esPersonalizado = $matrizTipo === 'personalizado';
$formImportId = 'form_import_matriz_config_' . $matrizTipo;
?>
<div class="border rounded p-3 mb-3 bg-light-subtle cultivo-matriz-export-import">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <strong class="small"><i class="fa-solid fa-file-arrow-up me-1"></i>Exportar / Importar matriz</strong>
        <span class="badge bg-secondary"><?= $esPersonalizado ? 'Personalizado' : 'Cultivo' ?></span>
    </div>
    <p class="text-muted small mb-2">
        <?= $esPersonalizado
            ? 'Exporta o importa la matriz personalizada (bloques, celdas, estilos de reporte). También puede importar una matriz exportada desde una prueba tipo Cultivo.'
            : 'Exporta o importa la configuración completa de bloques y celdas de la matriz.' ?>
    </p>
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <a href="<?= site_url('labotests/exportdetailconfig/' . $priaId) ?>"
               class="btn btn-outline-primary btn-sm w-100"
               download>
                <i class="fa-solid fa-download me-1"></i>Exportar JSON
            </a>
        </div>
        <div class="col-md-8">
            <?= form_open_multipart('labotests/importdetailconfig/' . $priaId, [
                'class' => 'row g-2 cultivo-matriz-import-form',
                'id'    => $formImportId,
            ]) ?>
            <div class="col-md-8">
                <input type="file" name="config_file" class="form-control form-control-sm" accept=".json,application/json" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-success btn-sm w-100">
                    <i class="fa-solid fa-upload me-1"></i>Importar JSON
                </button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>
<script>
(function() {
    var importForm = document.getElementById(<?= json_encode($formImportId) ?>);
    if (!importForm || importForm.dataset.confirmBound === '1') return;
    importForm.dataset.confirmBound = '1';
    importForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var msg = 'Esto reemplazará la matriz actual de esta prueba. ¿Continuar?';
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
