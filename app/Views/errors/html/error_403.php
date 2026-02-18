<!DOCTYPE html>
<html>
<head>
    <title>Acceso denegado</title>
    <link rel="stylesheet" href="<?= base_url('css/vendor/bootstrap.min.css') ?>">
</head>
<body class="p-5">
    <div class="container">
        <h1>Acceso denegado</h1>
        <p><?= esc($message ?? 'No tienes permiso para acceder.') ?></p>
        <a href="<?= site_url('home') ?>" class="btn btn-primary">Volver al inicio</a>
    </div>
</body>
</html>
