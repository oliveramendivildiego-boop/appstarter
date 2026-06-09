<?php
$show_toolbar = ! empty($show_toolbar);
$equipo = $equipo ?? [];
$mantenimientos = $mantenimientos ?? [];
$totalMant = count($mantenimientos);
$equipoId = (int) ($equipo['equipo_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historial de mantenimientos — <?= esc($equipo['nombre'] ?? 'Equipo') ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #111;
            margin: 12mm 14mm;
            line-height: 1.35;
        }
        h1 {
            font-size: 16pt;
            margin: 0 0 4px 0;
            font-weight: 700;
        }
        .muted { color: #555; font-size: 9pt; }
        .equipo-info {
            border: 1px solid #333;
            padding: 10px 12px;
            margin: 12px 0;
            background: #f9f9f9;
        }
        .equipo-info strong { display: inline-block; min-width: 7em; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #333;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th { background: #e8e8e8; font-weight: 600; }
        .footer-note {
            margin-top: 16px;
            padding-top: 10px;
            border-top: 1px solid #999;
            font-size: 8pt;
            color: #444;
        }
        .no-print {
            margin-bottom: 16px;
            padding: 10px;
            background: #eef6ff;
            border: 1px solid #b8d4f0;
            border-radius: 4px;
        }
        .no-print a, .no-print button {
            margin-right: 8px;
            margin-bottom: 4px;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 10mm; }
        }
        .no-print button {
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 4px;
            border: 1px solid #0d6efd;
            background: #0d6efd;
            color: #fff;
            font-size: 14px;
        }
        .no-print a.pdf-btn {
            display: inline-block;
            padding: 6px 12px;
            background: #dc3545;
            color: #fff !important;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<?php if ($show_toolbar): ?>
<div class="no-print">
    <a href="<?= site_url('equipos/detalle/' . $equipoId) ?>">← Volver al equipo</a>
    <button type="button" onclick="window.print()">Imprimir</button>
    <a class="pdf-btn" href="<?= site_url('equipos/historialPdf/' . $equipoId) ?>">Descargar PDF</a>
</div>
<?php endif; ?>

<h1>Historial de mantenimientos</h1>
<p class="muted"><?= esc($company_name ?? 'Laboratorio') ?> · Generado: <?= esc($generado_en ?? '') ?></p>

<div class="equipo-info">
    <div><strong>Equipo:</strong> <?= esc($equipo['nombre'] ?? '') ?></div>
    <div><strong>Código:</strong> <?= esc($equipo['codigo'] ?? '-') ?></div>
    <div><strong>Ubicación:</strong> <?= esc($equipo['ubicacion'] ?? '-') ?></div>
</div>

<p class="muted"><?= (int) $totalMant ?> mantenimiento(s) registrado(s)</p>

<?php if ($totalMant === 0): ?>
    <p class="muted">No hay mantenimientos registrados para este equipo.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width:18%">Fecha</th>
                <th style="width:18%">Tipo</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mantenimientos as $m): ?>
            <tr>
                <td><?= esc($m['fecha'] ?? '') ?></td>
                <td><?= (int) ($m['tipo'] ?? 1) === 1 ? 'Preventivo' : 'Correctivo' ?></td>
                <td><?= esc($m['descripcion'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p class="footer-note">Documento generado por el módulo de equipos.</p>

<?php if ($show_toolbar): ?>
<script>
(function() {
    function openPrintDialog() {
        window.print();
    }
    window.addEventListener('afterprint', function() {
        setTimeout(function() {
            if (window.opener) {
                window.close();
            }
        }, 150);
    });
    if (document.readyState === 'complete') {
        setTimeout(openPrintDialog, 350);
    } else {
        window.addEventListener('load', function() {
            setTimeout(openPrintDialog, 350);
        });
    }
})();
</script>
<?php endif; ?>
</body>
</html>
