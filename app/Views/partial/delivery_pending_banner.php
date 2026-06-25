<?php
/**
 * Banner de notificaciones de entrega (barra superior).
 *
 * @var int $delivery_pending_count
 */
$count = (int) ($delivery_pending_count ?? 0);
if ($count < 1) {
    return;
}
helper('registro');
$label = delivery_notification_count_label($count);
?>
<a href="<?= site_url('registers/deliveryNotifications') ?>"
   class="delivery-pending-alert alert alert-warning fade show rounded-0 mb-0 border-0 text-center small text-decoration-none d-block py-2"
   role="alert">
    <span class="fw-semibold"><i class="fa-solid fa-bell me-1"></i> Tiene <?= esc($label) ?></span>
    <span class="d-none d-md-inline"> — haga clic para ver el listado</span>
</a>
