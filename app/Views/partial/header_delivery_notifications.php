<?php
/**
 * Enlace de notificaciones de entrega en la barra superior.
 *
 * @var int $delivery_pending_count
 * @var bool $delivery_notifications_enabled
 */
$enabled = ! empty($delivery_notifications_enabled);
if (! $enabled) {
    return;
}
helper('registro');
$count = max(0, (int) ($delivery_pending_count ?? 0));
$isActive = ($count > 0);
$label = delivery_notification_count_label($count);
$href = $isActive
    ? site_url('registers/deliveryNotifications')
    : site_url('reports/notificacionesEntrega');
?>
<a href="<?= $href ?>"
   class="header-delivery-notifications text-decoration-none d-flex align-items-center gap-2 px-2 px-md-3 py-2 flex-shrink-0<?= $isActive ? ' header-delivery-notifications--active' : '' ?>"
   title="<?= esc($label) ?>">
    <span class="header-delivery-notifications__icon position-relative d-inline-flex">
        <i class="fa-solid fa-bell"></i>
        <?php if ($isActive): ?>
        <span class="header-delivery-notifications__dot" aria-hidden="true"></span>
        <?php endif; ?>
    </span>
    <span class="header-delivery-notifications__label"><?= esc($label) ?></span>
</a>
