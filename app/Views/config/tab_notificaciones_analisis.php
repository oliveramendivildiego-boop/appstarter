<?php

/**
 * @var bool   $delivery_notifications_enabled
 * @var string $delivery_notifications_scope
 */
$activeTab = $activeTab ?? 'sistema';
$scope = \App\Services\DeliveryNotificationService::normalizeScope($delivery_notifications_scope ?? 'all');
?>
<div class="tab-pane fade <?= $activeTab === 'notificaciones_analisis' ? 'show active' : '' ?>" id="tab-notificaciones-analisis" role="tabpanel">
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fa-solid fa-bell me-2"></i>Notificaciones de análisis</h5>
        </div>
        <div class="card-body">
            <?= view('config/partials/config_section_guide', [
                'guide_key' => 'notificaciones_analisis',
                'title' => 'Alertas de entrega de resultados',
                'body' => 'Evite olvidar entregar resultados clínicos mediante alertas persistentes cuando los análisis de una recepción estén validados, firmados o finalizados.',
                'steps' => [
                    'Active la funcionalidad con el interruptor principal.',
                    'Elija el alcance: todas las recepciones o solo las que marque en <strong>Recepción</strong>.',
                    'Si elige <strong>Solo algunos</strong>, en <code>/registers</code> active <strong>Notificar entrega</strong> en cada orden que deba generar alertas.',
                ],
            ]) ?>

            <?= form_open(site_url('config/saveDeliveryNotifications'), ['id' => 'delivery_notifications_form']) ?>
            <?= csrf_field() ?>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" role="switch" id="delivery_notifications_enabled"
                       name="<?= \App\Services\DeliveryNotificationService::KEY_ENABLED ?>" value="1"
                       <?= ! empty($delivery_notifications_enabled) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="delivery_notifications_enabled">
                    Habilitar notificaciones de análisis
                </label>
                <small class="text-muted d-block mt-1">Desactivado por defecto. Con la opción apagada el sistema funciona igual que antes.</small>
            </div>

            <fieldset id="delivery_notifications_options" <?= empty($delivery_notifications_enabled) ? 'disabled' : '' ?>>
                <h6 class="border-bottom pb-2">Alcance de la notificación</h6>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="<?= \App\Services\DeliveryNotificationService::KEY_SCOPE ?>"
                               id="delivery_scope_all" value="all" <?= $scope === 'all' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="delivery_scope_all">Todos los registros/recepciones</label>
                        <small class="text-muted d-block ms-4">Todas las órdenes creadas desde que active la función parpadearán hasta confirmar la entrega; cuando haya resultados listos también se registrarán por análisis.</small>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="radio" name="<?= \App\Services\DeliveryNotificationService::KEY_SCOPE ?>"
                               id="delivery_scope_selected" value="selected" <?= $scope === 'selected' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="delivery_scope_selected">Solo algunos registros/recepciones seleccionados</label>
                        <small class="text-muted d-block ms-4">Solo las órdenes con la casilla <strong>Notificar entrega</strong> activada en Recepción (<code>/registers</code>).</small>
                    </div>
                </div>
            </fieldset>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
                </button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var enabled = document.getElementById('delivery_notifications_enabled');
    var options = document.getElementById('delivery_notifications_options');
    if (enabled && options) {
        enabled.addEventListener('change', function () {
            options.disabled = !enabled.checked;
        });
    }
});
</script>
