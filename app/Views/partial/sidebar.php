<?php
helper('layout');
$current       = $current_module ?? 'home';
$sidebarRight  = !empty($sidebar_right);
$sbBorderClass = $sidebarRight ? 'border-start' : 'border-end';
$offcanvasDir  = $sidebarRight ? 'offcanvas-end' : 'offcanvas-start';
$has_registers = false;
foreach ($allowed_modules ?? [] as $m) {
    if (($m->module_id ?? '') === 'registers') { $has_registers = true; break; }
}
$has_config = false;
$can_view_home = false;
foreach ($allowed_modules ?? [] as $m) {
    $mid = $m->module_id ?? '';
    if ($mid === 'config') { $has_config = true; }
    if ($mid === 'home') { $can_view_home = true; }
}
?>
<div class="sidebar border <?= esc($sbBorderClass) ?> col-lg-2 p-0">
  <div class="offcanvas-lg <?= esc($offcanvasDir) ?>" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header d-lg-none">
      <h5 class="offcanvas-title" id="sidebarMenuLabel"><?= esc($companyName ?? 'Menu') ?></h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body d-md-flex flex-column p-0 pt-lg-3">
      <ul class="nav flex-column">
        <?php if ($can_view_home): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $current === 'home' ? 'active' : '' ?>" href="<?= site_url('home') ?>"<?= $current === 'home' ? ' aria-current="page"' : '' ?>>
            <?= sidebar_module_icon('home') ?>
            <?= lang('Module.module_home') ?>
          </a>
        </li>
        <?php endif; ?>
        <?php foreach ($allowed_modules ?? [] as $module): ?>
        <?php if (in_array(($module->module_id ?? ''), ['home', 'config', 'interpretacion_clinica', 'interpretacion-clinica'], true)) continue; ?>
        <?php $mid = (string) ($module->module_id ?? ''); ?>
        <?php if ($mid === 'registers'): ?>
        <?php $uri = trim(uri_string(), '/'); $isRegistersNuevo = ($uri === 'registers'); ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= in_array($current, ['registers', 'recepcion']) ? 'active' : '' ?>" href="<?= site_url('registers/lista') ?>">
            <?= sidebar_module_icon('registers') ?>
            <?= lang('Module.module_' . $module->module_id) ?>
          </a>
          <ul class="nav flex-column ps-3 pb-1">
            <li class="nav-item"><a class="nav-link py-1 small d-flex align-items-center gap-2 <?= $isRegistersNuevo ? 'active' : '' ?>" href="<?= site_url('registers') ?>"><?= sidebar_module_icon('registers_nuevo', true) ?> Nuevo registro</a></li>
          </ul>
        </li>
        <?php else: ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $current === $module->module_id ? 'active' : '' ?>" href="<?= site_url($module->module_id) ?>">
            <?= sidebar_module_icon($mid) ?>
            <?= lang('Module.module_' . $module->module_id) ?>
          </a>
        </li>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($has_registers): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $current === 'expediente' ? 'active' : '' ?>" href="<?= site_url('expediente') ?>">
            <?= sidebar_module_icon('expediente') ?>
            Historial paciente
          </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Services\TenantSubscriptionService::shouldShowClientPortal()): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= ($current ?? '') === 'tenant_subscription' ? 'active' : '' ?>" href="<?= site_url('tenant-subscription') ?>">
            <?= sidebar_module_icon('tenant_subscription') ?>
            Suscripción y comprobantes
          </a>
        </li>
        <?php endif; ?>
      </ul>
      <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase">
        <span><?= lang('Common.common_administration') ?: 'Administración' ?></span>
      </h6>
      <ul class="nav flex-column mb-auto">
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $current === 'account' ? 'active' : '' ?>" href="<?= site_url('account/password') ?>">
            <?= sidebar_module_icon('account_password') ?>
            Cambiar contraseña
          </a>
        </li>
        <?php if ($has_config): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $current === 'config' ? 'active' : '' ?>" href="<?= site_url('config') ?>">
            <?= sidebar_module_icon('config') ?>
            <?= lang('Module.module_config') ?>
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="<?= site_url('home/logout') ?>">
            <?= sidebar_module_icon('logout') ?>
            Salir
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>
