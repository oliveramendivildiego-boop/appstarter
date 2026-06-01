<?php
helper('layout');
$layoutConfig = layout_config();
$companyName = $company_name ?? ($layoutConfig['company'] ?? 'Laboratorio');
$logoPath = $layoutConfig['logo'] ?? 'images/logo-john.png';
$showLogoInHeader = $layoutConfig['show_logo'] ?? false;
$themeColor = $layoutConfig['theme_color'] ?? '#FF7218';
$themeHover = $layoutConfig['theme_hover'] ?? $themeColor;
$themeActive = $layoutConfig['theme_active'] ?? $themeColor;

$moduleCss = [
    'config'      => 'assets/css/config.css',
    'expediente'  => 'assets/css/expediente.css',
    'labotests'   => 'assets/css/labotests.css',
    'registers'   => 'assets/css/registers.css',
    'toquotes'    => 'assets/css/toquotes.css',
];
$currentMod = $current_module ?? $controller_name ?? 'home';
$extraCss = isset($moduleCss[$currentMod]) ? $moduleCss[$currentMod] : null;
?>
<!DOCTYPE html>
<html lang="es" data-theme-primary="<?= esc($themeColor) ?>" data-theme-hover="<?= esc($themeHover) ?>" data-theme-active="<?= esc($themeActive) ?>">
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
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>" />
    <?php if ($extraCss): ?>
    <link rel="stylesheet" href="<?= base_url($extraCss) ?>" />
    <?php endif; ?>
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
    <script src="<?= base_url('js/header-datetime.js') ?>"></script>
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
    <script>
      (function(){var d=document.documentElement;var p=d.getAttribute('data-theme-primary');var h=d.getAttribute('data-theme-hover');var a=d.getAttribute('data-theme-active');if(p)d.style.setProperty('--primary-color',p);if(h)d.style.setProperty('--primary-hover',h);if(a)d.style.setProperty('--primary-active',a);})();
    </script>
    <?php if (!empty($extra_head_links) && is_array($extra_head_links)): ?>
        <?php foreach ($extra_head_links as $link): ?>
            <?= $link . "\n" ?>
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
<div id="toast-container" class="position-fixed top-0 end-0 p-3"></div>

<header class="navbar navbar-dark navbar-theme sticky-top flex-md-nowrap p-0 shadow">
    <button class="navbar-toggler d-md-none ms-2 me-2 collapsed" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6 d-flex align-items-center" href="<?= employee_landing_url() ?>">
        <?php if ($showLogoInHeader): ?>
            <img src="<?= base_url($logoPath) ?>" alt="<?= esc($companyName) ?>" class="me-2 navbar-brand-logo">
        <?php else: ?>
            <?= esc($companyName) ?>
        <?php endif; ?>
    </a>
    <?= view('partial/header_datetime') ?>
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
