<?php
helper('layout');
$layoutConfig = layout_config();
$companyName  = (isset($layoutConfig['company']) && $layoutConfig['company'] !== null && $layoutConfig['company'] !== '')
    ? $layoutConfig['company']
    : 'Laboratorio';
$logoPath     = (isset($layoutConfig['logo']) && $layoutConfig['logo'] !== null && $layoutConfig['logo'] !== '')
    ? $layoutConfig['logo']
    : 'images/logo-john.png';
$showLogoInHeader = ! empty($layoutConfig['show_logo']);
$themeColor   = (isset($layoutConfig['theme_color']) && $layoutConfig['theme_color'] !== null && $layoutConfig['theme_color'] !== '')
    ? $layoutConfig['theme_color']
    : '#6366f1';
$themeHover   = (isset($layoutConfig['theme_hover']) && $layoutConfig['theme_hover'] !== null && $layoutConfig['theme_hover'] !== '')
    ? $layoutConfig['theme_hover']
    : '#4f46e5';
$themeActive  = (isset($layoutConfig['theme_active']) && $layoutConfig['theme_active'] !== null && $layoutConfig['theme_active'] !== '')
    ? $layoutConfig['theme_active']
    : $themeHover;
$themeGradientEnd = (isset($layoutConfig['theme_gradient_end']) && $layoutConfig['theme_gradient_end'] !== null && $layoutConfig['theme_gradient_end'] !== '')
    ? $layoutConfig['theme_gradient_end']
    : '#4f46e5';
$uiInline = (string) ($layoutConfig['ui_inline_style'] ?? '');

$pageTitle = $this->renderSection('title');
?>
<!DOCTYPE html>
<html lang="es" data-theme-primary="<?= esc($themeColor) ?>" data-theme-hover="<?= esc($themeHover) ?>" data-theme-active="<?= esc($themeActive) ?>" data-theme-gradient-end="<?= esc($themeGradientEnd) ?>" class="layout-public-resultados"<?= $uiInline !== '' ? ' style="' . htmlspecialchars($uiInline, ENT_COMPAT, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <base href="<?= base_url() ?>" />
    <title><?= trim($pageTitle ?? '') !== '' ? esc($pageTitle) . ' - ' : '' ?><?= esc($companyName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('css/vendor/bootstrap.min.css') ?>" />
    <link rel="stylesheet" href="<?= asset_url('css/vendor/fontawesome.min.css') ?>" />
    <link rel="stylesheet" href="<?= asset_url('css/dom.css') ?>" />
    <link rel="stylesheet" href="<?= asset_url('assets/css/ynex.css') ?>" />
    <link rel="stylesheet" href="<?= asset_url('assets/css/app.css') ?>" />
    <script>(function(){var d=document.documentElement;var p=d.getAttribute('data-theme-primary');var h=d.getAttribute('data-theme-hover');var a=d.getAttribute('data-theme-active');var g=d.getAttribute('data-theme-gradient-end');if(p)d.style.setProperty('--primary-color',p);if(h)d.style.setProperty('--primary-hover',h);if(a)d.style.setProperty('--primary-active',a);if(g)d.style.setProperty('--theme-gradient-end',g);})();</script>
    <?= $this->renderSection('head_extra') ?>
</head>
<body class="ynex-theme">
<?php if (! empty($showLogoInHeader) && $showLogoInHeader): ?>
    <div class="site-watermark" aria-hidden="true">
        <img src="<?= base_url($logoPath) ?>" alt="" />
    </div>
<?php endif; ?>

<header class="ynex-navbar navbar navbar-expand-lg sticky-top">
    <div class="container-fluid">
        <span class="navbar-brand d-flex align-items-center gap-2 mb-0">
            <?php if ($showLogoInHeader): ?>
                <img src="<?= base_url($logoPath) ?>" alt="<?= esc($companyName) ?>" class="navbar-brand-logo">
            <?php else: ?>
                <i class="fa-solid fa-flask-vial text-white"></i>
                <span class="text-white"><?= esc($companyName) ?></span>
            <?php endif; ?>
        </span>
    </div>
</header>

<main class="ynex-main">
    <div class="container-fluid py-4">
        <?= $this->renderSection('content') ?>
    </div>
</main>

<footer class="ynex-footer">
    <div class="container-fluid">
        <span>&copy; <?= date('Y') ?> <?= esc($companyName) ?></span>
    </div>
</footer>

<?= $this->renderSection('scripts') ?>
</body>
</html>
