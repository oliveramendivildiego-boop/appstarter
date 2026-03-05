<?php
helper('layout');
$layoutConfig = layout_config();
$companyName = $layoutConfig['company'] ?? 'Laboratorio';
$logoPath = $layoutConfig['logo'] ?? 'images/logo-john.png';
$showLogoInHeader = $layoutConfig['show_logo'] ?? false;
$themeColor = $layoutConfig['theme_color'] ?? '#FF7218';
$themeHover = $layoutConfig['theme_hover'] ?? $themeColor;
$themeActive = $layoutConfig['theme_active'] ?? $themeColor;
?>
<!DOCTYPE html>
<html lang="es" data-theme-primary="<?= esc($themeColor) ?>" data-theme-hover="<?= esc($themeHover) ?>" data-theme-active="<?= esc($themeActive) ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <base href="<?= base_url() ?>" />
    <title><?= esc($companyName) ?> - Portal Doctor</title>
    <link rel="stylesheet" href="<?= base_url('css/vendor/bootstrap.min.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/vendor/fontawesome.min.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/dom.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>" />
    <script>
      BASE_URL = '<?= site_url() ?>';
      window.CI_CSRF_TOKEN = '<?= csrf_hash() ?>';
      window.CI_CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
    </script>
    <script src="<?= base_url('js/jquery-3.7.1.min.js') ?>"></script>
    <script src="<?= base_url('js/vendor/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('js/common.js') ?>"></script>
    <script>(function(){var d=document.documentElement;var p=d.getAttribute('data-theme-primary');if(p)d.style.setProperty('--primary-color',p);var h=d.getAttribute('data-theme-hover');if(h)d.style.setProperty('--primary-hover',h);})();</script>
    <?php if (!empty($extra_head_links) && is_array($extra_head_links)): ?>
        <?php foreach ($extra_head_links as $link): ?><?= $link . "\n" ?><?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
<div id="toast-container" class="position-fixed top-0 end-0 p-3"></div>
<header class="navbar navbar-dark navbar-theme sticky-top flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6 d-flex align-items-center" href="<?= site_url('doctor/home') ?>">
        <?php if ($showLogoInHeader): ?>
        <img src="<?= base_url($logoPath) ?>" alt="<?= esc($companyName) ?>" class="me-2 navbar-brand-logo">
        <?php else: ?>
        <?= esc($companyName) ?>
        <?php endif; ?>
    </a>
    <div class="navbar-nav ms-auto px-3">
        <a class="nav-link text-white" href="<?= site_url('doctor/logout') ?>"><i class="fa-solid fa-right-from-bracket me-1"></i> Cerrar sesión</a>
    </div>
</header>
<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4 pt-4">
            <div class="container-fluid mx-auto doctor-portal-main">
