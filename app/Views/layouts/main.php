<?php
helper('layout');
$layoutConfig = layout_config();
$companyName = (isset($company_name) && $company_name !== null && $company_name !== '')
    ? $company_name
    : ((isset($layoutConfig['company']) && $layoutConfig['company'] !== null && $layoutConfig['company'] !== '')
        ? $layoutConfig['company']
        : 'Laboratorio');

$logoPath = (isset($layoutConfig['logo']) && $layoutConfig['logo'] !== null && $layoutConfig['logo'] !== '')
    ? $layoutConfig['logo']
    : 'images/logo-john.png';

$showLogoInHeader = !empty($layoutConfig['show_logo']);

$themeColor = (isset($layoutConfig['theme_color']) && $layoutConfig['theme_color'] !== null && $layoutConfig['theme_color'] !== '')
    ? $layoutConfig['theme_color']
    : '#FF7218';
$themeHover = (isset($layoutConfig['theme_hover']) && $layoutConfig['theme_hover'] !== null && $layoutConfig['theme_hover'] !== '')
    ? $layoutConfig['theme_hover']
    : $themeColor;
$themeActive = (isset($layoutConfig['theme_active']) && $layoutConfig['theme_active'] !== null && $layoutConfig['theme_active'] !== '')
    ? $layoutConfig['theme_active']
    : $themeColor;

$sidebarRight = !empty($layoutConfig['sidebar_right']);
$hideSidebar  = !empty($hide_sidebar);
$uiGoogleFont = $layoutConfig['ui_google_font_href'] ?? null;
$uiUseInter   = !empty($layoutConfig['ui_use_inter_css']);
$uiInline     = (string) ($layoutConfig['ui_inline_style'] ?? '');

$moduleCss = [
    'config'      => 'assets/css/config.css',
    'expediente'  => 'assets/css/expediente.css',
    'labotests'   => 'assets/css/labotests.css',
    'registers'   => 'assets/css/registers.css',
    'toquotes'    => 'assets/css/toquotes.css',
];
$currentMod = (isset($current_module) && $current_module)
    ? $current_module
    : ((isset($controller_name) && $controller_name)
        ? $controller_name
        : ((isset($module_id) && $module_id)
            ? $module_id
            : 'home'));
$extraCss = isset($moduleCss[$currentMod]) ? $moduleCss[$currentMod] : null;
$pageTitle = $this->renderSection('title');
?>
<!DOCTYPE html>
<html lang="es" data-theme-primary="<?= esc($themeColor) ?>" data-theme-hover="<?= esc($themeHover) ?>" data-theme-active="<?= esc($themeActive) ?>"<?= $uiInline !== '' ? ' style="' . htmlspecialchars($uiInline, ENT_COMPAT, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <base href="<?= base_url() ?>" />
    <title><?= trim($pageTitle ?? '') !== '' ? esc($pageTitle) . ' - ' : '' ?><?= esc($companyName) ?> - <?= lang('Common.common_powered_by') ?> Olivera Solutions</title>
    <link rel="stylesheet" href="<?= base_url('css/dom_print.css') ?>" media="print"/>
    <link rel="stylesheet" href="<?= base_url('css/vendor/bootstrap.min.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/vendor/fontawesome.min.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/vendor/material-icons.css') ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if ($uiGoogleFont): ?>
    <link href="<?= esc($uiGoogleFont) ?>" rel="stylesheet">
    <?php endif; ?>
    <?php if ($uiUseInter): ?>
    <link rel="stylesheet" href="<?= base_url('css/vendor/inter-font.css') ?>" />
    <?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('css/dom.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/ynex.css') ?>" />
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
      window.SESSION_MONITOR_DISABLED = <?= filter_var((string) env('session.monitorDisabled', 'false'), FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false' ?>;
      window.APP_CURRENCY_SYMBOL = <?= json_encode($layoutConfig['currency_symbol'] ?? '$', JSON_UNESCAPED_UNICODE) ?>;
      window.APP_CURRENCY_IS_RIGHT = <?= json_encode(strtolower(trim((string) ($layoutConfig['currency_side'] ?? 'left'))) === 'right') ?>;
    </script>
    <script src="<?= base_url('js/jquery-3.7.1.min.js') ?>"></script>
    <script src="<?= base_url('js/jquery.autocomplete.js') ?>"></script>
    <script src="<?= base_url('js/vendor/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('js/common.js') ?>"></script>
    <script src="<?= base_url('js/validation-common.js') ?>"></script>
    <script src="<?= base_url('js/manage_tables.js') ?>"></script>
    <script src="<?= base_url('js/session-monitor.js') ?>"></script>
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
    <?php echo $this->renderSection('head_extra'); ?>
</head>
<body class="ynex-theme">
<?= view('partial/ghost_tenant_banner') ?>
<?php
helper('url');
$subAlert = \App\Services\TenantSubscriptionService::alertForCurrentSession();
$subBlockedUri = trim((string) uri_string(), '/');
$hideSubAlertOnBlockedPage = $subBlockedUri === 'subscription-blocked'
    || str_starts_with($subBlockedUri, 'subscription-blocked/');
if ($subAlert !== null && ! $hideSubAlertOnBlockedPage):
    $subAlertClass = ($subAlert['type'] ?? '') === 'danger' ? 'danger' : 'warning';
?>
<div class="alert alert-<?= esc($subAlertClass) ?> <?= ($subAlertClass === 'danger') ? '' : 'alert-dismissible' ?> fade show rounded-0 mb-0 border-0 text-center small" role="alert">
    <?= esc($subAlert['message'] ?? '') ?>
    <?php if ($subAlertClass !== 'danger'): ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    <?php endif; ?>
</div>
<?php endif; ?>
<div id="toast-container" class="position-fixed top-0 end-0 p-3"></div>

<!-- Modal global (mensajes/confirmaciones) -->
<div class="modal fade" id="globalModalMensaje" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="globalModalMensajeTitulo">Mensaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="globalModalMensajeTexto"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="globalModalConfirmacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="globalModalConfirmacionTitulo">Confirmar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="globalModalConfirmacionTexto"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="globalModalConfirmacionCancelar">Cancelar</button>
                <button type="button" class="btn btn-primary" id="globalModalConfirmacionAceptar">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($showLogoInHeader)): ?>
    <?php if ($showLogoInHeader): ?>
        <div class="site-watermark" aria-hidden="true">
            <img src="<?= base_url($logoPath) ?>" alt="" />
        </div>
    <?php endif; ?>
<?php endif; ?>

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
    <div class="row flex-nowrap<?= $sidebarRight ? ' flex-row-reverse' : '' ?>">
        <?php if (! $hideSidebar): ?>
        <?= view('partial/sidebar', [
            'allowed_modules' => $allowed_modules ?? [],
            'user_info'      => $user_info ?? null,
            'companyName'    => $companyName,
            'current_module' => $current_module ?? 'home',
            'sidebar_right'  => $sidebarRight,
        ]) ?>
        <?php endif; ?>
        <main class="<?= $hideSidebar ? 'col-12' : 'col-12 col-md-9 col-lg-10' ?> px-md-4 pt-4<?= $hideSidebar ? '' : ($sidebarRight ? '' : ' ms-sm-auto') ?>">
            <div class="container-fluid">
                <?= $this->renderSection('content') ?>
            </div>
        </main>
    </div>
</div>

<footer id="footer" class="ynex-footer mt-4">
    <div class="container-fluid">
        <span>&copy; <?= date('Y') ?></span>
        <span class="ms-2">Desarrollado por <a href="https://oliverasolutions.com/" target="_blank" rel="noopener">Olivera Solutions</a></span>
    </div>
</footer>

<?php echo $this->renderSection('scripts'); ?>
</body>
</html>
