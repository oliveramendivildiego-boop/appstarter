<?php
helper('layout');
$layoutCfg   = layout_config();
$companyName = ! empty($layoutCfg['company']) ? $layoutCfg['company'] : 'Laboratorio';
$message     = $message ?? 'El enlace no es válido o ha expirado. Solicite un nuevo enlace al laboratorio.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Enlace no disponible - <?= esc($companyName) ?></title>
    <link rel="stylesheet" href="<?= base_url('css/vendor/bootstrap.min.css') ?>" />
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
<div class="container py-5">
    <div class="mx-auto bg-white shadow-sm rounded p-4" style="max-width: 480px;">
        <h1 class="h5 text-danger mb-3">No se puede mostrar el reporte</h1>
        <p class="mb-0 text-muted"><?= esc($message) ?></p>
    </div>
</div>
</body>
</html>
