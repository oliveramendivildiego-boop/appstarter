<?php
$icons = [
    'home'           => 'fa-house',
    'customers'      => 'fa-hospital-user',
    'doctors'        => 'fa-user-doctor',
    'doctor_commissions' => 'fa-dollar-sign',
    'labotests'      => 'fa-flask-vial',
    'toquotes'       => 'fa-file-invoice-dollar',
    'registers'      => 'fa-book-medical',
    'expediente'     => 'fa-folder-open',
    'reports'        => 'fa-chart-line',
    'controlcalidad' => 'fa-vial-circle-check',
    'reactivos'      => 'fa-bottle-droplet',
    'equipos'        => 'fa-microscope',
    'egresos'        => 'fa-money-bill-transfer',
    'leyendas'       => 'fa-quote-right',
    'auditoria'      => 'fa-clipboard-list',
    'employees'      => 'fa-user-tie',
    'config'         => 'fa-gear',
];
$current       = $current_module ?? 'home';
$sidebarRight  = !empty($sidebar_right);
$sbBorderClass = $sidebarRight ? 'border-start' : 'border-end';
$offcanvasDir  = $sidebarRight ? 'offcanvas-end' : 'offcanvas-start';
$has_registers = false;
foreach ($allowed_modules ?? [] as $m) {
    if (($m->module_id ?? '') === 'registers') { $has_registers = true; break; }
}
$has_config = false;
foreach ($allowed_modules ?? [] as $m) {
    if (($m->module_id ?? '') === 'config') { $has_config = true; break; }
}
?>
<div class="sidebar border <?= esc($sbBorderClass) ?> col-md-3 col-lg-2 p-0">
  <div class="offcanvas-lg <?= esc($offcanvasDir) ?>" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="sidebarMenuLabel"><?= esc($companyName ?? 'Menu') ?></h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body d-md-flex flex-column p-0 pt-lg-3">
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $current === 'home' ? 'active' : '' ?>" href="<?= site_url('home') ?>"<?= $current === 'home' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid fa-house"></i>
            <?= lang('Module.module_home') ?>
          </a>
        </li>
        <?php foreach ($allowed_modules ?? [] as $module): ?>
        <?php if (in_array(($module->module_id ?? ''), ['config', 'interpretacion_clinica', 'interpretacion-clinica'], true)) continue; ?>
        <?php $icon = $icons[$module->module_id] ?? 'fa-circle'; ?>
        <?php if (($module->module_id ?? '') === 'registers'): ?>
        <?php $uri = trim(uri_string(), '/'); $isRegistersNuevo = ($uri === 'registers'); ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= in_array($current, ['registers', 'recepcion']) ? 'active' : '' ?>" href="<?= site_url('registers/lista') ?>">
            <i class="fa-solid <?= $icon ?>"></i>
            <?= lang('Module.module_' . $module->module_id) ?>
          </a>
          <ul class="nav flex-column ps-3 pb-1">
            <li class="nav-item"><a class="nav-link py-1 small d-flex align-items-center gap-2 <?= $isRegistersNuevo ? 'active' : '' ?>" href="<?= site_url('registers') ?>"><i class="fa-solid fa-plus"></i> Nuevo registro</a></li>
          </ul>
        </li>
        <?php else: ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $current === $module->module_id ? 'active' : '' ?>" href="<?= site_url($module->module_id) ?>">
            <i class="fa-solid <?= $icon ?>"></i>
            <?= lang('Module.module_' . $module->module_id) ?>
          </a>
        </li>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($has_registers): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $current === 'expediente' ? 'active' : '' ?>" href="<?= site_url('expediente') ?>">
            <i class="fa-solid fa-folder-open"></i>
            Historial paciente
          </a>
        </li>
        <?php endif; ?>
        <?php if (\App\Services\TenantSubscriptionService::shouldShowClientPortal()): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= ($current ?? '') === 'tenant_subscription' ? 'active' : '' ?>" href="<?= site_url('tenant-subscription') ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i>
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
            <i class="fa-solid fa-key"></i>
            Cambiar contraseña
          </a>
        </li>
        <?php if ($has_config): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $current === 'config' ? 'active' : '' ?>" href="<?= site_url('config') ?>">
            <i class="fa-solid fa-gear"></i>
            <?= lang('Module.module_config') ?>
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="<?= site_url('home/logout') ?>">
            <i class="fa-solid fa-right-from-bracket"></i>
            Salir
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>
