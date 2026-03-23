<?php
helper('layout');
$layoutConfig = layout_config();
$companyName = (isset($layoutConfig['company']) && $layoutConfig['company'] !== null && $layoutConfig['company'] !== '')
    ? $layoutConfig['company']
    : 'Laboratorio John';
$logoPath = (isset($layoutConfig['logo']) && $layoutConfig['logo'] !== null && $layoutConfig['logo'] !== '')
    ? $layoutConfig['logo']
    : 'images/logo-john.png';
$showLogoInHeader = !empty($layoutConfig['show_logo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= lang('Login.login_login') ?> - <?= esc($companyName) ?></title>
    <link rel="stylesheet" href="<?= base_url('css/login-modern.css') ?>" />
    <?php if (!empty($googleClientId ?? '')): ?>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <?php endif; ?>
</head>
<body class="login-modern">
    <div class="login-wrapper">
        <div class="login-card">
            <?php if (!empty($showLogoInHeader)): ?>
                <div class="site-watermark" aria-hidden="true">
                    <img src="<?= base_url($logoPath) ?>" alt="" />
                </div>
                <style>
                    .login-card { position: relative; overflow: hidden; }
                    .login-card .site-watermark{
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        z-index: 0;
                        pointer-events: none;
                        opacity: 0.06;
                        width: 100%;
                        text-align: center;
                    }
                    .login-card .site-watermark img{
                        width: min(520px, 72%);
                        height: auto;
                        display: inline-block;
                    }
                    .login-card > *:not(.site-watermark){
                        position: relative;
                        z-index: 1;
                    }
                </style>
            <?php endif; ?>
            <div class="login-logo">
                <?php if ($showLogoInHeader): ?>
                <img src="<?= base_url($logoPath) ?>" alt="<?= esc($companyName) ?>">
                <?php else: ?>
                <h1><?= esc($companyName) ?></h1>
                <?php endif; ?>
                <p><?= lang('Login.login_welcome_message') ?></p>
            </div>
            <div id="login-loading-overlay" class="login-loading-overlay" aria-hidden="true">
                <div class="login-spinner"></div>
                <div class="login-loading-text">Procesando...</div>
            </div>
            <div class="login-error" role="alert" id="login-error" <?= empty($error) ? ' style="display:none"' : '' ?>>
                <?php if (!empty($error)): ?><strong>⚠ <?= esc($error) ?></strong><?php endif; ?>
            </div>
            <?= form_open(site_url('login'), ['id' => 'login_form', 'class' => 'login-form', 'novalidate' => '']) ?>
                <div class="mb-3">
                    <label for="username" class="form-label"><?= lang('Login.login_username') ?></label>
                    <?= form_input(['name' => 'username', 'id' => 'username', 'class' => 'form-control', 'placeholder' => 'Usuario o correo electrónico', 'autocomplete' => 'username']) ?>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label"><?= lang('Login.login_password') ?></label>
                    <?= form_password(['name' => 'password', 'id' => 'password', 'class' => 'form-control', 'placeholder' => '••••••••', 'autocomplete' => 'current-password']) ?>
                </div>
                <button type="submit" name="loginButton" class="login-submit mb-3"><?= lang('Login.login_login') ?></button>
            <?= form_close() ?>
            <?php if (!empty($googleClientId ?? '')): ?>
            <div id="google_signin_button" class="d-flex justify-content-center mb-2"></div>
            <?php
                $googleHint = trim((string) lang('Login.login_google_hint'));
            ?>
            <?php if ($googleHint !== ''): ?>
                <p class="text-muted text-center small mb-3"><?= esc($googleHint) ?></p>
            <?php endif; ?>
            <?php endif; ?>
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

            <?php if (!empty($googleClientId ?? '')): ?>
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
                google.accounts.id.renderButton(
                    document.getElementById('google_signin_button'),
                    { theme: 'outline', size: 'large', text: 'signin_with', shape: 'pill' }
                );
            });
            <?php endif; ?>
            </script>
            <p class="login-footer"><a href="https://oliverasolutions.com/" target="_blank">Olivera Solutions</a></p>
        </div>
    </div>
</body>
</html>
