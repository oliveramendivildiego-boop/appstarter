<?php
$appConfig = model(\App\Models\AppConfigModel::class);
$configKeys = $appConfig->getMultiple(['company', 'logo', 'header_brand', 'theme_color']);
$companyName = $company_name ?? ($configKeys['company'] ?? 'Laboratorio');
$logoPath = !empty(trim((string)($configKeys['logo'] ?? ''))) ? trim($configKeys['logo']) : 'images/logo-john.png';
$headerBrand = $configKeys['header_brand'] ?? 'logo';
$showLogoInHeader = ($headerBrand === 'logo') && !empty($logoPath) && file_exists(FCPATH . $logoPath);
$themeColor = '#FF7218';
if (!empty($configKeys['theme_color']) && preg_match('/^#[a-fA-F0-9]{3,6}$/', $configKeys['theme_color'])) {
    $themeColor = $configKeys['theme_color'];
}
helper('config');
$themeHover = function_exists('darken_hex_color') ? darken_hex_color($themeColor, 12) : $themeColor;
$themeActive = function_exists('darken_hex_color') ? darken_hex_color($themeColor, 25) : $themeColor;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <base href="<?= base_url() ?>" />
    <title><?= esc($companyName) ?> - <?= lang('Common.common_powered_by') ?> Olivera Solutions</title>
    <link rel="stylesheet" href="<?= base_url('css/dom_print.css') ?>" media="print"/>
    <link rel="stylesheet" href="<?= base_url('css/vendor/bootstrap.min.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/vendor/fontawesome.min.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/vendor/material-icons.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/vendor/inter-font.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/dom.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/autocomplete.css') ?>" />
    <style>:root{--primary-color:<?= $themeColor ?>;--primary-hover:<?= $themeHover ?>;--primary-active:<?= $themeActive ?>;}</style>
    <script>
      BASE_URL = '<?= site_url() ?>';
      window.CI_CSRF_TOKEN = '<?= csrf_hash() ?>';
      window.CI_CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
    </script>
    <script src="<?= base_url('js/jquery-3.7.1.min.js') ?>"></script>
    <script src="<?= base_url('js/jquery.autocomplete.js') ?>"></script>
    <script src="<?= base_url('js/vendor/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('js/common.js') ?>"></script>
    <script src="<?= base_url('js/manage_tables.js') ?>"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var sb = document.getElementById('sidebarMenu');
        if (sb && window.innerWidth >= 992) sb.classList.add('show');
      });
      window.addEventListener('resize', function() {
        var sb = document.getElementById('sidebarMenu');
        if (!sb) return;
        if (window.innerWidth >= 992) sb.classList.add('show'); else sb.classList.remove('show');
      });
    </script>
    <?php if (!empty($extra_head_links) && is_array($extra_head_links)): ?>
        <?php foreach ($extra_head_links as $link): ?>
            <?= $link . "\n" ?>
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
<div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<header class="navbar navbar-dark navbar-theme sticky-top flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6 d-flex align-items-center" href="<?= site_url('home') ?>">
        <?php if ($showLogoInHeader): ?>
            <img src="<?= base_url($logoPath) ?>" alt="<?= esc($companyName) ?>" class="me-2 navbar-brand-logo">
        <?php else: ?>
            <?= esc($companyName) ?>
        <?php endif; ?>
    </a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-nav d-md-none">
        <div class="nav-item text-nowrap px-3 py-2">
            <span class="text-white-50 small"><?= date('d/m/Y H:i') ?></span>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row flex-nowrap">
        <?= view('partial/sidebar', [
            'allowed_modules' => $allowed_modules ?? [],
            'user_info'      => $user_info ?? null,
            'companyName'    => $companyName,
            'current_module' => $current_module ?? 'home',
        ]) ?>
        <main class="col-12 col-md-9 col-lg-10 px-md-4 pt-4 ms-sm-auto">
            <div class="container-fluid">
