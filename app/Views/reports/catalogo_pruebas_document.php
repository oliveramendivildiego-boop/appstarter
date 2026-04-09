<?php
$show_toolbar = ! empty($show_toolbar);
$categories   = $categories ?? [];
$totalCats    = count($categories);
$totalHijos   = 0;
foreach ($categories as $c) {
    $totalHijos += count($c['items'] ?? []);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo de pruebas — <?= esc($company_name ?? 'Laboratorio') ?></title>
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
        h2 {
            font-size: 11pt;
            margin: 14px 0 6px 0;
            border-bottom: 1px solid #333;
            padding-bottom: 2px;
        }
        .muted { color: #555; font-size: 9pt; }
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
        .cat-meta { font-size: 8.5pt; color: #444; font-weight: normal; }
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
        .no-print a.excel-btn {
            display: inline-block;
            padding: 6px 12px;
            background: #198754;
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
    <a href="<?= site_url('reports') ?>">← Reportes</a>
    <button type="button" onclick="window.print()">Imprimir</button>
    <a class="pdf-btn" href="<?= site_url('reports/catalogoPruebasPdf') ?>">Descargar PDF</a>
    <a class="excel-btn" href="<?= site_url('reports/catalogoPruebasExcel') ?>">Exportar Excel</a>
</div>
<?php endif; ?>

<h1>Catálogo de pruebas</h1>
<p class="muted"><?= esc($company_name ?? 'Laboratorio') ?> · Generado: <?= esc($generado_en ?? '') ?></p>
<p class="muted"><?= (int) $totalCats ?> grupo(s) · <?= (int) $totalHijos ?> análisis listado(s)</p>

<?php foreach ($categories as $cat): ?>
    <?php
    $cid = (int) ($cat['id'] ?? 0);
    $cname = (string) ($cat['name'] ?? '');
    $items = $cat['items'] ?? [];
    ?>
    <h2>
        <?= esc($cname) ?>
        <span class="cat-meta">(ID grupo <?= $cid ?>)</span>
    </h2>
    <?php if ($items === []): ?>
        <p class="muted" style="margin: 4px 0 12px;">Sin análisis activos en este grupo.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:8%">ID</th>
                    <th>Análisis</th>
                    <th style="width:12%">Tipo prueba</th>
                    <th style="width:18%">Tipo de muestra</th>
                    <th style="width:18%">Método</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                $pid = (int) ($it['id'] ?? 0);
                $pname = (string) ($it['name'] ?? '');
                $compleja = (int) ($it['compleja'] ?? 0) === 1;
                $tm = (string) ($it['tipo_muestra'] ?? '');
                $me = (string) ($it['metodo'] ?? '');
                ?>
                <tr>
                    <td><?= $pid ?></td>
                    <td><?= esc($pname) ?></td>
                    <td><?= $compleja ? 'Compuesta' : 'Simple' ?></td>
                    <td><?= $tm !== '' ? esc($tm) : '—' ?></td>
                    <td><?= $me !== '' ? esc($me) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endforeach; ?>

<?php if ($categories === []): ?>
    <p class="muted">No hay categorías de prueba configuradas.</p>
<?php endif; ?>

<div class="footer-note">
    Incluye solo grupos y análisis no eliminados (activos en el catálogo). El orden coincide con el configurado en Pruebas de laboratorio.
</div>

</body>
</html>
