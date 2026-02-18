<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= lang('Login.login_login') ?> - Laboratorio John</title>
    <link rel="stylesheet" href="<?= base_url('css/login-modern.css') ?>" />
</head>
<body class="login-modern">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">
                <h1>Laboratorio John</h1>
                <p><?= lang('Login.login_welcome_message') ?></p>
            </div>
            <div class="login-error" role="alert" id="login-error" <?= empty($error) ? ' style="display:none"' : '' ?>>
                <?php if (!empty($error)): ?><strong>⚠ <?= esc($error) ?></strong><?php endif; ?>
            </div>
            <?= form_open(site_url('login'), ['id' => 'login_form', 'class' => 'login-form', 'novalidate' => '']) ?>
                <div class="mb-3">
                    <label for="username" class="form-label"><?= lang('Login.login_username') ?></label>
                    <?= form_input(['name' => 'username', 'id' => 'username', 'class' => 'form-control', 'placeholder' => 'Usuario', 'autocomplete' => 'username']) ?>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label"><?= lang('Login.login_password') ?></label>
                    <?= form_password(['name' => 'password', 'id' => 'password', 'class' => 'form-control', 'placeholder' => '••••••••', 'autocomplete' => 'current-password']) ?>
                </div>
                <button type="submit" name="loginButton" class="login-submit"><?= lang('Login.login_login') ?></button>
            <?= form_close() ?>
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
            </script>
            <p class="login-footer"><a href="http://olivera-solutions.com/" target="_blank">Olivera Solutions</a></p>
        </div>
    </div>
</body>
</html>
