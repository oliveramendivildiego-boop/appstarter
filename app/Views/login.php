<?php
helper('layout');
$layoutConfig = layout_config();
$companyName = $layoutConfig['company'] ?? 'Laboratorio John';
$logoPath = $layoutConfig['logo'] ?? 'images/logo-john.png';
$showLogoInHeader = $layoutConfig['show_logo'] ?? false;
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
            <div class="login-logo">
                <?php if ($showLogoInHeader): ?>
                <img src="<?= base_url($logoPath) ?>" alt="<?= esc($companyName) ?>">
                <?php else: ?>
                <h1><?= esc($companyName) ?></h1>
                <?php endif; ?>
                <p><?= lang('Login.login_welcome_message') ?></p>
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
                <button type="submit" name="loginButton" class="login-submit"><?= lang('Login.login_login') ?></button>
            <?= form_close() ?>
            <?php if (!empty($googleClientId ?? '')): ?>
            <div class="text-center my-3">
                <small class="text-muted"><?= lang('Login.login_or') ?></small>
            </div>
            <div id="google_signin_button" class="d-flex justify-content-center mb-2"></div>
            <p class="text-muted text-center small mb-3"><?= esc(lang('Login.login_google_hint')) ?></p>
            <?php endif; ?>
            <script>
            document.getElementById('login_form').addEventListener('submit', function(e) {
                e.preventDefault();
                var form = this;
                var btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.textContent = 'Conectando...';
                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        window.location.href = data.redirect || '<?= site_url('home') ?>';
                    } else {
                        document.getElementById('login-error').innerHTML = '<strong>⚠ ' + (data.message || '') + '</strong>';
                        document.getElementById('login-error').style.display = 'block';
                        btn.disabled = false;
                        btn.textContent = '<?= lang('Login.login_login') ?>';
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    btn.textContent = '<?= lang('Login.login_login') ?>';
                });
            });

            <?php if (!empty($googleClientId ?? '')): ?>
            window.addEventListener('load', function() {
                if (!window.google || !google.accounts || !google.accounts.id) return;
                google.accounts.id.initialize({
                    client_id: '<?= esc($googleClientId ?? '') ?>',
                    callback: function(response) {
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
                            if (data && data.success) {
                                window.location.href = data.redirect || '<?= site_url('home') ?>';
                                return;
                            }
                            document.getElementById('login-error').innerHTML = '<strong>⚠ ' + ((data && data.message) ? data.message : 'Error al iniciar con Google') + '</strong>';
                            document.getElementById('login-error').style.display = 'block';
                        })
                        .catch(function() {
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
