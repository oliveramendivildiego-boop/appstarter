<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($pdf_title ?? 'Reporte') ?></title>
    <style>
        @page { margin: 11mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #111;
            line-height: 1.35;
            margin: 0;
        }
        h1 {
            font-size: 13pt;
            margin: 0 0 4px 0;
            font-weight: 700;
        }
        .meta {
            font-size: 7.5pt;
            color: #444;
            margin-bottom: 10px;
        }
        h2 {
            font-size: 10pt;
            margin: 12px 0 5px 0;
            border-bottom: 1px solid #999;
            padding-bottom: 2px;
        }
        table.pdf-t {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 7.5pt;
        }
        table.pdf-t th, table.pdf-t td {
            border: 1px solid #333;
            padding: 3px 4px;
            text-align: left;
            vertical-align: top;
        }
        table.pdf-t th {
            background: #e8e8e8;
            font-weight: 700;
        }
        .text-end { text-align: right; }
        .small { font-size: 7pt; color: #555; }
        .mb-1 { margin-bottom: 4px; }
        .alert-box {
            border: 1px solid #666;
            padding: 6px 8px;
            margin-bottom: 10px;
            font-size: 7.5pt;
            background: #f5f5f5;
        }
    </style>
</head>
<body>
<h1><?= esc($pdf_title ?? '') ?></h1>
<div class="meta"><?= esc($pdf_company ?? '') ?> · <?= esc($pdf_subtitle ?? '') ?> · Generado <?= esc($pdf_generated ?? '') ?></div>
<?= $pdf_content ?? '' ?>
</body>
</html>
