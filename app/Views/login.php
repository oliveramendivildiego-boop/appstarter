<?php
helper(['layout', 'login']);
$layoutConfig = layout_config();
$companyName = (isset($layoutConfig['company']) && $layoutConfig['company'] !== null && $layoutConfig['company'] !== '')
    ? $layoutConfig['company']
    : 'Laboratorio John';
$logoPath = (isset($layoutConfig['logo']) && $layoutConfig['logo'] !== null && $layoutConfig['logo'] !== '')
    ? $layoutConfig['logo']
    : 'images/logo-john.png';
$showLogoInHeader = !empty($layoutConfig['show_logo']);
$hasGoogleLogin = !empty($googleClientId ?? '');
$featureHighlights = login_feature_highlights();
$heroBackgrounds   = login_hero_backgrounds();

$themeColor    = preg_match('/^#[a-fA-F0-9]{3,6}$/', (string) ($layoutConfig['theme_color'] ?? ''))
    ? $layoutConfig['theme_color']
    : '#0d9488';
$themeHover    = preg_match('/^#[a-fA-F0-9]{3,6}$/', (string) ($layoutConfig['theme_hover'] ?? ''))
    ? $layoutConfig['theme_hover']
    : $themeColor;
$gradientEnd   = preg_match('/^#[a-fA-F0-9]{3,6}$/', (string) ($layoutConfig['theme_gradient_end'] ?? ''))
    ? $layoutConfig['theme_gradient_end']
    : '#0284c7';
$matrixRainColor = login_matrix_rain_color($themeColor, $gradientEnd);
$heroPitch     = esc($companyName) . ' le permite gestionar recepción, resultados, inventario, calidad y reportes desde un solo lugar.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= lang('Login.login_login') ?> - <?= esc($companyName) ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/login-modern.css') ?>" />
    <style>
        :root {
            --login-primary: <?= esc($themeColor) ?>;
            --login-primary-hover: <?= esc($themeHover) ?>;
            --login-gradient-end: <?= esc($gradientEnd) ?>;
            --login-primary-soft: color-mix(in srgb, <?= esc($themeColor) ?> 14%, transparent);
            --login-accent: <?= esc($gradientEnd) ?>;
            --login-matrix-color: <?= esc($matrixRainColor) ?>;
            --login-hero-accent: linear-gradient(135deg, <?= esc($themeColor) ?> 0%, <?= esc($gradientEnd) ?> 100%);
        }
    </style>
    <?php if ($hasGoogleLogin): ?>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <?php endif; ?>
</head>
<body class="login-modern">
    <div class="login-layout">
        <aside class="login-hero" aria-label="Información del laboratorio">
            <?php if ($heroBackgrounds !== []): ?>
            <div class="login-hero-scene" aria-hidden="true">
                <div class="login-hero-photos">
                    <?php foreach ($heroBackgrounds as $i => $bgPath): ?>
                    <div class="login-hero-photo" style="--login-photo-i: <?= (int) $i ?>; background-image: url('<?= esc(base_url($bgPath), 'attr') ?>')"></div>
                    <?php endforeach; ?>
                </div>
                <div class="login-hero-gradient"></div>
                <div class="login-hero-scrim"></div>
            </div>
            <?php else: ?>
            <div class="login-hero-scene login-hero-scene--gradient-only" aria-hidden="true">
                <div class="login-hero-gradient"></div>
            </div>
            <?php endif; ?>

            <div class="login-hero-inner">
                <?php if ($showLogoInHeader): ?>
                <div class="login-logo-plate">
                    <div class="login-logo-stack">
                        <img class="login-hero-logo" src="<?= base_url($logoPath) ?>" alt="<?= esc($companyName) ?>">
                        <div class="login-hero-reflection" aria-hidden="true">
                            <img class="login-hero-logo login-hero-logo--mirror" src="<?= base_url($logoPath) ?>" alt="">
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <h1 class="login-hero-title"><?= esc($companyName) ?></h1>
                <?php endif; ?>

                <p class="login-hero-pitch"><?= $heroPitch ?></p>

                <ul class="login-features">
                    <?php foreach ($featureHighlights as $feature): ?>
                    <li class="login-feature">
                        <span class="login-feature-mark" aria-hidden="true"></span>
                        <div class="login-feature-body">
                            <strong><?= esc($feature['title']) ?></strong>
                            <span><?= esc($feature['text']) ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <main class="login-form-side">
            <canvas id="login_matrix_canvas" class="login-matrix-canvas" aria-hidden="true"></canvas>
            <div class="login-matrix-vignette" aria-hidden="true"></div>

            <div id="login-loading-overlay" class="login-loading-overlay" aria-hidden="true">
                <div class="login-spinner"></div>
                <div class="login-loading-text">Procesando...</div>
            </div>

            <div class="login-form-card">
                <header class="login-form-head">
                    <h2><?= lang('Login.login_login') ?></h2>
                    <p><?= lang('Login.login_welcome_message') ?></p>
                </header>

                <div class="login-error" role="alert" id="login-error" <?= empty($error) ? ' style="display:none"' : '' ?>>
                    <?php if (!empty($error)): ?><strong>⚠ <?= esc($error) ?></strong><?php endif; ?>
                </div>

                <?= form_open(site_url('login'), ['id' => 'login_form', 'class' => 'login-form', 'novalidate' => '']) ?>
                    <div class="mb-3">
                        <label for="username" class="form-label"><?= lang('Login.login_username') ?></label>
                        <div class="login-input-wrap">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <?= form_input(['name' => 'username', 'id' => 'username', 'class' => 'form-control login-input', 'placeholder' => 'Usuario o correo electrónico', 'autocomplete' => 'username']) ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= lang('Login.login_password') ?></label>
                        <div class="login-input-wrap">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <?= form_password(['name' => 'password', 'id' => 'password', 'class' => 'form-control login-input', 'placeholder' => '••••••••', 'autocomplete' => 'current-password']) ?>
                        </div>
                    </div>
                    <button type="submit" name="loginButton" class="login-submit"><?= lang('Login.login_login') ?></button>
                <?= form_close() ?>

                <?php if ($hasGoogleLogin): ?>
                <div class="login-divider" role="separator"><span><?= lang('Login.login_or') ?></span></div>
                <div id="google_signin_button" class="login-google-wrap"></div>
                <?php
                    $googleHint = trim((string) lang('Login.login_google_hint'));
                ?>
                <?php if ($googleHint !== ''): ?>
                <p class="google-hint"><?= esc($googleHint) ?></p>
                <?php endif; ?>
                <?php endif; ?>

                <p class="login-secure-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Acceso seguro para personal autorizado del laboratorio.
                </p>

                <p class="login-footer"><a href="https://oliverasolutions.com/" target="_blank" rel="noopener noreferrer">Olivera Solutions</a></p>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('login_form').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var btn = form.querySelector('button[type="submit"]');
        var overlay = document.getElementById('login-loading-overlay');
        var originalBtnText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Conectando...';
        if (overlay) overlay.classList.add('is-active');
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (overlay) overlay.classList.remove('is-active');
            if (data.success) {
                window.location.href = data.redirect || '<?= site_url('home') ?>';
            } else {
                document.getElementById('login-error').innerHTML = '<strong>⚠ ' + (data.message || '') + '</strong>';
                document.getElementById('login-error').style.display = 'block';
                btn.disabled = false;
                btn.textContent = originalBtnText;
            }
        })
        .catch(function() {
            if (overlay) overlay.classList.remove('is-active');
            btn.disabled = false;
            btn.textContent = originalBtnText;
        });
    });

    <?php if ($hasGoogleLogin): ?>
    window.addEventListener('load', function() {
        if (!window.google || !google.accounts || !google.accounts.id) return;
        google.accounts.id.initialize({
            client_id: '<?= esc($googleClientId ?? '') ?>',
            callback: function(response) {
                var overlay = document.getElementById('login-loading-overlay');
                if (overlay) overlay.classList.add('is-active');
                fetch('<?= site_url('login/google_login') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'id_token=' + encodeURIComponent(response.credential || '')
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (overlay) overlay.classList.remove('is-active');
                    if (data && data.success) {
                        window.location.href = data.redirect || '<?= site_url('home') ?>';
                        return;
                    }
                    document.getElementById('login-error').innerHTML = '<strong>⚠ ' + ((data && data.message) ? data.message : 'Error al iniciar con Google') + '</strong>';
                    document.getElementById('login-error').style.display = 'block';
                })
                .catch(function() {
                    var overlay = document.getElementById('login-loading-overlay');
                    if (overlay) overlay.classList.remove('is-active');
                    document.getElementById('login-error').innerHTML = '<strong>⚠ Error al iniciar con Google</strong>';
                    document.getElementById('login-error').style.display = 'block';
                });
            }
        });
        var googleWrap = document.getElementById('google_signin_button');
        var googleWidth = Math.min(400, Math.max(220, googleWrap ? googleWrap.clientWidth : 400));
        google.accounts.id.renderButton(
            googleWrap,
            { theme: 'outline', size: 'large', text: 'signin_with', shape: 'pill', width: googleWidth }
        );
    });
    <?php endif; ?>
    </script>
    <script src="<?= asset_url('js/login-matrix.js') ?>"></script>
</body>
</html>
