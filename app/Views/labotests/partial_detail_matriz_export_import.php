<?php
/**
 * Exportar / importar configuración de matriz (cultivo o personalizado).
 *
 * @var object $labotests_info
 * @var string $matriz_tipo cultivo|personalizado
 * @var string|null $matriz_export_url
 * @var string|null $matriz_import_url
 * @var string|null $matriz_import_confirm_message
 * @var string|null $matriz_export_import_description
 * @var string|null $matriz_export_import_badge
 */
$priaId = (int) ($labotests_info->prianacategoria_id ?? 0);
$matrizTipo = ($matriz_tipo ?? 'cultivo') === 'personalizado' ? 'personalizado' : 'cultivo';
$esPersonalizado = $matrizTipo === 'personalizado';
$formImportId = 'form_import_matriz_config_' . $matrizTipo . '_' . $priaId;
$exportUrl = $matriz_export_url ?? site_url('labotests/exportdetailconfig/' . $priaId);
$importUrl = $matriz_import_url ?? site_url('labotests/importdetailconfig/' . $priaId);
$importConfirmMessage = $matriz_import_confirm_message
    ?? ($esPersonalizado
        ? 'Esto reemplazará la matriz personalizada actual. ¿Continuar?'
        : 'Esto reemplazará la matriz actual de esta prueba. ¿Continuar?');
$description = $matriz_export_import_description
    ?? ($esPersonalizado
        ? 'Exporta o importa la matriz personalizada (bloques, celdas, estilos de reporte). También puede importar una matriz exportada desde una prueba tipo Cultivo.'
        : 'Exporta o importa la configuración completa de bloques y celdas de la matriz.');
$badgeLabel = $matriz_export_import_badge ?? ($esPersonalizado ? 'Personalizado' : 'Cultivo');
?>
<div class="border rounded p-3 mb-3 bg-light-subtle cultivo-matriz-export-import">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <strong class="small"><i class="fa-solid fa-file-arrow-up me-1"></i>Exportar / Importar matriz</strong>
        <span class="badge bg-secondary"><?= esc($badgeLabel) ?></span>
    </div>
    <p class="text-muted small mb-2"><?= esc($description) ?></p>
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <a href="<?= esc($exportUrl, 'attr') ?>"
               class="btn btn-outline-primary btn-sm w-100"
               download>
                <i class="fa-solid fa-download me-1"></i>Exportar JSON
            </a>
        </div>
        <div class="col-md-8">
            <?= form_open_multipart($importUrl, [
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
        var msg = <?= json_encode($importConfirmMessage, JSON_UNESCAPED_UNICODE) ?>;
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
