<?php
helper('layout');
$layoutConfig = layout_config();
$companyName = (isset($layoutConfig['company']) && $layoutConfig['company'] !== null && $layoutConfig['company'] !== '')
    ? $layoutConfig['company']
    : 'Laboratorio';
$logoPath = (isset($layoutConfig['logo']) && $layoutConfig['logo'] !== null && $layoutConfig['logo'] !== '')
    ? $layoutConfig['logo']
    : 'images/logo-john.png';
$showLogoInHeader = !empty($layoutConfig['show_logo']);
$themeColor = (isset($layoutConfig['theme_color']) && $layoutConfig['theme_color'] !== null && $layoutConfig['theme_color'] !== '')
    ? $layoutConfig['theme_color']
    : '#6366f1';
$themeHover = (isset($layoutConfig['theme_hover']) && $layoutConfig['theme_hover'] !== null && $layoutConfig['theme_hover'] !== '')
    ? $layoutConfig['theme_hover']
    : '#4f46e5';
$themeActive = (isset($layoutConfig['theme_active']) && $layoutConfig['theme_active'] !== null && $layoutConfig['theme_active'] !== '')
    ? $layoutConfig['theme_active']
    : $themeHover;
$themeGradientEnd = (isset($layoutConfig['theme_gradient_end']) && $layoutConfig['theme_gradient_end'] !== null && $layoutConfig['theme_gradient_end'] !== '')
    ? $layoutConfig['theme_gradient_end']
    : '#4f46e5';
$uiInline = (string) ($layoutConfig['ui_inline_style'] ?? '');

$pageTitle = $this->renderSection('title');
?>
<!DOCTYPE html>
<html lang="es" data-theme-primary="<?= esc($themeColor) ?>" data-theme-hover="<?= esc($themeHover) ?>" data-theme-active="<?= esc($themeActive) ?>" data-theme-gradient-end="<?= esc($themeGradientEnd) ?>" class="layout-doctor"<?= $uiInline !== '' ? ' style="' . htmlspecialchars($uiInline, ENT_COMPAT, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <base href="<?= base_url() ?>" />
    <title><?= trim($pageTitle ?? '') !== '' ? esc($pageTitle) . ' - ' : '' ?><?= esc($companyName) ?> - Portal Doctor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/vendor/bootstrap.min.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/vendor/fontawesome.min.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/dom.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/ynex.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>" />
    <script>
      BASE_URL = '<?= site_url() ?>';
      window.CI_CSRF_TOKEN = '<?= csrf_hash() ?>';
      window.CI_CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
    </script>
    <script src="<?= base_url('js/jquery-3.7.1.min.js') ?>"></script>
    <script src="<?= base_url('js/vendor/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('js/common.js') ?>"></script>
    <script>(function(){var d=document.documentElement;var p=d.getAttribute('data-theme-primary');var h=d.getAttribute('data-theme-hover');var a=d.getAttribute('data-theme-active');var g=d.getAttribute('data-theme-gradient-end');if(p)d.style.setProperty('--primary-color',p);if(h)d.style.setProperty('--primary-hover',h);if(a)d.style.setProperty('--primary-active',a);if(g)d.style.setProperty('--theme-gradient-end',g);})();</script>
    <?= $this->renderSection('head_extra') ?>
</head>
<body class="ynex-theme doctor-portal">
<div id="toast-container" class="position-fixed top-0 end-0 p-3"></div>

<?php if (!empty($showLogoInHeader) && $showLogoInHeader): ?>
    <div class="site-watermark" aria-hidden="true">
        <img src="<?= base_url($logoPath) ?>" alt="" />
    </div>
<?php endif; ?>

<header class="ynex-navbar navbar navbar-expand-lg sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= site_url('doctor/home') ?>">
            <?php if ($showLogoInHeader): ?>
                <img src="<?= base_url($logoPath) ?>" alt="<?= esc($companyName) ?>" class="navbar-brand-logo">
            <?php else: ?>
                <i class="fa-solid fa-flask-vial text-white"></i>
                <span><?= esc($companyName) ?></span>
            <?php endif; ?>
        </a>
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#doctorNav" aria-controls="doctorNav" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="doctorNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center gap-2" href="<?= site_url('doctor/home') ?>">
                        <i class="fa-solid fa-house"></i> Mi panel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center gap-2" href="<?= site_url('doctor/logout') ?>">
                        <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

<main class="ynex-main">
    <div class="container-fluid py-4">
        <?= $this->renderSection('content') ?>
    </div>
</main>

<footer class="ynex-footer">
    <div class="container-fluid">
        <span>&copy; <?= date('Y') ?> <?= esc($companyName) ?> - Portal Doctor</span>
        <span class="ms-2">| Desarrollado por <a href="https://oliverasolutions.com/" target="_blank" rel="noopener">Olivera Solutions</a></span>
    </div>
</footer>

<?= $this->renderSection('scripts') ?>
</body>
</html>
